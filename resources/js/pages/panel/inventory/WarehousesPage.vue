<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue';
import axios from 'axios';

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
import { Label } from '@/components/ui/label';

import { Plus, Warehouse } from 'lucide-vue-next';
import { useToast } from '@/components/ui/toast/use-toast';
import { Checkbox } from '@/components/ui/checkbox';
import { SearchableSelect } from '@/components/ui/searchable-select';

interface Store {
  id: string;
  name: string;
}

interface Branch {
  id: string;
  name: string;
}

interface WarehouseItem {
  id: string;
  name: string;
  code: string;
  type: string;
  is_active: boolean;
  store?: Store;
  branch?: Branch;
  created_at: string;
}

const { toast } = useToast();
const warehouses = ref<WarehouseItem[]>([]);
const isLoading = ref(true);
const searchQuery = ref('');
const isDialogOpen = ref(false);
const isEditing = ref(false);
const currentId = ref<string | null>(null);

const form = reactive({
  name: '',
  code: '',
  type: 'central',
  store_id: '',
  branch_id: '',
  is_active: true
});

const types = ref<{id: string, name: string}[]>([]);

const columns = computed<Column[]>(() => {
  const badgeVariants: Record<string, { label: string; class: string }> = {};
  const classes: Record<string, string> = {
    'central': 'bg-blue-100 text-blue-800',
    'branch': 'bg-purple-100 text-purple-800',
    'distribution': 'bg-orange-100 text-orange-800',
  };

  types.value.forEach(t => {
    badgeVariants[t.id] = {
      label: t.name,
      class: classes[t.id] || 'bg-gray-100 text-gray-800'
    };
  });

  return [
    { 
      key: 'name', 
      label: 'Nombre', 
      sortable: true,
      type: 'text'
    },
    { 
      key: 'code', 
      label: 'Código', 
      type: 'text' 
    },
    {
      key: 'type',
      label: 'Tipo',
      type: 'badge',
      badgeVariants
    },
    {
      key: 'store',
      label: 'Tienda',
      type: 'custom'
    },
    {
      key: 'branch',
      label: 'Sucursal',
      type: 'custom'
    },
    {
      key: 'is_active',
      label: 'Estado',
      type: 'custom'
    }
  ];
});

const actions: Action[] = [
  { key: 'edit', label: 'Editar' },
  { key: 'delete', label: 'Eliminar', variant: 'destructive' }
];

async function fetchWarehouses() {
  isLoading.value = true;
  try {
    const token = localStorage.getItem('auth_token');
    const response = await axios.get('/api/admin/warehouses', {
      headers: { Authorization: `Bearer ${token}` }
    });
    warehouses.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching warehouses:', error);
    toast({
      title: 'Error',
      description: 'No se pudieron cargar los almacenes.',
      variant: 'destructive',
    });
  } finally {
    isLoading.value = false;
  }
}

async function fetchTypes() {
  try {
    const token = localStorage.getItem('auth_token');
    const response = await axios.get('/api/admin/warehouses/types', {
      headers: { Authorization: `Bearer ${token}` }
    });
    types.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching types:', error);
  }
}

function handleSearch(value: string) {
  searchQuery.value = value;
  // Filter locally or implement server-side search
}

function handleAction(actionKey: string, row: WarehouseItem) {
  if (actionKey === 'edit') {
    openEditDialog(row);
  } else if (actionKey === 'delete') {
    handleDelete(row.id);
  }
}

function openCreateDialog() {
  isEditing.value = false;
  currentId.value = null;
  form.name = '';
  form.code = '';
  form.type = 'storage';
  form.store_id = '';
  form.branch_id = '';
  form.is_active = true;
  isDialogOpen.value = true;
}

function openEditDialog(warehouse: WarehouseItem) {
  isEditing.value = true;
  currentId.value = warehouse.id;
  form.name = warehouse.name;
  form.code = warehouse.code || '';
  form.type = warehouse.type;
  form.store_id = warehouse.store?.id || '';
  form.branch_id = warehouse.branch?.id || '';
  form.is_active = warehouse.is_active;
  isDialogOpen.value = true;
}

async function handleSubmit() {
  const token = localStorage.getItem('auth_token');
  
  const payload = {
    name: form.name,
    code: form.code || null,
    type: form.type,
    store_id: form.store_id || null,
    branch_id: form.branch_id || null,
    is_active: form.is_active
  };
  
  try {
    if (isEditing.value && currentId.value) {
      await axios.put(`/api/admin/warehouses/${currentId.value}`, payload, {
        headers: { Authorization: `Bearer ${token}` }
      });
      toast({ title: 'Éxito', description: 'Almacén actualizado correctamente.' });
    } else {
      await axios.post('/api/admin/warehouses', payload, {
        headers: { Authorization: `Bearer ${token}` }
      });
      toast({ title: 'Éxito', description: 'Almacén creado correctamente.' });
    }
    isDialogOpen.value = false;
    fetchWarehouses();
  } catch (error) {
    console.error('Error saving warehouse:', error);
    toast({
      title: 'Error',
      description: 'Hubo un error al guardar el almacén.',
      variant: 'destructive',
    });
  }
}

async function handleDelete(id: string) {
  if (!confirm('¿Estás seguro de eliminar este almacén?')) return;

  const token = localStorage.getItem('auth_token');
  try {
    await axios.delete(`/api/admin/warehouses/${id}`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    toast({ title: 'Éxito', description: 'Almacén eliminado.' });
    fetchWarehouses();
  } catch (error) {
    console.error('Error deleting warehouse:', error);
    toast({
      title: 'Error',
      description: 'No se pudo eliminar el almacén.',
      variant: 'destructive',
    });
  }
}

onMounted(function() {
  fetchWarehouses();
  fetchTypes();
});
</script>

<template>
  <div class="h-[calc(100vh-120px)] flex flex-col">
    <div class="flex flex-col gap-6 h-full">
      <!-- DataTable with full height -->
      <DataTable
        :columns="columns"
        :data="warehouses"
        :actions="actions"
        :is-loading="isLoading"
        :search-value="searchQuery"
        search-placeholder="Buscar almacenes..."
        empty-message="No hay almacenes registrados."
        :empty-icon="Warehouse"
        class="flex-1 min-h-0"
        @search="handleSearch"
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
            :class="row.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
          >
            {{ row.is_active ? 'Activo' : 'Inactivo' }}
          </span>
        </template>
      </DataTable>

      <!-- Dialog Create/Edit -->
      <Dialog v-model:open="isDialogOpen">
        <DialogContent class="sm:max-w-[500px]">
          <DialogHeader>
            <DialogTitle>{{ isEditing ? 'Editar Almacén' : 'Nuevo Almacén' }}</DialogTitle>
            <DialogDescription>
              Completa los detalles del almacén aquí.
            </DialogDescription>
          </DialogHeader>
          <div class="grid gap-4 py-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="grid gap-2">
                <Label htmlFor="name">Nombre <span class="text-destructive">*</span></Label>
                <Input id="name" v-model="form.name" />
              </div>
              <div class="grid gap-2">
                <Label htmlFor="code">Código</Label>
                <Input id="code" v-model="form.code" placeholder="ALM-001" />
              </div>
            </div>
            
            <div class="grid gap-2">
              <Label htmlFor="type">Tipo <span class="text-destructive">*</span></Label>
                <SearchableSelect
                  v-model="form.type"
                  endpoint="/api/admin/warehouses/types"
                  label-key="name"
                  value-key="id"
                  placeholder="Buscar tipo..."
                />
            </div>

            <div class="grid gap-2">
              <Label htmlFor="branch_id">Sucursal</Label>
              <SearchableSelect
                v-model="form.branch_id"
                endpoint="/api/admin/branches"
                label-key="name"
                value-key="id"
                placeholder="Buscar sucursal..."
              />
            </div>
            <div class="grid gap-2">
              <Label htmlFor="store_id">Tienda</Label>
              <SearchableSelect
                v-model="form.store_id"
                endpoint="/api/admin/stores"
                label-key="name"
                value-key="id"
                placeholder="Buscar tienda (opcional)..."
              />
              <p class="text-[0.8rem] text-muted-foreground">
                Dejar vacío para que sea un almacén compartido por todas las tiendas de la sucursal.
              </p>
            </div>

            <div class="flex items-center space-x-2">
              <Checkbox id="is_active" :checked="form.is_active" @update:checked="(val) => form.is_active = val" />
              <Label htmlFor="is_active">Almacén activo</Label>
            </div>
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" @click="isDialogOpen = false">Cancelar</Button>
            <Button type="submit" @click="handleSubmit">Guardar</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </div>
</template>
