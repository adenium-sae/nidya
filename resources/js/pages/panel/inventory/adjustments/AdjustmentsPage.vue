<script setup lang="ts">
import { onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { stockApi } from '@/api/stock.api';
import { useApiList } from '@/composables/useApiList';
import { DataTable, type Column } from '@/components/ui/data-table';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  TrendingUp,
  TrendingDown,
  ArrowRightLeft,
  CheckCircle,
  Settings2,
  ArrowUpDown,
  Eye,
} from 'lucide-vue-next';
import { useToast } from '@/components/ui/toast/use-toast';
import Button from '@/components/ui/button/Button.vue';

import type { StockAdjustment } from '@/types/models';

const router = useRouter();
const { t } = useI18n();
const { toast } = useToast();

const statusLabels = computed<Record<string, string>>(() => ({
  pending: t('common.pending'),
  completed: t('common.completed'),
  cancelled: t('common.cancelled'),
}));

const {
  items: adjustments,
  isLoading,
  fetch: fetchAdjustments,
} = useApiList<StockAdjustment>(stockApi.adjustments);

function getAdjustmentLabel(type: string): string {
  const labels: Record<string, string> = {
    increase: t('adjustments.type_increase'),
    decrease: t('adjustments.type_decrease'),
    adjustment: t('adjustments.type_direct'),
    recount: t('adjustments.type_recount'),
  };
  return labels[type] || type;
}

function getAdjustmentClass(type: string): string {
  const classes: Record<string, string> = {
    increase: 'bg-green-100 text-green-800',
    decrease: 'bg-red-100 text-red-800',
    adjustment: 'bg-blue-100 text-blue-800',
    recount: 'bg-yellow-100 text-yellow-800',
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

async function confirmAdjustment(adj: any) {
  try {
    await stockApi.confirmAdjustment(adj.id);
    toast({ title: t('common.success'), description: t('adjustments.confirm_success', { folio: adj.folio }) });
    fetchAdjustments();
  } catch (error: any) {
    const message = error?.response?.data?.message || t('common.cannot_process');
    toast({ title: t('common.error'), description: message, variant: 'destructive' });
  }
}

function viewAdjustment(adj: any) {
  router.push(`/panel/inventory/adjustments/${adj.id}`);
}

onMounted(() => fetchAdjustments());

const columns = computed<Column[]>(() => [
  { key: 'folio', label: t('adjustments.folio'), type: 'custom' },
  { key: 'created_at', label: t('common.date'), type: 'date', sortable: true },
  { key: 'type', label: t('common.type'), type: 'custom' },
  { key: 'warehouse', label: t('inventory.warehouse'), type: 'custom' },
  { key: 'products', label: t('adjustments.products'), type: 'custom' },
  { key: 'status', label: t('common.status'), type: 'custom' },
  { key: 'user', label: t('common.user'), type: 'custom' },
  { key: 'actions', label: '', type: 'custom', align: 'right' },
]);
</script>

<template>
  <div class="h-[calc(100vh-120px)] flex flex-col">
    <div class="flex flex-col gap-6 h-full">
      <!-- Quick Actions -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card class="hover:border-primary transition-colors cursor-pointer" @click="router.push('/panel/inventory/adjustments/entry')">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">{{ t('adjustments.new_entry') }}</CardTitle>
            <TrendingUp class="h-4 w-4 text-green-500" />
          </CardHeader>
          <CardContent>
            <p class="text-xs text-muted-foreground">{{ t('adjustments.new_entry_desc') }}</p>
          </CardContent>
        </Card>

        <Card class="hover:border-primary transition-colors cursor-pointer" @click="router.push('/panel/inventory/adjustments/exit')">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">{{ t('adjustments.new_exit') }}</CardTitle>
            <TrendingDown class="h-4 w-4 text-red-500" />
          </CardHeader>
          <CardContent>
            <p class="text-xs text-muted-foreground">{{ t('adjustments.new_exit_desc') }}</p>
          </CardContent>
        </Card>

        <Card class="hover:border-primary transition-colors cursor-pointer" @click="router.push('/panel/inventory/adjustments/new-adjustment')">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">{{ t('adjustments.new_adjustment') }}</CardTitle>
            <Settings2 class="h-4 w-4 text-blue-500" />
          </CardHeader>
          <CardContent>
            <p class="text-xs text-muted-foreground">{{ t('adjustments.new_adj_desc') }}</p>
          </CardContent>
        </Card>

        <Card class="hover:border-primary transition-colors cursor-pointer" @click="router.push('/panel/inventory/adjustments/transfer')">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">{{ t('adjustments.transfer') }}</CardTitle>
            <ArrowRightLeft class="h-4 w-4 text-purple-500" />
          </CardHeader>
          <CardContent>
            <p class="text-xs text-muted-foreground">{{ t('adjustments.transfer_desc') }}</p>
          </CardContent>
        </Card>
      </div>

      <!-- History Table (DataTable) -->
      <div class="bg-card flex-1 overflow-auto min-h-0">
        <DataTable
          :columns="columns"
          :data="adjustments"
          :is-loading="isLoading"
          :search-placeholder="t('adjustments.search')"
          :empty-message="t('adjustments.empty')"
          :empty-icon="ArrowUpDown"
          class="flex-1 min-h-0"
        >
          <template #cell-folio="{ row }">
            <div class="font-mono text-xs">{{ row.folio }}</div>
          </template>

          <template #cell-created_at="{ row }">
            <div>{{ new Date(row.created_at).toLocaleDateString() }}</div>
          </template>

          <template #cell-type="{ row }">
            <span
              :class="getAdjustmentClass(row.type)"
              class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
            >
              {{ getAdjustmentLabel(row.type) }}
            </span>
          </template>

          <template #cell-warehouse="{ row }">
            {{ row.warehouse?.name || '-' }}
          </template>

          <template #cell-products="{ row }">
            <div class="max-w-[300px] truncate">
              {{ row.items?.map((i: any) => i.product?.name).join(', ') }}
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

          <template #cell-user="{ row }">
            <div class="text-xs">{{ row.user?.email }}</div>
          </template>

          <template #cell-actions="{ row }">
            <div class="text-right flex items-center justify-end gap-2">
              <Button
                v-if="row.status === 'pending'"
                variant="ghost"
                size="sm"
                class="h-8 px-2 text-green-600 hover:text-green-700 hover:bg-green-50"
                @click="confirmAdjustment(row)"
              >
                <CheckCircle class="h-4 w-4 mr-1" />
                {{ t('common.confirm_action') }}
              </Button>

              <Button
                variant="ghost"
                size="sm"
                class="h-8 px-2 text-muted-foreground hover:bg-muted/50"
                @click="viewAdjustment(row)"
              >
                <Eye class="h-4 w-4" />
              </Button>
            </div>
          </template>
        </DataTable>
      </div>
    </div>
  </div>
</template>