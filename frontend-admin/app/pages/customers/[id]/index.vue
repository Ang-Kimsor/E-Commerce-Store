<template>
  <div class="w-full font-sans antialiased text-slate-800">
    <!-- Main Card -->
    <div
      class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 relative min-h-[400px] flex flex-col"
    >
      <!-- Card Header -->
      <div
        class="flex flex-col md:flex-row md:items-center justify-between gap-4 -mt-2 mb-6"
      >
        <div class="flex items-center gap-3">
          <NuxtLink
            to="/customers"
            class="inline-flex items-center gap-2 text-[9.7px] font-bold text-slate-500 hover:text-slate-800 transition-colors"
          >
            <ArrowLeftIcon class="w-4 h-4" />
            Back to Customers
          </NuxtLink>
        </div>
        <div v-if="!loading" class="flex items-center gap-2">
          <IconButton
            color="green"
            title="Export Excel"
            label="Export Excel"
            :disabled="exportingExcel"
            :loading="exportingExcel"
            @click="exportExcel"
          >
            <DownloadIcon class="w-4 h-4" />
          </IconButton>
          <div class="h-4 w-px bg-slate-200 mx-1"></div>
          <IconButton color="blue" title="Refresh" @click="loadCustomer">
            <RefreshCwIcon class="w-4 h-4" />
            <span class="text-[9.7px] font-bold ml-1">Refresh</span>
          </IconButton>
          <div class="h-4 w-px bg-slate-200 mx-1"></div>

          <IconButton
            color="blue"
            title="Edit Customer"
            @click="$router.push(`/customers/${customer?.id}/edit`)"
          >
            <Edit3Icon class="w-4 h-4" />
            <span class="text-[9.7px] font-bold ml-1">Edit</span>
          </IconButton>
          <IconButton
            v-if="customer?.status === 'active'"
            color="orange"
            title="Block Customer"
            @click="blockCustomer"
          >
            <UserXIcon class="w-4 h-4" />
            <span class="text-[9.7px] font-bold ml-1">Block</span>
          </IconButton>
          <IconButton
            v-if="customer?.status === 'inactive'"
            color="green"
            title="Unblock Customer"
            @click="unblockCustomer"
          >
            <CheckCircleIcon class="w-4 h-4" />
            <span class="text-[9.7px] font-bold ml-1">Unblock</span>
          </IconButton>
        </div>
      </div>

      <!-- Loading State -->
      <div
        v-if="loading"
        class="flex-1 flex flex-col items-center justify-center min-h-[300px]"
      >
        <Loader2Icon class="w-8 h-8 animate-spin text-blue-600 mb-4" />
        <span class="text-[8.7px] font-bold text-slate-500"
          >Loading profile...</span
        >
      </div>

      <!-- Error State -->
      <div
        v-else-if="!loading && !customer"
        class="flex flex-col items-center justify-center py-20"
      >
        <UserIcon class="w-12 h-12 text-slate-200 mb-3" />
        <h3 class="text-[15.4px] font-bold text-slate-800">
          Customer Not Found
        </h3>
        <p class="text-[9.9px] text-slate-500 mt-1">
          Unable to load customer details or they do not exist.
        </p>
      </div>

      <!-- Actual Content -->
      <div v-else-if="customer" class="space-y-8">
        <!-- Profile & Contact -->
        <div
          class="flex flex-col md:flex-row gap-6 items-start pb-6 border-b border-slate-100"
        >
          <img
            v-if="customer.avatar_url"
            :src="getImageUrl(customer.avatar_url)!"
            class="w-24 h-24 rounded-2xl object-cover border border-slate-200 shadow-sm shrink-0"
            alt="Avatar"
          />
          <div
            v-else
            class="w-24 h-24 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-[28.5px] shadow-sm border border-slate-200 shrink-0"
          >
            {{ customer.name ? customer.name.charAt(0).toUpperCase() : "U" }}
          </div>

          <div class="flex-1 space-y-4 pt-1">
            <div
              class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
              <div>
                <div class="flex items-center gap-3 mb-1">
                  <h1
                    class="text-[18.8px] font-black tracking-tight text-slate-800"
                  >
                    {{ customer.name || "Unknown Customer" }}
                  </h1>
                  <div
                    class="px-2 py-0.5 rounded-md text-[9.1px] font-black uppercase tracking-wider"
                    :class="[
                      customer.status === 'active'
                        ? 'bg-green-100 text-green-700'
                        : 'bg-slate-100 text-slate-700',
                    ]"
                  >
                    {{ customer.status === "inactive" ? "Blocked" : "Active" }}
                  </div>
                </div>
                <p class="text-[8.7px] font-bold text-slate-500">
                  Customer ID:
                  <span class="text-slate-700">#{{ customer.id }}</span> &bull;
                  Member since {{ formatDate(customer.created_at) }}
                </p>
              </div>
            </div>

            <!-- Contact Data -->
            <div
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-y-3 gap-x-4 text-[11.5px]"
            >
              <div class="flex items-center gap-2">
                <MailIcon class="w-4 h-4 text-slate-400 shrink-0" />
                <span
                  class="font-medium text-slate-700 truncate"
                  :title="primaryEmail"
                  >{{ primaryEmail }}</span
                >
              </div>
              <div class="flex items-center gap-2">
                <PhoneIcon class="w-4 h-4 text-slate-400 shrink-0" />
                <span class="font-medium text-slate-700">{{
                  primaryPhone
                }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-8">
          <!-- Statistics Cards -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
            <div
              class="bg-white p-5 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-center gap-1.5"
            >
              <span
                class="block text-[8.2px] font-bold uppercase text-slate-400 tracking-wider flex items-center gap-1.5"
              >
                <ShoppingBagIcon class="w-3.5 h-3.5" /> Orders
              </span>
              <span class="font-black text-slate-800 text-[18.8px]">{{
                customer.orders_count
              }}</span>
            </div>
            <div
              class="bg-white p-5 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-center gap-1.5"
            >
              <span
                class="block text-[8.2px] font-bold uppercase text-slate-400 tracking-wider flex items-center gap-1.5"
              >
                <DollarSignIcon class="w-3.5 h-3.5" /> Spent
              </span>
              <span class="font-black text-emerald-600 text-[18.8px]">{{
                formatCurrency(customer.total_spent)
              }}</span>
            </div>
            <div
              class="bg-white p-5 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-center gap-1.5"
            >
              <span
                class="block text-[8.2px] font-bold uppercase text-slate-400 tracking-wider flex items-center gap-1.5"
              >
                <PackageIcon class="w-3.5 h-3.5" /> Delivered
              </span>
              <span class="font-black text-slate-800 text-[18.8px]">{{
                customer.orders?.filter((o) => o.status === "delivered")
                  .length || 0
              }}</span>
            </div>
            <div
              class="bg-white p-5 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-center gap-1.5"
            >
              <span
                class="block text-[8.2px] font-bold uppercase text-slate-400 tracking-wider flex items-center gap-1.5"
              >
                <MapPinIcon class="w-3.5 h-3.5" /> Addresses
              </span>
              <span class="font-black text-slate-800 text-[18.8px]">{{
                customer.addresses?.length || 0
              }}</span>
            </div>
          </div>

          <!-- Saved Addresses -->
          <div
            class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 space-y-4"
          >
            <div class="flex items-center justify-between">
              <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <MapPinIcon class="w-5 h-5 text-slate-400" />
                Shipping Addresses
              </h3>
              <button
                type="button"
                @click="openAddAddress"
                class="px-3 py-1.5 text-[9.7px] font-bold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors flex items-center gap-1.5"
              >
                <PlusIcon class="w-3.5 h-3.5" />
                Add Address
              </button>
            </div>

            <div
              v-if="customer.addresses && customer.addresses.length > 0"
              class="space-y-2"
            >
              <div
                v-for="(addr, index) in customer.addresses"
                :key="addr.id || index"
                class="p-4 bg-slate-50 rounded-xl border border-slate-200 mt-2 relative transition-all"
              >
                <div
                  v-if="editingAddressId !== addr.id"
                  class="flex flex-col gap-1.5 pr-16"
                >
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-800 text-[11.5px]">{{
                      addr.label || "Unnamed Address"
                    }}</span>
                    <span
                      v-if="addr.is_default"
                      class="text-[8.2px] font-bold bg-blue-100 text-blue-700 px-1.5 py-px rounded-full uppercase tracking-wider"
                      >Default</span
                    >
                  </div>
                  <div class="text-[11.5px] font-medium text-slate-700 mt-1">
                    {{ addr.name || "No Name" }}
                    <span
                      v-if="addr.phone"
                      class="text-slate-500 font-normal ml-1"
                      >· {{ addr.phone }}</span
                    >
                  </div>
                  <div class="text-[11.5px] text-slate-500 leading-relaxed">
                    {{
                      [
                        addr.address_line_1,
                        addr.address_line_2,
                        addr.village,
                        addr.commune,
                        addr.district,
                        addr.province,
                      ]
                        .filter(Boolean)
                        .join(", ") || "No address details provided."
                    }}
                  </div>

                  <div
                    v-if="customer.status !== 'deleted'"
                    class="absolute top-4 right-4 flex items-center gap-1"
                  >
                    <IconButton
                      color="blue"
                      title="Edit Address"
                      @click="openEditAddress(addr)"
                    >
                      <Edit3Icon class="w-4 h-4" />
                    </IconButton>
                    <IconButton
                      color="red"
                      title="Delete Address"
                      @click="openDeleteAddress(addr)"
                    >
                      <TrashIcon class="w-4 h-4" />
                    </IconButton>
                  </div>
                </div>
                <div v-else class="mt-2">
                  <AddressForm
                    :address="addr"
                    :loading="isSubmitting"
                    @submit="handleAddressSubmit"
                    @cancel="editingAddressId = null"
                  />
                </div>
              </div>
            </div>

            <div
              v-if="isAddingAddress"
              class="p-4 bg-slate-50 rounded-xl border border-slate-200 mt-2"
            >
              <h3 class="text-[11.5px] font-bold text-slate-800 mb-4">
                Add New Address
              </h3>
              <AddressForm
                :address="null"
                :loading="isSubmitting"
                @submit="handleAddressSubmit"
                @cancel="isAddingAddress = false"
              />
            </div>

            <div
              v-if="
                !isAddingAddress &&
                (!customer.addresses || customer.addresses.length === 0)
              "
              class="text-center p-6 bg-slate-50 rounded-xl border border-dashed border-slate-200 text-slate-500 text-[11.5px] font-medium"
            >
              No addresses added yet. Click "Add Address" to create one.
            </div>
          </div>

          <!-- Order History -->
          <div class="pt-2">
            <DataTable
              title="Order History"
              subtitle="View all orders placed by this customer."
              :columns="orderColumns"
              :data="filteredAndSortedOrders"
              :loading="tableLoading"
              entityName="orders"
              emptyMessage="No orders found."
              v-model:search="search"
              searchPlaceholder="Search orders..."
              v-model:sort-by="sortBy"
              v-model:sort-desc="sortDesc"
              :hasActiveFilters="
                !!statusFilter ||
                !!paymentStatusFilter ||
                !!paymentMethodFilter ||
                !!startDateFilter ||
                !!endDateFilter
              "
              @reset="
                search = '';
                sortBy = 'created_at';
                sortDesc = true;
                statusFilter = '';
                paymentStatusFilter = '';
                paymentMethodFilter = '';
                startDateFilter = '';
                endDateFilter = '';
              "
            >
              <template #toolbar>
                <div>
                  <label
                    class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1"
                    >Date Range</label
                  >
                  <div
                    class="flex items-center bg-slate-50 border border-slate-200 rounded-xl px-2 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all"
                  >
                    <input
                      type="date"
                      v-model="startDateFilter"
                      class="bg-transparent py-1.5 text-slate-800 text-[11.5px] focus:outline-none cursor-pointer"
                    />
                    <span class="text-slate-300 text-[11.5px] mx-1">-</span>
                    <input
                      type="date"
                      v-model="endDateFilter"
                      class="bg-transparent py-1.5 text-slate-800 text-[11.5px] focus:outline-none cursor-pointer"
                    />
                  </div>
                </div>
                <div>
                  <label
                    class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1"
                    >Order Status</label
                  >
                  <SearchableSelect
                    v-model="statusFilter"
                    :options="[
                      { label: 'Pending', value: 'pending' },
                      { label: 'Confirmed', value: 'confirmed' },
                      { label: 'Processing', value: 'processing' },
                      { label: 'Shipped', value: 'shipped' },
                      { label: 'Delivered', value: 'delivered' },
                      { label: 'Completed', value: 'completed' },
                      { label: 'Cancelled', value: 'cancelled' },
                      { label: 'Returned', value: 'returned' },
                    ]"
                    clearLabel="All Orders"
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
                    clearLabel="All Payments"
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
                      { label: 'Bank', value: 'bank' },
                      { label: 'Cash', value: 'cash' },
                    ]"
                    clearLabel="All Methods"
                    searchPlaceholder="Search payment methods..."
                  />
                </div>
              </template>
              <template #col_order_number="{ item }">
                <span class="font-extrabold text-slate-800"
                  >#{{ item.order_number }}</span
                >
              </template>
              <template #col_created_at="{ item }">
                <span class="text-slate-450 font-medium whitespace-nowrap">{{
                  formatDateTime(item.created_at)
                }}</span>
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
              <template #col_total="{ item }">
                <span class="font-black text-slate-800">{{
                  formatCurrency(item.total)
                }}</span>
              </template>
              <template #col_actions="{ item }">
                <div class="flex items-center gap-2 justify-center">
                  <IconButton
                    @click="$router.push(`/orders/${item.id}`)"
                    title="View Details"
                    color="slate"
                  >
                    <EyeIcon class="w-4 h-4" />
                  </IconButton>
                  <IconButton
                    @click="$router.push(`/orders/${item.id}/edit`)"
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
        </div>
      </div>
    </div>

    <!-- Delete Address Modal -->
    <ConfirmModal
      v-model="isDeleteModalOpen"
      title="Delete Address"
      message="Are you sure you want to delete this address? This action cannot be undone."
      confirm-text="Delete"
      type="danger"
      :loading="isSubmitting"
      @confirm="handleAddressDelete"
      @cancel="isDeleteModalOpen = false"
    />

    <!-- Block Customer Modal -->
    <ConfirmModal
      v-model="isBlockModalOpen"
      title="Block Customer"
      message="Are you sure you want to block this customer? They will not be able to log in or place orders."
      confirmText="Block Customer"
      type="danger"
      :loading="isBlocking"
      @confirm="handleBlockConfirm"
      @cancel="isBlockModalOpen = false"
    />

    <ConfirmModal
      v-model="isUnblockModalOpen"
      title="Unblock Customer"
      message="Are you sure you want to unblock this customer? They will regain access to their account."
      confirmText="Unblock Customer"
      type="info"
      :loading="isUnblocking"
      @confirm="handleUnblockConfirm"
      @cancel="isUnblockModalOpen = false"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { CustomerService } from "~/services/customer.service";
import { useProductImage } from "~/composables/useProductImage";
const { getImageUrl } = useProductImage();
import { AddressService } from "~/services/address.service";
import type { ApiCustomerDetail } from "@/types/api";
import {
  ArrowLeftIcon,
  RefreshCwIcon,
  ShoppingBagIcon,
  DollarSignIcon,
  UserIcon,
  MailIcon,
  PhoneIcon,
  MessageSquareIcon,
  MapPinIcon,
  PackageIcon,
  EyeIcon,
  Edit3Icon,
  TrashIcon,
  PlusIcon,
  FilterIcon,
  UserXIcon,
  SendIcon,
  RefreshCcwIcon,
  Loader2Icon,
  CheckCircleIcon,
  DownloadIcon,
} from "@lucide/vue";

import IconButton from "~/components/ui/IconButton.vue";
import SearchableSelect from "~/components/ui/SearchableSelect.vue";
import ConfirmModal from "~/components/modals/ConfirmModal.vue";
import AddressForm from "~/components/forms/AddressForm.vue";

definePageMeta({
  middleware: "admin",
  layout: "default",
});

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const customer = ref<ApiCustomerDetail | null>(null);
const expandedOrderId = ref<number | null>(null);
const exportingExcel = ref(false);

async function exportExcel() {
  if (!customer.value) return;
  exportingExcel.value = true;
  try {
    const res = await CustomerService.exportDetail(route.params.id as string);
    const url = window.URL.createObjectURL(new Blob([res as any]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute(
      "download",
      `Customer_Detail_${customer.value.name.replace(/\s+/g, "_")}_${new Date().toISOString().split("T")[0]}.xlsx`,
    );
    document.body.appendChild(link);
    link.click();
    link.parentNode?.removeChild(link);
  } catch (error) {
    console.error("Failed to export customer detail", error);
  } finally {
    exportingExcel.value = false;
  }
}

const search = ref("");
const sortBy = ref("created_at");
const sortDesc = ref(true);
const startDateFilter = ref("");
const endDateFilter = ref("");
const statusFilter = ref("");
const paymentStatusFilter = ref("");
const paymentMethodFilter = ref("");

const tableLoading = ref(false);
let loadingTimeout: any = null;

watch(
  [
    search,
    sortBy,
    sortDesc,
    startDateFilter,
    endDateFilter,
    statusFilter,
    paymentStatusFilter,
    paymentMethodFilter,
  ],
  () => {
    tableLoading.value = true;
    if (loadingTimeout) clearTimeout(loadingTimeout);
    loadingTimeout = setTimeout(() => {
      tableLoading.value = false;
    }, 400);
  },
);

const orderColumns = [
  { key: "created_at", label: "Ordered Date", sortable: true },
  { key: "order_number", label: "Order Number", sortable: true },
  { key: "status", label: "Status" },
  { key: "payment_status", label: "Payment Status" },
  { key: "payment_method", label: "Payment Method" },
  { key: "items", label: "Items", align: "right" as const, sortable: true },
  { key: "total", label: "Total", align: "right" as const, sortable: true },
  { key: "actions", label: "Actions", align: "center" as const },
];

const filteredAndSortedOrders = computed(() => {
  if (!customer.value?.orders) return [];

  let result = [...customer.value.orders];

  if (search.value) {
    const q = search.value.toLowerCase();
    result = result.filter(
      (o) =>
        o.order_number?.toLowerCase().includes(q) ||
        o.status?.toLowerCase().includes(q) ||
        o.payment_status?.toLowerCase().includes(q),
    );
  }

  if (statusFilter.value) {
    result = result.filter((o) => o.status === statusFilter.value);
  }
  if (paymentStatusFilter.value) {
    result = result.filter(
      (o) => o.payment_status === paymentStatusFilter.value,
    );
  }
  if (paymentMethodFilter.value) {
    result = result.filter(
      (o) => o.payment_method === paymentMethodFilter.value,
    );
  }
  if (startDateFilter.value) {
    const start = new Date(startDateFilter.value).getTime();
    result = result.filter((o) => new Date(o.created_at).getTime() >= start);
  }
  if (endDateFilter.value) {
    const end = new Date(endDateFilter.value);
    end.setHours(23, 59, 59, 999);
    result = result.filter(
      (o) => new Date(o.created_at).getTime() <= end.getTime(),
    );
  }

  result.sort((a: any, b: any) => {
    let aVal = a[sortBy.value];
    let bVal = b[sortBy.value];

    if (sortBy.value === "items") {
      aVal = a.items_count ?? a.items?.length ?? 0;
      bVal = b.items_count ?? b.items?.length ?? 0;
    }

    if (aVal === bVal) return 0;

    if (aVal === null || aVal === undefined) return 1;
    if (bVal === null || bVal === undefined) return -1;

    const comparison = aVal < bVal ? -1 : 1;
    return sortDesc.value ? -comparison : comparison;
  });

  return result;
});

const isAddingAddress = ref(false);
const editingAddressId = ref<number | null>(null);
const isDeleteModalOpen = ref(false);
const selectedAddress = ref<any>(null);
const isSubmitting = ref(false);

const isBlockModalOpen = ref(false);
const isBlocking = ref(false);
const isUnblockModalOpen = ref(false);
const isUnblocking = ref(false);

function openAddAddress() {
  selectedAddress.value = null;
  isAddingAddress.value = true;
  editingAddressId.value = null;
}

function openEditAddress(addr: any) {
  selectedAddress.value = addr;
  editingAddressId.value = addr.id;
  isAddingAddress.value = false;
}

function openDeleteAddress(addr: any) {
  selectedAddress.value = addr;
  isDeleteModalOpen.value = true;
}

async function handleAddressSubmit(formData: any) {
  isSubmitting.value = true;
  try {
    if (editingAddressId.value) {
      const id = editingAddressId.value;
      await AddressService.update(id, formData);
    } else {
      await AddressService.create({
        ...formData,
        customer_id: customer.value?.id,
      });
    }
    isAddingAddress.value = false;
    editingAddressId.value = null;
    loadCustomer();
  } catch (error) {
    console.error("Failed to save address", error);
  } finally {
    isSubmitting.value = false;
  }
}

async function handleAddressDelete() {
  if (!selectedAddress.value) return;
  isSubmitting.value = true;
  try {
    const id = selectedAddress.value.id;
    await AddressService.delete(id);
    isDeleteModalOpen.value = false;
    loadCustomer();
  } catch (error) {
    console.error("Failed to delete address", error);
  } finally {
    isSubmitting.value = false;
  }
}

function statusBadge(status: string) {
  const map: Record<string, string> = {
    pending: "bg-yellow-50 text-yellow-700 border-yellow-200",
    processing: "bg-blue-50 text-blue-700 border-blue-200",
    shipped: "bg-cyan-50 text-cyan-700 border-cyan-200",
    delivered: "bg-emerald-50 text-emerald-700 border-emerald-200",
    cancelled: "bg-slate-100 text-slate-700 border-slate-300",
    returned: "bg-rose-50 text-rose-700 border-rose-200",
  };
  return map[status] || "bg-slate-100 text-slate-700 border-slate-300";
}

const primaryEmail = computed(() => {
  const value = customer.value;
  if (!value) return "—";
  if (value.email) return value.email;

  const orderWithEmail = value.orders.find(
    (order) => order.user?.email || order.guest_email,
  );
  return orderWithEmail?.user?.email || orderWithEmail?.guest_email || "—";
});

const primaryPhone = computed(() => {
  const value = customer.value;
  if (!value) return "—";
  if (value.phone) return value.phone;

  const orderWithPhone = value.orders.find(
    (order) => order.guest_phone || order.address?.phone,
  );
  return orderWithPhone?.guest_phone || orderWithPhone?.address?.phone || "—";
});

async function loadCustomer() {
  loading.value = true;
  try {
    const id = route.params.id as string;
    customer.value = await CustomerService.getById(id);
    expandedOrderId.value = null;
  } catch (error) {
    console.error("Failed to load customer", error);
    customer.value = null;
  } finally {
    loading.value = false;
  }
}

function blockCustomer() {
  if (!customer.value) return;
  isBlockModalOpen.value = true;
}

async function handleBlockConfirm() {
  if (!customer.value) return;

  isBlocking.value = true;
  try {
    await CustomerService.block(customer.value.id);
    await loadCustomer();
  } catch (error: any) {
    console.error("Failed to block customer", error);
    alert(error.data?.message || "Failed to block customer");
  } finally {
    isBlocking.value = false;
    isBlockModalOpen.value = false;
  }
}

function unblockCustomer() {
  if (!customer.value) return;
  isUnblockModalOpen.value = true;
}

async function handleUnblockConfirm() {
  if (!customer.value) return;

  isUnblocking.value = true;
  try {
    await CustomerService.unblock(customer.value.id);
    await loadCustomer();
  } catch (error: any) {
    console.error("Failed to unblock customer", error);
    alert(error.data?.message || "Failed to unblock customer");
  } finally {
    isUnblocking.value = false;
    isUnblockModalOpen.value = false;
  }
}

onMounted(() => {
  loadCustomer();
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #e2e8f0;
  border-radius: 10px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
}
</style>
