<template>
  <div class="space-y-6 w-full pb-10 font-sans antialiased text-slate-800 print-container">
    <!-- Header with Common Tabs Navigation -->
    <div class="space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-100">
        <div>
          <h1 class="text-[18.8px] font-extrabold text-slate-800 tracking-tight">Inventory Report</h1>
          <p class="text-[11.5px] text-slate-500 mt-0.5">Monitor inventory movement, stock in/out quantities, warehouse stock levels, and stock movement logs.</p>
        </div>
        <div class="flex items-center gap-2 print:hidden mt-2 sm:mt-0">
          <IconButton color="green" label="Export Excel" :disabled="loading || exportingExcel" :loading="exportingExcel" @click="exportExcel" title="Export Excel">
            <DownloadIcon class="w-3.5 h-3.5" />
          </IconButton>
        </div>
      </div>
    </div>



    <!-- Reusable Filter Bar Component -->
    <ReportFilterBar
      :loading="loading || tableLoading"

      @apply="fetchReport(1)"
      @reset="resetFilters"
    >
      <!-- Category Filter -->
      <div class="shrink-0">
        <label class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1">Category</label>
        <SearchableSelect
          v-model="filters.category_id"
          :options="categoryOptions"
          :serverSearch="true"
          :isLoading="isCategoriesLoading"
          @search="fetchCategoryOptions"
          placeholder="All Categories"
          clearLabel="All Categories"
          :allowClear="true"
        />
      </div>

      <!-- Product Filter -->
      <div class="shrink-0">
        <label class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1">Product</label>
        <SearchableSelect
          v-model="filters.product_id"
          :options="productOptions"
          :serverSearch="true"
          :isLoading="isProductsLoading"
          @search="fetchProductOptions"
          placeholder="All Products"
          clearLabel="All Products"
          :allowClear="true"
        />
      </div>

      <!-- User Filter -->
      <div class="shrink-0">
        <label class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1">Proceed By</label>
        <SearchableSelect
          v-model="filters.user_id"
          :options="userOptions"
          :serverSearch="true"
          :isLoading="isUsersLoading"
          @search="fetchUserOptions"
          placeholder="All Proceed By"
          clearLabel="All Proceed By"
          :allowClear="true"
        />
      </div>

      <!-- Stock Status Filter -->
      <div class="shrink-0">
        <label class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1">Type</label>
        <SearchableSelect
          v-model="filters.stock_status"
          :options="[
            { label: 'Stock IN', value: 'IN' },
            { label: 'Stock OUT', value: 'OUT' }
          ]"
          placeholder="All Types"
          clearLabel="All Types"
          :allowClear="true"
        />
      </div>

      <!-- Date Range -->
      <div class="shrink-0">
        <label class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1">Date Range</label>
        <div class="flex items-center bg-slate-50 border border-slate-200 rounded-xl px-3 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all">
          <input type="date" v-model="filters.start_date" class="bg-transparent py-1.5 px-1 text-slate-800 text-[11.5px] focus:outline-none cursor-pointer" />
          <span class="text-slate-300 text-[11.5px] mx-1">-</span>
          <input type="date" v-model="filters.end_date" class="bg-transparent py-1.5 px-1 text-slate-800 text-[11.5px] focus:outline-none cursor-pointer" />
        </div>
      </div>

      <!-- Search Input -->
      <div class="w-64 shrink-0">
        <label class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1">Search Product / Ref</label>
        <div class="relative">
          <SearchIcon class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          <input 
            type="text" 
            v-model="filters.search" 
            placeholder="Search product or reference..."
            class="w-full pl-9 pr-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-[11.5px] outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all text-slate-800"
          />
        </div>
      </div>
    </ReportFilterBar>

    <!-- Page Level Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center min-h-[400px] bg-white border border-slate-100 rounded-3xl p-12 space-y-3 text-slate-400 shadow-sm">
      <Loader2Icon class="w-10 h-10 animate-spin text-blue-600 mb-1" />
      <p class="text-[11.5px] font-bold text-slate-600 uppercase tracking-wider">Loading inventory report...</p>
    </div>

    <div v-else class="space-y-6">
      <!-- Summary Cards Grid (3 columns x 2 rows) -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <KpiCard
          label="In Stock Products"
          :value="reportData?.summary?.in_stock ?? '—'"
          :icon="PackageIcon"
          bgClass="bg-blue-50 border-blue-100 text-blue-600"
        />
        <KpiCard
          label="Low Stock"
          :value="reportData?.summary?.low_stock ?? '—'"
          :icon="AlertTriangleIcon"
          bgClass="bg-orange-50 border-orange-100 text-orange-600"
        />
        <KpiCard
          label="Out of Stock"
          :value="reportData?.summary?.out_of_stock ?? '—'"
          :icon="XCircleIcon"
          bgClass="bg-rose-50 border-rose-100 text-rose-600"
        />
        <KpiCard
          label="Stock In"
          :value="reportData?.summary?.stock_in !== undefined ? '+' + reportData.summary.stock_in : '—'"
          :icon="ArrowDownRightIcon"
          bgClass="bg-emerald-50 border-emerald-100 text-emerald-600"
        />
        <KpiCard
          label="Stock Out"
          :value="reportData?.summary?.stock_out !== undefined ? '-' + reportData.summary.stock_out : '—'"
          :icon="ArrowUpRightIcon"
          bgClass="bg-purple-50 border-purple-100 text-purple-600"
        />
        <KpiCard
          label="Net Movement"
          :value="reportData?.summary?.net_movement !== undefined ? (reportData.summary.net_movement > 0 ? '+' : '') + reportData.summary.net_movement : '—'"
          :icon="RefreshCwIcon"
          bgClass="bg-indigo-50 border-indigo-100 text-indigo-600"
        />
      </div>

      <!-- Charts Section (Using SalesChart component) -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- 1. Stock Movement Over Time (Line Chart) -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
          <div class="mb-4">
            <h2 class="text-[15.4px] font-bold text-slate-800 flex items-center gap-2">
              <TrendingUpIcon class="w-5 h-5 text-indigo-500" />
              Stock Movement Over Time
            </h2>
            <p class="text-[11.5px] text-slate-500 mt-1 pl-7">Stock coming IN vs OUT over the selected period.</p>
          </div>
          <SalesChart 
            heightClass="h-64"
            type="line"
            :labels="reportData?.charts?.movement_over_time?.map((m: any) => m.date) || []"
            :datasets="[
              {
                label: 'Stock IN',
                data: reportData?.charts?.movement_over_time?.map((m: any) => m.in) || [],
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4
              },
              {
                label: 'Stock OUT',
                data: reportData?.charts?.movement_over_time?.map((m: any) => m.out) || [],
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                fill: true,
                tension: 0.4
              }
            ]"
            formatType="number"
          />
        </div>

        <!-- 2. Top Stock Movement by Product (Bar Chart) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
          <div class="mb-4">
            <h2 class="text-[15.4px] font-bold text-slate-800 flex items-center gap-2">
              <PackageIcon class="w-5 h-5 text-blue-500" />
              Stock Movement by Product
            </h2>
            <p class="text-[11.5px] text-slate-500 mt-1 pl-7">Top 10 products with the most inventory movement.</p>
          </div>
          <SalesChart 
            heightClass="h-64"
            type="bar"
            :labels="reportData?.charts?.movement_by_product?.map((m: any) => m.label) || []"
            :datasets="[{
              label: 'Total Movement (Units)',
              data: reportData?.charts?.movement_by_product?.map((m: any) => m.value) || [],
              backgroundColor: '#3b82f6',
              borderRadius: 4
            }]"
            formatType="number"
          />
        </div>

        <!-- 3. Stock Status Distribution Chart -->
        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm flex flex-col space-y-4">
          <div class="pb-4 border-b border-slate-50">
            <h2 class="text-[15.4px] font-extrabold text-slate-800 flex items-center gap-2">
              <BoxIcon class="w-5 h-5 text-amber-500" />
              Stock Status Distribution
            </h2>
            <p class="text-[11.5px] text-slate-500 mt-1 pl-7">Shows inventory health across in stock, low stock, and out of stock products.</p>
          </div>
          <SalesChart 
            heightClass="h-72"
            type="doughnut"
            :labels="reportData?.charts?.stock_status_distribution?.map((s: any) => s.label) || []"
            :datasets="[{
              label: 'Products',
              data: reportData?.charts?.stock_status_distribution?.map((s: any) => s.value) || [],
              backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
              borderWidth: 0
            }]"
          />
        </div>
      </div>

      <!-- Standard DataTable Component Matching User Screenshot -->
      <DataTable
        :loading="loading || tableLoading"
        title="Stock Movements"
        subtitle="Full log of stock movements, inventory changes, and references."
        :columns="columns"
        :data="reportData?.table?.data || []"

        entityName="stock movements"
        emptyMessage="No stock movements found."
        :pagination="reportData?.table"
        v-model:sort-by="sortBy"
        v-model:sort-desc="sortDesc"
        :showPerPage="false"
        @page-change="fetchReport($event, true)"
        @sort="handleSort"
      >
        <template #header-actions>
          <div class="flex items-center gap-2">
            <label class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider">Rows</label>
            <div class="relative w-20">
              <select 
                v-model="perPage"
                class="block w-full px-3 py-1.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-[11.5px] transition-all disabled:cursor-not-allowed cursor-pointer appearance-none"
              >
                <option :value="15">15</option>
                <option :value="30">30</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-slate-400">
                <ChevronDownIcon class="w-4 h-4" />
              </div>
            </div>
          </div>
        </template>

        <!-- DATE Column -->
        <template #col_date="{ item }">
          <span class="text-[9.9px] font-semibold text-slate-500 whitespace-nowrap">{{ item.date }}</span>
        </template>

        <!-- PRODUCT Column -->
        <template #col_product="{ item }">
          <span class="font-bold text-slate-800 text-[9.9px]">{{ item.product }}</span>
        </template>

        <!-- SKU Column -->
        <template #col_sku="{ item }">
          <span class="text-[9.9px] text-slate-500 font-mono">{{ item.sku }}</span>
        </template>

        <!-- TYPE Column -->
        <template #col_type="{ item }">
          <span 
            class="px-2.5 py-0.5 rounded-full text-[8.7px] font-bold border uppercase tracking-wider"
            :class="item.type === 'IN' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100'"
          >
            {{ item.type }}
          </span>
        </template>

        <!-- QUANTITY Column -->
        <template #col_quantity="{ item }">
          <span class="font-extrabold text-[9.9px]" :class="item.type === 'IN' ? 'text-emerald-600' : 'text-rose-600'">
            {{ item.type === 'IN' ? '+' : '-' }}{{ item.quantity }}
          </span>
        </template>

        <!-- REFERENCE Column -->
        <template #col_reference="{ item }">
          <span class="text-[9.9px] text-slate-700 font-mono">{{ item.reference }}</span>
        </template>

        <!-- PROCESSED BY Column -->
        <template #col_processed_by="{ item }">
          <span class="text-[9.9px] font-semibold text-slate-600">{{ item.processed_by }}</span>
        </template>
      </DataTable>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { 
  AlertCircleIcon, 
  PackageIcon, 
  LayersIcon, 
  ArrowDownRightIcon, 
  ArrowUpRightIcon, 
  AlertTriangleIcon, 
  XCircleIcon, 
  RefreshCwIcon, 
  TrashIcon, 
  SearchIcon, 
  SparklesIcon,
  DownloadIcon,
  Loader2Icon,
  ChevronDownIcon,
  TrendingUpIcon,
  BoxIcon
} from '@lucide/vue'
import { ReportService } from '~/services/report.service'
import { useSelectFetch } from '~/composables/useSelectFetch'

import IconButton from '~/components/ui/IconButton.vue'

import KpiCard from '~/components/dashboard/KpiCard.vue'
import SalesChart from '~/components/dashboard/SalesChart.vue'
import ReportFilterBar from '~/components/ui/ReportFilterBar.vue'
import SearchableSelect from '~/components/ui/SearchableSelect.vue'
import DataTable from '~/components/ui/DataTable.vue'

definePageMeta({
  middleware: 'admin',
  layout: 'default'
})

const loading = ref(true)
const tableLoading = ref(false)
const exportingExcel = ref(false)
const error = ref('')
const reportData = ref<any>(null)
const page = ref(1)
const perPage = ref(15)
const sortBy = ref('date')
const sortDesc = ref(true)

watch(perPage, () => { fetchReport(1, true) })

import type { Column } from '~~/types/datatable'

const columns: Column[] = [
  { key: 'date', label: 'DATE', sortable: true },
  { key: 'product', label: 'PRODUCT', sortable: true },
  { key: 'sku', label: 'SKU', sortable: true },
  { key: 'type', label: 'TYPE', align: 'center' },
  { key: 'quantity', label: 'QUANTITY', align: 'center', sortable: true },
  { key: 'reference', label: 'REFERENCE' },
  { key: 'processed_by', label: 'PROCEED BY', align: 'center' },
]

function getDefaultDates() {
  const end = new Date()
  const start = new Date()
  start.setDate(end.getDate() - 29)
  
  const formatDate = (date: Date) => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  }

  return {
    start_date: formatDate(start),
    end_date: formatDate(end)
  }
}

const defaultDates = getDefaultDates()

const filters = reactive({
  start_date: defaultDates.start_date,
  end_date: defaultDates.end_date,
  search: '',
  category_id: '',
  product_id: '',
  user_id: '',
  stock_status: '',
})

const { selectOptions: categoryOptions, isLoading: isCategoriesLoading, fetchOptions: fetchCategoryOptions } = useSelectFetch({
  endpoint: '/admin/categories',
  staticParams: { status: 'all' },
  buildLabel: (c) => c.status === 'deleted' ? `${c.name} (Deleted)` : (c.status === 'inactive' ? `${c.name} (Inactive)` : c.name),
})

const { selectOptions: productOptions, isLoading: isProductsLoading, fetchOptions: fetchProductOptions } = useSelectFetch({
  endpoint: '/admin/products',
  staticParams: { status: 'all' },
  buildLabel: (p) => {
    let name = p.sku ? `${p.name} (${p.sku})` : p.name;
    if (p.status === 'deleted') name += ' (Deleted)';
    else if (p.status === 'inactive') name += ' (Inactive)';
    return name;
  },
  dynamicParams: () => ({ category_id: filters.category_id }),
})

watch(() => filters.category_id, () => {
  filters.product_id = ''
  fetchProductOptions()
})

const { selectOptions: userOptions, isLoading: isUsersLoading, fetchOptions: fetchUserOptions } = useSelectFetch({
  endpoint: '/admin/admins',
  staticParams: { include_superadmin: '1', status: 'all' },
  buildLabel: (u) => {
    let name = u.name;
    if (u.status === 'deleted') name += ' (Deleted)';
    else if (u.status === 'inactive') name += ' (Inactive)';
    return name;
  },
})



function resetFilters() {
  const defaults = getDefaultDates()
  filters.start_date = defaults.start_date
  filters.end_date = defaults.end_date
  filters.search = ''
  filters.category_id = ''
  filters.product_id = ''
  filters.user_id = ''
  filters.stock_status = ''
  fetchReport(1)
}

function handleSort() {
  fetchReport(1, true)
}

async function fetchReport(pageNumber = 1, isTableOnly = false) {
  if (!isTableOnly) {
    loading.value = true
  }
  tableLoading.value = true
  error.value = ''
  page.value = pageNumber

  const queryParams = new URLSearchParams()
  queryParams.set('page', page.value.toString())
  queryParams.set('per_page', perPage.value.toString())
  if (filters.start_date) queryParams.set('start_date', filters.start_date)
  if (filters.end_date) queryParams.set('end_date', filters.end_date)
  if (filters.search) queryParams.set('search', filters.search)
  if (filters.category_id) queryParams.set('category_id', filters.category_id)
  if (filters.product_id) queryParams.set('product_id', filters.product_id)
  if (filters.user_id) queryParams.set('user_id', filters.user_id)
  if (filters.stock_status) queryParams.set('stock_status', filters.stock_status)
  if (sortBy.value) queryParams.set('sort_by', sortBy.value)
  if (sortDesc.value !== undefined) queryParams.set('sort_dir', sortDesc.value ? 'desc' : 'asc')

  try {
    const res: any = await ReportService.getInventory(queryParams.toString())
    reportData.value = res
  } catch (err: any) {
    error.value = err.message || 'Failed to load inventory report.'
  } finally {
    loading.value = false
    tableLoading.value = false
  }
}

async function exportExcel() {
  exportingExcel.value = true
  try {
    const queryParams = new URLSearchParams()
    queryParams.set('export', 'excel')
    if (filters.start_date) queryParams.set('start_date', filters.start_date)
    if (filters.end_date) queryParams.set('end_date', filters.end_date)
    if (filters.search) queryParams.set('search', filters.search)
    if (filters.category_id) queryParams.set('category_id', filters.category_id)
    if (filters.product_id) queryParams.set('product_id', filters.product_id)
    if (filters.user_id) queryParams.set('user_id', filters.user_id)
    if (filters.stock_status) queryParams.set('stock_status', filters.stock_status)
    if (sortBy.value) queryParams.set('sort_by', sortBy.value)
    if (sortDesc.value !== undefined) queryParams.set('sort_dir', sortDesc.value ? 'desc' : 'asc')

    const res = await ReportService.exportInventory(queryParams.toString())

    const parts = [
      'inventory_report_',
      filters.category_id,
      filters.product_id,
      filters.user_id,
      filters.stock_status,
      filters.start_date,
      filters.end_date,
      filters.search,
    ]
    const filename = parts.filter(p => p).join('_') + '.xlsx'
    
    const url = window.URL.createObjectURL(new Blob([res as Blob]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (err: any) {
    console.error('Export failed:', err)
  } finally {
    exportingExcel.value = false
  }
}


onMounted(() => {
  fetchReport(1)
})
</script>
