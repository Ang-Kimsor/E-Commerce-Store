<template>
  <div class="space-y-6 w-full pb-10 font-sans antialiased text-slate-800 print-container">
    <!-- Header with Common Tabs Navigation -->
    <div class="space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-100">
        <div>
          <h1 class="text-[18.8px] font-extrabold text-slate-800 tracking-tight">Customer Report</h1>
          <p class="text-[11.5px] text-slate-500 mt-0.5">Analyze customer purchasing activities, retention, lifetime value, and growth.</p>
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

      <!-- Customer Status Filter -->
      <div class="shrink-0">
        <label class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1">Customer Status</label>
        <SearchableSelect
          v-model="filters.customer_status"
          :options="[
            { label: 'Active', value: 'active' },
            { label: 'Inactive', value: 'inactive' },
            { label: 'Deleted', value: 'deleted' }
          ]"
          placeholder="All Statuses"
          clearLabel="All Statuses"
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
        <label class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1">Search Customer / Email</label>
        <div class="relative">
          <SearchIcon class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          <input 
            type="text" 
            v-model="filters.search" 
            placeholder="Search name, email, or phone..."
            class="w-full pl-9 pr-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-[11.5px] outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all text-slate-800"
          />
        </div>
      </div>
    </ReportFilterBar>

    <!-- Page Level Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center min-h-[400px] bg-white border border-slate-100 rounded-3xl p-12 space-y-3 text-slate-400 shadow-sm">
      <Loader2Icon class="w-10 h-10 animate-spin text-blue-600 mb-1" />
      <p class="text-[11.5px] font-bold text-slate-600 uppercase tracking-wider">Loading customer report...</p>
    </div>

    <div v-else class="space-y-6">
      <!-- Summary Cards Grid (Existing KpiCard Component) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <KpiCard
          label="Total Customers"
          :value="reportData?.summary?.total_customers ?? '—'"
          :icon="UsersIcon"
          bgClass="bg-blue-50 border-blue-100 text-blue-600"
        />
        <KpiCard
          label="Active Customers"
          :value="reportData?.summary?.active_customers ?? '—'"
          :icon="UserCheckIcon"
          bgClass="bg-emerald-50 border-emerald-100 text-emerald-600"
        />
        <KpiCard
          label="Inactive Customers"
          :value="reportData?.summary?.inactive_customers ?? '—'"
          :icon="UserMinusIcon"
          bgClass="bg-slate-50 border-slate-200 text-slate-600"
        />
        <KpiCard
          label="Deleted Customers"
          :value="reportData?.summary?.deleted_customers ?? '—'"
          :icon="Trash2Icon"
          bgClass="bg-rose-50 border-rose-100 text-rose-600"
        />
        <KpiCard
          label="Customers With Orders"
          :value="reportData?.summary?.customers_with_orders ?? '—'"
          :icon="ShoppingBagIcon"
          bgClass="bg-purple-50 border-purple-100 text-purple-600"
        />
        <KpiCard
          label="Customer Spending"
          :value="reportData?.summary?.revenue !== undefined ? formatPrice(reportData.summary.revenue) : '—'"
          :icon="DollarSignIcon"
          bgClass="bg-indigo-50 border-indigo-100 text-indigo-600"
        />
        <KpiCard
          label="AVG Spending/Customer"
          :value="reportData?.summary?.avg_spending !== undefined ? formatPrice(reportData.summary.avg_spending) : '—'"
          :icon="TrendingUpIcon"
          bgClass="bg-amber-50 border-amber-100 text-amber-600"
        />
      </div>

      <!-- Charts Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        <!-- 1. Customer Growth Chart -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
          <div class="mb-4">
            <h2 class="text-[15.4px] font-bold text-slate-800 flex items-center gap-2">
              <TrendingUpIcon class="w-5 h-5 text-indigo-500" />
              Customer Growth
            </h2>
            <p class="text-[11.5px] text-slate-500 mt-1 pl-7">New customers registered over time.</p>
          </div>
          <SalesChart 
            heightClass="h-64"
            type="line"
            :labels="reportData?.charts?.customer_growth?.map((cg: any) => cg.label) || []"
            :datasets="[{
              label: 'New Customers',
              data: reportData?.charts?.customer_growth?.map((cg: any) => cg.value) || [],
              borderColor: '#6366f1',
              backgroundColor: 'rgba(99, 102, 241, 0.1)',
              fill: true,
              tension: 0.4
            }]"
            formatType="number"
          />
        </div>

        <!-- 2. Top Customers by Spending Chart -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
          <div class="mb-4">
            <h2 class="text-[15.4px] font-bold text-slate-800 flex items-center gap-2">
              <DollarSignIcon class="w-5 h-5 text-blue-500" />
              Top Customers by Spending
            </h2>
            <p class="text-[11.5px] text-slate-500 mt-1 pl-7">Highest-value customers by total spent.</p>
          </div>
          <SalesChart 
            heightClass="h-64"
            type="bar"
            :labels="reportData?.charts?.top_spending?.map((c: any) => c.label) || []"
            :datasets="[{
              label: 'Total Spent',
              data: reportData?.charts?.top_spending?.map((c: any) => c.value) || [],
              backgroundColor: '#3b82f6',
              borderRadius: 4
            }]"
          />
        </div>

        <!-- 3. Top Customers by Orders Chart -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
          <div class="mb-4">
            <h2 class="text-[15.4px] font-bold text-slate-800 flex items-center gap-2">
              <ShoppingBagIcon class="w-5 h-5 text-emerald-500" />
              Top Customers by Orders
            </h2>
            <p class="text-[11.5px] text-slate-500 mt-1 pl-7">Most frequent customers by order count.</p>
          </div>
          <SalesChart 
            heightClass="h-64"
            type="bar"
            :labels="reportData?.charts?.top_orders?.map((c: any) => c.label) || []"
            :datasets="[{
              label: 'Orders',
              data: reportData?.charts?.top_orders?.map((c: any) => c.value) || [],
              backgroundColor: '#10b981',
              borderRadius: 4
            }]"
          />
        </div>


      </div>

      <!-- Standard DataTable Component -->
      <DataTable
        :loading="loading || tableLoading"
        title="Customer Purchase Records"
        subtitle="Individual customer ordering activity, volume, and total spend."
        :columns="columns"
        :data="reportData?.table?.data || []"

        entityName="customer records"
        emptyMessage="No customers found."
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

        <template #col_registered_date="{ item }">
          <span class="text-[9.9px] font-medium text-slate-500 whitespace-nowrap">{{ item.registered_date }}</span>
        </template>

        <template #col_customer="{ item }">
          <span class="font-bold text-slate-800">{{ item.customer }}</span>
        </template>

        <template #col_phone="{ item }">
          <span class="text-[9.9px] font-mono text-slate-500">{{ item.phone }}</span>
        </template>

        <template #col_orders="{ item }">
          <span class="font-bold text-slate-700">{{ item.orders }}</span>
        </template>

        <template #col_completed_orders="{ item }">
          <span class="font-bold text-blue-600">{{ item.completed_orders }}</span>
        </template>

        <template #col_total_spent="{ item }">
          <span class="font-extrabold text-slate-900">{{ formatPrice(item.total_spent) }}</span>
        </template>

        <template #col_avg_order_value="{ item }">
          <span class="font-bold text-slate-800">{{ formatPrice(item.avg_order_value) }}</span>
        </template>

        <template #col_last_purchase="{ item }">
          <span class="font-bold text-slate-800">{{ item.last_purchase }}</span>
        </template>

        <template #col_status="{ item }">
          <span 
            class="px-2.5 py-1 text-[8.7px] font-bold rounded-full border uppercase tracking-wider"
            :class="customerBadge(item.status)"
          >
            {{ item.status }}
          </span>
        </template>

        <template #col_actions="{ item }">
          <IconButton 
            color="slate" 
            title="View customer" 
            @click="navigateTo(`/customers/${item.id}`)"
          >
            <EyeIcon class="w-4 h-4" />
          </IconButton>
        </template>
      </DataTable>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive, watch } from 'vue'
import { 
  AlertCircleIcon, 
  UsersIcon, 
  UserCheckIcon, 
  UserMinusIcon,
  ShoppingBagIcon,
  DollarSignIcon, 
  TrendingUpIcon, 
  SearchIcon,
  DownloadIcon,
  Loader2Icon,
  ChevronDownIcon,
  EyeIcon,
  Trash2Icon
} from '@lucide/vue'
import { ReportService } from '~/services/report.service'

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
const sortBy = ref('total_spent')
const sortDesc = ref(true)

watch(perPage, () => { fetchReport(1, true) })

const columns: Column[] = [
  { key: 'registered_date', label: 'Registered Date', sortable: true },
  { key: 'customer', label: 'Customer', sortable: true },
  { key: 'phone', label: 'Phone', sortable: true },
  { key: 'orders', label: 'Orders', align: 'center', sortable: true },
  { key: 'completed_orders', label: 'Completed Orders', align: 'center', sortable: true },
  { key: 'total_spent', label: 'Total Spent', align: 'right', sortable: true },
  { key: 'avg_order_value', label: 'Avg. Order Value', align: 'right', sortable: true },
  { key: 'last_purchase', label: 'Last Order', align: 'center', sortable: true },
  { key: 'status', label: 'Status', align: 'center' },
  { key: 'actions', label: 'Action', align: 'center', sortable: false },]

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
  search: '',
  customer_status: '',
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





function customerBadge(status: string) {
  if (status === 'Active') return 'bg-emerald-50 text-emerald-700 border-emerald-200'
  if (status === 'Deleted') return 'bg-rose-50 text-rose-700 border-rose-200'
  return 'bg-slate-100 text-slate-600 border-slate-200'
}

function resetFilters() {
  const defaults = getDefaultDates()
  filters.start_date = defaults.start_date
  filters.end_date = defaults.end_date
  filters.customer_id = ''
  filters.search = ''
  filters.customer_status = ''
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
  if (filters.search) queryParams.set('search', filters.search)
  if (filters.customer_status) queryParams.set('status', filters.customer_status)
  if (sortBy.value) queryParams.set('sort_by', sortBy.value)
  if (sortDesc.value !== undefined) queryParams.set('sort_dir', sortDesc.value ? 'desc' : 'asc')

  try {
    const res: any = await ReportService.getCustomers(queryParams.toString())
    reportData.value = res
  } catch (err: any) {
    error.value = err.message || 'Failed to load customer report.'
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
    if (filters.search) queryParams.set('search', filters.search)
    if (filters.customer_status) queryParams.set('status', filters.customer_status)
    if (sortBy.value) queryParams.set('sort_by', sortBy.value)
    if (sortDesc.value !== undefined) queryParams.set('sort_dir', sortDesc.value ? 'desc' : 'asc')

    const res = await ReportService.exportCustomers(queryParams.toString())

    const parts = [
      'customer_report_',
      filters.customer_status,
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
