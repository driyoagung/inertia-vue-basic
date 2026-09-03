<script setup>
import { ref, h } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
  FlexRender,
  getCoreRowModel,
  useVueTable,
  getPaginationRowModel,
  getSortedRowModel,
  getFilteredRowModel,
} from '@tanstack/vue-table'

const props = defineProps({
  employees: {
    type: Array,
    required: true,
  }
})

const data = ref(props.employees)
const globalFilter = ref('')
const sorting = ref([])

const columns = [
  {
    accessorKey: 'id',
    header: 'ID',
    cell: info => info.getValue(),
  },
  {
    accessorKey: 'name',
    header: 'Name',
    cell: info => info.getValue(),
  },
  {
    accessorKey: 'email',
    header: 'Email',
    cell: info => info.getValue(),
  },
  {
    accessorKey: 'department',
    header: 'Department',
    cell: info => info.getValue(),
  },
  {
    accessorKey: 'position',
    header: 'Position',
    cell: info => info.getValue(),
  },
  {
    accessorKey: 'salary',
    header: 'Salary',
    cell: info => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(info.getValue()),
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: info => {
      const status = info.getValue()
      let colorClass = 'bg-gray-100 text-gray-800'
      if (status === 'active') colorClass = 'bg-green-100 text-green-800'
      if (status === 'inactive') colorClass = 'bg-red-100 text-red-800'
      if (status === 'on-leave') colorClass = 'bg-yellow-100 text-yellow-800'
      return h('span', { class: `px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${colorClass}` }, status)
    },
  },
]

const table = useVueTable({
  get data() {
    return data.value
  },
  columns,
  state: {
    get globalFilter() {
      return globalFilter.value
    },
    get sorting() {
      return sorting.value
    },
  },
  onGlobalFilterChange: setGlobalFilter => {
    globalFilter.value = setGlobalFilter
  },
  onSortingChange: updaterOrValue => {
    sorting.value = typeof updaterOrValue === 'function' ? updaterOrValue(sorting.value) : updaterOrValue
  },
  getCoreRowModel: getCoreRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
})
</script>

<template>
  <Head title="TanStack Table Study Case" />

  <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-7xl mx-auto">
      
      <!-- Header -->
      <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Employee Directory</h1>
          <p class="mt-2 text-sm text-gray-500">
            A demonstration of TanStack Table in a Laravel + Vue + Inertia stack.
          </p>
        </div>
        <Link href="/" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
          &larr; Back to Home
        </Link>
      </div>

      <!-- Main Content -->
      <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        
        <!-- Toolbar -->
        <div class="p-5 border-b border-gray-100 bg-white/50 backdrop-blur-sm flex justify-between items-center">
          <div class="relative rounded-lg shadow-sm max-w-md w-full">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="globalFilter"
              type="text"
              class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-200 rounded-lg py-2.5 px-4 outline-none transition-all duration-200"
              placeholder="Search employees..."
            />
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50/80">
              <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                <th
                  v-for="header in headerGroup.headers"
                  :key="header.id"
                  scope="col"
                  class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-200/50 transition-colors select-none group"
                  @click="header.column.getToggleSortingHandler()?.($event)"
                >
                  <div class="flex items-center space-x-2">
                    <FlexRender
                      v-if="!header.isPlaceholder"
                      :render="header.column.columnDef.header"
                      :props="header.getContext()"
                    />
                    <!-- Sort Indicator -->
                    <span class="text-gray-400 group-hover:text-indigo-400 transition-colors w-4 flex justify-center">
                      <template v-if="header.column.getIsSorted() === 'asc'">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7-7"></path></svg>
                      </template>
                      <template v-else-if="header.column.getIsSorted() === 'desc'">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                      </template>
                      <template v-else>
                         <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>
                      </template>
                    </span>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
              <tr v-for="row in table.getRowModel().rows" :key="row.id" class="hover:bg-indigo-50/30 transition-colors">
                <td
                  v-for="cell in row.getVisibleCells()"
                  :key="cell.id"
                  class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"
                >
                   <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                </td>
              </tr>
            </tbody>
          </table>
          
          <!-- Empty State -->
          <div v-if="table.getRowModel().rows.length === 0" class="p-8 text-center text-gray-500">
            No employees found matching your search.
          </div>
        </div>

        <!-- Pagination -->
        <div class="bg-gray-50/50 px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
          
          <!-- Mobile Pagination -->
          <div class="flex-1 flex justify-between w-full sm:hidden">
            <button
              @click="table.previousPage()"
              :disabled="!table.getCanPreviousPage()"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
            >
              Previous
            </button>
            <button
              @click="table.nextPage()"
              :disabled="!table.getCanNextPage()"
              class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
            >
              Next
            </button>
          </div>
          
          <!-- Desktop Pagination -->
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div class="flex items-center space-x-4">
              <p class="text-sm text-gray-600">
                Showing Page <span class="font-semibold text-gray-900">{{ table.getState().pagination.pageIndex + 1 }}</span> of
                <span class="font-semibold text-gray-900">{{ table.getPageCount() }}</span>
              </p>
              
              <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">Rows per page:</span>
                <select
                    :value="table.getState().pagination.pageSize"
                    @change="e => table.setPageSize(Number(e.target.value))"
                    class="block w-full pl-3 pr-8 py-1.5 text-sm border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white shadow-sm"
                  >
                    <option v-for="pageSize in [10, 20, 30, 40, 50]" :key="pageSize" :value="pageSize">
                      {{ pageSize }}
                    </option>
                </select>
              </div>
            </div>
            
            <div>
              <nav class="relative z-0 inline-flex rounded-lg shadow-sm -space-x-px" aria-label="Pagination">
                <button
                  @click="table.setPageIndex(0)"
                  :disabled="!table.getCanPreviousPage()"
                  class="relative inline-flex items-center px-3 py-2 rounded-l-lg border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  <span class="sr-only">First</span>
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
                </button>
                <button
                  @click="table.previousPage()"
                  :disabled="!table.getCanPreviousPage()"
                  class="relative inline-flex items-center px-3 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  <span class="sr-only">Previous</span>
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <button
                  @click="table.nextPage()"
                  :disabled="!table.getCanNextPage()"
                  class="relative inline-flex items-center px-3 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  <span class="sr-only">Next</span>
                   <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
                <button
                  @click="table.setPageIndex(table.getPageCount() - 1)"
                  :disabled="!table.getCanNextPage()"
                  class="relative inline-flex items-center px-3 py-2 rounded-r-lg border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  <span class="sr-only">Last</span>
                   <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7m-8 14l7-7-7-7"></path></svg>
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
