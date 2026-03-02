<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue';
import { stockApi } from '@/api/stock.api';
import { useApiList } from '@/composables/useApiList';
import { DataTable, type Column } from '@/components/ui/data-table';
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
import { ArrowRightLeft, Search } from 'lucide-vue-next';
import { useToast } from '@/components/ui/toast/use-toast';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import PageHeader from '@/components/app/PageHeader.vue';
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
const { toast } = useToast();

const {
  items: stockItems,
  isLoading,
  searchQuery,
  fetch: fetchStock,
  search,
} = useApiList(stockApi.list);

const isDialogOpen = ref(false);
const processing = ref(false);

const form = reactive({
  stockItem: null as StockItem | null,
  type: 'recount',
  quantity: '',
  reason: 'recount',
  notes: '',
});

const adjustmentTypes = [
  { value: 'increase', label: 'Entrada (Aumentar)' },
  { value: 'decrease', label: 'Salida (Disminuir)' },
  { value: 'recount', label: 'Recuento (Fijar Total)' },
];

const reasons = [
  { value: 'recount', label: 'Recuento Cíclico' },
  { value: 'damaged', label: 'Producto Dañado' },
  { value: 'lost', label: 'Pérdida/Robo' },
  { value: 'found', label: 'Hallazgo' },
  { value: 'expired', label: 'Caducado' },
  { value: 'other', label: 'Otro' },
];

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
    toast({ title: 'Éxito', description: 'Inventario ajustado correctamente.' });
    isDialogOpen.value = false;
    fetchStock();
  } catch (error) {
    console.error('Error adjusting stock:', error);
    toast({
      title: 'Error',
      description: 'Hubo un error al ajustar el inventario.',
      variant: 'destructive',
    });
  } finally {
    processing.value = false;
  }
}

onMounted(() => fetchStock());

const columns: Column[] = [
  { key: 'product', label: 'Producto', type: 'custom' },
  { key: 'sku', label: 'SKU', type: 'custom' },
  { key: 'warehouse', label: 'Almacén', type: 'custom' },
  { key: 'location', label: 'Ubicación', type: 'custom' },
  { key: 'quantity', label: 'Disponible', type: 'custom', align: 'right' },
  { key: 'reserved', label: 'Reservado', type: 'custom', align: 'right' },
  { key: 'actions', label: '', type: 'custom', align: 'right' },
];
</script>

<template>
  <div class="flex flex-col gap-6">
    <div class="flex items-center gap-2">
      <div class="relative flex-1 max-w-sm">
        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
        <Input
          v-model="searchQuery"
          placeholder="Buscar producto o SKU..."
          class="pl-8"
          @keyup.enter="handleSearch"
        />
      </div>
    </div>

    <div class="h-[calc(100vh-160px)] flex flex-col rounded-md border">
      <DataTable
        :columns="columns"
        :data="stockItems"
        :is-loading="isLoading"
        :search-value="searchQuery"
        search-placeholder="Buscar producto o SKU..."
        empty-message="No hay existencias registradas."
        :empty-icon="ArrowRightLeft"
        class="flex-1 min-h-0"
        @search="search"
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
            <span v-else class="text-muted-foreground italic text-xs">Sin ubicar</span>
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
              Ajustar
            </Button>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Adjust Dialog -->
    <Dialog v-model:open="isDialogOpen">
      <DialogContent class="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>Ajustar Inventario</DialogTitle>
          <DialogDescription v-if="form.stockItem">
            {{ form.stockItem.product.name }} en {{ form.stockItem.warehouse.name }}
          </DialogDescription>
        </DialogHeader>
        <div class="grid gap-4 py-4" v-if="form.stockItem">
          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label htmlFor="type">Tipo de Ajuste</Label>
              <Select v-model="form.type">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="t in adjustmentTypes" :key="t.value" :value="t.value">
                    {{ t.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="grid gap-2">
              <Label htmlFor="reason">Razón</Label>
              <Select v-model="form.reason">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="r in reasons" :key="r.value" :value="r.value">
                    {{ r.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <div class="grid gap-2">
            <Label>Cantidad Actual: {{ form.stockItem.quantity }}</Label>
          </div>
          <div class="grid gap-2">
            <Label htmlFor="quantity">
              {{ form.type === 'recount' ? 'Nueva Cantidad Total' : 'Cantidad a Ajustar' }}
            </Label>
            <Input id="quantity" type="number" v-model="form.quantity" />
          </div>
          <div class="p-3 bg-muted rounded-md text-sm text-center">
            <span class="text-muted-foreground">Cantidad Resultante:</span>
            <span class="font-bold text-lg ml-2">{{ calculatedTotal }}</span>
          </div>
          <div class="grid gap-2">
            <Label htmlFor="notes">Notas (Opcional)</Label>
            <Textarea id="notes" v-model="form.notes" />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="isDialogOpen = false">Cancelar</Button>
          <Button type="submit" :disabled="processing" @click="handleSubmit">
            {{ processing ? 'Procesando...' : 'Confirmar Ajuste' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>