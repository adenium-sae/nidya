<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue';
import { branchesApi } from '@/api/branches.api';
import { useApiList } from '@/composables/useApiList';
import { useConfirmDelete } from '@/composables/useConfirmDelete';
import { DataTable, type Column, type Action } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/searchable-select';
import { Plus, Building2, Wand2 } from 'lucide-vue-next';
import { useToast } from '@/components/ui/toast/use-toast';
import { Checkbox } from '@/components/ui/checkbox';
import ConfirmDialog from '@/components/app/ConfirmDialog.vue';
import type { Branch as BranchModel } from '@/types/models';

const { toast } = useToast();

const {
  items: branches,
  isLoading,
  searchQuery,
  fetch: fetchBranches,
  search,
  removeItem,
} = useApiList<BranchModel>(branchesApi.list);

const {
  isOpen: deleteDialogOpen,
  isDeleting,
  itemToDelete,
  openDialog: _openDeleteDialog,
  confirmDelete,
} = useConfirmDelete(branchesApi.destroy, {
  successMessage: 'Sucursal eliminada correctamente.',
  onSuccess: (item: any) => removeItem(item.id),
});

const isDialogOpen = ref(false);
const isEditing = ref(false);
const currentId = ref<string | null>(null);

const form = reactive({
  name: '',
  code: '',
  store_ids: [] as { id: string; name: string }[],
  email: '',
  phone: '',
  allow_sales: true,
  allow_inventory: true,
  is_active: true,
});

const errors = ref<Record<string, string[]>>({});

const columns = computed<Column[]>(() => [
  { key: 'name', label: 'Nombre', sortable: true, type: 'text' },
  { key: 'code', label: 'Código', type: 'text' },
  { key: 'store_name', label: 'Tienda', type: 'custom' },
  { key: 'email', label: 'Correo', type: 'text' },
  { key: 'phone', label: 'Teléfono', type: 'text' },
  { key: 'permissions', label: 'Permisos', type: 'custom' },
  { key: 'is_active', label: 'Estado', type: 'custom' },
]);

const actions: Action[] = [
  { key: 'edit', label: 'Editar' },
  { key: 'delete', label: 'Eliminar', variant: 'destructive' },
];

function generateCode() {
  const prefix = 'SUC-';
  const randomStr = Math.random().toString(36).substring(2, 6).toUpperCase();
  form.code = `${prefix}${randomStr}`;
}

function handleAction(actionKey: string, row: any) {
  if (actionKey === 'edit') {
    openEditDialog(row);
  } else if (actionKey === 'delete') {
    openDeleteDialog(row);
  }
}

function openDeleteDialog(item: BranchModel) {
  _openDeleteDialog(item);
}

function openCreateDialog() {
  isEditing.value = false;
  currentId.value = null;
  errors.value = {};
  Object.assign(form, { name: '', code: '', store_ids: [], email: '', phone: '', allow_sales: true, allow_inventory: true, is_active: true });
  isDialogOpen.value = true;
}

function openEditDialog(branch: BranchModel) {
  isEditing.value = true;
  currentId.value = branch.id;
  errors.value = {};
  Object.assign(form, {
    name: branch.name,
    code: branch.code || '',
    store_ids: branch.stores?.map((s: any) => ({ id: s.id, name: s.name })) || [],
    email: branch.email || '',
    phone: branch.phone || '',
    allow_sales: !!branch.allow_sales,
    allow_inventory: !!branch.allow_inventory,
    is_active: branch.is_active === undefined ? true : !!branch.is_active,
  });
  isDialogOpen.value = true;
}

async function handleSubmit() {
  errors.value = {};
  try {
    const payload = { ...form, store_ids: form.store_ids.map(s => s.id) };
    if (isEditing.value && currentId.value) {
      await branchesApi.update(currentId.value, payload);
      toast({ title: 'Éxito', description: 'Sucursal actualizada correctamente.' });
    } else {
      await branchesApi.create(payload);
      toast({ title: 'Éxito', description: 'Sucursal creada correctamente.' });
    }
    fetchBranches();
    isDialogOpen.value = false;
  } catch (error: any) {
    if (error.response?.status === 422 && error.response.data.errors) {
      errors.value = error.response.data.errors;
      toast({ variant: 'destructive', title: 'Error de validación', description: 'Revisa los campos marcados en el formulario.' });
    } else {
      console.error('Error saving branch:', error);
      toast({ variant: 'destructive', title: 'Error', description: 'Ocurrió un problema inesperado.' });
    }
  }
}

onMounted(() => {
  fetchBranches();
});
</script>

<template>
  <div class="h-[calc(100vh-120px)] flex flex-col">
    <DataTable
      :columns="columns"
      :data="branches"
      :actions="actions"
      :is-loading="isLoading"
      :search-value="searchQuery"
      empty-message="No hay sucursales registradas."
      :empty-icon="Building2"
      class="flex-1 min-h-0"
      @search="search"
      @action="handleAction"
    >
      <template #toolbar-end>
        <Button @click="openCreateDialog">
          <Plus class="mr-2 h-4 w-4" />
          Nueva Sucursal
        </Button>
      </template>

      <template #cell-store_name="{ row }">
        <div class="flex flex-wrap gap-1 max-w-[200px]">
          <span 
            v-for="store in (row.stores || [])" 
            :key="store.id"
            class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 truncate max-w-full"
            :title="store.name"
          >
            {{ store.name }}
          </span>
          <span v-if="!row.stores || row.stores.length === 0" class="text-muted-foreground">-</span>
        </div>
      </template>

      <template #cell-permissions="{ row }">
        <div class="flex gap-1">
          <span v-if="row.allow_sales" class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700" title="Ventas">Ventas</span>
          <span v-if="row.allow_inventory" class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs text-indigo-700" title="Inventario">Inventario</span>
        </div>
      </template>

      <template #cell-is_active="{ row }">
        <span
          class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
          :class="row.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
        >
          {{ row.is_active ? 'Activa' : 'Inactiva' }}
        </span>
      </template>
    </DataTable>

    <Dialog :open="isDialogOpen" @update:open="isDialogOpen = $event">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{{ isEditing ? 'Editar Sucursal' : 'Nueva Sucursal' }}</DialogTitle>
          <DialogDescription>
            Completa los detalles de la sucursal y asígnala a una tienda.
          </DialogDescription>
        </DialogHeader>
        <div class="grid gap-4 py-4">
          <div class="grid gap-2">
            <Label htmlFor="name" :class="{'text-destructive': errors?.name}">Nombre <span class="text-destructive">*</span></Label>
            <Input id="name" v-model="form.name" placeholder="Ej. Sucursal Centro" :class="{'border-destructive': errors?.name}" />
            <span v-if="errors?.name" class="text-xs text-destructive">{{ errors.name[0] }}</span>
          </div>

          <div class="grid gap-2 rounded-lg border bg-muted/30 p-4" :class="{'border-destructive/50 bg-destructive/5': errors?.store_ids}">
            <Label class="text-base font-semibold" :class="{'text-destructive': errors?.store_ids}">Tiendas Asignadas <span class="text-destructive">*</span></Label>
            <p class="text-xs text-muted-foreground mb-1" :class="{'text-destructive/80': errors?.store_ids}">
              Selecciona las tiendas que operarán en esta sucursal. Se creará automáticamente un almacén consolidado.
            </p>
            <SearchableSelect
              :model-value="null"
              @select="(option: any) => {
                if (option && option.value && !form.store_ids.some(s => s.id === option.value)) {
                  form.store_ids.push({ id: option.value, name: option.label })
                }
              }"
              endpoint="/admin/stores"
              label-key="name"
              value-key="id"
              placeholder="Buscar y añadir tienda..."
            />
            <div class="flex flex-wrap gap-2 mt-2" v-if="form.store_ids.length > 0">
               <div 
                  v-for="(store, index) in form.store_ids" 
                  :key="store.id" 
                  class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10"
               >
                 <span>{{ store.name }}</span>
                 <button type="button" class="ml-1 inline-flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-full text-blue-600 hover:bg-blue-200 hover:text-blue-900 focus:bg-blue-500 focus:text-white transition-colors" @click="form.store_ids.splice(index, 1)">
                   <span class="sr-only">Eliminar tienda</span>
                   <svg class="h-2 w-2" stroke="currentColor" fill="none" viewBox="0 0 8 8"><path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 7" /></svg>
                 </button>
               </div>
            </div>
            <span v-if="errors?.store_ids" class="text-xs text-destructive mt-1">{{ errors.store_ids[0] }}</span>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label htmlFor="code" :class="{'text-destructive': errors?.code}">Código</Label>
              <div class="flex gap-2">
                <Input id="code" v-model="form.code" placeholder="SUC-01" class="flex-1" :class="{'border-destructive': errors?.code}" />
                <Button type="button" variant="outline" size="icon" @click="generateCode" title="Generar código">
                  <Wand2 class="h-4 w-4" />
                </Button>
              </div>
              <span v-if="errors?.code" class="text-xs text-destructive">{{ errors.code[0] }}</span>
            </div>
            <div class="grid gap-2">
              <Label htmlFor="phone" :class="{'text-destructive': errors?.phone}">Teléfono (Opcional)</Label>
              <Input id="phone" v-model="form.phone" placeholder="555..." :class="{'border-destructive': errors?.phone}" />
              <span v-if="errors?.phone" class="text-xs text-destructive">{{ errors.phone[0] }}</span>
            </div>
          </div>
          
          <div class="grid gap-2">
            <Label htmlFor="email" :class="{'text-destructive': errors?.email}">Correo (Opcional)</Label>
            <Input id="email" type="email" v-model="form.email" placeholder="correo@ejemplo.com" :class="{'border-destructive': errors?.email}" />
            <span v-if="errors?.email" class="text-xs text-destructive">{{ errors.email[0] }}</span>
          </div>
          <div class="space-y-3 mt-2 border rounded-md p-3 bg-muted/20">
            <Label class="text-sm border-b pb-2 block mb-3">Permisos de Operación</Label>
            <div class="flex items-center justify-between">
              <Label htmlFor="allow_sales" class="font-normal cursor-pointer flex-1">
                <span class="block">Permitir Ventas</span>
                <span class="text-xs text-muted-foreground font-normal">Puede registrar cotizaciones, ventas POS y cobros.</span>
              </Label>
              <Checkbox id="allow_sales" :checked="form.allow_sales" @update:checked="(val: boolean) => (form.allow_sales = val)" />
            </div>
            <div class="flex items-center justify-between">
              <Label htmlFor="allow_inventory" class="font-normal cursor-pointer flex-1">
                <span class="block">Permitir Inventario</span>
                <span class="text-xs text-muted-foreground font-normal">Puede autorizar traspasos y ajustes en almacenes.</span>
              </Label>
              <Checkbox id="allow_inventory" :checked="form.allow_inventory" @update:checked="(val: boolean) => (form.allow_inventory = val)" />
            </div>
          </div>
          <div class="flex items-center space-x-2 mt-2">
            <Checkbox
              id="is_active"
              :checked="form.is_active"
              @update:checked="(val: boolean) => (form.is_active = val)"
            />
            <Label htmlFor="is_active" class="text-sm font-normal">
              Sucursal activa en el sistema
            </Label>
          </div>
        </div>
        <div class="flex justify-end gap-2">
          <Button variant="outline" @click="isDialogOpen = false">Cancelar</Button>
          <Button @click="handleSubmit">
            {{ isEditing ? 'Guardar Cambios' : 'Crear Sucursal' }}
          </Button>
        </div>
      </DialogContent>
    </Dialog>

    <ConfirmDialog
      :open="deleteDialogOpen"
      title="¿Eliminar sucursal?"
      description="¿Estás seguro de que deseas eliminar esta sucursal? Esta acción no se puede deshacer."
      :loading="isDeleting"
      @confirm="confirmDelete"
      @update:open="deleteDialogOpen = $event"
    />
  </div>
</template>
