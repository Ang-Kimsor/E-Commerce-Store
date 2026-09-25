import type { ApiCategory } from '@/types/api'

export const CategoryService = {
  /**
   * Fetch a single category by ID
   */
  async getById(id: number | string): Promise<ApiCategory> {
    return await useApiFetch<ApiCategory>(`/admin/categories/${id}`)
  },

  /**
   * Create a new category
   */
  async create(data: Partial<ApiCategory> | Record<string, unknown>): Promise<ApiCategory> {
    return await useApiFetch<ApiCategory>('/admin/categories', {
      method: 'POST',
      body: data
    })
  },

  /**
   * Update an existing category
   */
  async update(id: number | string, data: Partial<ApiCategory> | Record<string, unknown>): Promise<ApiCategory> {
    return await useApiFetch<ApiCategory>(`/admin/categories/${id}`, {
      method: 'PUT',
      body: data
    })
  },

  /**
   * Delete a category
   */
  async delete(id: number | string): Promise<void> {
    return await useApiFetch<void>(`/admin/categories/${id}`, {
      method: 'DELETE'
    })
  },

  /**
   * Restore a soft-deleted category
   */
  async restore(id: number | string): Promise<void> {
    return await useApiFetch<void>(`/admin/categories/${id}/restore`, {
      method: 'POST'
    })
  },

  /**
   * Export all categories list in excel format with active filters
   */
  export(query: string): Promise<Blob> {
    return useApiFetch<Blob>(`/admin/categories/export?${query}`, { responseType: 'blob' })
  },

  /**
   * Export category detail and products in excel format
   */
  exportDetail(id: number | string, query: string): Promise<Blob> {
    return useApiFetch<Blob>(`/admin/categories/${id}/export-detail?${query}`, { responseType: 'blob' })
  }
}

