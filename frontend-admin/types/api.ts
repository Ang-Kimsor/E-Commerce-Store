export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface ApiUser {
  id: number
  name: string
  email?: string | null
  phone?: string | null
  telegram_id?: string | number | null
  avatar_url?: string | null
  role: 'superadmin' | 'admin' | 'customer'
  status?: 'active' | 'inactive' | 'deleted'
  is_active?: boolean
  created_at?: string
  updated_at?: string
  deleted_at?: string | null
}

export interface ApiCategory {
  id: number
  name: string
  slug: string
  status: 'active' | 'inactive' | 'deleted'
  is_active?: boolean
  description?: string | null
  parent_id?: number | null
  children?: ApiCategory[]
  products_count?: number
  created_at?: string
  updated_at?: string
  deleted_at?: string | null
}

export interface ApiProduct {
  id: number
  name: string
  sku?: string | null
  slug: string
  description?: string | null
  price: number
  stock: number
  status: 'active' | 'inactive' | 'deleted'
  is_active?: boolean
  discount_percent?: number
  display_order?: number | null
  image?: string | null
  category_id?: number | null
  category?: ApiCategory | null
  sold_units?: number
  created_at?: string
  updated_at?: string
  deleted_at?: string | null
}

export interface ApiOrderItem {
  id: number
  order_id?: number
  product_id: number
  product_name?: string
  product_sku?: string
  product_discount?: number
  quantity: number
  unit_price: number
  subtotal?: number
  total: number
  product?: ApiProduct
}

export interface ApiAddress {
  id: number
  customer_id?: number
  label?: string | null
  name: string
  phone?: string | null
  address_line_1: string
  address_line_2?: string | null
  village?: string | null
  commune?: string | null
  district?: string | null
  province?: string | null
  notes?: string | null
  latitude?: number | null
  longitude?: number | null
  is_default: boolean
  full_address?: string
  created_at?: string
  updated_at?: string
  deleted_at?: string | null
}

export interface ApiOrder {
  id: number
  order_number: string
  customer_id?: number | null
  address_id?: number | null
  status: 'pending'| 'confirmed' | 'processing' | 'shipped' | 'delivered' | 'completed' | 'cancelled' | 'returned'
  status_remark?: string | null
  payment_status: 'unpaid' | 'paid' | 'refunded'
  payment_status_remark?: string | null
  payment_reference?: string | null
  payment_method?: string | null
  payment_receipt?: string | null
  admin_note?: string | null
  subtotal: number
  discount?: number
  shipping_cost: number
  total: number
  address?: ApiAddress | null
  items: ApiOrderItem[]
  items_count?: number
  created_at: string
  user?: ApiUser | null
  guest_name?: string | null
  guest_email?: string | null
  guest_phone?: string | null
  customer_note?: string | null
  created_by?: number | null
  creator?: ApiUser | null
  status_history?: any[]
  payment_history?: any[]
}


export interface AnalyticsTrendPoint {
  date: string
  label: string
  orders: number
  revenue: number
}

export interface AnalyticsStatusBreakdown {
  status: string
  count: number
  revenue: number
}

export interface AnalyticsTopProduct {
  product_id: number
  name: string
  thumbnail_url?: string | null
  quantity: number
  revenue: number
}

export interface AnalyticsRecentCustomer {
  id: number
  name: string
  email?: string | null
  avatar_url?: string | null
  joined_at?: string | null
}

export interface DashboardCategoryBreakdown {
  name: string
  count: number
}

export interface DashboardSummary {
  generated_at: string
  totals: {
    products: number
    orders: number
    orders_today?: number
    revenue: number
    revenue_today?: number
    average_order_value: number
    customers: number
    new_customers_today?: number
    low_stock_count?: number
    out_of_stock_products?: number
  }
  trend: AnalyticsTrendPoint[]
  trend_week?: AnalyticsTrendPoint[]
  trend_year?: AnalyticsTrendPoint[]
  status_breakdown: AnalyticsStatusBreakdown[]
  top_products: AnalyticsTopProduct[]
  recent_customers: AnalyticsRecentCustomer[]
  recent_orders?: ApiOrder[]
  low_stock_products?: ApiProduct[]
  category_breakdown?: DashboardCategoryBreakdown[]
  revenue_by_province?: Array<{ label: string; revenue: number }>
}

export interface ApiCustomerSummary {
  id: number
  name: string
  email?: string | null
  phone?: string | null
  avatar_url?: string | null
  orders_count: number
  total_spent: number
  created_at: string
  deleted_at?: string | null
  status?: 'active' | 'inactive' | 'deleted'
}

export interface ApiCustomerDetail extends ApiCustomerSummary {
  average_order_value: number
  last_order_at?: string | null
  addresses: ApiAddress[]
  orders: ApiOrder[]
}

export interface ApiStockMovement {
  id: number
  product_id: number
  user_id?: number | null
  type: 'in' | 'out'
  quantity: number
  reference?: string | null
  notes?: string | null
  created_at?: string
  updated_at?: string
  user?: ApiUser | null
}

export interface ApiAddStockMovementResponse {
  movement: ApiStockMovement
  product: ApiProduct
}

export interface StockMovementPayload {
  type: 'in' | 'out' | 'IN' | 'OUT'
  quantity: number
  reference?: string | null
  notes?: string | null
  [key: string]: unknown
}

export interface ApiSetting {
  id: number
  key: string
  value: string | null
  type: string
  description?: string | null
  full_url?: string
  created_at?: string
  updated_at?: string
}

export interface ApiBulkSettingsResponse {
  message: string
  settings: ApiSetting[]
}

export interface ApiUserProfileResponse {
  message?: string
  user: ApiUser
}

export interface ReportPeriod {
  start: string
  end: string
}

export interface ReportChartPoint {
  label: string
  value?: number
  revenue?: number
  count?: number
  [key: string]: unknown
}

export interface ReportTablePagination<T = Record<string, unknown>> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface ApiSalesReportResponse {
  title: string
  period: ReportPeriod
  summary: {
    total_orders: number
    total_sales: number
    products_sold: number
    average_order_value: number
  }
  charts: {
    daily_sales: Array<{ label: string; revenue: number; orders?: number }>
    monthly_sales: Array<{ label: string; revenue: number; orders?: number }>
    order_status: Array<{ label: string; value: number }>
    payment_status: Array<{ label: string; value: number }>
    revenue_by_province: Array<{ label: string; revenue: number }>
  }
  table: ReportTablePagination
}

export interface ApiProductsReportResponse {
  title: string
  period: ReportPeriod
  summary: {
    total_products: number
    active_products: number
    inactive_products: number
    deleted_products: number
  }
  charts: {
    products_added_over_time: Array<{ label: string; count: number }>
    products_by_category: Array<{ label: string; value: number }>
    price_range_distribution: Array<{ label: string; value: number }>
  }
  table: ReportTablePagination
}

export interface ApiInventoryReportResponse {
  title: string
  period: ReportPeriod
  summary: {
    in_stock: number
    low_stock: number
    out_of_stock: number
    stock_in: number
    stock_out: number
    net_movement: number
  }
  charts: {
    movement_over_time: Array<{ date: string; in: number; out: number }>
    movement_by_product: Array<{ label: string; value: number }>
    stock_status_distribution: Array<{ label: string; value: number }>
  }
  table: ReportTablePagination
}

export interface ApiCustomersReportResponse {
  title: string
  period: ReportPeriod
  summary: {
    total_customers: number
    active_customers: number
    inactive_customers: number
    deleted_customers: number
    new_customers_in_period: number
  }
  charts: {
    registrations_over_time: Array<{ label: string; count: number }>
    customers_by_status: Array<{ label: string; value: number }>
    top_customers_by_spending: Array<{ label: string; revenue: number; orders?: number }>
  }
  table: ReportTablePagination
}

