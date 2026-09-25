import { ref, onMounted } from 'vue'

export interface SelectFetchOptions {
  endpoint: string
  labelKey?: string
  valueKey?: string
  perPage?: number
  buildLabel?: (item: any) => string
  staticParams?: Record<string, string | number | boolean>
  dynamicParams?: () => Record<string, any>
  immediate?: boolean
}

export function useSelectFetch(options: SelectFetchOptions) {
  const {
    endpoint,
    labelKey = 'name',
    valueKey = 'id',
    perPage = 100,
    buildLabel,
    staticParams = {},
    dynamicParams,
    immediate = true,
  } = options

  const items = ref<any[]>([])
  const selectOptions = ref<{ label: string; value: any }[]>([])
  const isLoading = ref(immediate)
  
  const defaultItems = ref<any[]>([])
  const hasFetchedDefault = ref(false)
  const lastDynamicParamsString = ref('{}')

  async function fetchOptions(query = '') {
    // If we're searching for the default list and we already have it cached, just restore it
    const currentDynamicParamsString = dynamicParams ? JSON.stringify(dynamicParams()) : '{}'
    
    if (query === '' && hasFetchedDefault.value && lastDynamicParamsString.value === currentDynamicParamsString) {
      items.value = defaultItems.value
      selectOptions.value = defaultItems.value.map((item: any) => ({
        label: buildLabel ? buildLabel(item) : String(item[labelKey] ?? ''),
        value: item[valueKey],
        status: item.status,
        is_active: item.is_active,
        deleted_at: item.deleted_at,
        raw: item,
      }))
      return
    }

    isLoading.value = true
    try {
      const params = new URLSearchParams()
      params.set('per_page', perPage.toString())
      params.set('sort_by', 'created_at')
      params.set('sort_desc', '1')
      if (query) params.set('search', query)
      for (const [key, val] of Object.entries(staticParams)) {
        params.set(key, String(val))
      }
      if (dynamicParams) {
        const dParams = dynamicParams()
        for (const [key, val] of Object.entries(dParams)) {
          if (val !== undefined && val !== null && val !== '') {
            params.set(key, String(val))
          }
        }
      }

      const res = await useApiFetch<any>(`${endpoint}?${params.toString()}`, { method: 'GET' })
      const data = res?.data?.data ?? res?.data ?? res ?? []
      const arr: any[] = Array.isArray(data)
        ? data
        : (Object.values(res || {}).find(v => Array.isArray(v)) as any[] || [])

      items.value = arr
      selectOptions.value = arr.map((item: any) => ({
        label: buildLabel ? buildLabel(item) : String(item[labelKey] ?? ''),
        value: item[valueKey],
        status: item.status,
        is_active: item.is_active,
        deleted_at: item.deleted_at,
        raw: item,
      }))

      // Cache the default list for the current dynamicParams
      if (query === '') {
        defaultItems.value = arr
        hasFetchedDefault.value = true
        lastDynamicParamsString.value = currentDynamicParamsString
      }
    } catch (err) {
      console.error(`[useSelectFetch] Failed to fetch ${endpoint}:`, err)
      items.value = []
      selectOptions.value = []
    } finally {
      isLoading.value = false
    }
  }

  if (immediate) {
    onMounted(() => fetchOptions())
  }

  return {
    items,
    selectOptions,
    isLoading,
    fetchOptions,
  }
}
