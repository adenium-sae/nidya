<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { useToast } from '@/components/ui/toast/use-toast';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/searchable-select';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Trash2, ArrowLeft, Save } from 'lucide-vue-next';

const router = useRouter();
const { toast } = useToast();
const isSubmitting = ref(false);

const form = reactive({
  warehouse_id: '',
  storage_location_id: '',
  type: 'decrease',
  items: [
    { product_id: '', quantity_to_remove: 0, current_quantity: 0, reason: 'damaged' }
  ]
});

const reasons = [
  { value: 'damaged', label: 'Dañado' },
  { value: 'lost', label: 'Extravío / Robo' },
  { value: 'expired', label: 'Caducado' },
  { value: 'other', label: 'Otro' },
];

const locationEndpoint = computed(() => {
  return form.warehouse_id ? `/api/admin/inventory/locations?warehouse_id=${form.warehouse_id}` : null;
});

const productEndpoint = computed(() => {
  if (!form.warehouse_id) return null;
  let url = `/api/admin/products?warehouse_id=${form.warehouse_id}`;
  if (form.storage_location_id) {
    url += `&storage_location_id=${form.storage_location_id}`;
  }
  return url;
});

function addItem() {
  form.items.push({ product_id: '', quantity_to_remove: 0, current_quantity: 0, reason: 'damaged' });
}

function handleWarehouseChange() {
  // Clear items when warehouse changes to maintain consistency
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
      title: 'Validación',
      description: 'Completa todos los campos obligatorios.',
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
        quantity_after: Math.max(0, i.current_quantity - i.quantity_to_remove),
        reason: i.reason
      }))
    };

    await axios.post('/api/admin/inventory/stock/adjust', payload, {
      headers: { Authorization: `Bearer ${token}` }
    });

    toast({ title: 'Éxito', description: 'Salida de almacén registrada.' });
    router.push('/panel/inventory/adjustments');
  } catch (error) {
    toast({
      title: 'Error',
      description: 'No se pudo registrar la salida.',
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
        <h1 class="text-3xl font-bold tracking-tight">Nueva Salida</h1>
        <p class="text-muted-foreground">Registra la baja de mercancía del almacén.</p>
      </div>
    </div>

    <!-- Step 1: Origin -->
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
            @update:model-value="handleWarehouseChange"
          />
        </div>
      </div>
    </div>

    <!-- Step 2: Products -->
    <div class="bg-card border rounded-lg p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Productos a dar de baja</h2>
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
              @update:model-value="val => handleProductSelect(val, index)"
            />
          </div>
          <div class="md:col-span-2 space-y-2 relative">
            <Label>A retirar</Label>
            <Input type="number" v-model.number="item.quantity_to_remove" min="1" class="h-10" />
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
          {{ isSubmitting ? 'Guardando...' : 'Registrar Salida' }}
        </Button>
      </div>
    </div>
  </div>
</template>
