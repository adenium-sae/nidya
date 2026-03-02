<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from '@/components/ui/toast/use-toast';
import { stockApi } from '@/api/stock.api';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/searchable-select';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Trash2, Save } from 'lucide-vue-next';
import PageHeader from '@/components/app/PageHeader.vue';

type AdjustmentMode = 'entry' | 'exit' | 'adjustment';

const props = defineProps<{
  mode: AdjustmentMode;
}>();

const router = useRouter();
const { toast } = useToast();
const isSubmitting = ref(false);

// Mode-specific configuration
const config = computed(() => ({
  entry: {
    title: 'Nueva Entrada',
    description: 'Registra el ingreso de mercancía al almacén.',
    quantityLabel: 'Cant.',
    apiMode: 'increment',
    type: 'increase',
    submitLabel: 'Registrar Entrada',
    submitVariant: 'default' as const,
    quantityMin: 1,
    reasons: [
      { value: 'found', label: 'Hallazgo' },
      { value: 'recount', label: 'Recuento' },
      { value: 'other', label: 'Otro' },
    ],
    successMessage: 'Entrada de almacén registrada.',
    errorMessage: 'No se pudo registrar la entrada.',
  },
  exit: {
    title: 'Nueva Salida',
    description: 'Registra la baja de mercancía del almacén.',
    quantityLabel: 'A retirar',
    apiMode: 'decrement',
    type: 'decrease',
    submitLabel: 'Registrar Salida',
    submitVariant: 'destructive' as const,
    quantityMin: 1,
    reasons: [
      { value: 'damaged', label: 'Dañado' },
      { value: 'lost', label: 'Extravío / Robo' },
      { value: 'expired', label: 'Caducado' },
      { value: 'other', label: 'Otro' },
    ],
    successMessage: 'Salida de almacén registrada.',
    errorMessage: 'No se pudo registrar la salida.',
  },
  adjustment: {
    title: 'Nuevo Ajuste',
    description: 'Reemplaza el valor de stock directamente.',
    quantityLabel: 'Nueva Cant.',
    apiMode: 'absolute',
    type: 'recount',
    submitLabel: 'Registrar Ajuste',
    submitVariant: 'default' as const,
    quantityMin: 0,
    reasons: [
      { value: 'recount', label: 'Recuento' },
      { value: 'correction', label: 'Corrección' },
      { value: 'other', label: 'Otro' },
    ],
    successMessage: 'Ajuste de inventario registrado.',
    errorMessage: 'No se pudo registrar el ajuste.',
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
    ? `/api/admin/inventory/locations?warehouse_id=${form.warehouse_id}`
    : undefined;
});

const productEndpoint = computed(() => {
  if (!form.warehouse_id) return undefined;
  let url = `/api/admin/products?warehouse_id=${form.warehouse_id}`;
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
      title: 'Validación',
      description: `Completa todos los campos. La cantidad debe ser ${minQty > 0 ? 'mayor a 0' : 'válida'}.`,
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
    toast({ title: 'Éxito', description: config.value.successMessage });
    router.push('/panel/inventory/adjustments');
  } catch (error) {
    toast({
      title: 'Error',
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
          <Label>Almacén <span class="text-destructive">*</span></Label>
          <SearchableSelect
            v-model="form.warehouse_id"
            endpoint="/api/admin/warehouses"
            label-key="name"
            value-key="id"
            placeholder="Seleccionar almacén..."
            @update:model-value="handleWarehouseChange"
          />
        </div>
        <div class="space-y-2">
          <Label>Ubicación (Opcional)</Label>
          <SearchableSelect
            v-model="form.storage_location_id"
            :endpoint="locationEndpoint"
            label-key="name"
            value-key="id"
            placeholder="Seleccionar ubicación..."
            :disabled="!form.warehouse_id"
          />
        </div>
      </div>
    </div>

    <!-- Products -->
    <div class="bg-card border rounded-lg p-6">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold">Productos</h2>
        <Button
          type="button"
          variant="outline"
          size="sm"
          @click="addItem"
          :disabled="!form.warehouse_id"
        >
          <Plus class="mr-2 h-4 w-4" />
          Agregar Item
        </Button>
      </div>

      <div class="space-y-4">
        <div
          v-for="(item, index) in form.items"
          :key="index"
          class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start border-b pb-8 last:border-0"
        >
          <div class="md:col-span-5 space-y-2">
            <Label>Producto</Label>
            <SearchableSelect
              v-model="item.product_id"
              :endpoint="productEndpoint"
              label-key="name"
              value-key="id"
              placeholder="Buscar producto..."
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
              En stock: <span class="font-medium text-primary">{{ item.current_quantity }}</span>
            </div>
          </div>
          <div class="md:col-span-4 space-y-2">
            <Label>Motivo</Label>
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
        {{ isSubmitting ? 'Guardando...' : config.submitLabel }}
      </Button>
    </div>
  </div>
</template>
