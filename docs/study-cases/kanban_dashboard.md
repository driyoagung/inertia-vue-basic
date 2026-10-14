# Studi Kasus: Kanban Dashboard Drag-and-Drop

Studi kasus ini mendemonstrasikan bagaimana kita bisa membuat Kanban Board interaktif menggunakan **Laravel**, **Inertia**, **Vue 3**, dan **vuedraggable**.

## Konsep Utama

Pada aplikasi Kanban, setiap _task_ biasanya memiliki:
1. `status` (Misalnya: Todo, In Progress, Done) - Menandakan di kolom mana task tersebut berada.
2. `order` (Integer) - Menandakan urutan task tersebut di dalam satu kolom.

### Database dan Model
Tabel `kanban_tasks` dirancang untuk menampung data tersebut:
```php
Schema::create('kanban_tasks', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('status')->default('todo');
    $table->integer('order')->default(0);
    $table->timestamps();
});
```

### Flow di Frontend (Vue + vuedraggable)
Kita menggunakan package `vuedraggable`, yang merupakan _wrapper_ Vue untuk Sortable.js.

```vue
<draggable 
    v-model="column.tasks" 
    group="tasks" 
    item-key="id"
    @change="onDragChange"
>
    <!-- Template item -->
</draggable>
```
**Poin penting di frontend:**
- `v-model="column.tasks"`: Ketika user melakukan drag-and-drop, state (array tasks di dalam kolom) akan diupdate secara otomatis oleh library.
- `group="tasks"`: Atribut ini memungkinkan _draggable item_ dipindahkan lintas `<draggable>` component yang berbeda, asalkan memiliki group name yang sama.
- `@change="onDragChange"`: Event ini ter-trigger ketika user selesai melakukan *drop* (berhasil mengubah posisi).

**Logika Menyimpan Perubahan:**
Ketika posisi berubah, kita menyusun ulang urutannya, mengubah statusnya, dan mengirim semua task (atau task yang berubah) ke backend secara bulk.

```javascript
const onDragChange = () => {
    const payload = [];
    columns.forEach(column => {
        column.tasks.forEach((task, index) => {
            payload.push({
                id: task.id,
                status: column.id,
                order: index + 1 // Urutan baru (1-based)
            });
        });
    });

    // Mengirim ke backend tanpa me-refresh posisi scroll halaman
    router.post('/kanban/reorder', { tasks: payload }, {
        preserveScroll: true,
        preserveState: true,
    });
};
```

### Menyimpan ke Backend
Di Controller, request array tersebut di-loop untuk memperbarui database:
```php
public function reorder(Request $request)
{
    foreach ($request->tasks as $taskData) {
        KanbanTask::where('id', $taskData['id'])->update([
            'status' => $taskData['status'],
            'order' => $taskData['order'],
        ]);
    }

    return redirect()->back(); // Inertia me-reload secara halus
}
```

## Kesimpulan
Dengan menggunakan `vuedraggable` dan kemampuan InertiaJS menjaga state (`preserveState: true`), sinkronisasi drag-and-drop dengan database backend menjadi sangat mulus dan *real-time* seperti halnya SPA pada umumnya, tanpa harus membuat REST API manual atau menggunakan Axios secara terpisah.
