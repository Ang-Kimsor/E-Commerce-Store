<template>
  <div
    class="bg-white rounded-xl border border-gray-100 hover:border-gray-200 hover:shadow-sm transition-all duration-200 flex flex-col"
  >
    <!-- Card Header -->
    <div
      class="px-4 py-3 border-b border-gray-50 flex items-center justify-between gap-2"
    >
      <div>
        <p class="font-bold text-gray-900 text-sm">#{{ order.order_number }}</p>
        <p class="text-xs text-gray-400 mt-0.5">
          {{ formatDate(order.created_at) }}
        </p>
      </div>
      <div class="flex gap-1.5 flex-shrink-0">
        <span
          class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase"
          :class="getStatusBadge(order.status)"
          >{{ formatStatus(order.status) }}</span
        >
        <span
          class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase"
          :class="getPaymentBadge(order.payment_status)"
          >{{ formatStatus(order.payment_status) }}</span
        >
      </div>
    </div>

    <!-- Card Body -->
    <div class="px-4 py-3 flex items-center justify-between">
      <span class="text-xs text-gray-400"
        >{{ order.items?.length || 0 }} item{{
          (order.items?.length || 0) !== 1 ? "s" : ""
        }}</span
      >
      <span class="text-base font-bold text-gray-900">{{
        formatPrice(order.total)
      }}</span>
    </div>

    <!-- Invoice Button -->
    <div class="px-4 pb-3">
      <button
        @click="$emit('open-invoice', order)"
        class="w-full inline-flex items-center justify-center gap-1.5 py-2 rounded-lg text-xs font-semibold border border-gray-200 bg-gray-50 text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer"
      >
        <FileTextIcon class="w-3.5 h-3.5" />
        Invoice
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { FileTextIcon } from "@lucide/vue";
import type { ApiOrder } from "@/types/api";

const props = defineProps<{
  order: ApiOrder;
}>();

defineEmits<{
  (e: "open-invoice", order: ApiOrder): void;
}>();

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
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });
}

function formatStatus(status: string) {
  if (!status) return "N/A";
  return status.charAt(0).toUpperCase() + status.slice(1);
}

function getStatusBadge(status: string) {
  const map: Record<string, string> = {
    pending: "bg-orange-100 text-orange-700",
    confirmed: "bg-amber-100 text-amber-700",
    processing: "bg-blue-100 text-blue-700",
    shipped: "bg-violet-100 text-violet-700",
    delivered: "bg-teal-100 text-teal-700",
    completed: "bg-green-100 text-green-700",
    cancelled: "bg-red-100 text-red-700",
    returned: "bg-gray-100 text-gray-700",
  };
  return map[status.toLowerCase()] || "bg-gray-100 text-gray-600";
}

function getPaymentBadge(status: string) {
  const map: Record<string, string> = {
    unpaid: "bg-orange-100 text-orange-700",
    paid: "bg-green-100 text-green-700",
    refunded: "bg-red-100 text-red-700",
  };
  return map[status.toLowerCase()] || "bg-gray-100 text-gray-600";
}
</script>
