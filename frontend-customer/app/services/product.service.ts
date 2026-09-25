import type { ApiProduct, PaginatedResponse, ApiProductDetailResponse } from '../../types/api'

export const ProductService = {
  /**
   * Fetch all products
   */
  async getAll(params?: Record<string, string | number>): Promise<PaginatedResponse<ApiProduct>> {
    const query = params ? '?' + new URLSearchParams(Object.fromEntries(Object.entries(params).map(([k, v]) => [k, String(v)]))).toString() : ''
    return await useApiFetch<PaginatedResponse<ApiProduct>>(`/products${query}`)
  },
  
  /**
   * Fetch a single product by identifier (id or slug)
   */
  async getByIdentifier(identifier: string): Promise<ApiProductDetailResponse> {
    return await useApiFetch<ApiProductDetailResponse>(`/products/${identifier}`)
  }
}

