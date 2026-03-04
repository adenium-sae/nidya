// ============================
// API Response Types
// ============================

export interface ApiResponse<T> {
  data: T
  message?: string
}

export interface PaginatedResponse<T> {
  data: T[]
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
  current_page?: number
  last_page?: number
  per_page?: number
  total?: number
}

// ============================
// Domain Models
// ============================

export interface User {
  id: string
  email: string
  profile?: UserProfile
}

export interface UserProfile {
  first_name: string
  middle_name?: string
  last_name: string
  second_last_name?: string
  avatar_url?: string
}

export interface Store {
  id: string
  name: string
  slug?: string
  description?: string
  logo_url?: string
  primary_color?: string
  address?: string
  phone?: string
  email?: string
  is_active?: boolean
}

export interface Branch {
  id: string
  name: string
  code?: string
  stores?: Store[]
  address?: string
  phone?: string
  email?: string
  allow_sales?: boolean
  allow_inventory?: boolean
  is_active?: boolean
}

export interface Category {
  id: string
  name: string
  description?: string
  is_active?: boolean
  products_count?: number
}

export interface Product {
  id: string
  name: string
  description?: string
  sku: string
  barcode?: string
  cost: string | number
  price?: string | number
  category_id: string
  category?: Category
  type: 'product' | 'service'
  min_stock: number
  is_active: boolean
  image_url?: string
  stores?: Store[]
}

export interface Warehouse {
  id: string
  name: string
  code?: string
  type: string
  address?: string
  is_active: boolean
  store_id?: string
  branch_id?: string
  store?: Store
  branch?: Branch
}

export interface StockItem {
  id: string
  product_id: string
  warehouse_id: string
  storage_location_id?: string
  quantity: number
  product?: Product
  warehouse?: Warehouse
}

export interface StockMovement {
  id: string
  product: {
    id: string
    name: string
    sku: string
  }
  warehouse: {
    id: string
    name: string
  }
  user?: {
    id: string
    email: string
  }
  type: string
  status: 'pending' | 'completed' | 'cancelled'
  quantity: number
  quantity_before: number
  quantity_after: number
  notes?: string
  reference?: string
  created_at: string
}

export interface StockAdjustment {
  id: string
  folio: string
  type: string
  status: 'pending' | 'completed' | 'cancelled'
  reason: string
  created_at: string
  warehouse: {
    name: string
  }
  user: {
    email: string
  }
  items: {
    product: {
      name: string
    }
    quantity_before: number
    quantity_after: number
  }[]
}

export interface StockTransferItem {
  id: string
  stock_transfer_id: string
  product_id: string
  quantity_requested: number
  quantity_sent: number
  quantity_received: number
  notes?: string
  product?: Product
}

export interface StockTransfer {
  id: string
  folio: string
  from_warehouse_id: string
  to_warehouse_id: string
  requested_by: string
  approved_by?: string
  received_by?: string
  status: 'pending' | 'in_transit' | 'completed' | 'cancelled'
  approved_at?: string
  shipped_at?: string
  received_at?: string
  notes?: string
  created_at: string
  updated_at: string
  source_warehouse?: Warehouse
  destination_warehouse?: Warehouse
  requested_by_user?: User
  items?: StockTransferItem[]
}

export interface DashboardStats {
  total_sales: number
  total_products: number
  total_customers: number
  total_categories: number
  recent_sales: any[]
  top_products: any[]
  sales_by_day: any[]
}
