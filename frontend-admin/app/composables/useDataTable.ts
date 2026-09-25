import { ref, watch, onMounted } from 'vue'

export interface DataTableOptions {
  /** API endpoint path, e.g. '/admin/orders' */
  endpoint: string
  /** Default per_page value (default: 50) */
  perPage?: number
  /** Default sort column (default: 'created_at') */
  defaultSortBy?: string
  /** Default sort direction (default: true = descending) */
  defaultSortDesc?: boolean
  /** Param name for sort column (default: 'sort_by') */
  sortKey?: string
  /** Param name for sort direction (default: 'sort_desc') */
  orderKey?: string
  /** Function to format sort direction value (default: desc => desc ? '1' : '0') */
  formatOrder?: (desc: boolean) => string
  /** Static extra params always added to every request */
  staticParams?: Record<string, string | number | boolean | undefined>
  /** Reactive filter refs to watch. Key = param name sent to API, Value = a ref */
  filters?: Record<string, any>
  /** Run fetch immediately on mount (default: true) */
  immediate?: boolean
  /** Search debounce ms (default: 300) */
  debounceMs?: number
  /** Callback to extract items array from response */
  extractItems?: (res: any) => any[]
  /** Callback to extract pagination from response */
  extractPagination?: (res: any) => any
  /** Callback to extract any extra data from response (e.g. status_counts) */
  extractExtra?: (res: any) => Record<string, any>
}

/**
 * useDataTable
 * Reusable composable that manages paginated, searchable, sortable, filterable API data.
 */
export function useDataTable(options: DataTableOptions) {
  const {
    endpoint,
    perPage: initialPerPage = 15,
    defaultSortBy = 'created_at',
    defaultSortDesc = true,
    sortKey = 'sort_by',
    orderKey = 'sort_desc',
    formatOrder = (desc: boolean) => (desc ? '1' : '0'),
    staticParams = {},
    filters = {},
    immediate = true,
    debounceMs = 300,
  } = options

  const items = ref<any[]>([])
  const loading = ref(immediate)
  const search = ref('')
  const page = ref(1)
  const perPage = ref(initialPerPage)
  const sortBy = ref(defaultSortBy)
  const sortDesc = ref(defaultSortDesc)
  const paginationData = ref<any>(null)
  const extra = ref<Record<string, any>>({})

  let searchTimer: any = null

  function buildParams(): URLSearchParams {
    const params = new URLSearchParams()
    params.set('page', page.value.toString())
    params.set('per_page', perPage.value.toString())
    params.set('sort_by', sortBy.value)
    params.set('sort_desc', sortDesc.value ? '1' : '0')

    if (search.value) params.set('search', search.value)

    // Append dynamic filter refs
    for (const [key, val] of Object.entries(filters)) {
      const v = typeof val === 'object' && val !== null && 'value' in val ? val.value : val
      if (v !== '' && v !== null && v !== undefined) {
        params.set(key, String(v))
      }
    }

    // Append static params
    for (const [key, val] of Object.entries(staticParams)) {
      if (val !== undefined && val !== null && val !== '') {
        params.set(key, String(val))
      }
    }

    return params
  }

  function extractItemsDefault(res: any): any[] {
    if (res?.data?.data && Array.isArray(res.data.data)) return res.data.data
    if (res?.data && Array.isArray(res.data)) return res.data
    if (Array.isArray(res)) return res
    const found = Object.values(res || {}).find(v => Array.isArray(v))
    return (found as any[]) || []
  }

  function extractPaginationDefault(res: any): any {
    const d = res?.data?.current_page ? res.data : res?.current_page ? res : null
    if (!d) return null
    return {
      current_page: d.current_page,
      last_page: d.last_page,
      per_page: d.per_page,
      total: d.total,
      from: d.from,
      to: d.to,
    }
  }

  async function fetch() {
    loading.value = true
    try {
      const params = buildParams()
      const res = await useApiFetch<any>(`${endpoint}?${params.toString()}`, { method: 'GET' })
      items.value = options.extractItems ? options.extractItems(res) : extractItemsDefault(res)
      paginationData.value = options.extractPagination ? options.extractPagination(res) : extractPaginationDefault(res)
      extra.value = options.extractExtra ? options.extractExtra(res) : {}
    } catch (err) {
      console.error(`[useDataTable] Failed to fetch ${endpoint}:`, err)
      items.value = []
      paginationData.value = null
    } finally {
      loading.value = false
    }
  }

  function handleSearch(val: string) {
    search.value = val
    if (searchTimer) clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
      page.value = 1
      fetch()
    }, debounceMs)
  }

  function handlePageChange(newPage: number) {
    page.value = newPage
    fetch()
  }

  function handleSort(by: string, desc: boolean) {
    sortBy.value = by
    sortDesc.value = desc
    page.value = 1
    fetch()
  }

  function resetFilters(defaults: Record<string, any> = {}) {
    search.value = ''
    page.value = 1
    sortBy.value = defaultSortBy
    sortDesc.value = defaultSortDesc
    for (const [key, val] of Object.entries(filters)) {
      if (typeof val === 'object' && val !== null && 'value' in val) {
        val.value = key in defaults ? defaults[key] : ''
      }
    }
    fetch()
  }

  // Watch sortBy and sortDesc — fires when DataTable v-model updates them
  watch(sortBy, () => {
    page.value = 1
    fetch()
  })

  watch(sortDesc, () => {
    page.value = 1
    fetch()
  })

  watch(perPage, () => {
    page.value = 1
    fetch()
  })

  // Watch search and trigger fetch
  watch(search, () => {
    page.value = 1
    fetch()
  })

  // Watch all filter refs and auto-refetch on change
  const filterValues = Object.values(filters)
  if (filterValues.length > 0) {
    watch(filterValues, () => {
      page.value = 1
      fetch()
    })
  }

  if (immediate) {
    onMounted(() => fetch())
  }

  return {
    items,
    loading,
    search,
    page,
    perPage,
    sortBy,
    sortDesc,
    paginationData,
    extra,
    fetch,
    handleSearch,
    handlePageChange,
    handleSort,
    resetFilters,
  }
}

