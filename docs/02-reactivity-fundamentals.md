# Reactivity Fundamentals

Directive Vue (lihat [01-vue-directives-vs-blade.md](./01-vue-directives-vs-blade.md)) baru "hidup" ketika ada **state yang reactive**. Dokumen ini menjelaskan primitif state di Vue 3 (Composition API) yang wajib dikuasai sebelum lanjut ke studi kasus.

> Analogi: directive = "tag HTML pintar", reactivity = "listrik yang bikin tag-nya bisa nyala sendiri saat data berubah".

---

## 1. `ref()` — State Primitif

`ref` membungkus nilai apa pun (string, number, object, array) jadi **reactive**. Di template, otomatis di-`unwrap`. Di script, akses via `.value`.

```vue
<script setup>
import { ref } from 'vue';

const count = ref(0);
const name  = ref('Budi');
const user  = ref({ id: 1, email: 'a@b.c' });
const list  = ref([1, 2, 3]);

function increment() {
  count.value++;   // di script, HARUS pakai .value
}
</script>

<template>
  <p>{{ count }}</p>            <!-- di template, tanpa .value -->
  <p>{{ name }}</p>
  <p>{{ user.email }}</p>
  <button @click="increment">+</button>
</template>
```

**Aturan penting**:
- Di **`<script>` & JS biasa**: `count.value`
- Di **`<template>`**: `count` (auto-unwrap)
- **Jangan** destructuring langsung `const { value } = count` — reaktivitas hilang.

**Kapan pakai `ref`?**
- Semua tipe data primitif (string, number, boolean).
- Single object yang sering diganti total (`user.value = newUser`).
- Ingin paling aman & konsisten — default untuk pemula.

---

## 2. `reactive()` — State Object/Array

`reactive` membuat object/array reactive **tanpa** `.value`. Lebih natural untuk nested data.

```js
import { reactive } from 'vue';

const form = reactive({
  name: '',
  email: '',
  errors: {},
  items: [],
});

// Akses langsung tanpa .value
form.name = 'Budi';
form.items.push({ id: 1 });
```

**Kapan pakai `reactive`?**
- Object/array besar dengan banyak field yang sering diubah sebagian.
- Lebih natural karena tidak perlu `.value` di mana-mana.

**Jebakan umum `reactive`**:
- **Hilang reaktivitas** jika di-destructuring:
  ```js
  const { name, email } = form;   // name & email BUKAN reactive lagi
  ```
  Solusi: pakai `toRefs(form)` atau tetap akses via `form.name`.
- **Hilang reaktivitas** jika diganti total:
  ```js
  form = { name: '...' };   // ❌ reaktivitas hilang
  form.name = '...';        // ✅ ok
  ```

**Rekomendasi**: Pemula, default ke `ref`. Pakai `reactive` hanya kalau jelas bentuknya object/array besar dan kamu paham jebakannya.

---

## 3. `computed()` — Nilai Turunan

`computed` = nilai yang **otomatis dihitung ulang** saat dependensinya berubah. Mirip accessor di OOP, atau `getter` reaktif.

```js
import { ref, computed } from 'vue';

const items = ref([
  { name: 'Apel', price: 10000, qty: 3 },
  { name: 'Jeruk', price: 8000,  qty: 2 },
]);

const total = computed(() =>
  items.value.reduce((sum, i) => sum + i.price * i.qty, 0)
);
```

```vue
<p>Total: Rp {{ total.toLocaleString() }}</p>
```

**Kapan pakai `computed`?**
- Saat nilai **berasal dari state lain** dan ingin di-cache.
- Lebih baik dari method di template: `{{ total() }}` (method dipanggil tiap render) vs `{{ total }}` (computed cache otomatis).

**Aturan**:
- **Pure function** — jangan mutate state di dalam computed.
- Computed **lazy**: hanya jalan jika ada yang membaca dan dependensinya berubah.
- Untuk side-effect (misal: simpan ke localStorage), pakai `watch`.

---

## 4. `watch()` — Side Effect Saat Data Berubah

`watch` menjalankan fungsi saat data reactive berubah. Mirip observer.

```js
import { ref, watch } from 'vue';

const search = ref('');
const results = ref([]);

watch(search, async (newVal, oldVal) => {
  if (newVal.length < 2) return;
  const res = await fetch(`/api/search?q=${newVal}`);
  results.value = await res.json();
});
```

**Opsi penting**:

```js
watch(source, callback, {
  immediate: true,   // jalankan callback saat inisialisasi juga
  deep: true,        // pantau perubahan nested object/array
  once: true,        // hanya trigger sekali
  flush: 'post',     // 'pre' (default) | 'post' (setelah DOM update) | 'sync'
});
```

**Watch multiple sources**:

```js
watch([firstName, lastName], ([newFirst, newLast], [oldFirst, oldLast]) => {
  console.log(`Nama berubah: ${oldFirst} ${oldLast} -> ${newFirst} ${newLast}`);
});
```

**Kapan pakai `watch`?**
- HTTP request saat input berubah (debounced search).
- Validasi side-effect.
- Sinkron ke localStorage.
- Animasi triggered by state.

---

## 5. `watchEffect()` — Auto-Track

Mirip `watch` tapi **otomatis mendeteksi dependensi** yang dibaca di dalam callback.

```js
import { ref, watchEffect } from 'vue';

const userId = ref(1);
const user   = ref(null);

watchEffect(async () => {
  // Otomatis track: userId
  const res = await fetch(`/api/users/${userId.value}`);
  user.value = await res.json();
});
```

Sama seperti:

```js
watch(userId, async (id) => { /* fetch */ });
```

Bedanya: `watchEffect` jalan **langsung sekali** saat dipanggil, dan track apa pun yang dibaca di dalam (bisa `userId`, bisa juga `user.value.role`, dst).

**Kapan pakai mana?**
- `watch`: dependensi eksplisit (umumnya lebih aman & jelas).
- `watchEffect`: dependensi dinamis, atau lazy loading pattern.

---

## 6. `readonly()` & `shallowRef()`

### `readonly`

Membuat proxy read-only dari object reactive — berguna untuk props yang harus immutable.

```js
import { readonly } from 'vue';

const original = reactive({ count: 0 });
const copy = readonly(original);

copy.count++;   // warning, tidak berubah
original.count++; // ✅ berubah, dan copy ikut (karena proxy)
```

### `shallowRef` / `shallowReactive`

Hanya reaktif di level atas, tidak nested. Optimal untuk data besar (misal: list 10.000 item yang hanya diganti total, tidak dimodifikasi sebagian).

```js
import { shallowRef } from 'vue';

const bigList = shallowRef([...]);   // perubahan .push() tidak trigger
bigList.value = [...newData];        // ✅ trigger re-render
```

---

## 7. Pattern Penting di Composition API

### Setup Script (`<script setup>`)

Sintaks ringkas — semua yang di-declare di sini otomatis tersedia di template.

```vue
<script setup>
import { ref } from 'vue';

const count = ref(0);
// function, const, import — semua auto-exposed ke template
</script>
```

### `defineProps` & `defineEmits` (mirip `@props` Blade Component)

```vue
<!-- Parent -->
<MyButton label="Klik" :count="5" @click="onClick" />

<!-- MyButton.vue -->
<script setup>
const props = defineProps({
  label: { type: String, required: true },
  count: { type: Number, default: 0 },
});

const emit = defineEmits(['click']);

function handleClick() {
  emit('click', props.count);
}
</script>
```

### `defineModel` (Vue 3.4+, pengganti `v-model` manual)

```vue
<!-- CustomInput.vue -->
<script setup>
const model = defineModel();          // otomatis v-model
</script>
<input v-model="model">

<!-- Parent -->
<CustomInput v-model="text" />
```

---

## 8. Lifecycle Hooks (Composition API)

| Hook | Dipanggil saat | Setara Vue 2 (Options) |
|---|---|---|
| `onMounted` | Komponen di-mount | `mounted()` |
| `onUnmounted` | Komponen dihapus | `beforeUnmount()` + `unmounted()` |
| `onUpdated` | Setelah re-render | `updated()` |
| `onBeforeMount` | Sebelum mount | `beforeMount()` |
| `onErrorCaptured` | Error dari child | `errorCaptured()` |

```js
import { onMounted, onUnmounted } from 'vue';

onMounted(() => {
  console.log('komponen muncul');
  window.addEventListener('resize', onResize);
});

onUnmounted(() => {
  window.removeEventListener('resize', onResize);
});
```

**Tidak ada di Blade**: Blade tidak punya konsep "render lifecycle" — server render sekali, kirim, selesai.

---

## 9. Cheatsheet "Kapan Pakai Apa"

| Tujuan | Pakai |
|---|---|
| State primitif (string/number/boolean) | `ref` |
| Object/array besar | `reactive` |
| Nilai turunan otomatis | `computed` |
| Side effect saat state berubah | `watch` / `watchEffect` |
| Baca prop dari parent | `defineProps` |
| Kirim event ke parent | `defineEmits` |
| Inisialisasi fetch | `onMounted` (atau `watch` di setup) |
| Cleanup | `onUnmounted` |

---

## 10. Jebakan Umum Pemula

1. **Lupa `.value` di script**
   ```js
   count = count + 1;     // ❌ cuma variabel biasa
   count.value = count.value + 1;  // ✅
   ```

2. **Destructuring `reactive`**
   ```js
   const form = reactive({ name: '' });
   const { name } = form;
   name = 'x';   // ❌ tidak reaktif
   form.name = 'x';  // ✅
   ```
   Solusi modern: `const { name } = toRefs(form)`, atau tetap akses via `form.name`.

3. **Mutate array/object biasa (bukan ref/reactive)**
   ```js
   const items = [1, 2, 3];
   items.push(4);   // ❌ Vue tidak tahu
   const itemsRef = ref([1, 2, 3]);
   itemsRef.value.push(4);  // ✅ reaktif
   ```

4. **`computed` yang ada side-effect**
   ```js
   const total = computed(() => {
     localStorage.setItem('last', count.value);  // ❌ jangan
   });
   // ✅ pakai watch(count, val => localStorage.setItem('last', val))
   ```

5. **Akses `ref` di watch tanpa `.value` (kadang lupa)**
   ```js
   watch(count.value, ...);  // ❌ tidak jalan
   watch(count, ...);        // ✅ otomatis unwrap di watch source
   ```

---

## Lanjut ke

**[03-inertia-vue-patterns.md](./03-inertia-vue-patterns.md)** — pola Vue yang spesifik untuk Inertia: `<Link>`, `<Head>`, `useForm`, persistent layout, dll. Atau loncat ke **[study-cases/01-todo-app.md](./study-cases/01-todo-app.md)** untuk praktik.
