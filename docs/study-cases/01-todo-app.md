# Study Case 1: Todo App (Client-Side)

Tujuan: pakai directive Vue dasar (`v-for`, `v-model`, `v-if`, `v-show`, `@click`, `@submit.prevent`) untuk membangun Todo App **sepenuhnya di client**. Data disimpan di `localStorage` (persist antar reload). Backend tidak disentuh di studi kasus ini — fokus murni Vue.

> **Lokasi file**: `resources/js/Pages/TodoApp.vue`
> **Cara akses**: tambah route dummy atau langsung import di Welcome.vue sementara.

---

## Vue Features yang Dipelajari

| Fitur Vue | Dipakai untuk |
|---|---|
| `ref`, `reactive` | state todos & input |
| `v-model` | input field 2-way binding |
| `v-for` + `:key` | render list todo |
| `v-if` / `v-show` | filter "Selesai / Aktif / Semua" |
| `@click`, `@submit.prevent` | tombol aksi & form submit |
| `computed` | filter list & statistik |
| `watch` + `localStorage` | persist otomatis |
| `defineProps` / `defineEmits` | (versi 2: pecah jadi TodoItem component) |

---

## Langkah 1: Siapkan Route Dummy

```php
// routes/web.php
Route::get('/todo', fn () => Inertia::render('TodoApp'));
```

Buka `http://127.0.0.1:8000/todo`.

---

## Langkah 2: Component Versi Sederhana (1 file)

```vue
<!-- resources/js/Pages/TodoApp.vue -->
<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const STORAGE_KEY = 'todos:v1';

// state
const todos     = ref([]);          // { id, text, done }
const draft     = ref('');          // input baru
const filter    = ref('all');       // all | active | done

// load dari localStorage saat mount
onMounted(() => {
  const saved = localStorage.getItem(STORAGE_KEY);
  if (saved) todos.value = JSON.parse(saved);
});

// auto-save setiap ada perubahan
watch(todos, (newVal) => {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(newVal));
}, { deep: true });

// actions
function addTodo() {
  const text = draft.value.trim();
  if (!text) return;
  todos.value.push({
    id: Date.now(),
    text,
    done: false,
  });
  draft.value = '';
}

function removeTodo(id) {
  todos.value = todos.value.filter(t => t.id !== id);
}

function toggleTodo(todo) {
  todo.done = !todo.done;
}

function clearDone() {
  todos.value = todos.value.filter(t => !t.done);
}

// computed
const filtered = computed(() => {
  if (filter.value === 'active') return todos.value.filter(t => !t.done);
  if (filter.value === 'done')   return todos.value.filter(t => t.done);
  return todos.value;
});

const stats = computed(() => ({
  total:  todos.value.length,
  active: todos.value.filter(t => !t.done).length,
  done:   todos.value.filter(t => t.done).length,
}));
</script>

<template>
  <div class="min-h-screen bg-gray-100 py-10">
    <div class="mx-auto max-w-xl rounded bg-white p-6 shadow">
      <h1 class="mb-4 text-2xl font-bold">Todo App</h1>

      <!-- FORM: @submit.prevent + v-model -->
      <form @submit.prevent="addTodo" class="mb-4 flex gap-2">
        <input
          v-model="draft"
          placeholder="Apa yang ingin dilakukan?"
          class="flex-1 rounded border px-3 py-2"
        />
        <button type="submit" class="rounded bg-blue-500 px-4 py-2 text-white">
          Tambah
        </button>
      </form>

      <!-- FILTER: v-show untuk toggle, :class untuk active state -->
      <div class="mb-4 flex gap-2 text-sm">
        <button
          v-for="f in ['all', 'active', 'done']"
          :key="f"
          @click="filter = f"
          :class="[
            'rounded px-3 py-1',
            filter === f ? 'bg-blue-500 text-white' : 'bg-gray-200',
          ]"
        >
          {{ f }}
        </button>
        <button
          v-if="stats.done > 0"
          @click="clearDone"
          class="ml-auto rounded bg-red-500 px-3 py-1 text-white"
        >
          Hapus selesai ({{ stats.done }})
        </button>
      </div>

      <!-- LIST: v-for + :key + v-if (kosong) -->
      <p v-if="filtered.length === 0" class="py-8 text-center text-gray-400">
        Tidak ada todo.
      </p>

      <ul v-else class="divide-y">
        <li
          v-for="todo in filtered"
          :key="todo.id"
          class="flex items-center gap-2 py-2"
        >
          <input
            type="checkbox"
            :checked="todo.done"
            @change="toggleTodo(todo)"
          />
          <span
            :class="{ 'line-through text-gray-400': todo.done }"
            class="flex-1"
          >
            {{ todo.text }}
          </span>
          <button
            @click="removeTodo(todo.id)"
            class="text-red-500 hover:underline"
          >
            Hapus
          </button>
        </li>
      </ul>

      <!-- STATS -->
      <div class="mt-4 text-sm text-gray-500">
        Total: {{ stats.total }} | Aktif: {{ stats.active }} | Selesai: {{ stats.done }}
      </div>
    </div>
  </div>
</template>
```

---

## Langkah 3: Coba & Modifikasi

Jalankan:
```bash
npm run dev      # satu terminal
php artisan serve # terminal lain
```

Buka `/todo` — coba:
1. Tambah todo, hapus, centang selesai.
2. Reload halaman — data tetap (localStorage).
3. Buka DevTools → Application → Local Storage → lihat `todos:v1`.

**Tantangan modifikasi** (pilih salah satu atau lebih):
- [ ] Tambah field `priority` (low/medium/high) dengan badge warna.
- [ ] Tambah `dueDate` dan tampilkan warning jika lewat.
- [ ] Tambah **edit inline** (double-click teks → jadi input → enter untuk simpan).
- [ ] Tambah **drag & drop** untuk reorder.
- [ ] Tambah **bulk actions**: pilih beberapa, hapus sekaligus.
- [ ] Tambah **undo** untuk action hapus (simpan ke stack).

---

## Langkah 4: Refactor — Pecah Jadi Component

Setelah paham, refactor dengan memecah jadi sub-komponen untuk belajar `defineProps` / `defineEmits`.

### `resources/js/Components/TodoItem.vue`

```vue
<script setup>
defineProps({
  todo: { type: Object, required: true },
});

defineEmits(['toggle', 'remove']);
</script>

<template>
  <li class="flex items-center gap-2 py-2">
    <input type="checkbox" :checked="todo.done" @change="$emit('toggle', todo)" />
    <span :class="{ 'line-through text-gray-400': todo.done }" class="flex-1">
      {{ todo.text }}
    </span>
    <button @click="$emit('remove', todo.id)" class="text-red-500 hover:underline">
      Hapus
    </button>
  </li>
</template>
```

### `resources/js/Components/TodoFilter.vue`

```vue
<script setup>
defineProps({
  modelValue: { type: String, required: true },
  doneCount:  { type: Number, default: 0 },
});
defineEmits(['update:modelValue', 'clear-done']);
</script>

<template>
  <div class="mb-4 flex gap-2 text-sm">
    <button
      v-for="f in ['all', 'active', 'done']"
      :key="f"
      @click="$emit('update:modelValue', f)"
      :class="[
        'rounded px-3 py-1',
        modelValue === f ? 'bg-blue-500 text-white' : 'bg-gray-200',
      ]"
    >
      {{ f }}
    </button>
    <button
      v-if="doneCount > 0"
      @click="$emit('clear-done')"
      class="ml-auto rounded bg-red-500 px-3 py-1 text-white"
    >
      Hapus selesai ({{ doneCount }})
    </button>
  </div>
</template>
```

### `resources/js/Pages/TodoApp.vue` (refactored)

```vue
<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import TodoItem   from '@/Components/TodoItem.vue';
import TodoFilter from '@/Components/TodoFilter.vue';

const STORAGE_KEY = 'todos:v2';
const todos  = ref([]);
const draft  = ref('');
const filter = ref('all');

onMounted(() => {
  const saved = localStorage.getItem(STORAGE_KEY);
  if (saved) todos.value = JSON.parse(saved);
});

watch(todos, (v) => localStorage.setItem(STORAGE_KEY, JSON.stringify(v)), { deep: true });

function addTodo() {
  const text = draft.value.trim();
  if (!text) return;
  todos.value.push({ id: Date.now(), text, done: false });
  draft.value = '';
}

const filtered = computed(() => {
  if (filter.value === 'active') return todos.value.filter(t => !t.done);
  if (filter.value === 'done')   return todos.value.filter(t => t.done);
  return todos.value;
});

const stats = computed(() => ({
  total:  todos.value.length,
  active: todos.value.filter(t => !t.done).length,
  done:   todos.value.filter(t => t.done).length,
}));
</script>

<template>
  <div class="min-h-screen bg-gray-100 py-10">
    <div class="mx-auto max-w-xl rounded bg-white p-6 shadow">
      <h1 class="mb-4 text-2xl font-bold">Todo App</h1>

      <form @submit.prevent="addTodo" class="mb-4 flex gap-2">
        <input v-model="draft" placeholder="..." class="flex-1 rounded border px-3 py-2" />
        <button type="submit" class="rounded bg-blue-500 px-4 py-2 text-white">Tambah</button>
      </form>

      <TodoFilter
        v-model="filter"
        :done-count="stats.done"
        @clear-done="todos = todos.filter(t => !t.done)"
      />

      <p v-if="filtered.length === 0" class="py-8 text-center text-gray-400">
        Tidak ada todo.
      </p>

      <ul v-else class="divide-y">
        <TodoItem
          v-for="todo in filtered"
          :key="todo.id"
          :todo="todo"
          @toggle="(t) => (t.done = !t.done)"
          @remove="(id) => (todos = todos.filter(x => x.id !== id))"
        />
      </ul>

      <div class="mt-4 text-sm text-gray-500">
        Total: {{ stats.total }} | Aktif: {{ stats.active }} | Selesai: {{ stats.done }}
      </div>
    </div>
  </div>
</template>
```

> **Catatan alias `@`**: untuk `@/Components/...` bekerja, tambahkan di `vite.config.js`:
> ```js
> resolve: {
>   alias: {
>     '@': '/resources/js',
>   },
> },
> ```
> Lalu rebuild.

---

## Latihan Mandiri

Coba bangun **Todo App** versi kamu sendiri dengan syarat:

1. **Wajib pakai** minimal 5 directive Vue berbeda.
2. **Wajib pakai** minimal 1 `computed` dan 1 `watch`.
3. **Wajib pecah** minimal jadi 2 komponen (parent + child).
4. **Bonus**: pakai `<TransitionGroup>` untuk animasi masuk/keluar list.

---

## Konsep Vue yang Sudah Dipakai di Sini

✅ `ref` (state)
✅ `v-model`, `v-for`, `:key`, `v-if`, `v-else`, `v-show` (template)
✅ `@click`, `@change`, `@submit.prevent` (event)
✅ `:class` dinamis (binding)
✅ `computed` (derived state)
✅ `watch` + `{ deep: true }` (side effect)
✅ `onMounted` (lifecycle)
✅ `defineProps`, `defineEmits` (component contract)
✅ `v-model` pada component (`v-model="filter"` di TodoFilter)

Lanjut ke studi kasus berikutnya: **[02-product-search.md](./02-product-search.md)** — belajar `computed` lebih dalam, `watch` dengan debounce, dan Inertia navigation.
