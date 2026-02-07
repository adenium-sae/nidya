<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue';
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
import { Plus, FolderTree } from 'lucide-vue-next';
import { useToast } from '@/components/ui/toast/use-toast';

interface Category {
  id: string;
  name: string;
  description?: string;
  slug: string;
  is_active: boolean;
  created_at: string;
}

const { toast } = useToast();
const categories = ref<Category[]>([]);
const isLoading = ref(true);
const searchQuery = ref('');
const isDialogOpen = ref(false);
const isEditing = ref(false);
const currentId = ref<string | null>(null);

const form = reactive({
  name: '',
  description: ''
});

// DataTable Configuration
const columns: Column[] = [
  { 
    key: 'name', 
    label: 'Nombre', 
    sortable: true,
    type: 'text'
  },
  { 
    key: 'description', 
    label: 'Descripción', 
    type: 'text' 
  },
  {
    key: 'created_at',
    label: 'Fecha Creación',
    type: 'date'
  }
];

const actions: Action[] = [
  { key: 'edit', label: 'Editar' },
  { key: 'delete', label: 'Eliminar', variant: 'destructive' }
];

async function fetchCategories() {
  isLoading.value = true;
  try {
    const token = localStorage.getItem('auth_token');
    const response = await axios.get('/api/admin/categories', {
      headers: { Authorization: `Bearer ${token}` },
      params: { search: searchQuery.value }
    });
    categories.value = response.data;
  } catch (error) {
    console.error('Error fetching categories:', error);
    toast({
      title: 'Error',
      description: 'No se pudieron cargar las categorías.',
      variant: 'destructive',
    });
  } finally {
    isLoading.value = false;
  }
}

function handleSearch(value: string) {
  searchQuery.value = value;
  fetchCategories();
}

function handleAction(actionKey: string, row: Category) {
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
  form.description = '';
  isDialogOpen.value = true;
}

function openEditDialog(category: Category) {
  isEditing.value = true;
  currentId.value = category.id;
  form.name = category.name;
  form.description = category.description || '';
  isDialogOpen.value = true;
}

async function handleSubmit() {
  const token = localStorage.getItem('auth_token');
  try {
    if (isEditing.value && currentId.value) {
      await axios.put(`/api/admin/categories/${currentId.value}`, form, {
        headers: { Authorization: `Bearer ${token}` }
      });
      toast({ title: 'Éxito', description: 'Categoría actualizada correctamente.' });
    } else {
      await axios.post('/api/admin/categories', form, {
        headers: { Authorization: `Bearer ${token}` }
      });
      toast({ title: 'Éxito', description: 'Categoría creada correctamente.' });
    }
    isDialogOpen.value = false;
    fetchCategories();
  } catch (error) {
    console.error('Error saving category:', error);
    toast({
      title: 'Error',
      description: 'Hubo un error al guardar la categoría.',
      variant: 'destructive',
    });
  }
}

async function handleDelete(id: string) {
  if (!confirm('¿Estás seguro de eliminar esta categoría?')) return;

  const token = localStorage.getItem('auth_token');
  try {
    await axios.delete(`/api/admin/categories/${id}`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    toast({ title: 'Éxito', description: 'Categoría eliminada.' });
    fetchCategories();
  } catch (error) {
    console.error('Error deleting category:', error);
    toast({
      title: 'Error',
      description: 'No se pudo eliminar la categoría.',
      variant: 'destructive',
    });
  }
}

onMounted(function() {
  fetchCategories();
});
</script>

<template>
  <div class="h-[calc(100vh-120px)] flex flex-col">
    <div class="flex flex-col gap-6 h-full">
      <!-- DataTable with full height -->
      <DataTable
        :columns="columns"
        :data="categories"
        :actions="actions"
        :is-loading="isLoading"
        :search-value="searchQuery"
        search-placeholder="Buscar categorías..."
        empty-message="No hay categorías registradas."
        :empty-icon="FolderTree"
        class="flex-1 min-h-0"
        @search="handleSearch"
        @action="handleAction"
      >
        <template #toolbar-end>
          <Button @click="openCreateDialog">
            <Plus class="mr-2 h-4 w-4" />
            Nueva Categoría
          </Button>
        </template>
      </DataTable>

      <!-- Dialog Create/Edit -->
      <Dialog v-model:open="isDialogOpen">
        <DialogContent class="sm:max-w-[425px]">
          <DialogHeader>
            <DialogTitle>{{ isEditing ? 'Editar Categoría' : 'Nueva Categoría' }}</DialogTitle>
            <DialogDescription>
              Completa los detalles de la categoría aquí.
            </DialogDescription>
          </DialogHeader>
          <div class="grid gap-4 py-4">
            <div class="grid gap-2">
              <Label htmlFor="name">Nombre <span class="text-destructive">*</span></Label>
              <Input id="name" v-model="form.name" />
            </div>
            <div class="grid gap-2">
              <Label htmlFor="description">Descripción</Label>
              <Input id="description" v-model="form.description" />
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
