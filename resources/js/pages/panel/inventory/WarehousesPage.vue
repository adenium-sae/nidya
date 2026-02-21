<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue';
import { warehousesApi } from '@/api/warehouses.api';
import { useApiList } from '@/composables/useApiList';
import { useConfirmDelete } from '@/composables/useConfirmDelete';
import { DataTable, type Column, type Action } from '@/components/ui/data-table';
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Plus, Warehouse } from 'lucide-vue-next';
import { useToast } from '@/components/ui/toast/use-toast';
import { Checkbox } from '@/components/ui/checkbox';
import ConfirmDialog from '@/components/app/ConfirmDialog.vue';
import type { Warehouse as WarehouseModel } from '@/types/models';

const { toast } = useToast();

// List composable
const {
  items: warehouses,
  isLoading,
  searchQuery,
  fetch: fetchWarehouses,
  search,
  removeItem,
} = useApiList<WarehouseModel>(warehousesApi.list);

// Delete composable
const {
  isOpen: deleteDialogOpen,
  isDeleting,
  itemToDelete,
  openDialog: _openDeleteDialog,
  confirmDelete,
} = useConfirmDelete(warehousesApi.destroy, {
  successMessage: 'Almacén eliminado correctamente.',
  onSuccess: (item: any) => removeItem(item.id),
});

// Form dialog
const isDialogOpen = ref(false);
const isEditing = ref(false);
const currentId = ref<string | null>(null);
const types = ref<{ id: string; name: string }[]>([]);

const form = reactive({
  name: '',
  code: '',
  type: 'central',
  store_id: '',
  branch_id: '',
  is_active: true,
});

const columns = computed<Column[]>(() => {
  const badgeVariants: Record<string, { label: string; class: string }> = {};
  const classes: Record<string, string> = {
    central: 'bg-blue-100 text-blue-800',
    branch: 'bg-purple-100 text-purple-800',
    distribution: 'bg-orange-100 text-orange-800',
  };
  types.value.forEach(t => {
    badgeVariants[t.id] = {
      label: t.name,
      class: classes[t.id] || 'bg-gray-100 text-gray-800',
    };
  });
  return [
    { key: 'name', label: 'Nombre', sortable: true, type: 'text' },
    { key: 'code', label: 'Código', type: 'text' },
    { key: 'type', label: 'Tipo', type: 'badge', badgeVariants },
    { key: 'store', label: 'Tienda', type: 'custom' },
    { key: 'branch', label: 'Sucursal', type: 'custom' },
    { key: 'is_active', label: 'Estado', type: 'custom' },
  ];
});

const actions: Action[] = [
  { key: 'edit', label: 'Editar' },
  { key: 'delete', label: 'Eliminar', variant: 'destructive' },
];

function handleAction(actionKey: string, row: any) {
  if (actionKey === 'edit') {
    openEditDialog(row);
  } else if (actionKey === 'delete') {
    openDeleteDialog(row);
  }
}

function openDeleteDialog(item: WarehouseModel) {
  _openDeleteDialog(item);
}

function openCreateDialog() {
  isEditing.value = false;
  currentId.value = null;
  Object.assign(form, { name: '', code: '', type: 'central', store_id: '', branch_id: '', is_active: true });
  isDialogOpen.value = true;
}

function openEditDialog(warehouse: WarehouseModel) {
  isEditing.value = true;
  currentId.value = warehouse.id;
  Object.assign(form, {
    name: warehouse.name,
    code: warehouse.code || '',
    type: warehouse.type,
    store_id: warehouse.store_id || '',
    branch_id: warehouse.branch_id || '',
    is_active: warehouse.is_active,
  });
  isDialogOpen.value = true;
}

async function handleSubmit() {
  try {
    if (isEditing.value && currentId.value) {
      await warehousesApi.update(currentId.value, form);
      toast({ title: 'Éxito', description: 'Almacén actualizado correctamente.' });
    } else {
      await warehousesApi.create(form);
      toast({ title: 'Éxito', description: 'Almacén creado correctamente.' });
    }
    fetchWarehouses();
    isDialogOpen.value = false;
  } catch (error) {
    console.error('Error saving warehouse:', error);
  }
}

async function fetchTypes() {
  try {
    const response = await warehousesApi.types();
    types.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching warehouse types:', error);
  }
}

onMounted(() => {
  fetchWarehouses();
  fetchTypes();
});
</script>

<template>
  <div class="h-[calc(100vh-120px)] flex flex-col">
    <DataTable
      :columns="columns"
      :data="warehouses"
      :actions="actions"
      :is-loading="isLoading"
      :search-value="searchQuery"
      empty-message="No hay almacenes registrados."
      :empty-icon="Warehouse"
      class="flex-1 min-h-0"
      @search="search"
      @action="handleAction"
    >
      <template #toolbar-end>
        <Button @click="openCreateDialog">
          <Plus class="mr-2 h-4 w-4" />
          Nuevo Almacén
        </Button>
      </template>

      <template #cell-store="{ row }">
        {{ row.store?.name || '-' }}
      </template>

      <template #cell-branch="{ row }">
        {{ row.branch?.name || '-' }}
      </template>

      <template #cell-is_active="{ row }">
        <span
          class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
          :class="row.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
        >
          {{ row.is_active ? 'Activo' : 'Inactivo' }}
        </span>
      </template>
    </DataTable>

    <Dialog :open="isDialogOpen" @update:open="isDialogOpen = $event">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{{ isEditing ? 'Editar Almacén' : 'Nuevo Almacén' }}</DialogTitle>
          <DialogDescription>
            Completa los detalles del almacén para comenzar a gestionar el stock.
          </DialogDescription>
        </DialogHeader>
        <div class="grid gap-4 py-4">
          <div class="grid gap-2">
            <Label htmlFor="name">Nombre <span class="text-destructive">*</span></Label>
            <Input id="name" v-model="form.name" placeholder="Ej. Almacén Central" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label htmlFor="code">Código</Label>
              <Input id="code" v-model="form.code" placeholder="AL-001" />
            </div>
            <div class="grid gap-2">
              <Label htmlFor="type">Tipo <span class="text-destructive">*</span></Label>
              <Select
                :model-value="form.type"
                @update:model-value="(val: any) => (form.type = val)"
              >
                <SelectTrigger>
                  <SelectValue placeholder="Seleccionar tipo" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="t in types" :key="t.id" :value="t.id">
                    {{ t.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <div class="flex items-center space-x-2">
            <Checkbox id="is_active" :checked="form.is_active" @update:checked="form.is_active = $event" />
            <Label htmlFor="is_active">Activo</Label>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="isDialogOpen = false">Cancelar</Button>
          <Button @click="handleSubmit">Guardar</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <ConfirmDialog
      v-model:open="deleteDialogOpen"
      title="¿Eliminar almacén?"
      description="Esta acción eliminará el almacén de forma permanente. ¿Estás seguro?"
      :loading="isDeleting"
      @confirm="confirmDelete"
    />
  </div>
</template>
