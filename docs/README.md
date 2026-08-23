# Roadmap Belajar Vue di Inertia-Laravel

Dokumen ini memetakan cara belajar Vue 3 (Composition API) dengan pendekatan **studi kasus bertingkat**. Tujuannya: memahami directive & reactivity Vue melalui fitur nyata yang umum di aplikasi Laravel.

> Asumsi: kamu sudah paham Blade (`@if`, `@foreach`, `@csrf`, `{{ }}`, `<?php ?>`), Eloquent, dan routing Laravel.

---

## Prasyarat Mental Model

| Blade (Server) | Vue (Client) |
|---|---|
| Di-render setiap request | Di-render ulang saat data berubah |
| `{{ $var }}` = escape output | `{{ var }}` = escape output (sama!) |
| `@if` direkompilasi setiap request | `v-if` reactive, dependensi re-render |
| `@foreach` loop PHP biasa | `v-for` virtual DOM, diffing |
| `mixin` / `component` class | Composition API (`ref`, `computed`, `watch`) |
| `request()->validate()` di controller | Inertia form helpers / `useForm` |

**Kunci**: Blade = *render sekali kirim*. Vue = *render ulang saat state berubah*. Saat belajar, pikirkan: "kalau di Blade saya `@foreach`, di Vue kapan array ini berubah dan UI harus re-render?"

---

## Urutan Baca (Disarankan)

1. **[01-vue-directives-vs-blade.md](./01-vue-directives-vs-blade.md)**
   Cheatsheet directive Vue vs Blade. Cocok untuk yang baru pertama kali lihat `v-if`, `v-for`, `v-model`, `v-bind`, `v-on`, slot, dll.

2. **[02-reactivity-fundamentals.md](./02-reactivity-fundamentals.md)**
   `ref`, `reactive`, `computed`, `watch`, `watchEffect`. Ini "tulang punggung" Vue — tanpa ini, directive tidak ada gunanya.

3. **[03-inertia-vue-patterns.md](./03-inertia-vue-patterns.md)**
   Komponen & helper spesifik Inertia: `<Link>`, `<Head>`, `useForm`, `defineProps`, persistent layout, defer & merge props.

4. **[study-cases/01-todo-app.md](./study-cases/01-todo-app.md)** — `v-for`, `v-model`, event emit, filter
5. **[study-cases/02-product-search.md](./study-cases/02-product-search.md)** — `computed`, `watch`, debounce, Inertia visit
6. **[study-cases/03-auth-flow.md](./study-cases/03-auth-flow.md)** — `useForm`, validasi, error bag, redirect
7. **[study-cases/04-combobox-searchable-primevue.md](./study-cases/04-combobox-searchable-primevue.md)** — PrimeVue `Select filter` + `AutoComplete` (search server-side), race-condition guard, slot template

---

## Rekomendasi Studi Kasus (Sesuai Level)

| # | Studi Kasus | Directive / Fitur Vue yang Dipelajari | Level |
|---|---|---|---|
| 1 | **Todo App** | `v-for`, `v-model`, `v-if`, `v-show`, `@click`, `@submit.prevent` | Pemula |
| 2 | **Pencarian Produk + Filter** | `computed`, `watch`, `v-model.lazy/.debounce`, Inertia `router.get` | Menengah |
| 3 | **Form Registrasi / Login** | `useForm`, error handling, `defineProps`, persist old input | Menengah |
| 4 | **Modal & Dialog Reusable** | `Teleport`, `v-if`, slot, named slot, custom event | Menengah |
| 5 | **Tabs / Accordion** | `v-show`, dynamic component `<component :is>`, `v-slot` | Menengah |
| 6 | **CRUD dengan Pagination** | Inertia `<Link preserve-scroll>`, deferred props, partial reload | Lanjut |
| 7 | **Drag & Drop Kanban** | `provide/inject`, `transition`, custom directive | Lanjut |
| 8 | **Combobox Searchable (PrimeVue)** | `<Select filter>`, `<AutoComplete>`, slot template, race-condition guard, API search | Menengah |

> Mulai dari studi kasus #1. Setelah nyaman, loncat ke #2 atau #3 sesuai kebutuhan.

---

## Cara Kerja Belajar yang Disarankan

1. **Buka docs studi kasus** — baca deskripsi & directive yang akan dipakai.
2. **Coba implementasi sendiri dulu** — lihat studi kasus sebelumnya untuk referensi, lalu tulis ulang dari nol.
3. **Bandingkan dengan kode referensi** di docs — cek apa yang terlewat (computed vs method, ref vs reactive, dsb).
4. **Modifikasi** — tambahkan fitur sendiri (misal: Todo → tambah kategori, due date, drag-reorder).

---

## Perintah Penting Selama Belajar

```bash
# Jalankan dev server (auto reload)
npm run dev

# Jalankan Laravel
php artisan serve

# Atau keduanya sekaligus
composer run dev
```

Buka browser di `http://127.0.0.1:8000` — setiap perubahan di `.vue` akan hot-reload.

---

## Struktur Docs

```
docs/
├── README.md                              ← kamu di sini
├── 01-vue-directives-vs-blade.md
├── 02-reactivity-fundamentals.md
├── 03-inertia-vue-patterns.md
└── study-cases/
    ├── 01-todo-app.md
    ├── 02-product-search.md
    ├── 03-auth-flow.md
    └── 04-combobox-searchable-primevue.md
```

Selamat belajar!
