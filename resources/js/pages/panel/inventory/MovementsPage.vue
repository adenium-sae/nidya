<script setup lang="ts">
import { onMounted, computed, ref } from 'vue';
import { stockApi } from '@/api/stock.api';
import { useApiList } from '@/composables/useApiList';
import { DataTable, type Column, type Filter, type Pagination } from '@/components/ui/data-table';
import Button from '@/components/ui/button/Button.vue';
import { useToast } from '@/components/ui/toast/use-toast';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { ArrowUpDown, CheckCircle, XCircle } from 'lucide-vue-next';

const { toast } = useToast();

// Movement types matching the backend enum:
// ['entry', 'exit', 'transfer', 'adjustment', 'sale', 'return', 'damage', 'production']
const movementTypeLabels: Record<string, string> = {
  entry: 'Entrada',
  exit: 'Salida',
  transfer: 'Transferencia',
  adjustment: 'Ajuste',
  sale: 'Venta',
  return: 'Devolución',
  damage: 'Daño',
  production: 'Producción',
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
    entry: 'bg-green-100 text-green-800',
    exit: 'bg-red-100 text-red-800',
    transfer: 'bg-purple-100 text-purple-800',
    adjustment: 'bg-yellow-100 text-yellow-800',
    sale: 'bg-blue-100 text-blue-800',
    return: 'bg-cyan-100 text-cyan-800',
    damage: 'bg-orange-100 text-orange-800',
    production: 'bg-indigo-100 text-indigo-800',
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

// --- Confirm / Cancel Dialog ---
const showDialog = ref(false);
const dialogMovementId = ref<string | null>(null);
const dialogAction = ref<'confirm' | 'cancel'>('confirm');
const dialogMovementInfo = ref<{ product: string; type: string; quantity: number } | null>(null);
const isProcessing = ref(false);

function openConfirmDialog(row: any) {
  dialogMovementId.value = row.id;
  dialogAction.value = 'confirm';
  dialogMovementInfo.value = {
    product: row.product?.name || '-',
    type: getTypeLabel(row.type),
    quantity: row.quantity,
  };
  showDialog.value = true;
}

function openCancelDialog(row: any) {
  dialogMovementId.value = row.id;
  dialogAction.value = 'cancel';
  dialogMovementInfo.value = {
    product: row.product?.name || '-',
    type: getTypeLabel(row.type),
    quantity: row.quantity,
  };
  showDialog.value = true;
}

const dialogTitle = computed(() => {
  return dialogAction.value === 'confirm' ? 'Confirmar Movimiento' : 'Cancelar Movimiento';
});

const dialogDescription = computed(() => {
  if (dialogAction.value === 'confirm') {
    return '¿Estás seguro de que deseas confirmar este movimiento? El cambio de stock se marcará como completado.';
  }
  return '¿Estás seguro de que deseas cancelar este movimiento? Esta acción no se puede deshacer.';
});

async function executeDialogAction() {
  if (!dialogMovementId.value) return;

  isProcessing.value = true;
  try {
    if (dialogAction.value === 'confirm') {
      await stockApi.confirmMovement(dialogMovementId.value);
      toast({ title: 'Éxito', description: 'Movimiento confirmado correctamente.' });
    } else {
      await stockApi.cancelMovement(dialogMovementId.value);
      toast({ title: 'Éxito', description: 'Movimiento cancelado correctamente.' });
    }
    showDialog.value = false;
    dialogMovementId.value = null;
    dialogMovementInfo.value = null;
    fetchMovements();
  } catch (error: any) {
    const message = error?.response?.data?.message || 'No se pudo procesar la acción.';
    toast({ title: 'Error', description: message, variant: 'destructive' });
  } finally {
    isProcessing.value = false;
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
          <div class="flex items-center justify-end gap-1">
            <Button
              v-if="row.status === 'pending'"
              variant="ghost"
              size="sm"
              class="h-8 px-2 text-green-600 hover:text-green-700 hover:bg-green-50"
              @click.stop="openConfirmDialog(row)"
            >
              <CheckCircle class="h-4 w-4 mr-1" />
              Confirmar
            </Button>
            <Button
              v-if="row.status === 'pending'"
              variant="ghost"
              size="sm"
              class="h-8 px-2 text-red-600 hover:text-red-700 hover:bg-red-50"
              @click.stop="openCancelDialog(row)"
            >
              <XCircle class="h-4 w-4 mr-1" />
              Cancelar
            </Button>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Confirm / Cancel Dialog -->
    <Dialog v-model:open="showDialog">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{{ dialogTitle }}</DialogTitle>
          <DialogDescription>{{ dialogDescription }}</DialogDescription>
        </DialogHeader>

        <div v-if="dialogMovementInfo" class="rounded-md border p-4 space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-muted-foreground">Producto:</span>
            <span class="font-medium">{{ dialogMovementInfo.product }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">Tipo:</span>
            <span>{{ dialogMovementInfo.type }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">Cantidad:</span>
            <span
              :class="dialogMovementInfo.quantity >= 0 ? 'text-green-600' : 'text-red-600'"
              class="font-medium"
            >
              {{ dialogMovementInfo.quantity >= 0 ? '+' : '' }}{{ dialogMovementInfo.quantity }}
            </span>
          </div>
        </div>

        <DialogFooter class="gap-2">
          <Button variant="outline" @click="showDialog = false" :disabled="isProcessing">
            Volver
          </Button>
          <Button
            :variant="dialogAction === 'cancel' ? 'destructive' : 'default'"
            :disabled="isProcessing"
            @click="executeDialogAction"
          >
            <CheckCircle v-if="dialogAction === 'confirm'" class="h-4 w-4 mr-1" />
            <XCircle v-else class="h-4 w-4 mr-1" />
            {{ isProcessing ? 'Procesando...' : (dialogAction === 'confirm' ? 'Confirmar' : 'Cancelar Movimiento') }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>