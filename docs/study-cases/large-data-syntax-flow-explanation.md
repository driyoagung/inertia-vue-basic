# Penjelasan Syntax: Flow Backend Laravel ke Frontend Vue TanStack Table

Dokumen ini menjelaskan alur syntax dari data dibuat/query di backend Laravel sampai tampil di frontend Vue lewat Inertia dan TanStack Table.

## 1. Gambaran Flow

```txt
Database orders
    ↓
Order Model
    ↓
LargeOrderController@index
    ↓
Eloquent query + filter + sort + paginate
    ↓
Inertia::render('LargeOrders/Index', props)
    ↓
resources/js/Pages/LargeOrders/Index.vue
    ↓
TanStack Table render rows
```

Intinya: Laravel hanya mengirim data halaman aktif, bukan semua data.

---

## 2. Model: `app/Models/Order.php`

```php
class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_name',
        'customer_email',
        'status',
        'total',
        'ordered_at',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'ordered_at' => 'datetime',
    ];
}
```

### Syntax penting

#### `$fillable`

```php
protected $fillable = [...];
```

Menentukan kolom yang boleh diisi secara mass assignment, misalnya saat factory/seeder membuat data.

#### `$casts`

```php
protected $casts = [
    'total' => 'decimal:2',
    'ordered_at' => 'datetime',
];
```

Mengubah tipe data otomatis ketika data dibaca dari database.

- `total` dibaca sebagai decimal 2 digit
- `ordered_at` dibaca sebagai object date/datetime Laravel

---

## 3. Migration: Struktur Tabel dan Index

File:

```txt
database/migrations/2026_09_20_054926_create_orders_table.php
```

```php
Schema::create('orders', function (Blueprint $table) {
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
});
```

### Syntax penting

#### `unique()`

```php
$table->string('invoice_number')->unique();
```

Invoice harus unik. Tidak boleh ada invoice number yang sama.

#### `index()`

```php
$table->string('status')->index();
$table->timestamp('ordered_at')->index();
```

Index membantu database mencari/filter/sort lebih cepat.

Tanpa index, database bisa membaca terlalu banyak row ketika data sudah ratusan ribu.

#### Composite index

```php
$table->index(['status', 'ordered_at']);
$table->index(['ordered_at', 'id']);
```

Index gabungan untuk query seperti:

```php
where status = 'paid'
order by ordered_at desc
```

---

## 4. Seeder: Membuat Data Besar Tanpa Memory Habis

File:

```txt
database/seeders/DatabaseSeeder.php
```

Seeder tidak memakai:

```php
Order::factory()->count(100000)->create();
```

Karena itu membuat banyak Eloquent model dan bisa boros memory.

Seeder memakai bulk insert per chunk:

```php
$total = (int) env('ORDER_SEED_COUNT', 100000);
$chunkSize = (int) env('ORDER_SEED_CHUNK', 1000);
```

### Syntax penting

#### Ambil jumlah data dari `.env`

```php
$total = (int) env('ORDER_SEED_COUNT', 100000);
```

Artinya:

- kalau `ORDER_SEED_COUNT` ada di `.env`, pakai nilai itu
- kalau tidak ada, default 100000

Contoh `.env`:

```env
ORDER_SEED_COUNT=250000
ORDER_SEED_CHUNK=1000
```

#### Loop chunk

```php
for ($offset = 0; $offset < $total; $offset += $chunkSize) {
    $rows = [];
    $limit = min($chunkSize, $total - $offset);

    for ($index = 1; $index <= $limit; $index++) {
        $number = $offset + $index;

        $rows[] = [
            'invoice_number' => 'INV-'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
            'customer_name' => 'Customer '.$number,
            'customer_email' => 'customer'.$number.'@example.test',
            'status' => $statuses[$number % count($statuses)],
            'total' => random_int(2500, 50000000) / 100,
            'ordered_at' => $now->copy()->subMinutes($number),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    DB::table('orders')->insert($rows);
}
```

#### `DB::table()->insert()`

```php
DB::table('orders')->insert($rows);
```

Melakukan insert banyak row sekaligus langsung ke database.

Lebih hemat memory dibanding membuat Eloquent model satu per satu.

#### `str_pad()`

```php
str_pad((string) $number, 8, '0', STR_PAD_LEFT)
```

Mengubah angka menjadi format 8 digit.

Contoh:

```txt
1     -> 00000001
25    -> 00000025
10000 -> 00010000
```

Lalu menjadi invoice:

```txt
INV-00000001
INV-00000025
```

---

## 5. Route: Endpoint Halaman Table

File:

```txt
routes/web.php
```

```php
Route::get('/large-orders', [LargeOrderController::class, 'index'])->name('large-orders.index');
```

### Syntax penting

#### `Route::get()`

```php
Route::get('/large-orders', ...)
```

Mendaftarkan route HTTP GET.

Ketika browser membuka:

```txt
/large-orders
```

Laravel menjalankan method:

```php
LargeOrderController@index
```

#### `name()`

```php
->name('large-orders.index')
```

Memberi nama route agar bisa dipanggil dari Vue dengan Ziggy:

```js
route('large-orders.index')
```

---

## 6. Controller: Query Data Besar Secara Aman

File:

```txt
app/Http/Controllers/LargeOrderController.php
```

Method utama:

```php
public function index(Request $request): Response
```

Method ini menerima request dari browser, membaca query string, mengambil data dari database, lalu mengirim ke Vue.

---

## 7. Validasi Query String

```php
$validated = $request->validate([
    'search' => ['nullable', 'string', 'max:100'],
    'status' => ['nullable', 'string', 'in:pending,paid,shipped,completed,cancelled'],
    'sort' => ['nullable', 'string', 'in:id,invoice_number,customer_name,customer_email,status,total,ordered_at'],
    'direction' => ['nullable', 'string', 'in:asc,desc'],
    'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
]);
```

### Syntax penting

#### `validate()`

```php
$request->validate([...])
```

Memastikan input dari URL aman dan sesuai aturan.

Contoh URL:

```txt
/large-orders?search=andi&status=paid&sort=ordered_at&direction=desc&per_page=25
```

#### Validasi `sort`

```php
'sort' => ['nullable', 'string', 'in:id,invoice_number,customer_name,customer_email,status,total,ordered_at']
```

Ini penting karena nilai `sort` akan masuk ke:

```php
orderBy($sort, $direction)
```

Kalau tidak divalidasi, user bisa mengirim nama kolom sembarangan.

#### Validasi `per_page`

```php
'per_page' => ['nullable', 'integer', 'min:10', 'max:100']
```

User hanya boleh meminta 10 sampai 100 data per halaman.

Ini mencegah request berat seperti:

```txt
?per_page=100000
```

---

## 8. Default Filter

```php
$search = $validated['search'] ?? null;
$status = $validated['status'] ?? null;
$sort = $validated['sort'] ?? 'ordered_at';
$direction = $validated['direction'] ?? 'desc';
$perPage = $validated['per_page'] ?? 25;
```

### Syntax penting

#### Null coalescing operator `??`

```php
$sort = $validated['sort'] ?? 'ordered_at';
```

Artinya:

- kalau `sort` ada, pakai nilai dari request
- kalau tidak ada, pakai default `ordered_at`

---

## 9. Query Eloquent

```php
$orders = Order::query()
    ->select(['id', 'invoice_number', 'customer_name', 'customer_email', 'status', 'total', 'ordered_at'])
    ->when($search, function ($query, string $search): void {
        $query->where(function ($query) use ($search): void {
            $query->where('invoice_number', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('customer_email', 'like', "%{$search}%");
        });
    })
    ->when($status, fn ($query, string $status) => $query->where('status', $status))
    ->orderBy($sort, $direction)
    ->orderBy('id', 'desc')
    ->paginate($perPage)
    ->withQueryString();
```

---

## 10. Penjelasan Method Query Penting

### `Order::query()`

```php
Order::query()
```

Membuat query builder untuk model `Order`.

Belum mengambil data dari database sampai method akhir seperti `paginate()` dijalankan.

---

### `select()`

```php
->select(['id', 'invoice_number', 'customer_name', 'customer_email', 'status', 'total', 'ordered_at'])
```

Mengambil kolom yang dibutuhkan saja.

Lebih ringan daripada:

```php
Order::query()
```

atau:

```php
Order::all()
```

Karena tidak semua kolom dikirim ke frontend.

---

### `when()`

```php
->when($search, function ($query, string $search): void {
    ...
})
```

`when()` menjalankan query tambahan hanya kalau kondisi bernilai true.

Artinya:

- kalau `$search` ada, jalankan filter search
- kalau kosong, skip filter search

Contoh sederhana:

```php
->when($status, fn ($query) => $query->where('status', $status))
```

Sama seperti:

```php
if ($status) {
    $query->where('status', $status);
}
```

Tapi lebih rapi dalam chain Eloquent.

---

### Grouping `where()` untuk search

```php
$query->where(function ($query) use ($search): void {
    $query->where('invoice_number', 'like', "%{$search}%")
        ->orWhere('customer_name', 'like', "%{$search}%")
        ->orWhere('customer_email', 'like', "%{$search}%");
});
```

Ini menghasilkan query logika seperti:

```sql
WHERE (
    invoice_number LIKE '%keyword%'
    OR customer_name LIKE '%keyword%'
    OR customer_email LIKE '%keyword%'
)
```

Kenapa dibungkus `where(function () {})`?

Agar kondisi `OR` tidak mengganggu filter lain seperti `status`.

---

### `use ($search)`

```php
function ($query) use ($search): void
```

Closure PHP perlu `use ($search)` agar variable `$search` dari luar bisa dipakai di dalam function.

---

### `orWhere()`

```php
->orWhere('customer_name', 'like', "%{$search}%")
```

Artinya: atau cari di kolom lain.

Contoh:

```txt
invoice cocok ATAU customer cocok ATAU email cocok
```

---

### `orderBy()`

```php
->orderBy($sort, $direction)
```

Mengurutkan data berdasarkan kolom dan arah dari request.

Contoh:

```txt
?sort=total&direction=desc
```

Menjadi:

```sql
ORDER BY total DESC
```

---

### Secondary sort

```php
->orderBy('id', 'desc')
```

Dipakai sebagai urutan tambahan agar hasil lebih stabil jika nilai kolom sort sama.

Contoh banyak order punya `ordered_at` yang sama, maka `id` membantu menentukan urutan akhir.

---

### `paginate()`

```php
->paginate($perPage)
```

Ini method paling penting untuk data besar.

`paginate()` hanya mengambil data sesuai halaman.

Contoh:

```txt
?page=3&per_page=25
```

Laravel mengambil 25 data untuk halaman 3, bukan semua data.

Response pagination berisi:

```php
[
    'data' => [...],
    'current_page' => 3,
    'last_page' => 4000,
    'per_page' => 25,
    'total' => 100000,
]
```

---

### `withQueryString()`

```php
->withQueryString()
```

Menjaga query string tetap ada ketika pindah halaman.

Contoh saat user sedang filter:

```txt
/large-orders?status=paid&search=andi&page=2
```

Link pagination tetap membawa `status=paid` dan `search=andi`.

---

## 11. Transform Data dengan `through()`

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

### Syntax penting

#### `through()`

```php
->through(fn (Order $order): array => [...])
```

Mengubah setiap item order sebelum dikirim ke frontend.

Mirip `map()`, tapi tetap menjaga struktur pagination Laravel.

#### Arrow function `fn`

```php
fn (Order $order): array => [...]
```

Syntax singkat untuk function sederhana.

#### Nullable operator `?->`

```php
$order->ordered_at?->format('Y-m-d H:i')
```

Artinya:

- kalau `ordered_at` ada, format tanggal
- kalau null, hasilnya null

Aman dari error ketika tanggal kosong.

---

## 12. Mengirim Data ke Vue dengan Inertia

```php
return Inertia::render('LargeOrders/Index', [
    'orders' => $orders,
    'filters' => [
        'search' => $search,
        'status' => $status,
        'sort' => $sort,
        'direction' => $direction,
        'per_page' => $perPage,
    ],
    'statuses' => ['pending', 'paid', 'shipped', 'completed', 'cancelled'],
]);
```

### Syntax penting

#### `Inertia::render()`

```php
Inertia::render('LargeOrders/Index', [...])
```

Memanggil page Vue:

```txt
resources/js/Pages/LargeOrders/Index.vue
```

#### Props

```php
[
    'orders' => $orders,
    'filters' => [...],
    'statuses' => [...],
]
```

Data ini akan masuk ke Vue sebagai props.

---

## 13. Menerima Props di Vue

File:

```txt
resources/js/Pages/LargeOrders/Index.vue
```

```js
const props = defineProps({
    orders: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    statuses: {
        type: Array,
        required: true,
    },
});
```

### Syntax penting

#### `defineProps()`

```js
defineProps({...})
```

Dipakai di Vue `<script setup>` untuk menerima data dari parent/Inertia.

Dalam kasus ini, props berasal dari Laravel:

```php
Inertia::render(..., [
    'orders' => $orders,
    'filters' => $filters,
    'statuses' => $statuses,
])
```

---

## 14. State Filter di Vue

```js
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const sort = ref(props.filters.sort ?? 'ordered_at');
const direction = ref(props.filters.direction ?? 'desc');
```

### Syntax penting

#### `ref()`

```js
const search = ref('');
```

Membuat reactive state di Vue.

Kalau nilainya berubah, UI bisa ikut berubah.

Di template, `ref` bisa langsung dipakai:

```vue
<input v-model="search" />
```

Di JavaScript, nilainya diakses dengan `.value`:

```js
search.value
```

---

## 15. Columns TanStack Table

```js
const columns = [
    {
        accessorKey: 'invoice_number',
        header: 'Invoice',
    },
    {
        accessorKey: 'customer_name',
        header: 'Customer',
    },
    {
        accessorKey: 'total',
        header: 'Total',
        cell: ({ getValue }) => formatCurrency(getValue()),
    },
];
```

### Syntax penting

#### `accessorKey`

```js
accessorKey: 'invoice_number'
```

Menentukan field mana dari object data yang ditampilkan di kolom tersebut.

Contoh data:

```js
{
    invoice_number: 'INV-00000001',
    customer_name: 'Customer 1'
}
```

Maka `accessorKey: 'invoice_number'` akan menampilkan:

```txt
INV-00000001
```

#### `header`

```js
header: 'Invoice'
```

Label kolom di `<thead>`.

#### `cell`

```js
cell: ({ getValue }) => formatCurrency(getValue())
```

Mengatur tampilan isi cell.

Contoh kolom `total` diformat menjadi Rupiah.

---

## 16. Membuat Table Instance

```js
const table = useTable({
    get data() {
        return props.orders.data;
    },
    columns,
    getCoreRowModel: createCoreRowModel(),
    manualPagination: true,
    manualSorting: true,
    pageCount: props.orders.last_page,
});
```

### Syntax penting

#### `useTable()`

```js
useTable({...})
```

Membuat instance table dari TanStack Vue Table.

#### `data`

```js
get data() {
    return props.orders.data;
}
```

Data yang diberikan ke table adalah data dari Laravel pagination.

Yang dipakai hanya:

```js
props.orders.data
```

Bukan semua response pagination.

#### `columns`

```js
columns,
```

Sama dengan:

```js
columns: columns
```

Berisi definisi kolom table.

#### `createCoreRowModel()`

```js
getCoreRowModel: createCoreRowModel()
```

Method TanStack untuk membuat struktur row dasar dari data.

#### `manualPagination`

```js
manualPagination: true
```

Artinya pagination tidak dilakukan oleh TanStack di browser.

Pagination dilakukan oleh Laravel.

#### `manualSorting`

```js
manualSorting: true
```

Artinya sorting tidak dilakukan oleh TanStack di browser.

Sorting dilakukan oleh Laravel.

#### `pageCount`

```js
pageCount: props.orders.last_page
```

Memberitahu TanStack jumlah halaman total dari backend.

---

## 17. Computed Visible Pages

```js
const visiblePages = computed(() => {
    const current = props.orders.current_page;
    const last = props.orders.last_page;
    const start = Math.max(1, current - 2);
    const end = Math.min(last, current + 2);

    return Array.from({ length: end - start + 1 }, (_, index) => start + index);
});
```

### Syntax penting

#### `computed()`

```js
computed(() => {...})
```

Membuat nilai turunan yang otomatis berubah jika data sumber berubah.

Di sini `visiblePages` berubah saat `orders.current_page` berubah.

#### `Math.max()` dan `Math.min()`

```js
const start = Math.max(1, current - 2);
const end = Math.min(last, current + 2);
```

Membatasi tombol pagination agar tidak kurang dari halaman 1 dan tidak lebih dari halaman terakhir.

#### `Array.from()`

```js
Array.from({ length: end - start + 1 }, (_, index) => start + index)
```

Membuat array nomor halaman.

Contoh current page 5:

```js
[3, 4, 5, 6, 7]
```

---

## 18. Watch Filter dan Debounce

```js
let searchTimer;

watch([search, status, perPage], () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => reload({ page: 1 }), 350);
});
```

### Syntax penting

#### `watch()`

```js
watch([search, status, perPage], () => {...})
```

Mengamati perubahan pada search, status, dan perPage.

Kalau salah satu berubah, function akan dijalankan.

#### `clearTimeout()` dan `setTimeout()`

```js
clearTimeout(searchTimer);
searchTimer = setTimeout(() => reload({ page: 1 }), 350);
```

Ini debounce.

Tujuannya agar request tidak dikirim setiap user mengetik satu huruf.

Contoh user mengetik:

```txt
a
an
and
andi
```

Tanpa debounce: 4 request.

Dengan debounce: request dikirim setelah user berhenti mengetik 350ms.

---

## 19. Reload Data dari Vue ke Laravel

```js
function reload(extra = {}) {
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
}
```

### Syntax penting

#### `router.get()`

```js
router.get(url, data, options)
```

Mengirim request GET Inertia ke Laravel tanpa full page reload.

#### `route()`

```js
route('large-orders.index')
```

Menghasilkan URL dari nama route Laravel.

Route asalnya:

```php
->name('large-orders.index')
```

#### Query data

```js
{
    search: search.value || undefined,
    status: status.value || undefined,
    per_page: perPage.value,
    sort: sort.value,
    direction: direction.value,
    ...extra,
}
```

Object ini menjadi query string.

Contoh:

```txt
/large-orders?search=andi&status=paid&per_page=25&sort=total&direction=desc&page=2
```

#### Spread operator `...extra`

```js
...extra
```

Menggabungkan object tambahan.

Contoh:

```js
reload({ page: 2 })
```

Maka `page: 2` ikut dikirim ke Laravel.

#### `preserveState`

```js
preserveState: true
```

Menjaga state page agar tidak terasa reload penuh.

#### `replace`

```js
replace: true
```

Mengganti history browser saat filter berubah.

Tanpa ini, tombol back browser bisa terlalu banyak melewati riwayat search.

#### `only`

```js
only: ['orders', 'filters']
```

Inertia hanya reload props `orders` dan `filters`.

Ini disebut partial reload.

Lebih ringan dibanding reload semua props.

---

## 20. Sorting dari Header Table

```js
function changeSort(column) {
    if (sort.value === column) {
        direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value = column;
        direction.value = 'asc';
    }

    reload({ page: 1 });
}
```

### Syntax penting

#### Toggle sort direction

```js
if (sort.value === column) {
    direction.value = direction.value === 'asc' ? 'desc' : 'asc';
}
```

Kalau klik kolom yang sama, arah sorting dibalik.

Contoh:

```txt
asc -> desc
desc -> asc
```

#### Reset ke page 1

```js
reload({ page: 1 });
```

Saat sorting berubah, halaman dikembalikan ke page 1 agar hasil lebih masuk akal.

---

## 21. Pagination dari Vue

```js
function goToPage(page) {
    if (page < 1 || page > props.orders.last_page || page === props.orders.current_page) {
        return;
    }

    reload({ page });
}
```

### Syntax penting

#### Guard clause

```js
if (...) {
    return;
}
```

Mencegah request yang tidak valid:

- halaman kurang dari 1
- halaman lebih dari halaman terakhir
- klik halaman yang sedang aktif

#### Shorthand object

```js
reload({ page });
```

Sama dengan:

```js
reload({ page: page });
```

---

## 22. Format Currency

```js
function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}
```

### Syntax penting

#### `Intl.NumberFormat`

```js
new Intl.NumberFormat('id-ID', {...}).format(value)
```

Format angka menjadi mata uang Indonesia.

Contoh:

```txt
1500000 -> Rp1.500.000
```

---

## 23. Render Header Table

```vue
<tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
    <th v-for="header in headerGroup.headers" :key="header.id">
        <button @click="changeSort(header.column.id)">
            <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
            <span v-if="sort === header.column.id">
                {{ direction === 'asc' ? '↑' : '↓' }}
            </span>
        </button>
    </th>
</tr>
```

### Syntax penting

#### `v-for`

```vue
v-for="header in headerGroup.headers"
```

Looping data di template Vue.

#### `:key`

```vue
:key="header.id"
```

Membantu Vue mengenali item saat render ulang.

#### `@click`

```vue
@click="changeSort(header.column.id)"
```

Menjalankan function saat header diklik.

#### `FlexRender`

```vue
<FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
```

Component dari TanStack untuk merender header/cell yang bisa berupa string atau function.

---

## 24. Render Body Table

```vue
<tr v-for="row in table.getRowModel().rows" :key="row.id">
    <td v-for="cell in row.getAllCells()" :key="cell.id">
        <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
    </td>
</tr>
```

### Syntax penting

#### `table.getRowModel().rows`

```js
table.getRowModel().rows
```

Mengambil rows yang sudah diproses oleh TanStack.

Datanya berasal dari:

```js
props.orders.data
```

#### `row.getAllCells()`

```js
row.getAllCells()
```

Mengambil semua cell dalam satu row.

Catatan: pada versi TanStack Vue Table yang dipakai project ini, method yang cocok adalah `getAllCells()`, bukan `getVisibleCells()`.

#### `cell.getContext()`

```js
cell.getContext()
```

Memberi context ke `FlexRender`, termasuk value cell, row, dan column.

---

## 25. Empty State

```vue
<tr v-if="table.getRowModel().rows.length === 0">
    <td :colspan="columns.length">
        Data tidak ditemukan.
    </td>
</tr>
```

### Syntax penting

#### `v-if`

```vue
v-if="table.getRowModel().rows.length === 0"
```

Menampilkan elemen hanya jika kondisi true.

#### `:colspan`

```vue
:colspan="columns.length"
```

Binding dynamic attribute di Vue.

Jumlah colspan mengikuti jumlah kolom.

---

## 26. Render Pagination Button

```vue
<button
    v-for="page in visiblePages"
    :key="page"
    type="button"
    :class="page === orders.current_page ? 'border-white bg-white text-neutral-950' : 'border-neutral-700'"
    @click="goToPage(page)"
>
    {{ page }}
</button>
```

### Syntax penting

#### Dynamic class

```vue
:class="page === orders.current_page ? 'border-white bg-white text-neutral-950' : 'border-neutral-700'"
```

Class berubah sesuai kondisi.

Kalau page adalah halaman aktif, tombol diberi style aktif.

#### Text interpolation

```vue
{{ page }}
```

Menampilkan nilai JavaScript ke HTML.

---

## 27. Kenapa Tidak `Order::get()`?

Jangan pakai:

```php
Order::get();
```

Untuk data besar, itu berarti:

```txt
ambil semua row dari database
simpan semua di memory PHP
kirim semua ke browser
render semua di Vue
```

Yang dipakai dalam studi kasus ini:

```php
Order::query()->paginate($perPage)
```

Artinya:

```txt
ambil row sesuai halaman saja
misalnya 25 row per request
```

---

## 28. Alur Saat User Search

Contoh user mengetik `andi`.

```txt
User mengetik di input search
    ↓
v-model mengubah search.value
    ↓
watch mendeteksi perubahan
    ↓
debounce 350ms
    ↓
reload({ page: 1 })
    ↓
router.get('/large-orders?search=andi&page=1')
    ↓
Laravel validate request
    ↓
Eloquent where invoice/customer/email LIKE '%andi%'
    ↓
paginate hasil
    ↓
Inertia kirim props orders baru
    ↓
Vue render table ulang
```

---

## 29. Alur Saat User Sorting

Contoh user klik kolom `Total`.

```txt
Klik header Total
    ↓
changeSort('total')
    ↓
sort.value = 'total'
    ↓
direction.value = 'asc' atau 'desc'
    ↓
reload({ page: 1 })
    ↓
Request ke Laravel: ?sort=total&direction=desc
    ↓
Controller validate sort dan direction
    ↓
Query orderBy('total', 'desc')
    ↓
Laravel paginate
    ↓
Vue render hasil baru
```

---

## 30. Alur Saat User Ganti Halaman

Contoh user klik page 5.

```txt
Klik tombol 5
    ↓
goToPage(5)
    ↓
reload({ page: 5 })
    ↓
Request: /large-orders?page=5&per_page=25
    ↓
Laravel paginate halaman 5
    ↓
Inertia update props orders
    ↓
TanStack render rows baru
```

---

## 31. Syntax Paling Penting untuk Diingat

### Backend

```php
$request->validate([...]);
```

Validasi query string.

```php
Order::query();
```

Mulai query Eloquent.

```php
->select([...]);
```

Ambil kolom yang dibutuhkan saja.

```php
->when($condition, fn () => ...);
```

Filter conditional.

```php
->orderBy($sort, $direction);
```

Sorting server-side.

```php
->paginate($perPage);
```

Pagination server-side.

```php
->through(fn ($item) => [...]);
```

Transform data pagination.

```php
Inertia::render('Page/Name', [...]);
```

Kirim data ke Vue.

### Frontend

```js
defineProps({...})
```

Menerima data dari Laravel.

```js
ref(...)
```

State reactive.

```js
useTable({...})
```

Membuat instance TanStack Table.

```js
manualPagination: true
manualSorting: true
```

Menandakan pagination/sorting dilakukan server.

```js
router.get(route(...), data, options)
```

Request ulang ke Laravel via Inertia.

```vue
v-for="row in table.getRowModel().rows"
```

Render rows table.

```vue
v-for="cell in row.getAllCells()"
```

Render cells table.

---

## 32. Kesimpulan

Flow utama studi kasus ini adalah:

```txt
Vue mengirim filter/sort/page
Laravel query database sesuai request
Laravel paginate hasil
Inertia mengirim props kecil
Vue render table dari props tersebut
```

Dengan pola ini, aplikasi tetap ringan walaupun data di database berjumlah ratusan ribu atau lebih.
