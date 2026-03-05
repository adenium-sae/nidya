<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { productsApi } from '@/api/products.api';
import { categoriesApi } from '@/api/categories.api';
import { useApiList } from '@/composables/useApiList';
import { useConfirmDelete } from '@/composables/useConfirmDelete';
import { useFormatters } from '@/composables/useFormatters';
import { DataTable, type Column, type Action, type Filter } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Plus, Package } from 'lucide-vue-next';
import ConfirmDialog from '@/components/app/ConfirmDialog.vue';
import type { Category, Product } from '@/types/models';

const router = useRouter();
const { t } = useI18n();
const { formatCurrency } = useFormatters();

const categories = ref<Category[]>([]);

// List composable
const {
  items: products,
  isLoading,
  searchQuery,
  pagination,
  filterValues,
  fetch: fetchProducts,
  search,
  filter,
  changePage,
  changePerPage,
  removeItem,
} = useApiList<Product>(productsApi.list);

// Delete composable
const {
  isOpen: deleteDialogOpen,
  isDeleting,
  itemToDelete,
  openDialog: _openDeleteDialog,
  confirmDelete,
} = useConfirmDelete(productsApi.destroy, {
  successMessage: () => t('products.deleted'),
  onSuccess: (item: any) => removeItem(item.id),
});

function openDeleteDialog(item: Product) {
  _openDeleteDialog(item);
}

function getImageUrl(path: string | null) {
  if (!path) return '';
  if (path.startsWith('http')) return path;
  const backendUrl = import.meta.env.VITE_BACKEND_URL || '';
  const baseUrl = backendUrl.endsWith('/') ? backendUrl.slice(0, -1) : backendUrl;
  const fullPath = path.startsWith('/storage') ? path : `/storage/${path.replace(/^\//, '')}`;
  return `${baseUrl}${fullPath}`;
}

const columns = computed<Column[]>(() => [
  { key: 'image_url', label: '', type: 'custom', width: 'w-[60px]' },
  { key: 'name', label: t('inventory.product'), type: 'custom', sortable: true },
  { key: 'sku', label: t('inventory.sku'), type: 'text' },
  { key: 'category', label: t('inventory.category'), type: 'custom' },
  { key: 'cost', label: t('inventory.cost'), type: 'custom', align: 'right' },
  {
    key: 'is_active',
    label: t('common.status'),
    type: 'badge',
    badgeVariants: {
      true: { label: t('common.active'), class: 'bg-green-100 text-green-800' },
      false: { label: t('common.inactive'), class: 'bg-gray-100 text-gray-600' },
    },
  },
]);

const filters = computed<Filter[]>(() => [
  {
    key: 'category_id',
    label: t('inventory.category'),
    type: 'select',
    options: categories.value.map(c => ({ value: String(c.id), label: c.name })),
  },
]);

const actions = computed<Action[]>(() => [
  { key: 'edit', label: t('common.edit') },
  { key: 'delete', label: t('common.delete'), variant: 'destructive' },
]);

function handleAction(actionKey: string, row: any) {
  if (actionKey === 'edit') {
    router.push(`/panel/inventory/products/${row.id}/edit`);
  } else if (actionKey === 'delete') {
    openDeleteDialog(row);
  }
}

async function fetchCategories() {
  try {
    const response = await categoriesApi.list();
    categories.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching categories:', error);
  }
}

onMounted(() => {
  fetchProducts();
  fetchCategories();
});
</script>

<template>
  <div class="h-[calc(100vh-120px)] flex flex-col">
    <DataTable
      :columns="columns"
      :data="products"
      :actions="actions"
      :is-loading="isLoading"
      :search-value="searchQuery"
      :filters="filters"
      :filter-values="filterValues"
      :pagination="pagination"
      :search-placeholder="t('products.search')"
      :empty-message="t('products.empty')"
      :empty-icon="Package"
      class="flex-1 min-h-0"
      @search="search"
      @filter="filter"
      @page-change="changePage"
      @per-page-change="changePerPage"
      @action="handleAction"
    >
      <template #toolbar-end>
        <Button @click="router.push('/panel/inventory/products/create')">
          <Plus class="mr-2 h-4 w-4" />
          {{ t('inventory.new_product') }}
        </Button>
      </template>

      <template #cell-image_url="{ row }">
        <div class="h-10 w-10 overflow-hidden rounded-md border bg-muted">
          <img
            v-if="row.image_url"
            :src="getImageUrl(row.image_url)"
            :alt="row.name"
            class="h-full w-full object-cover"
          />
          <div v-else class="flex h-full w-full items-center justify-center">
            <Package class="h-5 w-5 text-muted-foreground/50" />
          </div>
        </div>
      </template>

      <template #cell-name="{ row }">
        <div class="flex flex-col">
          <span class="font-medium line-clamp-1">{{ row.name }}</span>
          <span class="text-xs text-muted-foreground line-clamp-1">{{ row.description }}</span>
        </div>
      </template>

      <template #cell-category="{ row }">
        {{ row.category?.name || '-' }}
      </template>

      <template #cell-cost="{ row }">
        {{ formatCurrency(row.cost) }}
      </template>
    </DataTable>

    <ConfirmDialog
      v-model:open="deleteDialogOpen"
      :title="t('products.delete_confirm')"
      :description="t('products.delete_desc')"
      :loading="isDeleting"
      @confirm="confirmDelete"
    />
  </div>
</template>
