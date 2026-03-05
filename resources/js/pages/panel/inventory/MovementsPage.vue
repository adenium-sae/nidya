<script setup lang="ts">
import { onMounted, computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
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

const { t } = useI18n();
const { toast } = useToast();

const movementTypeLabels = computed<Record<string, string>>(() => ({
  entry: t('movements.type_entry'),
  exit: t('movements.type_exit'),
  transfer: t('movements.type_transfer'),
  adjustment: t('movements.type_adjustment'),
  sale: t('movements.type_sale'),
  return: t('movements.type_return'),
  damage: t('movements.type_damage'),
  production: t('movements.type_production'),
}));

const statusLabels = computed<Record<string, string>>(() => ({
  pending: t('common.pending'),
  completed: t('common.completed'),
  cancelled: t('common.cancelled'),
}));

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

const columns = computed<Column[]>(() => [
  { key: 'created_at', label: t('common.date'), type: 'date', sortable: true },
  { key: 'product', label: t('inventory.product'), type: 'custom' },
  { key: 'warehouse', label: t('inventory.warehouse'), type: 'custom' },
  { key: 'type', label: t('common.type'), type: 'custom' },
  { key: 'quantity', label: t('inventory.quantity'), type: 'custom', align: 'right' },
  { key: 'stock_change', label: t('inventory.stock'), type: 'custom', align: 'right' },
  { key: 'status', label: t('common.status'), type: 'custom' },
  { key: 'user', label: t('common.user'), type: 'custom' },
  { key: 'actions', label: '', type: 'custom', align: 'right' },
]);

const filters = computed<Filter[]>(() => [
  {
    key: 'warehouse_id',
    label: t('inventory.warehouse'),
    type: 'searchable-select',
    endpoint: '/api/admin/warehouses',
    labelKey: 'name',
    valueKey: 'id',
    placeholder: t('movements.search_warehouse'),
  },
  {
    key: 'type',
    label: t('common.type'),
    type: 'select',
    options: Object.entries(movementTypeLabels.value).map(([value, label]) => ({ value, label })),
  },
]);

function getTypeLabel(type: string): string {
  return movementTypeLabels.value[type] || type;
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
  return dialogAction.value === 'confirm' ? t('movements.confirm_title') : t('movements.cancel_title');
});

const dialogDescription = computed(() => {
  if (dialogAction.value === 'confirm') {
    return t('movements.confirm_desc');
  }
  return t('movements.cancel_desc');
});

async function executeDialogAction() {
  if (!dialogMovementId.value) return;

  isProcessing.value = true;
  try {
    if (dialogAction.value === 'confirm') {
      await stockApi.confirmMovement(dialogMovementId.value);
      toast({ title: t('common.success'), description: t('movements.confirmed') });
    } else {
      await stockApi.cancelMovement(dialogMovementId.value);
      toast({ title: t('common.success'), description: t('movements.cancelled') });
    }
    showDialog.value = false;
    dialogMovementId.value = null;
    dialogMovementInfo.value = null;
    fetchMovements();
  } catch (error: any) {
    const message = error?.response?.data?.message || t('common.cannot_process');
    toast({ title: t('common.error'), description: message, variant: 'destructive' });
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
        :search-placeholder="t('movements.search')"
        :empty-message="t('movements.empty')"
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
    <Dialog v-model:open="showDialog">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{{ dialogTitle }}</DialogTitle>
          <DialogDescription>{{ dialogDescription }}</DialogDescription>
        </DialogHeader>

        <div v-if="dialogMovementInfo" class="rounded-md border p-4 space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-muted-foreground">{{ t('movements.product_label') }}</span>
            <span class="font-medium">{{ dialogMovementInfo.product }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">{{ t('movements.type_label') }}</span>
            <span>{{ dialogMovementInfo.type }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">{{ t('movements.quantity_label') }}</span>
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
            {{ t('common.back') }}
          </Button>
          <Button
            :variant="dialogAction === 'cancel' ? 'destructive' : 'default'"
            :disabled="isProcessing"
            @click="executeDialogAction"
          >
            <CheckCircle v-if="dialogAction === 'confirm'" class="h-4 w-4 mr-1" />
            <XCircle v-else class="h-4 w-4 mr-1" />
            {{ isProcessing ? t('common.processing') : (dialogAction === 'confirm' ? t('common.confirm') : t('movements.cancel_movement')) }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>