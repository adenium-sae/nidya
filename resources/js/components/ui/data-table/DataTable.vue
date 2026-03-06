<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import Button from '@/components/ui/button/Button.vue'
import Input from '@/components/ui/input/Input.vue'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { 
  Search, 
  ChevronLeft, 
  ChevronRight,
  MoreHorizontal,
  Pencil,
  Trash2,
  Eye,
  ArrowUpDown
} from 'lucide-vue-next'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Spinner } from "@/components/ui/spinner"
import { SearchableSelect } from '@/components/ui/searchable-select'
import { useI18n } from 'vue-i18n'

// Types
export interface Column {
  key: string
  label: string
  sortable?: boolean
  width?: string
  align?: 'left' | 'center' | 'right'
  // For custom rendering
  type?: 'text' | 'image' | 'badge' | 'currency' | 'date' | 'boolean' | 'custom'
  badgeVariants?: Record<string, { label: string; class: string }>
  currencyCode?: string
  dateFormat?: string
}

export interface Action {
  key: string
  label: string
  icon?: any
  variant?: 'default' | 'destructive'
  show?: (row: any) => boolean
}

export interface Filter {
  key: string
  label: string
  type: 'select' | 'text' | 'searchable-select'
  options?: { value: string; label: string }[]
  endpoint?: string
  labelKey?: string
  valueKey?: string
  searchKey?: string
  placeholder?: string
}

export interface Pagination {
  currentPage: number
  lastPage: number
  perPage: number
  total: number
}

const props = withDefaults(defineProps<{
  columns: Column[]
  data: any[]
  actions?: Action[]
  filters?: Filter[]
  pagination?: Pagination
  showSearch?: boolean
  searchPlaceholder?: string
  searchValue?: string
  isLoading?: boolean
  emptyMessage?: string
  emptyIcon?: any
  rowKey?: string
  filterValues?: Record<string, string>
}>(), {
  actions: () => [],
  filters: () => [],
  showSearch: true,
  searchPlaceholder: '',
  searchValue: '',
  isLoading: false,
  emptyMessage: '',
  rowKey: 'id',
  filterValues: () => ({})
})

const { t } = useI18n()

const emit = defineEmits<{
  (e: 'search', value: string): void
  (e: 'filter', key: string, value: string): void
  (e: 'page-change', page: number): void
  (e: 'per-page-change', perPage: number): void
  (e: 'action', action: string, row: any): void
  (e: 'sort', column: string, direction: 'asc' | 'desc'): void
  (e: 'row-click', row: any): void
}>()

function formatCurrency(value: number | string, currency = 'MXN') {
  const num = typeof value === 'string' ? parseFloat(value) : value;
  if (isNaN(num)) return '$0.00';
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency
  }).format(num);
}

function formatDate(value: string, format = 'short') {
  if (!value) return '-';
  const date = new Date(value);
  return date.toLocaleDateString('es-MX', {
    year: 'numeric',
    month: format === 'long' ? 'long' : 'short',
    day: 'numeric'
  });
}

function getCellValue(row: any, column: Column) {
  const value = row[column.key];
  
  switch (column.type) {
    case 'currency':
      return formatCurrency(value, column.currencyCode);
    case 'date':
      return formatDate(value, column.dateFormat);
    case 'boolean':
      return value ? t('common.yes') : t('common.no');
    default:
      return value ?? '-';
  }
}

function getCellClass(column: Column) {
  const classes = [];
  if (column.align === 'center') classes.push('text-center');
  if (column.align === 'right') classes.push('text-right');
  if (column.width) classes.push(column.width);
  return classes.join(' ');
}

function getHeaderClass(column: Column) {
  const classes = [];
  if (column.align === 'center') classes.push('text-center');
  if (column.align === 'right') classes.push('text-right');
  if (column.sortable) classes.push('cursor-pointer hover:text-foreground transition-colors');
  return classes.join(' ');
}

function visibleActions(row: any) {
  return props.actions.filter(action => !action.show || action.show(row));
}

function getActionIcon(action: Action) {
  if (action.icon) return action.icon;
  switch (action.key) {
    case 'edit': return Pencil;
    case 'delete': return Trash2;
    case 'view': return Eye;
    default: return null;
  }
}

const paginationInfo = computed(function() {
  if (!props.pagination) return '';
  const { currentPage, perPage, total } = props.pagination;
  const from = (currentPage - 1) * perPage + 1;
  const to = Math.min(currentPage * perPage, total);
  return `${from}-${to} ${t('common.of')} ${total}`;
});

const perPageOptions = [10, 15, 25, 50, 100]

const localSearch = ref(props.searchValue || '')
watch(() => props.searchValue, (v) => {
  if (v !== localSearch.value) localSearch.value = v || ''
})

const debouncedSearch = useDebounceFn((v: string) => emit('search', v), 350)

function handleSearchInput(v: string | number | undefined) {
  localSearch.value = String(v ?? '')
  debouncedSearch(String(v ?? ''))
}
</script>

<template>
  <div class="space-y-4 h-full flex flex-col">
    <!-- Toolbar: Search & Filters -->
    <div v-if="showSearch || filters.length > 0" class="flex flex-wrap items-center gap-3 flex-shrink-0">
      <!-- Search -->
      <div v-if="showSearch" class="relative flex-1 min-w-[200px] max-w-sm">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input 
          :model-value="localSearch"
          :placeholder="searchPlaceholder || t('common.search')"
          class="pl-9"
          @update:model-value="handleSearchInput"
        />
      </div>

      <!-- Filters -->
      <div v-for="filter in filters" :key="filter.key" class="min-w-[150px]">
        <Select 
          v-if="filter.type === 'select'"
          :model-value="filterValues[filter.key] || '_ALL_'"
          @update:model-value="(val) => emit('filter', filter.key, String(val === '_ALL_' ? '' : val))"
        >
          <SelectTrigger>
            <SelectValue :placeholder="filter.placeholder || filter.label" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="_ALL_">{{ filter.label }}: {{ t('common.all') }}</SelectItem>
            <SelectItem 
              v-for="option in filter.options" 
              :key="option.value" 
              :value="option.value"
            >
              {{ option.label }}
            </SelectItem>
          </SelectContent>
        </Select>

        <!-- Searchable Select Filter -->
        <div v-else-if="filter.type === 'searchable-select'">
          <SearchableSelect
            :model-value="filterValues[filter.key] || null"
            :placeholder="filter.placeholder || filter.label"
            :endpoint="filter.endpoint"
            :options="filter.options"
            :label-key="filter.labelKey"
            :value-key="filter.valueKey"
            :search-key="filter.searchKey"
            @update:model-value="(val) => emit('filter', filter.key, String(val || ''))"
          />
        </div>
      </div>

      <!-- Slot for extra toolbar items (like create button) -->
      <div class="flex-1"></div>
      <slot name="toolbar-end"></slot>
    </div>

    <!-- Table Container -->
    <div class="rounded-md border bg-card flex-1 flex flex-col overflow-hidden w-full min-w-0">
      <!-- Scrollable Table Wrapper -->
      <div class="flex-1 overflow-auto flex flex-col min-w-0 w-full">
        <Table class="flex-1 h-full">
          <TableHeader class="sticky top-0 z-10 bg-card shadow-sm">
            <TableRow class="hover:bg-transparent">
              <TableHead 
                v-for="column in columns" 
                :key="column.key"
                :class="getHeaderClass(column)"
                @click="column.sortable && emit('sort', column.key, 'asc')"
              >
                <div class="flex items-center gap-1" :class="{ 'justify-center': column.align === 'center', 'justify-end': column.align === 'right' }">
                  {{ column.label }}
                  <ArrowUpDown v-if="column.sortable" class="h-3 w-3 opacity-50" />
                </div>
              </TableHead>
              <TableHead v-if="actions.length > 0" class="text-right w-[100px]">
                {{ t('common.actions') }}
              </TableHead>
            </TableRow>
          </TableHeader>
          
          <TableBody class="h-full">
            <!-- Loading State -->
            <TableRow v-if="isLoading">
              <TableCell :colspan="columns.length + (actions.length > 0 ? 1 : 0)" class="h-24 text-center">
                <div class="flex items-center justify-center">
                  <Spinner />
                  <span class="ml-3 text-muted-foreground">{{ t('common.loading') }}</span>
                </div>
              </TableCell>
            </TableRow>
            
            <!-- Empty State -->
            <TableRow v-else-if="data.length === 0" class="h-full hover:bg-transparent border-0">
              <TableCell :colspan="columns.length + (actions.length > 0 ? 1 : 0)" class="h-full p-0 text-center">
                <div class="flex flex-col items-center justify-center text-center h-full min-h-[300px]">
                  <component v-if="emptyIcon" :is="emptyIcon" class="h-16 w-16 text-muted-foreground/20 mb-4" />
                  <p class="text-muted-foreground text-xl font-medium">{{ emptyMessage || t('common.no_results') }}</p>
                  <p class="text-muted-foreground/60 text-sm mt-1">{{ t('common.empty_desc') }}</p>
                </div>
              </TableCell>
            </TableRow>
            
            <!-- Data Rows -->
            <TableRow 
              v-else
              v-for="row in data" 
              :key="row[rowKey]"
              class="group cursor-pointer hover:bg-muted/50"
              @click="emit('row-click', row)"
            >
              <TableCell 
                v-for="column in columns" 
                :key="column.key"
                :class="getCellClass(column)"
              >
                <!-- Image type -->
                <template v-if="column.type === 'image'">
                  <div class="flex items-center gap-3">
                    <div class="w-10 rounded-lg bg-muted overflow-hidden flex-shrink-0 aspect-square">
                      <img 
                        v-if="row[column.key]" 
                        :src="row[column.key]" 
                        :alt="row.name || ''"
                        class="h-full w-full object-cover"
                      />
                      <div v-else class="h-full w-full flex items-center justify-center text-muted-foreground text-xs">
                        N/A
                      </div>
                    </div>
                  </div>
                </template>

                <!-- Badge type -->
                <template v-else-if="column.type === 'badge' && column.badgeVariants">
                  <span 
                    :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      column.badgeVariants[row[column.key]]?.class || 'bg-gray-100 text-gray-800'
                    ]"
                  >
                    {{ column.badgeVariants[row[column.key]]?.label || row[column.key] }}
                  </span>
                </template>

                <!-- Boolean type -->
                <template v-else-if="column.type === 'boolean'">
                  <span 
                    :class="[
                      'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                      row[column.key] ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                    ]"
                  >
                    {{ row[column.key] ? t('common.yes') : t('common.no') }}
                  </span>
                </template>

                <!-- Custom slot -->
                <template v-else-if="column.type === 'custom'">
                  <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
                    {{ getCellValue(row, column) }}
                  </slot>
                </template>

                <!-- Default text/currency/date -->
                <template v-else>
                  <div :class="column.type === 'currency' ? 'font-medium tabular-nums truncate max-w-[200px]' : 'truncate max-w-[300px]'">
                    {{ getCellValue(row, column) }}
                  </div>
                </template>
              </TableCell>

              <!-- Actions Column -->
              <TableCell v-if="actions.length > 0" class="text-right">
                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity" @click.stop>
                  <!-- If only 1-2 actions, show inline buttons -->
                  <template v-if="visibleActions(row).length <= 2">
                    <Button
                      v-for="action in visibleActions(row)"
                      :key="action.key"
                      variant="ghost"
                      size="icon"
                      class="h-8 w-8"
                      :class="action.variant === 'destructive' ? 'hover:text-destructive hover:bg-destructive/10' : 'hover:bg-primary/10'"
                      @click="emit('action', action.key, row)"
                      :title="action.label"
                    >
                      <component :is="getActionIcon(action)" class="h-4 w-4" />
                    </Button>
                  </template>

                  <!-- If more actions, show dropdown -->
                  <template v-else>
                    <DropdownMenu>
                      <DropdownMenuTrigger as-child>
                        <Button variant="ghost" size="icon" class="h-8 w-8">
                          <MoreHorizontal class="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem
                          v-for="action in visibleActions(row)"
                          :key="action.key"
                          @click="emit('action', action.key, row)"
                          :class="action.variant === 'destructive' ? 'text-destructive focus:text-destructive' : ''"
                        >
                          <component v-if="getActionIcon(action)" :is="getActionIcon(action)" class="h-4 w-4 mr-2" />
                          {{ action.label }}
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </template>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Pagination (Fixed at bottom) -->
      <div v-if="pagination && pagination.total > 0" class="flex items-center justify-between px-4 py-3 border-t bg-muted/30 flex-shrink-0">
        <div class="flex items-center gap-2 text-sm text-muted-foreground">
          <span>{{ t('common.show') }}</span>
          <Select 
            :model-value="pagination.perPage.toString()"
            @update:model-value="(val) => emit('per-page-change', parseInt(String(val)))"
          >
            <SelectTrigger class="w-[70px] h-8">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="n in perPageOptions" :key="n" :value="n.toString()">
                {{ n }}
              </SelectItem>
            </SelectContent>
          </Select>
          <span>{{ t('common.per_page') }}</span>
        </div>

        <div class="flex items-center gap-4">
          <span class="text-sm text-muted-foreground">{{ paginationInfo }}</span>
          
          <div class="flex items-center gap-1">
            <Button 
              variant="outline" 
              size="icon"
              class="h-8 w-8"
              :disabled="pagination.currentPage <= 1"
              @click="emit('page-change', pagination.currentPage - 1)"
            >
              <ChevronLeft class="h-4 w-4" />
            </Button>
            <Button 
              variant="outline" 
              size="icon"
              class="h-8 w-8"
              :disabled="pagination.currentPage >= pagination.lastPage"
              @click="emit('page-change', pagination.currentPage + 1)"
            >
              <ChevronRight class="h-4 w-4" />
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
