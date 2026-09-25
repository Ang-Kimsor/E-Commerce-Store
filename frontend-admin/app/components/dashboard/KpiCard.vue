<template>
  <div class="group relative bg-white rounded-3xl border border-slate-100 p-5 overflow-hidden flex flex-col justify-between min-h-[120px] shadow-sm hover:shadow-xl hover:shadow-slate-200/60 hover:-translate-y-1 transition-all duration-300 cursor-default">
    <!-- Colored top accent bar -->
      <div class="absolute top-0 left-0 right-0 h-1 rounded-t-3xl" :class="accentClass"></div>

      <!-- Decorative background blob -->
      <div class="absolute -bottom-6 -right-6 w-20 h-20 rounded-full opacity-[0.07] blur-2xl transition-all duration-500 group-hover:opacity-[0.12]" :class="blobClass"></div>

      <!-- Top row: label + icon -->
      <div class="relative z-10 flex items-start justify-between">
        <p class="text-[8.2px] font-bold uppercase tracking-[0.15em] text-slate-400">{{ label }}</p>
        <div class="w-9 h-9 rounded-2xl flex items-center justify-center flex-shrink-0 border group-hover:scale-110 group-hover:-rotate-6 transition-all duration-300" :class="iconClass">
          <component :is="icon" class="w-4 h-4" />
        </div>
      </div>

      <div class="relative z-10 my-2 min-h-[28px] flex items-center">
        <p class="text-[22.6px] font-black tracking-tight leading-none truncate text-slate-800">{{ value }}</p>
      </div>

      <!-- Detail badge -->
      <div class="relative z-10 flex items-center gap-2">
        <span v-if="detail" class="inline-flex items-center gap-1.5 text-[8.2px] font-bold uppercase tracking-widest px-1.5 py-0.5 rounded-full border" :class="badgeClass">
          <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" :class="dotClass"></span>
          {{ detail }}
        </span>
        <span v-if="detail2" class="inline-flex items-center gap-1.5 text-[8.2px] font-bold uppercase tracking-widest px-1.5 py-0.5 rounded-full border bg-rose-50 border-rose-100 text-rose-600">
          <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 bg-rose-400"></span>
          {{ detail2 }}
        </span>
        <span v-if="!detail && !detail2" class="h-4"></span>
      </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Component } from 'vue'

const props = defineProps<{
  icon: Component | string | object
  label: string
  value: string | number
  bgClass: string
  detail?: string
  detail2?: string
}>()

const accentClass = computed(() => {
  if (props.bgClass.includes('blue'))   return 'bg-gradient-to-r from-blue-400 to-blue-600'
  if (props.bgClass.includes('green'))  return 'bg-gradient-to-r from-emerald-400 to-emerald-600'
  if (props.bgClass.includes('purple')) return 'bg-gradient-to-r from-violet-400 to-violet-600'
  if (props.bgClass.includes('orange')) return 'bg-gradient-to-r from-orange-400 to-orange-600'
  if (props.bgClass.includes('indigo')) return 'bg-gradient-to-r from-indigo-400 to-indigo-600'
  if (props.bgClass.includes('rose'))   return 'bg-gradient-to-r from-rose-400 to-rose-600'
  return 'bg-gradient-to-r from-slate-400 to-slate-600'
})

const blobClass = computed(() => {
  if (props.bgClass.includes('blue'))   return 'bg-blue-500'
  if (props.bgClass.includes('green'))  return 'bg-emerald-500'
  if (props.bgClass.includes('purple')) return 'bg-violet-500'
  if (props.bgClass.includes('orange')) return 'bg-orange-500'
  if (props.bgClass.includes('indigo')) return 'bg-indigo-500'
  if (props.bgClass.includes('rose'))   return 'bg-rose-500'
  return 'bg-slate-500'
})

const iconClass = computed(() => props.bgClass)

const badgeClass = computed(() => {
  if (props.bgClass.includes('blue'))   return 'bg-blue-50 border-blue-100 text-blue-600'
  if (props.bgClass.includes('green'))  return 'bg-emerald-50 border-emerald-100 text-emerald-600'
  if (props.bgClass.includes('purple')) return 'bg-violet-50 border-violet-100 text-violet-600'
  if (props.bgClass.includes('orange')) return 'bg-orange-50 border-orange-100 text-orange-600'
  if (props.bgClass.includes('indigo')) return 'bg-indigo-50 border-indigo-100 text-indigo-600'
  if (props.bgClass.includes('rose'))   return 'bg-rose-50 border-rose-100 text-rose-600'
  return 'bg-slate-50 border-slate-100 text-slate-600'
})

const dotClass = computed(() => {
  if (props.bgClass.includes('blue'))   return 'bg-blue-400'
  if (props.bgClass.includes('green'))  return 'bg-emerald-400'
  if (props.bgClass.includes('purple')) return 'bg-violet-400'
  if (props.bgClass.includes('orange')) return 'bg-orange-400'
  if (props.bgClass.includes('indigo')) return 'bg-indigo-400'
  if (props.bgClass.includes('rose'))   return 'bg-rose-400'
  return 'bg-slate-400'
})
</script>
