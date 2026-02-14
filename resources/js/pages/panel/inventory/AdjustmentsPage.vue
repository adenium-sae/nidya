<script setup lang="ts">
import { ref, onMounted, computed, reactive } from 'vue';
import axios from 'axios';

import { DataTable, type Column, type Filter, type Pagination } from '@/components/ui/data-table';
import { ClipboardList, Plus, Trash2, ArrowDownCircle, ArrowUpCircle, MoveHorizontal } from 'lucide-vue-next';
import { useToast } from '@/components/ui/toast/use-toast';
import { useRouter } from 'vue-router';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { SearchableSelect } from '@/components/ui/searchable-select';

interface AdjustmentItem {
  id: string;
  product: {
    id: string;
    name: string;
    sku: string;
  };
  quantity_before: number;
  quantity_after: number;
}

interface Adjustment {
  id: string;
  folio: string;
  type: string;
  reason: string;
  notes?: string;
  warehouse: {
    id: string;
    name: string;
  };
  user?: {
    id: string;
    email: string;
  };
  items: AdjustmentItem[];
  created_at: string;
}

interface Warehouse {
  id: string;
  name: string;
}

const router = useRouter();
const { toast } = useToast();
const adjustments = ref<Adjustment[]>([]);
const warehouses = ref<Warehouse[]>([]);
const isLoading = ref(true);
const searchQuery = ref('');
const filterValues = ref<Record<string, string>>({});

const pagination = ref<Pagination>({
  currentPage: 1,
  lastPage: 1,
  perPage: 50,
  total: 0
});

const isCreateDialogOpen = ref(false); // Kept for minimal compatibility but unused
const isLocationDialogOpen = ref(false);
const isSubmitting = ref(false);
const isSubmittingLocation = ref(false);

const locationForm = reactive({
  warehouse_id: '',
  code: '',
  name: '',
  type: 'shelf',
  aisle: '',
  section: ''
});

const form = reactive({
  warehouse_id: '',
  storage_location_id: '',
  type: 'recount',
  reason: 'recount',
  notes: '',
  items: [
    { product_id: '', quantity_after: 0 }
  ]
});

function resetForm() {
  form.warehouse_id = '';
  form.storage_location_id = '';
  form.type = 'recount';
  form.reason = 'recount';
  form.notes = '';
  form.items = [{ product_id: '', quantity_after: 0 }];
}

function addItem() {
  form.items.push({ product_id: '', quantity_after: 0 });
}

function removeItem(index: number) {
  form.items.splice(index, 1);
}

async function handleCreateAdjustment() {
  if (!form.warehouse_id || form.items.some(item => !item.product_id)) {
    toast({
      title: 'Error',
      description: 'Por favor, completa todos los campos requeridos.',
      variant: 'destructive',
    });
    return;
  }

  isSubmitting.value = true;
  try {
    const token = localStorage.getItem('auth_token');
    await axios.post('/api/admin/inventory/stock/adjust', form, {
      headers: { Authorization: `Bearer ${token}` }
    });
    
    toast({
      title: 'Éxito',
      description: 'Ajuste de inventario registrado correctamente.',
    });
    
    isCreateDialogOpen.value = false;
    resetForm();
    fetchAdjustments();
  } catch (error) {
    console.error('Error creating adjustment:', error);
    toast({
      title: 'Error',
      description: 'No se pudo registrar el ajuste.',
      variant: 'destructive',
    });
  } finally {
    isSubmitting.value = false;
  }
}

async function handleCreateLocation() {
  if (!locationForm.name || !locationForm.code) {
    toast({
      title: 'Error',
      description: 'Nombre y código son obligatorios.',
      variant: 'destructive',
    });
    return;
  }

  isSubmittingLocation.value = true;
  try {
    const token = localStorage.getItem('auth_token');
    const response = await axios.post('/api/admin/inventory/locations', locationForm, {
      headers: { Authorization: `Bearer ${token}` }
    });
    
    toast({
      title: 'Éxito',
      description: 'Ubicación creada correctamente.',
    });
    
    // Auto-select the new location
    form.storage_location_id = response.data.data.id;
    isLocationDialogOpen.value = false;
  } catch (error) {
    console.error('Error creating location:', error);
    toast({
      title: 'Error',
      description: 'No se pudo crear la ubicación.',
      variant: 'destructive',
    });
  } finally {
    isSubmittingLocation.value = false;
  }
}

const locationEndpoint = computed(() => {
  return form.warehouse_id ? `/api/admin/inventory/locations?warehouse_id=${form.warehouse_id}` : null;
});

function handleAddNewLocation() {
  if (!form.warehouse_id) {
    toast({
      title: 'Atención',
      description: 'Primero selecciona un almacén.',
      variant: 'destructive',
    });
    return;
  }
  
  locationForm.warehouse_id = form.warehouse_id;
  locationForm.code = '';
  locationForm.name = '';
  locationForm.type = 'shelf';
  locationForm.aisle = '';
  locationForm.section = '';
  isLocationDialogOpen.value = true;
}

function handleAddNewWarehouse() {
  toast({
    title: 'Crear Almacén',
    description: 'Navega a la sección de Almacenes para crear uno nuevo.',
  });
}

function handleAddNewProduct() {
  toast({
    title: 'Crear Producto',
    description: 'Navega a la sección de Productos para crear uno nuevo.',
  });
}

const adjustmentTypeLabels: Record<string, string> = {
  'increase': 'Entrada',
  'decrease': 'Salida',
  'recount': 'Recuento',
};

const reasonLabels: Record<string, string> = {
  'recount': 'Recuento Cíclico',
  'damaged': 'Producto Dañado',
  'lost': 'Pérdida/Robo',
  'found': 'Hallazgo',
  'expired': 'Caducado',
  'other': 'Otro',
};

const locationTypeOptions = [
  { value: 'shelf', label: 'Estante' },
  { value: 'box', label: 'Caja' },
  { value: 'pallet', label: 'Tarima (Pallet)' },
  { value: 'display', label: 'Exhibidor' },
  { value: 'floor', label: 'Piso' },
  { value: 'other', label: 'Otro' },
];

// DataTable Configuration
const columns: Column[] = [
  { 
    key: 'folio', 
    label: 'Folio', 
    type: 'text',
    sortable: true
  },
  {
    key: 'created_at',
    label: 'Fecha',
    type: 'date',
    sortable: true
  },
  { 
    key: 'warehouse', 
    label: 'Almacén', 
    type: 'custom' 
  },
  {
    key: 'type',
    label: 'Tipo',
    type: 'custom'
  },
  {
    key: 'reason',
    label: 'Razón',
    type: 'custom'
  },
  {
    key: 'user',
    label: 'Usuario',
    type: 'custom'
  },
  {
    key: 'items_count',
    label: 'Productos',
    type: 'custom',
    align: 'right'
  }
];

const filters = computed<Filter[]>(() => [
  {
    key: 'warehouse_id',
    label: 'Almacén',
    type: 'select',
    options: warehouses.value.map(w => ({ value: w.id, label: w.name }))
  },
  {
    key: 'type',
    label: 'Tipo',
    type: 'select',
    options: Object.entries(adjustmentTypeLabels).map(([value, label]) => ({ value, label }))
  },
  {
    key: 'reason',
    label: 'Razón',
    type: 'select',
    options: Object.entries(reasonLabels).map(([value, label]) => ({ value, label }))
  }
]);

async function fetchAdjustments() {
  isLoading.value = true;
  try {
    const token = localStorage.getItem('auth_token');
    const response = await axios.get('/api/admin/inventory/stock/adjustments', {
      headers: { Authorization: `Bearer ${token}` },
      params: {
        page: pagination.value.currentPage,
        per_page: pagination.value.perPage,
        ...filterValues.value
      }
    });
    
    const data = response.data;
    adjustments.value = data.data || data;
    
    if (data.meta) {
      pagination.value = {
        currentPage: data.meta.current_page,
        lastPage: data.meta.last_page,
        perPage: data.meta.per_page,
        total: data.meta.total
      };
    } else if (data.current_page) {
      pagination.value = {
        currentPage: data.current_page,
        lastPage: data.last_page,
        perPage: data.per_page,
        total: data.total
      };
    }
  } catch (error) {
    console.error('Error fetching adjustments:', error);
    toast({
      title: 'Error',
      description: 'No se pudieron cargar los ajustes.',
      variant: 'destructive',
    });
  } finally {
    isLoading.value = false;
  }
}

async function fetchWarehouses() {
  try {
    const token = localStorage.getItem('auth_token');
    const response = await axios.get('/api/admin/warehouses', {
      headers: { Authorization: `Bearer ${token}` }
    });
    warehouses.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching warehouses:', error);
  }
}

function handleSearch(value: string) {
  searchQuery.value = value;
  pagination.value.currentPage = 1;
  fetchAdjustments();
}

function handleFilter(key: string, value: string) {
  if (value) {
    filterValues.value[key] = value;
  } else {
    delete filterValues.value[key];
  }
  pagination.value.currentPage = 1;
  fetchAdjustments();
}

function handlePageChange(page: number) {
  pagination.value.currentPage = page;
  fetchAdjustments();
}

function getTypeLabel(type: string): string {
  return adjustmentTypeLabels[type] || type;
}

function getTypeClass(type: string): string {
  const classes: Record<string, string> = {
    'increase': 'bg-green-100 text-green-800',
    'decrease': 'bg-red-100 text-red-800',
    'recount': 'bg-blue-100 text-blue-800',
  };
  return classes[type] || 'bg-gray-100 text-gray-800';
}

function getReasonLabel(reason: string): string {
  return reasonLabels[reason] || reason;
}

onMounted(function() {
  fetchAdjustments();
  fetchWarehouses();
});
</script>

<template>
  <div class="h-[calc(100vh-120px)] flex flex-col">
    <div class="flex flex-col gap-6 h-full">
      <DataTable
        :columns="columns"
        :data="adjustments"
        :is-loading="isLoading"
        :search-value="searchQuery"
        :filters="filters"
        :pagination="pagination"
        search-placeholder="Buscar por folio..."
        empty-message="No hay ajustes de inventario registrados."
        :empty-icon="ClipboardList"
        class="flex-1 min-h-0"
        @search="handleSearch"
        @filter="handleFilter"
        @page-change="handlePageChange"
      >
        <template #toolbar-end>
          <div class="flex gap-2">
            <Button variant="outline" @click="router.push('/panel/inventory/adjustments/entry')">
              <ArrowDownCircle class="mr-2 h-4 w-4 text-green-600" />
              Nueva Entrada
            </Button>
            <Button variant="outline" @click="router.push('/panel/inventory/adjustments/exit')">
              <ArrowUpCircle class="mr-2 h-4 w-4 text-red-600" />
              Nueva Salida
            </Button>
            <Button @click="router.push('/panel/inventory/adjustments/transfer')">
              <MoveHorizontal class="mr-2 h-4 w-4" />
              Transferencia
            </Button>
          </div>
        </template>

        <template #cell-warehouse="{ row }">
          {{ row.warehouse?.name || '-' }}
        </template>

        <template #cell-type="{ row }">
          <span 
            :class="getTypeClass(row.type)"
            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
          >
            {{ getTypeLabel(row.type) }}
          </span>
        </template>

        <template #cell-reason="{ row }">
          {{ getReasonLabel(row.reason) }}
        </template>

        <template #cell-user="{ row }">
          {{ row.user?.email || '-' }}
        </template>

        <template #cell-items_count="{ row }">
          <span class="font-medium">{{ row.items?.length || 0 }}</span>
        </template>
      </DataTable>
    </div>

    <!-- Create Adjustment Dialog -->
    <Dialog v-model:open="isCreateDialogOpen">
      <DialogContent class="sm:max-w-[700px] max-h-[90vh] flex flex-col">
        <DialogHeader>
          <DialogTitle>Registrar Ajuste Manual</DialogTitle>
          <DialogDescription>
            Crea un ajuste de stock seleccionando los productos y almacén destino.
          </DialogDescription>
        </DialogHeader>

        <div class="flex-1 overflow-y-auto px-6 py-4">
          <div class="grid gap-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div class="grid gap-2">
                <Label>Almacén <span class="text-destructive">*</span></Label>
                <SearchableSelect
                  v-model="form.warehouse_id"
                  endpoint="/api/admin/warehouses"
                  label-key="name"
                  value-key="id"
                  placeholder="Seleccionar almacén..."
                  show-add-option
                  add-option-label="Nuevo Almacén"
                  @add-click="handleAddNewWarehouse"
                />
              </div>
              <div class="grid gap-2">
                <Label>Ubicación (Opcional)</Label>
                <SearchableSelect
                  v-model="form.storage_location_id"
                  :endpoint="locationEndpoint"
                  label-key="name"
                  value-key="id"
                  placeholder="Seleccionar ubicación..."
                  :disabled="!form.warehouse_id"
                  show-add-option
                  add-option-label="Nueva Ubicación"
                  @add-click="handleAddNewLocation"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4">
              <div class="grid gap-2">
                <Label>Tipo de Ajuste <span class="text-destructive">*</span></Label>
                <Select v-model="form.type">
                  <SelectTrigger>
                    <SelectValue placeholder="Seleccionar tipo..." />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="(label, val) in adjustmentTypeLabels" :key="val" :value="val">
                      {{ label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="grid gap-2">
                <Label>Razón <span class="text-destructive">*</span></Label>
                <Select v-model="form.reason">
                  <SelectTrigger>
                    <SelectValue placeholder="Seleccionar razón..." />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="(label, val) in reasonLabels" :key="val" :value="val">
                      {{ label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div class="grid gap-4">
              <div class="flex items-center justify-between">
                <Label class="text-base font-semibold">Productos</Label>
                <Button type="button" variant="outline" size="sm" @click="addItem">
                  <Plus class="mr-2 h-4 w-4" />
                  Agregar Producto
                </Button>
              </div>

              <div class="border rounded-lg overflow-hidden shrink-0">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="border-b bg-muted/50">
                      <th class="p-2 text-left font-medium">Producto <span class="text-destructive">*</span></th>
                      <th class="p-2 text-left font-medium w-32">Cant. Final <span class="text-destructive">*</span></th>
                      <th class="p-2 w-10"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in form.items" :key="index" class="border-b last:border-0">
                      <td class="p-2">
                        <SearchableSelect
                          v-model="item.product_id"
                          endpoint="/api/admin/products"
                          label-key="name"
                          value-key="id"
                          placeholder="Buscar producto..."
                          show-add-option
                          add-option-label="Nuevo Producto"
                          @add-click="handleAddNewProduct"
                        />
                      </td>
                      <td class="p-2">
                        <Input
                          v-model.number="item.quantity_after"
                          type="number"
                          min="0"
                          placeholder="0"
                        />
                      </td>
                      <td class="p-2">
                        <Button
                          v-if="form.items.length > 1"
                          type="button"
                          variant="ghost"
                          size="icon"
                          class="h-8 w-8 text-destructive"
                          @click="removeItem(index)"
                        >
                          <Trash2 class="h-4 w-4" />
                        </Button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="grid gap-2">
              <Label>Notas (Opcional)</Label>
              <Textarea
                v-model="form.notes"
                placeholder="Escribe detalles sobre este ajuste..."
              />
            </div>
          </div>
        </div>

        <DialogFooter class="pt-4 border-t">
          <Button variant="outline" @click="isCreateDialogOpen = false">
            Cancelar
          </Button>
          <Button :disabled="isSubmitting" @click="handleCreateAdjustment">
            {{ isSubmitting ? 'Guardando...' : 'Guardar Ajuste' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Create Location Dialog -->
    <Dialog v-model:open="isLocationDialogOpen">
      <DialogContent class="sm:max-w-[450px]">
        <DialogHeader>
          <DialogTitle>Nueva Ubicación</DialogTitle>
          <DialogDescription>
            Registra un nuevo pasillo, estante o área dentro del almacén.
          </DialogDescription>
        </DialogHeader>

        <div class="grid gap-4 py-4">
          <div class="grid gap-2">
            <Label>Código / Identificador <span class="text-destructive">*</span></Label>
            <Input v-model="locationForm.code" placeholder="P-01, EST-A, etc." />
          </div>
          <div class="grid gap-2">
            <Label>Nombre Descriptivo <span class="text-destructive">*</span></Label>
            <Input v-model="locationForm.name" placeholder="Pasillo 1, Estante Principal, etc." />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label>Tipo</Label>
              <Select v-model="locationForm.type">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="opt in locationTypeOptions" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="grid gap-2">
              <Label>Pasillo (Opcional)</Label>
              <Input v-model="locationForm.aisle" placeholder="A, 1..." />
            </div>
          </div>
          <div class="grid gap-2">
            <Label>Sección / Nivel (Opcional)</Label>
            <Input v-model="locationForm.section" placeholder="Nivel 2, Lado B..." />
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="isLocationDialogOpen = false">
            Cancelar
          </Button>
          <Button :disabled="isSubmittingLocation" @click="handleCreateLocation">
            {{ isSubmittingLocation ? 'Guardando...' : 'Crear Ubicación' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
