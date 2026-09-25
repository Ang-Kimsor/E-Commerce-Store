
import type { ApiSetting } from '../../types/api'

export const SettingsService = {
  /**
   * Fetch application settings
   */
  async getSettings(): Promise<ApiSetting[]> {
    return await useApiFetch<ApiSetting[]>('/settings')
  }
}

