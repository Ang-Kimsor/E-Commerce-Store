import { inject } from 'vue'

interface ToastMethods {
  success: (message: string, description?: string) => void
  error: (message: string, description?: string) => void
  info: (message: string, description?: string) => void
}

export function useToast(): ToastMethods {
  const toast = inject<ToastMethods>('toast')
  
  if (!toast) {
    // Fallback if toast is not provided
    console.warn('Toast not available')
    return {
      success: (message) => console.log('Success:', message),
      error: (message) => console.error('Error:', message),
      info: (message) => console.info('Info:', message),
    }
  }
  
  return toast
}
