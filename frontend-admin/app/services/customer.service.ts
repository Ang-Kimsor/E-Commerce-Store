import type { ApiCustomerDetail } from '@/types/api'

export const CustomerService = {
  /**
   * Fetch a single customer by ID
   */
  async getById(id: number | string, includeDeleted: boolean = false): Promise<ApiCustomerDetail> {
    const url = includeDeleted ? `/admin/customers/${id}?include_deleted=1` : `/admin/customers/${id}`
    return await useApiFetch<ApiCustomerDetail>(url)
  },

  /**
   * Create a new customer
   */
  async create(data: Partial<ApiCustomerDetail> | FormData | Record<string, unknown>): Promise<ApiCustomerDetail> {
    return await useApiFetch<ApiCustomerDetail>('/admin/customers', {
      method: 'POST',
      body: data
    })
  },

  /**
   * Update an existing customer
   */
  async update(id: number | string, data: Partial<ApiCustomerDetail> | FormData | Record<string, unknown>): Promise<ApiCustomerDetail> {
    return await useApiFetch<ApiCustomerDetail>(`/admin/customers/${id}`, {
      method: 'POST', // Use POST for FormData with _method=PUT
      body: data
    })
  },

  /**
   * Delete a customer
   */
  async delete(id: number | string): Promise<void> {
    return await useApiFetch<void>(`/admin/customers/${id}`, {
      method: 'DELETE'
    })
  },

  /**
   * Restore a soft-deleted customer
   */
  async restore(id: number | string): Promise<void> {
    return await useApiFetch<void>(`/admin/customers/${id}/restore`, {
      method: 'POST'
    })
  },

  /**
   * Block a customer
   */
  async block(id: number | string): Promise<void> {
    return await useApiFetch<void>(`/admin/customers/${id}/block`, {
      method: 'POST'
    })
  },

  /**
   * Unblock a customer
   */
  async unblock(id: number | string): Promise<void> {
    return await useApiFetch<void>(`/admin/customers/${id}/unblock`, {
      method: 'POST'
    })
  },

  /**
   * Export customers list in excel format
   */
  export(query: string): Promise<Blob> {
    return useApiFetch<Blob>(`/admin/customers/export?${query}`, { responseType: 'blob' })
  },

  /**
   * Export single customer detail in excel format
   */
  exportDetail(id: number | string): Promise<Blob> {
    return useApiFetch<Blob>(`/admin/customers/${id}/export-detail`, { responseType: 'blob' })
  }
}

