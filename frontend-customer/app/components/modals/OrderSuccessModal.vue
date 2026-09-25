<template>
  <Teleport to="body">
    <div v-if="modelValue" class="relative z-[100]">
      <!-- Backdrop -->
      <Transition
        enter-active-class="transition-opacity ease-linear duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity ease-linear duration-300"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="modelValue" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm" />
      </Transition>

      <!-- Panel container -->
      <div class="fixed inset-0 overflow-hidden pointer-events-none z-[100] flex items-center justify-center p-4">
        <Transition
          enter-active-class="transform transition ease-out duration-300"
          enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          enter-to-class="opacity-100 translate-y-0 sm:scale-100"
          leave-active-class="transform transition ease-in duration-200"
          leave-from-class="opacity-100 translate-y-0 sm:scale-100"
          leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
          <div v-if="modelValue" class="pointer-events-auto w-full max-w-sm">
            <div class="flex flex-col bg-white shadow-xl rounded-2xl overflow-hidden text-center p-6 space-y-4">
              
              <!-- Icon -->
              <div class="flex justify-center">
                <div class="p-3 rounded-full bg-green-50">
                  <CheckCircle2Icon class="w-10 h-10 text-green-500" />
                </div>
              </div>

              <!-- Text Content -->
              <div class="space-y-1">
                <h3 class="text-[15.6px] font-extrabold text-slate-800">Order Placed!</h3>
                <p class="text-[11.5px] text-slate-500 font-medium">Your order <span class="font-bold text-slate-800">#{{ orderNumber }}</span> has been placed successfully.</p>
              </div>

              <!-- Action -->
              <div class="pt-2 flex flex-col gap-2">
                <NuxtLink 
                  to="/orders" 
                  class="w-full px-4 py-2.5 text-[11.5px] font-bold text-white bg-blue-600 hover:bg-blue-700 active:scale-98 shadow-md shadow-blue-500/20 outline-none focus:ring-2 focus:ring-blue-500/20 rounded-xl transition-all"
                >
                  View Orders
                </NuxtLink>
                <NuxtLink 
                  to="/" 
                  class="w-full px-4 py-2.5 text-[11.5px] font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all outline-none focus:ring-2 focus:ring-slate-500/20"
                >
                  Continue Shopping
                </NuxtLink>
              </div>

            </div>
          </div>
        </Transition>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { CheckCircle2Icon } from '@lucide/vue'

defineProps({
  modelValue: { type: Boolean, required: true },
  orderNumber: { type: String, required: true }
})
</script>
