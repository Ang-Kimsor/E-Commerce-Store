
import type { ApiSetting, ApiBulkSettingsResponse } from '@/types/api'

export const SettingsService = {
  /**
   * Get all settings
   */
  async getAll(): Promise<ApiSetting[]> {
    return await useApiFetch<ApiSetting[]>('/admin/settings')
  },

  /**
   * Save settings in bulk
   */
  async saveBulk(formData: FormData): Promise<ApiBulkSettingsResponse> {
    if (!formData.has('_method')) {
      formData.append('_method', 'PUT')
    }
    return await useApiFetch<ApiBulkSettingsResponse>('/admin/settings/update', {
      method: 'POST',
      body: formData
    })
  },

  /**
   * Delete an image setting
   */
  async deleteImage(key: string): Promise<void> {
    return await useApiFetch<void>(`/admin/settings/${key}/image`, {
      method: 'DELETE'
    })
  }
}

