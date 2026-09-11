# Dokumentasi: CRUD SPA dengan Laravel, Inertia, dan Vue

Dokumen ini menjelaskan alur kerja dan kode-kode penting di balik implementasi Single Page Application (SPA) CRUD yang telah kita buat.

## 1. Backend (Laravel)

### A. Model `Post`
**File:** `app/Models/Post.php`
```php
class Post extends Model
{
    use HasFactory;
    
    // Property $fillable digunakan untuk mengizinkan Mass Assignment.
    // Ini berarti kita hanya mengizinkan kolom 'title' dan 'content' yang bisa diisi secara otomatis 
    // ketika menggunakan fungsi seperti Post::create($request->all()) atau saat update.
    protected $fillable = ['title', 'content'];
}
```

### B. Route
**File:** `routes/web.php`
```php
// Route resource akan otomatis membuatkan route untuk seluruh aksi CRUD standar 
// (index, store, update, destroy). Kita mengecualikan 'create', 'show', dan 'edit'
// karena proses pembuatan form dan edit form dilakukan via Modal di sisi Vue, 
// sehingga kita tidak memerlukan halaman atau route terpisah.
Route::resource('posts', PostController::class)->except(['create', 'show', 'edit']);
```

### C. Controller `PostController`
**File:** `app/Http/Controllers/PostController.php`

- **Fungsi `index()`**:
  ```php
  public function index()
  {
      // Inertia::render berfungsi mirip seperti view() di Laravel standar.
      // Parameter pertama adalah nama komponen Vue di folder resources/js/Pages (yaitu 'Posts/Index').
      // Parameter kedua adalah data (props) yang akan dikirim ke komponen Vue (daftar seluruh posts).
      return Inertia::render('Posts/Index', [
          'posts' => Post::latest()->get()
      ]);
  }
  ```

- **Fungsi `store()` dan `update()`**:
  ```php
  public function store(Request $request)
  {
      // Memvalidasi request. Inertia otomatis akan menangkap dan melempar
      // pesan error validasi ke frontend (dapat diakses via form.errors di Vue).
      $validated = $request->validate([
          'title' => 'required|string|max:255',
          'content' => 'required|string',
      ]);
      
      Post::create($validated);
      
      // Berbeda dengan API biasa yang mengembalikan JSON, pada Inertia kita cukup 
      // melakukan redirect. Inertia di Frontend (Vue) tidak akan memuat ulang halaman secara penuh,
      // melainkan hanya merequest data terbaru dan merender ulang komponen secara otomatis (SPA).
      return redirect()->back();
  }
  ```

---

## 2. Frontend (Vue 3 dengan Composition API & Inertia)

**File:** `resources/js/Pages/Posts/Index.vue`

### A. Konsep `script setup` dan Reactivity
```vue
<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
```
- `<script setup>` merupakan syntactic sugar dari Vue 3 Composition API yang mempermudah penulisan logika agar lebih ringkas.
- `ref()` digunakan untuk membuat sebuah variabel menjadi *reactive*. Jika nilai dari sebuah `ref` berubah, maka Vue akan otomatis memperbarui tampilan UI yang menggunakan variabel tersebut (contoh: `isModalOpen`).

### B. Menerima Data dari Laravel (`defineProps`)
```vue
const props = defineProps({
    posts: Array
});
```
Fungsi `defineProps` akan menerima *Data Array* yang dikirim dari controller `Inertia::render('Posts/Index', ['posts' => ...])` di atas.

### C. Inertia `useForm`
```vue
const form = useForm({
    title: '',
    content: ''
});
```
Fungsi `useForm` adalah sebuah penolong/helper khusus dari Inertia. Fungsinya sangat luar biasa karena menaungi:
1. Menyimpan data dari input (`form.title`, `form.content`).
2. Menyediakan fungsi pengiriman ke backend (`form.post()`, `form.put()`).
3. Menangkap otomatis *validation errors* dari Laravel dan menyimpannya di `form.errors`.
4. Mengetahui *state* form apakah sedang disubmit atau belum (melalui `form.processing`).

### D. Penjelasan Fungsi Penting di Vue
- **`openModal(post = null)`**: Jika dipanggil dengan sebuah parameter data post, artinya itu adalah proses **Edit** (isEdit di-set jadi true), lalu kita isikan data post tersebut ke form. Jika dipanggil kosong, itu proses **Add**, maka form di-reset menjadi kosong.
- **`closeModal()`**: Menutup tampilan modal, lalu melakukan `form.reset()` untuk mengosongkan teks input dan `form.clearErrors()` untuk menghilangkan pesan error jika form batal disubmit.
- **`submit()`**: Mengecek jika `isEdit` adalah `true`, maka ia akan menembakkan _request_ `put()` untuk Update. Jika bukan, akan menggunakan _request_ `post()` untuk Create. *Method* `onSuccess` dipanggil **hanya** jika request berhasil diselesaikan Laravel (validasi lulus dan redirect back berhasil).
- **`router.delete(url)`**: Fungsi dari `@inertiajs/vue3` untuk melakukan HTTP DELETE.

---

## 3. Penjelasan Directive pada Template Vue (HTML)

Directive adalah sintaks khusus Vue (biasanya diawali dengan `v-`, `@`, atau `:`) yang ditempel pada HTML.

- **`v-for="post in posts" :key="post.id"`**:
  Fungsi perulangan mirip *foreach* di PHP. Ini akan menduplikasi baris tabel `<tr>` sebanyak data `posts`. Atribut `:key` sangat diwajibkan oleh Vue supaya Vue bisa membedakan setiap baris secara unik saat mengupdate DOM.
  
- **`v-if="isModalOpen"`**:
  Fungsi kondisional (if). Elemen HTML (dalam hal ini `<div>` Modal) **hanya** akan dirender/ditampilkan di browser jika nilai `isModalOpen` adalah *true*.

- **`v-model="form.title"`**:
  *Two-way data binding*. Artinya, teks apapun yang diketik oleh user di kotak input akan langsung disimpan secara otomatis pada variabel `form.title`. Dan sebaliknya, jika kita ubah nilai di script, input di browser otomatis berganti teksnya.
  
- **`@click="openModal()"`**:
  Listener _event handler_. Ini sama seperti `onclick="..."` di HTML biasa. Jika diklik, maka ia akan mengeksekusi method `openModal()`. Simbol `@` merupakan singkatan dari `v-on:`.

- **`@submit.prevent="submit"`**:
  Diletakkan di `<form>`. Ini merespon saat *button type="submit"* di-klik atau user menekan *Enter* di input.
  Akhiran `.prevent` (disebut _modifier_) berfungsi untuk mencegah form merefresh keseluruhan web secara penuh layaknya _event.preventDefault()_ pada javascript dasar. Jadi form disubmit secara *background* (AJAX).

- **`:class="{ 'border-red-500': form.errors.title }"`**:
  *Dynamic Class Binding* (simbol `:` adalah singkatan dari `v-bind:`). CSS *class* `border-red-500` hanya akan diterapkan/aktif ke kotak input apabila `form.errors.title` bernilai *true* (artinya: ketika ada pesan error dari backend).

- **`:disabled="form.processing"`**:
  Membuat tombol **Save** tidak dapat di-klik lagi secara double ketika request sedang diproses (*processing* / _loading_). Ini mencegah user menekan tombol save berkali-kali.
