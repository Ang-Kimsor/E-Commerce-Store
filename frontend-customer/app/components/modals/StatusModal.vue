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
        <div v-if="modelValue" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm" @click="close" />
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
                <div class="p-3 rounded-full" :class="isSuccess ? 'bg-green-50' : 'bg-red-50'">
                  <CheckCircle2Icon v-if="isSuccess" class="w-10 h-10" :class="isSuccess ? 'text-green-500' : 'text-red-500'" />
                  <XCircleIcon v-else class="w-10 h-10" :class="isSuccess ? 'text-green-500' : 'text-red-500'" />
                </div>
              </div>

              <!-- Text Content -->
              <div class="space-y-1">
                <h3 class="text-[15.6px] font-extrabold text-slate-800">{{ title }}</h3>
                <p class="text-[11.5px] text-slate-500 font-medium">{{ message }}</p>
              </div>

              <!-- Action -->
              <div class="pt-2">
                <button 
                  @click="close" 
                  class="w-full px-4 py-2.5 text-[11.5px] font-bold text-white rounded-xl transition-all shadow-md outline-none focus:ring-2"
                  :class="isSuccess ? 'bg-green-600 hover:bg-green-700 active:scale-98 shadow-green-500/20 focus:ring-green-500/20' : 'bg-red-600 hover:bg-red-700 active:scale-98 shadow-red-500/20 focus:ring-red-500/20'"
                >
                  Okay
                </button>
              </div>

            </div>
          </div>
        </Transition>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { CheckCircle2Icon, XCircleIcon } from '@lucide/vue'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  title: { type: String, required: true },
  message: { type: String, required: true },
  isSuccess: { type: Boolean, default: true }
})

const emit = defineEmits(['update:modelValue', 'close'])

function close() {
  emit('update:modelValue', false)
  emit('close')
}
</script>
