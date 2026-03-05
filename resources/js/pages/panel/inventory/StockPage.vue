<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { stockApi } from '@/api/stock.api';
import { useApiList } from '@/composables/useApiList';
import { DataTable, type Column, type Filter } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { ArrowRightLeft } from 'lucide-vue-next';
import { useToast } from '@/components/ui/toast/use-toast';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useRouter } from 'vue-router';

interface StockItem {
  id: string;
  quantity: number;
  reserved: number;
  product: { id: string; name: string; sku: string };
  warehouse: { id: string; name: string };
  storage_location?: { id: string; name: string; code: string } | null;
}

const router = useRouter();
const { t } = useI18n();
const { toast } = useToast();

const {
  items: stockItems,
  isLoading,
  searchQuery,
  filterValues,
  pagination,
  fetch: fetchStock,
  search,
  filter,
} = useApiList<StockItem>(stockApi.list);

const isDialogOpen = ref(false);
const processing = ref(false);

const form = reactive({
  stockItem: null as StockItem | null,
  type: 'recount',
  quantity: '',
  reason: 'recount',
  notes: '',
});

const adjustmentTypes = computed(() => [
  { value: 'increase', label: t('stock.type_increase') },
  { value: 'decrease', label: t('stock.type_decrease') },
  { value: 'recount', label: t('stock.type_recount') },
]);

const reasons = computed(() => [
  { value: 'recount', label: t('stock.reason_recount') },
  { value: 'damaged', label: t('stock.reason_damaged') },
  { value: 'lost', label: t('stock.reason_lost') },
  { value: 'found', label: t('stock.reason_found') },
  { value: 'expired', label: t('stock.reason_expired') },
  { value: 'other', label: t('stock.reason_other') },
]);

const columns = computed<Column[]>(() => [
  { key: 'product', label: t('inventory.product'), type: 'custom' },
  { key: 'sku', label: t('inventory.sku'), type: 'custom' },
  { key: 'warehouse', label: t('inventory.warehouse'), type: 'custom' },
  { key: 'location', label: t('inventory.location'), type: 'custom' },
  { key: 'quantity', label: t('inventory.available'), type: 'custom', align: 'right' },
  { key: 'reserved', label: t('inventory.reserved'), type: 'custom', align: 'right' },
  { key: 'actions', label: '', type: 'custom', align: 'right' },
]);

const filters = computed<Filter[]>(() => [
  {
    key: 'warehouse_id',
    label: t('inventory.warehouse'),
    type: 'searchable-select',
    endpoint: '/admin/warehouses',
    labelKey: 'name',
    valueKey: 'id',
    placeholder: t('inventory.all_warehouses'),
  },
  {
    key: 'storage_location_id',
    label: t('inventory.location'),
    type: 'searchable-select',
    endpoint: '/admin/inventory/locations',
    labelKey: 'name',
    valueKey: 'id',
    placeholder: t('stock.all_locations'),
  },
  {
    key: 'low_stock',
    label: t('stock.low_stock'),
    type: 'select',
    placeholder: t('stock.low_stock'),
    options: [
      { value: '1', label: t('stock.only_low_stock') },
    ],
  },
]);

function handleFilter(key: string, value: string) {
  filter(key, value);
}

function handleSearch() {
  fetchStock();
}

function openAdjustDialog(item: StockItem) {
  form.stockItem = item;
  form.type = 'recount';
  form.quantity = '';
  form.reason = 'recount';
  form.notes = '';
  isDialogOpen.value = true;
}

const calculatedTotal = computed(() => {
  if (!form.stockItem || !form.quantity) return form.stockItem?.quantity || 0;
  const current = form.stockItem.quantity;
  const input = parseInt(form.quantity as string) || 0;
  if (form.type === 'increase') return current + input;
  if (form.type === 'decrease') return Math.max(0, current - input);
  return input;
});

async function handleSubmit() {
  if (!form.stockItem) return;

  processing.value = true;
  try {
    const modeMap: Record<string, string> = {
      increase: 'increment',
      decrease: 'decrement',
      recount: 'absolute',
    };

    const payload = {
      warehouse_id: form.stockItem.warehouse.id,
      storage_location_id: form.stockItem.storage_location?.id || null,
      type: form.type,
      reason: form.reason,
      notes: form.notes,
      items: [
        {
          product_id: form.stockItem.product.id,
          quantity: parseFloat(form.quantity as string) || 0,
          mode: modeMap[form.type] || 'absolute',
          reason: form.reason,
        },
      ],
    };

    await stockApi.adjust(payload);
    toast({ title: t('common.success'), description: t('stock.adjusted_success') });
    isDialogOpen.value = false;
    fetchStock();
  } catch (error) {
    console.error('Error adjusting stock:', error);
    toast({
      title: t('common.error'),
      description: t('common.unexpected_error'),
      variant: 'destructive',
    });
  } finally {
    processing.value = false;
  }
}

onMounted(() => fetchStock());
</script>

<template>
  <div class="flex flex-col gap-6">
    <div class="h-[calc(100vh-160px)] flex flex-col">
      <DataTable
        :columns="columns"
        :data="stockItems"
        :is-loading="isLoading"
        :search-value="searchQuery"
        :search-placeholder="t('stock.search')"
        :empty-message="t('stock.empty')"
        :empty-icon="ArrowRightLeft"
        :pagination="pagination"
        :filters="filters"
        :filter-values="filterValues"
        class="flex-1 min-h-0"
        @search="search"
        @filter="handleFilter"
        @page-change="fetchStock"
        @per-page-change="(n) => { pagination.perPage = n; fetchStock(1) }"
      >
        <template #cell-product="{ row }">
          <div>
            <div class="font-medium">{{ row.product?.name }}</div>
          </div>
        </template>

        <template #cell-sku="{ row }">
          <div class="text-sm text-muted-foreground">{{ row.product?.sku }}</div>
        </template>

        <template #cell-warehouse="{ row }">
          {{ row.warehouse?.name || '-' }}
        </template>

        <template #cell-location="{ row }">
          <div>
            <span v-if="row.storage_location" class="text-xs font-mono bg-muted px-1.5 py-0.5 rounded">
              {{ row.storage_location.code }}
            </span>
            <span v-else class="text-muted-foreground italic text-xs">{{ t('stock.no_location') }}</span>
          </div>
        </template>

        <template #cell-quantity="{ row }">
          <div class="text-right font-bold">{{ row.quantity }}</div>
        </template>

        <template #cell-reserved="{ row }">
          <div class="text-right text-muted-foreground">{{ row.reserved }}</div>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end">
            <Button variant="outline" size="sm" @click="openAdjustDialog(row)">
              <ArrowRightLeft class="mr-2 h-3 w-3" />
              {{ t('stock.adjust') }}
            </Button>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Adjust Dialog -->
    <Dialog v-model:open="isDialogOpen">
      <DialogContent class="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>{{ t('stock.adjust_title') }}</DialogTitle>
          <DialogDescription v-if="form.stockItem">
            {{ form.stockItem.product.name }} en {{ form.stockItem.warehouse.name }}
          </DialogDescription>
        </DialogHeader>
        <div class="grid gap-4 py-4" v-if="form.stockItem">
          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label htmlFor="type">{{ t('stock.adjustment_type') }}</Label>
              <Select v-model="form.type">
                <SelectTrigger><SelectValue :placeholder="t('stock.select_type')" /></SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="t in adjustmentTypes" :key="t.value" :value="t.value">
                    {{ t.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="grid gap-2">
              <Label htmlFor="reason">{{ t('stock.reason') }}</Label>
              <Select v-model="form.reason">
                <SelectTrigger><SelectValue :placeholder="t('stock.select_reason')" /></SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="r in reasons" :key="r.value" :value="r.value">
                    {{ r.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <div class="grid gap-2">
            <Label>{{ t('stock.current_stock') }}: {{ form.stockItem.quantity }}</Label>
          </div>
          <div class="grid gap-2">
            <Label htmlFor="quantity">
              {{ form.type === 'recount' ? t('stock.new_quantity') : t('inventory.adjust_stock') }}
            </Label>
            <Input id="quantity" type="number" v-model="form.quantity" />
          </div>
          <div class="p-3 bg-muted rounded-md text-sm text-center">
            <span class="text-muted-foreground">{{ t('stock.resulting_quantity') }}:</span>
            <span class="font-bold text-lg ml-2">{{ calculatedTotal }}</span>
          </div>
          <div class="grid gap-2">
            <Label htmlFor="notes">{{ t('stock.notes') }} ({{ t('common.optional') }})</Label>
            <Textarea id="notes" v-model="form.notes" :placeholder="t('stock.notes_placeholder')" />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="isDialogOpen = false">{{ t('common.cancel') }}</Button>
          <Button type="submit" :disabled="processing" @click="handleSubmit">
            {{ processing ? t('common.processing') : t('stock.apply_adjustment') }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>