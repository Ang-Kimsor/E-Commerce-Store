import type { ApiCategory } from '../../types/api'

export const CategoryService = {
  /**
   * Fetch categories
   */
  async getAll(params?: Record<string, string | number>): Promise<ApiCategory[] | { data: ApiCategory[] }> {
    const query = params ? '?' + new URLSearchParams(Object.fromEntries(Object.entries(params).map(([k, v]) => [k, String(v)]))).toString() : ''
    return await useApiFetch<ApiCategory[] | { data: ApiCategory[] }>(`/categories${query}`)
  }
}

