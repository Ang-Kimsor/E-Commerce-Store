<template>
  <div v-if="totalPages > 1" class="w-full flex items-center justify-end my-2 sm:my-3">
    <!-- Single Capsule Pill Bar (Full Width on Mobile, Right-Aligned on Desktop) -->
    <div class="w-full sm:w-auto flex sm:inline-flex items-center justify-between gap-1 sm:gap-4 bg-white border border-gray-200/90 rounded-full px-2.5 sm:px-8 py-2 sm:py-3 shadow-xs text-xs sm:text-sm font-semibold sm:ml-auto">
      
      <!-- Previous Link -->
      <button 
        @click="$emit('change', currentPage - 1)" 
        :disabled="currentPage === 1"
        class="flex items-center gap-0.5 sm:gap-1.5 font-bold text-gray-700 hover:text-blue-600 disabled:opacity-30 disabled:hover:text-gray-700 cursor-pointer disabled:cursor-not-allowed transition-colors py-1 px-1 select-none text-xs sm:text-sm shrink-0"
      >
        <ChevronLeftIcon class="w-3.5 h-3.5 sm:w-5 sm:h-5" />
        <span>Previous</span>
      </button>

      <!-- Page Numbers List -->
      <div class="flex items-center gap-0.5 sm:gap-2 overflow-x-auto py-1.5 px-1 scrollbar-none">
        <template v-for="(page, index) in pageNumbers" :key="index">
          <span v-if="page === '...'" class="px-0.5 text-gray-400 font-bold select-none text-xs sm:text-sm">...</span>
          <button 
            v-else
            @click="$emit('change', page)"
            class="w-7 h-7 sm:w-9 sm:h-9 min-w-[28px] min-h-[28px] rounded-full flex items-center justify-center font-bold text-xs sm:text-sm transition-all cursor-pointer select-none shrink-0"
            :class="page === currentPage 
              ? 'bg-blue-600 text-white shadow-xs font-extrabold scale-105' 
              : 'text-gray-700 hover:text-blue-600 hover:bg-blue-50'"
          >
            {{ page }}
          </button>
        </template>
      </div>

      <!-- Next Link -->
      <button 
        @click="$emit('change', currentPage + 1)" 
        :disabled="currentPage === totalPages"
        class="flex items-center gap-0.5 sm:gap-1.5 font-bold text-gray-700 hover:text-blue-600 disabled:opacity-30 disabled:hover:text-gray-700 cursor-pointer disabled:cursor-not-allowed transition-colors py-1 px-1 select-none text-xs sm:text-sm shrink-0"
      >
        <span>Next</span>
        <ChevronRightIcon class="w-3.5 h-3.5 sm:w-5 sm:h-5" />
      </button>

      <!-- Right Divider & Results Counter -->
      <div v-if="totalItems !== undefined && totalItems > 0" class="hidden lg:flex items-center pl-4 border-l border-gray-200 text-gray-500 font-medium whitespace-nowrap text-xs sm:text-sm">
        <div v-if="isLoading" class="h-4 w-32 bg-gray-200 rounded animate-pulse"></div>
        <template v-else>
          Showing 
          <span v-if="perPage" class="font-extrabold text-gray-900 mx-1">
            {{ Math.min((currentPage - 1) * perPage + 1, totalItems) }}-{{ Math.min(currentPage * perPage, totalItems) }}
          </span>
          <span v-else class="font-extrabold text-gray-900 mx-1">
            {{ showingCount !== undefined ? showingCount : totalItems }}
          </span>
          of <span class="font-extrabold text-gray-900 mx-1">{{ formattedTotalItems }}</span> results
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { ChevronLeftIcon, ChevronRightIcon } from '@lucide/vue'

const props = defineProps<{
  currentPage: number
  totalPages: number
  totalItems?: number
  showingCount?: number
  perPage?: number
  isLoading?: boolean
}>()

defineEmits(['change'])

const formattedTotalItems = computed(() => {
  if (!props.totalItems) return '0'
  return props.totalItems.toLocaleString()
})

const pageNumbers = computed(() => {
  const pages: (number | string)[] = []
  
  if (props.totalPages <= 7) {
    for (let i = 1; i <= props.totalPages; i++) {
      pages.push(i)
    }
    return pages
  }

  if (props.currentPage <= 4) {
    pages.push(1, 2, 3, 4, 5, '...', props.totalPages)
  } else if (props.currentPage >= props.totalPages - 3) {
    pages.push(1, '...', props.totalPages - 4, props.totalPages - 3, props.totalPages - 2, props.totalPages - 1, props.totalPages)
  } else {
    pages.push(1, '...', props.currentPage - 1, props.currentPage, props.currentPage + 1, '...', props.totalPages)
  }

  return pages
})
</script>
