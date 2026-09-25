<template>
  <div class="space-y-6 w-full pb-10 font-sans antialiased text-slate-800 print-container">
    <!-- Header with Common Tabs Navigation -->
    <div class="space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-100">
        <div>
          <h1 class="text-[18.8px] font-extrabold text-slate-800 tracking-tight">Product Report</h1>
          <p class="text-[11.5px] text-slate-500 mt-0.5">Analyze product catalog, category distribution, stock levels, and active status.</p>
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
      <div class="shrink-0 min-w-[180px]">
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

      <!-- Status Filter -->
      <div class="shrink-0">
        <label class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1">Status</label>
        <SearchableSelect
          v-model="filters.status"
          :options="[
            { label: 'Active', value: 'Active' },
            { label: 'Inactive', value: 'Inactive' },
            { label: 'Deleted', value: 'Deleted' }
          ]"
          placeholder="All Statuses"
          clearLabel="All Statuses"
          :allowClear="true"
        />
      </div>

      <!-- Discount Filter -->
      <div class="shrink-0">
        <label class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1">Discount</label>
        <SearchableSelect
          v-model="filters.discount"
          :options="[
            { label: 'Has Discount', value: 'has_discount' },
            { label: 'No Discount', value: 'no_discount' }
          ]"
          placeholder="All Discounts"
          clearLabel="All Discounts"
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


    </ReportFilterBar>

    <!-- Page Level Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center min-h-[400px] bg-white border border-slate-100 rounded-3xl p-12 space-y-3 text-slate-400 shadow-sm">
      <Loader2Icon class="w-10 h-10 animate-spin text-blue-600 mb-1" />
      <p class="text-[11.5px] font-bold text-slate-600 uppercase tracking-wider">Loading product report...</p>
    </div>

    <div v-else class="space-y-6">
      <!-- Summary Cards Grid (2 columns x 2 rows) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <KpiCard
          label="Total Products"
          :value="reportData?.summary?.total_products ?? '—'"
          :icon="PackageIcon"
          bgClass="bg-blue-50 border-blue-100 text-blue-600"
        />
        <KpiCard
          label="Active Products"
          :value="reportData?.summary?.active_products ?? '—'"
          :icon="CheckCircleIcon"
          bgClass="bg-emerald-50 border-emerald-100 text-emerald-600"
        />
        <KpiCard
          label="Inactive Products"
          :value="reportData?.summary?.inactive_products ?? '—'"
          :icon="XCircleIcon"
          bgClass="bg-slate-50 border-slate-200 text-slate-600"
        />
        <KpiCard
          label="Deleted Products"
          :value="reportData?.summary?.deleted_products ?? '—'"
          :icon="Trash2Icon"
          bgClass="bg-rose-50 border-rose-100 text-rose-600"
        />

      </div>

      <!-- Products Added Over Time (Full Row Line Chart) -->
      <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm flex flex-col space-y-4">
        <div class="pb-4 border-b border-slate-50">
          <h2 class="text-[15.4px] font-extrabold text-slate-800 flex items-center gap-2">
            <SparklesIcon class="w-5 h-5 text-blue-500" />
            Products Added Over Time
          </h2>
          <p class="text-[11.5px] text-slate-500 mt-1 pl-7">A daily breakdown of new products created in your catalog over the selected date range.</p>
        </div>
        <SalesChart 
          heightClass="h-72"
          type="line"
          formatType="number"
          :labels="reportData?.charts?.products_added_over_time?.map((d: any) => d.label) || []"
          :datasets="[{
            label: 'Products Created',
            data: reportData?.charts?.products_added_over_time?.map((d: any) => d.count) || [],
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#3b82f6',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6
          }]"
        />
      </div>

      <!-- Charts Section (Using SalesChart component) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Products by Category Chart -->
        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm flex flex-col space-y-4">
          <div class="pb-4 border-b border-slate-50">
            <h2 class="text-[15.4px] font-extrabold text-slate-800 flex items-center gap-2">
              <FolderOpenIcon class="w-5 h-5 text-blue-500" />
              Products by Category
            </h2>
            <p class="text-[11.5px] text-slate-500 mt-1 pl-7">Shows how products are distributed across categories to understand your catalog.</p>
          </div>
          <SalesChart 
            heightClass="h-72"
            type="bar"
            formatType="number"
            :labels="reportData?.charts?.products_by_category?.map((p: any) => p.label) || []"
            :datasets="[{
              label: 'Products',
              data: reportData?.charts?.products_by_category?.map((p: any) => p.value) || [],
              backgroundColor: '#3b82f6',
              borderRadius: 6
            }]"
          />
        </div>

        <!-- Price Range Distribution Chart -->
        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm flex flex-col space-y-4">
          <div class="pb-4 border-b border-slate-50">
            <h2 class="text-[15.4px] font-extrabold text-slate-800 flex items-center gap-2">
              <DollarSignIcon class="w-5 h-5 text-emerald-500" />
              Price Range Distribution
            </h2>
            <p class="text-[11.5px] text-slate-500 mt-1 pl-7">Shows product count across price range tiers in your catalog.</p>
          </div>
          <SalesChart 
            heightClass="h-72"
            type="bar"
            formatType="number"
            :labels="reportData?.charts?.price_range_distribution?.map((pr: any) => pr.label) || []"
            :datasets="[{
              label: 'Products',
              data: reportData?.charts?.price_range_distribution?.map((pr: any) => pr.value) || [],
              backgroundColor: '#10b981',
              borderRadius: 6
            }]"
          />
        </div>

      </div>

      <!-- Standard DataTable Component Matching All Backend Data -->
      <DataTable
        :loading="loading || tableLoading"
        title="Product Records"
        subtitle="Comprehensive breakdown of all products in the catalog."
        :columns="columns"
        :data="reportData?.table?.data || []"

        entityName="products"
        emptyMessage="No products found."
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

        <!-- CREATED DATE Column -->
        <template #col_created_at_formatted="{ item }">
          <span class="text-[9.9px] font-medium text-slate-500 whitespace-nowrap">{{ item.created_at_formatted }}</span>
        </template>

        <!-- NAME Column (Thumbnail + Title) -->
        <template #col_name="{ item }">
          <div class="flex items-center gap-3 py-1">
            <div class="w-10 h-10 rounded-xl overflow-hidden bg-slate-100 shrink-0 border border-slate-100 flex items-center justify-center shadow-xs">
              <img v-if="item.image" :src="getImageUrl(item.image)!" :alt="item.name" class="w-full h-full object-cover" />
              <PackageIcon v-else class="w-5 h-5 text-slate-400" />
            </div>
            <span class="font-bold text-slate-800 text-[11.5px] truncate max-w-xs">{{ item.name }}</span>
          </div>
        </template>

        <!-- SKU Column -->
        <template #col_sku="{ item }">
          <span class="inline-block px-2.5 py-0.5 text-[9.9px] font-mono font-medium text-slate-600 bg-slate-100 border border-slate-200 rounded-md">
            {{ item.sku }}
          </span>
        </template>

        <!-- PRICE Column (Struck through original price + bold final price) -->
        <template #col_price="{ item }">
          <div class="flex items-center justify-end gap-2 font-semibold">
            <span v-if="item.discount_percent > 0" class="text-[9.9px] text-slate-400 line-through">
              {{ formatPrice(item.original_price) }}
            </span>
            <span class="font-bold text-slate-900 text-[11.5px]">
              {{ formatPrice(item.final_price) }}
            </span>
          </div>
        </template>

        <!-- DISCOUNT Column (-XX% in red text) -->
        <template #col_discount_percent="{ item }">
          <span v-if="item.discount_percent > 0" class="text-[8.7px] font-bold text-rose-500">
            -{{ item.discount_percent }}%
          </span>
          <span v-else class="text-[9.9px] text-slate-300">—</span>
        </template>

        <!-- STOCK Column (Color Coded Pill Badge) -->
        <template #col_current_stock="{ item }">
          <span 
            class="inline-block px-3 py-0.5 text-[8.7px] font-bold rounded-full border"
            :class="{
              'bg-rose-50 text-rose-600 border-rose-200': item.current_stock <= 0,
              'bg-amber-50 text-amber-600 border-amber-200': item.current_stock > 0 && item.current_stock <= 10,
              'bg-emerald-50 text-emerald-600 border-emerald-200': item.current_stock > 10
            }"
          >
            {{ item.current_stock }}
          </span>
        </template>

        <!-- SOLD Column -->
        <template #col_sold_units="{ item }">
          <span class="text-[8.7px] font-bold text-slate-700">
            {{ item.sold_units }}
          </span>
        </template>

        <!-- STATUS Column (Active/Inactive/Deleted Pill Badge) -->
        <template #col_status="{ item }">
          <span 
            class="inline-block px-3 py-0.5 text-[8.7px] font-bold rounded-full border"
            :class="{
              'bg-emerald-50 text-emerald-700 border-emerald-100': item.status === 'Active',
              'bg-slate-100 text-slate-600 border-slate-200': item.status === 'Inactive',
              'bg-rose-50 text-rose-700 border-rose-200': item.status === 'Deleted'
            }"
          >
            {{ item.status }}
          </span>
        </template>

        <!-- ACTIONS Column (Eye Icon Button) -->
        <template #col_actions="{ item }">
          <NuxtLink 
            :to="`/products/${item.id}`"
            class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
            title="View Product"
          >
            <EyeIcon class="w-4 h-4" />
          </NuxtLink>
        </template>
      </DataTable>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive, watch } from 'vue'
import { 
  AlertCircleIcon, 
  PackageIcon, 
  CheckCircleIcon, 
  XCircleIcon, 
  DollarSignIcon,
  SparklesIcon,
  DownloadIcon,
  Loader2Icon,
  ChevronDownIcon,
  FolderOpenIcon,
  EyeIcon,
  Trash2Icon
} from '@lucide/vue'
import { ReportService } from '~/services/report.service'
import { useSelectFetch } from '~/composables/useSelectFetch'
import { useProductImage } from '~/composables/useProductImage'

import IconButton from '~/components/ui/IconButton.vue'

import KpiCard from '~/components/dashboard/KpiCard.vue'
import SalesChart from '~/components/dashboard/SalesChart.vue'
import ReportFilterBar from '~/components/ui/ReportFilterBar.vue'
import SearchableSelect from '~/components/ui/SearchableSelect.vue'
import DataTable from '~/components/ui/DataTable.vue'
import type { Column } from '~~/types/datatable'

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
const sortBy = ref('sold_units')
const sortDesc = ref(true)

const { getImageUrl } = useProductImage()

watch(perPage, () => { fetchReport(1, true) })

const columns: Column[] = [
  { key: 'created_at_formatted', label: 'CREATED DATE', sortable: true },
  { key: 'name', label: 'NAME', sortable: true },
  { key: 'sku', label: 'SKU', align: 'center', sortable: true },
  { key: 'price', label: 'PRICE', align: 'right', sortable: true },
  { key: 'discount_percent', label: 'DISCOUNT', align: 'center', sortable: true },
  { key: 'current_stock', label: 'STOCK', align: 'center', sortable: true },
  { key: 'sold_units', label: 'SOLD', align: 'center', sortable: true },
  { key: 'status', label: 'STATUS', align: 'center' },
  { key: 'actions', label: 'ACTION', align: 'center', sortable: false },
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
  category_id: '',
  product_id: '',
  status: '',
  discount: '',
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
  dynamicParams: () => ({ category_id: filters.category_id })
})

watch(() => filters.category_id, () => {
  filters.product_id = ''
  fetchProductOptions()
})




function resetFilters() {
  const defaults = getDefaultDates()
  filters.start_date = defaults.start_date
  filters.end_date = defaults.end_date
  filters.category_id = ''
  filters.product_id = ''
  filters.status = ''
  filters.discount = ''
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
  if (filters.category_id) queryParams.set('category_id', filters.category_id)
  if (filters.product_id) queryParams.set('product_id', filters.product_id)
  if (filters.status) queryParams.set('status', filters.status)
  if (filters.discount) queryParams.set('discount', filters.discount)
  if (filters.stock_status) queryParams.set('stock_status', filters.stock_status)
  if (sortBy.value) queryParams.set('sort_by', sortBy.value)
  if (sortDesc.value !== undefined) queryParams.set('sort_dir', sortDesc.value ? 'desc' : 'asc')

  try {
    const res: any = await ReportService.getProducts(queryParams.toString())
    reportData.value = res
  } catch (err: any) {
    error.value = err.message || 'Failed to load product report.'
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
    if (filters.category_id) queryParams.set('category_id', filters.category_id)
    if (filters.product_id) queryParams.set('product_id', filters.product_id)
    if (filters.status) queryParams.set('status', filters.status)
    if (filters.discount) queryParams.set('discount', filters.discount)
    if (filters.stock_status) queryParams.set('stock_status', filters.stock_status)
    if (sortBy.value) queryParams.set('sort_by', sortBy.value)
    if (sortDesc.value !== undefined) queryParams.set('sort_dir', sortDesc.value ? 'desc' : 'asc')

    const res: any = await ReportService.exportProducts(queryParams.toString())

    if (res instanceof Blob && res.type.includes('application/json')) {
      const text = await res.text()
      try {
        const json = JSON.parse(text)
        alert(json.message || 'No product data found for selected filters.')
        return
      } catch (e) {}
    }

    const todayStr = new Date().toISOString().split('T')[0]
    const filename = `Product_Report_${todayStr}.xlsx`
    
    const url = window.URL.createObjectURL(new Blob([res as Blob]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (err: any) {
    alert(err.message || 'No product data found for selected filters.')
  } finally {
    exportingExcel.value = false
  }
}


onMounted(() => {
  fetchReport(1)
})
</script>
