# Studi Kasus: Menampilkan Ratusan Ribu Data di Inertia Vue Laravel dengan TanStack Table

## Tujuan

Studi kasus ini menunjukkan cara menampilkan data order berjumlah sangat besar, misalnya ratusan ribu sampai jutaan baris, tanpa mengambil semua data sekaligus memakai `Model::get()`.

Contoh route:

```txt
GET /large-orders
```

Stack yang dipakai:

- Laravel 12
- Inertia Laravel
- Vue 3
- TanStack Vue Table
- Tailwind CDN
- Server-side pagination, sorting, dan filtering

## Masalah Utama

Pendekatan seperti ini tidak cocok untuk data besar:

```php
$orders = Order::get();
```

Masalahnya:

- semua row dibaca dari database
- semua data disimpan di memory PHP
- semua data dikirim ke browser
- payload Inertia menjadi sangat besar
- browser berat saat render ribuan DOM row
- sorting dan search frontend menjadi lambat

Untuk data besar, frontend hanya boleh menerima data yang sedang tampil di halaman tersebut.

## Solusi yang Dipakai

Contoh ini memakai pola:

```txt
Browser Vue
  -> kirim query: search, status, sort, direction, page, per_page
Laravel Controller
  -> query database dengan filter/sort
  -> paginate()
  -> kirim hanya 10-100 row ke Inertia
Vue + TanStack Table
  -> render data halaman aktif
```

TanStack Table di sini dipakai sebagai table engine di frontend, tetapi pagination dan sorting tetap dilakukan oleh server.

## File yang Dibuat / Diubah

### Backend

- `app/Models/Order.php`
- `database/migrations/2026_09_20_054926_create_orders_table.php`
- `database/factories/OrderFactory.php`
- `database/seeders/DatabaseSeeder.php`
- `app/Http/Controllers/LargeOrderController.php`
- `routes/web.php`

### Frontend

- `resources/js/Pages/LargeOrders/Index.vue`
- `resources/views/app.blade.php`
- `package.json`

## Dependency TanStack Table

Dependency yang ditambahkan:

```bash
npm install @tanstack/vue-table
```

Package:

```json
"@tanstack/vue-table": "^9.2.4"
```

## Struktur Tabel Orders

Migration membuat kolom:

```php
$table->id();
$table->string('invoice_number')->unique();
$table->string('customer_name')->index();
$table->string('customer_email')->index();
$table->string('status')->index();
$table->decimal('total', 12, 2);
$table->timestamp('ordered_at')->index();
$table->timestamps();

$table->index(['status', 'ordered_at']);
$table->index(['ordered_at', 'id']);
```

Index penting karena query akan sering melakukan:

- filter status
- sorting tanggal order
- pencarian customer/email/invoice
- pagination

## Seeder Data Besar

Seeder memakai environment variable:

```php
Order::factory()
    ->count((int) env('ORDER_SEED_COUNT', 100000))
    ->create();
```

Default akan membuat 100.000 order.

Untuk mengubah jumlah data:

```env
ORDER_SEED_COUNT=250000
```

Lalu jalankan:

```bash
php artisan migrate:fresh --seed
```

Untuk data sangat besar, seeding bisa memakan waktu lama. Jika hanya ingin mencoba cepat, pakai nilai kecil dulu:

```env
ORDER_SEED_COUNT=1000
```

## Controller: Server-side Query

Controller utama ada di:

```txt
app/Http/Controllers/LargeOrderController.php
```

Poin penting:

### 1. Validasi query string

```php
$validated = $request->validate([
    'search' => ['nullable', 'string', 'max:100'],
    'status' => ['nullable', 'string', 'in:pending,paid,shipped,completed,cancelled'],
    'sort' => ['nullable', 'string', 'in:id,invoice_number,customer_name,customer_email,status,total,ordered_at'],
    'direction' => ['nullable', 'string', 'in:asc,desc'],
    'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
]);
```

Validasi ini penting agar user tidak bisa mengirim nama kolom bebas ke `orderBy()`.

### 2. Select kolom yang dibutuhkan saja

```php
->select(['id', 'invoice_number', 'customer_name', 'customer_email', 'status', 'total', 'ordered_at'])
```

Jangan mengambil semua kolom kalau frontend tidak membutuhkannya.

### 3. Filter search

```php
->when($search, function ($query, string $search): void {
    $query->where(function ($query) use ($search): void {
        $query->where('invoice_number', 'like', "%{$search}%")
            ->orWhere('customer_name', 'like', "%{$search}%")
            ->orWhere('customer_email', 'like', "%{$search}%");
    });
})
```

Untuk production skala sangat besar, pertimbangkan full-text index, Scout, Meilisearch, Typesense, Elasticsearch, atau pencarian prefix-only agar index lebih efektif.

### 4. Pagination server-side

```php
->paginate($perPage)
->withQueryString()
```

Ini yang menggantikan `get()`.

Yang dikirim ke browser hanya data halaman aktif, misalnya 25 row.

### 5. Transform data

```php
->through(fn (Order $order): array => [
    'id' => $order->id,
    'invoice_number' => $order->invoice_number,
    'customer_name' => $order->customer_name,
    'customer_email' => $order->customer_email,
    'status' => $order->status,
    'total' => (float) $order->total,
    'ordered_at' => $order->ordered_at?->format('Y-m-d H:i'),
]);
```

Transformasi ini menjaga payload tetap kecil dan rapi.

## Route

Route ditambahkan di `routes/web.php`:

```php
Route::get('/large-orders', [LargeOrderController::class, 'index'])->name('large-orders.index');
```

Akses halaman:

```txt
http://localhost:8000/large-orders
```

## Frontend Vue + TanStack Table

Page utama:

```txt
resources/js/Pages/LargeOrders/Index.vue
```

Import utama:

```js
import { Head, router } from '@inertiajs/vue3';
import {
    FlexRender,
    getCoreRowModel,
    useVueTable,
} from '@tanstack/vue-table';
```

Table dibuat dengan mode manual:

```js
const table = useVueTable({
    get data() {
        return props.orders.data;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    manualSorting: true,
    pageCount: props.orders.last_page,
});
```

`manualPagination` dan `manualSorting` berarti TanStack tidak melakukan pagination/sorting lokal. Vue hanya menampilkan data dari Laravel.

## Filter dan Sorting

Saat user mengetik search, memilih status, memilih per-page, klik sort, atau klik page, Vue mengirim request baru ke Laravel:

```js
router.get(route('large-orders.index'), {
    search: search.value || undefined,
    status: status.value || undefined,
    per_page: perPage.value,
    sort: sort.value,
    direction: direction.value,
    ...extra,
}, {
    preserveState: true,
    replace: true,
    only: ['orders', 'filters'],
});
```

Opsi penting:

- `preserveState: true` menjaga state halaman
- `replace: true` agar history browser tidak penuh saat search
- `only: ['orders', 'filters']` membuat partial reload Inertia

Search memakai debounce 350ms agar tidak request di setiap ketikan secara langsung.

## Tailwind CDN

Tailwind CDN ditambahkan di layout Inertia:

```html
<script src="https://cdn.tailwindcss.com"></script>
```

File:

```txt
resources/views/app.blade.php
```

Catatan: untuk production, lebih disarankan memakai Tailwind build dari Vite. CDN cocok untuk studi kasus/demo cepat.

## Kenapa Tidak Pakai Virtual Scroll Saja?

Virtual scroll hanya mengurangi jumlah DOM yang dirender di browser. Tetapi kalau backend tetap mengirim 100.000 row sekaligus, masalah memory, payload, dan query masih terjadi.

Untuk data besar, solusi utama tetap:

- server-side pagination
- server-side filtering
- server-side sorting
- index database yang tepat

Virtual scroll bisa ditambahkan setelah itu jika UX membutuhkan infinite scrolling.

## Offset Pagination vs Cursor Pagination

Contoh ini memakai `paginate()` karena cocok untuk demo table dengan nomor halaman.

Namun untuk data jutaan row, page sangat jauh seperti page 20.000 bisa berat karena database tetap menghitung offset.

Alternatif untuk skala lebih besar:

```php
->cursorPaginate($perPage)
```

Gunakan cursor pagination jika:

- user lebih sering klik next/previous
- tidak butuh lompat ke nomor halaman tertentu
- data sangat besar dan terus bertambah

## Checklist Best Practice

Untuk data besar:

- jangan pakai `::get()` untuk table besar
- batasi `per_page`, misalnya maksimal 100
- validasi kolom sorting
- pilih kolom yang dibutuhkan saja
- buat index untuk kolom filter/sort
- gunakan pagination server-side
- gunakan Inertia partial reload
- debounce input search
- hindari render ribuan row di DOM
- pertimbangkan search engine untuk pencarian kompleks

## Cara Menjalankan

Install dependency:

```bash
npm install
composer install
```

Migrate dan seed:

```bash
php artisan migrate:fresh --seed
```

Jalankan server Laravel:

```bash
php artisan serve
```

Jalankan Vite:

```bash
npm run dev
```

Buka:

```txt
http://localhost:8000/large-orders
```

## Ringkasan Arsitektur

Jangan jadikan Vue sebagai tempat menampung semua data besar. Vue hanya bertugas menampilkan halaman aktif, sedangkan Laravel dan database bertugas melakukan query berat.

Pola yang benar:

```txt
Laravel paginate/filter/sort -> Inertia props kecil -> Vue render table kecil
```

Pola yang dihindari:

```txt
Laravel get semua data -> Inertia props besar -> Vue render semua row
```
