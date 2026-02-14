<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import axios from 'axios'
import { useDebounceFn } from '@vueuse/core'
import { Check, ChevronsUpDown, Search, Loader2, Plus } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'

export interface SearchableSelectOption {
  value: string
  label: string
  [key: string]: any
}

const props = withDefaults(defineProps<{
  modelValue?: string | null
  placeholder?: string
  searchPlaceholder?: string
  emptyMessage?: string
  endpoint?: string
  options?: SearchableSelectOption[]
  labelKey?: string
  valueKey?: string
  searchKey?: string
  disabled?: boolean
  minSearchLength?: number
  debounceMs?: number
  showAddOption?: boolean
  addOptionLabel?: string
}>(), {
  placeholder: 'Seleccionar...',
  searchPlaceholder: 'Buscar...',
  emptyMessage: 'No se encontraron resultados.',
  options: () => [],
  labelKey: 'name',
  valueKey: 'id',
  searchKey: 'search',
  disabled: false,
  minSearchLength: 0,
  debounceMs: 300,
  showAddOption: false,
  addOptionLabel: 'Agregar nuevo'
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | null): void
  (e: 'select', option: SearchableSelectOption | null): void
  (e: 'add-click'): void
}>()

const open = ref(false)
const searchQuery = ref('')
const isLoading = ref(false)
const fetchedOptions = ref<SearchableSelectOption[]>([])

// Use fetched options if endpoint is provided, otherwise use static options
const displayOptions = computed(() => {
  if (props.endpoint) {
    return fetchedOptions.value
  }
  
  // Filter static options by search query
  if (!searchQuery.value) {
    return props.options
  }
  
  const query = searchQuery.value.toLowerCase()
  return props.options.filter(opt => 
    opt.label.toLowerCase().includes(query) ||
    opt.value.toLowerCase().includes(query)
  )
})

const selectedOption = computed(() => {
  if (!props.modelValue) return null
  
  // First check in displayed options
  const found = displayOptions.value.find(opt => opt.value === props.modelValue)
  if (found) return found
  
  // Check in static options
  return props.options.find(opt => opt.value === props.modelValue) || null
})

const displayLabel = computed(() => {
  return selectedOption.value?.label || props.placeholder
})

async function fetchOptions(query: string) {
  if (!props.endpoint) return
  if (query.length < props.minSearchLength) {
    fetchedOptions.value = []
    return
  }
  
  isLoading.value = true
  try {
    const token = localStorage.getItem('auth_token')
    const response = await axios.get(props.endpoint, {
      headers: { Authorization: `Bearer ${token}` },
      params: { [props.searchKey]: query }
    })
    
    const data = response.data.data || response.data
    
    // Map response to standard format
    fetchedOptions.value = Array.isArray(data) 
      ? data.map(item => ({
          value: String(item[props.valueKey]),
          label: String(item[props.labelKey]),
          ...item
        }))
      : []
  } catch (error) {
    console.error('Error fetching options:', error)
    fetchedOptions.value = []
  } finally {
    isLoading.value = false
  }
}

const debouncedFetch = useDebounceFn(fetchOptions, props.debounceMs)

function handleSearch(value: string) {
  searchQuery.value = value
  if (props.endpoint) {
    debouncedFetch(value)
  }
}

function handleSelect(option: SearchableSelectOption) {
  emit('update:modelValue', option.value)
  emit('select', option)
  open.value = false
  searchQuery.value = ''
}

function handleClear() {
  emit('update:modelValue', null)
  emit('select', null)
}

// Load initial options when opened (for endpoint mode)
watch(open, (isOpen) => {
  if (isOpen && props.endpoint && fetchedOptions.value.length === 0) {
    fetchOptions('')
  }
})
</script>

<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        role="combobox"
        :aria-expanded="open"
        :disabled="disabled"
        class="w-full justify-between font-normal"
      >
        <span :class="cn('truncate flex-1 text-left', !selectedOption && 'text-muted-foreground')">
          {{ displayLabel }}
        </span>
        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-[--radix-popover-trigger-width] p-0" align="start">
      <Command>
        <div class="flex items-center border-b px-3">
          <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
          <input
            v-model="searchQuery"
            :placeholder="searchPlaceholder"
            class="flex h-10 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
            @input="handleSearch(($event.target as HTMLInputElement).value)"
          />
          <Loader2 v-if="isLoading" class="ml-2 h-4 w-4 animate-spin opacity-50" />
        </div>
        <CommandList class="flex flex-col h-full">
          <div v-if="isLoading" class="py-6 text-center">
            <Loader2 class="h-6 w-6 animate-spin mx-auto text-muted-foreground opacity-50" />
            <p class="text-xs text-muted-foreground mt-2">Cargando...</p>
          </div>

          <template v-else>
            <div v-if="displayOptions.length === 0" class="py-6 text-center">
              <p class="text-sm text-muted-foreground">{{ emptyMessage }}</p>
            </div>

            <CommandGroup v-if="displayOptions.length > 0" class="flex-1 overflow-auto">
              <CommandItem
                v-for="option in displayOptions"
                :key="option.value"
                :value="option.value"
                @select="handleSelect(option)"
              >
                <Check
                  :class="cn(
                    'mr-2 h-4 w-4',
                    modelValue === option.value ? 'opacity-100' : 'opacity-0'
                  )"
                />
                {{ option.label }}
              </CommandItem>
            </CommandGroup>

            <!-- Always show Add New at the bottom if enabled -->
            <div v-if="showAddOption" class="p-2 border-t bg-muted/5 mt-auto">
              <Button 
                type="button" 
                variant="outline" 
                size="sm" 
                class="w-full justify-start font-normal h-9 text-primary hover:text-primary hover:bg-primary/10 border-dashed"
                @click="emit('add-click')"
              >
                <Plus class="mr-2 h-4 w-4" />
                {{ addOptionLabel }}
              </Button>
            </div>
          </template>
        </CommandList>
      </Command>
    </PopoverContent>
  </Popover>
</template>
