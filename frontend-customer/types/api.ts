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
  avatar_url?: string | null
  phone?: string | null
  role: 'superadmin' | 'admin' | 'customer'
  is_active?: boolean
  status?: 'active' | 'inactive' | 'deleted'
}

export interface ApiCategory {
  id: number
  name: string
  slug: string
  description?: string | null
  parent_id?: number | null
  children?: ApiCategory[]
  products_count?: number
}

export interface ApiProduct {
  id: number
  name: string
  slug: string
  description?: string | null
  price: number
  stock: number
  is_active: boolean
  discount_percent?: number
  display_order?: number | null
  image?: string | null
  category_id?: number | null
  category?: ApiCategory | null
  sold_units?: number

  created_at?: string
  updated_at?: string
}

export interface ApiProductDetailResponse {
  product: ApiProduct
  related_products: ApiProduct[]
}

export interface ApiOrderItem {
  id: number
  product_id: number

  quantity: number
  unit_price: number
  total: number
  product?: ApiProduct

}

export interface ApiAddress {
  id: number
  customer_id?: number
  label?: string | null
  name?: string | null
  phone?: string | null
  address_line_1?: string | null
  address_line_2?: string | null
  village?: string | null
  commune?: string | null
  district?: string | null
  province?: string | null
  notes?: string | null
  is_default: boolean
  latitude?: number | null
  longitude?: number | null
  full_address?: string | null
  hasGPS?: boolean
  created_at?: string
  updated_at?: string
  deleted_at?: string | null
}

export interface ApiOrder {
  id: number
  order_number: string
  status: 'pending' | 'processing' | 'shipped' | 'delivered' | 'cancelled' | 'returned'
  payment_status: 'unpaid' | 'paid' | 'refunded'
  subtotal: number
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
  meta?: Record<string, unknown> | null
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
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
  image?: string | null
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

export interface AnalyticsSummary {
  generated_at: string
  totals: {
    products: number
    orders: number
    revenue: number
    average_order_value: number
    customers: number
  }
  trend: AnalyticsTrendPoint[]
  status_breakdown: AnalyticsStatusBreakdown[]
  top_products: AnalyticsTopProduct[]
  recent_customers: AnalyticsRecentCustomer[]
}

export interface ApiCustomerSummary {
  id: number
  name: string
  email?: string | null
  phone?: string | null
  orders_count: number
  total_spent: number
  created_at: string
}

export interface ApiCustomerDetail extends ApiCustomerSummary {
  average_order_value: number
  last_order_at?: string | null
  addresses: ApiAddress[]
  orders: ApiOrder[]
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

export interface ApiUserProfileResponse {
  message?: string
  user: ApiUser
}

export interface ApiProductDetailResponse {
  product: ApiProduct
  related_products: ApiProduct[]
}

