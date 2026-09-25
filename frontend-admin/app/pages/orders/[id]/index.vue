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
          <nuxt-link
            to="/orders"
            class="inline-flex items-center gap-2 text-[9.7px] font-bold text-slate-500 hover:text-slate-800 transition-colors"
          >
            <ArrowLeftIcon class="w-4 h-4" />
            Back to Orders
          </nuxt-link>
        </div>
        <div v-if="!loading" class="flex flex-wrap items-center gap-2">
          <IconButton
            color="blue"
            title="Download A4 Invoice"
            label="A4 Invoice"
            :disabled="downloadingA4 || downloadingReceipt"
            :loading="downloadingA4"
            @click="downloadPdf('a4')"
          >
            <FileTextIcon class="w-4 h-4" />
          </IconButton>
          <IconButton
            color="blue"
            title="Download Thermal Receipt"
            label="Thermal Receipt"
            :disabled="downloadingA4 || downloadingReceipt"
            :loading="downloadingReceipt"
            @click="downloadPdf('receipt')"
          >
            <PrinterIcon class="w-4 h-4" />
          </IconButton>
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
          <IconButton color="slate" title="Refresh" @click="fetchOrder">
            <RefreshCwIcon class="w-4 h-4" />
            <span class="text-[9.7px] font-bold ml-1">Refresh</span>
          </IconButton>
          <div class="h-4 w-px bg-slate-200 mx-1"></div>
          <IconButton
            color="blue"
            title="Edit Order"
            @click="$router.push(`/orders/${id}/edit`)"
          >
            <Edit3Icon class="w-4 h-4" />
            <span class="text-[9.7px] font-bold ml-1">Edit Order</span>
          </IconButton>
        </div>
      </div>

      <!-- Loading State (Initial or Refresh) -->
      <div
        v-if="loading"
        class="flex-1 flex flex-col items-center justify-center min-h-[300px]"
      >
        <Loader2Icon class="w-8 h-8 animate-spin text-blue-600 mb-4" />
        <span class="text-[8.7px] font-bold text-slate-500"
          >Loading order details...</span
        >
      </div>

      <!-- Error State -->
      <div
        v-else-if="!loading && !order"
        class="flex flex-col items-center justify-center py-20"
      >
        <AlertCircleIcon class="w-12 h-12 text-red-400 mb-3" />
        <h3 class="text-[15.4px] font-bold text-slate-800">Order not found</h3>
        <p class="text-slate-500 mt-1">
          The order you are looking for does not exist or has been deleted.
        </p>
      </div>

      <!-- Actual Content -->
      <div v-else-if="order" class="space-y-8">
        <div
          class="flex items-center justify-between pb-4 border-b border-slate-100"
        >
          <div>
            <h1 class="text-[18.8px] font-black tracking-tight text-slate-800">
              Order #{{ order.order_number }}
            </h1>
            <!-- Created By -->
            <p v-if="order.creator" class="text-[9.9px] text-slate-500 mt-1">
              Created by
              <span class="font-bold text-slate-700">{{
                order.creator.name
              }}</span>
              <span class="text-slate-400 ml-1"
                >({{ order.creator.role }})</span
              >
              <span
                v-if="order.creator.id === order.user?.id"
                class="font-medium text-blue-500 ml-1"
                >&bull; Self-Service</span
              >
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <span
              class="px-1.5 py-px rounded-full text-[8.2px] font-bold uppercase tracking-wider border"
              :class="[
                order.status === 'pending'
                  ? 'bg-yellow-100 text-yellow-800 border-yellow-200'
                  : '',
                order.status === 'confirmed'
                  ? 'bg-blue-100 text-blue-800 border-blue-200'
                  : '',
                order.status === 'processing'
                  ? 'bg-purple-100 text-purple-800 border-purple-200'
                  : '',
                order.status === 'shipped'
                  ? 'bg-indigo-100 text-indigo-800 border-indigo-200'
                  : '',
                order.status === 'delivered'
                  ? 'bg-lime-100 text-lime-800 border-lime-200'
                  : '',
                order.status === 'completed'
                  ? 'bg-green-100 text-green-800 border-green-200'
                  : '',
                order.status === 'cancelled'
                  ? 'bg-red-100 text-red-800 border-red-200'
                  : '',
                order.status === 'returned'
                  ? 'bg-gray-100 text-gray-800 border-gray-200'
                  : '',
                ![
                  'pending',
                  'confirmed',
                  'processing',
                  'shipped',
                  'delivered',
                  'completed',
                  'cancelled',
                  'returned',
                ].includes(order.status)
                  ? 'bg-slate-100 text-slate-800 border-slate-200'
                  : '',
              ]"
            >
              {{ order.status }}
            </span>
            <span
              class="px-1.5 py-px rounded-full text-[8.2px] font-bold uppercase tracking-wider border"
              :class="[
                order.payment_status === 'unpaid'
                  ? 'bg-yellow-100 text-yellow-800 border-yellow-200'
                  : '',
                order.payment_status === 'paid'
                  ? 'bg-green-100 text-green-800 border-green-200'
                  : '',
                order.payment_status === 'refunded'
                  ? 'bg-red-100 text-red-800 border-red-200'
                  : '',
                !['unpaid', 'paid', 'refunded'].includes(order.payment_status)
                  ? 'bg-slate-100 text-slate-800 border-slate-200'
                  : '',
              ]"
            >
              {{ order.payment_status }}
            </span>
          </div>
        </div>

        <div class="space-y-8">
          <!-- Customer Details -->
          <div
            class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm"
          >
            <h3
              class="text-[9.9px] font-black uppercase text-slate-400 tracking-wider mb-4"
            >
              Customer Details
            </h3>

            <div v-if="order.user" class="space-y-4">
              <div
                class="flex items-center gap-3 border-b border-slate-50 pb-4"
              >
                <img
                  v-if="order.user.avatar_url"
                  :src="
                    order.user.avatar_url.startsWith('http')
                      ? order.user.avatar_url
                      : getImageUrl(order.user.avatar_url)!
                  "
                  class="w-12 h-12 rounded-full object-cover border border-slate-200 shadow-sm"
                  alt="Avatar"
                />
                <div
                  v-else
                  class="w-12 h-12 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-[15.4px] shadow-sm"
                >
                  {{ order.user.name.charAt(0) }}
                </div>
                <div>
                  <p class="text-[13.2px] font-bold text-slate-800">
                    {{ order.user.name }}
                  </p>
                  <p
                    class="text-[9.9px] font-semibold text-slate-500 flex items-center gap-1.5"
                  >
                    <span>{{ order.user.email || "No email provided" }}</span>
                    <span v-if="order.user.phone" class="text-slate-300"
                      >&bull;</span
                    >
                    <span v-if="order.user.phone">{{ order.user.phone }}</span>
                  </p>
                </div>
              </div>
            </div>

            <div v-else>
              <p class="text-[9.9px] text-slate-500 italic">
                Guest Checkout or Missing User
              </p>
            </div>
          </div>

          <!-- Shipping Address -->
          <div
            class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm"
          >
            <h3
              class="text-[9.9px] font-black uppercase text-slate-400 tracking-wider mb-4"
            >
              Shipping Address
            </h3>
            <div v-if="order.address" class="space-y-3">
              <!-- Label Badge -->
              <div v-if="order.address.label">
                <span
                  class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[8.2px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200"
                >
                  {{ order.address.label }}
                </span>
              </div>

              <!-- Name & Phone -->
              <div class="flex items-start justify-between gap-4">
                <div>
                  <p class="text-[11.5px] font-bold text-slate-800">
                    {{ order.address.name }}
                  </p>
                  <p
                    class="text-[9.9px] text-slate-500 mt-0.5"
                    v-if="order.address.phone"
                  >
                    {{ order.address.phone }}
                  </p>
                </div>
              </div>

              <!-- Full Address -->
              <div class="text-[9.9px] text-slate-600 leading-relaxed space-y-1 mt-2">
                <p v-if="order.address.address_line_1"><span class="font-bold text-slate-500">Address 1:</span> {{ order.address.address_line_1 }}</p>
                <p v-if="order.address.address_line_2"><span class="font-bold text-slate-500">Address 2:</span> {{ order.address.address_line_2 }}</p>
                <p v-if="order.address.village"><span class="font-bold text-slate-500">Village:</span> {{ order.address.village }}</p>
                <p v-if="order.address.commune"><span class="font-bold text-slate-500">Commune:</span> {{ order.address.commune }}</p>
                <p v-if="order.address.district"><span class="font-bold text-slate-500">District:</span> {{ order.address.district }}</p>
                <p v-if="order.address.province"><span class="font-bold text-slate-500">Province:</span> {{ order.address.province }}</p>
              </div>

              <!-- Notes + Map -->
              <div
                class="flex items-start justify-between gap-3 pt-2 border-t border-slate-100"
              >
                <p
                  v-if="order.address.notes"
                  class="text-[9.9px] text-slate-500 italic flex-1"
                >
                  {{ order.address.notes }}
                </p>
                <div v-else class="flex-1"></div>
                <a
                  v-if="order.address.latitude && order.address.longitude"
                  :href="`https://www.google.com/maps?q=${order.address.latitude},${order.address.longitude}`"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 rounded-lg text-[8.7px] font-bold transition-colors shrink-0"
                  title="View on Google Maps"
                >
                  <MapPinIcon class="w-3.5 h-3.5" />
                  View on Map
                </a>
              </div>
            </div>

            <div v-else>
              <p class="text-[9.9px] text-slate-500 italic">
                No address provided
              </p>
            </div>
          </div>

          <!-- Payment Details -->
          <div
            v-if="order.payment_method || order.payment_receipt"
            class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm space-y-3"
          >
            <h3
              class="text-[9.9px] font-black uppercase text-slate-400 tracking-wider mb-4"
            >
              Payment Details
            </h3>

            <!-- Payment Method Badge -->
            <div v-if="order.payment_method" class="flex items-center gap-2">
              <span class="text-[8.7px] font-bold text-slate-500">Method:</span>
              <span
                :class="
                  order.payment_method === 'bank'
                    ? 'bg-blue-50 text-blue-700 border border-blue-200'
                    : 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                "
                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[8.7px] font-bold uppercase tracking-wider"
              >
                <LandmarkIcon
                  v-if="order.payment_method === 'bank'"
                  class="w-3.5 h-3.5"
                />
                <BanknoteIcon v-else class="w-3.5 h-3.5" />
                {{ order.payment_method === "bank" ? "Bank" : "Cash" }}
              </span>
            </div>

            <!-- Receipt Image -->
            <div v-if="order.payment_receipt">
              <p
                class="text-[8.2px] font-bold uppercase text-slate-400 tracking-wider mb-2"
              >
                Receipt / Proof
              </p>
              <a
                :href="getImageUrl(order.payment_receipt)!"
                target="_blank"
                class="block group relative rounded-xl overflow-hidden border border-slate-200 bg-slate-50 hover:border-blue-300 transition-colors"
              >
                <img
                  :src="getImageUrl(order.payment_receipt)!"
                  class="w-full max-h-48 object-contain p-2"
                  alt="Payment Receipt"
                />
                <div
                  class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
                >
                  <span
                    class="px-3 py-1.5 bg-white rounded-lg text-[8.7px] font-bold text-slate-800"
                    >Click to view full image</span
                  >
                </div>
              </a>
            </div>
          </div>

          <!-- Order Summary -->
          <div
            class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm"
          >
            <h3
              class="text-[9.9px] font-black uppercase text-slate-400 tracking-wider mb-4"
            >
              Order Summary
            </h3>

            <!-- Order Items -->
            <div class="mb-6">
              <div
                class="overflow-x-auto overflow-y-auto max-h-[400px] rounded-xl border border-slate-200 shadow-sm relative z-20"
              >
                <table
                  class="w-full text-left text-[9.9px] border-collapse bg-white"
                >
                  <thead class="sticky top-0 z-10 bg-slate-50 shadow-sm">
                    <tr class="border-b border-slate-200">
                      <th
                        class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider bg-slate-50"
                      >
                        Product
                      </th>
                      <th
                        class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider bg-slate-50"
                      >
                        SKU
                      </th>
                      <th
                        class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right bg-slate-50"
                      >
                        Price
                      </th>
                      <th
                        class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-center bg-slate-50"
                      >
                        Discount
                      </th>
                      <th
                        class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-center bg-slate-50"
                      >
                        Qty
                      </th>
                      <th
                        class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right bg-slate-50"
                      >
                        Total
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="item in order.items"
                      :key="item.id"
                      class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors"
                    >
                      <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                          <div
                            class="size-8 bg-slate-100 rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0 border border-slate-200"
                          >
                            <img
                              v-if="item.product?.image"
                              :src="getImageUrl(item.product.image)!"
                              class="w-full h-full object-cover"
                            />
                            <PackageIcon
                              v-else
                              class="w-5 h-5 text-slate-400"
                            />
                          </div>
                          <span class="font-bold text-slate-700">{{
                            item.product_name || item.product?.name || "Unknown"
                          }}</span>
                        </div>
                      </td>
                      <td class="px-4 py-3 font-medium text-slate-600">
                        {{ item.product_sku || item.product?.sku || "-" }}
                      </td>
                      <td
                        class="px-4 py-3 text-right font-medium text-slate-600"
                      >
                        {{ formatPrice(item.unit_price) }}
                      </td>
                      <td class="px-4 py-3 text-center font-bold text-rose-500">
                        {{
                          item.product_discount ||
                          item.product?.discount_percent ||
                          0
                        }}%
                      </td>
                      <td
                        class="px-4 py-3 text-center font-bold text-slate-700"
                      >
                        {{ item.quantity }}
                      </td>
                      <td class="px-4 py-3 text-right font-bold text-slate-800">
                        {{ formatPrice(item.total) }}
                      </td>
                    </tr>
                    <tr v-if="!order.items?.length">
                      <td
                        colspan="4"
                        class="px-4 py-8 text-center text-slate-500 font-medium"
                      >
                        No items found for this order.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="space-y-3">
              <div class="flex justify-between">
                <span class="text-[8.7px] font-bold text-slate-500"
                  >Subtotal</span
                >
                <span class="text-[11.5px] font-bold text-slate-800">{{
                  formatPrice(order.subtotal)
                }}</span>
              </div>
              <div
                v-if="Number(order.discount) > 0"
                class="flex justify-between"
              >
                <span class="text-[8.7px] font-bold text-slate-500"
                  >Discount</span
                >
                <span class="text-[11.5px] font-bold text-red-500"
                  >-{{ formatPrice(order.discount) }}</span
                >
              </div>
              <div class="flex justify-between">
                <span class="text-[8.7px] font-bold text-slate-500"
                  >Shipping</span
                >
                <span class="text-[11.5px] font-bold text-slate-800">{{
                  formatPrice(order.shipping_cost)
                }}</span>
              </div>
              <div class="pt-3 border-t border-slate-200 flex justify-between">
                <span class="text-[11.5px] font-black text-slate-700"
                  >Total</span
                >
                <span class="text-[15.4px] font-black text-blue-600">{{
                  formatPrice(order.total)
                }}</span>
              </div>
            </div>

            <!-- Notes -->
            <div
              v-if="order.customer_note || order.admin_note"
              class="mt-6 pt-6 border-t border-slate-200 space-y-3"
            >
              <!-- Customer Note -->
              <div
                v-if="order.customer_note"
                class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2"
              >
                <p
                  class="text-[8.2px] font-bold uppercase text-blue-500 tracking-wider mb-1"
                >
                  Customer Note
                </p>
                <p
                  class="text-[9.9px] text-blue-800 font-medium whitespace-pre-line"
                >
                  {{ order.customer_note }}
                </p>
              </div>

              <!-- Admin Note -->
              <div
                v-if="order.admin_note"
                class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2"
              >
                <p
                  class="text-[8.2px] font-bold uppercase text-amber-500 tracking-wider mb-1"
                >
                  Admin Note
                </p>
                <p
                  class="text-[9.9px] text-amber-800 font-medium whitespace-pre-line"
                >
                  {{ order.admin_note }}
                </p>
              </div>
            </div>
          </div>

          <!-- History Section -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Order Status History -->
            <div
              class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm"
            >
              <h3
                class="text-[9.9px] font-black uppercase text-slate-400 tracking-wider mb-4"
              >
                Order Status History
              </h3>
              <div class="border-l-2 border-slate-100 ml-2 space-y-6">
                <div
                  v-for="(history, index) in order.status_history"
                  :key="index"
                  class="relative pl-6"
                >
                  <div
                    class="absolute -left-[5px] top-1.5 w-2 h-2 rounded-full ring-4 ring-white"
                    :class="[
                      history.new_status === 'pending' ? 'bg-yellow-400' : '',
                      history.new_status === 'processing' ? 'bg-blue-400' : '',
                      history.new_status === 'shipped' ? 'bg-indigo-400' : '',
                      history.new_status === 'delivered' ? 'bg-green-400' : '',
                      history.new_status === 'cancelled' ? 'bg-red-400' : '',
                      history.new_status === 'returned' ? 'bg-gray-400' : '',
                      ![
                        'pending',
                        'processing',
                        'shipped',
                        'delivered',
                        'cancelled',
                        'returned',
                      ].includes(history.new_status)
                        ? 'bg-slate-400'
                        : '',
                    ]"
                  ></div>
                  <div class="mb-1 flex items-center">
                    <span
                      class="text-[11.5px] font-bold text-slate-800 capitalize"
                      >{{ history.new_status }}</span
                    >
                    <span class="text-[8.2px] font-bold text-slate-400 ml-2">{{
                      new Date(history.created_at).toLocaleString()
                    }}</span>
                  </div>
                  <div
                    class="text-[9.9px] text-slate-500"
                    v-if="history.changed_by?.name"
                  >
                    By:
                    <span class="font-medium text-slate-700">{{
                      history.changed_by.name
                    }}</span>
                  </div>
                  <div
                    class="text-[9.9px] text-slate-500 italic mt-1"
                    v-if="history.remarks"
                  >
                    "{{ history.remarks }}"
                  </div>
                </div>
                <div
                  v-if="!order.status_history?.length"
                  class="text-[11.5px] text-slate-500 italic pl-6"
                >
                  No history recorded
                </div>
              </div>
            </div>

            <!-- Payment Status History -->
            <div
              class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm"
            >
              <h3
                class="text-[9.9px] font-black uppercase text-slate-400 tracking-wider mb-4"
              >
                Payment Status History
              </h3>
              <div class="border-l-2 border-slate-100 ml-2 space-y-6">
                <div
                  v-for="(history, index) in order.payment_history"
                  :key="index"
                  class="relative pl-6"
                >
                  <div
                    class="absolute -left-[5px] top-1.5 w-2 h-2 rounded-full ring-4 ring-white"
                    :class="[
                      history.new_status === 'unpaid' ? 'bg-red-400' : '',
                      history.new_status === 'paid' ? 'bg-green-400' : '',
                      history.new_status === 'refunded' ? 'bg-yellow-400' : '',
                      !['unpaid', 'paid', 'refunded'].includes(
                        history.new_status,
                      )
                        ? 'bg-slate-400'
                        : '',
                    ]"
                  ></div>
                  <div class="mb-1 flex items-center">
                    <span
                      class="text-[11.5px] font-bold text-slate-800 capitalize"
                      >{{ history.new_status }}</span
                    >
                    <span class="text-[8.2px] font-bold text-slate-400 ml-2">{{
                      new Date(history.created_at).toLocaleString()
                    }}</span>
                  </div>
                  <div
                    class="text-[9.9px] text-slate-500"
                    v-if="history.changed_by?.name"
                  >
                    By:
                    <span class="font-medium text-slate-700">{{
                      history.changed_by.name
                    }}</span>
                  </div>
                  <div
                    class="text-[9.9px] text-slate-500 italic mt-1"
                    v-if="history.remarks"
                  >
                    "{{ history.remarks }}"
                  </div>
                </div>
                <div
                  v-if="!order.payment_history?.length"
                  class="text-[11.5px] text-slate-500 italic pl-6"
                >
                  No history recorded
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRoute } from "nuxt/app";
import {
  ArrowLeftIcon,
  Loader2Icon,
  AlertCircleIcon,
  Edit3Icon,
  RefreshCwIcon,
  PackageIcon,
  LandmarkIcon,
  BanknoteIcon,
  MapPinIcon,
  DownloadIcon,
  FileTextIcon,
  PrinterIcon,
} from "@lucide/vue";
import { OrderService } from "~/services/order.service";
import { useRuntimeConfig } from "nuxt/app";
import type { ApiOrder } from "@/types/api";

const { getImageUrl } = useProductImage();

definePageMeta({ layout: "default", middleware: "admin" });

const route = useRoute();
const id = route.params.id as string;
const config = useRuntimeConfig();

const order = ref<ApiOrder | null>(null);
const loading = ref(true);
const exportingExcel = ref(false);
const downloadingA4 = ref(false);
const downloadingReceipt = ref(false);

async function downloadPdf(format: "a4" | "receipt") {
  if (!order.value) return;
  if (format === "a4") downloadingA4.value = true;
  else downloadingReceipt.value = true;

  try {
    if (format === "a4") {
      const res = (await OrderService.downloadPdf(id, "a4")) as Blob;
      const url = window.URL.createObjectURL(
        new Blob([res as any], { type: "application/pdf" }),
      );
      const link = document.createElement("a");
      link.href = url;
      link.setAttribute(
        "download",
        `Order_${order.value.order_number}_invoice-a4.pdf`,
      );
      document.body.appendChild(link);
      link.click();
      link.parentNode?.removeChild(link);
    } else {
      const pdfRes = (await OrderService.downloadPdf(id, "receipt")) as Blob;
      const url = window.URL.createObjectURL(
        new Blob([pdfRes as any], { type: "application/pdf" }),
      );
      const link = document.createElement("a");
      link.href = url;
      link.setAttribute(
        "download",
        `Order_${order.value.order_number}_receipt-thermal.pdf`,
      );
      document.body.appendChild(link);
      link.click();
      link.parentNode?.removeChild(link);

      const htmlContent = (await OrderService.downloadPdf(
        id,
        "receipt_html",
      )) as string;
      const printWindow = window.open("", "_blank");
      if (printWindow) {
        printWindow.document.open();
        printWindow.document.write(htmlContent);
        printWindow.document.close();
      }
    }
  } catch (error) {
    console.error("Download/Print failed", error);
  } finally {
    if (format === "a4") downloadingA4.value = false;
    else downloadingReceipt.value = false;
  }
}

async function exportExcel() {
  if (!order.value) return;
  exportingExcel.value = true;
  try {
    const res = await OrderService.exportDetail(id);
    const url = window.URL.createObjectURL(new Blob([res as any]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute(
      "download",
      `Order_Detail_${order.value.order_number}_${new Date().toISOString().split("T")[0]}.xlsx`,
    );
    document.body.appendChild(link);
    link.click();
    link.parentNode?.removeChild(link);
  } catch (error) {
    console.error("Export failed", error);
  } finally {
    exportingExcel.value = false;
  }
}

async function fetchOrder() {
  loading.value = true;
  try {
    const res = (await OrderService.getById(id)) as any;
    if (res && res.order) {
      order.value = {
        ...res.order,
        items: res.items || [],
        status_history: res.status_history || [],
        payment_history: res.payment_history || [],
      };
    } else {
      order.value = res?.data || res;
    }
  } catch (e) {
    console.error("Failed to load order", e);
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  fetchOrder();
});
</script>
