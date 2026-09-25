import { defineStore } from 'pinia'
import { useRuntimeConfig, useNuxtApp } from 'nuxt/app'
import { useGlobalErrorStore } from './globalError'
import type { ApiUser } from '../../types/api'

interface AuthState {
  user: ApiUser | null
  token: string | null
  isBootstrapped: boolean
  isLoading: boolean
  userRoleVerified: boolean
}

const TOKEN_KEY = 'admin_auth_token'

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: null,
    isBootstrapped: false,
    isLoading: false,
    userRoleVerified: false,
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
    isAdmin: (state) => {
      if (!state.userRoleVerified || !state.user) return false
      const isActive = Boolean(state.user.is_active ?? (state.user.status === 'active'))
      if (!isActive) return false
      const role = (state.user.role as any)
      const roleValue = String(role?.value ?? role).toLowerCase().trim()
      return roleValue === 'admin' || roleValue === 'superadmin'
    },
    isSuperAdmin: (state) => {
      if (!state.userRoleVerified || !state.user) return false
      const isActive = Boolean(state.user.is_active ?? (state.user.status === 'active'))
      if (!isActive) return false
      const role = (state.user.role as any)
      const roleValue = String(role?.value ?? role).toLowerCase().trim()
      return roleValue === 'superadmin'
    },
  },
  actions: {
    // Removed setupTokenWatcher since cookies are handled directly in login/logout
    async bootstrap() {
      if (this.isBootstrapped) {
        return
      }
      
      // IMPORTANT: With pinia-plugin-persistedstate, token is restored from localStorage
      // Check if we already have a token from persistence FIRST
      if (this.token) {
        try {
          const user = await this.fetchProfile()
        } catch (error: any) {
          console.error('⚠️ Could not fetch profile during bootstrap')
          const status = error?.statusCode || error?.status || error?.response?.status
          
          if (status === 401 || status === 403) {
            this.clearSession()
          }
        }
        
        this.isBootstrapped = true
        return
      }
      
      // No token from persistence, check legacy localStorage key as fallback
      let storedToken: string | null = null
      
      const tokenCookie = useCookie<string | null>(TOKEN_KEY)
      storedToken = tokenCookie.value

      if (storedToken) {
        this.token = storedToken
        
        try {
          const user = await this.fetchProfile()
        } catch (error: any) {
          console.error('⚠️ Could not fetch profile during bootstrap')
          
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

      // No automatic login — user must manually log in via /login page
      this.isBootstrapped = true
    },

    async login(login: string, password: string) {
      const config = useRuntimeConfig()
      this.isLoading = true
      try {
        const response = await $fetch<{ token: string; user: ApiUser }>(`${config.public.apiBase}/admin/auth/login`, {
          method: 'POST',
          body: { login, password },
          headers: {
            'Content-Type': 'application/json',
          },
        })

        const rawUser = response.user as any
        const isActive = rawUser.is_active !== undefined ? Boolean(rawUser.is_active) : rawUser.status === 'active'
        
        if (!isActive) {
          this.clearSession()
          throw new Error('Your account is inactive. Please contact an administrator.')
        }

        this.token = response.token
        this.user = {
          id: rawUser.id,
          name: rawUser.name,
          email: rawUser.email,
          avatar_url: rawUser.avatar_url,
          telegram_id: rawUser.telegram_id,
          role: String(rawUser.role?.value ?? rawUser.role ?? '').toLowerCase().trim(),
          is_active: isActive,
          status: rawUser.status || (isActive ? 'active' : 'inactive'),
        } as ApiUser
        this.userRoleVerified = true
        this.isBootstrapped = true
        
        const tokenCookie = useCookie<string | null>(TOKEN_KEY, { maxAge: 60 * 60 * 24 * 7 })
        tokenCookie.value = response.token
        return this.user
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

    async fetchProfile() {
      const config = useRuntimeConfig()
      const token = this.token

      if (!token) {
        console.warn('⚠️ No token available for fetchProfile')
        return null
      }

      try {
        const response = await $fetch<any>(`${config.public.apiBase}/me`, {
          method: 'GET',
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json',
            'ngrok-skip-browser-warning': '69420',
          },
        })

        let userData: any = null
        if (response.data?.id) {
          userData = response.data
        } else if (response.id) {
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
            avatar_url: userData.avatar_url,
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
      
      console.log('🔓 Session and storage cleared')
    },
    async logout() {
      const config = useRuntimeConfig()
      const token = this.token

      if (token) {
        try {
          await $fetch(`${config.public.apiBase}/admin/auth/logout`, {
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

  },
})
