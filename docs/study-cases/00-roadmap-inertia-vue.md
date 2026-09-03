# Roadmap Studi Kasus Laravel Inertia + Vue.js

Dokumen ini berisi daftar ide studi kasus dan alur pembelajaran untuk transisi dari Laravel Blade ke ekosistem Laravel Inertia + Vue.js (Composition API).

## 1. Fundamental & Konsep Dasar (Wajib Paham)

Sebelum membuat fitur yang kompleks, penting untuk memahami bagaimana Inertia menghubungkan Laravel dan Vue sebagai "lem" antara backend dan frontend.

- **Routing & Rendering:** Memahami perbedaan `view('blade')` dengan `Inertia::render('VueComponent')`.
- **Passing Data (Props):** Cara mengirim data dari Controller Laravel ke Vue Component menggunakan *Props*.
- **Shared Data:** Membuat data yang selalu tersedia di setiap halaman (misal: data `auth()->user()`, menu navigasi, notifikasi flash) melalui file `HandleInertiaRequests.php`.
- **Persistent Layouts:** Membuat layout utama (Header, Sidebar, Footer) yang tidak me-render ulang (tidak *flicker*) ketika berpindah halaman, memberikan pengalaman *Single Page Application* (SPA) yang sebenarnya.

## 2. Studi Kasus Dasar (Pengganti Blade)

Ini adalah fitur-fitur dasar yang biasa dibangun di Blade, yang kini diimplementasikan menggunakan *State Management* dari Vue.

- **CRUD (Create, Read, Update, Delete) Basic:**
  - **Tujuan:** Membuat manajemen data sederhana (contoh: Manajemen *Employee* atau *Post*).
  - **Fokus Pembelajaran:** Menggunakan helper `useForm()` dari Inertia untuk mengirim data `POST`/`PUT`/`DELETE`, menangani *loading state* (disable tombol submit), dan merender error validasi dari Laravel secara otomatis di Vue.
  
- **Flash Messages (Notifikasi):**
  - **Tujuan:** Menampilkan notifikasi Toast/Alert ("Data berhasil disimpan!") setelah operasi CRUD.
  - **Fokus Pembelajaran:** Mengirim session flash dari Controller (`->with('success', '...')`) dan menangkapnya di Vue melalui *Shared Data*.

## 3. Studi Kasus Menengah (UX Interaktif)

Di tahap ini, kita akan memanfaatkan kekuatan SPA dari Vue untuk membuat aplikasi terasa lebih cepat dan interaktif tanpa reload halaman.

- **Pagination & Datatables (Tanpa Reload):**
  - **Tujuan:** Menampilkan data dalam jumlah banyak menggunakan pagination bawaan Laravel.
  - **Fokus Pembelajaran:** Mengirim `Model::paginate()` ke Vue, merender komponen `<Pagination>` kustom, dan menggunakan komponen `<Link>` Inertia dengan opsi `preserveScroll` agar posisi halaman tidak kembali ke atas saat berpindah halaman.

- **Live Search & Filter:**
  - **Tujuan:** Membuat pencarian data secara *real-time* dan filter dropdown.
  - **Fokus Pembelajaran:** Menggunakan fitur `router.get()` secara reaktif dengan `watch` di Vue, mengimplementasikan *Debounce* (menunda request server saat mengetik cepat), dan menggunakan opsi `preserveState` Inertia agar input user tidak hilang saat data diperbarui.

- **File Upload (Image Handling):**
  - **Tujuan:** Mengupload foto profil beserta data input teks lainnya.
  - **Fokus Pembelajaran:** Menangani form `multipart/form-data` dengan `useForm()`, menampilkan *progress bar* upload, dan membuat fitur preview gambar di Vue sebelum disubmit ke server.

## 4. Studi Kasus Mahir (Real-world App)

Studi kasus ini mencerminkan kebutuhan aplikasi dunia nyata yang lebih kompleks.

- **Authentication & Authorization:**
  - **Tujuan:** Membuat sistem Login, Register, dan mengatur hak akses UI (Roles/Permissions).
  - **Fokus Pembelajaran:** Handling *session timeout*, mengamankan rute, dan merender kondisional di Vue (contoh: `<button v-if="user.can_delete">Hapus</button>`).
  
- **Modals & Slide-overs (Dialogs):**
  - **Tujuan:** Menjadikan form *Create* atau *Edit* sebagai Popup Modal, bukan halaman terpisah.
  - **Fokus Pembelajaran:** Mengontrol *state* Vue (buka/tutup modal), *passing data* untuk mode Edit ke dalam modal, dan mereset isi form ketika modal ditutup atau request berhasil.

- **Integrasi Library Vue Eksternal (Contoh: TanStack Table):**
  - **Tujuan:** Membuat *Data Grid* kompleks dengan fitur *sorting*, *global filter*, dan *column visibility*.
  - **Fokus Pembelajaran:** Memadukan *state management* lokal library frontend dengan data yang berasal dari server (Laravel), serta memahami perbedaan antara *Server-side pagination* dan *Client-side pagination*.

---

### Tips Pembelajaran Vue 3 di Inertia
1. **Gunakan Composition API:** Selalu gunakan `<script setup>` karena kodenya lebih ringkas dan modern dibandingkan Options API.
2. **Reusability:** Biasakan memecah UI menjadi komponen kecil yang dapat dipakai ulang (`<TextInput>`, `<PrimaryButton>`, `<Modal>`).
3. **Ekosistem Laravel Tetap Berlaku:** Fitur kuat Laravel seperti Eloquent, FormRequest, Jobs, dan Middleware tetap bekerja 100% sempurna dengan Inertia.
