# Studi Kasus 04 — Multi-select Tag dengan Laravel, Inertia, dan Vue 3

Panduan ini membedah implementasi aktual studi kasus multi-select project/tag di repository ini. Fokus terbesar ada pada Vue: state array, `computed`, pencarian opsi, memilih/membatalkan pilihan, chip pilihan, event form, error Inertia, serta cara dropdown dirender tanpa menimpa konten di bawahnya.

## Hasil yang Dibangun

Halaman menyediakan form untuk membuat project dan memilih 0–10 tag. Satu project dapat mempunyai banyak tag, dan satu tag dapat terhubung dengan banyak project.

```text
GET  /projects/multi-select   menampilkan form dan data
POST /projects/multi-select   menyimpan project serta tag yang dipilih
```

Pilihan frontend disimpan sebagai array ID, misalnya `[2, 5, 8]`. Laravel menyimpan setiap hubungan project/tag sebagai baris sendiri dalam pivot `project_tag`.

## Berkas Implementasi

| Bagian | Berkas |
|---|---|
| Tabel project | `database/migrations/2026_09_25_092653_create_projects_table.php` |
| Tabel tag | `database/migrations/2026_09_25_092654_create_tags_table.php` |
| Tabel relasi | `database/migrations/2026_09_25_092656_create_project_tag_table.php` |
| Model project | `app/Models/Project.php` |
| Model tag | `app/Models/Tag.php` |
| Controller | `app/Http/Controllers/ProjectController.php` |
| Route | `routes/web.php` |
| Seeder contoh | `database/seeders/DatabaseSeeder.php` |
| Halaman Vue | `resources/js/Pages/Projects/MultiSelect.vue` |
| Feature test | `tests/Feature/ProjectMultiSelectTest.php` |

---

# 1. Pahami Bentuk Data Multi-select

Frontend tidak mengirim nama tag sebagai teks bebas. Ia mengirim array ID yang telah dipilih:

```json
{
  "name": "Portal inventori",
  "description": "Pengelolaan inventori internal",
  "tag_ids": [2, 5, 8]
}
```

ID dipakai karena `tags` adalah sumber data master. Database bisa memastikan setiap ID benar-benar ada, mencegah relasi yatim, dan menyimpan hubungan secara normal melalui foreign key.

Relasi many-to-many tampak seperti ini:

```text
projects                 project_tag                 tags
--------                 -----------                 ----
id                       project_id                  id
name                     tag_id                      name
                         created_at                  color
                         updated_at
```

Jika project `17` memilih tag `2`, `5`, dan `8`, pivot berisi tiga baris:

| project_id | tag_id |
|---:|---:|
| 17 | 2 |
| 17 | 5 |
| 17 | 8 |

Jangan menyimpan `2,5,8` ke satu kolom string untuk relasi database. Dengan pivot, foreign key dan operasi relasi Eloquent dapat bekerja langsung.

---

# 2. Database: Migrations

## 2.1 Tabel `projects`

File `database/migrations/2026_09_25_092653_create_projects_table.php`:

```php
Schema::create('projects', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->timestamps();
});
```

Rincian syntax:

- `Schema::create('projects', ...)` membuat tabel bernama `projects`.
- `Blueprint $table` adalah objek schema builder yang digunakan untuk mendefinisikan kolom.
- `$table->id()` membuat primary key auto-increment `id`.
- `$table->string('name')` membuat kolom string dengan batas panjang default database.
- `$table->text('description')->nullable()` membuat teks panjang yang boleh bernilai SQL `NULL`.
- `$table->timestamps()` membuat kolom `created_at` dan `updated_at` yang digunakan Eloquent.
- `down()` menghapus tabel saat migration di-rollback.

## 2.2 Tabel `tags`

File `database/migrations/2026_09_25_092654_create_tags_table.php`:

```php
Schema::create('tags', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->string('color', 7)->default('#64748b');
    $table->timestamps();
});
```

Rincian syntax:

- `name` adalah label yang terlihat pada pilihan.
- `unique()` membuat unique index, sehingga dua row tidak dapat memiliki nama tag yang sama.
- `string('color', 7)` menyimpan nilai warna contoh seperti `#38bdf8` (7 karakter termasuk `#`).
- `default('#64748b')` dipakai bila pembuatan tag tidak memberikan warna.
- Warna di frontend hanya dipakai untuk titik kecil penanda tag, bukan sebagai warna teks.

## 2.3 Pivot `project_tag`

File `database/migrations/2026_09_25_092656_create_project_tag_table.php`:

```php
Schema::create('project_tag', function (Blueprint $table) {
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
    $table->primary(['project_id', 'tag_id']);
    $table->timestamps();
});
```

Rincian syntax:

- `foreignId('project_id')` mendefinisikan kolom ID untuk relasi ke project.
- `constrained()` mengikuti konvensi Laravel: `project_id` mengacu ke `projects.id`, sedangkan `tag_id` mengacu ke `tags.id`.
- `cascadeOnDelete()` menghapus hubungan pivot terkait ketika project atau tag induk dihapus.
- `primary(['project_id', 'tag_id'])` menjadikan pasangan kedua kolom sebagai primary key gabungan. Project yang sama tidak bisa memiliki tag yang sama dua kali.
- `timestamps()` menambahkan timestamp pivot yang dikelola oleh relasi `withTimestamps()`.
- Nama `project_tag` adalah konvensi nama pivot alfabetis dari dua model `Project` dan `Tag`.

Migration harus berjalan dalam urutan dependency: tabel `projects` dan `tags` terlebih dahulu, lalu pivot yang merujuk keduanya.

---

# 3. Model dan Relasi Eloquent

## 3.1 `Project`

File `app/Models/Project.php`:

```php
class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }
}
```

Penjelasan:

- `extends Model` menjadikan class sebagai Eloquent model untuk tabel `projects`.
- `HasFactory` memungkinkan model dipakai oleh factory dan test.
- `$fillable` adalah allowlist mass assignment. Hanya `name` dan `description` yang dapat diisi melalui `create([...])`.
- `tags(): BelongsToMany` mendeklarasikan method relasi beserta return type PHP.
- `belongsToMany(Tag::class)` mendefinisikan banyak project berelasi dengan banyak tag melalui pivot.
- `withTimestamps()` membuat Eloquent mengisi timestamp di pivot saat relasi disimpan.

## 3.2 `Tag`

File `app/Models/Tag.php`:

```php
class Tag extends Model
{
    protected $fillable = ['name', 'color'];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withTimestamps();
    }
}
```

Relasi ditulis dari arah sebaliknya agar kita juga dapat meminta project dari tag, contohnya `$tag->projects`. `$fillable` mengizinkan field master tag `name` dan `color` diisi secara mass assignment.

Dengan konvensi nama tabel/kolom, Laravel menyimpulkan tabel pivot `project_tag`, `project_id`, dan `tag_id`. Nama dapat ditulis eksplisit sebagai argumen `belongsToMany()` jika tabel menggunakan konvensi berbeda.

---

# 4. Routes: Menghubungkan URL ke Controller

File `routes/web.php`:

```php
Route::get('/projects/multi-select', [ProjectController::class, 'index'])
    ->name('projects.multi-select');

Route::post('/projects/multi-select', [ProjectController::class, 'store'])
    ->name('projects.multi-select.store');
```

- Route `GET` menjalankan `ProjectController@index` untuk mengambil props dan merender halaman.
- Route `POST` menjalankan `ProjectController@store` untuk validasi dan penyimpanan.
- `name()` memberi nama route yang stabil. Vue memanggilnya dengan Ziggy melalui `route('projects.multi-select.store')`; kode frontend tidak perlu mengulang URL hardcoded.
- Verb dipisah karena GET membaca/menampilkan data, sedangkan POST mengubah data.

---

# 5. Controller: Membaca Props, Memvalidasi, dan Menyimpan

## 5.1 Method `index()`

File `app/Http/Controllers/ProjectController.php`:

```php
public function index(): Response
{
    return Inertia::render('Projects/MultiSelect', [
        'success' => session('success'),
        'tags' => Tag::query()->orderBy('name')->get(['id', 'name', 'color']),
        'projects' => Project::query()
            ->with(['tags:id,name,color'])
            ->latest()
            ->limit(10)
            ->get(['id', 'name', 'description', 'created_at']),
    ]);
}
```

Rincian:

- `: Response` menyatakan return type Inertia response.
- `Inertia::render('Projects/MultiSelect', [...])` merender file `resources/js/Pages/Projects/MultiSelect.vue` dan membagikan object sebagai props.
- `'success' => session('success')` mengambil flash message yang diset setelah redirect.
- `Tag::query()` mulai query builder tag.
- `orderBy('name')` mengurutkan tag berdasarkan nama.
- `get(['id', 'name', 'color'])` hanya mengambil kolom yang dibutuhkan UI.
- `with(['tags:id,name,color'])` eager load relasi tag untuk daftar project; ini mencegah query tambahan untuk setiap project (N+1).
- `latest()` mengurutkan project dari `created_at` paling baru.
- `limit(10)` membatasi daftar contoh ke 10 project.
- `get([...])` pada daftar project aman dalam kasus ini karena dibatasi 10 row. Untuk daftar besar, gunakan pagination server-side.

Bentuk data Inertia kurang lebih:

```js
{
  success: null,
  tags: [
    { id: 1, name: 'Backend', color: '#a78bfa' },
    { id: 2, name: 'Design', color: '#f472b6' }
  ],
  projects: [
    {
      id: 1,
      name: 'Portal inventori',
      description: '...',
      created_at: '...',
      tags: [{ id: 1, name: 'Backend', color: '#a78bfa' }]
    }
  ]
}
```

## 5.2 Method `store()` dan validasi request

```php
public function store(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:120'],
        'description' => ['nullable', 'string', 'max:1000'],
        'tag_ids' => ['nullable', 'array', 'max:10'],
        'tag_ids.*' => ['required', 'integer', 'distinct', 'exists:tags,id'],
    ]);
```

`Request $request` berisi data POST yang dikirim dari browser. `validate()` memeriksa semua aturan sebelum database diubah.

| Rule | Kegunaan |
|---|---|
| `required` | Nama wajib diisi |
| `string` | Nilai harus berupa teks |
| `max:120` | Panjang nama maksimal 120 karakter |
| `nullable` | Nilai boleh kosong/null |
| `array` | `tag_ids` harus array, bukan string berisi koma |
| `max:10` | Array berisi maksimal 10 pilihan |
| `tag_ids.*` | Terapkan rules ke setiap elemen array |
| `integer` | Setiap ID harus integer |
| `distinct` | ID yang sama tidak boleh muncul berulang |
| `exists:tags,id` | Setiap ID harus ada pada kolom `id` tabel `tags` |

Nama dengan dot notation `tag_ids.*` berarti “untuk semua elemen di dalam `tag_ids`”. Error item bisa memiliki key `tag_ids.0`, `tag_ids.1`, dan seterusnya.

Validasi backend wajib walaupun Vue membatasi pilihan. Request dapat dibuat di luar UI, jadi batas frontend tidak menjadi pengaman data.

## 5.3 Transaksi, pembuatan project, dan `sync()`

```php
    DB::transaction(function () use ($validated): void {
        $project = Project::query()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $project->tags()->sync($validated['tag_ids'] ?? []);
    });
```

- `DB::transaction()` menggabungkan pembuatan project dan relasi sebagai satu unit database. Jika salah satunya gagal, transaksi dapat di-rollback.
- `function () use ($validated)` adalah closure. `use` membawa variabel `$validated` dari scope luar ke dalam closure.
- `Project::query()->create([...])` membuat row baru memakai field yang telah lolos validasi dan diizinkan `$fillable` model.
- `$validated['description'] ?? null` memakai null coalescing: jika key tidak ada atau nilainya null, gunakan `null`.
- `$project->tags()` memanggil relasi many-to-many dari project yang baru dibuat.
- `sync([...])` membuat relasi pivot sesuai tepat dengan ID yang diberikan. Di form create belum ada hubungan lama; pada form edit, hubungan yang tak lagi dipilih akan dilepas.
- `?? []` membuat pilihan tag opsional. Bila field tidak dikirim, project dibuat tanpa tag.

Contoh array:

```php
$project->tags()->sync([2, 5, 8]);
```

akan menyimpan tiga baris ke `project_tag`.

## 5.4 Redirect dan flash message

```php
    return to_route('projects.multi-select')
        ->with('success', 'Project berhasil dibuat dengan tag yang dipilih.');
}
```

`to_route()` mengarahkan kembali ke route bernama. `with()` menyimpan flash data satu kali di session. Request GET berikutnya membacanya di `session('success')`, lalu Vue menampilkannya sebagai status sukses.

Validasi Laravel yang gagal akan redirect kembali secara otomatis, memasukkan error ke session, dan Inertia `useForm` akan mengisi `form.errors` di Vue.

---

# 6. Seeder: Opsi Tag untuk Dicoba

Di `database/seeders/DatabaseSeeder.php`, data contoh dibuat dari array:

```php
foreach ([
    ['name' => 'Frontend', 'color' => '#38bdf8'],
    ['name' => 'Backend', 'color' => '#a78bfa'],
    ['name' => 'Design', 'color' => '#f472b6'],
    ['name' => 'Laravel', 'color' => '#fb7185'],
    ['name' => 'Vue', 'color' => '#34d399'],
    ['name' => 'In Progress', 'color' => '#fbbf24'],
    ['name' => 'Documentation', 'color' => '#94a3b8'],
    ['name' => 'High Priority', 'color' => '#f87171'],
] as $tag) {
    Tag::query()->firstOrCreate(['name' => $tag['name']], ['color' => $tag['color']]);
}
```

- Array berisi konfigurasi tiap tag (`name`, `color`).
- `foreach (... as $tag)` memproses satu item pada setiap iterasi.
- `firstOrCreate(criteria, values)` mencari berdasarkan nama; jika belum ditemukan, membuat tag dengan warna yang diberikan.
- Seeder dapat dijalankan kembali tanpa menambahkan duplikat nama karena nama bersifat unik dan operasi memakai `firstOrCreate()`.

---

# 7. Frontend Vue: Penjelasan Detail

File utama: `resources/js/Pages/Projects/MultiSelect.vue`.

## 7.1 Struktur Single File Component

```vue
<script setup>
...
</script>

<template>
    ...
</template>
```

Vue Single File Component menaruh logika JavaScript di `<script setup>` dan markup di `<template>`. `setup` adalah sintaks Composition API yang membuat deklarasi di script tersedia secara langsung di template. Tidak perlu `return` object seperti Options API.

## 7.2 Import yang digunakan

```js
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
```

- `Head` mengatur metadata/title halaman melalui Inertia.
- `useForm` menyediakan state form Inertia, submit POST, errors, processing, dan reset.
- `ref` membuat state reaktif sederhana.
- `computed` membuat state turunan yang dihitung dari state lain.

Tidak ada library multi-select eksternal di implementasi ini. Kontrol dibuat langsung dengan Vue sehingga perilaku data dan event dapat dipelajari tanpa abstraksi tambahan.

## 7.3 Props dari Laravel

Kode:

```js
const props = defineProps({
    tags: {
        type: Array,
        required: true,
    },
    projects: {
        type: Array,
        required: true,
    },
    success: {
        type: String,
        default: null,
    },
});
```

Penjelasan syntax:

- `defineProps()` adalah macro Vue yang mendeklarasikan data yang diterima komponen.
- `tags` adalah array opsi tag dari controller.
- `projects` adalah array project terbaru dengan relasi `tags` yang sudah di-load.
- `success` adalah flash message opsional. `default: null` membuat halaman tetap valid saat tidak ada message.
- `required: true` menyatakan prop harus diberikan oleh server.
- Hasil `defineProps()` disimpan di `props`, jadi script mengakses `props.tags`, sedangkan template dapat memakai `tags` secara langsung.

Pemetaan:

```text
Inertia::render(..., ['tags' => ...])   -> props.tags / template tags
Inertia::render(..., ['projects' => ...]) -> props.projects / template projects
Inertia::render(..., ['success' => ...])  -> props.success / template success
```

## 7.4 Form Inertia dan bentuk state multi-select

Kode:

```js
const form = useForm({
    name: '',
    description: '',
    tag_ids: [],
});
```

`useForm()` membungkus nilai-nilai form dalam object reaktif Inertia. Semua nilai ini akan dikirim saat `form.post()` dipanggil.

Yang membedakan multi-select dari select biasa adalah:

```js
tag_ids: []
```

Pilihan disimpan sebagai array:

```js
form.tag_ids = [2, 5, 8];
```

Gunakan ID integer, bukan nama tag, karena ID menjadi foreign key pada pivot. State juga memakai nama `tag_ids` agar kontraknya jelas antara Vue dan rule Laravel `tag_ids.*`.

## 7.5 State lokal dropdown dan search

```js
const isOpen = ref(false);
const search = ref('');
```

- `isOpen` bernilai boolean: `false` saat panel tertutup dan `true` saat terbuka.
- `search` menyimpan teks pencarian.
- `ref()` menghasilkan ref reaktif. Di JavaScript nilainya dibaca/diubah melalui `.value`; di template Vue otomatis melakukan unwrapping.

Contoh di script:

```js
isOpen.value = true;
search.value.trim();
```

Contoh di template:

```vue
v-if="isOpen"
v-model="search"
```

## 7.6 `filteredTags`: filter daftar berdasarkan input

Kode aktual:

```js
const filteredTags = computed(() => {
    const term = search.value.trim().toLocaleLowerCase();

    if (!term) {
        return props.tags;
    }

    return props.tags.filter((tag) => tag.name.toLocaleLowerCase().includes(term));
});
```

Rincian langkah:

1. `computed(() => { ... })` membuat nilai turunan reaktif. Vue mengamati `search.value` dan `props.tags` yang dibaca di dalam callback.
2. `.trim()` membuang spasi awal/akhir. Search berisi `'  vue '` akan menjadi `'vue'`.
3. `.toLocaleLowerCase()` membuat pencarian tidak membedakan huruf besar/kecil untuk data ini.
4. `if (!term)` mengembalikan seluruh tag jika search kosong.
5. `props.tags.filter(...)` menghasilkan array baru hanya berisi item yang lolos kondisi.
6. `tag.name.toLocaleLowerCase().includes(term)` mencari substring pada nama tag.

Contoh: bila `search.value` berisi `end`, opsi `Backend` tetap cocok.

Catatan: pencarian ini berjalan di client karena daftar tag contoh kecil. Bila pilihan berjumlah puluhan ribu, pencarian sebaiknya dipindahkan ke endpoint Laravel (query, debounce, pagination).

## 7.7 `selectedTags`: ubah array ID menjadi object tag

Kode:

```js
const selectedTags = computed(() =>
    props.tags.filter((tag) => form.tag_ids.includes(tag.id)),
);
```

State form hanya menyimpan ID, tetapi UI perlu nama dan warna untuk chip. Computed ini mencocokkan setiap object tag dengan `form.tag_ids`.

- `form.tag_ids.includes(tag.id)` menghasilkan boolean.
- `.filter()` membuat array object tag yang ID-nya sudah dipilih.
- Template menggunakan `selectedTags.length` untuk menghitung jumlah pilihan dan me-render chip.

## 7.8 `tagError`: gabungkan error field array

Kode:

```js
const tagError = computed(() =>
    form.errors.tag_ids ?? Object.entries(form.errors).find(([key]) => key.startsWith('tag_ids.'))?.[1],
);
```

Laravel dapat mengirim error pada key umum `tag_ids` (misalnya jumlah item melebihi 10) atau key item seperti `tag_ids.0` (ID pertama tidak valid).

- `form.errors.tag_ids` memeriksa error umum field.
- Operator `??` menggunakan nilai kanan hanya jika nilai kiri `null` atau `undefined`.
- `Object.entries(form.errors)` mengubah object error menjadi pasangan `[key, value]`.
- `.find(([key]) => key.startsWith('tag_ids.'))` mencari error pertama untuk salah satu index array. Parameter destructuring `([key])` mengambil bagian pertama dari pasangan.
- `?.[1]` optional chaining mengambil value error dari pasangan yang ditemukan, tetapi tidak melempar error jika `.find()` tidak menemukan item.
- Computed menghindari memecah logika pencarian error ini ke dalam markup.

Di template, satu blok cukup:

```vue
<p v-if="tagError">{{ tagError }}</p>
```

## 7.9 `toggleTag()`: pilih atau batalkan pilihan

Kode aktual:

```js
function toggleTag(tagId) {
    if (form.tag_ids.includes(tagId)) {
        removeTag(tagId);
        return;
    }

    if (form.tag_ids.length < 10) {
        form.tag_ids = [...form.tag_ids, tagId];
    }
}
```

Alur method:

1. `tagId` adalah ID opsi yang ditekan.
2. `includes(tagId)` mengecek apakah opsi sudah dipilih.
3. Jika sudah ada, panggil `removeTag(tagId)` lalu `return`, sehingga tidak masuk ke proses tambah.
4. Jika belum ada, periksa `form.tag_ids.length < 10`.
5. Spread syntax `[...form.tag_ids, tagId]` membuat array baru berisi pilihan sebelumnya dan ID baru.
6. Jika sudah 10 pilihan, function tidak menambahkan pilihan lagi.

Mengganti array dengan array baru memudahkan pelacakan perubahan state dan menghindari mutasi tersembunyi.

## 7.10 `removeTag()`: hapus pilihan dari array

```js
function removeTag(tagId) {
    form.tag_ids = form.tag_ids.filter((id) => id !== tagId);
}
```

`filter()` mempertahankan semua ID kecuali ID yang akan dihapus. Function dipakai oleh:

- opsi yang diklik kembali pada list;
- tombol `Hapus` pada chip.

## 7.11 `submit()`: kirim form ke Laravel

Kode:

```js
function submit() {
    form.post(route('projects.multi-select.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            search.value = '';
            isOpen.value = false;
        },
    });
}
```

Penjelasan:

- `route('projects.multi-select.store')` menggunakan nama route Ziggy dan membuat URL POST.
- `form.post(url, options)` mengirim semua field dalam `form`, termasuk `tag_ids` array.
- `preserveScroll: true` meminta Inertia menjaga posisi scroll saat redirect.
- `onSuccess` callback berjalan ketika request sukses.
- `form.reset()` mengembalikan nilai form ke default awal (`name: ''`, `description: ''`, `tag_ids: []`).
- `search.value = ''` membersihkan query pencarian opsi.
- `isOpen.value = false` menutup panel setelah sukses.
- Jika validasi gagal, `onSuccess` tidak dijalankan; data yang diisi tetap terlihat dan `form.errors` digunakan untuk menampilkan error.

## 7.12 `<Head>` dan judul halaman

```vue
<Head title="Multi-select Tags" />
```

`Head` dari Inertia mengatur title dokumen. Ini bukan heading visual; heading visual adalah elemen `<h1>` di bawahnya.

## 7.13 Elemen form dan modifiers Vue

Form aktual:

```vue
<form class="mt-8 space-y-6" @submit.prevent="submit">
```

- `<form>` tetap mempertahankan semantics form HTML.
- `@submit` adalah shorthand Vue untuk `v-on:submit`.
- `.prevent` memanggil `event.preventDefault()` sehingga browser tidak melakukan submit native/reload penuh.
- Setelah event dicegah, Vue memanggil method `submit()` yang menggunakan Inertia.
- `space-y-6` adalah utility Tailwind yang memberi jarak vertikal konsisten pada child form.

## 7.14 Input nama project

Potongan aktual:

```vue
<label for="project-name">Nama project</label>
<input
    id="project-name"
    v-model="form.name"
    type="text"
    maxlength="120"
    required
    autocomplete="off"
    placeholder="Contoh: Portal inventori"
/>
<p v-if="form.errors.name">{{ form.errors.name }}</p>
```

Syntax dan atribut:

- `for="project-name"` menghubungkan label ke input dengan ID yang sama. Klik label akan memfokuskan input.
- `id` harus unik dalam halaman.
- `v-model="form.name"` menghubungkan nilai input dua arah dengan state form.
- `type="text"` menyatakan input teks.
- `maxlength="120"` dan `required` adalah constraint browser untuk UX; server tetap menjalankan validasi Laravel.
- `autocomplete="off"` meminta browser tidak mengisi nilai otomatis.
- `placeholder` memberi contoh, bukan pengganti label.
- `v-if` menampilkan pesan hanya jika error ada.
- `{{ form.errors.name }}` adalah interpolasi teks Vue yang di-escape.

## 7.15 Textarea description

```vue
<label for="project-description">Deskripsi (opsional)</label>
<textarea
    id="project-description"
    v-model="form.description"
    rows="3"
    maxlength="1000"
    placeholder="Jelaskan tujuan project..."
/>
<p v-if="form.errors.description">{{ form.errors.description }}</p>
```

`textarea` digunakan untuk teks multi-baris. `rows` adalah tinggi awal dalam jumlah baris. `resize-y` pada class mengizinkan resize vertikal. `maxlength` sejalan dengan rule Laravel `max:1000`.

## 7.16 Counter pilihan

```vue
<span>{{ form.tag_ids.length }} / 10 dipilih</span>
```

- `.length` membaca jumlah ID dalam array.
- Interpolasi `{{ ... }}` merender angka ke teks.
- Karena `form.tag_ids` reaktif, counter otomatis berubah ketika memilih/menghapus.
- `tabular-nums` membuat angka memakai lebar digit yang konsisten.

## 7.17 Tombol pembuka panel dan accessibility attributes

```vue
<button
    type="button"
    aria-haspopup="listbox"
    :aria-expanded="isOpen"
    aria-labelledby="tag-picker-label"
    @click="isOpen = !isOpen"
>
    <span v-if="selectedTags.length">{{ selectedTags.length }} tag dipilih</span>
    <span v-else>Pilih satu atau beberapa tag</span>
    <span aria-hidden="true">{{ isOpen ? 'Tutup' : 'Buka' }}</span>
</button>
```

Rincian:

- `type="button"` mencegah tombol ini menjadi submit button karena berada di dalam `<form>`.
- `aria-haspopup="listbox"` menjelaskan jenis popup ke accessibility API.
- `:aria-expanded="isOpen"` adalah dynamic attribute: nilainya berubah sesuai state.
- `aria-labelledby="tag-picker-label"` menggunakan label `Tags` sebagai nama kontrol.
- `@click="isOpen = !isOpen"` membalik boolean: `false → true` atau `true → false`.
- `v-if` dan `v-else` menampilkan teks berbeda saat ada/tidak ada pilihan.
- `aria-hidden="true"` mengecualikan kata dekoratif `Buka/Tutup` dari pembacaan screen reader; status sudah diberikan `aria-expanded`.

## 7.18 Chip pilihan dan tombol hapus

```vue
<div v-if="selectedTags.length" aria-label="Tag yang dipilih">
    <span v-for="tag in selectedTags" :key="tag.id">
        <span :style="{ backgroundColor: tag.color }" aria-hidden="true"></span>
        {{ tag.name }}
        <button
            type="button"
            :aria-label="`Hapus tag ${tag.name}`"
            @click="removeTag(tag.id)"
        >
            Hapus
        </button>
    </span>
</div>
```

- `v-if` hanya merender area chip jika ada pilihan.
- `v-for` mengulang tiap object dalam `selectedTags`.
- `:key="tag.id"` memberi key stabil kepada Vue saat pilihan berubah.
- `:style` adalah binding object style. Warna titik berasal dari nilai `tag.color`.
- `aria-hidden` menyembunyikan titik dekoratif yang tidak menyampaikan informasi tambahan.
- Template literal `` `Hapus tag ${tag.name}` `` membuat accessible name spesifik, seperti `Hapus tag Backend`.
- `@click` menghapus ID dari state melalui `removeTag()`.
- Tombol kecil juga diberi `type="button"` supaya tidak mengirim form.

Chip bukan sekadar dekorasi: masing-masing memiliki tindakan jelas untuk menghapus pilihan. Tag juga dapat dihapus dengan memilih ulang opsi pada panel.

## 7.19 Panel dropdown: inline, bukan overlay

Panel aktual:

```vue
<div v-if="isOpen" class="mt-2 w-full overflow-hidden rounded-lg border bg-neutral-900">
    ...
</div>
```

Poin layout penting: panel **tidak menggunakan** `position: absolute`/class Tailwind `absolute`. Karena itu panel tetap berada dalam normal document flow. Ketika panel terbuka, tinggi form bertambah dan elemen sesudahnya terdorong ke bawah.

Versi overlay `absolute` tidak mengambil ruang layout. Panel dapat menimpa isi bawah dan tinggi root tidak bertambah, sehingga area bawah halaman bisa terlihat seperti background putih. Untuk studi kasus ini opsi dibuat inline agar panel tidak overlap dan document/page bertambah tinggi secara normal.

- `v-if="isOpen"` memasang panel ke DOM saat terbuka, dan menghapusnya saat tertutup.
- `mt-2` memberi jarak dari trigger.
- `w-full` membuat panel mengikuti lebar kontrol.
- `overflow-hidden` menjaga child tidak keluar dari sudut/border panel.
- `max-h-56 overflow-y-auto` diterapkan ke daftar opsi, bukan semua panel: daftar punya tinggi maksimum dan bisa di-scroll sementara search dan footer tetap terlihat.

## 7.20 Search input panel

```vue
<input
    v-model="search"
    type="search"
    aria-label="Cari tag"
    placeholder="Cari tag..."
/>
```

- `v-model` menjaga `search` dan isi input sinkron.
- `type="search"` memberi semantics input pencarian dan affordance browser.
- `aria-label` memberi nama accessible karena tidak ada `<label>` visual terpisah.
- Setiap input berubah, `filteredTags` dihitung kembali.

## 7.21 Listbox, pilihan, dan checkbox visual

Struktur aktual:

```vue
<div role="listbox" aria-multiselectable="true" aria-labelledby="tag-picker-label">
    <button
        v-for="tag in filteredTags"
        :key="tag.id"
        type="button"
        role="option"
        :aria-selected="form.tag_ids.includes(tag.id)"
        :class="form.tag_ids.includes(tag.id) ? 'text-white' : 'text-neutral-300'"
        :disabled="!form.tag_ids.includes(tag.id) && form.tag_ids.length >= 10"
        @click="toggleTag(tag.id)"
    >
        <span :class="form.tag_ids.includes(tag.id) ? '...terpilih...' : '...belum terpilih...'">
            <svg v-if="form.tag_ids.includes(tag.id)">...</svg>
        </span>
        <span>{{ tag.name }}</span>
        <span v-if="form.tag_ids.includes(tag.id)">Dipilih</span>
    </button>
</div>
```

- `role="listbox"` menyatakan container sebagai daftar pilihan.
- `aria-multiselectable="true"` menyampaikan bahwa lebih dari satu option dapat dipilih.
- `role="option"` dan `:aria-selected` menyampaikan state tiap pilihan.
- Pilihan dicerminkan secara visual melalui kotak checkbox yang digambar dengan `<span>` dan tanda centang inline SVG.
- `v-if` hanya membuat SVG centang untuk item terpilih.
- `:class` memilih class berdasarkan boolean. Ini bukan mengubah state; hanya presentasi state.
- `:disabled` memblokir tag baru saat batas 10 tercapai. Pilihan terpilih tetap dapat diklik agar dapat dibatalkan.
- `@click` menjalankan toggle yang mengubah `form.tag_ids`.

Implementasi ini menggunakan button interaktif di dalam role listbox. Ia dapat diakses melalui kontrol fokus tombol standar; untuk combobox production dengan navigasi arrow-key, typeahead ARIA lengkap, dan manajemen fokus, pertimbangkan komponen headless/accessibility yang dirancang mengikuti pola APG. Search native dan pemilihan dengan pointer/keyboard Tab saat ini tetap berfungsi.

## 7.22 Tampilan empty search result

```vue
<p v-if="filteredTags.length === 0">
    Tidak ada tag yang cocok.
</p>
```

Ketika `filter()` mengembalikan array kosong, opsi diganti pesan kosong. Tanpa state ini, panel pencarian terlihat seolah gagal merender.

## 7.23 Panel footer dan tombol tutup

```vue
<div class="flex items-center justify-between ...">
    <span>Maksimal 10 tag</span>
    <button type="button" @click="isOpen = false">Selesai</button>
</div>
```

Footer memberi informasi batas pilihan dan tindakan eksplisit untuk menutup panel. Tombol memakai `type="button"`, sebab tombol berada dalam form dan bukan submit.

## 7.24 Error multi-select

```vue
<p v-if="tagError" class="mt-2 text-sm text-rose-300">{{ tagError }}</p>
```

`tagError` menyatukan error field global dan error pada item array. Error datang dari server; UI tidak menganggap validasi sisi client sebagai pengganti validasi Laravel.

## 7.25 Submit button: processing, disabled, dan label

```vue
<button type="submit" :disabled="form.processing">
    {{ form.processing ? 'Menyimpan...' : 'Simpan project' }}
</button>
```

- `type="submit"` memicu event submit form.
- `:disabled` mencegah klik berulang selama request.
- Ternary `condition ? A : B` mengubah label sesuai status request.
- Utility Tailwind `disabled:*` mengubah tampilan saat tombol disabled.
- `focus-visible:*` menjaga indikator fokus untuk navigasi keyboard.

## 7.26 Daftar project, nested loop, dan empty state

Daftar project:

```vue
<div v-if="projects.length">
    <article v-for="project in projects" :key="project.id">
        <h3>{{ project.name }}</h3>
        <p v-if="project.description">{{ project.description }}</p>
        <div v-if="project.tags.length">
            <span v-for="tag in project.tags" :key="tag.id">
                {{ tag.name }}
            </span>
        </div>
        <p v-else>Belum ada tag.</p>
    </article>
</div>
<div v-else>
    Belum ada project. Tambahkan project pertama menggunakan form.
</div>
```

- `v-if="projects.length"` memilih antara daftar project dan empty state.
- `v-for` pertama melakukan loop project.
- `:key="project.id"` mengidentifikasi project secara stabil.
- `v-if="project.description"` menyembunyikan deskripsi kosong.
- Nested `v-for` kedua melakukan loop tag milik tiap project.
- `v-else` harus bersebelahan dengan `v-if` yang dipasangkannya dan menampilkan kondisi tanpa tag/project.
- Data `project.tags` tersedia karena controller eager-load `tags`.

Flash message sukses:

```vue
<div v-if="success" role="status">
    {{ success }}
</div>
```

`role="status"` memberi tahu assistive technology bahwa pesan status telah diperbarui tanpa memindahkan fokus pengguna.

## 7.27 Utility class Tailwind yang penting

Vue menentukan **apa** yang dirender dan kapan; class Tailwind menentukan **bagaimana** tampilan elemen.

| Class | Efek |
|---|---|
| `min-h-screen` | tinggi minimum container setidaknya satu viewport |
| `bg-neutral-950` | surface gelap utama halaman |
| `grid ... lg:grid-cols-[...]` | satu kolom default, layout dua kolom pada breakpoint `lg` |
| `w-full` | lebar memenuhi parent |
| `rounded-lg` | radius sudut |
| `border border-neutral-700` | garis tepi kontrol |
| `focus-visible:ring-*` | indikator fokus keyboard |
| `max-h-56 overflow-y-auto` | batasi tinggi list dan izinkan scroll vertikal |
| `disabled:*` | style state disabled |
| `text-neutral-*` | hirarki warna teks |

Class tidak mengubah aturan validasi atau nilai yang dikirim ke Laravel.

---

# 8. Request Lifecycle Lengkap

Saat user memilih tag `2` dan `5`, lalu submit:

```text
1. Klik opsi 2
2. toggleTag(2) menambah 2 ke form.tag_ids
3. selectedTags dihitung ulang; chip dan counter diperbarui
4. Klik opsi 5
5. form.tag_ids sekarang [2, 5]
6. Submit memanggil form.post(route(...))
7. Inertia mengirim name, description, tag_ids ke POST route
8. Laravel menjalankan rules validasi, termasuk exists:tags,id
9. DB::transaction mulai
10. Project baru dimasukkan ke projects
11. tags()->sync([2, 5]) memasukkan dua baris project_tag
12. Transaksi commit
13. Controller redirect ke projects.multi-select dengan flash success
14. index() memuat ulang daftar dan relasi tags
15. Inertia memperbarui props halaman
16. Vue menampilkan project baru beserta chip tag
```

Jika validasi gagal, langkah 9–12 tidak terjadi. Laravel kembali dengan errors, Inertia mengisi `form.errors`, lalu Vue memperlihatkannya tanpa kehilangan input form.

---

# 9. Menjalankan Implementasi

Jalankan migration dan seed contoh:

```bash
php artisan migrate --seed
```

Jalankan aplikasi:

```bash
composer run dev
```

Atau jalankan server Laravel dan Vite pada terminal berbeda:

```bash
php artisan serve
npm run dev
```

Buka route aplikasi:

```text
/projects/multi-select
```

Seed mengisi pilihan `Frontend`, `Backend`, `Design`, `Laravel`, `Vue`, `In Progress`, `Documentation`, dan `High Priority`.

## Menjalankan test

```bash
php artisan test --filter=ProjectMultiSelectTest
```

Test mencakup:

1. GET halaman mengirim props Inertia yang benar.
2. POST project dengan beberapa ID membuat project dan pivot untuk setiap ID.
3. ID tag yang tidak ada ditolak dan project tidak dibuat.

Test memakai SQLite in-memory agar data test terisolasi.

---

# 10. Adaptasi untuk Form Edit

Multi-select yang sama dapat dipakai pada halaman edit. Sertakan tag relasi yang telah dipilih di props, kemudian inisialisasi `tag_ids` dengan array ID existing:

```js
const form = useForm({
    name: props.project.name,
    description: props.project.description ?? '',
    tag_ids: props.project.tags.map((tag) => tag.id),
});
```

`map()` mengubah array tag object menjadi array angka:

```js
[{ id: 2, name: 'Backend' }, { id: 5, name: 'Design' }]
```

menjadi:

```js
[2, 5]
```

POST/PUT ke action update lalu gunakan `sync($validated['tag_ids'] ?? [])`. `sync()` mengubah pivot sehingga sesuai dengan pilihan yang baru.

---

# 11. Hal yang Perlu Diingat

- Simpan pilihan multi-select sebagai array, bukan sebagai comma-separated string.
- Kirim ID dari frontend; ambil label dari data master.
- `v-model` pada input search mengubah state, sedangkan opsi pilihan diubah oleh `toggleTag()`.
- `computed()` menjaga hasil filter, daftar terpilih, dan error tetap mengikuti state sumber.
- `filter()` menghapus opsi dari array tanpa mutasi indeks manual.
- Validasi `tag_ids.*` memeriksa setiap item, bukan hanya memastikan nilai induknya array.
- UI membatasi maksimal 10; server tetap menjadi otoritas validasi.
- `sync()` menyimpan relasi pivot; `DB::transaction()` menyatukan perubahan database.
- Dropdown di contoh ini inline dalam document flow (bukan `absolute`) supaya terbukanya opsi menambah tinggi halaman dan tidak menimpa konten/background bawah.
- Untuk katalog opsi besar, ubah pencarian menjadi server-side; jangan mengirim ribuan opsi sekaligus.

## Ringkasan Syntax Utama

| Syntax | Arti dalam studi kasus |
|---|---|
| `$table->foreignId(...)->constrained()` | foreign key pivot ke model terkait |
| `belongsToMany()` | relasi many-to-many Eloquent |
| `Inertia::render(..., props)` | kirim data controller ke page Vue |
| `defineProps()` | deklarasi props pada Vue SFC |
| `useForm({ tag_ids: [] })` | state form Inertia dengan pilihan array |
| `ref()` | state reaktif seperti `isOpen` dan `search` |
| `computed()` | nilai turunan seperti `filteredTags`, `selectedTags`, `tagError` |
| `.filter()` | cari subset dan membangun array tanpa ID tertentu |
| `.includes()` | cek apakah ID sedang terpilih |
| `[...array, value]` | membuat array baru dengan pilihan tambahan |
| `v-model` | menghubungkan input dengan state Vue |
| `v-if` / `v-else` | merender kondisi state |
| `v-for` / `:key` | merender opsi, chip, project dengan key stabil |
| `@click` | menangani interaksi user |
| `@submit.prevent` | submit form lewat handler tanpa reload native |
| `:aria-selected` | menyampaikan state pilihan ke assistive tech |
| `form.post()` | POST Inertia ke route Laravel |
| `$request->validate()` | validasi data request server-side |
| `DB::transaction()` | membuat operasi database atomik |
| `tags()->sync($ids)` | menyelaraskan baris pivot dengan daftar ID |

---

# 12. Kesimpulan

Alur multi-select terdiri dari dua tanggung jawab:

1. **Vue** mengelola pilihan sebagai array ID reaktif dan menyediakan UI untuk mencari, memilih, membatalkan, dan menghapus pilihan.
2. **Laravel** memvalidasi array dan setiap ID, membuat project, lalu menyimpan relasi melalui pivot dalam transaksi.

Contoh data yang berpindah adalah `[2, 5, 8]`; hasil akhirnya adalah tiga baris relasi dalam `project_tag`. Ini membuat implementasi mudah di-query, aman terhadap ID tidak valid, dan dapat dikembangkan untuk form edit.
