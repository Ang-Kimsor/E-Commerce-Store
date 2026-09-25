import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { SettingsService } from '../services/settings.service'

export interface SiteSettingItem {
  id?: number
  key: string
  value: string | null
  type?: string
  description?: string
  full_url?: string
}

export const useSettingsStore = defineStore('settings', () => {
  const settingsList = ref<SiteSettingItem[]>([])
  const isLoaded = ref(false)
  const isLoading = ref(false)

  const settingsMap = computed<Record<string, string>>(() => {
    const map: Record<string, string> = {}
    settingsList.value.forEach((item) => {
      map[item.key] = item.value ?? ''
    })
    return map
  })

  const getSetting = (key: string, defaultValue: string = ''): string => {
    return settingsMap.value[key] ?? defaultValue
  }

  const siteName = computed(() => settingsMap.value['site_name'] || '')
  const siteDescription = computed(() => settingsMap.value['site_description'] || '')
  const copyrightText = computed(() => settingsMap.value['copyright_text'] || `© ${new Date().getFullYear()} ${siteName.value || 'Unknown Site'}. All rights reserved.`)
  const contactEmail = computed(() => settingsMap.value['contact_email'] || '')
  const contactPhone = computed(() => settingsMap.value['contact_phone'] || '')
  const contactAddress = computed(() => settingsMap.value['contact_address'] || '')
  const googleMapUrl = computed(() => settingsMap.value['google_map_url'] || '')
  const facebookUrl = computed(() => settingsMap.value['facebook_url'] || '')

  const siteLogo = computed(() => {
    const item = settingsList.value.find((s) => s.key === 'site_logo')
    if (item?.value || item?.full_url) {
      const { getImageUrl } = useProductImage()
      const resolved = getImageUrl(item.value || item.full_url)
      if (resolved) return resolved
    }
    return null
  })

  const siteFavicon = computed(() => {
    const item = settingsList.value.find((s) => s.key === 'site_favicon')
    if (item?.value || item?.full_url) {
      const { getImageUrl } = useProductImage()
      return getImageUrl(item.value || item.full_url)
    }
    return null
  })

  async function fetchSettings(force = false) {
    if (isLoaded.value && !force) return
    isLoading.value = true
    try {
      const res = await SettingsService.getSettings()
      if (Array.isArray(res)) {
        settingsList.value = res
        isLoaded.value = true
      }
    } catch (e) {
      console.error('Failed to fetch site settings:', e)
    } finally {
      isLoading.value = false
    }
  }

  return {
    settingsList,
    settingsMap,
    isLoaded,
    isLoading,
    getSetting,
    siteName,
    siteDescription,
    copyrightText,
    contactEmail,
    contactPhone,
    contactAddress,
    googleMapUrl,
    facebookUrl,
    siteLogo,
    siteFavicon,
    fetchSettings,
  }
})
