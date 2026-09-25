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
      <h3 class="text-xl font-bold text-slate-800 mb-2">Request Failed</h3>
      <p class="text-slate-600 mb-6">{{ globalErrorStore.message }}</p>
      
      <!-- Actions -->
      <div class="w-full flex gap-3">
        <button 
          @click="globalErrorStore.closeError()" 
          class="flex-1 px-4 py-2 bg-slate-100 text-slate-700 font-medium rounded-lg hover:bg-slate-200 transition-colors"
        >
          Close
        </button>
        <button 
          @click="refreshPage" 
          class="flex-1 px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm"
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
