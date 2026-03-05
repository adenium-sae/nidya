<script setup lang="ts">
import { onMounted, computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
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
const { t } = useI18n();
const { toast } = useToast();

const statusLabels = computed<Record<string, string>>(() => ({
  pending: t('transfers.status_pending'),
  in_transit: t('transfers.status_in_transit'),
  completed: t('transfers.status_completed'),
  cancelled: t('transfers.status_cancelled'),
}));

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

const columns = computed<Column[]>(() => [
  { key: 'folio', label: t('transfers.folio'), type: 'custom', sortable: true },
  { key: 'created_at', label: t('common.date'), type: 'date', sortable: true },
  { key: 'source_warehouse', label: t('transfers.source'), type: 'custom' },
  { key: 'destination_warehouse', label: t('transfers.destination'), type: 'custom' },
  { key: 'items_summary', label: t('transfers.products_col'), type: 'custom' },
  { key: 'status', label: t('common.status'), type: 'custom' },
  { key: 'requested_by', label: t('transfers.requested_by'), type: 'custom' },
  { key: 'actions', label: '', type: 'custom', align: 'right' },
]);

const filters = computed<Filter[]>(() => [
  {
    key: 'from_warehouse_id',
    label: t('transfers.source'),
    type: 'searchable-select',
    endpoint: '/api/admin/warehouses',
    labelKey: 'name',
    valueKey: 'id',
    placeholder: t('transfers.source_warehouse'),
  },
  {
    key: 'to_warehouse_id',
    label: t('transfers.destination'),
    type: 'searchable-select',
    endpoint: '/api/admin/warehouses',
    labelKey: 'name',
    valueKey: 'id',
    placeholder: t('transfers.destination_warehouse'),
  },
  {
    key: 'status',
    label: t('common.status'),
    type: 'select',
    options: Object.entries(statusLabels.value).map(([value, label]) => ({ value, label })),
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
  return transfer.items.map(i => i.product?.name || t('inventory.product')).join(', ');
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
  if (confirmDialogAction.value === 'confirm') return t('transfers.confirm_title');
  return t('transfers.cancel_title');
});

const confirmDialogDescription = computed(() => {
  const folio = confirmDialogTransfer.value?.folio || '';
  if (confirmDialogAction.value === 'confirm') {
    return t('transfers.confirm_desc', { folio });
  }
  return t('transfers.cancel_desc', { folio });
});

async function executeDialogAction() {
  if (!confirmDialogTransfer.value) return;

  isProcessing.value = true;
  try {
    if (confirmDialogAction.value === 'confirm') {
      await stockApi.confirmTransfer(confirmDialogTransfer.value.id);
      toast({ title: t('common.success'), description: t('transfers.confirmed') });
    } else {
      await stockApi.cancelTransfer(confirmDialogTransfer.value.id);
      toast({ title: t('common.success'), description: t('transfers.cancelled') });
    }
    showConfirmDialog.value = false;
    confirmDialogTransfer.value = null;
    fetchTransfers();
  } catch (error: any) {
    const message = error?.response?.data?.message || t('common.cannot_process');
    toast({ title: t('common.error'), description: message, variant: 'destructive' });
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
          <h1 class="text-3xl font-bold tracking-tight">{{ t('transfers.title') }}</h1>
          <p class="text-muted-foreground">{{ t('transfers.subtitle') }}</p>
        </div>
        <Button @click="router.push('/panel/inventory/adjustments/transfer')">
          <Plus class="mr-2 h-4 w-4" />
          {{ t('transfers.new') }}
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
        :search-placeholder="t('transfers.search')"
        :empty-message="t('transfers.empty')"
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
              {{ t('transfers.product_count', { count: row.items?.length || 0 }) }} · {{ t('transfers.unit_count', { count: getTotalItems(row) }) }}
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
              {{ t('common.confirm_action') }}
            </Button>
            <Button
              v-if="row.status === 'pending'"
              variant="ghost"
              size="sm"
              class="h-8 px-2 text-red-600 hover:text-red-700 hover:bg-red-50"
              @click.stop="openCancelDialog(row)"
            >
              <XCircle class="h-4 w-4 mr-1" />
              {{ t('common.cancel_action') }}
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
            <span class="text-muted-foreground">{{ t('transfers.folio_label') }}</span>
            <span class="font-mono font-medium">{{ confirmDialogTransfer.folio }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">{{ t('transfers.source_label') }}</span>
            <span>{{ confirmDialogTransfer.source_warehouse?.name || '-' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">{{ t('transfers.destination_label') }}</span>
            <span>{{ confirmDialogTransfer.destination_warehouse?.name || '-' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">{{ t('transfers.products_label') }}</span>
            <span>{{ t('transfers.product_count', { count: confirmDialogTransfer.items?.length || 0 }) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">{{ t('transfers.total_units_label') }}</span>
            <span class="font-medium">{{ getTotalItems(confirmDialogTransfer) }}</span>
          </div>
        </div>

        <DialogFooter class="gap-2">
          <Button variant="outline" @click="showConfirmDialog = false" :disabled="isProcessing">
            {{ t('common.back') }}
          </Button>
          <Button
            :variant="confirmDialogAction === 'cancel' ? 'destructive' : 'default'"
            :disabled="isProcessing"
            @click="executeDialogAction"
          >
            <CheckCircle v-if="confirmDialogAction === 'confirm'" class="h-4 w-4 mr-1" />
            <XCircle v-else class="h-4 w-4 mr-1" />
            {{ isProcessing ? t('common.processing') : (confirmDialogAction === 'confirm' ? t('common.confirm') : t('transfers.cancel_transfer')) }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>