# Penjelasan CRUD SPA dengan Image Upload dan Live Preview

Dokumen ini membahas implementasi fitur Post pada codebase ini berdasarkan perubahan Git saat ini. Fitur dibangun dengan Laravel 12 sebagai backend, Inertia Laravel sebagai penghubung server dan client, serta Vue 3 sebagai antarmuka. Fokus utamanya adalah navigasi bergaya SPA, upload gambar ke public storage, dan live preview sebelum file dikirim.

## 1. Ruang Lingkup Implementasi

File utama fitur ini adalah:

```text
routes/web.php
app/Http/Controllers/PostController.php
app/Models/Post.php
database/migrations/2026_09_11_154530_create_posts_table.php
resources/js/Pages/Posts/Index.vue
resources/js/Pages/Posts/Create.vue
resources/js/Pages/Posts/Edit.vue
```

Fitur yang sudah tersedia:

- menampilkan daftar post;
- membuka form tambah melalui navigasi Inertia;
- membuat post dengan gambar opsional;
- menampilkan live preview gambar sebelum submit;
- membuka form edit dengan gambar lama sebagai preview awal;
- mengganti gambar ketika post diperbarui;
- menghapus post beserta file gambarnya;
- menampilkan error validasi Laravel di form Vue.

Implementasi ini belum mempunyai halaman detail `show`. Karena `Route::resource()` masih mendaftarkan route `posts.show`, permintaan `GET /posts/{post}` akan diarahkan ke method `show()` yang belum tersedia. Jadi, istilah “Read” pada implementasi saat ini berarti membaca daftar post dan mengambil satu post untuk form edit, bukan halaman detail tersendiri.

## 2. Arsitektur Request Inertia

Inertia tidak membuat REST API JSON terpisah. Laravel tetap menangani routing, validasi, database, storage, dan redirect. Vue menangani tampilan serta interaksi browser.

Alur umumnya:

```text
Browser
  -> Inertia Link/useForm/router
  -> Laravel route
  -> PostController
  -> Eloquent + Storage
  -> Inertia::render() atau redirect
  -> komponen Vue diperbarui tanpa full-page reload
```

`bootstrap/app.php` menambahkan `\Inertia\Middleware::class` ke web middleware. Sementara itu, `resources/js/app.js` melakukan bootstrap aplikasi Vue melalui `createInertiaApp()` dan memuat page dari `resources/js/Pages/**/*.vue`.

Navigasi dianggap SPA karena:

- `<Link>` mencegat navigasi internal dan melakukan Inertia visit;
- `form.post()` mengirim form melalui Inertia;
- `router.delete()` mengirim request delete melalui Inertia;
- Laravel mengembalikan response Inertia atau redirect yang kemudian diproses tanpa memuat ulang seluruh dokumen HTML.

State halaman memang berganti, tetapi route, validasi, dan otorisasi tetap menjadi tanggung jawab Laravel.

## 3. Database: Tabel `posts`

Migration membuat tabel berikut:

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->string('image')->nullable();
    $table->timestamps();
});
```

Penjelasan setiap kolom:

- `id`: primary key post;
- `title`: judul post, dengan panjang default string Laravel maksimal 255 karakter;
- `content`: isi post dalam bentuk teks;
- `image`: path relatif file pada disk `public`, bukan isi binary dan bukan URL penuh;
- `created_at` dan `updated_at`: timestamp bawaan Eloquent.

Contoh nilai yang disimpan pada kolom `image`:

```text
posts/Hp8mVZP4hQWn3x.jpg
```

Menyimpan path relatif lebih fleksibel daripada URL penuh. URL publik dapat dibentuk saat data akan dikirim ke frontend, sedangkan lokasi storage dapat tetap dikelola oleh Laravel.

## 4. Model dan Mass Assignment

Model `Post` mendefinisikan field yang boleh diisi secara massal:

```php
protected $fillable = ['title', 'content', 'image'];
```

Konfigurasi ini diperlukan karena controller menggunakan:

```php
Post::create($validated);
$post->update($validated);
```

Hanya `title`, `content`, dan `image` yang dapat masuk melalui operasi tersebut. Model saat ini belum mempunyai relationship, cast, accessor `image_url`, soft delete, atau API Resource.

## 5. Routing REST

Route fitur didaftarkan dengan:

```php
Route::resource('posts', PostController::class);
```

Route resource yang dihasilkan adalah:

| Method | URL | Controller | Nama route | Status |
|---|---|---|---|---|
| GET | `/posts` | `index` | `posts.index` | tersedia |
| GET | `/posts/create` | `create` | `posts.create` | tersedia |
| POST | `/posts` | `store` | `posts.store` | tersedia |
| GET | `/posts/{post}` | `show` | `posts.show` | belum diimplementasikan |
| GET | `/posts/{post}/edit` | `edit` | `posts.edit` | tersedia |
| PUT/PATCH | `/posts/{post}` | `update` | `posts.update` | tersedia |
| DELETE | `/posts/{post}` | `destroy` | `posts.destroy` | tersedia |

Parameter `Post $post` pada `edit`, `update`, dan `destroy` menggunakan implicit route model binding. Laravel mengambil record berdasarkan ID pada URL. Jika record tidak ditemukan, Laravel otomatis memberikan response 404.

Jika memang tidak membutuhkan halaman detail, route sebaiknya dibatasi menjadi:

```php
Route::resource('posts', PostController::class)->except('show');
```

Sebaliknya, jika CRUD penuh termasuk halaman detail diperlukan, tambahkan method `show()` dan `Posts/Show.vue`.

## 6. Controller: Membaca dan Mengirim Data ke Vue

### 6.1 Method `index()`

Controller mengambil semua post dari yang terbaru:

```php
$posts = Post::latest()->get();
```

`latest()` secara default mengurutkan `created_at` secara descending. Setelah itu, setiap post ditransformasi agar mempunyai URL gambar yang siap digunakan browser:

```php
$posts->transform(function ($post) {
    return [
        'id' => $post->id,
        'title' => $post->title,
        'content' => $post->content,
        'image' => $post->image,
        'image_url' => $post->image
            ? asset('storage/' . $post->image)
            : null,
    ];
});
```

Data dikirim sebagai props Inertia:

```php
return Inertia::render('Posts/Index', [
    'posts' => $posts,
]);
```

`Posts/Index` mengarah ke `resources/js/Pages/Posts/Index.vue`. Props tersebut diterima dengan:

```javascript
defineProps({
    posts: Array,
});
```

Saat ini query menggunakan `get()`, sehingga seluruh post dimuat sekaligus. Untuk jumlah data besar, implementasi sebaiknya memakai pagination.

### 6.2 Method `create()`

Method ini tidak perlu mengambil data:

```php
return Inertia::render('Posts/Create');
```

Laravel hanya memilih page Vue yang harus dirender. State form dibuat di sisi Vue menggunakan `useForm()`.

### 6.3 Method `edit()`

Laravel menerima model dari route model binding, lalu menambahkan URL gambar:

```php
$post->image_url = $post->image
    ? asset('storage/' . $post->image)
    : null;

return Inertia::render('Posts/Edit', [
    'post' => $post,
]);
```

URL ini menjadi preview awal pada halaman edit. Perlu diperhatikan bahwa kontrak data `index()` dan `edit()` belum konsisten:

- `index()` memetakan field secara eksplisit;
- `edit()` mengirim model secara langsung dan menambahkan atribut dinamis.

Akibatnya, prop edit juga dapat membawa `created_at` dan `updated_at`, sedangkan item index tidak. API Resource, DTO, atau transformer bersama dapat dipakai jika kontrak data ingin dibuat konsisten.

## 7. Validasi Request

Method `store()` dan `update()` memakai rule yang sama:

```php
$validated = $request->validate([
    'title' => 'required|string|max:255',
    'content' => 'required|string',
    'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
]);
```

Maknanya:

- `title` wajib berupa string dan maksimal 255 karakter;
- `content` wajib berupa string;
- `image` opsional;
- jika dikirim, file harus dikenali sebagai gambar;
- format yang diterima adalah JPEG/JPG, PNG, dan GIF;
- ukuran maksimal adalah 2048 KB atau 2 MB.

Atribut `accept="image/*"` pada input file hanya membantu file picker browser dan bukan pengamanan. Backend tetap menjadi sumber validasi utama. Bahkan, `image/*` lebih luas daripada rule backend karena browser dapat menawarkan WebP atau SVG, sedangkan rule controller menolaknya.

Jika validasi gagal, Laravel melakukan redirect kembali dengan validation errors. Inertia memetakan error tersebut ke `form.errors`, misalnya:

```vue
<div v-if="form.errors.image">
    {{ form.errors.image }}
</div>
```

Rule saat ini ditulis dua kali. Form Request dapat digunakan untuk mengurangi duplikasi dan menempatkan validation/authorization concern di kelas khusus.

## 8. Penyimpanan Gambar

### 8.1 Menyimpan file baru

Pada proses create:

```php
if ($request->hasFile('image')) {
    $imagePath = $request->file('image')->store('posts', 'public');
    $validated['image'] = $imagePath;
}

Post::create($validated);
```

`store('posts', 'public')` berarti:

- disk yang dipakai adalah `public` dari `config/filesystems.php`;
- folder tujuan relatif adalah `posts`;
- lokasi fisiknya `storage/app/public/posts`;
- Laravel membuat nama file yang aman/unik;
- nilai path hasil penyimpanan dimasukkan ke database.

Karena controller menyebut disk `public` secara eksplisit, nilai default `FILESYSTEM_DISK` tidak menentukan lokasi upload fitur ini.

### 8.2 Membuat file dapat diakses browser

Direktori `storage/app/public` tidak langsung berada di document root. Jalankan:

```bash
php artisan storage:link
```

Perintah ini membuat link:

```text
public/storage -> storage/app/public
```

Dengan demikian, file berikut:

```text
storage/app/public/posts/Hp8mVZP4hQWn3x.jpg
```

bisa diakses melalui:

```text
/storage/posts/Hp8mVZP4hQWn3x.jpg
```

Controller membentuk URL penuh dengan:

```php
asset('storage/' . $post->image)
```

Nilai `APP_URL` harus benar agar URL yang dihasilkan sesuai dengan environment aplikasi.

## 9. Create Form dengan `useForm()`

State form pada `Create.vue` dibuat dengan:

```javascript
const form = useForm({
    title: '',
    content: '',
    image: null,
});
```

`useForm()` menyediakan:

- nilai field yang reactive;
- method request seperti `post()`;
- `form.errors` untuk error validasi;
- `form.processing` untuk status request;
- dukungan konversi payload yang berisi `File` menjadi `FormData`.

Field teks terhubung melalui `v-model`:

```vue
<input v-model="form.title">
<textarea v-model="form.content"></textarea>
```

File input tidak memakai `v-model`. Browser menyediakan file melalui event `change`, kemudian object `File` disimpan secara manual ke `form.image`.

Submit dilakukan dengan:

```javascript
const submit = () => {
    form.post('/posts');
};
```

Karena payload mengandung object `File`, Inertia mengirim data dalam format multipart/FormData. Laravel kemudian membacanya melalui `$request->file('image')`.

Tombol menggunakan `form.processing` agar tidak dapat diklik selama request:

```vue
<button :disabled="form.processing" type="submit">
```

Namun teks kondisional saat ini tetap diikuti teks utama, sehingga ketika request berlangsung tombol dapat menampilkan `Saving... Save Post`. Jika ingin mengganti label sepenuhnya, gunakan satu ekspresi kondisional.

## 10. Live Image Preview dengan Object URL

Live preview terjadi sepenuhnya di browser sebelum upload:

```javascript
const imagePreview = ref(null);

const handleImageChange = (e) => {
    const file = e.target.files[0];

    if (file) {
        form.image = file;
        imagePreview.value = URL.createObjectURL(file);
    } else {
        form.image = null;
        imagePreview.value = null;
    }
};
```

Alurnya:

1. user memilih file;
2. browser menghasilkan object `File`;
3. object tersebut disimpan ke `form.image` untuk payload upload;
4. `URL.createObjectURL(file)` membuat blob URL sementara;
5. blob URL dimasukkan ke `src` elemen `<img>`;
6. preview muncul tanpa mengirim request ke server.

Contoh blob URL:

```text
blob:http://localhost/74bfbd5c-...
```

Blob URL bukan lokasi final file dan tidak disimpan ke database. URL tersebut hanya hidup pada runtime browser. Setelah submit berhasil, URL gambar permanen berasal dari Laravel melalui `/storage/...`.

### Cleanup yang belum ada

Implementasi saat ini belum memanggil `URL.revokeObjectURL()`. Jika user berkali-kali mengganti pilihan file atau berpindah halaman dalam sesi SPA, object URL lama dapat tetap memakai memori sampai dokumen browser dibuang.

Pola yang lebih aman adalah:

```javascript
import { onUnmounted, ref } from 'vue';

const imagePreview = ref(null);
let objectUrl = null;

const revokePreview = () => {
    if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
    }
};

const handleImageChange = (event) => {
    revokePreview();

    const file = event.target.files[0];
    form.image = file ?? null;

    if (file) {
        objectUrl = URL.createObjectURL(file);
        imagePreview.value = objectUrl;
    } else {
        imagePreview.value = null;
    }
};

onUnmounted(revokePreview);
```

Kode tersebut adalah rekomendasi; belum menjadi bagian dari implementasi fitur saat ini.

## 11. Edit Form dan Preview Gambar Lama

State edit diawali dari props server:

```javascript
const form = useForm({
    title: props.post.title,
    content: props.post.content,
    image: null,
    _method: 'put',
});

const imagePreview = ref(props.post.image_url);
```

`image` sengaja bernilai `null`, bukan path gambar lama. Path lama sudah dimiliki record di database. Jika user tidak memilih file baru, controller tidak mengubah kolom `image` sehingga gambar lama tetap dipakai.

Preview edit mempunyai dua sumber:

- sebelum memilih file baru: URL permanen `props.post.image_url` dari server;
- setelah memilih file baru: blob URL dari `URL.createObjectURL(file)`.

Jika pemilihan file dibatalkan, implementasi mengembalikan preview ke URL gambar lama:

```javascript
form.image = null;
imagePreview.value = props.post.image_url;
```

Fitur saat ini tidak menyediakan pilihan untuk menghapus gambar lama tanpa mengunggah pengganti.

## 12. Method Spoofing pada Update

Form edit dikirim sebagai POST:

```javascript
form.post(`/posts/${props.post.id}`);
```

Tetapi payload mempunyai:

```javascript
_method: 'put'
```

Laravel membaca field `_method` lalu memperlakukan request sebagai `PUT`, sehingga request masuk ke route `posts.update`.

Pola ini berguna untuk multipart file upload pada stack Laravel/PHP. Native HTML form hanya menyediakan GET dan POST sebagai nilai method, dan multipart PUT/PATCH dapat mempunyai kendala parsing pada lingkungan server tertentu. Karena itu, pola yang umum adalah:

```text
HTTP request aktual: POST /posts/{id}
payload: _method=put
routing Laravel: PUT /posts/{post} -> update()
```

Perlu dibedakan bahwa `_method` pada implementasi ini adalah bagian dari data `useForm`, bukan options argument dari `form.post()`.

## 13. Mengganti Gambar Saat Update

Controller hanya mengganti gambar jika request benar-benar mempunyai file baru:

```php
if ($request->hasFile('image')) {
    if ($post->image) {
        Storage::disk('public')->delete($post->image);
    }

    $imagePath = $request->file('image')->store('posts', 'public');
    $validated['image'] = $imagePath;
}

$post->update($validated);
```

Ada dua kemungkinan:

### Tanpa file baru

- `hasFile('image')` bernilai false;
- file lama tidak dihapus;
- `validated` hanya memperbarui title/content;
- nilai kolom `image` lama tetap tersimpan.

### Dengan file baru

- file lama dihapus dari disk `public`;
- file baru disimpan;
- path baru dimasukkan ke `$validated['image']`;
- record database diperbarui.

Urutan saat ini menghapus file lama sebelum penyimpanan baru dan update database selesai. Jika penyimpanan atau query database gagal setelah delete, database dapat menunjuk ke file yang sudah tidak ada. Untuk kebutuhan production, pertimbangkan strategi yang lebih aman: simpan file baru terlebih dahulu, update database, lalu hapus file lama setelah proses utama berhasil, disertai cleanup jika terjadi exception.

## 14. Menghapus Post dan File

Delete dipicu dari `Index.vue`:

```javascript
const deletePost = (id) => {
    if (confirm('Are you sure you want to delete this post?')) {
        router.delete(`/posts/${id}`);
    }
};
```

`router.delete()` melakukan Inertia visit dengan HTTP DELETE tanpa full-page reload. Controller membersihkan file sebelum menghapus row:

```php
if ($post->image) {
    Storage::disk('public')->delete($post->image);
}

$post->delete();
```

Pembersihan file mencegah file menjadi orphan setelah post dihapus. Namun delete saat ini belum mempunyai state loading per baris, tombol disabled, callback error, atau perlindungan dari request berulang.

## 15. Menampilkan Data pada Index

`Index.vue` merender setiap post:

```vue
<tr v-for="post in posts" :key="post.id">
```

Gambar ditampilkan hanya jika `image_url` tersedia:

```vue
<img v-if="post.image_url" :src="post.image_url" alt="Post image">
<div v-else>No Image</div>
```

Link create dan edit memakai komponen Inertia:

```vue
<Link href="/posts/create">Create Post</Link>
<Link :href="`/posts/${post.id}/edit`">Edit</Link>
```

Karena menggunakan `<Link>`, navigasi internal diproses sebagai Inertia visit. URL pada implementasi saat ini masih hard-coded meskipun Ziggy tersedia di project.

## 16. Redirect dan Flash Message

Setelah create, update, atau delete, controller menjalankan redirect berikut:

```php
return redirect()
    ->route('posts.index')
    ->with('success', '...');
```

Index mencoba membaca:

```vue
$page.props.flash?.success
```

Akan tetapi, codebase saat ini hanya memasang middleware Inertia generik dan belum mempunyai `HandleInertiaRequests` dengan method `share()` untuk mengirim flash session sebagai props. Akibatnya, pesan sukses kemungkinan tidak tampil walaupun sudah disimpan ke session.

Konsep konfigurasi yang diperlukan adalah membagikan flash sebagai shared prop:

```php
'flash' => [
    'success' => fn () => $request->session()->get('success'),
],
```

Cuplikan tersebut menjelaskan solusi yang diperlukan, bukan konfigurasi yang sudah ada pada codebase.

## 17. Alur End-to-End

### 17.1 Create

```text
GET /posts/create
-> PostController@create
-> Inertia render Posts/Create
-> user mengisi title/content dan memilih gambar
-> File disimpan di state useForm
-> blob URL dibuat untuk preview lokal
-> POST /posts sebagai multipart/FormData
-> validasi Laravel
-> file disimpan ke disk public
-> path disimpan ke posts.image
-> redirect ke posts.index
-> daftar post dimuat ulang melalui Inertia
```

### 17.2 Read/List

```text
GET /posts
-> PostController@index
-> Post::latest()->get()
-> relative image path diubah menjadi image_url
-> Inertia render Posts/Index dengan prop posts
-> Vue merender tabel dan gambar
```

### 17.3 Update

```text
GET /posts/{id}/edit
-> route model binding mengambil Post
-> controller menambahkan image_url
-> Inertia render Posts/Edit
-> preview awal memakai URL gambar lama
-> user dapat memilih gambar baru dan melihat blob preview
-> POST /posts/{id} dengan _method=put
-> Laravel merutekan sebagai PUT
-> validasi
-> jika ada file baru, gambar lama diganti
-> record diperbarui
-> redirect ke index
```

### 17.4 Delete

```text
user menekan Delete
-> confirm() browser
-> router.delete('/posts/{id}')
-> route model binding mengambil Post
-> file gambar dihapus jika ada
-> row database dihapus
-> redirect ke index melalui Inertia
```

## 18. Kondisi dan Batasan Implementasi Saat Ini

Hal berikut penting dipahami agar dokumentasi tidak menggambarkan fitur lebih lengkap daripada kondisi codebase:

1. Route `posts.show` terdaftar, tetapi method `show()` dan `Show.vue` belum ada.
2. Shared flash prop belum dikonfigurasi, sehingga alert sukses di Index kemungkinan tidak tampil.
3. Object URL live preview belum dibersihkan dengan `URL.revokeObjectURL()`.
4. Tidak ada opsi menghapus gambar lama tanpa memilih gambar pengganti.
5. Store dan update masih menduplikasi validation rules.
6. Bentuk data post pada Index dan Edit belum konsisten.
7. Semua post diambil tanpa pagination.
8. Route Post belum dilindungi authentication, policy, atau ownership authorization.
9. Operasi file dan database belum dirancang atomic ketika terjadi kegagalan.
10. Delete belum mempunyai loading/error state.
11. Upload progress belum ditampilkan meskipun Inertia form dapat menyediakan progress.
12. Belum ada automated test khusus CRUD dan upload gambar.
13. `php artisan storage:link` harus dipastikan sudah dijalankan pada setiap environment deployment.

## 19. Skenario Test yang Disarankan

Backend feature test sebaiknya mencakup:

- Index merender komponen `Posts/Index` dengan prop yang benar;
- create page merender `Posts/Create`;
- post dapat dibuat tanpa gambar;
- post dapat dibuat dengan fake image menggunakan `Storage::fake('public')`;
- title/content wajib;
- gambar selain format yang diizinkan ditolak;
- gambar lebih dari 2 MB ditolak;
- edit merender data dan `image_url`;
- update tanpa file baru mempertahankan gambar lama;
- update dengan file baru menyimpan file baru dan menghapus file lama;
- delete menghapus row dan file;
- ID yang tidak ditemukan menghasilkan 404;
- route show dihapus atau diimplementasikan;
- flash prop benar-benar dibagikan ke Inertia.

Frontend atau browser test sebaiknya memeriksa:

- memilih file langsung menampilkan preview;
- mengganti file memperbarui preview;
- membatalkan pilihan pada Edit kembali ke gambar lama;
- object URL dicabut ketika diganti atau komponen unmount;
- validation errors muncul pada field yang sesuai;
- submit tidak dapat dikirim ulang selama `form.processing`;
- delete meminta konfirmasi dan mempunyai status proses.

## 20. Kesimpulan

Fitur ini memakai pembagian tanggung jawab yang jelas: Laravel mengelola route, validasi, Eloquent, storage, dan redirect; Inertia membawa props dan request antarhalaman tanpa API terpisah; Vue mengelola state form, error, loading, serta live preview.

Bagian inti live preview adalah memisahkan dua kebutuhan file:

```text
form.image
-> object File asli yang akan di-upload

imagePreview
-> blob URL sementara yang hanya dipakai elemen <img>
```

Saat create, Laravel menyimpan file lalu mencatat relative path ke database. Saat edit, preview awal berasal dari URL server dan berubah menjadi blob URL jika user memilih file baru. Update dikirim sebagai POST multipart dengan `_method: 'put'`, lalu Laravel memperlakukannya sebagai PUT. Saat update atau delete, file lama dibersihkan agar storage tidak terus menumpuk.

Implementasi dasar tersebut sudah menunjukkan alur SPA dan upload secara utuh, tetapi masih perlu penyempurnaan pada route show, shared flash props, cleanup object URL, konsistensi operasi storage/database, pagination, authorization, dan automated tests sebelum dianggap siap untuk skenario production.
