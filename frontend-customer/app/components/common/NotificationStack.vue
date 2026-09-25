<template>
  <div class="flex flex-col gap-3">
    <TransitionGroup
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="translate-x-full opacity-0"
      enter-to-class="translate-x-0 opacity-100"
      leave-active-class="transition-all duration-200 ease-in absolute w-full"
      leave-from-class="translate-x-0 opacity-100"
      leave-to-class="translate-x-full opacity-0"
      move-class="transition-all duration-300 ease-in-out"
    >
      <div
        v-for="n in notifications.items"
        :key="n.id"
      >
        <!-- Cart Notification -->
        <div
          v-if="n.type === 'cart'"
          class="bg-white rounded-2xl shadow-xl border border-blue-100 p-4 relative overflow-hidden"
        >
          <div class="absolute top-0 left-0 w-full h-1.5 bg-blue-600"></div>

          <div class="flex items-start justify-between mb-3">
            <h4 class="text-sm font-extrabold text-gray-900 flex items-center gap-1.5">
              <CheckCircle2Icon class="w-4 h-4 text-emerald-500" />
              Added to Cart
            </h4>
            <button
              @click="notifications.remove(n.id)"
              class="text-gray-400 hover:text-gray-600 p-1 -mt-1 -mr-1 rounded-lg hover:bg-gray-100 transition-colors"
            >
              <XIcon class="w-4 h-4" />
            </button>
          </div>

          <div class="flex gap-3 mb-4">
            <div class="w-14 h-14 rounded-xl bg-gray-100 border border-gray-200 overflow-hidden shrink-0 flex items-center justify-center">
              <img
                v-if="getImageUrl(n.item.product.image)"
                :src="getImageUrl(n.item.product.image) || undefined"
                :alt="n.item.product.name"
                class="w-full h-full object-cover"
              />
              <ImageIcon v-else class="w-6 h-6 text-gray-300" />
            </div>
            <div class="flex-1 min-w-0 flex flex-col justify-center">
              <h5 class="text-sm font-bold text-gray-800 leading-tight truncate">
                {{ n.item.product.name }}
              </h5>
              <div class="flex items-center justify-between mt-1">
                <div class="flex items-center gap-1.5">
                  <del
                    v-if="Math.round(Number(n.item.product.price) * 100) > Math.round(n.item.unitPrice * 100)"
                    class="text-gray-400 text-xs font-semibold"
                  >
                    {{ formatPrice((Math.round(Number(n.item.product.price) * 100) * n.item.quantity) / 100) }}
                  </del>
                  <span class="text-blue-600 font-extrabold text-sm">
                    {{ formatPrice((Math.round(n.item.unitPrice * 100) * n.item.quantity) / 100) }}
                  </span>
                </div>
                <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-md">
                  Qty: {{ n.item.quantity }}
                </span>
              </div>
            </div>
          </div>

          <NuxtLink
            to="/cart"
            @click="notifications.remove(n.id)"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-2.5 rounded-xl text-sm flex items-center justify-center gap-2 transition-all shadow-md active:scale-[0.98]"
          >
            <ShoppingCartIcon class="w-4 h-4" />
            View Cart
          </NuxtLink>
        </div>

        <!-- Toast Notification -->
        <div
          v-else
          class="flex items-start gap-3.5 p-4 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border overflow-hidden relative backdrop-blur-xl"
          :class="{
            'bg-green-50/95 border-green-200/80': n.type === 'success',
            'bg-red-50/95 border-red-200/80': n.type === 'error',
            'bg-blue-50/95 border-blue-200/80': n.type === 'info',
          }"
        >
          <div class="shrink-0 mt-0.5">
            <CheckCircle2Icon v-if="n.type === 'success'" class="w-5 h-5 text-green-600" />
            <XCircleIcon v-else-if="n.type === 'error'" class="w-5 h-5 text-red-600" />
            <InfoIcon v-else class="w-5 h-5 text-blue-600" />
          </div>
          <div class="flex-1 pr-6">
            <h4 class="text-sm font-extrabold mb-0.5" :class="{
              'text-green-900': n.type === 'success',
              'text-red-900': n.type === 'error',
              'text-blue-900': n.type === 'info',
            }">
              {{ (n as any).message }}
            </h4>
            <p v-if="(n as any).description" class="text-[13px] font-semibold" :class="{
              'text-green-700': n.type === 'success',
              'text-red-700': n.type === 'error',
              'text-blue-700': n.type === 'info',
            }">
              {{ (n as any).description }}
            </p>
          </div>
          <button
            class="absolute top-3 right-3 p-1.5 rounded-xl opacity-60 hover:opacity-100 transition-all shrink-0 cursor-pointer"
            :class="{
              'hover:bg-green-200/60 text-green-800': n.type === 'success',
              'hover:bg-red-200/60 text-red-800': n.type === 'error',
              'hover:bg-blue-200/60 text-blue-800': n.type === 'info',
            }"
            @click="notifications.remove(n.id)"
          >
            <XIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import { useNotificationsStore } from '~/stores/notifications'
import { useProductImage } from '~/composables/useProductImage'
import {
  CheckCircle2Icon,
  XCircleIcon,
  InfoIcon,
  XIcon,
  ShoppingCartIcon,
  ImageIcon,
} from '@lucide/vue'

const notifications = useNotificationsStore()
const { getImageUrl } = useProductImage()

const formatPrice = (price: number) =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price)
</script>
