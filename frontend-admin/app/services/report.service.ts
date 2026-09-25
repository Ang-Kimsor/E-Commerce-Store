import type {
  ApiSalesReportResponse,
  ApiProductsReportResponse,
  ApiInventoryReportResponse,
  ApiCustomersReportResponse
} from '@/types/api'

export const ReportService = {
  async getSales(query: string = ''): Promise<ApiSalesReportResponse> {
    return useApiFetch<ApiSalesReportResponse>(`/admin/reports/sales?${query}`)
  },
  async exportSales(query: string = ''): Promise<Blob> {
    return useApiFetch(`/admin/reports/sales?${query}`, { responseType: 'blob' })
  },
  async getProducts(query: string = ''): Promise<ApiProductsReportResponse> {
    return useApiFetch<ApiProductsReportResponse>(`/admin/reports/products?${query}`)
  },
  async exportProducts(query: string = ''): Promise<Blob> {
    return useApiFetch(`/admin/reports/products?${query}`, { responseType: 'blob' })
  },
  async getInventory(query: string = ''): Promise<ApiInventoryReportResponse> {
    return useApiFetch<ApiInventoryReportResponse>(`/admin/reports/inventory?${query}`)
  },
  async exportInventory(query: string = ''): Promise<Blob> {
    return useApiFetch(`/admin/reports/inventory?${query}`, { responseType: 'blob' })
  },
  async getCustomers(query: string = ''): Promise<ApiCustomersReportResponse> {
    return useApiFetch<ApiCustomersReportResponse>(`/admin/reports/customers?${query}`)
  },
  async exportCustomers(query: string = ''): Promise<Blob> {
    return useApiFetch(`/admin/reports/customers?${query}`, { responseType: 'blob' })
  }
}
