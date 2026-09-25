import { defineNuxtRouteMiddleware, navigateTo, useCookie } from 'nuxt/app'

function getToken(): string | null {
  // Use Nuxt's useCookie to get the token directly from the request cookies (works in SSR and Client)
  const tokenCookie = useCookie('customer_auth_token')
  return tokenCookie.value || null
}

export default defineNuxtRouteMiddleware((to) => {
  if (process.server) return

  const token = getToken()

  if (!token) {
    return navigateTo({ path: '/login', query: { redirect: to.fullPath } })
  }

  // Token is present — allow navigation.
  // The auth plugin will fetch the user profile async after the page mounts.
})
