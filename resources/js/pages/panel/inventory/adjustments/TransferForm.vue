<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/components/ui/toast/use-toast';
import { stockApi } from '@/api/stock.api';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { SearchableSelect } from '@/components/ui/searchable-select';
import { Plus, Trash2, ArrowLeft, MoveHorizontal, AlertTriangle } from 'lucide-vue-next';

const router = useRouter();
const { t } = useI18n();
const { toast } = useToast();
const isSubmitting = ref(false);

interface TransferItem {
  product_id: string | null;
  quantity: number;
  current_source_stock: number;
  source_location_id: string | null;
  destination_location_id: string | null;
}

function createEmptyItem(): TransferItem {
  return {
    product_id: null,
    quantity: 0,
    current_source_stock: 0,
    source_location_id: null,
    destination_location_id: null,
  };
}

const form = reactive({
  source_warehouse_id: null as string | null,
  destination_warehouse_id: null as string | null,
  notes: '',
  items: [createEmptyItem()] as TransferItem[],
});

const sourceLocationEndpoint = computed(() => {
  return form.source_warehouse_id
    ? `/api/admin/inventory/locations?warehouse_id=${form.source_warehouse_id}`
    : null;
});

const destLocationEndpoint = computed(() => {
  return form.destination_warehouse_id
    ? `/api/admin/inventory/locations?warehouse_id=${form.destination_warehouse_id}`
    : null;
});

const productEndpoint = computed(() => {
  if (!form.source_warehouse_id) return null;
  return `/api/admin/products?warehouse_id=${form.source_warehouse_id}`;
});

function addItem() {
  form.items.push(createEmptyItem());
}

function handleSourceWarehouseChange() {
  form.items = [createEmptyItem()];
}

function removeItem(index: number) {
  form.items.splice(index, 1);
}

async function handleProductSelect(productId: string | null, index: number) {
  if (!productId || !form.source_warehouse_id) return;

  try {
    const response = await stockApi.list({
      product_id: productId,
      warehouse_id: form.source_warehouse_id,
      storage_location_id: form.items[index].source_location_id || undefined,
    });

    const data = response.data.data || response.data;
    const totalQty = Array.isArray(data)
      ? data.reduce((sum: number, stock: any) => sum + stock.quantity, 0)
      : 0;
    form.items[index].current_source_stock = totalQty;
  } catch (error) {
    console.error('Error fetching source stock:', error);
  }
}

const validationErrors = computed(() => {
  const errors: string[] = [];
  form.items.forEach((item, index) => {
    if (item.product_id && item.quantity > 0 && item.quantity > item.current_source_stock) {
      errors.push(
        t('adjustments.transfer_stock_error', { index: index + 1, qty: item.quantity, available: item.current_source_stock })
      );
    }
  });
  return errors;
});

const hasStockErrors = computed(() => validationErrors.value.length > 0);

async function handleSubmit() {
  if (
    !form.source_warehouse_id ||
    !form.destination_warehouse_id ||
    form.items.some((i) => !i.product_id || i.quantity <= 0)
  ) {
    toast({
      title: t('common.validation_error'),
      description: t('common.check_errors'),
      variant: 'destructive',
    });
    return;
  }

  if (form.source_warehouse_id === form.destination_warehouse_id) {
    toast({
      title: t('common.validation_error'),
      description: t('adjustments.transfer_same_warehouse'),
      variant: 'destructive',
    });
    return;
  }

  if (hasStockErrors.value) {
    toast({
      title: t('adjustments.insufficientStockTitle'),
      description: validationErrors.value[0],
      variant: 'destructive',
    });
    return;
  }

  isSubmitting.value = true;
  try {
    const result = await stockApi.transfer(form);
    const folio = result?.data?.data?.folio;
    toast({
      title: t('common.success'),
      description: folio
        ? t('adjustments.transfer_success_folio', { folio })
        : t('adjustments.transfer_success'),
    });
    router.push('/panel/inventory/transfers');
  } catch (error: any) {
    const message =
      error?.response?.data?.message || t('adjustments.transfer_error');
    toast({
      title: t('common.error'),
      description: message,
      variant: 'destructive',
    });
  } finally {
    isSubmitting.value = false;
  }
}
</script>

<template>
  <div class="flex flex-col gap-6">
    <div class="flex items-center gap-4">
      <Button variant="ghost" size="icon" @click="router.back()">
        <ArrowLeft class="h-4 w-4" />
      </Button>
      <div>
        <h1 class="text-3xl font-bold tracking-tight">{{ t('adjustments.transfer') }}</h1>
        <p class="text-muted-foreground">{{ t('adjustments.transfer_desc') }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-card border rounded-lg p-6 space-y-4">
        <h2 class="text-lg font-semibold flex items-center gap-2">
          <span
            class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-sm"
            >1</span
          >
          {{ t('adjustments.origin') }}
        </h2>
        <div class="space-y-4">
          <div class="space-y-2">
            <Label
              >{{ t('adjustments.origin_warehouse') }} <span class="text-destructive">*</span></Label
            >
            <SearchableSelect
              v-model="form.source_warehouse_id"
              endpoint="/api/admin/warehouses"
              label-key="name"
              value-key="id"
              :placeholder="t('adjustments.select_origin')"
              @update:model-value="handleSourceWarehouseChange"
            />
          </div>
        </div>
      </div>

      <div class="bg-card border rounded-lg p-6 space-y-4">
        <h2 class="text-lg font-semibold flex items-center gap-2">
          <span
            class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-sm"
            >2</span
          >
          {{ t('adjustments.destination') }}
        </h2>
        <div class="space-y-4">
          <div class="space-y-2">
            <Label
              >{{ t('adjustments.destination_warehouse') }}
              <span class="text-destructive">*</span></Label
            >
            <SearchableSelect
              v-model="form.destination_warehouse_id"
              endpoint="/api/admin/warehouses"
              label-key="name"
              value-key="id"
              :placeholder="t('adjustments.select_destination')"
            />
          </div>
        </div>
      </div>
    </div>

    <div class="bg-card border rounded-lg p-6">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold">{{ t('adjustments.products_to_transfer') }}</h2>
        <Button
          type="button"
          variant="outline"
          size="sm"
          @click="addItem"
          :disabled="!form.source_warehouse_id"
        >
          <Plus class="mr-2 h-4 w-4" />
          {{ t('adjustments.add_item') }}
        </Button>
      </div>

      <div class="space-y-6">
        <div
          v-for="(item, index) in form.items"
          :key="index"
          class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start border-b pb-8 last:border-0"
        >
          <div class="lg:col-span-3 space-y-2">
            <Label>{{ t('inventory.product') }}</Label>
            <SearchableSelect
              v-model="item.product_id"
              :endpoint="productEndpoint"
              label-key="name"
              value-key="id"
              :placeholder="t('adjustments.search_product')"
              :disabled="!form.source_warehouse_id"
              @update:model-value="
                (val: any) => handleProductSelect(val, index)
              "
            />
          </div>
          <div class="lg:col-span-3 space-y-2">
            <Label>{{ t('adjustments.origin_location') }}</Label>
            <SearchableSelect
              v-model="item.source_location_id"
              :endpoint="sourceLocationEndpoint"
              label-key="name"
              value-key="id"
              :placeholder="t('adjustments.all_area')"
              :disabled="!form.source_warehouse_id"
              @update:model-value="
                () => handleProductSelect(item.product_id, index)
              "
            />
          </div>
          <div class="lg:col-span-3 space-y-2">
            <Label>{{ t('adjustments.destination_location') }}</Label>
            <SearchableSelect
              v-model="item.destination_location_id"
              :endpoint="destLocationEndpoint"
              label-key="name"
              value-key="id"
              :placeholder="t('adjustments.all_area')"
              :disabled="!form.destination_warehouse_id"
            />
          </div>
          <div class="lg:col-span-2 space-y-2 relative">
            <Label>{{ t('adjustments.qty') }}</Label>
            <Input
              type="number"
              v-model.number="item.quantity"
              min="1"
              :max="item.current_source_stock || undefined"
              class="h-10"
              :class="{
                'border-destructive focus-visible:ring-destructive':
                  item.product_id &&
                  item.quantity > 0 &&
                  item.quantity > item.current_source_stock,
              }"
            />
            <div
              class="absolute -bottom-5 left-0 text-[10px] whitespace-nowrap"
              :class="
                item.product_id && item.quantity > item.current_source_stock
                  ? 'text-destructive font-medium'
                  : 'text-muted-foreground'
              "
            >
              {{ t('adjustments.available') }}:
              <span
                class="font-medium"
                :class="
                  item.product_id && item.quantity > item.current_source_stock
                    ? 'text-destructive'
                    : 'text-primary'
                "
                >{{ item.current_source_stock }}</span
              >
            </div>
          </div>
          <div class="lg:col-span-1 pt-8 flex justify-end">
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

    <div class="flex flex-col lg:flex-row gap-6">
      <div class="flex-1 bg-card border rounded-lg p-6 space-y-2">
        <Label>{{ t('adjustments.transfer_notes') }}</Label>
        <Textarea
          v-model="form.notes"
          :placeholder="t('adjustments.transfer_notes_pl')"
        />
      </div>
    </div>

    <!-- Validation warnings -->
    <div
      v-if="hasStockErrors"
      class="rounded-md border border-destructive/50 bg-destructive/5 p-4"
    >
      <div class="flex items-start gap-3">
        <AlertTriangle class="h-5 w-5 text-destructive flex-shrink-0 mt-0.5" />
        <div class="space-y-1">
          <p class="text-sm font-medium text-destructive">{{ t('adjustments.insufficientStockTitle') }}</p>
          <ul class="text-xs text-destructive/80 space-y-0.5">
            <li v-for="(error, i) in validationErrors" :key="i">
              {{ error }}
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div class="w-full lg:w-[300px] flex items-end">
      <Button
        class="w-full"
        :disabled="isSubmitting || hasStockErrors"
        @click="handleSubmit"
      >
        <MoveHorizontal class="mr-2 h-5 w-5" />
        {{ isSubmitting ? t('common.processing') : t('adjustments.confirm_transfer') }}
      </Button>
    </div>
  </div>
</template>