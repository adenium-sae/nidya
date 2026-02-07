<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

import { DataTable, type Column, type Filter, type Pagination } from '@/components/ui/data-table';
import { ClipboardList } from 'lucide-vue-next';
import { useToast } from '@/components/ui/toast/use-toast';

interface AdjustmentItem {
  id: string;
  product: {
    id: string;
    name: string;
    sku: string;
  };
  quantity_before: number;
  quantity_after: number;
}

interface Adjustment {
  id: string;
  folio: string;
  type: string;
  reason: string;
  notes?: string;
  warehouse: {
    id: string;
    name: string;
  };
  user?: {
    id: string;
    email: string;
  };
  items: AdjustmentItem[];
  created_at: string;
}

interface Warehouse {
  id: string;
  name: string;
}

const { toast } = useToast();
const adjustments = ref<Adjustment[]>([]);
const warehouses = ref<Warehouse[]>([]);
const isLoading = ref(true);
const searchQuery = ref('');
const filterValues = ref<Record<string, string>>({});

const pagination = ref<Pagination>({
  currentPage: 1,
  lastPage: 1,
  perPage: 50,
  total: 0
});

const adjustmentTypeLabels: Record<string, string> = {
  'increase': 'Entrada',
  'decrease': 'Salida',
  'recount': 'Recuento',
};

const reasonLabels: Record<string, string> = {
  'recount': 'Recuento Cíclico',
  'damaged': 'Producto Dañado',
  'lost': 'Pérdida/Robo',
  'found': 'Hallazgo',
  'expired': 'Caducado',
  'other': 'Otro',
};

// DataTable Configuration
const columns: Column[] = [
  { 
    key: 'folio', 
    label: 'Folio', 
    type: 'text',
    sortable: true
  },
  {
    key: 'created_at',
    label: 'Fecha',
    type: 'date',
    sortable: true
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
    key: 'reason',
    label: 'Razón',
    type: 'custom'
  },
  {
    key: 'user',
    label: 'Usuario',
    type: 'custom'
  },
  {
    key: 'items_count',
    label: 'Productos',
    type: 'custom',
    align: 'right'
  }
];

const filters = computed<Filter[]>(() => [
  {
    key: 'warehouse_id',
    label: 'Almacén',
    type: 'select',
    options: warehouses.value.map(w => ({ value: w.id, label: w.name }))
  },
  {
    key: 'type',
    label: 'Tipo',
    type: 'select',
    options: Object.entries(adjustmentTypeLabels).map(([value, label]) => ({ value, label }))
  },
  {
    key: 'reason',
    label: 'Razón',
    type: 'select',
    options: Object.entries(reasonLabels).map(([value, label]) => ({ value, label }))
  }
]);

async function fetchAdjustments() {
  isLoading.value = true;
  try {
    const token = localStorage.getItem('auth_token');
    const response = await axios.get('/api/admin/inventory/stock/adjustments', {
      headers: { Authorization: `Bearer ${token}` },
      params: {
        page: pagination.value.currentPage,
        per_page: pagination.value.perPage,
        ...filterValues.value
      }
    });
    
    const data = response.data;
    adjustments.value = data.data || data;
    
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
    console.error('Error fetching adjustments:', error);
    toast({
      title: 'Error',
      description: 'No se pudieron cargar los ajustes.',
      variant: 'destructive',
    });
  } finally {
    isLoading.value = false;
  }
}

async function fetchWarehouses() {
  try {
    const token = localStorage.getItem('auth_token');
    const response = await axios.get('/api/admin/warehouses', {
      headers: { Authorization: `Bearer ${token}` }
    });
    warehouses.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching warehouses:', error);
  }
}

function handleSearch(value: string) {
  searchQuery.value = value;
  pagination.value.currentPage = 1;
  fetchAdjustments();
}

function handleFilter(key: string, value: string) {
  if (value) {
    filterValues.value[key] = value;
  } else {
    delete filterValues.value[key];
  }
  pagination.value.currentPage = 1;
  fetchAdjustments();
}

function handlePageChange(page: number) {
  pagination.value.currentPage = page;
  fetchAdjustments();
}

function getTypeLabel(type: string): string {
  return adjustmentTypeLabels[type] || type;
}

function getTypeClass(type: string): string {
  const classes: Record<string, string> = {
    'increase': 'bg-green-100 text-green-800',
    'decrease': 'bg-red-100 text-red-800',
    'recount': 'bg-blue-100 text-blue-800',
  };
  return classes[type] || 'bg-gray-100 text-gray-800';
}

function getReasonLabel(reason: string): string {
  return reasonLabels[reason] || reason;
}

onMounted(function() {
  fetchAdjustments();
  fetchWarehouses();
});
</script>

<template>
  <div class="h-[calc(100vh-120px)] flex flex-col">
    <div class="flex flex-col gap-6 h-full">
      <DataTable
        :columns="columns"
        :data="adjustments"
        :is-loading="isLoading"
        :search-value="searchQuery"
        :filters="filters"
        :pagination="pagination"
        search-placeholder="Buscar por folio..."
        empty-message="No hay ajustes de inventario registrados."
        :empty-icon="ClipboardList"
        class="flex-1 min-h-0"
        @search="handleSearch"
        @filter="handleFilter"
        @page-change="handlePageChange"
      >
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

        <template #cell-reason="{ row }">
          {{ getReasonLabel(row.reason) }}
        </template>

        <template #cell-user="{ row }">
          {{ row.user?.email || '-' }}
        </template>

        <template #cell-items_count="{ row }">
          <span class="font-medium">{{ row.items?.length || 0 }}</span>
        </template>
      </DataTable>
    </div>
  </div>
</template>
