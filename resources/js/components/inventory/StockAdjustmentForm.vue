<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/components/ui/toast/use-toast';
import { stockApi } from '@/api/stock.api';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/searchable-select';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Trash2, Save } from 'lucide-vue-next';
import PageHeader from '@/components/app/PageHeader.vue';
import StorageLocationFormDialog from '@/components/inventory/StorageLocationFormDialog.vue';

type AdjustmentMode = 'entry' | 'exit' | 'adjustment';

const props = defineProps<{
  mode: AdjustmentMode;
}>();

const router = useRouter();
const { t } = useI18n();
const { toast } = useToast();
const isSubmitting = ref(false);
const isCreatingLocation = ref(false);

function handleLocationCreated(location: any) {
  form.storage_location_id = location.id;
}

// Mode-specific configuration
const config = computed(() => ({
  entry: {
    title: t('adjustments.new_entry'),
    description: t('adjustments.subtitle_entry'),
    quantityLabel: t('adjustments.qty'),
    apiMode: 'increment',
    type: 'increase',
    submitLabel: t('adjustments.submit_entry'),
    submitVariant: 'default' as const,
    quantityMin: 1,
    reasons: [
      { value: 'found', label: t('adjustments.reason_found') },
      { value: 'recount', label: t('adjustments.reason_recount') },
      { value: 'other', label: t('adjustments.reason_other') },
    ],
    successMessage: t('adjustments.entry_success'),
    errorMessage: t('adjustments.entry_error'),
  },
  exit: {
    title: t('adjustments.new_exit'),
    description: t('adjustments.subtitle_exit'),
    quantityLabel: t('adjustments.qty_to_remove'),
    apiMode: 'decrement',
    type: 'decrease',
    submitLabel: t('adjustments.submit_exit'),
    submitVariant: 'destructive' as const,
    quantityMin: 1,
    reasons: [
      { value: 'damaged', label: t('adjustments.reason_damaged') },
      { value: 'lost', label: t('adjustments.reason_lost') },
      { value: 'expired', label: t('adjustments.reason_expired') },
      { value: 'other', label: t('adjustments.reason_other') },
    ],
    successMessage: t('adjustments.exit_success'),
    errorMessage: t('adjustments.exit_error'),
  },
  adjustment: {
    title: t('adjustments.new_adjustment'),
    description: t('adjustments.subtitle_adjustment'),
    quantityLabel: t('adjustments.qty'),
    apiMode: 'absolute',
    type: 'recount',
    submitLabel: t('adjustments.submit_adjustment'),
    submitVariant: 'default' as const,
    quantityMin: 0,
    reasons: [
      { value: 'recount', label: t('adjustments.reason_recount') },
      { value: 'correction', label: 'Corrección' },
      { value: 'other', label: t('adjustments.reason_other') },
    ],
    successMessage: t('adjustments.adjustment_success'),
    errorMessage: t('adjustments.adjustment_error'),
  },
})[props.mode]);

const form = reactive({
  warehouse_id: '',
  storage_location_id: '',
  type: computed(() => config.value.type),
  items: [createEmptyItem()],
});

function createEmptyItem() {
  return {
    product_id: '',
    quantity: 0,
    current_quantity: 0,
    reason: config.value?.reasons[0]?.value || 'other',
  };
}

const locationEndpoint = computed(() => {
  return form.warehouse_id
    ? `/admin/inventory/locations?warehouse_id=${form.warehouse_id}`
    : undefined;
});

const productEndpoint = computed(() => {
  if (!form.warehouse_id) return undefined;
  let url = `/admin/products?warehouse_id=${form.warehouse_id}`;
  if (form.storage_location_id) {
    url += `&storage_location_id=${form.storage_location_id}`;
  }
  return url;
});

function addItem() {
  form.items.push(createEmptyItem());
}

function removeItem(index: number) {
  form.items.splice(index, 1);
}

function handleWarehouseChange() {
  form.storage_location_id = '';
  form.items = [createEmptyItem()];
}

async function handleProductSelect(productId: string, index: number) {
  if (!productId || !form.warehouse_id) return;
  try {
    const response = await stockApi.list({
      product_id: productId,
      warehouse_id: form.warehouse_id,
      storage_location_id: form.storage_location_id || undefined,
    });
    const data = response.data.data || response.data;
    const totalQty = Array.isArray(data)
      ? data.reduce((sum: number, stock: any) => sum + stock.quantity, 0)
      : 0;
    form.items[index].current_quantity = totalQty;
  } catch (error) {
    console.error('Error fetching product stock:', error);
  }
}

async function handleSubmit() {
  const minQty = config.value.quantityMin;
  if (!form.warehouse_id || form.items.some(i => !i.product_id || i.quantity < minQty)) {
    toast({
      title: t('common.validation_error'),
      description: t('adjustments.validation_msg'),
      variant: 'destructive',
    });
    return;
  }

  isSubmitting.value = true;
  try {
    const payload = {
      warehouse_id: form.warehouse_id,
      storage_location_id: form.storage_location_id || undefined,
      type: config.value.type,
      items: form.items.map(i => ({
        product_id: i.product_id,
        quantity: i.quantity,
        mode: config.value.apiMode,
        reason: i.reason,
      })),
    };

    await stockApi.adjust(payload);
    toast({ title: t('common.success'), description: config.value.successMessage });
    router.push('/panel/inventory/adjustments');
  } catch (error) {
    toast({
      title: t('common.error'),
      description: config.value.errorMessage,
      variant: 'destructive',
    });
  } finally {
    isSubmitting.value = false;
  }
}
</script>

<template>
  <div class="flex flex-col gap-6 max-w-6xl mx-auto">
    <PageHeader :title="config.title" :description="config.description" show-back />

    <!-- Warehouse & Location -->
    <div class="bg-card border rounded-lg p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-2">
          <Label>{{ t('inventory.warehouse') }} <span class="text-destructive">*</span></Label>
          <SearchableSelect
            v-model="form.warehouse_id"
            endpoint="/admin/warehouses"
            label-key="name"
            value-key="id"
            :placeholder="t('adjustments.select_warehouse')"
            @update:model-value="handleWarehouseChange"
          />
        </div>
        <div class="space-y-2">
          <Label>{{ t('common.optional') }}</Label>
          <SearchableSelect
            v-model="form.storage_location_id"
            :endpoint="locationEndpoint"
            label-key="name"
            value-key="id"
            :placeholder="t('adjustments.select_location')"
            :disabled="!form.warehouse_id"
            :show-add-option="!!form.warehouse_id"
            :add-option-label="t('locations.new_location')"
            @add-click="isCreatingLocation = true"
          />
        </div>
      </div>
    </div>

    <!-- Products -->
    <div class="bg-card border rounded-lg p-6">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold">{{ t('adjustments.products') }}</h2>
        <Button
          type="button"
          variant="outline"
          size="sm"
          @click="addItem"
          :disabled="!form.warehouse_id"
        >
          <Plus class="mr-2 h-4 w-4" />
          {{ t('adjustments.add_item') }}
        </Button>
      </div>

      <div class="space-y-4">
        <div
          v-for="(item, index) in form.items"
          :key="index"
          class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start border-b pb-8 last:border-0"
        >
          <div class="md:col-span-5 space-y-2">
            <Label>{{ t('inventory.product') }}</Label>
            <SearchableSelect
              v-model="item.product_id"
              :endpoint="productEndpoint"
              label-key="name"
              value-key="id"
              :placeholder="t('adjustments.search_product')"
              :disabled="!form.warehouse_id"
              @update:model-value="val => handleProductSelect(val as string, index)"
            />
          </div>
          <div class="md:col-span-2 space-y-2 relative">
            <Label>{{ config.quantityLabel }}</Label>
            <Input
              type="number"
              v-model.number="item.quantity"
              :min="config.quantityMin"
              class="h-10"
            />
            <div class="absolute -bottom-5 left-0 text-[10px] text-muted-foreground whitespace-nowrap">
              {{ t('adjustments.in_stock') }}: <span class="font-medium text-primary">{{ item.current_quantity }}</span>
            </div>
          </div>
          <div class="md:col-span-4 space-y-2">
            <Label>{{ t('adjustments.reason_label') }}</Label>
            <Select v-model="item.reason">
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="r in config.reasons" :key="r.value" :value="r.value">
                  {{ r.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="md:col-span-1 pt-8 flex justify-end">
            <Button
              variant="ghost"
              size="icon"
              class="text-destructive hover:bg-destructive/10 h-10 w-10"
              @click="removeItem(index)"
              :disabled="form.items.length === 1"
            >
              <Trash2 class="h-4 w-4" />
            </Button>
          </div>
        </div>
      </div>
    </div>

    <!-- Submit -->
    <div class="flex justify-end">
      <Button
        :variant="config.submitVariant"
        :disabled="isSubmitting"
        @click="handleSubmit"
      >
        <Save class="mr-2" />
        {{ isSubmitting ? t('common.saving') : config.submitLabel }}
      </Button>
    </div>
  </div>
  <StorageLocationFormDialog
    v-model:open="isCreatingLocation"
    :warehouse-id="form.warehouse_id"
    @saved="handleLocationCreated"
  />
</template>