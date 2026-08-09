# Pola Vue di Inertia-Laravel

Setelah paham directive ([01](./01-vue-directives-vs-blade.md)) dan reactivity ([02](./02-reactivity-fundamentals.md)), sekarang kita lihat helper & pola spesifik yang membedakan **Vue di Inertia** vs **Vue standalone**.

---

## 1. Komponen `Link` (Pengganti `<a href>`)

`<Link>` = wrapper Inertia untuk `<a>` yang melakukan **client-side navigation** (tanpa full reload). Mirip `route()` di Blade tapi + transisi halaman instan.

```vue
<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <!-- Navigasi ke route Laravel -->
  <Link href="/dashboard">Dashboard</Link>
  <Link :href="route('users.show', user.id)">Lihat User</Link>

  <!-- Method selain GET -->
  <Link href="/logout" method="post" as="button">Logout</Link>
  <Link href="/posts/1" method="delete" as="button" class="text-red">
    Hapus
  </Link>
</template>
```

**Properti penting**:

| Prop | Fungsi |
|---|---|
| `href` | URL / named route |
| `method` | HTTP method: `get` (default), `post`, `put`, `patch`, `delete` |
| `as` | Render sebagai: `a` (default) atau `button` |
| `data` | Object payload (untuk POST/PUT) |
| `headers` | Custom headers |
| `preserve-scroll` | Jaga posisi scroll setelah navigasi |
| `preserve-state` | Jaga state komponen (form, scroll position internal) |
| `only` / `except` | Ambil props tertentu saja dari controller |
| `replace` | Pakai `history.replaceState` (tidak menambah history entry) |

```vue
<Link href="/search?q=foo" preserve-scroll>Search</Link>
```

**Vs Blade**:
```blade
<a href="{{ route('users.show', $user) }}">Lihat User</a>
<form action="{{ route('logout') }}" method="POST">@csrf<button>Logout</button></form>
```

Di Inertia, satu `<Link>` bisa untuk banyak hal yang di Blade butuh form terpisah.

---

## 2. Komponen `Head` (Meta Tag)

Menggantikan `title` / meta di Blade. Reactive: saat `title` berubah, `<title>` di head ikut berubah.

```vue
<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
  <Head title="Dashboard - MyApp" />
  <Head>
    <title>Halaman Detail</title>
    <meta name="description" content="...">
  </Head>
</template>
```

**Vs Blade**:
```blade
@extends('layout', ['title' => 'Dashboard'])
@section('title', 'Dashboard - MyApp')
```

Di Vue, tidak perlu `@section`/`@yield` — pakai `<Head>` di mana pun di template.

---

## 3. `usePage()` — Akses Global Props

Setiap halaman yang di-render via `Inertia::render()` otomatis punya `$page.props` yang berisi shared data (auth user, flash message, dll).

```vue
<script setup>
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash);
</script>

<template>
  <div v-if="user">Halo, {{ user.name }}!</div>
  <div v-if="flash.success" class="alert">{{ flash.success }}</div>
</template>
```

**Setara Blade**: di Blade, variabel global ini harus di-share manual via `View::share()` atau `with()` di controller.

**Share data global** di Laravel:
```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return [
        'auth' => ['user' => $request->user()],
        'flash' => fn () => ['success' => $request->session()->get('success')],
    ];
}
```

---

## 4. `useForm()` — Form State dengan Validasi

`useForm` mengembalikan object reactive yang **mirip Eloquent + Validator Laravel** tapi di Vue. Plus otomatis handle error, processing state, dan progress.

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  name: '',
  email: '',
  password: '',
});

function submit() {
  form.post('/register', {
    onSuccess: () => form.reset(),
    onError:   () => console.log('gagal'),
  });
}
</script>

<template>
  <form @submit.prevent="submit">
    <input v-model="form.name" />
    <p v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</p>

    <input v-model="form.email" />
    <p v-if="form.errors.email">{{ form.errors.email }}</p>

    <button :disabled="form.processing">
      {{ form.processing ? 'Loading...' : 'Daftar' }}
    </button>
  </form>
</template>
```

**Properti & method `useForm`**:

| Properti / Method | Fungsi |
|---|---|
| `form.<field>` | Bind dengan `v-model` |
| `form.errors.<field>` | Error validasi per field |
| `form.processing` | `true` saat submit berjalan |
| `form.progress` | Object upload progress (untuk file) |
| `form.recentlySuccessful` | `true` 2 detik setelah success |
| `form.isDirty` | Ada perubahan dari nilai awal |
| `form.post(url, opts)` | Submit POST |
| `form.put / patch / delete` | Method lain |
| `form.reset()` | Reset ke nilai awal |
| `form.clearErrors()` | Hapus semua error |

**Vs Blade**:
```blade
<form method="POST" action="/register">
  @csrf
  <input name="name" value="{{ old('name') }}">
  @error('name') <p>{{ $message }}</p> @enderror
  <button>Submit</button>
</form>
```

Di Vue: validasi error **otomatis** tersedia di `form.errors.name` tanpa `old()` & `@error` directive.

---

## 5. `router` — Programmatic Navigation

Untuk navigasi via JS (bukan klik link), pakai `router`.

```js
import { router } from '@inertiajs/vue3';

// GET
router.get('/users', { search: 'budi' });
router.visit('/users', { method: 'get', data: { search: 'budi' } });

// POST
router.post('/posts', { title: '...' });

// Reload halaman saat ini
router.reload();

// Reload hanya props tertentu
router.reload({ only: ['stats'] });
```

**Penggunaan umum**: search dengan debounce.

```js
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash';

const search = ref('');

watch(search, debounce((value) => {
  router.get('/products', { q: value }, { preserveState: true });
}, 300));
```

---

## 6. Persistent Layout (Menu/Sidebar Tidak Re-render)

Secara default, setiap navigasi Inertia **mengganti seluruh halaman** (termasuk sidebar). Untuk komponen layout tetap:

```vue
<!-- Pages/Users/Index.vue -->
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
defineOptions({ layout: AdminLayout });
</script>
```

Atau via `useLayout`:

```js
import { useLayout } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

useLayout(AdminLayout);
```

Saat navigasi dari `/users` ke `/users/2`, layout (`AdminLayout`) **tidak di-unmount** — state menu, scroll, dsb tetap.

**Vs Blade**: `@extends('admin.layout')` mirip, tapi Blade selalu reload layout. Di Inertia, layout benar-benar **sticky** di Vue tree.

---

## 7. Deferred Props (Lazy Load)

Untuk data berat (misal: statistik dashboard), controller bisa return sebagai "deferred" — hanya dimuat via AJAX terpisah setelah halaman render.

```php
// Controller
return Inertia::render('Dashboard', [
    'user' => $user,            // ← synchronous, langsung ada
    'stats' => Inertia::defer(fn () => expensive_stats()),  // ← diload terpisah
]);
```

Di Vue:
```vue
<template>
  <h1>Halo, {{ user.name }}</h1>
  <div v-if="!stats">Loading stats...</div>
  <Stats v-else :data="stats" />
</template>
```

Vue otomatis update saat `stats` datang. Ini yang **tidak bisa** dilakukan Blade — Blade selalu render synchronous.

---

## 8. Partial Reload (Update Sebagian Props)

Sering dipakai untuk filter / pagination yang tidak mau reload semua data:

```vue
<Link href="/products?category=foo" preserve-scroll :only="['products']">
  Filter Kategori
</Link>
```

Atau via `router`:
```js
router.reload({ only: ['products'] });
```

Backend perlu return array asosiatif dengan key yang sama (`products`) agar partial reload bekerja.

---

## 9. Form dengan File Upload (Progress Bar)

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  title: '',
  photo: null,
});

function submit() {
  form.post('/posts', {
    forceFormData: true,   // penting untuk file
  });
}

function onFileChange(e) {
  form.photo = e.target.files[0];
}
</script>

<template>
  <form @submit.prevent="submit">
    <input v-model="form.title" />
    <input type="file" @change="onFileChange" />

    <div v-if="form.progress" class="progress">
      <div :style="{ width: form.progress.percentage + '%' }"></div>
    </div>

    <button :disabled="form.processing">Upload</button>
  </form>
</template>
```

`form.progress.percentage` adalah 0-100. **Tidak bisa** di Blade tanpa JS tambahan.

---

## 10. Merge / Append Props (Pagination, dll)

Untuk pagination, controller bisa pakai `Inertia::merge()` atau `merge()` di response:

```php
// Append (untuk infinite scroll / "load more")
return Inertia::render('Posts', [
    'posts' => Inertia::merge(fn () => Post::paginate(10)),
]);
```

Vue bisa baca `props.posts.data`, `props.posts.links`, dll.

---

## 11. Validasi dengan Custom Rule + Error UX

```php
// Controller
$request->validate([
    'email' => 'required|email|unique:users,email',
    'password' => 'required|min:8|confirmed',
]);
```

```vue
<template>
  <input
    v-model="form.email"
    :class="{ 'border-red': form.errors.email }"
  />
  <p v-if="form.errors.email">{{ form.errors.email }}</p>
</template>
```

Error otomatis dipopulate dari `Validator` Laravel ke `form.errors`. Plus `form.reset('password_field')` untuk clear field tertentu setelah error.

---

## 12. Pola Umum dalam Studi Kasus

Berikut snippet yang akan sering muncul di studi kasus:

### Fetch + loading state

```vue
<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const items = ref([]);
const loading = ref(true);

onMounted(async () => {
  // opsi 1: Inertia (props)
  // (data sudah ada di props dari controller)

  // opsi 2: fetch manual
  loading.value = true;
  try {
    items.value = await fetch('/api/items').then(r => r.json());
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div v-if="loading">Loading...</div>
  <ul v-else>
    <li v-for="item in items" :key="item.id">{{ item.name }}</li>
  </ul>
</template>
```

### Optimistic Update (UI update sebelum server konfirmasi)

```js
function toggleLike(postId) {
  const original = posts.value.find(p => p.id === postId);
  original.liked = !original.liked;   // UI update langsung

  router.post(`/posts/${postId}/like`, {}, {
    onError: () => { original.liked = !original.liked; }   // rollback
  });
}
```

Pola ini sangat sulit di Blade (harus reload halaman).

---

## Ringkasan Cheatsheet

| Pola | Inertia/Vue | Setara Blade |
|---|---|---|
| Link ke route | `<Link :href="route('x')">` | `<a href="{{ route('x') }}">` |
| Form submit | `useForm` + `form.post()` | `<form>` + `@csrf` + `@error` |
| Validasi error | `form.errors.<field>` | `@error('field')` |
| Loading state | `form.processing` | manual JS |
| Meta title | `<Head title="...">` | `@section('title')` |
| Share data global | `HandleInertiaRequests::share()` | `View::share()` |
| Programmatic nav | `router.get('/x')` | `redirect()->route('x')` |
| Lazy load data | `Inertia::defer()` | tidak ada |
| Partial reload | `router.reload({ only: [...] })` | tidak ada |

---

## Lanjut ke

Mulai praktik di **[study-cases/01-todo-app.md](./study-cases/01-todo-app.md)** — Todo App pertama kamu dengan Inertia-Vue.
