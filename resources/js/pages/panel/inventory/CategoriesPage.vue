<script setup lang="ts">
import { onMounted, computed } from 'vue';
import { categoriesApi } from '@/api/categories.api';
import { useApiList } from '@/composables/useApiList';
import { useConfirmDelete } from '@/composables/useConfirmDelete';
import { DataTable, type Column, type Action } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Package, Plus } from 'lucide-vue-next';
import { useToast } from '@/components/ui/toast/use-toast';
import CategoryFormDialog from '@/components/inventory/CategoryFormDialog.vue';
import ConfirmDialog from '@/components/app/ConfirmDialog.vue';
import { ref } from 'vue';
import type { Category } from '@/types/models';

const { toast } = useToast();

// List composable
const {
  items: categories,
  isLoading,
  searchQuery,
  pagination,
  fetch: fetchCategories,
  search,
  changePage,
  changePerPage,
  removeItem,
} = useApiList(categoriesApi.list);

// Delete composable
const {
  isOpen: deleteDialogOpen,
  isDeleting,
  itemToDelete,
  openDialog: openDeleteDialog,
  closeDialog: closeDeleteDialog,
  confirmDelete,
} = useConfirmDelete(categoriesApi.destroy, {
  successMessage: 'Categoría eliminada correctamente.',
  onSuccess: (item: any) => removeItem(item.id),
});

// Form dialog state
const formDialogOpen = ref(false);
const editingCategory = ref<Category | null>(null);

const columns: Column[] = [
  { key: 'name', label: 'Nombre', type: 'text', sortable: true },
  { key: 'description', label: 'Descripción', type: 'text' },
  { key: 'is_active', label: 'Estado', type: 'boolean' },
];

const actions: Action[] = [
  { key: 'edit', label: 'Editar' },
  { key: 'delete', label: 'Eliminar', variant: 'destructive' },
];

function handleAction(action: string, row: any) {
  if (action === 'edit') {
    editingCategory.value = row;
    formDialogOpen.value = true;
  } else if (action === 'delete') {
    openDeleteDialog(row);
  }
}

function openCreateDialog() {
  editingCategory.value = null;
  formDialogOpen.value = true;
}

function handleSaved() {
  fetchCategories();
}

onMounted(() => fetchCategories());
</script>

<template>
  <div class="h-[calc(100vh-120px)] flex flex-col">
    <DataTable
      :columns="columns"
      :data="categories"
      :actions="actions"
      :is-loading="isLoading"
      :search-value="searchQuery"
      :pagination="pagination"
      search-placeholder="Buscar categorías..."
      empty-message="No hay categorías registradas."
      :empty-icon="Package"
      class="flex-1 min-h-0"
      @search="search"
      @page-change="changePage"
      @per-page-change="changePerPage"
      @action="handleAction"
    >
      <template #toolbar-end>
        <Button @click="openCreateDialog">
          <Plus class="mr-2 h-4 w-4" />
          Nueva Categoría
        </Button>
      </template>
    </DataTable>

    <CategoryFormDialog
      v-model:open="formDialogOpen"
      :category="editingCategory"
      @saved="handleSaved"
    />

    <ConfirmDialog
      v-model:open="deleteDialogOpen"
      title="¿Eliminar categoría?"
      :description="`Se eliminará la categoría '${itemToDelete?.name}'. Esta acción no se puede deshacer.`"
      :loading="isDeleting"
      @confirm="confirmDelete"
    />
  </div>
</template>
