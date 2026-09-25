<template>
  <div v-if="globalErrorStore.isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Overlay -->
    <div 
      class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" 
      @click="globalErrorStore.closeError()"
    ></div>

    <!-- Modal Panel -->
    <div class="relative w-full max-w-sm bg-white rounded-xl shadow-2xl p-6 overflow-hidden flex flex-col items-center text-center transform transition-all">
      <!-- Icon -->
      <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mb-4 text-red-600">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      
      <!-- Content -->
      <h3 class="text-[15.6px] font-extrabold text-slate-800 mb-1.5">Something went wrong</h3>
      <p class="text-[11.5px] text-slate-500 font-medium mb-5">Please try again later.</p>
      
      <!-- Actions -->
      <div class="w-full flex gap-3">
        <button 
          @click="globalErrorStore.closeError()" 
          class="flex-1 px-4 py-2.5 text-[11.5px] font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all outline-none focus:ring-2 focus:ring-slate-500/20"
        >
          Close
        </button>
        <button 
          @click="refreshPage" 
          class="flex-1 px-4 py-2.5 text-[11.5px] font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all shadow-md shadow-blue-500/20 outline-none focus:ring-2 focus:ring-blue-500/20"
        >
          Refresh
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useGlobalErrorStore } from '~/stores/globalError'

const globalErrorStore = useGlobalErrorStore()

function refreshPage() {
  if (typeof window !== 'undefined') {
    window.location.reload()
  }
}
</script>
