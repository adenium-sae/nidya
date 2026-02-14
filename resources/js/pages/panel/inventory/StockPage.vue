<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue'
import axios from 'axios'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import { 
  ArrowRightLeft, 
  Search,
  History
} from 'lucide-vue-next'
import { useToast } from '@/components/ui/toast/use-toast'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'

interface StockItem {
  id: string
  quantity: number
  reserved: number
  product: {
    id: string
    name: string
    sku: string
  }
  warehouse: {
    id: string
    name: string
  }
  storage_location?: {
    id: string
    name: string
    code: string
  }
}

const { toast } = useToast()
const stockItems = ref<StockItem[]>([])
const isLoading = ref(true)
const searchQuery = ref('')
const isDialogOpen = ref(false)
const processing = ref(false)

const form = reactive({
  stockItem: null as StockItem | null,
  type: 'recount', // increase, decrease, recount
  quantity: '', // The amount to add/sub or the new total
  reason: 'recount', // damaged, lost, found, expired, recount, other
  notes: ''
})

const adjustmentTypes = [
  { value: 'increase', label: 'Entrada (Aumentar)' },
  { value: 'decrease', label: 'Salida (Disminuir)' },
  { value: 'recount', label: 'Recuento (Fijar Total)' }
]

const reasons = [
  { value: 'recount', label: 'Recuento Cíclico' },
  { value: 'damaged', label: 'Producto Dañado' },
  { value: 'lost', label: 'Pérdida/Robo' },
  { value: 'found', label: 'Hallazgo' },
  { value: 'expired', label: 'Caducado' },
  { value: 'other', label: 'Otro' },
]

async function fetchStock() {
  isLoading.value = true;
  try {
    const token = localStorage.getItem('auth_token');
    const response = await axios.get('/api/admin/inventory/stock', {
      headers: { Authorization: `Bearer ${token}` },
      params: { search: searchQuery.value }
    });
    stockItems.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching stock:', error);
  } finally {
    isLoading.value = false;
  }
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

const calculatedTotal = computed(function() {
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
  const token = localStorage.getItem('auth_token');
  try {
     const payload = {
        warehouse_id: form.stockItem.warehouse.id,
        type: form.type, // This is just for record keeping in the adjustment header
        reason: form.reason,
        notes: form.notes,
        items: [
            {
                product_id: form.stockItem.product.id,
                storage_location_id: form.stockItem.storage_location?.id || null,
                quantity_after: calculatedTotal.value
            }
        ]
     };

    await axios.post('/api/admin/inventory/stock/adjust', payload, {
        headers: { Authorization: `Bearer ${token}` }
    });
    
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

onMounted(function() {
  fetchStock();
});
</script>

<template>
  <div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Existencias</h1>
        <p class="text-muted-foreground">Consulta y ajusta el inventario por almacén.</p>
      </div>
      <Button variant="outline">
        <History class="mr-2 h-4 w-4" />
        Ver Movimientos
      </Button>
    </div>

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

    <div class="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Producto</TableHead>
            <TableHead>SKU</TableHead>
            <TableHead>Almacén</TableHead>
            <TableHead>Ubicación</TableHead>
            <TableHead class="text-right">Disponible</TableHead>
            <TableHead class="text-right">Reservado</TableHead>
            <TableHead class="text-right">Acciones</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
           <TableRow v-if="stockItems.length === 0 && !isLoading">
            <TableCell colspan="6" class="text-center h-24 text-muted-foreground">
              No hay existencias registradas.
            </TableCell>
          </TableRow>
          <TableRow v-for="item in stockItems" :key="item.id">
            <TableCell class="font-medium">{{ item.product?.name }}</TableCell>
            <TableCell>{{ item.product?.sku }}</TableCell>
            <TableCell>{{ item.warehouse?.name }}</TableCell>
            <TableCell>
              <span v-if="item.storage_location" class="text-xs font-mono bg-muted px-1.5 py-0.5 rounded">
                {{ item.storage_location.code }}
              </span>
              <span v-else class="text-muted-foreground italic text-xs">Sin ubicar</span>
            </TableCell>
            <TableCell class="text-right font-bold">{{ item.quantity }}</TableCell>
            <TableCell class="text-right text-muted-foreground">{{ item.reserved }}</TableCell>
            <TableCell class="text-right">
              <Button variant="outline" size="sm" @click="openAdjustDialog(item)">
                <ArrowRightLeft class="mr-2 h-3 w-3" />
                Ajustar
              </Button>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
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
                      <SelectTrigger>
                          <SelectValue />
                      </SelectTrigger>
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
