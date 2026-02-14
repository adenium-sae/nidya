<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { useToast } from '@/components/ui/toast/use-toast';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { SearchableSelect } from '@/components/ui/searchable-select';
import { Plus, Trash2, ArrowLeft, Save, MoveHorizontal } from 'lucide-vue-next';

const router = useRouter();
const { toast } = useToast();
const isSubmitting = ref(false);

const form = reactive({
  source_warehouse_id: '',
  destination_warehouse_id: '',
  notes: '',
  items: [
    { product_id: '', quantity: 0, current_source_stock: 0, source_location_id: '', destination_location_id: '' }
  ]
});

const sourceLocationEndpoint = computed(() => {
  return form.source_warehouse_id ? `/api/admin/inventory/locations?warehouse_id=${form.source_warehouse_id}` : null;
});

const destLocationEndpoint = computed(() => {
  return form.destination_warehouse_id ? `/api/admin/inventory/locations?warehouse_id=${form.destination_warehouse_id}` : null;
});

const productEndpoint = computed(() => {
  if (!form.source_warehouse_id) return null;
  return `/api/admin/products?warehouse_id=${form.source_warehouse_id}`;
});

function addItem() {
  form.items.push({ 
    product_id: '', 
    quantity: 0, 
    current_source_stock: 0, 
    source_location_id: '', 
    destination_location_id: '' 
  });
}

function handleSourceWarehouseChange() {
  // Clear items when source warehouse changes
  form.items = [{ product_id: '', quantity: 0, current_source_stock: 0, source_location_id: '', destination_location_id: '' }];
}

function removeItem(index: number) {
  form.items.splice(index, 1);
}

async function handleProductSelect(productId: string, index: number) {
  if (!productId || !form.source_warehouse_id) return;
  
  try {
    const token = localStorage.getItem('auth_token');
    const response = await axios.get(`/api/admin/inventory/stock`, {
      headers: { Authorization: `Bearer ${token}` },
      params: { 
        product_id: productId, 
        warehouse_id: form.source_warehouse_id,
        storage_location_id: form.items[index].source_location_id || undefined
      }
    });
    
    const totalQty = response.data.data?.reduce((sum: number, stock: any) => sum + stock.quantity, 0) || 0;
    form.items[index].current_source_stock = totalQty;
  } catch (error) {
    console.error('Error fetching source stock:', error);
  }
}

async function handleSubmit() {
  if (!form.source_warehouse_id || !form.destination_warehouse_id || form.items.some(i => !i.product_id || i.quantity <= 0)) {
    toast({
      title: 'Validación',
      description: 'Completa todos los campos obligatorios.',
      variant: 'destructive'
    });
    return;
  }

  if (form.source_warehouse_id === form.destination_warehouse_id) {
    toast({
      title: 'Validación',
      description: 'El almacén de origen y destino deben ser diferentes.',
      variant: 'destructive'
    });
    return;
  }

  isSubmitting.value = true;
  try {
    const token = localStorage.getItem('auth_token');
    await axios.post('/api/admin/inventory/stock/transfer', form, {
      headers: { Authorization: `Bearer ${token}` }
    });

    toast({ title: 'Éxito', description: 'Transferencia realizada correctamente.' });
    router.push('/panel/inventory/adjustments');
  } catch (error) {
    toast({
      title: 'Error',
      description: 'No se pudo realizar la transferencia.',
      variant: 'destructive'
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
        <h1 class="text-3xl font-bold tracking-tight">Nueva Transferencia</h1>
        <p class="text-muted-foreground">Mueve mercancía entre diferentes almacenes.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-card border rounded-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold flex items-center gap-2">
                <span class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-sm">1</span>
                Origen
            </h2>
            <div class="space-y-4">
                <div class="space-y-2">
                    <Label>Almacén de Origen <span class="text-destructive">*</span></Label>
                    <SearchableSelect
                        v-model="form.source_warehouse_id"
                        endpoint="/api/admin/warehouses"
                        label-key="name"
                        value-key="id"
                        placeholder="Seleccionar origen..."
                        @update:model-value="handleSourceWarehouseChange"
                    />
                </div>
            </div>
        </div>

        <div class="bg-card border rounded-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold flex items-center gap-2">
                <span class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-sm">2</span>
                Destino
            </h2>
            <div class="space-y-4">
                <div class="space-y-2">
                    <Label>Almacén de Destino <span class="text-destructive">*</span></Label>
                    <SearchableSelect
                        v-model="form.destination_warehouse_id"
                        endpoint="/api/admin/warehouses"
                        label-key="name"
                        value-key="id"
                        placeholder="Seleccionar destino..."
                    />
                </div>
            </div>
        </div>
    </div>

    <div class="bg-card border rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold">Productos a Transferir</h2>
            <Button 
                type="button" 
                variant="outline" 
                size="sm" 
                @click="addItem"
                :disabled="!form.source_warehouse_id"
            >
                <Plus class="mr-2 h-4 w-4" />
                Agregar Item
            </Button>
        </div>

        <div class="space-y-6">
            <div 
                v-for="(item, index) in form.items" 
                :key="index"
                class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start border-b pb-8 last:border-0"
            >
                <div class="lg:col-span-3 space-y-2">
                    <Label>Producto</Label>
                    <SearchableSelect
                        v-model="item.product_id"
                        :endpoint="productEndpoint"
                        label-key="name"
                        value-key="id"
                        placeholder="Buscar producto..."
                        :disabled="!form.source_warehouse_id"
                        @update:model-value="val => handleProductSelect(val, index)"
                    />
                </div>
                <div class="lg:col-span-3 space-y-2">
                    <Label>Ubicación Origen</Label>
                    <SearchableSelect
                        v-model="item.source_location_id"
                        :endpoint="sourceLocationEndpoint"
                        label-key="name"
                        value-key="id"
                        placeholder="Toda el área"
                        :disabled="!form.source_warehouse_id"
                        @update:model-value="() => handleProductSelect(item.product_id, index)"
                    />
                </div>
                <div class="lg:col-span-3 space-y-2">
                    <Label>Ubicación Destino</Label>
                    <SearchableSelect
                        v-model="item.destination_location_id"
                        :endpoint="destLocationEndpoint"
                        label-key="name"
                        value-key="id"
                        placeholder="Toda el área"
                        :disabled="!form.destination_warehouse_id"
                    />
                </div>
                <div class="lg:col-span-2 space-y-2 relative">
                    <Label>Cant.</Label>
                    <Input type="number" v-model.number="item.quantity" min="1" class="h-10" />
                    <div class="absolute -bottom-5 left-0 text-[10px] text-muted-foreground whitespace-nowrap">
                        Disponible: <span class="font-medium text-primary">{{ item.current_source_stock }}</span>
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
            <Label>Notas de Transferencia</Label>
            <Textarea v-model="form.notes" placeholder="Ej: Reposición de tienda, envío consolidado..." />
        </div>
    </div>
    <div class="w-full lg:w-[300px] flex items-end">
        <Button 
            class="w-full" 
            :disabled="isSubmitting"
            @click="handleSubmit"
        >
            <MoveHorizontal class="mr-2 h-5 w-5" />
            {{ isSubmitting ? 'Procesando...' : 'Confirmar Transferencia' }}
        </Button>
    </div>
  </div>
</template>
