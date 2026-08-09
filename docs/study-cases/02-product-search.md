# Study Case 2: Product Search & Filter

Tujuan: belajar **dua mode** pencarian produk — client-side (filter array lokal) dan server-side (via Inertia `router.get` dengan debounce). Ini juga memperkenalkan `computed` untuk sorting, dan `watch` dengan debounce.

---

## Vue Features yang Dipelajari

| Fitur Vue | Dipakai untuk |
|---|---|
| `v-model.lazy` / `.debounce` | input tidak trigger tiap ketukan |
| `computed` | sort & filter derived dari list |
| `watch` + debounce | trigger Inertia visit setelah user berhenti ngetik |
| `router.get` | navigasi dengan query string |
| `preserveState` | pertahankan state komponen saat navigasi |
| `defineProps` | props `products` dari controller |
| `usePage` | baca `props.filters` dari server |

---

## Backend: Siapkan Data

### 1. Model & Migration

```bash
php artisan make:model Product -m
```

```php
// database/migrations/xxxx_create_products_table.php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('category');
    $table->integer('price');
    $table->integer('stock');
    $table->timestamps();
});
```

```bash
php artisan migrate
```

### 2. Seeder

```php
// database/seeders/ProductSeeder.php
Product::create(['name' => 'Laravel T-Shirt', 'category' => 'apparel',  'price' => 250000,  'stock' => 50]);
Product::create(['name' => 'Vue Sticker',      'category' => 'merch',    'price' => 15000,   'stock' => 200]);
Product::create(['name' => 'Inertia Mug',      'category' => 'merch',    'price' => 75000,   'stock' => 30]);
Product::create(['name' => 'Tailwind Hoodie',  'category' => 'apparel',  'price' => 450000,  'stock' => 20]);
Product::create(['name' => 'PHP Pin',          'category' => 'merch',    'price' => 10000,   'stock' => 100]);
```

```bash
php artisan db:seed --class=ProductSeeder
```

### 3. Controller

```bash
php artisan make:controller ProductController
```

```php
// app/Http/Controllers/ProductController.php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        // filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // search by name
        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        // sort
        $sort = $request->get('sort', 'name');
        $dir  = $request->get('dir', 'asc');
        $query->orderBy($sort, $dir);

        $products = $query->paginate(10)->withQueryString();

        return Inertia::render('Products/Index', [
            'products'   => $products,
            'filters'    => $request->only(['q', 'category', 'sort', 'dir']),
            'categories' => ['apparel', 'merch', 'book'],
        ]);
    }
}
```

### 4. Route

```php
// routes/web.php
use App\Http\Controllers\ProductController;

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
```

---

## Frontend: Vue Page

### `resources/js/Pages/Products/Index.vue`

```vue
<script setup>
import { ref, watch, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

const props = defineProps({
  products:   { type: Object, required: true },   // paginator
  filters:    { type: Object, default: () => ({}) },
  categories: { type: Array,  default: () => [] },
});

// local state (mirror dari props agar UX cepat)
const search = ref(props.filters.q ?? '');
const category = ref(props.filters.category ?? '');

// opsi 1: SERVER-SIDE search via Inertia
const submitSearch = () => {
  router.get(
    route('products.index'),
    { q: search.value, category: category.value },
    { preserveState: true, replace: true }
  );
};

// watch + debounce: trigger otomatis setelah 300ms user berhenti ngetik
watch([search, category], debounce(submitSearch, 300));

// opsi 2: CLIENT-SIDE sort (langsung dari data yang sudah ada)
const sortKey = ref('name');
const sortDir = ref('asc');

const sortedProducts = computed(() => {
  // products.data adalah array di paginator Laravel
  const arr = [...props.products.data];
  arr.sort((a, b) => {
    const aVal = a[sortKey.value];
    const bVal = b[sortKey.value];
    if (aVal < bVal) return sortDir.value === 'asc' ? -1 : 1;
    if (aVal > bVal) return sortDir.value === 'asc' ?  1 : -1;
    return 0;
  });
  return arr;
});

function toggleSort(key) {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = key;
    sortDir.value = 'asc';
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="mx-auto max-w-5xl">
      <h1 class="mb-6 text-3xl font-bold">Produk</h1>

      <!-- FILTER BAR -->
      <div class="mb-4 flex flex-wrap gap-2">
        <input
          v-model="search"
          @keyup.enter="submitSearch"
          placeholder="Cari produk..."
          class="flex-1 rounded border px-3 py-2"
        />
        <select v-model="category" class="rounded border px-3 py-2">
          <option value="">Semua kategori</option>
          <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
        </select>
        <button @click="submitSearch" class="rounded bg-blue-500 px-4 py-2 text-white">
          Cari
        </button>
      </div>

      <!-- TABLE dengan client-side sort -->
      <div class="overflow-hidden rounded border bg-white">
        <table class="w-full">
          <thead class="bg-gray-100 text-left">
            <tr>
              <th
                v-for="col in ['name', 'category', 'price', 'stock']"
                :key="col"
                @click="toggleSort(col)"
                class="cursor-pointer px-4 py-2"
              >
                {{ col }}
                <span v-if="sortKey === col">
                  {{ sortDir === 'asc' ? '↑' : '↓' }}
                </span>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="p in sortedProducts"
              :key="p.id"
              class="border-t hover:bg-gray-50"
            >
              <td class="px-4 py-2">{{ p.name }}</td>
              <td class="px-4 py-2 text-gray-600">{{ p.category }}</td>
              <td class="px-4 py-2">Rp {{ p.price.toLocaleString() }}</td>
              <td class="px-4 py-2">{{ p.stock }}</td>
            </tr>
            <tr v-if="sortedProducts.length === 0">
              <td colspan="4" class="px-4 py-8 text-center text-gray-400">
                Tidak ada produk.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- PAGINATION: pakai <Link> dengan preserve-scroll -->
      <div class="mt-4 flex items-center justify-between">
        <p class="text-sm text-gray-500">
          Halaman {{ products.current_page }} dari {{ products.last_page }}
          ({{ products.total }} total)
        </p>

        <div class="flex gap-1">
          <Link
            v-for="link in products.links"
            :key="link.label"
            :href="link.url ?? '#'"
            :class="[
              'rounded px-3 py-1 text-sm',
              link.active ? 'bg-blue-500 text-white' : 'bg-white hover:bg-gray-100',
              !link.url && 'pointer-events-none opacity-50',
            ]"
            preserve-scroll
            v-html="link.label"
          />
        </div>
      </div>
    </div>
  </div>
</template>
```

---

## Konsep Penting

### 1. `debounce` — Trigger Setelah User Berhenti Ngetik

```js
import debounce from 'lodash/debounce';

watch([search, category], debounce(submitSearch, 300));
```

Tanpa debounce, tiap ketuk karakter = 1 HTTP request. Dengan debounce 300ms, request hanya dikirim setelah 300ms user tidak mengetik. **Ini murni pola Vue/JS** — Blade tidak punya konsep ini.

### 2. `router.get` dengan `preserveState`

```js
router.get('/products', { q: 'vue' }, { preserveState: true });
```

`preserveState: true` = setelah navigasi, state komponen (input value, scroll, dll) tetap ada. Cocok untuk filter/search yang tidak ingin "reset" UX.

### 3. `computed` untuk Sort Client-Side

`computed` lebih baik dari method di template karena **di-cache**:
```vue
<!-- ❌ method dipanggil tiap render -->
<tr v-for="p in sortBy(sortKey)" :key="p.id">

<!-- ✅ computed cache otomatis -->
<tr v-for="p in sortedProducts" :key="p.id">
```

### 4. Paginator Laravel → Vue

Response Laravel paginator di Inertia punya shape:
```js
{
  current_page: 1,
  data: [...],
  first_page_url: "...",
  from: 1,
  last_page: 5,
  last_page_url: "...",
  links: [{ url: null, label: "&laquo; Previous", active: false }, ...],
  next_page_url: "...",
  path: "...",
  per_page: 10,
  prev_page_url: null,
  to: 10,
  total: 50,
}
```

Bisa langsung di-loop di Vue.

### 5. `v-html` untuk Label Pagination

Pagination Laravel mengirim label dengan HTML entity (`&laquo;`, `&raquo;`). Pakai `v-html`:
```vue
<Link ... v-html="link.label" />
```
Hanya aman karena ini dari Laravel, bukan input user.

---

## Tantangan Modifikasi

- [ ] Tambah **filter harga** (range slider min-max).
- [ ] Tambah **checkbox "hanya yang tersedia"** (stock > 0).
- [ ] Tambah **"save filter"** di localStorage.
- [ ] Tambah **empty state illustration** saat hasil kosong.
- [ ] Ganti sort ke **server-side** (kirim `sort` & `dir` ke controller, hapus `computed sortedProducts`).
- [ ] Tambah **infinite scroll** (pakai `Inertia::merge()` di controller, scroll listener di Vue).

---

## Konsep Vue yang Sudah Dipakai

✅ `ref`, `computed`, `watch`
✅ `v-model` + `@keyup.enter`
✅ `v-for` + `:key`
✅ `v-if` (kondisional)
✅ `v-html` (render HTML dari Laravel paginator)
✅ `@click` + dynamic class binding
✅ `defineProps`
✅ Import helper eksternal (`lodash/debounce`)
✅ `router.get` + options (`preserveState`, `replace`)
✅ `<Link>` Inertia untuk pagination

Lanjut ke **[03-auth-flow.md](./03-auth-flow.md)** — form, validasi, error handling, `useForm`, dan flash message.
