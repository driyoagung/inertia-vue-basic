<script setup>
import { Head, router } from '@inertiajs/vue3';
import {
    FlexRender,
    createCoreRowModel,
    useTable,
} from '@tanstack/vue-table';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    orders: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    statuses: {
        type: Array,
        required: true,
    },
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const sort = ref(props.filters.sort ?? 'ordered_at');
const direction = ref(props.filters.direction ?? 'desc');

const columns = [
    {
        accessorKey: 'invoice_number',
        header: 'Invoice',
    },
    {
        accessorKey: 'customer_name',
        header: 'Customer',
    },
    {
        accessorKey: 'customer_email',
        header: 'Email',
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ getValue }) => getValue(),
    },
    {
        accessorKey: 'total',
        header: 'Total',
        cell: ({ getValue }) => formatCurrency(getValue()),
    },
    {
        accessorKey: 'ordered_at',
        header: 'Ordered At',
    },
];

const table = useTable({
    get data() {
        return props.orders.data;
    },
    columns,
    getCoreRowModel: createCoreRowModel(),
    manualPagination: true,
    manualSorting: true,
    pageCount: props.orders.last_page,
});

const visiblePages = computed(() => {
    const current = props.orders.current_page;
    const last = props.orders.last_page;
    const start = Math.max(1, current - 2);
    const end = Math.min(last, current + 2);

    return Array.from({ length: end - start + 1 }, (_, index) => start + index);
});

let searchTimer;

watch([search, status, perPage], () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => reload({ page: 1 }), 350);
});

function reload(extra = {}) {
    router.get(route('large-orders.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
        per_page: perPage.value,
        sort: sort.value,
        direction: direction.value,
        ...extra,
    }, {
        preserveState: true,
        replace: true,
        only: ['orders', 'filters'],
    });
}

function changeSort(column) {
    if (sort.value === column) {
        direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value = column;
        direction.value = 'asc';
    }

    reload({ page: 1 });
}

function goToPage(page) {
    if (page < 1 || page > props.orders.last_page || page === props.orders.current_page) {
        return;
    }

    reload({ page });
}

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}
</script>

<template>
    <Head title="Large Orders" />

    <main class="min-h-screen bg-neutral-950 px-4 py-8 text-neutral-100 sm:px-6 lg:px-8">
        <section class="mx-auto max-w-7xl">
            <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium uppercase tracking-[0.2em] text-neutral-500">Inertia Vue Laravel</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-white">Server-side TanStack Table</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-neutral-400">
                        Studi kasus menampilkan ratusan ribu order tanpa mengambil semua data sekaligus dari database.
                    </p>
                </div>
                <div class="rounded-xl border border-neutral-800 bg-neutral-900 px-4 py-3 text-sm text-neutral-300">
                    {{ orders.total.toLocaleString('id-ID') }} rows
                </div>
            </div>

            <div class="mb-4 grid gap-3 rounded-2xl border border-neutral-800 bg-neutral-900 p-4 shadow-2xl shadow-black/20 md:grid-cols-[1fr_180px_140px]">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Cari invoice, customer, email..."
                    class="rounded-xl border border-neutral-700 bg-neutral-950 px-4 py-2.5 text-sm text-white outline-none transition focus:border-white"
                />
                <select
                    v-model="status"
                    class="rounded-xl border border-neutral-700 bg-neutral-950 px-4 py-2.5 text-sm text-white outline-none transition focus:border-white"
                >
                    <option value="">Semua status</option>
                    <option v-for="item in statuses" :key="item" :value="item">
                        {{ item }}
                    </option>
                </select>
                <select
                    v-model="perPage"
                    class="rounded-xl border border-neutral-700 bg-neutral-950 px-4 py-2.5 text-sm text-white outline-none transition focus:border-white"
                >
                    <option :value="10">10 / page</option>
                    <option :value="25">25 / page</option>
                    <option :value="50">50 / page</option>
                    <option :value="100">100 / page</option>
                </select>
            </div>

            <div class="overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-900 shadow-2xl shadow-black/20">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-800 text-sm">
                        <thead class="bg-neutral-950/70 text-left text-xs uppercase tracking-wider text-neutral-500">
                            <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                                <th
                                    v-for="header in headerGroup.headers"
                                    :key="header.id"
                                    class="whitespace-nowrap px-4 py-3 font-medium"
                                >
                                    <button
                                        type="button"
                                        class="flex items-center gap-2 transition hover:text-white"
                                        @click="changeSort(header.column.id)"
                                    >
                                        <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
                                        <span v-if="sort === header.column.id">{{ direction === 'asc' ? '↑' : '↓' }}</span>
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-800">
                            <tr v-for="row in table.getRowModel().rows" :key="row.id" class="transition hover:bg-neutral-800/50">
                                <td
                                    v-for="cell in row.getAllCells()"
                                    :key="cell.id"
                                    class="whitespace-nowrap px-4 py-3 text-neutral-300"
                                >
                                    <span
                                        v-if="cell.column.id === 'status'"
                                        class="rounded-full border border-neutral-700 px-2.5 py-1 text-xs font-medium capitalize text-neutral-200"
                                    >
                                        <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                    </span>
                                    <FlexRender v-else :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                </td>
                            </tr>
                            <tr v-if="table.getRowModel().rows.length === 0">
                                <td :colspan="columns.length" class="px-4 py-10 text-center text-neutral-500">
                                    Data tidak ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-3 border-t border-neutral-800 px-4 py-4 text-sm text-neutral-400 sm:flex-row sm:items-center sm:justify-between">
                    <p>
                        Menampilkan {{ orders.from ?? 0 }} - {{ orders.to ?? 0 }} dari {{ orders.total.toLocaleString('id-ID') }} data
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-neutral-700 px-3 py-1.5 transition hover:border-white disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="orders.current_page === 1"
                            @click="goToPage(orders.current_page - 1)"
                        >
                            Prev
                        </button>
                        <button
                            v-for="page in visiblePages"
                            :key="page"
                            type="button"
                            class="rounded-lg border px-3 py-1.5 transition hover:border-white"
                            :class="page === orders.current_page ? 'border-white bg-white text-neutral-950' : 'border-neutral-700'"
                            @click="goToPage(page)"
                        >
                            {{ page }}
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-neutral-700 px-3 py-1.5 transition hover:border-white disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="orders.current_page === orders.last_page"
                            @click="goToPage(orders.current_page + 1)"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </main>
</template>
