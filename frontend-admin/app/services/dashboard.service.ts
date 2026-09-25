
import type { DashboardSummary } from '@/types/api'

export const DashboardService = {
  /**
   * Get dashboard summary data
   */
  async getSummary(): Promise<DashboardSummary> {
    return await useApiFetch<DashboardSummary>('/admin/dashboard', { method: 'GET' })
  }
}

