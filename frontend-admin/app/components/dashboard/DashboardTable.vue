<template>
  <div class="h-full bg-white border border-slate-100 rounded-2xl p-5 shadow-sm space-y-4 overflow-hidden flex flex-col">
    <div class="flex items-start justify-between border-b border-slate-50 pb-4">
      <div>
        <h2 class="text-[13.6px] font-extrabold text-slate-800 flex items-center gap-2">
          <slot name="icon"></slot>
          {{ title }}
        </h2>
        <p class="text-[10px] text-slate-500 mt-1 pl-7">{{ description }}</p>
      </div>
    </div>
    
    <div v-if="!items || !items.length" class="flex-1 min-h-[120px] flex flex-col items-center justify-center py-8 text-slate-400">
      <div class="flex justify-center mb-2">
        <slot name="empty-icon"><FolderOpenIcon class="w-8 h-8 text-slate-300" /></slot>
      </div>
      <p class="font-bold text-[10px] text-slate-500">{{ emptyMessage || 'No data found' }}</p>
    </div>
    
    <div v-else class="overflow-x-auto flex-1 -mx-5">
      <table class="w-full text-left border-collapse" :class="tableClass">
        <thead>
          <tr class="border-b border-slate-100 text-[9.1px] text-slate-500">
            <slot name="header"></slot>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 text-[10.9px]">
          <tr v-for="(item, i) in items" :key="item.id || item.product_id || i" class="hover:bg-slate-50 transition-colors ">
            <slot name="row" :item="item"></slot>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { FolderOpenIcon } from '@lucide/vue'

defineProps<{
  title: string
  description: string
  items: any[]
  viewAllLink?: string
  emptyMessage?: string
  tableClass?: string
}>()
</script>
