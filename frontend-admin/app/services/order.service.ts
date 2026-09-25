import type { ApiOrder, PaginatedResponse } from '@/types/api'

export const OrderService = {
  /**
   * Fetch all orders (if needed directly outside of useDataTable)
   */
  async getAll(params?: Record<string, string | number>): Promise<PaginatedResponse<ApiOrder>> {
    const query = params ? '?' + new URLSearchParams(Object.fromEntries(Object.entries(params).map(([k, v]) => [k, String(v)]))).toString() : ''
    return await useApiFetch<PaginatedResponse<ApiOrder>>(`/admin/orders${query}`)
  },

  /**
   * Fetch a single order by ID
   */
  async getById(id: number | string): Promise<ApiOrder> {
    return await useApiFetch<ApiOrder>(`/admin/orders/${id}`)
  },

  /**
   * Create a new order
   */
  async create(data: Partial<ApiOrder> | FormData | Record<string, unknown>): Promise<ApiOrder> {
    return await useApiFetch<ApiOrder>('/admin/orders', {
      method: 'POST',
      body: data
    })
  },

  /**
   * Update an existing order
   */
  async update(id: number | string, data: Partial<ApiOrder> | FormData | Record<string, unknown>): Promise<ApiOrder> {
    return await useApiFetch<ApiOrder>(`/admin/orders/${id}`, {
      method: 'POST', // Use POST to support FormData with _method=PUT in Laravel
      body: data
    })
  },


  /**
   * Export orders list in excel format with active filters
   */
  export(query: string): Promise<Blob> {
    return useApiFetch<Blob>(`/admin/orders/export?${query}`, { responseType: 'blob' })
  },

  /**
   * Export single order detail in excel format
   */
  exportDetail(id: number | string): Promise<Blob> {
    return useApiFetch<Blob>(`/admin/orders/${id}/export-detail`, { responseType: 'blob' })
  },

  /**
   * Download single order PDF or get HTML view
   */
  downloadPdf(id: number | string, format: 'a4' | 'receipt' | 'receipt_html' = 'a4'): Promise<Blob | string> {
    const isHtml = format === 'receipt_html';
    return useApiFetch<Blob | string>(`/admin/orders/${id}/pdf?format=${format}`, { responseType: isHtml ? 'text' : 'blob' })
  }
}

