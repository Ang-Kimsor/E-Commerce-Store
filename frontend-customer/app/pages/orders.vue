<template>
  <div class="w-full px-4 pt-4">
    <Breadcrumb :items="[{ label: 'My Orders' }]" />
    <!-- Success Banner -->
    <div
      v-if="showSuccessBanner"
      class="p-3 rounded-xl border border-green-200 bg-green-50 text-green-800 mb-4 flex items-center justify-between"
    >
      <div>
        <p class="font-semibold text-sm">Order placed!</p>
        <p class="text-xs text-green-700 mt-0.5">
          Your order is being processed.
        </p>
      </div>
      <button
        @click="showSuccessBanner = false"
        class="ml-4 px-3 py-1.5 bg-green-600 text-white text-xs font-semibold rounded-lg hover:bg-green-700 transition-colors cursor-pointer"
      >
        OK
      </button>
    </div>



    <!-- Loading Skeleton -->
    <div v-if="isLoading" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <div
        v-for="i in 15"
        :key="i"
        class="bg-white rounded-xl border border-gray-100 p-4 space-y-3 animate-pulse"
      >
        <div class="flex justify-between">
          <div class="h-4 bg-gray-200 rounded w-2/5"></div>
          <div class="flex gap-1">
            <div class="h-4 bg-gray-100 rounded-full w-16"></div>
            <div class="h-4 bg-gray-100 rounded-full w-12"></div>
          </div>
        </div>
        <div class="h-3 bg-gray-100 rounded w-1/4"></div>
        <div class="flex justify-between items-center pt-1">
          <div class="h-3 bg-gray-100 rounded w-16"></div>
          <div class="h-5 bg-gray-200 rounded w-20"></div>
        </div>
        <div class="h-8 bg-gray-100 rounded-lg mt-1"></div>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="!orders.length"
      class="bg-white rounded-2xl border border-gray-100 text-center py-16 px-4 flex flex-col items-center justify-center shadow-sm"
    >
      <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mb-4 border border-gray-100 shadow-sm">
        <PackageIcon class="w-8 h-8 text-gray-400" />
      </div>
      <h2 class="text-sm font-bold text-gray-900 mb-1">No orders yet</h2>
      <p class="text-xs text-gray-500 max-w-xs mx-auto mb-5 leading-relaxed">
        When you place orders, they will appear here so you can track their status.
      </p>
      <NuxtLink
        to="/"
        class="inline-flex items-center justify-center px-5 py-2.5 bg-gray-900 text-white rounded-xl text-xs font-semibold hover:bg-black transition-all hover:shadow-md hover:-translate-y-0.5 active:translate-y-0"
      >
        Start Shopping
      </NuxtLink>
    </div>

    <!-- Order List -->
    <div v-else class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <OrderCard
        v-for="order in orders"
        :key="order.id"
        :order="order"
        @open-invoice="openInvoice"
      />
    </div>

    <!-- Right-Aligned Pagination Capsule Bar -->
    <div class="flex justify-end items-center w-full pt-4 pb-2">
      <Pagination
        v-if="orders.length"
        :current-page="currentPage"
        :total-pages="lastPage"
        :total-items="totalOrders"
        :showing-count="orders.length"
        :per-page="15"
        @change="loadOrders"
      />
    </div>

    <!-- Invoice Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="invoiceOrder"
          class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4"
          @click.self="closeInvoice"
        >
          <div
            id="print-invoice-area"
            class="bg-white w-full max-w-lg rounded-2xl max-h-[90vh] flex flex-col shadow-xl overflow-hidden animate-slide-up"
          >
            <!-- Modal Header -->
            <div
              class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 flex items-center justify-between flex-shrink-0"
            >
              <div>
                <h2 class="font-bold text-base">Invoice Details</h2>
                <p class="text-[10px] text-blue-100 mt-0.5">
                  Order #{{ invoiceOrder.order_number }}
                </p>
              </div>
              <button
                @click="closeInvoice"
                class="w-8 h-8 rounded-full flex items-center justify-center bg-white/10 hover:bg-white/20 text-white transition-colors text-xl leading-none cursor-pointer"
              >
                &times;
              </button>
            </div>

            <!-- Modal Body -->
            <div class="overflow-y-auto flex-1 p-6 space-y-5">
              <!-- Company Header -->
              <div class="flex justify-between items-start">
                <div>
                  <h3 class="text-xl font-black text-blue-600 leading-none">
                    {{ settingsStore.siteName?.toUpperCase() || "STORE" }}
                  </h3>
                  <p
                    class="text-[9px] uppercase font-bold text-gray-400 mt-1 tracking-wider"
                  >
                    Invoice
                  </p>
                </div>
                <div
                  class="text-right text-xs text-gray-400 space-y-0.5 font-semibold"
                >
                  <p v-if="settingsStore.contactEmail">
                    {{ settingsStore.contactEmail }}
                  </p>
                  <p v-if="settingsStore.contactPhone">
                    {{ settingsStore.contactPhone }}
                  </p>
                </div>
              </div>

              <div class="border-t border-gray-100"></div>

              <!-- Billed To & Info -->
              <div class="grid grid-cols-2 gap-4 text-xs">
                <div>
                  <p
                    class="text-[9px] text-gray-400 font-black uppercase tracking-wider mb-1"
                  >
                    Billed To
                  </p>
                  <p class="font-bold text-gray-900 text-sm leading-snug">
                    {{
                      invoiceOrder.address?.name ||
                      invoiceOrder.user?.name ||
                      invoiceOrder.guest_name ||
                      "Customer"
                    }}
                  </p>
                  <p class="text-gray-500 font-semibold mt-1 leading-relaxed">
                    {{
                      invoiceOrder.address
                        ? [
                            invoiceOrder.address.address_line_1,
                            invoiceOrder.address.address_line_2,
                            invoiceOrder.address.village,
                            invoiceOrder.address.commune,
                            invoiceOrder.address.district,
                            invoiceOrder.address.province,
                          ]
                            .filter(Boolean)
                            .join(", ")
                        : "No shipping address"
                    }}
                  </p>
                  <p
                    class="text-gray-500 font-semibold leading-relaxed"
                    v-if="invoiceOrder.address?.phone"
                  >
                    Phone: {{ invoiceOrder.address.phone }}
                  </p>
                </div>
                <div class="text-right space-y-1">
                  <p
                    class="text-[9px] text-gray-400 font-black uppercase tracking-wider mb-1"
                  >
                    Invoice Details
                  </p>
                  <p class="text-gray-500 font-semibold">
                    Invoice No:
                    <span class="text-gray-900 font-bold"
                      >#{{ invoiceOrder.order_number }}</span
                    >
                  </p>
                  <p class="text-gray-500 font-semibold">
                    Date:
                    <span class="text-gray-900 font-bold">{{
                      formatDate(invoiceOrder.created_at)
                    }}</span>
                  </p>
                  <div class="flex items-center justify-end gap-1.5 mt-2">
                    <span
                      class="inline-flex px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider shadow-xs"
                      :class="getPaymentBadge(invoiceOrder.payment_status)"
                    >
                      {{ invoiceOrder.payment_status }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Table -->
              <div
                class="border-t border-b pr-1 border-gray-100 overflow-y-auto max-h-[250px] relative z-20"
              >
                <table class="w-full text-xs">
                  <thead class="sticky top-0 bg-white z-10 shadow-sm">
                    <tr
                      class="text-gray-400 border-b border-gray-100 font-bold"
                    >
                      <th
                        class="text-left py-2 font-bold uppercase tracking-wider bg-white"
                      >
                        Item
                      </th>
                      <th
                        class="text-center py-2 font-bold uppercase tracking-wider bg-white"
                      >
                        Qty
                      </th>
                      <th
                        class="text-right py-2 font-bold uppercase tracking-wider bg-white"
                      >
                        Price
                      </th>
                      <th
                        class="text-right py-2 font-bold uppercase tracking-wider bg-white"
                      >
                        Total
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="item in invoiceOrder.items"
                      :key="item.id"
                      class="border-b border-gray-50 font-medium text-gray-700"
                    >
                      <td class="py-2.5 font-bold text-gray-800">
                        {{ item.product?.name || "Product" }}
                      </td>
                      <td class="text-center py-2.5 text-gray-500">
                        {{ item.quantity }}
                      </td>
                      <td class="text-right py-2.5">
                        {{ formatPrice(item.unit_price) }}
                      </td>
                      <td class="text-right py-2.5 font-bold text-gray-900">
                        {{ formatPrice(item.total) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Totals -->
              <div class="space-y-2 text-xs font-semibold pt-1">
                <div class="flex justify-between text-gray-500">
                  <span>Subtotal</span>
                  <span class="text-gray-900 font-bold">{{
                    formatPrice(invoiceOrder.subtotal)
                  }}</span>
                </div>
                <div class="flex justify-between text-gray-500">
                  <span>Delivery</span>
                  <span class="text-green-600 font-bold">{{
                    invoiceOrder.shipping_cost > 0
                      ? formatPrice(invoiceOrder.shipping_cost)
                      : "Free"
                  }}</span>
                </div>
                <div
                  class="flex justify-between font-black text-sm border-t border-gray-100 pt-3 mt-1"
                >
                  <span class="text-gray-900">Total</span>
                  <span class="text-blue-600 font-black text-base">{{
                    formatPrice(invoiceOrder.total)
                  }}</span>
                </div>
              </div>
            </div>

            <!-- Modal Footer -->
            <div
              class="flex gap-2 p-4 border-t border-gray-100 bg-gray-50 flex-shrink-0"
            >
              <button
                @click="downloadInvoice(invoiceOrder, 'a4')"
                :disabled="downloadingOrderId === invoiceOrder.id"
                class="flex-1 py-2.5 rounded-xl font-semibold text-xs bg-blue-600 hover:bg-blue-700 text-white transition-colors flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span
                  v-if="downloadingOrderId === invoiceOrder.id"
                  class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"
                ></span>
                <DownloadIcon v-else class="w-3.5 h-3.5" />
                <span>{{
                  downloadingOrderId === invoiceOrder.id
                    ? "Downloading..."
                    : "Download A4 Receipt"
                }}</span>
              </button>
              <button
                @click="closeInvoice"
                class="py-2.5 px-4 rounded-xl font-semibold text-xs border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition-colors cursor-pointer"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
    <!-- Status Modal -->
    <StatusModal
      v-model="showStatusModal"
      :title="statusTitle"
      :message="statusMessage"
      :is-success="isSuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useRuntimeConfig } from "nuxt/app";
import type { ApiOrder, PaginatedResponse } from "../../types/api";
import { OrderService } from "../services/order.service";
import { useAuthStore } from "../stores/auth";
import { PackageIcon, DownloadIcon } from "@lucide/vue";
import { useSettingsStore } from "../stores/settings";

const showStatusModal = ref(false);
const statusTitle = ref("");
const statusMessage = ref("");
const isSuccess = ref(true);

const showStatus = (title: string, message: string, success = true) => {
  statusTitle.value = title;
  statusMessage.value = message;
  isSuccess.value = success;
  showStatusModal.value = true;
};

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const settingsStore = useSettingsStore();

const orders = ref<ApiOrder[]>([]);
const isLoading = ref(true);
const currentPage = ref(1);
const lastPage = ref(1);
const totalOrders = ref(0);
const showSuccessBanner = ref(route.query.success === "true");
const invoiceOrder = ref<ApiOrder | null>(null);

function formatPrice(value: number | string) {
  const n = typeof value === "string" ? Number(value) : value;
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(Number.isFinite(n) ? n : 0);
}

function formatDate(value: string) {
  return new Date(value).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

function getPaymentBadge(status: string) {
  const map: Record<string, string> = {
    unpaid: "bg-orange-100 text-orange-700",
    paid: "bg-green-100 text-green-700",
    refunded: "bg-red-100 text-red-700",
  };
  return map[status.toLowerCase()] || "bg-gray-100 text-gray-600";
}

function openInvoice(order: ApiOrder) {
  invoiceOrder.value = order;
}
function closeInvoice() {
  invoiceOrder.value = null;
}

const downloadingOrderId = ref<number | null>(null);
const downloadingFormat = ref<"receipt" | "a4" | null>(null);

async function downloadInvoice(order: ApiOrder, format: "receipt" | "a4") {
  downloadingOrderId.value = order.id;
  downloadingFormat.value = format;
  try {
    const config = useRuntimeConfig();
    const response = await fetch(
      `${config.public.apiBase}/customer/orders/${order.id}/invoice?format=${format}`,
      {
        headers: {
          Authorization: `Bearer ${auth.token}`,
          Accept: "application/pdf",
        },
      },
    );
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const blob = await response.blob();
    const link = Object.assign(document.createElement("a"), {
      href: window.URL.createObjectURL(blob),
      download: `${order.order_number}-${format}-invoice.pdf`,
    });
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (error: any) {
    showStatus(
      "Download Failed",
      `Unable to download invoice: ${error?.message}`,
      false,
    );
  } finally {
    downloadingOrderId.value = null;
    downloadingFormat.value = null;
  }
}

async function loadOrders(page = 1) {
  isLoading.value = true;
  try {
    if (auth.isAuthenticated) {
      const response = await OrderService.getAll({ page, per_page: 15 });
      orders.value = response.data;
      currentPage.value = response.current_page;
      lastPage.value = response.last_page;
      totalOrders.value = response.total;
    }
  } finally {
    isLoading.value = false;
  }
}

onMounted(() => {
  if (!auth.isAuthenticated) {
    router.push("/login?redirect=" + route.fullPath);
    return;
  }

  loadOrders(1);
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
