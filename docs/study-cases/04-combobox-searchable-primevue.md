# Study Case 4: Combobox Select Option dengan Pencarian (PrimeVue)

Tujuan: belajar membangun **combobox / select option yang searchable** di Inertia + Vue 3, dengan dua skenario:

1. **Client-side filter** — data kecil (kategori), filter langsung di browser.
2. **Server-side search** — data besar (user), setiap ketukan tombol memanggil API.

Studi kasus ini fokus pada **PrimeVue** sebagai UI library, tapi pola fetch data + Inertia juga relevan untuk komponen lain.

> Jalankan `php artisan serve` & `npm run dev`, lalu buka `http://127.0.0.1:8000/tasks`.

---

## Vue Features yang Dipelajari

| Fitur Vue / Inertia / PrimeVue | Dipakai untuk |
|---|---|
| `defineProps` | terima data `tasks`, `categories`, `users` dari controller |
| `ref` | state reaktif untuk form & daftar suggestion |
| `watch` | auto-clear flash message |
| `router.post` + `onSuccess` / `onError` | submit form ke endpoint Laravel |
| `usePage` | baca `props.flash` (flash message dari `back()->with()`) |
| PrimeVue `<Select filter>` | combobox dengan filter built-in (client-side) |
| PrimeVue `<AutoComplete>` | combobox yang trigger API setiap ketukan (server-side) |
| PrimeVue `Tag`, `Message`, `Button`, `InputText`, `Textarea` | UI pembangun form |
| Slot template (`#option`, `#value`) | custom render item di dropdown |
| `force-selection` | batasi input user hanya ke opsi yang ada |
| Race-condition guard (`searchSeq`) | abaikan response lama saat user mengetik cepat |

---

## Backend: Persiapan

### 1. Install Library Frontend

```bash
npm install primevue@^4 primeicons @primeuix/themes
```

- `primevue` — komponen UI
- `primeicons` — icon set (`pi pi-save`, `pi pi-user`, dll)
- `@primeuix/themes` — preset theme (di PrimeVue v4, theme terpisah dari core)

---

### 2. Daftarkan PrimeVue ke `app.js`

**File: `resources/js/app.js`**

```js
import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';   // preset theme
import 'primeicons/primeicons.css';          // icon font

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)                      // plugin Inertia
            .use(PrimeVue, {                  // plugin PrimeVue
                theme: {
                    preset: Aura,             // gunakan theme Aura
                    options: {
                        darkModeSelector: '.app-dark', // opsional: dark mode
                    },
                },
                ripple: true,                 // efek ripple pada button
            })
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
```

**Penjelasan sintaks penting:**

| Kode | Fungsi |
|---|---|
| `createInertiaApp({...})` | Inisialisasi Inertia. Wajib ada `setup` untuk mounting Vue. |
| `.use(plugin)` | Daftarkan Inertia plugin supaya `<Link>`, `Head`, `useForm`, dll bisa dipakai di semua komponen. |
| `.use(PrimeVue, { theme: { preset: Aura } })` | Daftarkan PrimeVue secara global. Sekali daftarkan, semua komponen PrimeVue (`Select`, `AutoComplete`, dll) bisa di-import langsung di mana saja. |
| `ripple: true` | Animasi ripple (efek klik) pada komponen Button & interaktif. |
| `import.meta.glob('./Pages/**/*.vue')` | Vite trick: me-load semua file `.vue` di `Pages/` secara lazy. |

---

### 3. Migration: `categories`

**File: `database/migrations/..._create_categories_table.php`**

```bash
php artisan make:model Category -m
```

```php
public function up(): void
{
    Schema::create('categories', function (Blueprint $table) {
        $table->id();
        $table->string('name');                 // nama kategori
        $table->string('slug')->unique();       // slug unik (untuk URL/identifier)
        $table->string('color')->default('#3b82f6');  // warna badge (UI)
        $table->timestamps();
    });
}
```

**Penjelasan sintaks:**

| Kode | Fungsi |
|---|---|
| `$table->id()` | bigint auto increment primary key. |
| `$table->string('name')` | kolom VARCHAR(255). |
| `$table->string('slug')->unique()` | kolom dengan constraint unique di level DB. |
| `$table->string('color')->default('#3b82f6')` | nilai default jika tidak diisi. |
| `$table->timestamps()` | tambah `created_at` & `updated_at` otomatis. |

---

### 4. Migration: `tasks`

**File: `database/migrations/..._create_tasks_table.php`**

```bash
php artisan make:model Task -m
```

```php
public function up(): void
{
    Schema::create('tasks', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('description')->nullable();   // boleh kosong
        $table->foreignId('category_id')
            ->nullable()
            ->constrained('categories')             // FK ke tabel categories
            ->nullOnDelete();                       // kalau category dihapus, set null
        $table->foreignId('assignee_id')
            ->nullable()
            ->constrained('users')                  // FK ke tabel users
            ->nullOnDelete();
        $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
        $table->enum('status', ['pending', 'in_progress', 'done'])->default('pending');
        $table->timestamps();
    });
}
```

**Penjelasan sintaks:**

| Kode | Fungsi |
|---|---|
| `$table->text('description')->nullable()` | `TEXT` (lebih panjang dari VARCHAR), `nullable` artinya boleh NULL. |
| `$table->foreignId('category_id')` | `BIGINT UNSIGNED` + relasi FK. |
| `->constrained('categories')` | otomatis membuat FK constraint + index. Tanpa parameter, Laravel menebak tabel target dari nama kolom (`category_id` → `categories`). |
| `->nullOnDelete()` | ketika parent (category) dihapus, kolom ini di-set NULL (bukan cascade). |
| `$table->enum(...)` | kolom ENUM MySQL — hanya boleh salah satu dari nilai tersebut. Cocok untuk nilai tetap seperti priority/status. |

---

### 5. Model: `Category`

**File: `app/Models/Category.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'color'];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
```

**Penjelasan sintaks:**

| Kode | Fungsi |
|---|---|
| `protected $fillable` | whitelist kolom yang boleh diisi via `Category::create([...])` / mass assignment. Tanpa ini, `MassAssignmentException` akan terlempar. |
| `hasMany(Task::class)` | relasi one-to-many: satu Category punya banyak Task. Akses via `$category->tasks`. |

---

### 6. Model: `Task`

**File: `app/Models/Task.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $fillable = [
        'title', 'description', 'category_id',
        'assignee_id', 'priority', 'status',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
```

**Penjelasan sintaks:**

| Kode | Fungsi |
|---|---|
| `belongsTo(Category::class)` | relasi inverse: Task milik satu Category. Akses via `$task->category`. |
| `belongsTo(User::class, 'assignee_id')` | argumen kedua adalah **foreign key** (karena nama method `assignee` ≠ nama kolom `assignee_id`). |

**Eager loading** di controller (lihat di bawah) akan menggunakan relasi ini:
```php
Task::with(['category', 'assignee'])->get();
```

---

### 7. Seeder

**File: `database/seeders/CategorySeeder.php`**

```php
public function run(): void
{
    $categories = [
        ['name' => 'Frontend Development', 'color' => '#3b82f6'],
        ['name' => 'Backend Development',  'color' => '#10b981'],
        // ... dst
    ];

    foreach ($categories as $cat) {
        Category::updateOrCreate(
            ['slug' => Str::slug($cat['name'])],  // kondisi pencarian
            ['name' => $cat['name'], 'color' => $cat['color']],  // data yang disimpan
        );
    }
}
```

| Kode | Fungsi |
|---|---|
| `Str::slug($cat['name'])` | konversi "Frontend Development" → "frontend-development". |
| `updateOrCreate([...], [...])` | jika record dengan `slug` tertentu sudah ada → update; jika belum → insert. Idempotent untuk seeder. |

**File: `database/seeders/DemoComboboxSeeder.php`**

```php
public function run(): void
{
    if (User::count() === 0) {
        User::factory()->count(8)->create();   // factory User bikin 8 user dummy
    }

    $categories = Category::all();
    $users = User::all();

    if ($categories->isEmpty() || $users->isEmpty()) {
        return;
    }

    foreach ($tasks as $task) {
        Task::create([
            'title' => $task['title'],
            'category_id' => $categories->random()->id,
            'assignee_id' => $users->random()->id,
            'priority' => $task['priority'],
            'status' => $task['status'],
        ]);
    }
}
```

| Kode | Fungsi |
|---|---|
| `User::factory()->count(8)->create()` | buat 8 user dari factory (otomatis isi nama, email, password). |
| `$categories->random()` | ambil elemen random dari Collection. |
| `Task::create([...])` | mass-insert. Berhasil karena semua kolom ada di `$fillable`. |

Jalankan dengan:
```bash
php artisan migrate --seed
```

---

### 8. Controller: `TaskController`

**File: `app/Http/Controllers/TaskController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(): Response
    {
        $tasks = Task::with(['category', 'assignee'])
            ->latest()
            ->limit(25)
            ->get();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'categories' => Category::orderBy('name')->get(['id', 'name', 'color']),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'priority'    => ['required', 'in:low,medium,high'],
            'status'      => ['required', 'in:pending,in_progress,done'],
        ]);

        Task::create($data);

        return back()->with('success', 'Task berhasil dibuat.');
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($w) use ($query) {
                    $w->where('name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email']);

        return response()->json(['data' => $users]);
    }
}
```

**Penjelasan sintaks:**

| Kode | Fungsi |
|---|---|
| `Inertia::render('Tasks/Index', [...])` | render Vue component `Pages/Tasks/Index.vue` dan kirim props. Props ini akan diterima sebagai `defineProps` di Vue. |
| `Task::with(['category', 'assignee'])` | **Eager loading** — sekaligus load relasi. Tanpa ini, akses `$task->category` di Blade/Vue akan trigger N+1 query. |
| `->latest()` | shortcut untuk `orderBy('created_at', 'desc')`. |
| `->limit(25)` | batasi 25 row. |
| `$request->validate([...])` | validasi + auto-redirect-back dengan error bag jika gagal. Aturan `exists:categories,id` memvalidasi bahwa nilai ada di kolom `id` tabel `categories`. |
| `back()->with('success', '...')` | setelah berhasil, redirect kembali ke halaman sebelumnya. `with()` mengirim **flash data** — Inertia otomatis menyediakannya sebagai `page.props.flash.success`. |
| `->when($query !== '', fn)` | query builder conditional: hanya tambahkan `where` jika `$query` tidak kosong. |
| `where('name', 'like', "%{$query}%")` | SQL `LIKE` dengan wildcard `%` di kedua sisi = substring match. |
| `get(['id', 'name', 'email'])` | **Column selection** — hemat memory dengan tidak select kolom yang tidak dipakai. |
| `response()->json(['data' => $users])` | return JSON response (bukan Inertia). Cocok untuk endpoint API yang dipanggil via `fetch`. |

---

### 9. Routes

**File: `routes/web.php`**

```php
<?php

use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::get('/api/users/search', [TaskController::class, 'searchUsers'])->name('api.users.search');
```

| Kode | Fungsi |
|---|---|
| `Inertia::render('Welcome')` | render `Pages/Welcome.vue` sebagai root. |
| `Route::get('/tasks', ...)->name('tasks.index')` | route named — bisa dipanggil dengan `route('tasks.index')` di mana saja. |
| `Route::get('/api/users/search', ...)` | endpoint JSON untuk AutoComplete — tetap di `web.php` agar session/CSRF bisa dipakai (untuk studi kasus simple). |

---

## Frontend: Vue Component

**File: `resources/js/Pages/Tasks/Index.vue`**

### Bagian 1 — Imports & Props

```vue
<script setup>
import { ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import Select from 'primevue/select';
import AutoComplete from 'primevue/autocomplete';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Message from 'primevue/message';
import Tag from 'primevue/tag';

const props = defineProps({
    tasks:     { type: Array, required: true },
    categories:{ type: Array, required: true },
    users:     { type: Array, required: true },
});
```

| Kode | Fungsi |
|---|---|
| `<script setup>` | syntax sugar Vue 3 SFC — variable di top-level langsung tersedia di template. Tidak perlu `return { ... }`. |
| `import { ref, watch } from 'vue'` | reactivity primitives. |
| `import { Head, router, usePage } from '@inertiajs/vue3'` | `Head` untuk set `<title>`, `router` untuk navigasi & submit programmatic, `usePage` untuk akses shared props. |
| `import Select from 'primevue/select'` | import komponen PrimeVue per-baris (tree-shaking friendly). |
| `defineProps({...})` | deklarasi props. `required: true` artinya wajib dikirim dari controller (kalau tidak, console.warn). |

---

### Bagian 2 — State Form

```js
const form = ref({
    title: '',
    description: '',
    category_id: null,    // objek Category (PrimeVue Select dengan object value)
    assignee: null,       // objek User (PrimeVue AutoComplete)
    priority: 'medium',
    status: 'pending',
});

const errors = ref({});
```

| Kode | Fungsi |
|---|---|
| `ref({...})` | bungkus object jadi reactive. `form.value.title` di JS, tapi `form.title` di template (auto-unwrap). |
| `category_id: null` | awalnya null. PrimeVue Select akan bind **object** Category (bukan ID string). |
| `assignee: null` | sama — AutoComplete bind object User. |

Mengapa object, bukan ID? Karena PrimeVue `<Select>` & `<AutoComplete>` lebih cocok dengan object (otomatis render `option-label`). Kita konversi ke ID saat submit (lihat di bawah).

---

### Bagian 3 — Client-Side Search: `<Select filter>`

```vue
<Select
    v-model="form.category_id"
    :options="categories"
    option-label="name"
    placeholder="Pilih kategori"
    filter
    filter-placeholder="Cari kategori..."
    :invalid="!!errors.category_id"
    class="w-full"
>
    <template #option="{ option }">
        <div class="flex items-center gap-2">
            <span
                class="inline-block h-2.5 w-2.5 rounded-full"
                :style="{ backgroundColor: option.color }"
            ></span>
            <span>{{ option.name }}</span>
        </div>
    </template>
    <template #value="{ value }">
        <div v-if="value" class="flex items-center gap-2">
            <span
                class="inline-block h-2.5 w-2.5 rounded-full"
                :style="{ backgroundColor: value.color }"
            ></span>
            <span>{{ value.name }}</span>
        </div>
        <span v-else class="text-gray-400">Pilih kategori</span>
    </template>
</Select>
```

**Penjelasan sintaks:**

| Kode | Fungsi |
|---|---|
| `v-model="form.category_id"` | two-way binding. Saat user pilih item, `form.category_id` jadi object Category. |
| `:options="categories"` | array sumber data. |
| `option-label="name"` | field yang ditampilkan. PrimeVue otomatis render ini di tiap baris dropdown. |
| `filter` | aktifkan **built-in search**. Filter terjadi **di client-side** (browser), cocok untuk data kecil (~12 item). |
| `filter-placeholder="Cari kategori..."` | placeholder pada input search. |
| `:invalid="!!errors.category_id"` | styling merah saat ada error validasi. `!!` mengkonversi `undefined` → `false`. |
| `<template #option="{ option }">` | **named slot** PrimeVue. Override render item di dropdown. `option` adalah satu item dari `:options`. |
| `<template #value="{ value }">` | override render **selected value** di trigger button. `value` = `form.category_id`. |
| `:style="{ backgroundColor: option.color }"` | inline style dengan dynamic value. Di Vue, `background-color` jadi camelCase `backgroundColor`. |

---

### Bagian 4 — Server-Side Search: `<AutoComplete>`

```vue
<AutoComplete
    v-model="form.assignee"
    :suggestions="userSuggestions"
    @complete="searchUsers"
    option-label="name"
    placeholder="Ketik nama user..."
    class="w-full"
    :input-style="{ width: '100%' }"
    force-selection
>
    <template #option="{ option }">
        <div class="flex flex-col">
            <span class="font-medium">{{ option.name }}</span>
            <span class="text-xs text-gray-500">{{ option.email }}</span>
        </div>
    </template>
</AutoComplete>
```

**Penjelasan sintaks:**

| Kode | Fungsi |
|---|---|
| `:suggestions="userSuggestions"` | array yang akan ditampilkan di dropdown. Isinya di-update oleh `searchUsers`. |
| `@complete="searchUsers"` | event built-in. PrimeVue AutoComplete emit `complete` setiap kali user mengetik. Argumen event: `{ query: '...' }`. |
| `option-label="name"` | field yang ditampilkan. |
| `force-selection` | user **wajib** pilih dari suggestion. Input custom akan ditolak. |
| `:input-style="{ width: '100%' }"` | override style input bawaan PrimeVue supaya full-width. |
| `<template #option="{ option }">` | custom render item (sama seperti Select). |

---

### Bagian 5 — Logic `searchUsers` (Fetch API)

```js
const userSuggestions = ref([]);
let searchSeq = 0;

function searchUsers(event) {
    const query = event.query.trim();
    const seq = ++searchSeq;

    fetch(`/api/users/search?q=${encodeURIComponent(query)}`, {
        headers: { Accept: 'application/json' },
    })
        .then((r) => r.json())
        .then((json) => {
            if (seq !== searchSeq) return;  // race-condition guard
            userSuggestions.value = (json.data || []).map((u) => ({
                id: u.id,
                name: u.name,
                email: u.email,
            }));
        })
        .catch(() => {
            userSuggestions.value = [];
        });
}
```

**Penjelasan sintaks:**

| Kode | Fungsi |
|---|---|
| `userSuggestions = ref([])` | reactive array. Setiap kali di-assign ulang, dropdown re-render. |
| `searchSeq` (variabel biasa, bukan ref) | counter global. Increment setiap kali `searchUsers` dipanggil. |
| `++searchSeq` | ambil nomor urut request ini. |
| `fetch(url, { headers: { Accept: 'application/json' } })` | panggil API. Header `Accept: application/json` memicu Laravel return JSON (bukan redirect HTML). |
| `encodeURIComponent(query)` | amankan karakter spesial (spasi, `&`, dll) di URL. |
| `.then((json) => { if (seq !== searchSeq) return; ... })` | **race-condition guard**. Jika user mengetik cepat (misal "a" → "ab" → "abc"), mungkin response "a" datang **setelah** "abc". Kita bandingkan nomor urut — hanya response **terbaru** yang dipakai. |
| `.map((u) => ({ id, name, email }))` | normalisasi shape data. PrimeVue AutoComplete butuh field yang konsisten. |

---

### Bagian 6 — Submit Form

```js
function submit() {
    errors.value = {};

    router.post('/tasks', {
        title: form.value.title,
        description: form.value.description,
        category_id: form.value.category_id ? form.value.category_id.id : null,
        assignee_id: form.value.assignee ? form.value.assignee.id : null,
        priority: form.value.priority,
        status: form.value.status,
    }, {
        onSuccess: () => {
            form.value = {
                title: '',
                description: '',
                category_id: null,
                assignee: null,
                priority: 'medium',
                status: 'pending',
            };
        },
        onError: (err) => {
            errors.value = err;
        },
    });
}
```

**Penjelasan sintaks:**

| Kode | Fungsi |
|---|---|
| `router.post(url, data, options)` | submit form via Inertia. **Bukan** full-page reload — Inertia hanya swap komponen dengan response server. |
| `form.value.category_id.id` | ambil ID dari object Category. PrimeVue Select bind object, bukan scalar ID. |
| `form.value.assignee ? ... : null` | ternary: kalau belum pilih assignee, kirim `null` (bukan undefined). |
| `onSuccess: () => { ... }` | callback jika server return 200/302. Di sini kita reset form. |
| `onError: (err) => { errors.value = err }` | callback jika server return 422 (validation error). `err` adalah object `{ fieldName: ['pesan error'] }`. |
| `form.value = { ... }` | reset form dengan assign ulang object. Trik ini lebih bersih daripada reset field satu-satu. |

**Alternatif: `useForm`**

Studi kasus ini pakai `router.post` manual + `ref` karena lebih fleksibel. Untuk form sederhana, Inertia menyediakan `useForm`:

```js
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    title: '',
    // ...
});

form.post('/tasks', {
    onSuccess: () => form.reset(),
});
```

`useForm` otomatis menyediakan `form.errors`, `form.processing`, `form.recentlySuccessful`.

---

### Bagian 7 — Template Sisa

```vue
<form @submit.prevent="submit" class="grid gap-5 md:grid-cols-2">
```

| Kode | Fungsi |
|---|---|
| `@submit.prevent="submit"` | `@submit` listen event submit form HTML. `.prevent` = `event.preventDefault()` (stop reload halaman). |
| `class="grid gap-5 md:grid-cols-2"` | Tailwind: 2 kolom di layar ≥768px. |

```vue
<InputText v-model="form.title" :invalid="!!errors.title" />
<small v-if="errors.title" class="text-red-600">{{ errors.title }}</small>
```

| Kode | Fungsi |
|---|---|
| `v-model` | two-way binding. |
| `errors.title` | object-shaped error bag: `{ title: 'The title field is required.' }` atau `{ title: ['msg1', 'msg2'] }`. Laravel Validator mengirim sebagai array. |
| `v-if` | render hanya kalau ada error. |

```vue
<Message v-if="flash?.success" severity="success" :closable="false">
    {{ flash.success }}
</Message>
```

| Kode | Fungsi |
|---|---|
| `flash?.success` | optional chaining. Kalau `flash` undefined, return undefined (no error). |
| `usePage().props.flash` | flash message otomatis di-attach ke setiap Inertia response. |

---

### Bagian 8 — Loop Tasks

```vue
<ul v-else class="divide-y divide-gray-100">
    <li v-for="task in tasks" :key="task.id" class="flex items-start justify-between gap-4 py-3">
        <div class="min-w-0 flex-1">
            <p class="truncate font-medium">{{ task.title }}</p>
            <p class="mt-0.5 text-xs text-gray-500">
                <span v-if="task.category">
                    <i class="pi pi-tag mr-1"></i>{{ task.category.name }}
                </span>
                <span v-if="task.assignee" class="ml-3">
                    <i class="pi pi-user mr-1"></i>{{ task.assignee.name }}
                </span>
            </p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <Tag
                :value="priorityOptions.find(p => p.value === task.priority)?.label"
                :severity="prioritySeverity(task.priority)"
            />
            <Tag :value="statusLabel(task.status)" severity="secondary" />
        </div>
    </li>
</ul>
```

| Kode | Fungsi |
|---|---|
| `v-for="task in tasks"` | loop array `tasks`. |
| `:key="task.id"` | **wajib** untuk `v-for` — bantu Vue track element saat list berubah. |
| `v-if="task.category"` | tampilkan hanya kalau relasi tidak null (FK nullable). |
| `<i class="pi pi-tag mr-1">` | icon PrimeIcons. `pi-tag` = icon tag. `mr-1` = margin-right utility Tailwind. |
| `priorityOptions.find(p => p.value === task.priority)` | cari object di array `priorityOptions` yang `value`-nya cocok. |
| `:severity="prioritySeverity(...)"` | prop PrimeVue. Menentukan warna Tag (info=blue, warn=yellow, danger=red, success=green, secondary=gray). |

---

## Kapan Pakai Yang Mana?

| Skenario | Komponen | Alasan |
|---|---|---|
| Data < 100 item, statis (kategori, roles, status) | `<Select filter>` | Tanpa round-trip ke server. Pengalaman pengguna paling cepat. |
| Data > 100 item atau sering berubah (user, produk, kota) | `<AutoComplete>` | Hanya ambil data yang relevan dengan query. Hemat bandwidth. |
| User boleh input nilai baru (misal: tag custom) | `<AutoComplete>` tanpa `force-selection` | Bebas mengetik teks bebas. |
| Single-select dengan form-like behavior | `<Select>` | Lebih cocok dengan validasi `required` & form reset. |
| Multi-select | `<Select multiple>` atau `<MultiSelect>` | PrimeVue punya keduanya. |

---

## Latihan Modifikasi

1. **Tambah field "Tags"** (multi-select). Pakai `<Select multiple>` atau `<MultiSelect>` PrimeVue.
2. **Buat fitur "Tambah Kategori Baru"** tanpa reload. Pakai `<Dialog>` + form kecil, submit via `router.post`.
3. **Server-side untuk Kategori juga**. Konversi jika kategori > 100 item.
4. **Pagination di daftar task**. Kirim `?page=` di controller, pakai Link Inertia dengan `preserve-scroll`.
5. **Edit task** (inline). Klik title → jadi `<InputText>` → submit via `router.put`.

---

## Rangkuman File yang Dibuat/Diubah

| File | Peran |
|---|---|
| `database/migrations/..._create_categories_table.php` | Schema tabel categories |
| `database/migrations/..._create_tasks_table.php` | Schema tabel tasks + FK |
| `app/Models/Category.php` | Model + relasi `hasMany` |
| `app/Models/Task.php` | Model + relasi `belongsTo` (category, assignee) |
| `database/seeders/CategorySeeder.php` | 12 kategori dummy |
| `database/seeders/DemoComboboxSeeder.php` | 8 user + 5 task dummy |
| `app/Http/Controllers/TaskController.php` | `index`, `store`, `searchUsers` |
| `routes/web.php` | Routes `/tasks`, `/api/users/search` |
| `resources/js/app.js` | Bootstrap PrimeVue + theme Aura |
| `resources/js/Pages/Tasks/Index.vue` | UI form + daftar task |

Selamat bereksperimen!
