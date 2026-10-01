<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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

const form = useForm({
    name: '',
    description: '',
    tag_ids: [],
});

const isOpen = ref(false);
const search = ref('');

const filteredTags = computed(() => {
    const term = search.value.trim().toLocaleLowerCase();

    if (!term) {
        return props.tags;
    }

    return props.tags.filter((tag) => tag.name.toLocaleLowerCase().includes(term));
});

const selectedTags = computed(() =>
    props.tags.filter((tag) => form.tag_ids.includes(tag.id)),
);

const tagError = computed(() =>
    form.errors.tag_ids ?? Object.entries(form.errors).find(([key]) => key.startsWith('tag_ids.'))?.[1],
);

function toggleTag(tagId) {
    if (form.tag_ids.includes(tagId)) {
        removeTag(tagId);
        return;
    }

    if (form.tag_ids.length < 10) {
        form.tag_ids = [...form.tag_ids, tagId];
    }
}

function removeTag(tagId) {
    form.tag_ids = form.tag_ids.filter((id) => id !== tagId);
}

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
</script>

<template>
    <Head title="Multi-select Tags" />

    <main class="min-h-screen bg-neutral-950 px-4 py-10 text-neutral-100 sm:px-6">
        <div class="mx-auto grid max-w-6xl gap-10 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
            <section>
                <p class="text-sm text-neutral-400">Laravel · Inertia · Vue 3</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Pilih beberapa tag</h1>
                <p class="mt-4 max-w-xl text-sm leading-6 text-neutral-400">
                    Satu project dapat memiliki beberapa tag. Pilihan disimpan sebagai relasi many-to-many, bukan sebagai string gabungan.
                </p>

                <form class="mt-8 space-y-6" @submit.prevent="submit">
                    <div>
                        <label for="project-name" class="mb-2 block text-sm font-medium text-neutral-200">Nama project</label>
                        <input
                            id="project-name"
                            v-model="form.name"
                            type="text"
                            maxlength="120"
                            required
                            autocomplete="off"
                            placeholder="Contoh: Portal inventori"
                            class="w-full rounded-lg border border-neutral-700 bg-neutral-900 px-3.5 py-2.5 text-sm text-white outline-none placeholder:text-neutral-500 focus:border-sky-400 focus:ring-2 focus:ring-sky-400/20"
                        />
                        <p v-if="form.errors.name" class="mt-2 text-sm text-rose-300">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label for="project-description" class="mb-2 block text-sm font-medium text-neutral-200">Deskripsi <span class="font-normal text-neutral-500">(opsional)</span></label>
                        <textarea
                            id="project-description"
                            v-model="form.description"
                            rows="3"
                            maxlength="1000"
                            placeholder="Jelaskan tujuan project..."
                            class="w-full resize-y rounded-lg border border-neutral-700 bg-neutral-900 px-3.5 py-2.5 text-sm text-white outline-none placeholder:text-neutral-500 focus:border-sky-400 focus:ring-2 focus:ring-sky-400/20"
                        />
                        <p v-if="form.errors.description" class="mt-2 text-sm text-rose-300">{{ form.errors.description }}</p>
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <label id="tag-picker-label" class="text-sm font-medium text-neutral-200">Tags</label>
                            <span class="text-xs tabular-nums text-neutral-500">{{ form.tag_ids.length }} / 10 dipilih</span>
                        </div>

                        <div>
                            <button
                                type="button"
                                aria-haspopup="listbox"
                                :aria-expanded="isOpen"
                                aria-labelledby="tag-picker-label"
                                class="flex min-h-12 w-full items-center justify-between gap-3 rounded-lg border border-neutral-700 bg-neutral-900 px-3.5 py-2.5 text-left text-sm outline-none transition hover:border-neutral-500 focus-visible:border-sky-400 focus-visible:ring-2 focus-visible:ring-sky-400/20"
                                @click="isOpen = !isOpen"
                            >
                                <span v-if="selectedTags.length" class="text-neutral-100">{{ selectedTags.length }} tag dipilih</span>
                                <span v-else class="text-neutral-400">Pilih satu atau beberapa tag</span>
                                <span class="shrink-0 text-xs text-neutral-400" aria-hidden="true">{{ isOpen ? 'Tutup' : 'Buka' }}</span>
                            </button>

                            <div v-if="selectedTags.length" class="mt-2 flex flex-wrap gap-2" aria-label="Tag yang dipilih">
                                <span v-for="tag in selectedTags" :key="tag.id" class="inline-flex items-center gap-2 rounded-md border border-neutral-700 bg-neutral-900 px-2.5 py-1 text-xs text-neutral-100">
                                    <span class="size-2 rounded-full" :style="{ backgroundColor: tag.color }" aria-hidden="true"></span>
                                    {{ tag.name }}
                                    <button
                                        type="button"
                                        class="rounded text-neutral-400 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-400"
                                        :aria-label="`Hapus tag ${tag.name}`"
                                        @click="removeTag(tag.id)"
                                    >
                                        Hapus
                                    </button>
                                </span>
                            </div>

                            <div
                                v-if="isOpen"
                                class="mt-2 w-full overflow-hidden rounded-lg border border-neutral-700 bg-neutral-900 shadow-xl shadow-black/30"
                            >
                                <div class="border-b border-neutral-800 p-2">
                                    <input
                                        v-model="search"
                                        type="search"
                                        aria-label="Cari tag"
                                        placeholder="Cari tag..."
                                        class="w-full rounded-md border border-neutral-700 bg-neutral-950 px-3 py-2 text-sm text-white outline-none placeholder:text-neutral-500 focus:border-sky-400 focus:ring-2 focus:ring-sky-400/20"
                                    />
                                </div>
                                <div role="listbox" aria-multiselectable="true" aria-labelledby="tag-picker-label" class="max-h-56 overflow-y-auto p-1">
                                    <button
                                        v-for="tag in filteredTags"
                                        :key="tag.id"
                                        type="button"
                                        role="option"
                                        :aria-selected="form.tag_ids.includes(tag.id)"
                                        class="flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-left text-sm transition hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-sky-400"
                                        :class="form.tag_ids.includes(tag.id) ? 'text-white' : 'text-neutral-300'"
                                        :disabled="!form.tag_ids.includes(tag.id) && form.tag_ids.length >= 10"
                                        @click="toggleTag(tag.id)"
                                    >
                                        <span class="flex size-4 shrink-0 items-center justify-center rounded border" :class="form.tag_ids.includes(tag.id) ? 'border-sky-400 bg-sky-400 text-neutral-950' : 'border-neutral-600'">
                                            <svg v-if="form.tag_ids.includes(tag.id)" viewBox="0 0 16 16" fill="none" class="size-3" aria-hidden="true">
                                                <path d="m3 8 3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <span class="size-2.5 rounded-full" :style="{ backgroundColor: tag.color }" aria-hidden="true"></span>
                                        <span class="flex-1">{{ tag.name }}</span>
                                        <span v-if="form.tag_ids.includes(tag.id)" class="text-xs text-neutral-500">Dipilih</span>
                                    </button>
                                    <p v-if="filteredTags.length === 0" class="px-3 py-6 text-center text-sm text-neutral-400">
                                        Tidak ada tag yang cocok.
                                    </p>
                                </div>
                                <div class="flex items-center justify-between border-t border-neutral-800 px-3 py-2">
                                    <span class="text-xs text-neutral-500">Maksimal 10 tag</span>
                                    <button type="button" class="rounded px-2 py-1 text-xs font-medium text-neutral-200 hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-400" @click="isOpen = false">
                                        Selesai
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p v-if="tagError" class="mt-2 text-sm text-rose-300">{{ tagError }}</p>
                        <p class="mt-2 text-xs leading-5 text-neutral-500">Cari lalu centang beberapa tag. Klik tag terpilih untuk membatalkan pilihan.</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex min-h-10 items-center justify-center rounded-lg bg-sky-400 px-4 py-2 text-sm font-semibold text-neutral-950 transition hover:bg-sky-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-300 focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-950 disabled:cursor-not-allowed disabled:bg-neutral-700 disabled:text-neutral-400"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan project' }}
                    </button>
                </form>
            </section>

            <section class="border-t border-neutral-800 pt-8 lg:border-l lg:border-t-0 lg:pl-10 lg:pt-0">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-white">Project terbaru</h2>
                        <p class="mt-1 text-sm text-neutral-400">Tag disimpan sebagai relasi terpisah.</p>
                    </div>
                    <span class="text-xs tabular-nums text-neutral-500">{{ projects.length }} ditampilkan</span>
                </div>

                <div v-if="success" role="status" class="mt-5 rounded-lg border border-emerald-800 bg-emerald-950/60 px-4 py-3 text-sm text-emerald-100">
                    {{ success }}
                </div>

                <div v-if="projects.length" class="mt-5 divide-y divide-neutral-800 border-y border-neutral-800">
                    <article v-for="project in projects" :key="project.id" class="py-5">
                        <h3 class="font-medium text-white">{{ project.name }}</h3>
                        <p v-if="project.description" class="mt-1 text-sm leading-6 text-neutral-400">{{ project.description }}</p>
                        <div v-if="project.tags.length" class="mt-3 flex flex-wrap gap-2">
                            <span v-for="tag in project.tags" :key="tag.id" class="inline-flex items-center gap-2 rounded-md border border-neutral-800 px-2.5 py-1 text-xs text-neutral-300">
                                <span class="size-2 rounded-full" :style="{ backgroundColor: tag.color }" aria-hidden="true"></span>
                                {{ tag.name }}
                            </span>
                        </div>
                        <p v-else class="mt-3 text-xs text-neutral-500">Belum ada tag.</p>
                    </article>
                </div>
                <div v-else class="mt-5 border-y border-neutral-800 py-10 text-sm text-neutral-400">
                    Belum ada project. Tambahkan project pertama menggunakan form.
                </div>
            </section>
        </div>
    </main>
</template>
