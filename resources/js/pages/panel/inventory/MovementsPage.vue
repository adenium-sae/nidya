<script setup lang="ts">
import { onMounted, computed } from 'vue';
import { stockApi } from '@/api/stock.api';
import { useApiList } from '@/composables/useApiList';
import { DataTable, type Column, type Filter, type Pagination } from '@/components/ui/data-table';
import Button from '@/components/ui/button/Button.vue';
import { useToast } from '@/components/ui/toast/use-toast';
import { ArrowUpDown, CheckCircle } from 'lucide-vue-next';

const { toast } = useToast();

const movementTypeLabels: Record<string, string> = {
  sale: 'Venta',
  purchase: 'Compra',
  adjustment: 'Ajuste',
  transfer_in: 'Transferencia Entrada',
  transfer_out: 'Transferencia Salida',
  return: 'Devolución',
};

const statusLabels: Record<string, string> = {
  pending: 'Pendiente',
  completed: 'Completado',
  cancelled: 'Cancelado',
};

const {
  items: movements,
  isLoading,
  searchQuery,
  filterValues,
  pagination,
  fetch: fetchMovements,
  search,
  filter,
  changePage,
} = useApiList(stockApi.movements, { perPage: 50 });

const columns: Column[] = [
  { key: 'created_at', label: 'Fecha', type: 'date', sortable: true },
  { key: 'product', label: 'Producto', type: 'custom' },
  { key: 'warehouse', label: 'Almacén', type: 'custom' },
  { key: 'type', label: 'Tipo', type: 'custom' },
  { key: 'quantity', label: 'Cantidad', type: 'custom', align: 'right' },
  { key: 'stock_change', label: 'Stock', type: 'custom', align: 'right' },
  { key: 'status', label: 'Estado', type: 'custom' },
  { key: 'user', label: 'Usuario', type: 'custom' },
  { key: 'actions', label: '', type: 'custom', align: 'right' },
];

const filters = computed<Filter[]>(() => [
  {
    key: 'warehouse_id',
    label: 'Almacén',
    type: 'searchable-select',
    endpoint: '/api/admin/warehouses',
    labelKey: 'name',
    valueKey: 'id',
    placeholder: 'Buscar almacén...',
  },
  {
    key: 'type',
    label: 'Tipo',
    type: 'select',
    options: Object.entries(movementTypeLabels).map(([value, label]) => ({ value, label })),
  },
]);

function getTypeLabel(type: string): string {
  return movementTypeLabels[type] || type;
}

function getTypeClass(type: string): string {
  const classes: Record<string, string> = {
    sale: 'bg-blue-100 text-blue-800',
    purchase: 'bg-green-100 text-green-800',
    adjustment: 'bg-yellow-100 text-yellow-800',
    transfer_in: 'bg-purple-100 text-purple-800',
    transfer_out: 'bg-orange-100 text-orange-800',
    return: 'bg-gray-100 text-gray-800',
  };
  return classes[type] || 'bg-gray-100 text-gray-800';
}

function getStatusClass(status: string): string {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
}

async function confirmMovement(id: string) {
  try {
    await stockApi.confirmMovement(id);
    toast({ title: 'Éxito', description: 'Movimiento confirmado correctamente.' });
    fetchMovements();
  } catch (error) {
    toast({ title: 'Error', description: 'No se pudo confirmar el movimiento.', variant: 'destructive' });
  }
}

onMounted(() => fetchMovements());
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
        :filter-values="filterValues"
        :pagination="pagination"
        search-placeholder="Buscar por producto..."
        empty-message="No hay movimientos registrados."
        :empty-icon="ArrowUpDown"
        class="flex-1 min-h-0"
        @search="search"
        @filter="filter"
        @page-change="changePage"
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

        <template #cell-status="{ row }">
          <span
            :class="getStatusClass(row.status)"
            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
          >
            {{ statusLabels[row.status] || row.status }}
          </span>
        </template>

        <template #cell-actions="{ row }">
          <Button
            v-if="row.status === 'pending'"
            variant="ghost"
            size="sm"
            class="h-8 px-2 text-green-600 hover:text-green-700 hover:bg-green-50"
            @click="confirmMovement(row.id)"
          >
            <CheckCircle class="h-4 w-4 mr-1" />
            Confirmar
          </Button>
        </template>
      </DataTable>
    </div>
  </div>
</template>
