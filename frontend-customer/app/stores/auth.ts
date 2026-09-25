import { defineStore } from 'pinia'
import { useRuntimeConfig, useNuxtApp } from 'nuxt/app'
import { useGlobalErrorStore } from './globalError'
import type { ApiUser } from '../../types/api'
import { useCartStore } from './cart'

interface AuthState {
  user: ApiUser | null
  token: string | null
  isBootstrapped: boolean
  isLoading: boolean
  userRoleVerified: boolean
  pendingOtpEmail: string | null
  pendingOtpPurpose: 'login' | 'register' | 'reset_password' | null
}

const TOKEN_KEY = 'customer_auth_token'

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: null,
    isBootstrapped: false,
    isLoading: false,
    userRoleVerified: false,
    pendingOtpEmail: null,
    pendingOtpPurpose: null,
  }),
  
  // Persistence is now managed manually via cookies in the bootstrap/login/logout actions
  
  getters: {
    isActive: (state) => {
      if (!state.user) return false
      return Boolean(state.user.is_active ?? (state.user.status === 'active'))
    },
    isAuthenticated: (state) => {
      if (!state.token || !state.user) return false
      return Boolean(state.user.is_active ?? (state.user.status === 'active'))
    },

  },
  actions: {
    // Removed setupTokenWatcher since cookies are handled directly in login/logout
    async bootstrap() {
      if (this.isBootstrapped) return

      if (this.token) {
        try {
          await this.fetchProfile()
        } catch (error: any) {
          const status = error?.statusCode || error?.status || error?.response?.status
          if (status === 401 || status === 403) {
            this.clearSession()
          }
        }
        this.isBootstrapped = true
        return
      }
      
      const tokenCookie = useCookie<string | null>(TOKEN_KEY)
      const storedToken = tokenCookie.value

      if (storedToken) {
        this.token = storedToken
        try {
          await this.fetchProfile()
        } catch (error: any) {
          const status = error?.statusCode || error?.status || error?.response?.status
          if (status === 401 || status === 403) {
            this.clearSession()
            this.isBootstrapped = true
            return
          }
        }
        
        this.isBootstrapped = true
        return
      }

      this.isBootstrapped = true
    },

    async login(login: string, password: string) {
      const config = useRuntimeConfig()
      this.isLoading = true
      try {
        const response = await $fetch<any>(`${config.public.apiBase}/customer/auth/login`, {
          method: 'POST',
          body: { email: login, password },
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
        })

        // Instead of setting token, return the response so the UI can redirect to verify-otp
        return response
      } catch (error: any) {
        const errMsg = String(error?.data?.message || error?.message || '');
        if (
          errMsg.includes('SQLSTATE') ||
          errMsg.includes('Connection refused') ||
          errMsg.includes('fetch failed') ||
          errMsg.includes('target machine actively refused it')
        ) {
          const sanitizedMsg = 'An unexpected server error occurred. Please try again later.';
          if (error.data) {
            error.data.message = sanitizedMsg;
          } else {
            error.message = sanitizedMsg;
          }
        }
        throw error;
      } finally {
        this.isLoading = false
      }
    },
    async register(data: any) {
      const config = useRuntimeConfig()

      this.isLoading = true
      try {
        const response = await $fetch<any>(`${config.public.apiBase}/customer/auth/register/send-otp`, {
          method: 'POST',
          body: data,
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
        })

        // Just return response so UI can redirect to verify-otp
        return response
      } catch (error: any) {
        const errMsg = String(error?.data?.message || error?.message || '');
        if (
          errMsg.includes('SQLSTATE') ||
          errMsg.includes('Connection refused') ||
          errMsg.includes('fetch failed') ||
          errMsg.includes('target machine actively refused it')
        ) {
          const sanitizedMsg = 'An unexpected server error occurred. Please try again later.';
          if (error.data) {
            error.data.message = sanitizedMsg;
          } else {
            error.message = sanitizedMsg;
          }
        }
        throw error;
      } finally {
        this.isLoading = false
      }
    },

    async verifyLoginOtp(email: string, otp: string) {
      const config = useRuntimeConfig()
      this.isLoading = true
      try {
        const response = await $fetch<{ token: string; user: ApiUser }>(`${config.public.apiBase}/customer/auth/login/verify-otp`, {
          method: 'POST',
          body: { email, otp },
          headers: { 'Accept': 'application/json' },
        })

        this.token = response.token
        const tokenCookie = useCookie<string | null>('customer_auth_token', { maxAge: 60 * 60 * 24 * 7 })
        tokenCookie.value = response.token
        
        await this.fetchProfile()
        return this.user
      } catch (error) {
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async verifyRegisterOtp(email: string, otp: string) {
      const config = useRuntimeConfig()
      this.isLoading = true
      try {
        const response = await $fetch<{ token: string; user: ApiUser }>(`${config.public.apiBase}/customer/auth/register/verify-otp`, {
          method: 'POST',
          body: { email, otp },
          headers: { 'Accept': 'application/json' },
        })

        this.token = response.token
        const tokenCookie = useCookie<string | null>('customer_auth_token', { maxAge: 60 * 60 * 24 * 7 })
        tokenCookie.value = response.token
        
        await this.fetchProfile()
        return this.user
      } catch (error) {
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async sendForgotPasswordOtp(email: string) {
      const config = useRuntimeConfig()
      this.isLoading = true
      try {
        const response = await $fetch<any>(`${config.public.apiBase}/customer/auth/forgot-password/send-otp`, {
          method: 'POST',
          body: { email },
          headers: { 'Accept': 'application/json' },
        })
        return response
      } catch (error) {
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async resendOtp(email: string, purpose: string) {
      const config = useRuntimeConfig()
      this.isLoading = true
      try {
        const response = await $fetch<any>(`${config.public.apiBase}/customer/auth/resend-otp`, {
          method: 'POST',
          body: { email, purpose },
          headers: { 'Accept': 'application/json' },
        })
        return response
      } catch (error) {
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async resendProfileOtp(purpose: string) {
      const config = useRuntimeConfig()
      const token = this.token
      this.isLoading = true
      try {
        const response = await $fetch<any>(`${config.public.apiBase}/customer/profile/resend-otp`, {
          method: 'POST',
          headers: { Authorization: `Bearer ${token}`, 'Accept': 'application/json' },
          body: { purpose },
        })
        return response
      } catch (error) {
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async verifyForgotPasswordOtp(email: string, otp: string) {
      const config = useRuntimeConfig()
      this.isLoading = true
      try {
        const response = await $fetch<{ reset_token: string }>(`${config.public.apiBase}/customer/auth/forgot-password/verify-otp`, {
          method: 'POST',
          body: { email, otp },
          headers: { 'Accept': 'application/json' },
        })
        return response.reset_token
      } catch (error) {
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async fetchProfile() {
      const config = useRuntimeConfig()
      const token = this.token

      if (!token) {
        console.warn('⚠️ No token available for fetchProfile')
        return null
      }

      try {

        const response = await $fetch<any>(`${config.public.apiBase}/customer/auth/me`, {
          method: 'GET',
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'ngrok-skip-browser-warning': '69420',
          },
        })



        // Support both response formats:
        // 1. Raw user object: { id, name, email, role, ... }  ← what Laravel /me actually returns
        // 2. Wrapped format: { success: true, data: { id, name, ... } }
        let userData: any = null
        if (response.success && response.data) {
          userData = response.data
        } else if (response.id) {
          // Raw user object returned directly
          userData = response
        }

        if (userData) {
          const isActive = userData.is_active !== undefined ? Boolean(userData.is_active) : userData.status === 'active'

          if (!isActive) {
            console.warn('🔒 Account is inactive, clearing session')
            this.clearSession()
            throw new Error('Your account is inactive. Please contact an administrator.')
          }

          this.user = {
            id: userData.id,
            name: userData.name,
            email: userData.email,
            phone: userData.phone ?? null,
            avatar_url: userData.avatar_url ?? null,
            telegram_id: userData.telegram_id,
            role: String(userData.role?.value ?? userData.role ?? '').toLowerCase().trim(),
            is_active: isActive,
            status: userData.status || (isActive ? 'active' : 'inactive'),
          } as ApiUser
          this.userRoleVerified = true

          return this.user
        } else {
          this.userRoleVerified = false
          console.error('❌ Unexpected /me response format:', response)
          throw new Error('Failed to fetch profile')
        }
      } catch (error: any) {
        this.userRoleVerified = false
        console.error('❌ fetchProfile error:', error?.message || error)
        
        const status = error?.statusCode || error?.status || error?.response?.status
        if (status !== 401 && status !== 403) {
          try {
            const globalError = useGlobalErrorStore()
            const errorMsg = error?.data?.message || error?.message || 'Failed to fetch profile from server.'
            globalError.showError(errorMsg)
          } catch (e) {
            console.error('Failed to show global error modal', e)
          }
        }
        
        throw error
      }
    },
    clearSession(): void {
      this.user = null
      this.token = null
      this.userRoleVerified = false
      
      // Clear from cookie
      try {
        const tokenCookie = useCookie<string | null>(TOKEN_KEY)
        tokenCookie.value = null
      } catch {}

      // Clear from localStorage & sessionStorage (pinia-plugin-persistedstate)
      if (typeof window !== 'undefined') {
        try {
          window.localStorage?.removeItem(TOKEN_KEY)
          window.localStorage?.removeItem('auth')
          if (window.localStorage) {
            Object.keys(window.localStorage).forEach((key) => {
              if (key.includes('auth') || key.includes('afc') || key.includes('token')) {
                window.localStorage.removeItem(key)
              }
            })
          }
          if (window.sessionStorage) {
            Object.keys(window.sessionStorage).forEach((key) => {
              if (key.includes('auth') || key.includes('afc') || key.includes('token')) {
                window.sessionStorage.removeItem(key)
              }
            })
          }
        } catch (e) {
          console.warn('Failed to clear storage:', e)
        }
      }
      
      // Clear cart when logging out to prevent unauthorized orders
      try {
        const cartStore = useCartStore()
        cartStore.clear()
      } catch {}
      
      console.log('🔓 Session and storage cleared')
    },
    async logout() {
      const config = useRuntimeConfig()
      const token = this.token

      if (token) {
        try {
          await $fetch(`${config.public.apiBase}/customer/auth/logout`, {
            method: 'POST',
            headers: {
              Authorization: `Bearer ${token}`,
              'ngrok-skip-browser-warning': '69420',
            },
          })
        } catch (error) {
          console.error('Logout request failed', error)
        }
      }

      this.clearSession()
    },
    async deleteAccount() {
      const config = useRuntimeConfig()
      const token = this.token

      if (token) {
        try {
          await $fetch(`${config.public.apiBase}/customer/profile`, {
            method: 'DELETE',
            headers: {
              Authorization: `Bearer ${token}`,
              'ngrok-skip-browser-warning': '69420',
            },
          })
        } catch (error) {
          console.error('Delete account request failed', error)
          throw error
        }
      }

      this.clearSession()
    },
    async getOtpStatus(email: string, purpose: string) {
      const config = useRuntimeConfig()
      try {
        const response = await $fetch<any>(`${config.public.apiBase}/customer/auth/otp-status`, {
          method: 'GET',
          query: { email, purpose },
        })
        return response
      } catch (error) {
        throw error
      }
    },
  },
})
