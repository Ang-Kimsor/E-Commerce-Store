
import type { ApiAddress, PaginatedResponse } from '../../types/api'

export const AddressService = {
  /**
   * Fetch customer's addresses
   */
  async getAll(params?: Record<string, string | number>): Promise<PaginatedResponse<ApiAddress>> {
    const query = params ? '?' + new URLSearchParams(Object.fromEntries(Object.entries(params).map(([k, v]) => [k, String(v)]))).toString() : ''
    return await useApiFetch<PaginatedResponse<ApiAddress>>(`/customer/addresses${query}`)
  },

  /**
   * Fetch a single address by ID
   */
  async getById(id: number | string): Promise<ApiAddress> {
    return await useApiFetch<ApiAddress>(`/customer/addresses/${id}`)
  },

  /**
   * Create a new address
   */
  async create(data: Partial<ApiAddress> | Record<string, unknown>): Promise<ApiAddress> {
    return await useApiFetch<ApiAddress>('/customer/addresses', {
      method: 'POST',
      body: data
    })
  },

  /**
   * Update an existing address
   */
  async update(id: number | string, data: Partial<ApiAddress> | Record<string, unknown>): Promise<ApiAddress> {
    return await useApiFetch<ApiAddress>(`/customer/addresses/${id}`, {
      method: 'PUT',
      body: data
    })
  },

  /**
   * Delete an address
   */
  async delete(id: number | string): Promise<void> {
    return await useApiFetch<void>(`/customer/addresses/${id}`, {
      method: 'DELETE'
    })
  }
}

