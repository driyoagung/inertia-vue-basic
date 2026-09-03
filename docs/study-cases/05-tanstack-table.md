# Studi Kasus Mendalam: TanStack Table di Laravel Inertia Vue

Dokumen ini membedah implementasi nyata TanStack Table yang ada di project ini, terutama file [TanstackTable.vue](C:\laragon\www\inertia-vue\resources\js\Pages\StudyCases\TanstackTable.vue), controller [EmployeeController.php](C:\laragon\www\inertia-vue\app\Http\Controllers\EmployeeController.php), model [Employee.php](C:\laragon\www\inertia-vue\app\Models\Employee.php), dan route di [web.php](C:\laragon\www\inertia-vue\routes\web.php).

Fokusnya bukan hanya "cara memakai TanStack Table", tetapi juga:

- bagaimana data mengalir dari Laravel ke Vue lewat Inertia,
- bagaimana state Vue disambungkan ke engine TanStack,
- bagaimana HTML tabel dirender manual karena TanStack bersifat headless,
- kenapa beberapa baris kode terlihat kecil tetapi sebenarnya sangat penting,
- dan catatan penting yang perlu Anda pahami sebelum mengembangkan tabel ini lebih jauh.

---

## Gambaran Besar Arsitektur

Pada studi kasus ini, alurnya seperti ini:

1. User membuka URL `/study-cases/tanstack-table`.
2. Laravel route memanggil `EmployeeController@index`.
3. Controller mengambil seluruh data `employees` dari database.
4. Inertia mengirim data itu sebagai `props` ke halaman Vue `StudyCases/TanstackTable`.
5. Vue menerima data, lalu menyerahkannya ke `useVueTable(...)`.
6. TanStack Table membangun state internal untuk filtering, sorting, dan pagination.
7. Template Vue merender hasil akhirnya menjadi elemen `<table>`, `<thead>`, `<tbody>`, tombol pagination, dan search input.

Poin paling penting: `@tanstack/vue-table` tidak merender UI tabel untuk Anda. Library ini hanya mengurus logika tabel. HTML, Tailwind class, icon, layout, empty state, dan interaction tetap Anda bangun sendiri.

---

## Prasyarat

Library yang dipakai di project ini adalah:

```bash
npm install @tanstack/vue-table@8
```

Di repo ini, dependensi tersebut memang sudah terpasang pada `package.json` dan `package-lock.json`.

---

## 1. Alur Backend Laravel

### 1.1 Route

File: [web.php](C:\laragon\www\inertia-vue\routes\web.php)

```php
Route::get('/study-cases/tanstack-table', [App\Http\Controllers\EmployeeController::class, 'index'])->name('study-cases.tanstack-table');
```

Penjelasan penting:

- `Route::get(...)` berarti halaman tabel ini diakses lewat request `GET`.
- URL `/study-cases/tanstack-table` adalah endpoint yang dibuka user di browser.
- `[App\Http\Controllers\EmployeeController::class, 'index']` berarti Laravel akan memanggil method `index()` di controller tersebut.
- `->name(...)` memberi nama route agar nanti mudah dipanggil lewat helper Laravel jika dibutuhkan.

### 1.2 Controller

File: [EmployeeController.php](C:\laragon\www\inertia-vue\app\Http\Controllers\EmployeeController.php)

```php
public function index()
{
    return inertia('StudyCases/TanstackTable', [
        'employees' => Employee::all()
    ]);
}
```

Bedah baris penting:

#### `return inertia('StudyCases/TanstackTable', [ ... ])`

- `inertia(...)` memberi tahu Laravel untuk me-render halaman Inertia, bukan Blade biasa.
- String `'StudyCases/TanstackTable'` harus cocok dengan lokasi file Vue di `resources/js/Pages/StudyCases/TanstackTable.vue`.
- Array di argumen kedua adalah `props` yang akan diterima oleh komponen Vue.

#### `'employees' => Employee::all()`

- `Employee::all()` mengambil semua data karyawan dari tabel `employees`.
- Hasilnya berupa collection Eloquent lalu otomatis diserialisasi menjadi array/object JSON untuk frontend.
- Nama key `employees` akan muncul di Vue sebagai `props.employees`.

Catatan penting:

- Pendekatan ini cocok untuk data kecil sampai menengah, misalnya puluhan atau ratusan baris.
- Jika data ribuan atau puluhan ribu, `Employee::all()` akan berat di backend, berat di payload, dan berat di browser.
- Untuk dataset besar, sebaiknya gunakan server-side pagination, server-side filtering, atau lazy loading.

### 1.3 Model

File: [Employee.php](C:\laragon\www\inertia-vue\app\Models\Employee.php)

```php
protected $fillable = [
    'name',
    'email',
    'position',
    'department',
    'salary',
    'status',
];
```

Peran bagian ini:

- `fillable` menentukan field yang aman untuk mass assignment.
- Dalam studi kasus ini, daftar field ini juga memberi gambaran kolom apa saja yang akan muncul pada tabel.
- TanStack Table nantinya membaca data berdasarkan key seperti `name`, `email`, `department`, dan seterusnya.

---

## 2. Struktur File Vue yang Dipakai

File utama: [TanstackTable.vue](C:\laragon\www\inertia-vue\resources\js\Pages\StudyCases\TanstackTable.vue)

Komponen ini memiliki dua bagian utama:

1. `<script setup>` untuk logika dan konfigurasi tabel.
2. `<template>` untuk tampilan UI tabel.

Mari kita bedah dari atas ke bawah.

---

## 3. Pembedahan `<script setup>`

### 3.1 Import Vue, Inertia, dan TanStack

```vue
<script setup>
import { ref, h } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
  FlexRender,
  getCoreRowModel,
  useVueTable,
  getPaginationRowModel,
  getSortedRowModel,
  getFilteredRowModel,
} from '@tanstack/vue-table'
```

Penjelasan baris demi baris:

#### `import { ref, h } from 'vue'`

- `ref` dipakai untuk membuat state reaktif.
- `h` adalah helper render function milik Vue.
- `h(...)` dipakai ketika kita ingin membuat elemen secara programatik dari JavaScript, bukan dari template.
- Di file ini, `h` dipakai untuk membentuk badge status pada kolom `status`.

#### `import { Head, Link } from '@inertiajs/vue3'`

- `Head` dipakai untuk mengatur `<title>` halaman.
- `Link` dipakai untuk navigasi Inertia tanpa full page reload.
- Keduanya bukan bagian dari TanStack Table, tetapi bagian dari integrasi halaman Inertia.

#### `import { FlexRender, getCoreRowModel, useVueTable, getPaginationRowModel, getSortedRowModel, getFilteredRowModel } from '@tanstack/vue-table'`

Ini inti TanStack-nya:

- `useVueTable` membuat instance tabel.
- `getCoreRowModel` membaca data mentah dasar.
- `getFilteredRowModel` memproses hasil pencarian/filter.
- `getSortedRowModel` memproses sorting.
- `getPaginationRowModel` memotong data sesuai halaman aktif.
- `FlexRender` adalah renderer khusus agar definisi `header` dan `cell` bisa dijalankan dengan benar.

Catatan penting:

- TanStack Table v8 memakai konsep modular.
- Anda hanya mengimpor fitur yang Anda butuhkan.
- Kalau Anda tidak mengimpor `getPaginationRowModel`, maka pagination UI mungkin tetap bisa dibuat, tetapi data tidak akan dipotong per halaman oleh engine TanStack.

---

### 3.2 `defineProps`: menerima data dari Laravel Inertia

```vue
const props = defineProps({
  employees: {
    type: Array,
    required: true,
  }
})
```

Penjelasan:

- `defineProps(...)` adalah macro bawaan `<script setup>`.
- `employees` adalah prop yang dikirim dari controller Laravel.
- `type: Array` berarti halaman ini mengharapkan `employees` berbentuk array.
- `required: true` berarti komponen ini menganggap prop tersebut wajib ada.

Kenapa ini penting:

- Kalau controller tidak mengirim `employees`, komponen akan berada dalam kondisi tidak valid.
- Semua konfigurasi tabel di bawahnya bergantung pada prop ini.

Hubungannya dengan backend:

- Di controller kita punya `'employees' => Employee::all()`.
- Di Vue, data itu masuk ke `props.employees`.
- Nama key harus sama persis.

---

### 3.3 State reaktif dasar

```vue
const data = ref(props.employees)
const globalFilter = ref('')
const sorting = ref([])
```

Bedah satu per satu:

#### `const data = ref(props.employees)`

- Menyimpan data employee ke dalam state reaktif Vue.
- `data.value` berisi array seluruh karyawan.
- Walaupun nilai awalnya berasal dari props, membungkusnya dalam `ref` membuat data mudah dipakai oleh getter TanStack.

Catatan:

- Pada studi kasus ini, data belum diubah setelah diterima dari props.
- Tapi kalau nanti Anda ingin menambah fitur edit lokal, hapus lokal, atau inject row baru secara client-side, `data` sudah siap menjadi sumber utama.

#### `const globalFilter = ref('')`

- State pencarian global.
- Nilai awal string kosong berarti belum ada keyword pencarian.
- Begitu user mengetik di input search, nilai ini berubah.

#### `const sorting = ref([])`

- State sorting.
- Bentuknya array karena TanStack mendukung multi-column sorting.
- Walau saat ini UI terlihat seperti satu kolom per klik, struktur state-nya tetap array.

Catatan penting:

- Banyak developer baru heran kenapa sorting bukan string seperti `'name-asc'`.
- Jawabannya: TanStack mendesain sorting state agar fleksibel untuk kebutuhan kompleks.

---

### 3.4 Definisi `columns`: blueprint tabel

```vue
const columns = [
  {
    accessorKey: 'id',
    header: 'ID',
    cell: info => info.getValue(),
  },
  {
    accessorKey: 'name',
    header: 'Name',
    cell: info => info.getValue(),
  },
  {
    accessorKey: 'email',
    header: 'Email',
    cell: info => info.getValue(),
  },
  {
    accessorKey: 'department',
    header: 'Department',
    cell: info => info.getValue(),
  },
  {
    accessorKey: 'position',
    header: 'Position',
    cell: info => info.getValue(),
  },
  {
    accessorKey: 'salary',
    header: 'Salary',
    cell: info => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(info.getValue()),
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: info => {
      const status = info.getValue()
      let colorClass = 'bg-gray-100 text-gray-800'
      if (status === 'active') colorClass = 'bg-green-100 text-green-800'
      if (status === 'inactive') colorClass = 'bg-red-100 text-red-800'
      if (status === 'on-leave') colorClass = 'bg-yellow-100 text-yellow-800'
      return h('span', { class: `px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${colorClass}` }, status)
    },
  },
]
```

Ini adalah salah satu bagian terpenting. `columns` adalah kontrak antara data mentah dan UI tabel.

#### Apa fungsi `accessorKey`

- `accessorKey` memberi tahu TanStack: "untuk kolom ini, ambil nilai dari key object tertentu."
- Contoh `accessorKey: 'email'` berarti untuk setiap row employee, kolom ini membaca `row.email`.
- Key ini harus cocok dengan struktur data JSON dari Laravel.

#### Apa fungsi `header`

- `header` adalah isi yang nanti ditampilkan pada `<th>`.
- Di contoh ini, semuanya berupa string sederhana seperti `'Name'` atau `'Department'`.
- `header` juga bisa berupa function atau komponen, tetapi di studi kasus ini cukup string.

#### Apa fungsi `cell`

- `cell` menentukan bagaimana nilai akhir dirender.
- Argumen `info` berisi context dari sel aktif.
- `info.getValue()` mengambil nilai mentah berdasarkan `accessorKey`.

Sekarang kita bahas kolom-kolomnya.

---

### 3.5 Kolom sederhana: `id`, `name`, `email`, `department`, `position`

Contoh:

```vue
{
  accessorKey: 'name',
  header: 'Name',
  cell: info => info.getValue(),
}
```

Makna tiap baris:

- `accessorKey: 'name'` membaca field `name` dari setiap employee.
- `header: 'Name'` menampilkan teks `Name` di header tabel.
- `cell: info => info.getValue()` berarti isi sel dirender apa adanya tanpa transformasi.

Kenapa tetap pakai `cell` padahal nilainya langsung?

- Supaya pola kolom konsisten.
- Lebih mudah dikembangkan nanti jika Anda ingin menambahkan format tertentu.
- Menunjukkan bahwa TanStack memang merender isi sel lewat definisi `cell`, bukan mengambil string otomatis dari HTML.

---

### 3.6 Kolom `salary`: formatting data sebelum tampil

```vue
{
  accessorKey: 'salary',
  header: 'Salary',
  cell: info => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(info.getValue()),
}
```

Ini contoh bagus bahwa nilai database tidak harus ditampilkan mentah.

Penjelasan:

- `info.getValue()` kemungkinan bernilai angka seperti `50000`.
- `Intl.NumberFormat(...)` mengubah angka itu menjadi format mata uang.
- Locale yang dipakai di sini adalah `'en-US'`.
- Currency yang dipakai adalah `'USD'`.

Hasil akhirnya bisa menjadi seperti:

```txt
$50,000.00
```

Catatan penting:

- Ini murni format tampilan, bukan mengubah data asli.
- `salary` di `data.value` tetap angka mentah.
- Jika aplikasi Anda konteksnya Indonesia, Anda bisa menggantinya ke `'id-ID'` dan `'IDR'`.
- Namun dokumentasi ini sengaja mengikuti implementasi aktual file Vue di repo.

---

### 3.7 Kolom `status`: render badge custom dengan `h(...)`

```vue
{
  accessorKey: 'status',
  header: 'Status',
  cell: info => {
    const status = info.getValue()
    let colorClass = 'bg-gray-100 text-gray-800'
    if (status === 'active') colorClass = 'bg-green-100 text-green-800'
    if (status === 'inactive') colorClass = 'bg-red-100 text-red-800'
    if (status === 'on-leave') colorClass = 'bg-yellow-100 text-yellow-800'
    return h('span', { class: `px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${colorClass}` }, status)
  },
}
```

Bagian ini sangat penting karena menunjukkan kekuatan TanStack sebagai headless table.

#### `const status = info.getValue()`

- Mengambil nilai mentah status, misalnya `active`, `inactive`, atau `on-leave`.

#### `let colorClass = 'bg-gray-100 text-gray-800'`

- Memberi warna default.
- Default ini berguna sebagai fallback jika nilai status tidak sesuai ekspektasi.

#### Deretan `if (...)`

- Jika `status === 'active'`, badge hijau.
- Jika `status === 'inactive'`, badge merah.
- Jika `status === 'on-leave'`, badge kuning.

#### `return h('span', ...)`

- `h` adalah cara membangun elemen Vue dari JavaScript.
- Baris ini setara dengan membuat `<span class="...">status</span>`, tetapi dibuat dari fungsi.
- Ini berguna karena `cell` berjalan di level definisi kolom, bukan di dalam template HTML utama.

Kenapa tidak langsung return string?

- Karena kita ingin tampilan status berupa badge dengan style Tailwind, bukan teks polos.
- Dengan `h(...)`, setiap sel `status` bisa menjadi elemen UI penuh.

Catatan penting:

- Ini salah satu alasan kenapa `FlexRender` nanti wajib dipakai di template.
- Kalau `cell` bisa berupa string, function, atau elemen hasil render function, maka template biasa tidak cukup untuk menanganinya.

---

### 3.8 `useVueTable(...)`: mesin utama tabel

```vue
const table = useVueTable({
  get data() {
    return data.value
  },
  columns,
  state: {
    get globalFilter() {
      return globalFilter.value
    },
    get sorting() {
      return sorting.value
    },
  },
  onGlobalFilterChange: setGlobalFilter => {
    globalFilter.value = setGlobalFilter
  },
  onSortingChange: updaterOrValue => {
    sorting.value = typeof updaterOrValue === 'function' ? updaterOrValue(sorting.value) : updaterOrValue
  },
  getCoreRowModel: getCoreRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
})
```

Ini jantung dari seluruh studi kasus.

---

### 3.9 `get data() { return data.value }`

Kenapa tidak langsung `data: data.value`?

- Karena kita ingin TanStack selalu membaca nilai terbaru dari state reaktif Vue.
- Getter membuat `useVueTable` mengambil data saat dibutuhkan, bukan hanya snapshot saat inisialisasi.
- Ini penting kalau nanti `data.value` berubah.

Intinya:

- `data` milik Vue adalah sumber kebenaran.
- TanStack membaca data itu secara reaktif lewat getter.

---

### 3.10 `columns`

Baris ini sederhana tetapi penting:

```vue
columns,
```

Artinya instance tabel ini memakai blueprint kolom yang tadi sudah didefinisikan.

Tanpa `columns`, TanStack tidak tahu:

- kolom apa saja yang ada,
- header apa yang harus dirender,
- cara membaca value dari row,
- dan bagaimana setiap cell harus ditampilkan.

---

### 3.11 `state`: menyambungkan state Vue ke state TanStack

```vue
state: {
  get globalFilter() {
    return globalFilter.value
  },
  get sorting() {
    return sorting.value
  },
},
```

Ini bagian yang sering membingungkan pemula.

TanStack punya state internal sendiri, tetapi di Vue kita juga punya state reaktif sendiri. Bagian `state` ini adalah jembatan antara keduanya.

#### `get globalFilter() { return globalFilter.value }`

- Memberi tahu TanStack bahwa nilai global filter saat ini berasal dari `globalFilter.value`.
- Saat Vue state berubah, TanStack membaca nilai terbarunya.

#### `get sorting() { return sorting.value }`

- Memberi tahu TanStack bahwa state sorting dikendalikan oleh `sorting.value`.

Kenapa pakai getter lagi?

- Alasannya sama seperti `data`: agar reaktif dan selalu mengambil nilai terbaru.

Pola ini disebut controlled state.

Artinya:

- Kita tidak menyerahkan state sepenuhnya ke TanStack.
- Kita mengontrol state itu dari Vue, lalu TanStack hanya menggunakannya.

Keuntungan pendekatan ini:

- Lebih mudah di-debug.
- Mudah dihubungkan dengan UI lain.
- Mudah disimpan ke query string, local storage, atau sinkronisasi state lain nanti.

---

### 3.12 `onGlobalFilterChange`: sinkronisasi balik dari TanStack ke Vue

```vue
onGlobalFilterChange: setGlobalFilter => {
  globalFilter.value = setGlobalFilter
},
```

Fungsi ini menangani perubahan filter dari sisi engine TanStack.

Maknanya:

- Jika TanStack memicu perubahan `globalFilter`, kita simpan nilai barunya kembali ke state Vue.
- Dengan begitu state Vue dan state TanStack tetap sinkron dua arah.

Catatan:

- Pada implementasi ini, input search juga sudah terikat langsung ke `v-model="globalFilter"`.
- Jadi walaupun terlihat sederhana, callback ini tetap menunjukkan pola resmi controlled state pada TanStack Table.

Catatan kehati-hatian:

- Pada banyak contoh advanced TanStack, callback perubahan state bisa mengirim value langsung atau function updater.
- Untuk `globalFilter` di contoh ini, kode tetap sederhana karena nilainya string.

---

### 3.13 `onSortingChange`: updater function vs direct value

```vue
onSortingChange: updaterOrValue => {
  sorting.value = typeof updaterOrValue === 'function' ? updaterOrValue(sorting.value) : updaterOrValue
},
```

Ini salah satu baris paling penting di file ini.

Kenapa?

- TanStack kadang mengirim nilai baru secara langsung.
- Kadang TanStack mengirim function updater.
- Kode ini menangani keduanya.

Bedah logikanya:

#### `typeof updaterOrValue === 'function'`

- Mengecek apakah argumen yang dikirim TanStack berupa function.

#### `updaterOrValue(sorting.value)`

- Jika berupa function, jalankan function itu dengan state sorting saat ini.
- Hasilnya akan menjadi state sorting yang baru.

#### `: updaterOrValue`

- Jika bukan function, berarti nilainya sudah siap pakai.
- Langsung simpan ke `sorting.value`.

Kenapa ini penting?

- TanStack v8 sering memakai pola updater agar kompatibel dengan berbagai framework.
- Jika Anda asal menulis `sorting.value = updaterOrValue`, ada kasus tertentu state bisa gagal ter-update dengan benar.

Ini adalah contoh kecil tetapi sangat krusial.

---

### 3.14 Row model pipeline: urutan pemrosesan data

```vue
getCoreRowModel: getCoreRowModel(),
getPaginationRowModel: getPaginationRowModel(),
getSortedRowModel: getSortedRowModel(),
getFilteredRowModel: getFilteredRowModel(),
```

Keempat baris ini mendaftarkan fitur pemrosesan data.

Mari kita pahami fungsinya.

#### `getCoreRowModel: getCoreRowModel()`

- Fondasi dasar.
- Membaca row mentah dari data asli.
- Hampir selalu wajib ada.

#### `getFilteredRowModel: getFilteredRowModel()`

- Menghasilkan row hasil penyaringan berdasarkan `globalFilter`.
- Tanpa ini, search input tidak akan benar-benar memfilter data.

#### `getSortedRowModel: getSortedRowModel()`

- Menghasilkan row hasil sorting.
- Tanpa ini, klik header mungkin mengubah state sorting, tetapi urutan data di UI tidak ikut berubah.

#### `getPaginationRowModel: getPaginationRowModel()`

- Menghasilkan row hasil pagination.
- Tanpa ini, semua row akan tampil sekaligus.

Catatan konsep:

- Anda bisa membayangkan data mengalir melewati beberapa tahap.
- Tahap itu bukan berarti data aslinya dimutasi permanen.
- TanStack hanya membentuk view/hasil turunan dari state saat ini.

Catatan penting lain:

- Urutan penulisan properti di object ini tidak otomatis berarti urutan eksekusi literal seperti dibaca manusia.
- Tetapi secara konsep, hasil akhir `table.getRowModel()` adalah row yang telah melalui core model dan fitur-fitur yang terpasang seperti filter, sort, dan pagination.
- Yang perlu Anda pegang: jika fitur row model tertentu tidak didaftarkan, maka kemampuan tersebut tidak aktif.

---

## 4. Pembedahan `<template>`

Sekarang kita masuk ke bagaimana engine TanStack tadi diterjemahkan menjadi UI sungguhan.

---

### 4.1 `<Head title="TanStack Table Study Case" />`

```vue
<Head title="TanStack Table Study Case" />
```

Fungsi:

- Mengatur judul tab browser.
- Berguna untuk UX dan SEO dasar.
- Tidak memengaruhi tabel, tetapi bagian dari halaman Inertia yang rapi.

---

### 4.2 Wrapper layout halaman

```vue
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8 font-sans">
  <div class="max-w-7xl mx-auto">
```

Bagian ini murni layout:

- `min-h-screen` membuat halaman minimal setinggi layar.
- `max-w-7xl mx-auto` membuat konten terpusat dan tidak terlalu melebar.
- `px-*`, `py-*` mengatur padding.

Poin penting:

- Ini membuktikan lagi bahwa TanStack tidak mengurus styling.
- Semua tampilan visual diatur manual oleh developer.

---

### 4.3 Header halaman dan tombol kembali

```vue
<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
  <div>
    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Employee Directory</h1>
    <p class="mt-2 text-sm text-gray-500">
      A demonstration of TanStack Table in a Laravel + Vue + Inertia stack.
    </p>
  </div>
  <Link href="/" class="inline-flex items-center ...">
    &larr; Back to Home
  </Link>
</div>
```

Maknanya:

- Ada judul halaman.
- Ada deskripsi singkat.
- Ada tombol kembali memakai komponen `Link` dari Inertia.

Kenapa `Link` lebih baik dari `<a>` biasa di konteks Inertia:

- Navigasi lebih halus.
- Tidak perlu full reload seperti website tradisional.

---

### 4.4 Toolbar pencarian

```vue
<input
  v-model="globalFilter"
  type="text"
  class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-200 rounded-lg py-2.5 px-4 outline-none transition-all duration-200"
  placeholder="Search employees..."
/>
```

Baris paling penting di sini adalah:

#### `v-model="globalFilter"`

- Input ini terikat langsung ke state `globalFilter`.
- Setiap ketikan user langsung mengubah `globalFilter.value`.
- Karena `globalFilter.value` disambungkan ke `table.state.globalFilter`, TanStack langsung menghitung ulang hasil filter.

Artinya:

- User mengetik.
- State Vue berubah.
- TanStack membaca state baru.
- `getFilteredRowModel()` menghasilkan row yang sudah disaring.
- Tabel otomatis rerender.

Tidak ada request ulang ke server pada studi kasus ini.

Semua terjadi di browser.

Catatan penting:

- Ini adalah client-side filtering.
- Cepat untuk dataset kecil-menengah.
- Kurang cocok untuk dataset sangat besar.

---

### 4.5 Struktur `<table>`

```vue
<table class="min-w-full divide-y divide-gray-200">
```

TanStack tidak membuat tag ini. Anda yang menulisnya sendiri.

Itu berarti Anda bebas:

- memakai `<table>` HTML biasa,
- menggantinya dengan div grid,
- memakai desain card/mobile hybrid,
- atau membangun tampilan lain selama tetap mengambil data dari instance `table`.

---

## 5. Rendering Header Table

### 5.1 Loop header group

```vue
<tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
```

Kenapa `getHeaderGroups()` dan bukan langsung `columns`?

- Karena TanStack mendukung struktur header kompleks, termasuk grouped headers dan placeholder headers.
- Walau tabel ini sederhana, API yang dipakai tetap mengikuti sistem umum TanStack.

`headerGroup.id` dipakai sebagai `key` agar Vue bisa melacak node dengan stabil.

---

### 5.2 Loop setiap header

```vue
<th
  v-for="header in headerGroup.headers"
  :key="header.id"
  scope="col"
  class="..."
  @click="header.column.getToggleSortingHandler()?.($event)"
>
```

Bedah bagian penting:

#### `v-for="header in headerGroup.headers"`

- Merender setiap kolom header dalam satu baris group.

#### `:key="header.id"`

- Penting untuk performa dan kestabilan render Vue.

#### `@click="header.column.getToggleSortingHandler()?.($event)"`

Ini inti interaksi sorting.

Maknanya:

- Saat header diklik, TanStack akan mengganti state sorting kolom tersebut.
- Biasanya urutannya: tidak sort -> ascending -> descending -> kembali default.

Kenapa pakai `?.`:

- Ini optional chaining.
- Aman jika handler sorting tidak tersedia.
- Berguna kalau suatu kolom nanti dibuat tidak sortable.

Catatan:

- Saat ini semua header terlihat bisa diklik.
- Jika nanti ada kolom yang tidak boleh disort, sebaiknya UI cursor dan hover-nya juga ikut disesuaikan.

---

### 5.3 `FlexRender` untuk header

```vue
<FlexRender
  v-if="!header.isPlaceholder"
  :render="header.column.columnDef.header"
  :props="header.getContext()"
/>
```

Ini salah satu komponen paling penting di seluruh integrasi TanStack.

Kenapa tidak cukup `{{ header.column.columnDef.header }}`?

- Karena `header` bisa berupa string biasa.
- Bisa juga berupa function.
- Bisa juga berupa renderable definition lain.
- `FlexRender` tahu cara menangani semuanya.

#### `v-if="!header.isPlaceholder"`

- TanStack kadang membuat placeholder header untuk struktur grouped headers.
- Placeholder tidak boleh dirender seperti header normal.

#### `:render="header.column.columnDef.header"`

- Mengambil definisi `header` dari kolom.

#### `:props="header.getContext()"`

- Mengirim context header ke renderer.
- Jika `header` Anda nanti berupa function, context ini sangat penting.

Singkatnya:

- `FlexRender` adalah jembatan antara definisi kolom TanStack dan tampilan Vue.

---

### 5.4 Sort indicator UI

```vue
<span class="text-gray-400 group-hover:text-indigo-400 transition-colors w-4 flex justify-center">
  <template v-if="header.column.getIsSorted() === 'asc'">
    ...
  </template>
  <template v-else-if="header.column.getIsSorted() === 'desc'">
    ...
  </template>
  <template v-else>
    ...
  </template>
</span>
```

Bagian ini hanya UI, tetapi penting untuk pengalaman pengguna.

#### `header.column.getIsSorted()`

Method ini mengembalikan:

- `'asc'` jika kolom sedang ascending,
- `'desc'` jika kolom sedang descending,
- `false` jika belum aktif sorting.

Berdasarkan nilai itulah icon panah ditampilkan berbeda.

Catatan yang perlu diperhatikan:

- Sorting logic dikerjakan oleh TanStack.
- Icon hanya refleksi visual state tersebut.
- Jadi kalau icon salah, belum tentu sorting salah; bisa jadi UI indikatornya yang keliru.

---

## 6. Rendering Body Table

### 6.1 Loop baris hasil akhir

```vue
<tr v-for="row in table.getRowModel().rows" :key="row.id" class="hover:bg-indigo-50/30 transition-colors">
```

Ini penting sekali.

`table.getRowModel().rows` bukan data mentah awal, melainkan hasil akhir yang siap ditampilkan sesuai fitur aktif.

Artinya data ini sudah dipengaruhi oleh:

- global filter,
- sorting,
- pagination.

Kalau user sedang mencari "john", maka row di sini hanya yang cocok dengan "john".
Kalau user berada di page 3, row di sini hanya row untuk page 3.
Kalau user mengurutkan salary descending, row di sini sudah dalam urutan itu.

---

### 6.2 Loop cell per row

```vue
<td
  v-for="cell in row.getVisibleCells()"
  :key="cell.id"
  class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"
>
  <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
</td>
```

Bedah bagian penting:

#### `row.getVisibleCells()`

- Mengambil semua sel yang saat ini visible untuk row tersebut.
- Ini lebih baik daripada membaca object row langsung karena mengikuti sistem column visibility TanStack jika nanti fitur itu ditambah.

#### `FlexRender`

- Sama seperti pada header, cell juga harus dirender lewat `FlexRender`.
- Ini penting karena isi `cell` bisa:
  - value sederhana,
  - hasil function,
  - hasil `h(...)`,
  - atau render definition yang lebih kompleks.

#### `:render="cell.column.columnDef.cell"`

- Mengambil definisi renderer cell dari konfigurasi kolom.

#### `:props="cell.getContext()"`

- Mengirim context sel ke renderer.
- Context ini yang membuat `info.getValue()` di definisi kolom bekerja.

Tanpa `FlexRender`, kolom `status` yang memakai `h('span', ...)` tidak akan dirender sebagaimana mestinya.

---

## 7. Empty State

```vue
<div v-if="table.getRowModel().rows.length === 0" class="p-8 text-center text-gray-500">
  No employees found matching your search.
</div>
```

Kenapa ini penting:

- Saat hasil pencarian kosong, tabel tidak boleh terlihat rusak atau membingungkan.
- Empty state memberi feedback yang jelas kepada user.

Logikanya:

- Jika jumlah row hasil akhir adalah `0`, tampilkan pesan ini.
- Karena yang dicek adalah `table.getRowModel().rows.length`, maka kondisi kosong ini mempertimbangkan filter dan pagination saat ini.

Catatan:

- Empty state tidak menggantikan `<tbody>`.
- Ia hanya menjadi pesan tambahan ketika tidak ada row yang bisa ditampilkan.

---

## 8. Pagination

Implementasi pagination di file ini dibagi menjadi dua tampilan:

- versi mobile,
- versi desktop.

Logika TanStack-nya sama, hanya UI-nya berbeda.

---

### 8.1 Tombol mobile

```vue
<button
  @click="table.previousPage()"
  :disabled="!table.getCanPreviousPage()"
>
  Previous
</button>

<button
  @click="table.nextPage()"
  :disabled="!table.getCanNextPage()"
>
  Next
</button>
```

Makna method yang dipakai:

- `table.previousPage()` pindah ke halaman sebelumnya.
- `table.nextPage()` pindah ke halaman berikutnya.
- `table.getCanPreviousPage()` mengembalikan boolean apakah mundur masih memungkinkan.
- `table.getCanNextPage()` mengembalikan boolean apakah maju masih memungkinkan.

Kenapa `:disabled` penting:

- Mencegah user menekan tombol yang seharusnya tidak valid.
- Menjaga UX tetap jelas.
- Menghindari kondisi navigasi pagination yang absurd.

---

### 8.2 Informasi halaman aktif

```vue
Showing Page <span class="font-semibold text-gray-900">{{ table.getState().pagination.pageIndex + 1 }}</span> of
<span class="font-semibold text-gray-900">{{ table.getPageCount() }}</span>
```

Penjelasan:

#### `table.getState().pagination.pageIndex`

- Mengambil index halaman aktif dari state pagination TanStack.
- Index dimulai dari `0`.

#### `+ 1`

- Karena user tidak terbiasa membaca halaman mulai dari `0`.
- Jadi angka ditampilkan dalam format manusiawi.

#### `table.getPageCount()`

- Mengembalikan total jumlah halaman berdasarkan jumlah row dan page size saat ini.

---

### 8.3 Ganti jumlah row per halaman

```vue
<select
  :value="table.getState().pagination.pageSize"
  @change="e => table.setPageSize(Number(e.target.value))"
>
  <option v-for="pageSize in [10, 20, 30, 40, 50]" :key="pageSize" :value="pageSize">
    {{ pageSize }}
  </option>
</select>
```

Penjelasan:

#### `:value="table.getState().pagination.pageSize"`

- Menampilkan page size aktif saat ini.

#### `@change="e => table.setPageSize(Number(e.target.value))"`

- Saat user memilih ukuran baru, nilai dari `<select>` dibaca dari event.
- `e.target.value` selalu string.
- `Number(...)` dipakai agar TanStack menerima angka, bukan string.
- `table.setPageSize(...)` lalu memperbarui pagination state.

Catatan penting:

- Jika lupa `Number(...)`, ada risiko page size tersimpan sebagai string.
- Pada beberapa kasus, itu bisa menimbulkan perilaku yang tidak konsisten.

---

### 8.4 Tombol first, previous, next, last

```vue
<button @click="table.setPageIndex(0)" :disabled="!table.getCanPreviousPage()">
```

- Memaksa pindah ke halaman pertama.
- Karena halaman dimulai dari index `0`, maka first page adalah `0`.

```vue
<button @click="table.previousPage()" :disabled="!table.getCanPreviousPage()">
```

- Mundur satu halaman.

```vue
<button @click="table.nextPage()" :disabled="!table.getCanNextPage()">
```

- Maju satu halaman.

```vue
<button @click="table.setPageIndex(table.getPageCount() - 1)" :disabled="!table.getCanNextPage()">
```

- Pindah ke halaman terakhir.
- Karena `getPageCount()` adalah jumlah halaman total, index terakhir adalah `total - 1`.

Contoh:

- Jika total halaman `5`, maka index validnya `0, 1, 2, 3, 4`.
- Jadi halaman terakhir adalah index `4`.

---

## 9. Kenapa TanStack Table Disebut Headless

Dalam implementasi ini, Anda bisa melihat sendiri bahwa:

- TanStack tidak membuat `<table>`.
- TanStack tidak membuat `<tr>`.
- TanStack tidak membuat `<input>` search.
- TanStack tidak membuat `<button>` pagination.
- TanStack tidak membuat badge status.

Yang TanStack berikan adalah:

- API untuk membaca header groups,
- API untuk membaca row model hasil filter/sort/pagination,
- API untuk mengubah sorting,
- API untuk berpindah halaman,
- API untuk mengubah page size,
- dan state machine yang mengatur semuanya.

Inilah arti praktis dari headless:

- logika disediakan library,
- presentasi sepenuhnya milik Anda.

Keuntungannya:

- fleksibel sekali,
- cocok untuk Tailwind, Bootstrap, atau desain custom,
- tidak memaksa gaya visual tertentu.

Kekurangannya:

- Anda harus memahami alur render-nya,
- Anda harus membangun semua UI sendiri,
- dan dokumentasi/pemahaman awal biasanya lebih menantang dibanding table component yang full UI.

---

## 10. Catatan Penting dan Hal yang Sering Terlewat

### 10.1 `FlexRender` itu wajib di titik penting

Kalau Anda memakai definisi `header` dan `cell` dari TanStack, jangan render sembarangan dengan mustache biasa.

Gunakan:

```vue
<FlexRender :render="..." :props="..." />
```

Ini sangat penting terutama jika:

- `cell` berupa function,
- `cell` me-return `h(...)`,
- atau `header` kelak menjadi custom renderer.

### 10.2 `data`, `sorting`, dan `globalFilter` di sini adalah controlled state

Artinya state utama dikendalikan Vue, bukan dibiarkan liar di dalam TanStack.

Ini bagus karena:

- mudah diintegrasikan ke komponen lain,
- mudah di-debug,
- dan mudah dikembangkan menjadi sinkron ke URL query.

### 10.3 Search yang dipakai adalah global filter

Input di halaman ini bukan filter per kolom, melainkan satu filter global.

Implikasinya:

- satu keyword dipakai untuk keseluruhan tabel,
- bukan filter terpisah seperti "department saja" atau "status saja".

Jika nanti Anda ingin filter per kolom, struktur state dan UI-nya perlu ditambah.

### 10.4 `Employee::all()` punya batas aman

Untuk demo dan studi kasus, pendekatan ini enak karena sederhana.

Tetapi untuk produksi dengan data besar:

- payload besar,
- render browser berat,
- pencarian client-side makin lambat,
- dan first load membengkak.

Saat skala bertambah, pertimbangkan:

- `paginate()`,
- filter via request query ke backend,
- sorting server-side,
- debounce search,
- dan partial reload Inertia.

### 10.5 Formatting `salary` saat ini pakai `en-US` dan `USD`

Ini cocok untuk contoh di file aktual, tetapi mungkin tidak cocok dengan domain aplikasi Anda.

Jika konteks aplikasi Indonesia, kemungkinan lebih natural memakai:

```js
new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' })
```

### 10.6 Semua header saat ini dapat diklik

Karena semua `<th>` diberi click handler sorting, UI mengisyaratkan semua kolom sortable.

Kalau nanti ada kolom yang tidak boleh sorting:

- matikan sorting di definisi kolom,
- dan sesuaikan class cursor/hover agar user tidak bingung.

### 10.7 Empty state sudah bagus, tapi bisa dikembangkan

Saat ini empty state hanya menampilkan teks.

Nanti bisa ditingkatkan dengan:

- icon,
- tombol reset filter,
- atau informasi keyword yang sedang dicari.

---

## 11. Ringkasan Mental Model yang Paling Mudah Diingat

Kalau ingin mengingat TanStack Table dengan sederhana, pikirkan seperti ini:

1. Laravel mengirim data mentah.
2. Vue menyimpan data itu dalam state reaktif.
3. `useVueTable()` membungkus data dan state menjadi engine tabel.
4. Row models menentukan fitur apa yang aktif: filter, sorting, pagination.
5. Template Vue membaca hasil akhir engine itu.
6. `FlexRender` merender header dan cell sesuai definisi kolom.
7. UI seperti input, badge, icon, dan tombol tetap Anda bangun sendiri.

---

## 12. Kesimpulan

Implementasi TanStack Table di project ini sudah menunjukkan pola yang sangat baik untuk level dasar-menengah:

- data dikirim dari Laravel lewat Inertia,
- state tabel dikontrol secara reaktif dengan Vue,
- sorting, filtering, dan pagination dijalankan client-side,
- dan UI dibangun manual memakai Tailwind sehingga fleksibel.

Bagian yang paling penting untuk benar-benar dipahami adalah:

- `columns` sebagai blueprint tabel,
- `useVueTable(...)` sebagai engine utama,
- controlled state (`globalFilter`, `sorting`, `data`),
- `FlexRender` sebagai renderer wajib,
- dan `table.getRowModel().rows` sebagai hasil akhir yang benar-benar ditampilkan ke layar.

Kalau Anda sudah nyaman dengan struktur ini, langkah lanjutan yang biasanya dipelajari adalah:

- column filtering,
- row selection,
- server-side pagination,
- sinkron query string,
- dan reusable data-table component agar logika ini bisa dipakai di banyak halaman.
