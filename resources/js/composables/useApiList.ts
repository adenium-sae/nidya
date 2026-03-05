import { ref, type Ref } from 'vue'
import { useToast } from '@/components/ui/toast/use-toast'
import { useI18n } from 'vue-i18n'

interface ApiListOptions {
  perPage?: number;
  immediate?: boolean;
}

interface Pagination {
  currentPage: number;
  lastPage: number;
  perPage: number;
  total: number;
}

export function useApiList<T = any>(apiFn: (params: any) => Promise<any>, options: ApiListOptions = {}) {
  const { toast } = useToast()
  const { t } = useI18n()
  
  const items = ref<T[]>([]) as Ref<T[]>
  const isLoading = ref(false)
  const searchQuery = ref('')
  const filterValues = ref<Record<string, any>>({})
  const pagination = ref<Pagination>({
    currentPage: 1,
    lastPage: 1,
    perPage: options.perPage || 15,
    total: 0,
  })

  async function fetch(page?: number) {
    if (page !== undefined) {
      pagination.value.currentPage = page
    }
    
    isLoading.value = true
    try {
      const params = {
        page: pagination.value.currentPage,
        per_page: pagination.value.perPage,
        ...(searchQuery.value ? { search: searchQuery.value } : {}),
        ...filterValues.value,
      }
      
      const response = await apiFn(params)
      const data = response.data

      // Support Laravel paginated responses
      if (data.data && (data.meta || data.current_page)) {
        items.value = data.data
        const meta = data.meta || data
        pagination.value = {
          currentPage: meta.current_page,
          lastPage: meta.last_page,
          perPage: meta.per_page,
          total: meta.total,
        }
      } else if (Array.isArray(data)) {
        items.value = data
        pagination.value.total = data.length
      } else if (data.data && Array.isArray(data.data)) {
        items.value = data.data
        pagination.value.total = data.data.length
      } else {
        items.value = []
      }
    } catch (error) {
      console.error('Error fetching list:', error)
      toast({
        title: t('common.error'),
        description: t('common.no_results'),
        variant: 'destructive',
      })
    } finally {
      isLoading.value = false
    }
  }

  function search(query: string) {
    searchQuery.value = query
    fetch(1)
  }

  function filter(key: string, value: any) {
    if (value) {
      filterValues.value[key] = value
    } else {
      delete filterValues.value[key]
    }
    fetch(1)
  }

  function changePage(page: number) {
    fetch(page)
  }

  function changePerPage(perPage: number) {
    pagination.value.perPage = perPage
    fetch(1)
  }

  function refresh() {
    fetch(pagination.value.currentPage)
  }

  // Remove an item from the local list (after delete)
  function removeItem(id: string | number, idKey: string = 'id') {
    items.value = items.value.filter((item: any) => item[idKey] !== id)
    pagination.value.total = Math.max(0, pagination.value.total - 1)
  }

  // Update an item in the local list
  function updateItem(id: string | number, updatedData: Partial<T>, idKey: string = 'id') {
    const index = items.value.findIndex((item: any) => item[idKey] === id)
    if (index !== -1) {
      items.value[index] = { ...items.value[index], ...updatedData }
    }
  }

  if (options.immediate) {
    fetch()
  }

  return {
    items,
    isLoading,
    searchQuery,
    filterValues,
    pagination,
    fetch,
    search,
    filter,
    changePage,
    changePerPage,
    refresh,
    removeItem,
    updateItem,
  }
}
