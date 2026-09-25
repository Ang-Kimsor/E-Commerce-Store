<template>
  <div class="bg-white border border-slate-100 rounded-2xl shadow-sm p-4 flex flex-nowrap items-end gap-4 overflow-x-auto hide-scrollbar">
    <!-- Dynamic Filters Flex Slot -->
    <div 
      class="flex flex-nowrap items-end justify-end gap-3 w-max ml-auto transition-all duration-300"
      :class="{ 'opacity-60 pointer-events-none': loading }"
    >
      <slot />
    </div>

    <!-- Action Buttons & Export Row -->
    <div class="flex items-center gap-2 shrink-0 pl-4 border-l border-slate-100">
      <IconButton color="blue" label="Apply" :disabled="loading" @click="$emit('apply')">
        <FilterIcon class="w-3.5 h-3.5" />
      </IconButton>
      
      <IconButton color="slate" label="Reset" :disabled="loading" @click="$emit('reset')">
        <RotateCcwIcon class="w-3.5 h-3.5" />
      </IconButton>

    </div>
  </div>
</template>

<script setup lang="ts">
import { FilterIcon, RotateCcwIcon } from '@lucide/vue'
import IconButton from '~/components/ui/IconButton.vue'

defineProps<{
  loading?: boolean
}>()

defineEmits(['apply', 'reset'])
</script>
