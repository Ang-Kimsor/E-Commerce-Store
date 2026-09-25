<template>
  <div class="space-y-6 w-full pb-10 font-sans antialiased text-slate-800 print-container">
    <!-- Header with Common Tabs Navigation -->
    <div class="space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-100">
        <div>
          <h1 class="text-[18.8px] font-extrabold text-slate-800 tracking-tight">Sales Report</h1>
          <p class="text-[11.5px] text-slate-500 mt-0.5">Display sales performance, order metrics, and revenue breakdown during a selected period.</p>
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
      <!-- Order Filter -->
      <div class="shrink-0 min-w-[180px]">
        <label class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1">Order</label>
        <SearchableSelect
          v-model="filters.order_id"
          :options="orderOptions"
          :serverSearch="true"
          :isLoading="isOrdersLoading"
          @search="fetchOrderOptions"
          placeholder="All Orders"
          clearLabel="All Orders"
          :allowClear="true"
        />
      </div>

      <!-- Customer Filter -->
      <div class="shrink-0 min-w-[180px]">
        <label class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1">Customer</label>
        <SearchableSelect
          v-model="filters.customer_id"
          :options="customerOptions"
          :serverSearch="true"
          :isLoading="isCustomersLoading"
          @search="fetchCustomerOptions"
          placeholder="All Customers"
          clearLabel="All Customers"
          :allowClear="true"
        />
      </div>

      <!-- Order Status Filter -->
      <div class="shrink-0">
        <label class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1">Order Status</label>
        <SearchableSelect
          v-model="filters.status"
          :options="[
            { label: 'Pending', value: 'pending' },
            { label: 'Confirmed', value: 'confirmed' },
            { label: 'Processing', value: 'processing' },
            { label: 'Shipped', value: 'shipped' },
            { label: 'Delivered', value: 'delivered' },
            { label: 'Completed', value: 'completed' },
            { label: 'Cancelled', value: 'cancelled' },
            { label: 'Returned', value: 'returned' }
          ]"
          placeholder="All Order Statuses"
          clearLabel="All Order Statuses"
          :allowClear="true"
        />
      </div>

      <!-- Payment Status Filter -->
      <div class="shrink-0">
        <label class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1">Payment Status</label>
        <SearchableSelect
          v-model="filters.payment_status"
          :options="[
            { label: 'Unpaid', value: 'unpaid' },
            { label: 'Paid', value: 'paid' },
            { label: 'Refunded', value: 'refunded' }
          ]"
          placeholder="All Payment Statuses"
          clearLabel="All Payment Statuses"
          :allowClear="true"
        />
      </div>

      <!-- Payment Method Filter -->
      <div class="shrink-0">
        <label class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1">Payment Method</label>
        <SearchableSelect 
          v-model="filters.payment_method" 
          :options="[{label: 'Cash', value: 'cash'}, {label: 'Bank', value: 'bank'}, {label: 'None / Blank', value: 'blank'}]" 
          placeholder="All Payment Methods"
          clearLabel="All Payment Methods"
          :allowClear="true"
        />
      </div>

      <!-- Province Filter -->
      <div class="shrink-0 min-w-[160px]">
        <label class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1">Province</label>
        <SearchableSelect 
          v-model="filters.province" 
          :options="CAMBODIA_PROVINCES" 
          placeholder="All Provinces"
          clearLabel="All Provinces"
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
      <p class="text-[11.5px] font-bold text-slate-600 uppercase tracking-wider">Loading sales report...</p>
    </div>

    <div v-else class="space-y-6">
      <!-- Summary Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <KpiCard
          label="Total Orders"
          :value="reportData?.summary?.total_orders ?? '—'"
          :icon="ShoppingBagIcon"
          bgClass="bg-blue-50 border-blue-100 text-blue-600"
        />
        <KpiCard
          label="Total Sales"
          :value="reportData?.summary?.total_sales !== undefined ? formatPrice(reportData.summary.total_sales) : '—'"
          :icon="DollarSignIcon"
          bgClass="bg-emerald-50 border-emerald-100 text-emerald-600"
        />
        <KpiCard
          label="Products Sold"
          :value="reportData?.summary?.products_sold ?? '—'"
          :icon="PackageIcon"
          bgClass="bg-purple-50 border-purple-100 text-purple-600"
        />
        <KpiCard
          label="Avg. Order Value"
          :value="reportData?.summary?.average_order_value !== undefined ? formatPrice(reportData.summary.average_order_value) : '—'"
          :icon="TrendingUpIcon"
          bgClass="bg-indigo-50 border-indigo-100 text-indigo-600"
        />
      </div>

      <!-- Visual Charts Grid (Using SalesChart component) -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Daily Sales Trend (Line Chart) -->
        <div class="lg:col-span-2 bg-white border border-slate-100 rounded-2xl p-5 shadow-sm flex flex-col space-y-4">
          <template v-if="!loading">
            <div class="pb-4 border-b border-slate-50">
              <h2 class="text-[15.4px] font-extrabold text-slate-800 flex items-center gap-2">
                <TrendingUpIcon class="w-5 h-5 text-blue-500" />
                Daily Sales Trend ({{ dayCount }} Days)
              </h2>
              <p class="text-[11.5px] text-slate-500 mt-1 pl-7">A daily breakdown of your revenue for the selected {{ dayCount }} days, helping you track sales trends.</p>
            </div>
            <SalesChart 
              heightClass="h-96"
              type="line"
              :labels="reportData?.charts?.daily_sales?.map((d: any) => d.label) || []"
              :datasets="[{
                label: 'Revenue',
                data: reportData?.charts?.daily_sales?.map((d: any) => d.revenue) || [],
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
          </template>
        </div>

        <!-- Monthly Revenue Breakdown (Bar Chart) -->
        <div class="lg:col-span-2 bg-white border border-slate-100 rounded-2xl p-5 shadow-sm flex flex-col space-y-4">
          <template v-if="!loading">
            <div class="pb-4 border-b border-slate-50">
              <h2 class="text-[15.4px] font-extrabold text-slate-800 flex items-center gap-2">
                <DollarSignIcon class="w-5 h-5 text-emerald-500" />
                Monthly Revenue Breakdown
              </h2>
              <p class="text-[11.5px] text-slate-500 mt-1 pl-7">Monthly revenue comparison for the current year.</p>
            </div>
            <SalesChart 
              heightClass="h-72"
              type="bar"
              :labels="reportData?.charts?.monthly_sales?.map((m: any) => m.label) || []"
              :datasets="[{
                label: 'Revenue',
                data: reportData?.charts?.monthly_sales?.map((m: any) => m.revenue) || [],
                backgroundColor: '#10b981',
                borderRadius: 4
              }]"
            />
          </template>
        </div>

        <!-- Revenue by Province (Bar Chart) -->
        <div class="lg:col-span-2 bg-white border border-slate-100 rounded-2xl p-5 shadow-sm flex flex-col space-y-4">
          <template v-if="!loading">
            <div class="pb-4 border-b border-slate-50">
              <h2 class="text-[15.4px] font-extrabold text-slate-800 flex items-center gap-2">
                <MapPinIcon class="w-5 h-5 text-indigo-500" />
                Revenue by Province
              </h2>
              <p class="text-[11.5px] text-slate-500 mt-1 pl-7">Revenue breakdown by shipping destination province.</p>
            </div>
            <SalesChart 
              heightClass="h-72"
              type="bar"
              :labels="reportData?.charts?.revenue_by_province?.map((p: any) => p.label) || []"
              :datasets="[{
                label: 'Revenue',
                data: reportData?.charts?.revenue_by_province?.map((p: any) => p.revenue) || [],
                backgroundColor: '#6366f1',
                borderRadius: 4
              }]"
            />
          </template>
        </div>

        <!-- Order Status Distribution (Doughnut Chart) -->
        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm flex flex-col space-y-4">
          <template v-if="!loading">
            <div class="pb-4 border-b border-slate-50">
              <h2 class="text-[15.4px] font-extrabold text-slate-800 flex items-center gap-2">
                <ClockIcon class="w-5 h-5 text-amber-500" />
                Order Status Distribution
              </h2>
              <p class="text-[11.5px] text-slate-500 mt-1 pl-7">Distribution of all orders by current fulfillment status.</p>
            </div>
            <SalesChart 
              heightClass="h-72"
              type="doughnut"
              :labels="reportData?.charts?.order_status?.map((s: any) => s.label) || []"
              :datasets="[{
                label: 'Orders',
                data: reportData?.charts?.order_status?.map((s: any) => s.value) || [],
                backgroundColor: ['#eab308', '#3b82f6', '#a855f7', '#6366f1', '#84cc16', '#22c55e', '#ef4444', '#6b7280'],
                borderWidth: 0
              }]"
            />
          </template>
        </div>

        <!-- Payment Status Distribution (Doughnut Chart) -->
        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm flex flex-col space-y-4">
          <template v-if="!loading">
            <div class="pb-4 border-b border-slate-50">
              <h2 class="text-[15.4px] font-extrabold text-slate-800 flex items-center gap-2">
                <CreditCardIcon class="w-5 h-5 text-indigo-500" />
                Payment Status Distribution
              </h2>
              <p class="text-[11.5px] text-slate-500 mt-1 pl-7">Breakdown of orders by payment processing status.</p>
            </div>
            <SalesChart 
              heightClass="h-72"
              type="doughnut"
              :labels="reportData?.charts?.payment_status?.map((p: any) => p.label) || []"
              :datasets="[{
                label: 'Orders',
                data: reportData?.charts?.payment_status?.map((p: any) => p.value) || [],
                backgroundColor: ['#eab308', '#22c55e', '#ef4444'],
                borderWidth: 0
              }]"
            />
          </template>
        </div>
      </div>

      <!-- Standard DataTable Component Matching User Screenshot -->
      <DataTable
        :loading="loading || tableLoading"
        title="Sales Records"
        subtitle="List of all orders within the selected filter range."
        :columns="columns"
        :data="reportData?.table?.data || []"

        entityName="sales records"
        emptyMessage="No sales records found."
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

        <!-- ORDERED DATE Column -->
        <template #col_order_date="{ item }">
          <span class="text-[9.9px] font-semibold text-slate-500 whitespace-nowrap">{{ item.order_date }}</span>
        </template>

        <!-- ORDER NUMBER Column -->
        <template #col_order_number="{ item }">
          <span class="font-bold text-slate-900 text-[9.9px]">{{ item.order_number }}</span>
        </template>

        <!-- CUSTOMER Column -->
        <template #col_customer="{ item }">
          <span class="font-bold text-slate-700 text-[9.9px]">{{ item.customer }}</span>
        </template>

        <!-- STATUS Column (Uppercase Pill Badge) -->
        <template #col_order_status="{ item }">
          <span 
            class="inline-block px-2 py-0.5 text-[8.7px] font-extrabold rounded-full uppercase tracking-wider border" 
            :class="orderStatusBadge(item.order_status)"
          >
            {{ item.order_status }}
          </span>
        </template>

        <!-- PAYMENT STATUS Column (Uppercase Pill Badge) -->
        <template #col_payment_status="{ item }">
          <span 
            class="inline-block px-2 py-0.5 text-[8.7px] font-extrabold rounded-full uppercase tracking-wider border" 
            :class="paymentStatusBadge(item.payment_status)"
          >
            {{ item.payment_status }}
          </span>
        </template>

        <!-- PAYMENT METHOD Column -->
        <template #col_payment_method="{ item }">
          <span class="text-[9.9px] text-slate-600 font-medium">{{ item.payment_method || '—' }}</span>
        </template>

        <!-- ITEMS Column -->
        <template #col_total_items="{ item }">
          <span class="font-bold text-slate-800 text-[9.9px]">{{ item.total_items }}</span>
        </template>

        <!-- SUBTOTAL Column -->
        <template #col_subtotal="{ item }">
          <span class="font-bold text-slate-800 text-[11.5px]">{{ formatPrice(item.subtotal) }}</span>
        </template>

        <!-- DISCOUNT Column -->
        <template #col_discount="{ item }">
          <span class="text-red-500 text-[11.5px] font-medium" v-if="item.discount > 0">-{{ formatPrice(item.discount) }}</span>
          <span class="text-slate-400 text-[11.5px]" v-else>—</span>
        </template>

        <!-- TOTAL PRICE Column -->
        <template #col_total_amount="{ item }">
          <span class="font-extrabold text-slate-900 text-[11.5px]">{{ formatPrice(item.total_amount) }}</span>
        </template>

        <!-- ACTIONS Column (Eye button linking to Order Detail) -->
        <template #col_actions="{ item }">
          <NuxtLink 
            :to="`/orders/${item.id}`"
            class="w-8 h-8 rounded-full bg-slate-100 hover:bg-blue-50 hover:text-blue-600 active:bg-blue-100 flex items-center justify-center text-slate-500 transition-all border border-slate-200/60 shadow-2xs mx-auto"
            title="View Order Details"
          >
            <EyeIcon class="w-4 h-4" />
          </NuxtLink>
        </template>
      </DataTable>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive, watch, computed } from 'vue'
import { ChevronDownIcon,ShoppingBagIcon,DollarSignIcon, PackageIcon, TrendingUpIcon, DownloadIcon, Loader2Icon, AlertCircleIcon, EyeIcon, ClockIcon, CreditCardIcon, MapPinIcon } from '@lucide/vue'
import { ReportService } from '~/services/report.service'
import { useSelectFetch } from '~/composables/useSelectFetch'
import { CAMBODIA_PROVINCES } from '~/utils/constants'

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
const sortBy = ref('order_date')
const sortDesc = ref(true)

watch(perPage, () => { fetchReport(1, true) })

const columns: Column[] = [
  { key: 'order_date', label: 'ORDERED DATE', sortable: true },
  { key: 'order_number', label: 'ORDER NUMBER', sortable: true },
  { key: 'customer', label: 'CUSTOMER', sortable: true },
  { key: 'order_status', label: 'STATUS', align: 'center'},
  { key: 'payment_status', label: 'PAYMENT STATUS', align: 'center'},
  { key: 'payment_method', label: 'PAYMENT METHOD', align: 'center' },
  { key: 'total_items', label: 'ITEMS', align: 'center', sortable: true },
  { key: 'subtotal', label: 'SUBTOTAL', align: 'right', sortable: true },
  { key: 'discount', label: 'DISCOUNT', align: 'right', sortable: true },
  { key: 'total_amount', label: 'TOTAL PRICE', align: 'right', sortable: true },
  { key: 'actions', label: 'ACTIONS', align: 'center' },
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
  customer_id: '',
  order_id: '',
  status: '',
  payment_status: '',
  payment_method: '',
  province: '',
})

const dayCount = computed(() => {
  return reportData.value?.charts?.daily_sales?.length || 0
})

const { selectOptions: orderOptions, isLoading: isOrdersLoading, fetchOptions: fetchOrderOptions } = useSelectFetch({
  endpoint: '/admin/orders',
  buildLabel: (o) => o.order_number || ('#' + o.id),
})

const { selectOptions: customerOptions, isLoading: isCustomersLoading, fetchOptions: fetchCustomerOptions } = useSelectFetch({
  endpoint: '/admin/customers',
  staticParams: { status: 'all' },
  buildLabel: (c) => {
    let name = c.name;
    if (c.status === 'deleted') name += ' (Deleted)';
    else if (c.status === 'inactive') name += ' (Inactive)';
    return name;
  },
})






function orderStatusBadge(status: string) {
  const s = status?.toUpperCase()
  if (s === 'PENDING') return 'bg-yellow-100 text-yellow-800 border-yellow-200'
  if (s === 'CONFIRMED') return 'bg-blue-100 text-blue-800 border-blue-200'
  if (s === 'PROCESSING') return 'bg-purple-100 text-purple-800 border-purple-200'
  if (s === 'SHIPPED') return 'bg-indigo-100 text-indigo-800 border-indigo-200'
  if (s === 'DELIVERED') return 'bg-lime-100 text-lime-800 border-lime-200'
  if (s === 'COMPLETED') return 'bg-green-100 text-green-800 border-green-200'
  if (s === 'CANCELLED') return 'bg-red-100 text-red-800 border-red-200'
  if (s === 'RETURNED') return 'bg-gray-100 text-gray-800 border-gray-200'
  return 'bg-slate-100 text-slate-800 border-slate-200'
}

function paymentStatusBadge(status: string) {
  const s = status?.toUpperCase()
  if (s === 'UNPAID') return 'bg-yellow-100 text-yellow-800 border-yellow-200'
  if (s === 'PAID') return 'bg-green-100 text-green-800 border-green-200'
  if (s === 'REFUNDED') return 'bg-red-100 text-red-800 border-red-200'
  return 'bg-slate-100 text-slate-800 border-slate-200'
}

function resetFilters() {
  const defaults = getDefaultDates()
  filters.start_date = defaults.start_date
  filters.end_date = defaults.end_date
  filters.customer_id = ''
  filters.order_id = ''
  filters.status = ''
  filters.payment_status = ''
  filters.payment_method = ''
  filters.province = ''
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
  if (filters.customer_id) queryParams.set('customer_id', filters.customer_id)
  if (filters.order_id) queryParams.set('order_id', filters.order_id)
  if (filters.status) queryParams.set('status', filters.status)
  if (filters.payment_status) queryParams.set('payment_status', filters.payment_status)
  if (filters.payment_method) queryParams.set('payment_method', filters.payment_method)
  if (filters.province) queryParams.set('province', filters.province)
  if (sortBy.value) queryParams.set('sort_by', sortBy.value)
  if (sortDesc.value !== undefined) queryParams.set('sort_dir', sortDesc.value ? 'desc' : 'asc')

  try {
    const res: any = await ReportService.getSales(queryParams.toString())
    reportData.value = res
  } catch (err: any) {
    error.value = err.message || 'Failed to load sales report.'
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
    if (filters.customer_id) queryParams.set('customer_id', filters.customer_id)
    if (filters.order_id) queryParams.set('order_id', filters.order_id)
    if (filters.status) queryParams.set('status', filters.status)
    if (filters.payment_status) queryParams.set('payment_status', filters.payment_status)
    if (filters.payment_method) queryParams.set('payment_method', filters.payment_method)
    if (filters.province) queryParams.set('province', filters.province)
    if (sortBy.value) queryParams.set('sort_by', sortBy.value)
    if (sortDesc.value !== undefined) queryParams.set('sort_dir', sortDesc.value ? 'desc' : 'asc')

    const res: any = await ReportService.exportSales(queryParams.toString())

    if (res instanceof Blob && res.type.includes('application/json')) {
      const text = await res.text()
      try {
        const json = JSON.parse(text)
        alert(json.message || 'No sales data found for selected filters.')
        return
      } catch (e) {}
    }

    const todayStr = new Date().toISOString().split('T')[0]
    const filename = `Sales_Report_${todayStr}.xlsx`
    
    const url = window.URL.createObjectURL(new Blob([res as Blob]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (err: any) {
    alert(err.message || 'No sales data found for selected filters.')
  } finally {
    exportingExcel.value = false
  }
}


onMounted(() => {
  fetchReport(1)
})
</script>
