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
        <div v-if="modelValue" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm" @click="cancel" />
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
            <div class="flex flex-col bg-white shadow-2xl rounded-2xl overflow-hidden text-center p-6 space-y-4">
              
              <!-- Icon -->
              <div class="flex justify-center">
                <div class="p-3 rounded-full" :class="iconBgClass">
                  <component :is="icon" class="w-8 h-8" />
                </div>
              </div>

              <!-- Text Content -->
              <div class="space-y-1">
                <h3 class="text-[15.6px] font-extrabold text-slate-800">{{ title }}</h3>
                <p class="text-[11.5px] text-slate-500 font-medium">{{ message }}</p>
              </div>

              <!-- Actions -->
              <div class="flex items-center gap-3 pt-2">
                <button 
                  @click="cancel" 
                  class="flex-1 px-4 py-2.5 text-[11.5px] font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all outline-none focus:ring-2 focus:ring-slate-500/20"
                >
                  Cancel
                </button>
                <button 
                  @click="confirm" 
                  class="flex-1 px-4 py-2.5 text-[11.5px] font-bold text-white rounded-xl transition-all shadow-md outline-none focus:ring-2"
                  :class="confirmBtnClass"
                  :disabled="isLoading"
                >
                  <span v-if="isLoading" class="flex items-center justify-center gap-2">
                    <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                    Processing...
                  </span>
                  <span v-else>{{ confirmText }}</span>
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
import { AlertTriangleIcon } from '@lucide/vue'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  title: { type: String, required: true },
  message: { type: String, required: true },
  confirmText: { type: String, default: 'Confirm' },
  icon: { type: [Object, Function], default: () => AlertTriangleIcon },
  iconBgClass: { type: String, default: 'bg-red-50 text-red-500' },
  confirmBtnClass: { type: String, default: 'bg-red-600 hover:bg-red-700 active:scale-98 shadow-red-500/20 focus:ring-red-500/20' },
  isLoading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel'])

function cancel() {
  if (props.isLoading) return
  emit('update:modelValue', false)
  emit('cancel')
}

function confirm() {
  if (props.isLoading) return
  emit('confirm')
}
</script>
