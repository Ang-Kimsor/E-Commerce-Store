import type { ApiProduct, PaginatedResponse } from '@/types/api'

export const ProductService = {
  /**
   * Fetch all products
   */
  async getAll(params?: Record<string, string | number>): Promise<PaginatedResponse<ApiProduct>> {
    const query = params ? '?' + new URLSearchParams(Object.fromEntries(Object.entries(params).map(([k, v]) => [k, String(v)]))).toString() : ''
    return await useApiFetch<PaginatedResponse<ApiProduct>>(`/admin/products${query}`)
  },

  /**
   * Fetch a single product by ID
   */
  async getById(id: number | string): Promise<ApiProduct> {
    return await useApiFetch<ApiProduct>(`/admin/products/${id}`)
  },

  /**
   * Create a new product
   */
  async create(data: Partial<ApiProduct> | FormData | Record<string, unknown>): Promise<ApiProduct> {
    return await useApiFetch<ApiProduct>('/admin/products', {
      method: 'POST',
      body: data
    })
  },

  /**
   * Update an existing product
   */
  async update(id: number | string, data: Partial<ApiProduct> | FormData | Record<string, unknown>): Promise<ApiProduct> {
    return await useApiFetch<ApiProduct>(`/admin/products/${id}`, {
      method: 'PUT',
      body: data
    })
  },

  /**
   * Delete a product
   */
  async delete(id: number | string): Promise<void> {
    return await useApiFetch<void>(`/admin/products/${id}`, {
      method: 'DELETE'
    })
  },

  /**
   * Restore a soft-deleted product
   */
  async restore(id: number | string): Promise<void> {
    return await useApiFetch<void>(`/admin/products/${id}/restore`, {
      method: 'POST'
    })
  },

  /**
   * Export Products list in excel format with active filters
   */
  export(query: string): Promise<Blob> {
    return useApiFetch<Blob>(`/admin/products/export?${query}`, { responseType: 'blob' })
  },

  /**
   * Export Product Detail
   */
  exportDetail(id: number | string, query: string): Promise<Blob> {
    return useApiFetch<Blob>(`/admin/products/${id}/export-detail?${query}`, { responseType: 'blob' })
  }
}

