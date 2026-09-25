<template>
  <Teleport to="body">
    <div v-if="modelValue" class="relative z-50">
      <!-- Backdrop -->
      <Transition
        enter-active-class="transition-opacity ease-linear duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity ease-linear duration-300"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="modelValue" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm" @click="$emit('update:modelValue', false)" />
      </Transition>

      <!-- Panel container -->
      <div class="fixed inset-0 overflow-hidden pointer-events-none z-50 flex items-center justify-center p-4">
        <Transition
          enter-active-class="transform transition ease-out duration-300"
          enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          enter-to-class="opacity-100 translate-y-0 sm:scale-100"
          leave-active-class="transform transition ease-in duration-200"
          leave-from-class="opacity-100 translate-y-0 sm:scale-100"
          leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
          <div v-if="modelValue" class="pointer-events-auto w-full" :class="sizeClass">
            <div class="flex flex-col bg-white shadow-2xl rounded-2xl overflow-hidden max-h-[90vh]">
              <!-- Header -->
              <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
                <h2 class="text-[15.4px] font-extrabold text-slate-800 flex items-center gap-2">
                  <slot name="title">{{ title }}</slot>
                </h2>
                <IconButton
                  color="slate"
                  title="Close Modal"
                  @click="$emit('update:modelValue', false)"
                >
                  <XIcon class="w-5 h-5" />
                </IconButton>
              </div>

              <!-- Content -->
              <div class="flex-1 overflow-y-auto p-6">
                <slot />
              </div>

              <!-- Footer -->
              <div v-if="$slots.footer" class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex-shrink-0">
                <slot name="footer" />
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { XIcon } from '@lucide/vue'
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  modelValue: boolean
  title?: string
  size?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl' | '5xl' | '6xl' | '7xl' | 'full'
}>(), {
  size: 'lg'
})

defineEmits(['update:modelValue'])

const sizeClass = computed(() => {
  const sizes: Record<string, string> = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
    '4xl': 'max-w-4xl',
    '5xl': 'max-w-5xl',
    '6xl': 'max-w-6xl',
    '7xl': 'max-w-7xl',
    full: 'max-w-full mx-4',
  }
  return sizes[props.size] || 'max-w-lg'
})
</script>
