import { useSettingsStore } from '../stores/settings'

export default defineNuxtRouteMiddleware(async (to, from) => {
  const settingsStore = useSettingsStore()
  
  if (!settingsStore.isLoaded) {
    await settingsStore.fetchSettings()
  }

  // Use the cached setting from the store to avoid blocking navigation
  const val = settingsStore.getSetting('maintenance_mode')
  const isMaintenance = val === 'true' || val === '1'
  
  // Check if the setting exists and is set to true/1
  if (isMaintenance && to.path !== '/maintenance') {
    return navigateTo('/maintenance')
  }
  
  // If maintenance is OFF but user is on the maintenance page, redirect to home
  if (!isMaintenance && to.path === '/maintenance') {
    return navigateTo('/')
  }
})
