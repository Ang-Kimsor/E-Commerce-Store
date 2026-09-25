import type { FetchOptions, ResponseType } from 'ofetch'
import { useRuntimeConfig } from 'nuxt/app'
import { useAuthStore } from '../stores/auth'
import { useGlobalErrorStore } from '../stores/globalError'

type ApiFetchMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE' | 'OPTIONS'
type ApiFetchOptions<R extends ResponseType = ResponseType> = Omit<FetchOptions<R>, 'method'> & { method?: ApiFetchMethod }



export async function useApiFetch<T = unknown, R extends ResponseType = ResponseType>(path: string, options: ApiFetchOptions<R> = {} as ApiFetchOptions<R>): Promise<T> {
  const config = useRuntimeConfig()
  const auth = useAuthStore()
  const globalError = useGlobalErrorStore()

  // Check if body is FormData FIRST
  const isFormData = options.body instanceof FormData

  const headers = new Headers((options.headers as HeadersInit | undefined) ?? undefined)

  if (auth.token) {
    headers.set('Authorization', `Bearer ${auth.token}`)
  }

  // Add ngrok bypass header
  headers.set('ngrok-skip-browser-warning', 'true')
  headers.set('Accept', 'application/json')

  // IMPORTANT: DO NOT set Content-Type for FormData - let browser set it with boundary
  if (!isFormData && options.method !== 'GET') {
    headers.set('Content-Type', 'application/json')
  }

  // Ensure proper path concatenation with forward slash
  const finalPath = path.startsWith('/') ? path : `/${path}`;
  
  const url = `${config.public.apiBase}${finalPath}`.replace(/([^:]\/)\/+/g, "$1");

  try {
    // For FormData, use native fetch instead of $fetch to ensure proper handling
    if (isFormData) {
      const headersObject: Record<string, string> = {}
      headers.forEach((value, key) => {
        // Completely omit Content-Type for FormData - browser will set it
        if (key.toLowerCase() !== 'content-type') {
          headersObject[key] = value
        }
      })
      
      const response = await fetch(url, {
        method: options.method || 'POST',
        headers: headersObject,
        body: options.body as FormData,
      })

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({ message: 'Upload failed' }))
        const error: any = new Error(errorData.message || `HTTP ${response.status}`)
        error.data = errorData
        error.status = response.status
        throw error
      }

      const jsonResponse = await response.json()
      

      return jsonResponse as T
    }

    // For regular requests, use $fetch
    const headersObject: Record<string, string> = {}
    headers.forEach((value, key) => {
      headersObject[key] = value
    })
    
    const requestOptions: FetchOptions<R> = {
      ...(options as FetchOptions<R>),
      method: options.method,
      headers: headersObject,
      timeout: options.timeout ?? 60000,
      retry: options.retry ?? 0, // Don't retry by default — 401/403 errors should not be retried
    }

    const response = await $fetch<T>(url, requestOptions as any)
    
    return response as T
  } catch (error: any) {
    console.error('❌ API request failed:', error)
    
    const status = error?.statusCode || error?.status || error?.response?.status

    // Sanitize raw server/SQL error messages early
    if (error) {
      const errMsg = String(error.data?.message || error.message || '');
      if (
        errMsg.includes('SQLSTATE') ||
        errMsg.includes('Connection refused') ||
        errMsg.includes('fetch failed') ||
        errMsg.includes('target machine actively refused it') ||
        status >= 500
      ) {
        const sanitizedMsg = 'An unexpected server error occurred. Please try again later.';
        if (error.data) {
          error.data.message = sanitizedMsg;
        }
        error.message = sanitizedMsg;
      }
    }
    
    // Check for 401 Unauthenticated or 403 Forbidden (e.g. Inactive Account)
    const msg = String(error?.data?.message || error?.message || '').toLowerCase()
    const isAuthEndpoint = path.includes('/admin/auth/login')

    if ((status === 401 || msg.includes('inactive')) && !isAuthEndpoint) {
      console.warn('🔒 Unauthenticated or Inactive — clearing session and redirecting to login')
      try {
        const auth = useAuthStore()
        auth.clearSession()
      } catch {}
      
      // Redirect to login page (works in both browser and Nuxt context)
      if (typeof window !== 'undefined' && !window.location.pathname.startsWith('/login')) {
        const currentPath = window.location.pathname
        window.location.href = `/login?session_expired=1&redirect=${encodeURIComponent(currentPath)}`
      }
    } else {
      // Show global error modal for non-auth errors
      try {
        const errorMsg = error?.data?.message || error?.message || 'Failed to fetch data from server.'
        globalError.showError(errorMsg)
      } catch (e) {
        console.error('Failed to show global error modal', e)
      }
    }
    
    throw error
  }
}
