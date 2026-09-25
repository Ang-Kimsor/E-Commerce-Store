
import type { ApiStockMovement, ApiAddStockMovementResponse, PaginatedResponse, StockMovementPayload } from '@/types/api'

export const StockService = {
  /**
   * Get stock movements for a specific product
   */
  async getMovements(productId: number | string, page: number = 1): Promise<PaginatedResponse<ApiStockMovement>> {
    return await useApiFetch<PaginatedResponse<ApiStockMovement>>(`/admin/products/${productId}/stock-movements?page=${page}`)
  },

  /**
   * Add a new stock movement for a specific product
   */
  async addMovement(productId: number | string, data: StockMovementPayload | Record<string, unknown>): Promise<ApiAddStockMovementResponse> {
    return await useApiFetch<ApiAddStockMovementResponse>(`/admin/products/${productId}/stock-movements`, {
      method: 'POST',
      body: data
    })
  }
}

