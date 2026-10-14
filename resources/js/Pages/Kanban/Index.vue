<script setup>
import { ref, reactive, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import draggable from 'vuedraggable';

const props = defineProps({
    tasks: Array,
});

const columns = reactive([
    { id: 'todo', title: 'To Do', tasks: [] },
    { id: 'in_progress', title: 'In Progress', tasks: [] },
    { id: 'done', title: 'Done', tasks: [] },
]);

// Initialize columns with tasks from props
const initTasks = () => {
    // Clear existing tasks
    columns.forEach(col => col.tasks = []);
    
    // Distribute tasks into columns based on status
    props.tasks.forEach(task => {
        const column = columns.find(c => c.id === task.status);
        if (column) {
            column.tasks.push({ ...task });
        }
    });
};

onMounted(() => {
    initTasks();
});

// Watch for prop changes (e.g., after Inertia reload)
watch(() => props.tasks, () => {
    initTasks();
}, { deep: true });

let isUpdating = false;

const onDragChange = () => {
    if (isUpdating) return;
    
    // Prepare the payload for backend
    const payload = [];
    
    columns.forEach(column => {
        column.tasks.forEach((task, index) => {
            payload.push({
                id: task.id,
                status: column.id,
                order: index + 1 // 1-based ordering
            });
        });
    });

    isUpdating = true;
    
    router.post('/kanban/reorder', { tasks: payload }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            isUpdating = false;
        },
        onError: () => {
            isUpdating = false;
            // Optionally handle error, revert state, etc.
        }
    });
};

</script>

<template>
    <div class="min-h-screen bg-gray-50 p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Kanban Dashboard</h1>
        
        <div class="flex gap-6 overflow-x-auto pb-4">
            <!-- Kanban Columns -->
            <div 
                v-for="column in columns" 
                :key="column.id" 
                class="w-80 flex-shrink-0 flex flex-col bg-gray-100 rounded-xl shadow-sm border border-gray-200"
            >
                <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="font-semibold text-gray-700">{{ column.title }}</h2>
                    <span class="bg-gray-200 text-gray-600 text-xs py-1 px-2 rounded-full font-bold">
                        {{ column.tasks.length }}
                    </span>
                </div>
                
                <div class="p-3 flex-1">
                    <draggable 
                        v-model="column.tasks" 
                      
                        item-key="id"
                        @change="onDragChange"
                        class="min-h-[150px] h-full flex flex-col gap-3"
                        :animation="250"
                        ghost-class="kanban-ghost"
                        drag-class="kanban-drag"
                        chosen-class="kanban-chosen"
                    >
                        <template #item="{ element }">
                            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 cursor-grab hover:shadow-md hover:border-blue-300 transition-all duration-200 select-none">
                                <p class="text-sm font-medium text-gray-800">{{ element.title }}</p>
                            </div>
                        </template>
                    </draggable>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Styling ketika item sedang ditahan / di-click tapi belum digeser */
.kanban-chosen {
    border-color: #3b82f6 !important; /* blue-500 */
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* Styling untuk item bayangan (tempat item akan dijatuhkan) */
.kanban-ghost {
    opacity: 0.4;
    background-color: #f3f4f6 !important; /* gray-100 */
    border: 2px dashed #9ca3af !important; /* gray-400 */
    box-shadow: none !important;
}

/* Styling untuk item yang sedang ditarik/terbang (tergantung dukungan browser) */
.kanban-drag {
    opacity: 1 !important;
    transform: rotate(3deg) scale(1.05);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
    cursor: grabbing !important;
}
</style>
