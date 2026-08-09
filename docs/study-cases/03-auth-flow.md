# Study Case 3: Auth Flow (Login & Register)

Tujuan: belajar **`useForm`** untuk handle form dengan validasi, error per-field, processing state, flash message, dan proteksi route. Ini pola paling umum di aplikasi Laravel + Inertia.

---

## Vue Features yang Dipelajari

| Fitur Vue / Inertia | Dipakai untuk |
|---|---|
| `useForm` | state form + error + processing |
| `form.post / form.delete` | submit ke endpoint |
| `form.errors.<field>` | error per-field dari Laravel Validator |
| `form.processing` | disable tombol saat submit |
| `form.recentlySuccessful` | feedback sukses |
| `usePage` + `props.flash` | notifikasi flash message |
| `v-if` (error) + dynamic class | styling error state |
| `v-show` (flash) | auto-hide alert |
| `<Link>` logout | action dengan method DELETE |
| `v-once` (CSRF) | token CSRF tidak perlu di Vue (Inertia handle) |

---

## Backend: Setup Auth Manual

> **Catatan**: Studi kasus ini BUKAN pakai Laravel Breeze. Kita buat auth minimal sendiri untuk memahami setiap bagian.

### 1. Migration & Model User (sudah ada dari `laravel/laravel`)

Pastikan model `User` di `app/Models/User.php` sudah ada (default dari skeleton).

### 2. Controller Auth

```bash
php artisan make:controller AuthController
```

```php
// app/Http/Controllers/AuthController.php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Email atau password salah.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister()
    {
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
```

### 3. Route

```php
// routes/web.php
use App\Http\Controllers\AuthController;

Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');
});
```

### 4. Share Flash Message

Middleware Inertia sudah otomatis share `auth.user`. Untuk flash message, edit `HandleInertiaRequests`:

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'flash' => [
            'success' => fn () => $request->session()->get('success'),
            'error'   => fn () => $request->session()->get('error'),
        ],
    ]);
}
```

---

## Frontend: Halaman Auth

### 1. `resources/js/Pages/Auth/Login.vue`

```vue
<script setup>
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const page = usePage();
const form = useForm({
  email:    '',
  password: '',
  remember: false,
});

const flash = computed(() => page.props.flash);

function submit() {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
}

// demo: bersihkan error saat user mulai mengetik
watch(() => form.email, () => form.clearErrors('email'));
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-100 p-4">
    <div class="w-full max-w-md rounded bg-white p-6 shadow">
      <h1 class="mb-6 text-center text-2xl font-bold">Login</h1>

      <!-- Flash message -->
      <div
        v-if="flash.success"
        class="mb-4 rounded bg-green-100 px-3 py-2 text-green-700"
      >
        {{ flash.success }}
      </div>
      <div
        v-if="flash.error"
        class="mb-4 rounded bg-red-100 px-3 py-2 text-red-700"
      >
        {{ flash.error }}
      </div>

      <form @submit.prevent="submit">
        <!-- EMAIL -->
        <div class="mb-4">
          <label class="mb-1 block text-sm">Email</label>
          <input
            v-model="form.email"
            type="email"
            :class="[
              'w-full rounded border px-3 py-2',
              form.errors.email ? 'border-red-500' : 'border-gray-300',
            ]"
            autocomplete="email"
          />
          <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">
            {{ form.errors.email }}
          </p>
        </div>

        <!-- PASSWORD -->
        <div class="mb-4">
          <label class="mb-1 block text-sm">Password</label>
          <input
            v-model="form.password"
            type="password"
            :class="[
              'w-full rounded border px-3 py-2',
              form.errors.password ? 'border-red-500' : 'border-gray-300',
            ]"
            autocomplete="current-password"
          />
          <p v-if="form.errors.password" class="mt-1 text-sm text-red-500">
            {{ form.errors.password }}
          </p>
        </div>

        <!-- REMEMBER -->
        <label class="mb-4 flex items-center gap-2 text-sm">
          <input v-model="form.remember" type="checkbox" />
          Ingat saya
        </label>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full rounded bg-blue-500 px-4 py-2 text-white disabled:opacity-50"
        >
          {{ form.processing ? 'Loading...' : 'Login' }}
        </button>
      </form>

      <p class="mt-4 text-center text-sm text-gray-600">
        Belum punya akun?
        <Link :href="route('register')" class="text-blue-500 hover:underline">
          Daftar
        </Link>
      </p>
    </div>
  </div>
</template>
```

### 2. `resources/js/Pages/Auth/Register.vue`

```vue
<script setup>
import { useForm, Link } from '@inertiajs/vue3';

const form = useForm({
  name:                  '',
  email:                 '',
  password:              '',
  password_confirmation: '',
});

function submit() {
  form.post(route('register'), {
    onSuccess: () => form.reset('password', 'password_confirmation'),
  });
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-100 p-4">
    <div class="w-full max-w-md rounded bg-white p-6 shadow">
      <h1 class="mb-6 text-center text-2xl font-bold">Daftar</h1>

      <form @submit.prevent="submit">
        <div class="mb-4">
          <label class="mb-1 block text-sm">Nama</label>
          <input
            v-model="form.name"
            :class="[
              'w-full rounded border px-3 py-2',
              form.errors.name ? 'border-red-500' : 'border-gray-300',
            ]"
          />
          <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">
            {{ form.errors.name }}
          </p>
        </div>

        <div class="mb-4">
          <label class="mb-1 block text-sm">Email</label>
          <input
            v-model="form.email"
            type="email"
            :class="[
              'w-full rounded border px-3 py-2',
              form.errors.email ? 'border-red-500' : 'border-gray-300',
            ]"
          />
          <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">
            {{ form.errors.email }}
          </p>
        </div>

        <div class="mb-4">
          <label class="mb-1 block text-sm">Password</label>
          <input v-model="form.password" type="password"
            :class="['w-full rounded border px-3 py-2', form.errors.password ? 'border-red-500' : 'border-gray-300']" />
          <p v-if="form.errors.password" class="mt-1 text-sm text-red-500">
            {{ form.errors.password }}
          </p>
        </div>

        <div class="mb-4">
          <label class="mb-1 block text-sm">Konfirmasi Password</label>
          <input v-model="form.password_confirmation" type="password"
            :class="['w-full rounded border px-3 py-2', form.errors.password_confirmation ? 'border-red-500' : 'border-gray-300']" />
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full rounded bg-blue-500 px-4 py-2 text-white disabled:opacity-50"
        >
          {{ form.processing ? 'Loading...' : 'Daftar' }}
        </button>
      </form>

      <p class="mt-4 text-center text-sm text-gray-600">
        Sudah punya akun?
        <Link :href="route('login')" class="text-blue-500 hover:underline">Login</Link>
      </p>
    </div>
  </div>
</template>
```

### 3. `resources/js/Pages/Dashboard.vue` (Halaman Setelah Login)

```vue
<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page  = usePage();
const user  = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash);
</script>

<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="mx-auto max-w-3xl">
      <!-- Flash message -->
      <div
        v-if="flash.success"
        class="mb-4 rounded bg-green-100 px-4 py-2 text-green-700"
      >
        {{ flash.success }}
      </div>

      <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Dashboard</h1>
        <span class="text-sm text-gray-600">Halo, {{ user?.name }}</span>
      </div>

      <div class="rounded bg-white p-6 shadow">
        <p class="mb-4">Selamat datang, {{ user?.email }}!</p>

        <!-- Logout via Link method DELETE -->
        <Link
          href="/logout"
          method="post"
          as="button"
          class="rounded bg-red-500 px-4 py-2 text-white"
        >
          Logout
        </Link>
      </div>
    </div>
  </div>
</template>
```

---

## Konsep Penting

### 1. `useForm` adalah Jantung Form di Inertia

```js
const form = useForm({ email: '', password: '' });

form.email = 'a@b.c';        // reactive, di template jadi v-model
form.post('/login');          // submit POST
form.errors.email;            // auto-populate dari Validator Laravel
form.processing;              // boolean, true saat request jalan
form.reset('password');       // clear field tertentu
form.clearErrors('email');    // hapus error tertentu
```

Setara Blade sangat verbose:
```blade
<input name="email" value="{{ old('email') }}">
@error('email') <span>{{ $message }}</span> @enderror
<button @disabled(...)>Submit</button>
```

### 2. Error Per-Field Otomatis

Backend:
```php
$data = $request->validate(['email' => 'required|email']);
```

Frontend:
```vue
<p v-if="form.errors.email">{{ form.errors.email }}</p>
```

Tidak perlu `old()`, tidak perlu `@error`. **Ini kekuatan utama Inertia**.

### 3. CSRF Otomatis

Inertia otomatis kirim `X-CSRF-TOKEN` dari `<meta name="csrf-token">` yang ada di `app.blade.php`. Tidak perlu `@csrf` di setiap form Vue.

### 4. `usePage().props.auth.user`

Data `auth.user` di-share via middleware Inertia ke semua halaman. Bisa langsung diakses tanpa props drilling.

### 5. `<Link method="post">`代替 Form Logout

```vue
<Link href="/logout" method="post" as="button">Logout</Link>
```

= full Blade equivalent:
```blade
<form method="POST" action="/logout">@csrf<button>Logout</button></form>
```

Inertia otomatis handle CSRF, method, dan event.

### 6. Lifecycle Hooks on Success/Error

```js
form.post('/posts', {
  onSuccess: (page) => { /* page.props.flash etc */ },
  onError:   (errors) => { /* errors object */ },
  onFinish:  () => { /* selalu dipanggil, success maupun error */ },
});
```

### 7. Validasi Real-time (UX Modern)

```js
// Hapus error email saat user mulai mengetik ulang
watch(() => form.email, () => {
  if (form.errors.email) form.clearErrors('email');
});
```

Atau pakai `v-on:input` langsung:
```vue
<input @input="form.clearErrors('email')" v-model="form.email" />
```

---

## Tantangan Modifikasi

- [ ] Tambah **"Lupa Password"** flow (forgot → email → reset form).
- [ ] Tambah **password strength indicator** (computed dari `form.password`).
- [ ] Tambah **"show/hide password"** toggle.
- [ ] Tambah **email verification** (kirim link, status badge di dashboard).
- [ ] Tambah **two-factor auth** (field OTP setelah password benar).
- [ ] Tambah **login throttle lockout** (disable button + countdown setelah 5x salah).

---

## Konsep Vue yang Sudah Dipakai

✅ `useForm` (form state + error + processing)
✅ `usePage` (global props)
✅ `computed` (derived dari page props)
✅ `watch` (clear error on input)
✅ `v-model` (input + checkbox)
✅ `v-if` (error message, flash message)
✅ `@click`, `@submit.prevent`, `@input`
✅ `:class` dinamis (error styling)
✅ `:disabled` (saat processing)
✅ `defineOptions` (implicit, tidak pakai di sini)
✅ `Link` component (logout, navigasi)
✅ `v-once` (implicit, dari middleware)

---

## Recap: Pola Form Inertia

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  field1: '',
  field2: '',
});

function submit() {
  form.post('/url', {
    onSuccess: () => form.reset(),
  });
}
</script>

<template>
  <form @submit.prevent="submit">
    <input v-model="form.field1" :class="{ 'border-red': form.errors.field1 }" />
    <p v-if="form.errors.field1">{{ form.errors.field1 }}</p>
    <button :disabled="form.processing">Submit</button>
  </form>
</template>
```

Pola ini jadi **template dasar** untuk hampir semua form di Inertia: login, register, profile, settings, CRUD, dll.

---

## Lanjut?

Kamu sudah menguasai tiga studi kasus utama. Berikut saran untuk eksplorasi mandiri:

1. **CRUD Lengkap**: Buat halaman Products dengan create/edit/delete, modal form, konfirmasi hapus. Pelajari `<Teleport>`, `v-slot` untuk modal.
2. **Multi-step Form**: Wizard registrasi 3 langkah. Pelajari state management lintas langkah.
3. **Realtime**: Pakai Laravel Echo + WebSocket. Pelajari `onMounted` + cleanup di `onUnmounted`.
4. **Drag & Drop**: Library `vuedraggable` (atau `vue-draggable-plus`). Pelajari custom directive.
5. **Charts**: Library `Chart.js` + `vue-chartjs`. Pelajari wrapper component.

Setelah 3 studi kasus di atas, kamu sudah punya fondasi kuat untuk proyek Vue-Inertia Laravel apapun.
