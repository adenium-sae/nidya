<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { categoriesApi } from '@/api/categories.api';
import { useApiList } from '@/composables/useApiList';
import { useConfirmDelete } from '@/composables/useConfirmDelete';
import { DataTable, type Column, type Action } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Package, Plus } from 'lucide-vue-next';
import { useToast } from '@/components/ui/toast/use-toast';
import CategoryFormDialog from '@/components/inventory/CategoryFormDialog.vue';
import ConfirmDialog from '@/components/app/ConfirmDialog.vue';
import type { Category } from '@/types/models';

const { t } = useI18n();
const { toast } = useToast();

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
} = useApiList<Category>(categoriesApi.list);

const {
  isOpen: deleteDialogOpen,
  isDeleting,
  itemToDelete,
  openDialog: openDeleteDialog,
  closeDialog: closeDeleteDialog,
  confirmDelete,
} = useConfirmDelete(categoriesApi.destroy, {
  successMessage: () => t('categories.deleted'),
  onSuccess: (item: any) => removeItem(item.id),
});

const formDialogOpen = ref(false);
const editingCategory = ref<Category | null>(null);

const columns = computed<Column[]>(() => [
  { key: 'name', label: t('common.name'), type: 'text', sortable: true },
  { key: 'description', label: t('common.description'), type: 'text' },
  { key: 'is_active', label: t('common.status'), type: 'boolean' },
]);

const actions = computed<Action[]>(() => [
  { key: 'edit', label: t('common.edit') },
  { key: 'delete', label: t('common.delete'), variant: 'destructive' },
]);

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
      :search-placeholder="t('categories.search')"
      :empty-message="t('categories.empty')"
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
          {{ t('categories.new') }}
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
      :title="t('categories.delete_confirm')"
      :description="t('categories.delete_desc', { name: itemToDelete?.name })"
      :loading="isDeleting"
      @confirm="confirmDelete"
    />
  </div>
</template>
