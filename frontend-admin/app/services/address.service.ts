import type { ApiAddress } from '@/types/api'

export const AddressService = {
  /**
   * Fetch a single address by ID
   */
  async getById(id: number | string): Promise<ApiAddress> {
    return await useApiFetch<ApiAddress>(`/admin/addresses/${id}`)
  },

  /**
   * Create a new address
   */
  async create(data: Partial<ApiAddress> | Record<string, unknown>): Promise<ApiAddress> {
    return await useApiFetch<ApiAddress>('/admin/addresses', {
      method: 'POST',
      body: data
    })
  },

  /**
   * Update an existing address
   */
  async update(id: number | string, data: Partial<ApiAddress> | Record<string, unknown>): Promise<ApiAddress> {
    return await useApiFetch<ApiAddress>(`/admin/addresses/${id}`, {
      method: 'PUT',
      body: data
    })
  },

  /**
   * Delete an address
   */
  async delete(id: number | string): Promise<void> {
    return await useApiFetch<void>(`/admin/addresses/${id}`, {
      method: 'DELETE'
    })
  }
}

