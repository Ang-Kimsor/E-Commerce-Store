<template>
  <Teleport to="body">
    <div v-if="modelValue" class="relative z-[70]">
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
      <div class="fixed inset-0 overflow-hidden pointer-events-none z-[70] flex items-center justify-center p-4">
        <Transition
          enter-active-class="transform transition ease-out duration-300"
          enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          enter-to-class="opacity-100 translate-y-0 sm:scale-100"
          leave-active-class="transform transition ease-in duration-200"
          leave-from-class="opacity-100 translate-y-0 sm:scale-100"
          leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          @after-enter="startAutoClose"
        >
          <div v-if="modelValue" class="pointer-events-auto w-full max-w-sm">
            <div class="flex flex-col bg-white shadow-xl rounded-2xl overflow-hidden text-center p-6 space-y-4">
              
              <!-- Icon -->
              <div class="flex justify-center">
                <div class="p-3 rounded-full" :class="iconContainerClass">
                  <CheckCircle2Icon v-if="type === 'success'" class="w-10 h-10" :class="iconClass" />
                  <XCircleIcon v-else-if="type === 'error'" class="w-10 h-10" :class="iconClass" />
                  <InfoIcon v-else class="w-10 h-10" :class="iconClass" />
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
                  :class="buttonClass"
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
import { computed, watch } from 'vue'
import { CheckCircle2Icon, XCircleIcon, InfoIcon } from '@lucide/vue'

const props = withDefaults(defineProps<{
  modelValue: boolean
  type?: 'success' | 'error' | 'info'
  title: string
  message: string
  autoClose?: boolean
  autoCloseDelay?: number
}>(), {
  type: 'success',
  autoClose: true,
  autoCloseDelay: 3000
})

const emit = defineEmits(['update:modelValue'])
let timeoutId: any = null

function close() {
  if (timeoutId) clearTimeout(timeoutId)
  emit('update:modelValue', false)
}

function startAutoClose() {
  if (props.autoClose && props.modelValue) {
    if (timeoutId) clearTimeout(timeoutId)
    timeoutId = setTimeout(() => {
      close()
    }, props.autoCloseDelay)
  }
}

watch(() => props.modelValue, (newVal) => {
  if (!newVal && timeoutId) {
    clearTimeout(timeoutId)
  }
})

const iconContainerClass = computed(() => {
  switch (props.type) {
    case 'success': return 'bg-green-50'
    case 'error': return 'bg-red-50'
    case 'info': return 'bg-blue-50'
    default: return 'bg-slate-50'
  }
})

const iconClass = computed(() => {
  switch (props.type) {
    case 'success': return 'text-green-500'
    case 'error': return 'text-red-500'
    case 'info': return 'text-blue-500'
    default: return 'text-slate-500'
  }
})

const buttonClass = computed(() => {
  switch (props.type) {
    case 'success': return 'bg-green-600 hover:bg-green-700 active:scale-98 shadow-green-500/20 focus:ring-green-500/20'
    case 'error': return 'bg-red-600 hover:bg-red-700 active:scale-98 shadow-red-500/20 focus:ring-red-500/20'
    case 'info': return 'bg-blue-600 hover:bg-blue-700 active:scale-98 shadow-blue-500/20 focus:ring-blue-500/20'
    default: return 'bg-slate-800 hover:bg-slate-900 active:scale-98 shadow-slate-500/20 focus:ring-slate-500/20'
  }
})
</script>
