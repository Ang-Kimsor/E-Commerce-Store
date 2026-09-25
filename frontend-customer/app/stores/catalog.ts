import { defineStore } from 'pinia'
import type { ApiCategory, ApiProduct, PaginatedResponse } from '../../types/api'
import { CategoryService } from '../services/category.service'
import { ProductService } from '../services/product.service'
interface CatalogState {
  products: ApiProduct[]
  categories: ApiCategory[]
  pagination: Pick<PaginatedResponse<ApiProduct>, 'current_page' | 'last_page' | 'per_page' | 'total'>
  isLoading: boolean
  isCategoriesLoading: boolean
  selectedCategoryIds: number[]
  appliedCategoryIds: number[]
  searchTerm: string
  sortBy: string
  minPrice: number | null
  maxPrice: number | null
  stockStatus: 'both' | 'in_stock' | 'out_of_stock'
}

export const useCatalogStore = defineStore('catalog', {
  state: (): CatalogState => ({
    products: [],
    categories: [],
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
    },
    isLoading: true,
    isCategoriesLoading: true,
    selectedCategoryIds: [],
    appliedCategoryIds: [],
    searchTerm: '',
    sortBy: 'id_desc',
    minPrice: null,
    maxPrice: null,
    stockStatus: 'both',
  }),
  getters: {
    selectedCategoryId(): number | null {
      return this.selectedCategoryIds.length === 1 ? (this.selectedCategoryIds[0] ?? null) : null
    }
  },
  actions: {
    async fetchCategories(force = false) {
      if (this.categories.length && !force) {
        this.isCategoriesLoading = false
        return
      }
      this.isCategoriesLoading = true
      try {
        const response = await CategoryService.getAll()
        // /categories returns a plain array, but handle wrapped format defensively
        this.categories = Array.isArray(response) ? response : (response as any)?.data || []
      } catch (error) {
        console.error('Failed to fetch categories', error)
      } finally {
        this.isCategoriesLoading = false
      }
    },
    async fetchProducts(page = 1, forceLoading = true) {
      if (this.products.length === 0 || forceLoading) {
        this.isLoading = true
        this.products = []
      }
      try {
        const query: Record<string, any> = { 
          page, 
          per_page: this.pagination.per_page || 15 
        }

        if (this.appliedCategoryIds && this.appliedCategoryIds.length > 0) {
          query.category_ids = this.appliedCategoryIds.join(',')
        }

        if (this.searchTerm) {
          query.search = this.searchTerm
        }
        
        if (this.sortBy) {
          query.sort_by = this.sortBy
        }

        if (this.minPrice !== null && this.minPrice !== undefined) {
          query.min_price = this.minPrice
        }
        
        if (this.maxPrice !== null && this.maxPrice !== undefined) {
          query.max_price = this.maxPrice
        }

        if (this.stockStatus && this.stockStatus !== 'both') {
          query.stock_status = this.stockStatus
        }

        const response = await ProductService.getAll(query)

        this.products = response.data
        this.pagination = {
          current_page: response.current_page,
          last_page: response.last_page,
          per_page: response.per_page,
          total: response.total,
        }
      } catch (error) {
        console.error('Failed to fetch products', error)
      } finally {
        this.isLoading = false
      }
    },
    async fetchProduct(identifier: string | number) {
      return ProductService.getByIdentifier(identifier as string)
    },
    toggleCategory(categoryId: number) {
      const index = this.selectedCategoryIds.indexOf(categoryId)
      if (index > -1) {
        this.selectedCategoryIds.splice(index, 1)
      } else {
        this.selectedCategoryIds.push(categoryId)
      }
    },
    clearCategorySelection() {
      this.selectedCategoryIds = []
    },
    setCategory(categoryId: number | null) {
      if (categoryId === null) {
        this.selectedCategoryIds = []
      } else {
        this.selectedCategoryIds = [categoryId]
      }
    },
    setSearchTerm(term: string) {
      this.searchTerm = term
    },
  },
  // Only persist filter state (searchTerm intentionally excluded — it derives from the URL)
  persist: {
    pick: ['selectedCategoryIds'],
  } as any,
})
