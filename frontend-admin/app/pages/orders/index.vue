<template>
  <div class="space-y-2 w-full mx-auto font-sans antialiased text-slate-800">
    <DataTable
      title="Order Management"
      subtitle="Review orders status and update shipping progress."
      :columns="columns"
      :data="orders"
      :loading="isLoading"
      entityName="orders"
      emptyMessage="No orders found."
      v-model:search="search"
      searchPlaceholder="Search order #..."
      :hasActiveFilters="
        !!statusFilter ||
        !!paymentStatusFilter ||
        !!paymentMethodFilter ||
        !!customerFilter ||
        !!startDateFilter ||
        !!endDateFilter ||
        !!provinceFilter
      "
      :pagination="paginationData"
      v-model:sort-by="sortBy"
      v-model:sort-desc="sortDesc"
      v-model:per-page="perPage"
      @page-change="handlePageChange"
      @reset="resetFilters"
    >
      <template #header-actions>
        <div class="flex items-center gap-2">
          <IconButton
            color="green"
            title="Export Excel"
            label="Export Excel"
            :disabled="exportingExcel || isLoading"
            :loading="exportingExcel"
            @click="exportExcel"
          >
            <DownloadIcon class="w-4 h-4" />
          </IconButton>
          <IconButton
            color="blue"
            label="Create Order"
            :disabled="exportingExcel || isLoading"
            @click="$router.push('/orders/create')"
          >
            <PlusIcon class="w-4 h-4" />
          </IconButton>
        </div>
      </template>

      <template #toolbar>
        <div>
          <label
            class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1"
            >Customer</label
          >
          <SearchableSelect
            v-model="customerFilter"
            :options="customersList"
            placeholder="All Customers"
            searchPlaceholder="Search customers..."
            clearLabel="All Customers"
            :serverSearch="true"
            :isLoading="isSearchingCustomers"
            @search="fetchCustomers"
          />
        </div>

        <div>
          <label
            class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1"
            >Order Status</label
          >
          <SearchableSelect
            v-model="statusFilter"
            :options="
              statusStats.map((st) => ({
                label: `${st.label}`,
                value: st.status,
              }))
            "
            clearLabel="All Statuses"
            searchPlaceholder="Search order statuses..."
          />
        </div>

        <div>
          <label
            class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1"
            >Payment Status</label
          >
          <SearchableSelect
            v-model="paymentStatusFilter"
            :options="[
              { label: 'Unpaid', value: 'unpaid' },
              { label: 'Paid', value: 'paid' },
              { label: 'Refunded', value: 'refunded' },
            ]"
            clearLabel="All Payment Statuses"
            searchPlaceholder="Search payment statuses..."
          />
        </div>

        <div>
          <label
            class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1"
            >Payment Method</label
          >
          <SearchableSelect
            v-model="paymentMethodFilter"
            :options="[
              { label: 'Cash', value: 'cash' },
              { label: 'Bank', value: 'bank' },
              { label: 'None / Blank', value: 'blank' },
            ]"
            clearLabel="All Payment Methods"
            searchPlaceholder="Search payment methods..."
          />
        </div>

        <div class="min-w-[140px]">
          <label
            class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1"
            >Province</label
          >
          <SearchableSelect
            v-model="provinceFilter"
            :options="CAMBODIA_PROVINCES"
            clearLabel="All Provinces"
            searchPlaceholder="Search provinces..."
          />
        </div>

        <div>
          <label
            class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1"
            >Date Range</label
          >
          <div
            class="flex items-center bg-slate-50 border border-slate-200 rounded-xl px-3 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all"
          >
            <input
              type="date"
              v-model="startDateFilter"
              class="bg-transparent py-1.5 px-1 text-slate-800 text-[11.5px] focus:outline-none cursor-pointer"
            />
            <span class="text-slate-300 text-[11.5px] mx-1">-</span>
            <input
              type="date"
              v-model="endDateFilter"
              class="bg-transparent py-1.5 px-1 text-slate-800 text-[11.5px] focus:outline-none cursor-pointer"
            />
          </div>
        </div>
      </template>

      <template #col_order_number="{ item }">
        <span class="font-extrabold text-slate-800"
          >#{{ item.order_number }}</span
        >
      </template>

      <template #col_customer="{ item }">
        <span class="font-semibold text-slate-700">
          {{ (item as any).user?.name || "—" }}
          <span
            v-if="(item as any).user?.deleted_at"
            class="text-rose-500 font-bold ml-1"
            >(Deleted)</span
          >
          <span
            v-else-if="(item as any).user && !(item as any).user?.is_active"
            class="text-amber-500 font-bold ml-1"
            >(Inactive)</span
          >
        </span>
      </template>

      <template #col_status="{ item }">
        <span
          class="inline-flex items-center px-1.5 py-px rounded-full text-[8.2px] font-bold uppercase tracking-wider border"
          :class="[
            item.status === 'pending'
              ? 'bg-yellow-100 text-yellow-800 border-yellow-200'
              : '',
            item.status === 'confirmed'
              ? 'bg-blue-100 text-blue-800 border-blue-200'
              : '',
            item.status === 'processing'
              ? 'bg-purple-100 text-purple-800 border-purple-200'
              : '',
            item.status === 'shipped'
              ? 'bg-indigo-100 text-indigo-800 border-indigo-200'
              : '',
            item.status === 'delivered'
              ? 'bg-lime-100 text-lime-800 border-lime-200'
              : '',
            item.status === 'completed'
              ? 'bg-green-100 text-green-800 border-green-200'
              : '',
            item.status === 'cancelled'
              ? 'bg-red-100 text-red-800 border-red-200'
              : '',
            item.status === 'returned'
              ? 'bg-gray-100 text-gray-800 border-gray-200'
              : '',
          ]"
        >
          {{ item.status }}
        </span>
      </template>

      <template #col_payment_status="{ item }">
        <span
          class="inline-flex items-center px-1.5 py-px rounded-full text-[8.2px] font-bold uppercase tracking-wider border"
          :class="[
            (item as any).payment_status === 'unpaid'
              ? 'bg-yellow-100 text-yellow-800 border-yellow-200'
              : '',
            (item as any).payment_status === 'paid'
              ? 'bg-green-100 text-green-800 border-green-200'
              : '',
            (item as any).payment_status === 'refunded'
              ? 'bg-red-100 text-red-800 border-red-200'
              : '',
          ]"
        >
          {{ (item as any).payment_status }}
        </span>
      </template>

      <template #col_payment_method="{ item }">
        <span class="capitalize text-slate-600 font-medium">{{
          (item as any).payment_method || "—"
        }}</span>
      </template>

      <template #col_items="{ item }">
        <span class="font-medium text-slate-600">{{
          (item as any).items_count ?? (item as any).items?.length ?? 0
        }}</span>
      </template>

      <template #col_subtotal="{ item }">
        <span class="font-black text-slate-800">{{
          formatPrice(item.subtotal)
        }}</span>
      </template>

      <template #col_discount="{ item }">
        <span
          v-if="Number((item as any).discount) > 0"
          class="font-medium text-red-500"
          >-{{ formatPrice((item as any).discount) }}</span
        >
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #col_total="{ item }">
        <span class="font-black text-slate-800">{{
          formatPrice(item.total)
        }}</span>
      </template>

      <template #col_created_at="{ item }">
        <span class="text-slate-450 font-medium whitespace-nowrap">{{
          formatDate(item.created_at)
        }}</span>
      </template>

      <template #col_actions="{ item }">
        <div class="flex items-center gap-2 justify-center">
          <IconButton
            @click="openOrder(item)"
            title="View Details"
            color="slate"
          >
            <EyeIcon class="w-4 h-4" />
          </IconButton>
          <IconButton
            @click="openEditForm(item as any)"
            title="Edit Order"
            color="blue"
          >
            <Edit3Icon class="w-4 h-4" />
          </IconButton>
          <a
            v-if="
              (item as any).address?.latitude &&
              (item as any).address?.longitude
            "
            :href="`https://www.google.com/maps?q=${(item as any).address.latitude},${(item as any).address.longitude}`"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 transition-colors"
            title="View on Google Maps"
          >
            <MapPinIcon class="w-4 h-4" />
          </a>
        </div>
      </template>
    </DataTable>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useRouter } from "nuxt/app";
import type { ApiOrder } from "@/types/api";
import { useDataTable } from "~/composables/useDataTable";
import { useSelectFetch } from "~/composables/useSelectFetch";
import { OrderService } from "~/services/order.service";
import {
  EyeIcon,
  PlusIcon,
  Edit3Icon,
  MapPinIcon,
  DownloadIcon,
} from "@lucide/vue";

import SearchableSelect from "~/components/ui/SearchableSelect.vue";
import { CAMBODIA_PROVINCES } from "~/utils/constants";

definePageMeta({ layout: "default", middleware: "admin" });

const columns = [
  { key: "created_at", label: "Ordered Date", sortable: true },
  { key: "order_number", label: "Order Number", sortable: true },
  { key: "customer", label: "Customer", sortable: true },
  { key: "status", label: "Status" },
  { key: "payment_status", label: "Payment Status" },
  { key: "payment_method", label: "Payment Method" },
  { key: "items", label: "Items", align: "right" as const, sortable: false },
  {
    key: "subtotal",
    label: "Subtotal",
    align: "right" as const,
    sortable: false,
  },
  {
    key: "discount",
    label: "Discount",
    align: "right" as const,
    sortable: false,
  },
  {
    key: "total",
    label: "Total Price",
    align: "right" as const,
    sortable: true,
  },
  { key: "actions", label: "Actions", align: "center" as const },
];

const router = useRouter();

// ── Filter refs ──────────────────────────────────────────────
const statusFilter = ref("");
const paymentStatusFilter = ref("");
const paymentMethodFilter = ref("");
const customerFilter = ref<number | "">("");
const provinceFilter = ref("");
const startDateFilter = ref("");
const endDateFilter = ref("");

// ── Table data ───────────────────────────────────────────────
const {
  items: orders,
  loading: isLoading,
  search,
  sortBy,
  sortDesc,
  perPage,
  paginationData,
  extra,
  fetch: fetchOrders,
  handleSearch,
  handlePageChange,
  resetFilters: resetTableFilters,
} = useDataTable({
  endpoint: "/admin/orders",
  perPage: 15,
  filters: {
    status: statusFilter,
    payment_status: paymentStatusFilter,
    payment_method: paymentMethodFilter,
    customer_id: customerFilter,
    province: provinceFilter,
    start_date: startDateFilter,
    end_date: endDateFilter,
  },
  extractExtra: (res) => ({
    status_counts: res?.data?.status_counts || res?.status_counts || {},
  }),
});

// ── Customer dropdown (server-side search) ───────────────────
const {
  selectOptions: customersList,
  isLoading: isSearchingCustomers,
  fetchOptions: fetchCustomers,
} = useSelectFetch({
  endpoint: "/admin/customers",
  staticParams: { status: "all" },
  buildLabel: (c) =>
    c.status === "deleted"
      ? `${c.name} (Deleted)`
      : c.status === "inactive"
        ? `${c.name} (Inactive)`
        : c.name,
});

// ── Status stats (derived from extra) ────────────────────────
const rawStatusCounts = computed(() => extra.value.status_counts || {});

const statusStats = computed(() => {
  const statuses = [
    "pending",
    "confirmed",
    "processing",
    "shipped",
    "delivered",
    "completed",
    "cancelled",
    "returned",
  ];
  const labels: Record<string, string> = {
    pending: "Pending",
    confirmed: "Confirmed",
    processing: "Processing",
    shipped: "Shipped",
    delivered: "Delivered",
    completed: "Completed",
    cancelled: "Cancelled",
    returned: "Returned",
  };
  return statuses.map((s) => ({ status: s, label: labels[s] }));
});

// ── Reset all filters ─────────────────────────────────────────
function resetFilters() {
  resetTableFilters({
    status: "",
    payment_status: "",
    payment_method: "",
    customer_id: "",
    province: "",
    start_date: "",
    end_date: "",
  });
}

function openOrder(order: ApiOrder) {
  router.push(`/orders/${order.id}`);
}
function openEditForm(order: ApiOrder) {
  router.push(`/orders/${order.id}/edit`);
}

const exportingExcel = ref(false);
async function exportExcel() {
  exportingExcel.value = true;
  try {
    const params: Record<string, string> = { export: "excel" };
    if (statusFilter.value) params.status = statusFilter.value;
    if (paymentStatusFilter.value)
      params.payment_status = paymentStatusFilter.value;
    if (paymentMethodFilter.value)
      params.payment_method = paymentMethodFilter.value;
    if (customerFilter.value) params.customer_id = String(customerFilter.value);
    if (provinceFilter.value) params.province = provinceFilter.value;
    if (startDateFilter.value) params.start_date = startDateFilter.value;
    if (endDateFilter.value) params.end_date = endDateFilter.value;
    if (search.value) params.search = search.value;
    if (sortBy.value) params.sort_by = sortBy.value;
    if (sortDesc.value !== undefined)
      params.sort_desc = sortDesc.value ? "true" : "false";

    const res = await OrderService.export(
      new URLSearchParams(params).toString(),
    );

    const url = window.URL.createObjectURL(new Blob([res as any]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute(
      "download",
      `Orders_Report_${new Date().toISOString().split("T")[0]}.xlsx`,
    );
    document.body.appendChild(link);
    link.click();
    link.parentNode?.removeChild(link);
  } catch (error: any) {
    console.error("Export failed", error);
  } finally {
    exportingExcel.value = false;
  }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
