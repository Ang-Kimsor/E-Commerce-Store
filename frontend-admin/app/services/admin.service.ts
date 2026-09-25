import type { ApiUser } from '@/types/api'

export const AdminService = {
  /**
   * Fetch a single admin by ID
   */
  async getById(id: number | string): Promise<ApiUser> {
    return await useApiFetch<ApiUser>(`/admin/admins/${id}`)
  },

  /**
   * Get all admins (paginated or unpaginated list)
   */
  async getList(query: string = ''): Promise<ApiUser> {
    return await useApiFetch<ApiUser>('/admin/admins', {
      method: 'GET',
      query: query ? Object.fromEntries(new URLSearchParams(query)) : {}
    })
  },

  /**
   * Create a new admin
   */
  async create(data: Partial<ApiUser> | FormData | Record<string, unknown>): Promise<ApiUser> {
    return await useApiFetch<ApiUser>('/admin/admins', {
      method: 'POST',
      body: data
    })
  },

  /**
   * Update an existing admin
   */
  async update(id: number | string, data: Partial<ApiUser> | FormData | Record<string, unknown>): Promise<ApiUser> {
    const isFormData = data instanceof FormData
    return await useApiFetch<ApiUser>(`/admin/admins/${id}`, {
      method: isFormData ? 'POST' : 'PUT',
      body: data
    })
  },


  /**
   * Block admin
   */
  async block(id: number | string): Promise<void> {
    return await useApiFetch<void>(`/admin/admins/${id}/block`, {
      method: 'POST'
    })
  },

  /**
   * Unblock admin
   */
  async unblock(id: number | string): Promise<void> {
    return await useApiFetch<void>(`/admin/admins/${id}/unblock`, {
      method: 'POST'
    })
  }
}
