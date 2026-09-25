import type { ApiOrder, PaginatedResponse } from '../../types/api'

export const OrderService = {
  /**
   * Fetch customer's orders
   */
  async getAll(params?: Record<string, string | number>): Promise<PaginatedResponse<ApiOrder>> {
    const query = params ? '?' + new URLSearchParams(Object.fromEntries(Object.entries(params).map(([k, v]) => [k, String(v)]))).toString() : ''
    return await useApiFetch<PaginatedResponse<ApiOrder>>(`/customer/orders${query}`)
  },
  
  /**
   * Fetch a single order by ID
   */
  async getById(id: number | string): Promise<ApiOrder> {
    return await useApiFetch<ApiOrder>(`/customer/orders/${id}`)
  },

  /**
   * Create a new order (checkout)
   */
  async create(data: Partial<ApiOrder> | Record<string, unknown>): Promise<ApiOrder> {
    return await useApiFetch<ApiOrder>('/customer/orders', {
      method: 'POST',
      body: data
    })
  }
}

