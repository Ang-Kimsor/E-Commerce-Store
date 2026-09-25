import { defineNuxtRouteMiddleware, navigateTo } from 'nuxt/app'

function getToken(): string | null {
  // Use Nuxt's useCookie to get the token directly from the request cookies (works in SSR and Client)
  const tokenCookie = useCookie('admin_auth_token')
  return tokenCookie.value || null
}

export default defineNuxtRouteMiddleware((to) => {
  if (process.server) return

  const token = getToken()

  if (!token) {
    return navigateTo('/login')
  }

  // Token is present — allow navigation.
  // The auth plugin will fetch the user profile async after the page mounts.
  // The default layout does the role check once the plugin completes.
})
