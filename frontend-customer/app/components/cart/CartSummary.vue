<template>
  <section
    v-if="cart.items.length"
    class="w-full"
  >
    <div
      v-for="(item, index) in cart.items"
      :key="item.product.id"
      class="flex gap-4 p-6 sm:p-8"
      :class="{ 'border-b border-gray-200': index !== cart.items.length - 1 }"
    >
      <!-- Thumbnail -->
      <div class="h-28 w-28 shrink-0 overflow-hidden rounded-xl bg-[#F0F2F5]">
        <img
          v-if="getImageUrl(item.product.image)"
          :src="getImageUrl(item.product.image) || undefined"
          alt="thumb"
          class="h-full w-full object-cover mix-blend-multiply transition-transform duration-500 hover:scale-105"
        />
        <div
          v-else
          class="h-full w-full flex items-center justify-center bg-[#F0F2F5] text-gray-300"
        >
          <ShoppingCartIcon class="w-8 h-8" />
        </div>
      </div>

      <!-- Content -->
      <div class="flex-1 flex justify-between min-w-0">
        <div class="flex flex-col justify-between">
          <div>
            <h3
              class="font-bold text-gray-900 text-lg leading-snug truncate hover:text-blue-600 transition-colors"
            >
              {{ item.product.name }}
            </h3>
            <p class="text-sm text-gray-500 mt-1">
              In Stock:
              <span class="font-semibold text-gray-700">{{
                typeof item.product.stock === "number"
                  ? item.product.stock
                  : "Available"
              }}</span>
            </p>
            <div class="mt-3 text-sm text-gray-500 flex flex-col gap-1.5">
              <!-- Price per unit -->
              <p class="flex items-center gap-1.5">
                <span class="w-16">Price:</span>
                <del
                  v-if="Math.round(Number(item.product.price) * 100) > Math.round(item.unitPrice * 100)"
                  class="text-gray-400 font-medium"
                >
                  {{ formatPrice(Number(item.product.price)) }}
                </del>
                <span class="font-bold text-blue-600">
                  {{ formatPrice(item.unitPrice) }}
                </span>
              </p>

              <!-- Subtotal -->
              <p class="flex items-center gap-1.5">
                <span class="w-16">Subtotal:</span>
                <del
                  v-if="Math.round(Number(item.product.price) * 100) > Math.round(item.unitPrice * 100)"
                  class="text-gray-400 font-medium"
                >
                  {{ formatPrice((Math.round(Number(item.product.price) * 100) * item.quantity) / 100) }}
                </del>
                <span class="font-black text-gray-900 text-lg leading-none">
                  {{ formatPrice((Math.round(item.unitPrice * 100) * item.quantity) / 100) }}
                </span>
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-col items-end justify-between">
          <!-- Delete button -->
          <button
            type="button"
            @click="remove(item)"
            class="text-red-500 hover:text-red-600 active:scale-95 transition-all cursor-pointer p-1"
          >
            <Trash2Icon class="w-5 h-5 shrink-0" />
          </button>

          <!-- Quantity controls -->
          <div class="flex items-center gap-2 mt-4">
            <div class="flex items-center bg-gray-100 rounded-full px-3 py-1">
              <button
                type="button"
                @click="decrement(item)"
                :disabled="item.quantity <= 1"
                class="flex items-center justify-center text-gray-600 hover:text-black active:scale-90 transition-all cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed font-bold text-lg"
              >
                <MinusIcon class="w-4 h-4" />
              </button>
              <input
                type="number"
                :value="item.quantity"
                @change="updateInputQuantity(item, $event)"
                min="1"
                :max="item.product.stock"
                class="font-bold text-gray-900 text-sm px-2 w-16 text-center bg-transparent border-none outline-none focus:ring-0 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none [-moz-appearance:textfield]"
              />
              <button
                type="button"
                @click="increment(item)"
                :disabled="
                  item.product.stock !== undefined &&
                  item.quantity >= item.product.stock
                "
                class="flex items-center justify-center text-gray-600 hover:text-black active:scale-90 transition-all cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed font-bold text-lg"
              >
                <PlusIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <div
    v-else
    class="bg-white rounded-3xl border border-gray-100 shadow-sm text-center py-16 px-6 mt-4 space-y-4 flex flex-col items-center justify-center"
  >
    <div
      class="h-16 w-16 bg-blue-50 text-blue-600 rounded-3xl flex items-center justify-center mb-2"
    >
      <ShoppingCartIcon class="w-8 h-8" />
    </div>
    <h2 class="text-xl font-black text-gray-900 leading-none">
      Your cart is empty
    </h2>
    <p class="text-gray-400 text-xs max-w-[240px] leading-relaxed mx-auto">
      Looks like you haven't added any products to your cart yet. Let's browse
      some!
    </p>
    <NuxtLink
      to="/"
      class="inline-flex items-center justify-center px-6 py-3 rounded-2xl font-bold text-sm bg-gradient-to-r from-blue-600 to-blue-700 text-white hover:from-blue-700 hover:to-blue-800 shadow-lg shadow-blue-500/20 active:scale-95 transition-all cursor-pointer"
    >
      Browse Products
    </NuxtLink>
  </div>
</template>

<script setup lang="ts">
import type { CartItem } from "~/stores/cart";
import { useCartStore } from "~/stores/cart";
import { useProductImage } from "~/composables/useProductImage";
import { ShoppingCartIcon, Trash2Icon, MinusIcon, PlusIcon } from "@lucide/vue";

const cart = useCartStore();
const { getImageUrl } = useProductImage();

function formatPrice(value: number) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(value);
}

function increment(item: CartItem) {
  const maxStock =
    typeof item.product.stock === "number" ? item.product.stock : 999999;
  if (item.quantity >= maxStock) return;
  cart.addItem(item.product, 1);
}

function decrement(item: CartItem) {
  if (item.quantity <= 1) return;
  cart.updateQuantity(item.product.id, item.quantity - 1);
}

function remove(item: CartItem) {
  cart.removeItem(item.product.id);
}

function updateInputQuantity(item: CartItem, event: Event) {
  const target = event.target as HTMLInputElement;
  let val = parseInt(target.value);
  if (isNaN(val) || val < 1) {
    val = 1;
    target.value = "1";
  }
  const maxStock =
    typeof item.product.stock === "number" ? item.product.stock : 999999;
  if (val > maxStock) {
    val = maxStock;
    target.value = maxStock.toString();
  }
  cart.updateQuantity(item.product.id, val);
}
</script>
