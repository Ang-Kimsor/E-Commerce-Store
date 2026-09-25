import { defineNuxtRouteMiddleware, navigateTo } from 'nuxt/app'

export default defineNuxtRouteMiddleware(() => {
  if (process.server) return

  const tokenCookie = useCookie('admin_auth_token')
  if (!tokenCookie.value) {
    return navigateTo('/login')
  }

  const auth = useAuthStore()

  // If auth is not bootstrapped yet, let the page mount and handle it there
  if (!auth.isBootstrapped) return

  // If bootstrapped and role is verified, enforce superadmin only
  if (auth.userRoleVerified && !auth.isSuperAdmin) {
    console.warn('🚫 Superadmin middleware: Access denied — not a superadmin')
    return navigateTo('/')
  }
})
