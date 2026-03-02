<script setup lang="ts">
import { onMounted, computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { stockApi } from '@/api/stock.api';
import { useApiList } from '@/composables/useApiList';
import { DataTable, type Column, type Filter } from '@/components/ui/data-table';
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
import {
  ArrowRightLeft,
  CheckCircle,
  XCircle,
  Plus,
  Package,
  ArrowRight,
} from 'lucide-vue-next';

import type { StockTransfer } from '@/types/models';

const router = useRouter();
const { toast } = useToast();

const statusLabels: Record<string, string> = {
  pending: 'Pendiente',
  in_transit: 'En Tránsito',
  completed: 'Completada',
  cancelled: 'Cancelada',
};

const {
  items: transfers,
  isLoading,
  searchQuery,
  filterValues,
  pagination,
  fetch: fetchTransfers,
  search,
  filter,
  changePage,
} = useApiList<StockTransfer>(stockApi.transfers, { perPage: 50 });

const columns: Column[] = [
  { key: 'folio', label: 'Folio', type: 'custom', sortable: true },
  { key: 'created_at', label: 'Fecha', type: 'date', sortable: true },
  { key: 'source_warehouse', label: 'Origen', type: 'custom' },
  { key: 'destination_warehouse', label: 'Destino', type: 'custom' },
  { key: 'items_summary', label: 'Productos', type: 'custom' },
  { key: 'status', label: 'Estado', type: 'custom' },
  { key: 'requested_by', label: 'Solicitado por', type: 'custom' },
  { key: 'actions', label: '', type: 'custom', align: 'right' },
];

const filters = computed<Filter[]>(() => [
  {
    key: 'from_warehouse_id',
    label: 'Origen',
    type: 'searchable-select',
    endpoint: '/api/admin/warehouses',
    labelKey: 'name',
    valueKey: 'id',
    placeholder: 'Almacén origen...',
  },
  {
    key: 'to_warehouse_id',
    label: 'Destino',
    type: 'searchable-select',
    endpoint: '/api/admin/warehouses',
    labelKey: 'name',
    valueKey: 'id',
    placeholder: 'Almacén destino...',
  },
  {
    key: 'status',
    label: 'Estado',
    type: 'select',
    options: Object.entries(statusLabels).map(([value, label]) => ({ value, label })),
  },
]);

function getStatusClass(status: string): string {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    in_transit: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
}

function getTotalItems(transfer: StockTransfer): number {
  return transfer.items?.reduce((sum, item) => sum + (item.quantity_requested || 0), 0) ?? 0;
}

function getItemNames(transfer: StockTransfer): string {
  if (!transfer.items || transfer.items.length === 0) return '-';
  return transfer.items.map(i => i.product?.name || 'Producto').join(', ');
}

// --- Confirm dialog ---
const showConfirmDialog = ref(false);
const confirmDialogTransfer = ref<StockTransfer | null>(null);
const confirmDialogAction = ref<'confirm' | 'cancel'>('confirm');
const isProcessing = ref(false);

function openConfirmDialog(transfer: StockTransfer) {
  confirmDialogTransfer.value = transfer;
  confirmDialogAction.value = 'confirm';
  showConfirmDialog.value = true;
}

function openCancelDialog(transfer: StockTransfer) {
  confirmDialogTransfer.value = transfer;
  confirmDialogAction.value = 'cancel';
  showConfirmDialog.value = true;
}

const confirmDialogTitle = computed(() => {
  if (confirmDialogAction.value === 'confirm') return 'Confirmar Transferencia';
  return 'Cancelar Transferencia';
});

const confirmDialogDescription = computed(() => {
  const folio = confirmDialogTransfer.value?.folio || '';
  if (confirmDialogAction.value === 'confirm') {
    return `¿Estás seguro de que deseas confirmar la transferencia ${folio}? Los movimientos de stock asociados se marcarán como completados.`;
  }
  return `¿Estás seguro de que deseas cancelar la transferencia ${folio}? Esta acción no se puede deshacer y los movimientos pendientes serán cancelados.`;
});

async function executeDialogAction() {
  if (!confirmDialogTransfer.value) return;

  isProcessing.value = true;
  try {
    if (confirmDialogAction.value === 'confirm') {
      await stockApi.confirmTransfer(confirmDialogTransfer.value.id);
      toast({ title: 'Éxito', description: 'Transferencia confirmada correctamente.' });
    } else {
      await stockApi.cancelTransfer(confirmDialogTransfer.value.id);
      toast({ title: 'Éxito', description: 'Transferencia cancelada correctamente.' });
    }
    showConfirmDialog.value = false;
    confirmDialogTransfer.value = null;
    fetchTransfers();
  } catch (error: any) {
    const message = error?.response?.data?.message || 'No se pudo procesar la acción.';
    toast({ title: 'Error', description: message, variant: 'destructive' });
  } finally {
    isProcessing.value = false;
  }
}

onMounted(() => fetchTransfers());
</script>

<template>
  <div class="h-[calc(100vh-120px)] flex flex-col">
    <div class="flex flex-col gap-6 h-full">
      <!-- Header with new transfer button -->
      <div class="flex items-center justify-between flex-shrink-0">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Transferencias</h1>
          <p class="text-muted-foreground">Gestiona las transferencias de mercancía entre almacenes.</p>
        </div>
        <Button @click="router.push('/panel/inventory/adjustments/transfer')">
          <Plus class="mr-2 h-4 w-4" />
          Nueva Transferencia
        </Button>
      </div>

      <!-- Data Table -->
      <DataTable
        :columns="columns"
        :data="transfers"
        :is-loading="isLoading"
        :search-value="searchQuery"
        :filters="filters"
        :filter-values="filterValues"
        :pagination="pagination"
        search-placeholder="Buscar por folio..."
        empty-message="No hay transferencias registradas."
        :empty-icon="ArrowRightLeft"
        class="flex-1 min-h-0"
        @search="search"
        @filter="filter"
        @page-change="changePage"
      >
        <template #cell-folio="{ row }">
          <span class="font-mono text-xs font-medium">{{ row.folio }}</span>
        </template>

        <template #cell-source_warehouse="{ row }">
          <div class="flex items-center gap-2">
            <Package class="h-4 w-4 text-muted-foreground flex-shrink-0" />
            <span class="truncate">{{ row.source_warehouse?.name || '-' }}</span>
          </div>
        </template>

        <template #cell-destination_warehouse="{ row }">
          <div class="flex items-center gap-2">
            <ArrowRight class="h-4 w-4 text-muted-foreground flex-shrink-0" />
            <span class="truncate">{{ row.destination_warehouse?.name || '-' }}</span>
          </div>
        </template>

        <template #cell-items_summary="{ row }">
          <div class="max-w-[250px]">
            <div class="text-sm truncate">{{ getItemNames(row) }}</div>
            <div class="text-xs text-muted-foreground">
              {{ row.items?.length || 0 }} producto(s) · {{ getTotalItems(row) }} unidades
            </div>
          </div>
        </template>

        <template #cell-status="{ row }">
          <span
            :class="getStatusClass(row.status)"
            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
          >
            {{ statusLabels[row.status] || row.status }}
          </span>
        </template>

        <template #cell-requested_by="{ row }">
          <span class="text-xs">{{ row.requested_by?.email || '-' }}</span>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end gap-1">
            <Button
              v-if="row.status === 'pending' || row.status === 'in_transit'"
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
    <Dialog v-model:open="showConfirmDialog">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{{ confirmDialogTitle }}</DialogTitle>
          <DialogDescription>{{ confirmDialogDescription }}</DialogDescription>
        </DialogHeader>

        <!-- Transfer summary -->
        <div v-if="confirmDialogTransfer" class="rounded-md border p-4 space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-muted-foreground">Folio:</span>
            <span class="font-mono font-medium">{{ confirmDialogTransfer.folio }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">Origen:</span>
            <span>{{ confirmDialogTransfer.source_warehouse?.name || '-' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">Destino:</span>
            <span>{{ confirmDialogTransfer.destination_warehouse?.name || '-' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">Productos:</span>
            <span>{{ confirmDialogTransfer.items?.length || 0 }} producto(s)</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">Unidades totales:</span>
            <span class="font-medium">{{ getTotalItems(confirmDialogTransfer) }}</span>
          </div>
        </div>

        <DialogFooter class="gap-2">
          <Button variant="outline" @click="showConfirmDialog = false" :disabled="isProcessing">
            Volver
          </Button>
          <Button
            :variant="confirmDialogAction === 'cancel' ? 'destructive' : 'default'"
            :disabled="isProcessing"
            @click="executeDialogAction"
          >
            <CheckCircle v-if="confirmDialogAction === 'confirm'" class="h-4 w-4 mr-1" />
            <XCircle v-else class="h-4 w-4 mr-1" />
            {{ isProcessing ? 'Procesando...' : (confirmDialogAction === 'confirm' ? 'Confirmar' : 'Cancelar Transferencia') }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>