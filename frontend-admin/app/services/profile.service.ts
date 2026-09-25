
import type { ApiUserProfileResponse } from '@/types/api'

export const ProfileService = {
  /**
   * Get the current user's profile
   */
  async getProfile(): Promise<ApiUserProfileResponse> {
    return await useApiFetch<ApiUserProfileResponse>('/admin/profile')
  },

  /**
   * Update the current user's profile
   */
  async updateProfile(data: FormData): Promise<ApiUserProfileResponse> {
    return await useApiFetch<ApiUserProfileResponse>('/admin/profile', {
      method: 'POST',
      body: data
    })
  }
}

