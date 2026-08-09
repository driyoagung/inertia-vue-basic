# Vue Directives vs Blade

Cheatsheet paralel: directive Vue 3 (Composition API) ↔ Blade Laravel. Setiap directive punya analogi terdekat di Blade untuk mempercepat pemahaman.

> Aturan umum Vue: semua directive dimulai `v-` dan ditulis sebagai **atribut** HTML. Nilainya adalah **ekspresi JavaScript** (bukan string biasa, kecuali pakai `v-bind` untuk atribut string).

---

## 1. Rendering Kondisional

### `v-if` / `v-else-if` / `v-else`

| Blade | Vue |
|---|---|
| `@if ($user)` | `<div v-if="user">` |
| `@elseif ($role)` | `<div v-else-if="role">` |
| `@else` | `<div v-else>` |
| `@auth` | `<div v-if="$page.props.auth.user">` |
| `@guest` | `<div v-if="!$page.props.auth.user">` |

```vue
<template>
  <div v-if="status === 'loading'">Loading...</div>
  <div v-else-if="status === 'error'">Error!</div>
  <div v-else>{{ data }}</div>
</template>
```

**Catatan penting**:
- `v-if` benar-benar **membuat / menghapus elemen DOM**. Saat kondisi jadi `false`, elemen di-`unmount`.
- Tidak seperti Blade, `v-if` di Vue **reactive** — ketika nilainya berubah, Vue otomatis render ulang bagian itu.
- Wajib: `v-else` harus **langsung mengikuti** `v-if` (tidak boleh ada elemen lain di antara).

### `v-show`

Setara `display: none` via CSS, **tidak** menghapus dari DOM.

```vue
<h1 v-show="isVisible">Halo</h1>   <!-- DOM tetap ada, hidden jika false -->
<div v-if="isVisible">Halo</div>    <!-- DOM dihapus jika false -->
```

**Kapan pakai mana?**
- `v-if`: kondisi jarang berubah, atau inisialisasi komponen berat. (Lebih hemat memori.)
- `v-show`: toggle sering (misal tab, modal yang sering dibuka-tutup). (Lebih hemat CPU.)

### `v-cloak`

Mencegah template "kedip" sebelum Vue di-mount.

```vue
<template>
  <div v-cloak>{{ message }}</div>
</template>

<style>
[v-cloak] { display: none; }
</style>
```

Setara: Blade `{{-- placeholder --}}` tapi untuk Vue. Hampir tidak dibutuhkan di Inertia karena Vue di-mount sangat cepat, tapi berguna untuk fallback.

---

## 2. Rendering List

### `v-for`

| Blade | Vue |
|---|---|
| `@foreach ($items as $item)` | `<li v-for="item in items">` |
| `@foreach ($items as $key => $item)` | `<li v-for="(item, key) in items">` |
| `@for ($i = 0; $i < 10; $i++)` | `<li v-for="i in 10">` |
| `loop->index` | argumen ke-3 `index` |

```vue
<template>
  <ul>
    <li v-for="(user, index) in users" :key="user.id">
      {{ index + 1 }}. {{ user.name }}
    </li>
  </ul>
</template>
```

**Wajib**: gunakan **`:key`** (singkat dari `v-bind:key`) dengan nilai unik. Tanpa `key`, Vue tidak bisa diff list dengan benar → bug render & warning.

```vue
<!-- JANGAN -->
<li v-for="user in users">{{ user.name }}</li>

<!-- BENAR -->
<li v-for="user in users" :key="user.id">{{ user.name }}</li>
```

> Blade tidak butuh "key" karena rendering ulang dari nol setiap request. Vue **mendaur ulang** elemen DOM, jadi butuh identitas.

### `v-for` dengan `v-if`

Jangan digabung di elemen yang sama (Vue akan error). Pakai `<template>` wrapper atau filter lewat `computed`.

```vue
<!-- SALAH -->
<li v-for="u in users" v-if="u.active" :key="u.id">

<!-- BENAR opsi 1: template -->
<template v-for="u in users" :key="u.id">
  <li v-if="u.active">{{ u.name }}</li>
</template>

<!-- BENAR opsi 2: computed (lebih idiomatic) -->
<li v-for="u in activeUsers" :key="u.id">
```

```js
const activeUsers = computed(() => users.value.filter(u => u.active));
```

---

## 3. Interpolasi & Output

| Blade | Vue | Keterangan |
|---|---|---|
| `{{ $name }}` | `{{ name }}` | HTML-escaped, sama persis |
| `{!! $html !!}` | `v-html="html"` | Render raw HTML (awas XSS!) |
| `{{ Str::limit($text, 50) }}` | `{{ text.slice(0, 50) }}` | Method JS, bukan helper Laravel |
| `{{ $date->format('d M Y') }}` | pakai `dayjs` / `Intl.DateTimeFormat` | Format tanggal di JS |

```vue
<template>
  <p>{{ user.name }}</p>           <!-- aman, escaped -->
  <div v-html="trustedHtml"></div> <!-- raw, jangan untuk input user! -->
</template>
```

**Jangan pernah** `v-html` dari input user tanpa sanitasi. Sama risikonya seperti `{!! $input !!}` di Blade.

---

## 4. Attribute Binding

### `v-bind` (shorthand `:`)

| Blade | Vue |
|---|---|
| `<a href="{{ route('x') }}">` | `<a :href="route('x')">` |
| `<img src="{{ $url }}">` | `<img :src="url">` |
| `<div class="foo {{ $active ? 'on' : '' }}">` | `<div :class="{ on: active }">` |
| `<input :value="$val">` (Echo) | `<input :value="val">` |
| `<input @disabled($cond)>` | `<input :disabled="cond">` |

```vue
<template>
  <a :href="url" :class="['btn', { 'btn-primary': isPrimary }]" :disabled="loading">
    Klik
  </a>
</template>
```

### Binding Khusus: `class` & `style`

Sintaks object/array adalah kekuatan Vue:

```vue
<!-- class binding -->
<div :class="{ active: isActive, 'text-red': hasError }"></div>
<div :class="[isActive && 'active', errorClass]"></div>

<!-- style binding -->
<div :style="{ color: textColor, fontSize: size + 'px' }"></div>
```

Setara Blade:

```blade
<div class="{{ $isActive ? 'active' : '' }} {{ $hasError ? 'text-red' : '' }}"></div>
```

Vue lebih clean untuk toggle banyak class.

---

## 5. Event Handling

### `v-on` (shorthand `@`)

| Blade | Vue |
|---|---|
| `<form method="POST" action="...">` | `<form @submit.prevent="save">` |
| `<a href="..." onclick="...">` | `<a @click.prevent="go" href="...">` |
| `<input onkeyup="...">` | `<input @keyup.enter="submit">` |
| `<input oninput="...">` | `<input @input="onChange">` |

```vue
<template>
  <button @click="increment">+</button>
  <button @click="decrement($event)">-</button>
  <form @submit.prevent="save">
    <input v-model="name" @keyup.enter="save" />
  </form>
</template>
```

### Modifier Penting

| Modifier | Fungsi | Setara Blade |
|---|---|---|
| `.prevent` | `event.preventDefault()` | tidak ada, harus `e.preventDefault()` JS |
| `.stop` | `event.stopPropagation()` | tidak ada |
| `.once` | trigger hanya sekali | tidak ada |
| `.self` | trigger hanya jika target = `event.target` | tidak ada |
| `.passive` | optimize untuk scroll | tidak ada |
| `.enter` / `.esc` / `.tab` | filter key tertentu | tidak ada |
| `.left` / `.right` / `.middle` | filter mouse button | tidak ada |
| `.ctrl` / `.shift` / `.alt` / `.meta` | modifier key | tidak ada |

```vue
<form @submit.prevent="save">
<input @keyup.enter="submit">
<div @click.ctrl.exact="openMenu">       <!-- Ctrl+klik saja, tanpa shift dll -->
```

---

## 6. Two-way Binding: `v-model`

**Tidak ada padanan langsung di Blade** — ini fitur khas Vue. Blade selalu "server push", Vue bisa "user push ke state".

```vue
<input v-model="name">              <!-- text -->
<input v-model="agree" type="checkbox">  <!-- boolean -->
<select v-model="selected">
  <option value="a">A</option>
</select>
<input type="radio" value="x" v-model="picked">
```

### Modifier `v-model`

| Modifier | Fungsi |
|---|---|
| `.lazy` | sync pada `@change` (default: setiap `@input`) |
| `.number` | cast value ke `Number` |
| `.trim` | trim whitespace |

```vue
<input v-model.lazy="description">   <!-- tidak update tiap ketukan -->
<input v-model.number="age">         <!-- pasti number -->
<input v-model.trim="username">      <!-- auto-trim -->
```

### `v-model` pada Component

`v-model` di component = gabungan `:modelValue` + `@update:modelValue`. Misal `<MyInput v-model="text" />` di child:

```vue
<!-- child -->
<input :value="modelValue" @input="$emit('update:modelValue', $event.target.value)">
```

```js
defineProps(['modelValue']);
defineEmits(['update:modelValue']);
```

---

## 7. Slot

**Setara parsial**: Blade `@yield` / `@include` dengan `@slot`. Vue slot lebih eksplisit.

```vue
<!-- parent -->
<Card>
  <h1>Judul</h1>          <!-- default slot -->
  <template #footer>      <!-- named slot -->
    <button>OK</button>
  </template>
</Card>

<!-- child (Card.vue) -->
<template>
  <div class="card">
    <slot />              <!-- default slot output -->
    <slot name="footer" />
  </div>
</template>
```

Blade:
```blade
{{-- parent --}}
@include('card', ['slot' => '<h1>Judul</h1>', 'footer' => '<button>OK</button>'])

{{-- child (resources/views/components/card.blade.php) --}}
<div class="card">
  {{ $slot }}
  @isset($footer) {!! $footer !!} @endisset
</div>
```

Vue lebih fleksibel: slot bisa berisi komponen / directive, Blade slot hanya string HTML.

### Scoped Slot

```vue
<!-- child -->
<slot :user="user" :index="i" />

<!-- parent -->
<Card v-for="(u, i) in users" :key="u.id" v-slot="{ user, index }">
  {{ index }}. {{ user.name }}
</Card>
```

---

## 8. Directive Lanjutan

### `v-pre`

Skip kompilasi Vue untuk blok ini. Berguna saat menampilkan `{{ }}` Vue di dalam teks.

```vue
<span v-pre>{{ ini bukan Vue, tampil literal }}</span>
```

### `v-once`

Render sekali, tidak pernah re-render walaupun data berubah. Optimalisasi untuk konten statis.

```vue
<h1 v-once>{{ judulHalaman }}</h1>
```

### `v-memo` (Vue 3.2+)

Skip re-render jika dependensi tidak berubah. Mirip `React.memo`.

```vue
<div v-memo="[item.id, item.edited]">  <!-- hanya re-render kalau id/edited berubah -->
  {{ item.content }}
</div>
```

### `v-is` (untuk custom element)

```vue
<div v-is="'my-component'"></div>
```

---

## 9. Custom Directive

Vue memungkinkan buat directive sendiri (`v-focus`, `v-tooltip`, dll). Contoh: auto-focus input:

```js
// directives/focus.js
export const vFocus = {
  mounted: (el) => el.focus(),
};
```

```js
// app.js
import { vFocus } from './directives/focus';
app.directive('focus', vFocus);
```

```vue
<input v-focus>
```

**Lifecycle hooks directive**: `created`, `beforeMount`, `mounted`, `beforeUpdate`, `updated`, `beforeUnmount`, `unmounted`.

Setara Blade: `@directive('focus')` via Service Provider. Vue lebih ergonomis untuk efek UI.

---

## 10. Ringkasan Tabel Cepat

| Directive Vue | Shorthand | Tujuan | Setara Blade |
|---|---|---|---|
| `v-if` / `v-else-if` / `v-else` | — | Kondisional | `@if` / `@else` |
| `v-show` | — | Toggle display | `@if` + CSS |
| `v-for` | — | Loop | `@foreach` |
| `v-bind` | `:` | Bind atribut | `{{ }}` di atribut |
| `v-on` | `@` | Event listener | `onclick="..."` |
| `v-model` | — | Two-way binding | — |
| `v-slot` | `#` | Slot | `@yield` / `@slot` |
| `v-html` | — | Raw HTML | `{!! !!}` |
| `v-text` | — | Set textContent | `{{ }}` |
| `v-pre` | — | Skip kompilasi | `{{-- komentar --}}` |
| `v-once` | — | Render sekali | — |
| `v-cloak` | — | Anti-flicker | — |
| `v-memo` | — | Memoization | — |

---

## Catatan Penutup

- Semua directive Vue **evaluasi di sisi browser** (client). Blade = server. Jangan bingung: `{{ $user }}` di Blade = data dari PHP; `{{ user }}` di Vue = data dari `<script setup>` di file yang sama.
- Directive Vue **reactive**: jika state (`ref` / `reactive`) berubah, bagian yang dependensi dengannya otomatis re-render. Blade tidak punya konsep ini.
- Untuk kasus kompleks (kondisi + loop + binding), **pecah jadi computed & komponen kecil** daripada directive bertumpuk.

Lanjut ke **[02-reactivity-fundamentals.md](./02-reactivity-fundamentals.md)** untuk memahami *kenapa* directive bisa reactive.
