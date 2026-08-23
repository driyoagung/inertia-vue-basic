<script setup>
import { ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import Select from 'primevue/select';
import AutoComplete from 'primevue/autocomplete';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Message from 'primevue/message';
import Tag from 'primevue/tag';

const props = defineProps({
    tasks: { type: Array, required: true },
    categories: { type: Array, required: true },
    users: { type: Array, required: true },
});

const page = usePage();
const flash = page.props.flash;

const form = ref({
    title: '',
    description: '',
    category_id: null,
    assignee: null,
    priority: 'medium',
    status: 'pending',
});

const errors = ref({});

const priorityOptions = [
    { label: 'Rendah', value: 'low', severity: 'info' },
    { label: 'Sedang', value: 'medium', severity: 'warn' },
    { label: 'Tinggi', value: 'high', severity: 'danger' },
];

const statusOptions = [
    { label: 'Pending', value: 'pending' },
    { label: 'In Progress', value: 'in_progress' },
    { label: 'Selesai', value: 'done' },
];

const userSuggestions = ref([]);
let searchSeq = 0;

function searchUsers(event) {
    const query = event.query.trim();
    const seq = ++searchSeq;

    fetch(`/api/users/search?q=${encodeURIComponent(query)}`, {
        headers: { Accept: 'application/json' },
    })
        .then((r) => r.json())
        .then((json) => {
            if (seq !== searchSeq) return;
            userSuggestions.value = (json.data || []).map((u) => ({
                id: u.id,
                name: u.name,
                email: u.email,
            }));
        })
        .catch(() => {
            userSuggestions.value = [];
        });
}

function submit() {
    errors.value = {};

    router.post('/tasks', {
        title: form.value.title,
        description: form.value.description,
        category_id: form.value.category_id ? form.value.category_id.id : null,
        assignee_id: form.value.assignee ? form.value.assignee.id : null,
        priority: form.value.priority,
        status: form.value.status,
    }, {
        onSuccess: () => {
            form.value = {
                title: '',
                description: '',
                category_id: null,
                assignee: null,
                priority: 'medium',
                status: 'pending',
            };
        },
        onError: (err) => {
            errors.value = err;
        },
    });
}

function prioritySeverity(value) {
    return priorityOptions.find((p) => p.value === value)?.severity ?? 'info';
}

function statusLabel(value) {
    return statusOptions.find((s) => s.value === value)?.label ?? value;
}

watch(
    () => flash?.success,
    (msg) => {
        if (msg) setTimeout(() => router.reload({ only: ['flash'] }), 2500);
    },
);
</script>

<template>
    <Head title="Studi Kasus: Combobox Searchable" />

    <div class="min-h-screen bg-gray-50 px-6 py-10 text-gray-900">
        <div class="mx-auto max-w-5xl space-y-8">
            <header class="space-y-1">
                <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">
                    Studi Kasus Inertia + Vue 3 + PrimeVue
                </p>
                <h1 class="text-3xl font-bold">Combobox Select Option dengan Pencarian</h1>
                <p class="text-gray-600">
                    Dua skenario:
                    <span class="font-medium">client-side filter</span> untuk data kecil (kategori),
                    dan <span class="font-medium">server-side search</span> via API untuk data besar (user).
                </p>
            </header>

            <Message
                v-if="flash?.success"
                severity="success"
                :closable="false"
                class="!mt-2"
            >
                {{ flash.success }}
            </Message>

            <section class="rounded-xl bg-white p-6 shadow ring-1 ring-gray-200">
                <h2 class="mb-4 text-lg font-semibold">Form Task Baru</h2>

                <form @submit.prevent="submit" class="grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Judul Task</label>
                        <InputText
                            v-model="form.title"
                            placeholder="Contoh: Buat endpoint API baru"
                            class="w-full"
                            :invalid="!!errors.title"
                        />
                        <small v-if="errors.title" class="text-red-600">{{ errors.title }}</small>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Deskripsi</label>
                        <Textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full"
                            placeholder="Opsional"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Kategori
                            <span class="text-xs font-normal text-gray-500">
                                · PrimeVue Select (filter client-side)
                            </span>
                        </label>
                        <Select
                            v-model="form.category_id"
                            :options="categories"
                            option-label="name"
                            placeholder="Pilih kategori"
                            filter
                            filter-placeholder="Cari kategori..."
                            :invalid="!!errors.category_id"
                            class="w-full"
                        >
                            <template #option="{ option }">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-block h-2.5 w-2.5 rounded-full"
                                        :style="{ backgroundColor: option.color }"
                                    ></span>
                                    <span>{{ option.name }}</span>
                                </div>
                            </template>
                            <template #value="{ value }">
                                <div v-if="value" class="flex items-center gap-2">
                                    <span
                                        class="inline-block h-2.5 w-2.5 rounded-full"
                                        :style="{ backgroundColor: value.color }"
                                    ></span>
                                    <span>{{ value.name }}</span>
                                </div>
                                <span v-else class="text-gray-400">Pilih kategori</span>
                            </template>
                        </Select>
                        <small v-if="errors.category_id" class="text-red-600">
                            {{ errors.category_id }}
                        </small>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Assignee
                            <span class="text-xs font-normal text-gray-500">
                                · PrimeVue AutoComplete (search server-side)
                            </span>
                        </label>
                        <AutoComplete
                            v-model="form.assignee"
                            :suggestions="userSuggestions"
                            @complete="searchUsers"
                            option-label="name"
                            placeholder="Ketik nama user..."
                            class="w-full"
                            :input-style="{ width: '100%' }"
                            force-selection
                        >
                            <template #option="{ option }">
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ option.name }}</span>
                                    <span class="text-xs text-gray-500">{{ option.email }}</span>
                                </div>
                            </template>
                        </AutoComplete>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Prioritas</label>
                        <Select
                            v-model="form.priority"
                            :options="priorityOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Pilih prioritas"
                            class="w-full"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Status</label>
                        <Select
                            v-model="form.status"
                            :options="statusOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Pilih status"
                            class="w-full"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <Button
                            type="submit"
                            label="Simpan Task"
                            icon="pi pi-save"
                            class="!w-full md:!w-auto"
                        />
                    </div>
                </form>
            </section>

            <section class="rounded-xl bg-white p-6 shadow ring-1 ring-gray-200">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Daftar Task</h2>
                    <span class="text-sm text-gray-500">{{ tasks.length }} task</span>
                </div>

                <div v-if="tasks.length === 0" class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-gray-500">
                    Belum ada task. Silakan buat task baru di form atas.
                </div>

                <ul v-else class="divide-y divide-gray-100">
                    <li
                        v-for="task in tasks"
                        :key="task.id"
                        class="flex items-start justify-between gap-4 py-3"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ task.title }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">
                                <span v-if="task.category">
                                    <i class="pi pi-tag mr-1"></i>{{ task.category.name }}
                                </span>
                                <span v-if="task.assignee" class="ml-3">
                                    <i class="pi pi-user mr-1"></i>{{ task.assignee.name }}
                                </span>
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <Tag
                                :value="priorityOptions.find(p => p.value === task.priority)?.label"
                                :severity="prioritySeverity(task.priority)"
                            />
                            <Tag :value="statusLabel(task.status)" severity="secondary" />
                        </div>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
