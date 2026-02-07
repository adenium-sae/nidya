<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

import { DataTable, type Column, type Filter, type Pagination } from '@/components/ui/data-table';
import { ArrowUpDown } from 'lucide-vue-next';
import { useToast } from '@/components/ui/toast/use-toast';

interface Movement {
  id: string;
  product: {
    id: string;
    name: string;
    sku: string;
  };
  warehouse: {
    id: string;
    name: string;
  };
  user?: {
    id: string;
    email: string;
  };
  type: string;
  quantity: number;
  quantity_before: number;
  quantity_after: number;
  notes?: string;
  reference?: string;
  created_at: string;
}

interface Warehouse {
  id: string;
  name: string;
}

const { toast } = useToast();
const movements = ref<Movement[]>([]);

const isLoading = ref(true);
const searchQuery = ref('');
const filterValues = ref<Record<string, string>>({});

const pagination = ref<Pagination>({
  currentPage: 1,
  lastPage: 1,
  perPage: 50,
  total: 0
});

const movementTypeLabels: Record<string, string> = {
  'sale': 'Venta',
  'purchase': 'Compra',
  'adjustment': 'Ajuste',
  'transfer_in': 'Transferencia Entrada',
  'transfer_out': 'Transferencia Salida',
  'return': 'Devolución',
};

// DataTable Configuration
const columns: Column[] = [
  {
    key: 'created_at',
    label: 'Fecha',
    type: 'date',
    sortable: true
  },
  { 
    key: 'product', 
    label: 'Producto', 
    type: 'custom'
  },
  { 
    key: 'warehouse', 
    label: 'Almacén', 
    type: 'custom' 
  },
  {
    key: 'type',
    label: 'Tipo',
    type: 'custom'
  },
  {
    key: 'quantity',
    label: 'Cantidad',
    type: 'custom',
    align: 'right'
  },
  {
    key: 'stock_change',
    label: 'Stock',
    type: 'custom',
    align: 'right'
  },
  {
    key: 'user',
    label: 'Usuario',
    type: 'custom'
  }
];

const filters = computed<Filter[]>(() => [
  {
    key: 'warehouse_id',
    label: 'Almacén',
    type: 'searchable-select',
    endpoint: '/api/admin/warehouses',
    labelKey: 'name',
    valueKey: 'id',
    placeholder: 'Buscar almacén...'
  },
  {
    key: 'type',
    label: 'Tipo',
    type: 'select',
    options: Object.entries(movementTypeLabels).map(([value, label]) => ({ value, label }))
  }
]);

async function fetchMovements() {
  isLoading.value = true;
  try {
    const token = localStorage.getItem('auth_token');
    const response = await axios.get('/api/admin/inventory/stock/movements', {
      headers: { Authorization: `Bearer ${token}` },
      params: {
        page: pagination.value.currentPage,
        per_page: pagination.value.perPage,
        ...filterValues.value
      }
    });
    
    const data = response.data;
    movements.value = data.data || data;
    
    if (data.meta) {
      pagination.value = {
        currentPage: data.meta.current_page,
        lastPage: data.meta.last_page,
        perPage: data.meta.per_page,
        total: data.meta.total
      };
    } else if (data.current_page) {
      pagination.value = {
        currentPage: data.current_page,
        lastPage: data.last_page,
        perPage: data.per_page,
        total: data.total
      };
    }
  } catch (error) {
    console.error('Error fetching movements:', error);
    toast({
      title: 'Error',
      description: 'No se pudieron cargar los movimientos.',
      variant: 'destructive',
    });
  } finally {
    isLoading.value = false;
  }
}



function handleSearch(value: string) {
  searchQuery.value = value;
  pagination.value.currentPage = 1;
  fetchMovements();
}

function handleFilter(key: string, value: string) {
  if (value) {
    filterValues.value[key] = value;
  } else {
    delete filterValues.value[key];
  }
  pagination.value.currentPage = 1;
  fetchMovements();
}

function handlePageChange(page: number) {
  pagination.value.currentPage = page;
  fetchMovements();
}

function getTypeLabel(type: string): string {
  return movementTypeLabels[type] || type;
}

function getTypeClass(type: string): string {
  const classes: Record<string, string> = {
    'sale': 'bg-blue-100 text-blue-800',
    'purchase': 'bg-green-100 text-green-800',
    'adjustment': 'bg-yellow-100 text-yellow-800',
    'transfer_in': 'bg-purple-100 text-purple-800',
    'transfer_out': 'bg-orange-100 text-orange-800',
    'return': 'bg-gray-100 text-gray-800',
  };
  return classes[type] || 'bg-gray-100 text-gray-800';
}

onMounted(function() {
  fetchMovements();
});
</script>

<template>
  <div class="h-[calc(100vh-120px)] flex flex-col">
    <div class="flex flex-col gap-6 h-full">
      <DataTable
        :columns="columns"
        :data="movements"
        :is-loading="isLoading"
        :search-value="searchQuery"
        :filters="filters"
        :pagination="pagination"
        search-placeholder="Buscar por producto..."
        empty-message="No hay movimientos registrados."
        :empty-icon="ArrowUpDown"
        class="flex-1 min-h-0"
        @search="handleSearch"
        @filter="handleFilter"
        @page-change="handlePageChange"
      >
        <template #cell-product="{ row }">
          <div>
            <div class="font-medium">{{ row.product?.name }}</div>
            <div class="text-sm text-muted-foreground">{{ row.product?.sku }}</div>
          </div>
        </template>

        <template #cell-warehouse="{ row }">
          {{ row.warehouse?.name || '-' }}
        </template>

        <template #cell-type="{ row }">
          <span 
            :class="getTypeClass(row.type)"
            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
          >
            {{ getTypeLabel(row.type) }}
          </span>
        </template>

        <template #cell-quantity="{ row }">
          <span 
            :class="row.quantity >= 0 ? 'text-green-600' : 'text-red-600'"
            class="font-medium"
          >
            {{ row.quantity >= 0 ? '+' : '' }}{{ row.quantity }}
          </span>
        </template>

        <template #cell-stock_change="{ row }">
          <span class="text-muted-foreground">
            {{ row.quantity_before }} → <span class="font-medium text-foreground">{{ row.quantity_after }}</span>
          </span>
        </template>

        <template #cell-user="{ row }">
          {{ row.user?.email || '-' }}
        </template>
      </DataTable>
    </div>
  </div>
</template>
