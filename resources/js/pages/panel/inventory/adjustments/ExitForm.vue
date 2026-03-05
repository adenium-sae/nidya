<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/components/ui/toast/use-toast';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/searchable-select';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Trash2, ArrowLeft, Save } from 'lucide-vue-next';
import StorageLocationFormDialog from '@/components/inventory/StorageLocationFormDialog.vue';

const router = useRouter();
const { t } = useI18n();
const { toast } = useToast();
const isSubmitting = ref(false);
const isCreatingLocation = ref(false);

function handleLocationCreated(location: any) {
  form.storage_location_id = location.id;
}

const form = reactive({
  warehouse_id: '',
  storage_location_id: '',
  type: 'decrease',
  items: [
    { product_id: '', quantity_to_remove: 0, current_quantity: 0, reason: 'damaged' }
  ]
});

const reasons = computed(() => [
  { value: 'damaged', label: t('adjustments.reason_damaged') },
  { value: 'lost', label: t('adjustments.reason_lost') },
  { value: 'expired', label: t('adjustments.reason_expired') },
  { value: 'other', label: t('adjustments.reason_other') },
]);

const locationEndpoint = computed(() => {
  return form.warehouse_id ? `/admin/inventory/locations?warehouse_id=${form.warehouse_id}` : undefined;
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
  form.items.push({ product_id: '', quantity_to_remove: 0, current_quantity: 0, reason: 'damaged' });
}

function handleWarehouseChange() {
  form.items = [{ product_id: '', quantity_to_remove: 0, current_quantity: 0, reason: 'damaged' }];
}

function removeItem(index: number) {
  form.items.splice(index, 1);
}

async function handleProductSelect(productId: string, index: number) {
  if (!productId || !form.warehouse_id) return;
  
  try {
    const token = localStorage.getItem('auth_token');
    const response = await axios.get(`/api/admin/inventory/stock`, {
      headers: { Authorization: `Bearer ${token}` },
      params: { 
        product_id: productId, 
        warehouse_id: form.warehouse_id,
        storage_location_id: form.storage_location_id || undefined
      }
    });
    
    const totalQty = response.data.data?.reduce((sum: number, stock: any) => sum + stock.quantity, 0) || 0;
    form.items[index].current_quantity = totalQty;
  } catch (error) {
    console.error('Error fetching product stock:', error);
  }
}

async function handleSubmit() {
  if (!form.warehouse_id || form.items.some(i => !i.product_id || i.quantity_to_remove <= 0)) {
    toast({
      title: t('common.validation_error'),
      description: t('adjustments.validation_msg'),
      variant: 'destructive'
    });
    return;
  }

  isSubmitting.value = true;
  try {
    const token = localStorage.getItem('auth_token');
    
    const payload = {
      ...form,
      items: form.items.map(i => ({
        product_id: i.product_id,
        quantity: i.quantity_to_remove,
        mode: 'decrement',
        reason: i.reason
      }))
    };

    await axios.post('/api/admin/inventory/stock/adjust', payload, {
      headers: { Authorization: `Bearer ${token}` }
    });

    toast({ title: t('common.success'), description: t('adjustments.exit_success') });
    router.push('/panel/inventory/adjustments');
  } catch (error) {
    toast({
      title: t('common.error'),
      description: t('adjustments.exit_error'),
      variant: 'destructive'
    });
  } finally {
    isSubmitting.value = false;
  }
}
</script>

<template>
  <div class="flex flex-col gap-6 max-w-6xl mx-auto">
    <div class="flex items-center gap-4">
      <Button variant="ghost" size="icon" @click="router.back()">
        <ArrowLeft class="h-4 w-4" />
      </Button>
      <div>
        <h1 class="text-3xl font-bold tracking-tight">{{ t('adjustments.new_exit') }}</h1>
        <p class="text-muted-foreground">{{ t('adjustments.subtitle_exit') }}</p>
      </div>
    </div>

    <!-- Step 1: Origin -->
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
          <Label>{{ t('inventory.location') }} ({{ t('common.optional') }})</Label>
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
            @update:model-value="handleWarehouseChange"
          />
        </div>
      </div>
    </div>

    <!-- Step 2: Products -->
    <div class="bg-card border rounded-lg p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">{{ t('adjustments.products_to_remove') }}</h2>
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
            <Label>{{ t('adjustments.qty_to_remove') }}</Label>
            <Input type="number" v-model.number="item.quantity_to_remove" min="1" class="h-10" />
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
                <SelectItem v-for="r in reasons" :key="r.value" :value="r.value">
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

    <!-- Step 3: Confirmation -->
    <div class="flex justify-end">
      <div class="w-full md:w-[300px]">
        <Button 
          class="w-full font-bold shadow-lg shadow-destructive/20" 
          variant="destructive"
          :disabled="isSubmitting"
          @click="handleSubmit"
        >
          <Save class="mr-2 h-6 w-6" />
          {{ isSubmitting ? t('common.saving') : t('adjustments.submit_exit') }}
        </Button>
      </div>
    </div>
  </div>
  <StorageLocationFormDialog
    v-model:open="isCreatingLocation"
    :warehouse-id="form.warehouse_id"
    @saved="handleLocationCreated"
  />
</template>
