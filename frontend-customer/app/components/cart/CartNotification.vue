<template>
  <div class="flex flex-col gap-3">
    <TransitionGroup
      enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="translate-x-full opacity-0 translate-y-2"
        enter-to-class="translate-x-0 opacity-100 translate-y-0"
        leave-active-class="transition-all duration-200 ease-in absolute w-full"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-full opacity-0"
        move-class="transition-all duration-300 ease-in-out"
      >
        <div
          v-for="notification in cart.notifications"
          :key="notification.id"
          class="bg-white rounded-2xl shadow-xl border border-blue-100 p-4 relative overflow-hidden"
        >
          <!-- Top Accent Line -->
          <div class="absolute top-0 left-0 w-full h-1.5 bg-blue-600"></div>

          <div class="flex items-start justify-between mb-3">
            <h4
              class="text-sm font-extrabold text-gray-900 flex items-center gap-1.5"
            >
              <CheckCircle2Icon class="w-4 h-4 text-emerald-500" />
              Added to Cart
            </h4>
            <button
              @click="cart.removeNotification(notification.id)"
              class="text-gray-400 hover:text-gray-600 p-1 -mt-1 -mr-1 rounded-lg hover:bg-gray-100 transition-colors"
            >
              <XIcon class="w-4 h-4" />
            </button>
          </div>

          <div class="flex gap-3 mb-4">
            <!-- Product Image -->
            <div
              class="w-14 h-14 rounded-xl bg-gray-100 border border-gray-200 overflow-hidden shrink-0 flex items-center justify-center"
            >
              <img
                v-if="getImageUrl(notification.item.product.image)"
                :src="
                  getImageUrl(notification.item.product.image) || undefined
                "
                :alt="notification.item.product.name"
                class="w-full h-full object-cover"
              />
              <ImageIcon v-else class="w-6 h-6 text-gray-300" />
            </div>

            <!-- Product Details -->
            <div class="flex-1 min-w-0 flex flex-col justify-center">
              <h5
                class="text-sm font-bold text-gray-800 leading-tight truncate"
              >
                {{ notification.item.product.name }}
              </h5>
              <div class="flex items-center justify-between mt-1">
                <div class="flex items-center gap-1.5">
                  <del
                    v-if="Math.round(Number(notification.item.product.price) * 100) > Math.round(notification.item.unitPrice * 100)"
                    class="text-gray-400 text-xs font-semibold"
                  >
                    {{ formatPrice((Math.round(Number(notification.item.product.price) * 100) * notification.item.quantity) / 100) }}
                  </del>
                  <span class="text-blue-600 font-extrabold text-sm">
                    {{ formatPrice((Math.round(notification.item.unitPrice * 100) * notification.item.quantity) / 100) }}
                  </span>
                </div>
                <span
                  class="text-xs font-semibold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-md"
                >
                  Qty: {{ notification.item.quantity }}
                </span>
              </div>
            </div>
          </div>

          <NuxtLink
            to="/cart"
            @click="cart.removeNotification(notification.id)"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-2.5 rounded-xl text-sm flex items-center justify-center gap-2 transition-all shadow-md active:scale-[0.98]"
          >
            <ShoppingCartIcon class="w-4 h-4" />
            View Cart
          </NuxtLink>
        </div>
      </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import { useCartStore } from "~/stores/cart";
import { useProductImage } from "~/composables/useProductImage";
import {
  CheckCircle2Icon,
  XIcon,
  ShoppingCartIcon,
  ImageIcon,
} from "@lucide/vue";

const cart = useCartStore();
const { getImageUrl } = useProductImage();

const formatPrice = (price: number) => {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(price);
};
</script>
