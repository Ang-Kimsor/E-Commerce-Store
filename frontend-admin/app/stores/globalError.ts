import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useGlobalErrorStore = defineStore('globalError', () => {
  const isOpen = ref(false)
  const message = ref('')

  function showError(msg: string) {
    message.value = msg || 'An unexpected error occurred while communicating with the server.'
    isOpen.value = true
  }

  function closeError() {
    isOpen.value = false
    message.value = ''
  }

  return {
    isOpen,
    message,
    showError,
    closeError
  }
})
