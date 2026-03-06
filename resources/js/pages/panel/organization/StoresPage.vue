<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { storesApi } from '@/api/stores.api';
import { useApiList } from '@/composables/useApiList';
import { useConfirmDelete } from '@/composables/useConfirmDelete';
import { DataTable, type Column, type Action } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Plus, Store } from 'lucide-vue-next';
import { useToast } from '@/components/ui/toast/use-toast';
import { Checkbox } from '@/components/ui/checkbox';
import ConfirmDialog from '@/components/app/ConfirmDialog.vue';
import type { Store as StoreModel } from '@/types/models';

const { t } = useI18n();
const { toast } = useToast();

const {
  items: stores,
  isLoading,
  searchQuery,
  fetch: fetchStores,
  search,
  removeItem,
} = useApiList<StoreModel>(storesApi.list);

const {
  isOpen: deleteDialogOpen,
  isDeleting,
  itemToDelete,
  openDialog: _openDeleteDialog,
  confirmDelete,
} = useConfirmDelete(storesApi.destroy, {
  successMessage: () => t('stores.deleted'),
  onSuccess: (item: any) => removeItem(item.id),
});

const isDialogOpen = ref(false);
const isEditing = ref(false);
const currentId = ref<string | null>(null);

const form = reactive({
  name: '',
  slug: '',
  description: '',
  primary_color: '#0ea5e9',
  is_active: true,
});

const columns = computed<Column[]>(() => [
  { key: 'name', label: t('common.name'), sortable: true, type: 'text' },
  { key: 'description', label: t('common.description'), type: 'text' },
  { key: 'primary_color', label: t('stores.brand_color'), type: 'custom' },
  { key: 'is_active', label: t('common.status'), type: 'custom' },
]);

const actions = computed<Action[]>(() => [
  { key: 'edit', label: t('common.edit') },
  { key: 'delete', label: t('common.delete'), variant: 'destructive' },
]);

function handleAction(actionKey: string, row: any) {
  if (actionKey === 'edit') {
    openEditDialog(row);
  } else if (actionKey === 'delete') {
    openDeleteDialog(row);
  }
}

function openDeleteDialog(item: StoreModel) {
  _openDeleteDialog(item);
}

function openCreateDialog() {
  isEditing.value = false;
  currentId.value = null;
  Object.assign(form, { name: '', slug: '', description: '', primary_color: '#0ea5e9', is_active: true });
  isDialogOpen.value = true;
}

function openEditDialog(store: StoreModel) {
  isEditing.value = true;
  currentId.value = store.id;
  Object.assign(form, {
    name: store.name,
    slug: store.slug || '',
    description: store.description || '',
    primary_color: store.primary_color || '#0ea5e9',
    is_active: store.is_active,
  });
  isDialogOpen.value = true;
}

async function handleSubmit() {
  try {
    if (isEditing.value && currentId.value) {
      await storesApi.update(currentId.value, form);
      toast({ title: t('common.success'), description: t('stores.updated') });
    } else {
      await storesApi.create(form);
      toast({ title: t('common.success'), description: t('stores.created') });
    }
    fetchStores();
    isDialogOpen.value = false;
  } catch (error) {
    console.error('Error saving store:', error);
  }
}

onMounted(() => {
  fetchStores();
});
</script>

<template>
  <div class="h-[calc(100vh-120px)] flex flex-col">
    <DataTable
      :columns="columns"
      :data="stores"
      :actions="actions"
      :is-loading="isLoading"
      :search-value="searchQuery"
      :empty-message="t('stores.empty')"
      :empty-icon="Store"
      class="flex-1 min-h-0"
      :search-placeholder="t('stores.search')"
      @search="search"
      @action="handleAction"
    >
      <template #toolbar-end>
        <Button @click="openCreateDialog">
          <Plus class="mr-2 h-4 w-4" />
          {{ t('stores.new') }}
        </Button>
      </template>

      <template #cell-primary_color="{ row }">
        <div class="flex items-center gap-2">
          <div 
            class="w-4 h-4 rounded-full border border-border" 
            :style="{ backgroundColor: row.primary_color || '#0ea5e9' }"
          ></div>
          <span class="text-xs text-muted-foreground">{{ row.primary_color || '#0ea5e9' }}</span>
        </div>
      </template>

      <template #cell-is_active="{ row }">
        <span
          class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
          :class="row.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
        >
          {{ row.is_active ? t('common.active_f') : t('common.inactive_f') }}
        </span>
      </template>
    </DataTable>

    <Dialog :open="isDialogOpen" @update:open="isDialogOpen = $event">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{{ isEditing ? t('stores.edit') : t('stores.new') }}</DialogTitle>
          <DialogDescription>
            {{ t('stores.form_desc') }}
          </DialogDescription>
        </DialogHeader>
        <div class="grid gap-4 py-4">
          <div class="grid gap-2">
            <Label htmlFor="name">{{ t('common.name') }} <span class="text-destructive">*</span></Label>
            <Input id="name" v-model="form.name" placeholder="Ej. Nidya Cancún" />
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label htmlFor="slug">{{ t('stores.slug') }}</Label>
              <Input id="slug" v-model="form.slug" placeholder="nidya-cancun" />
            </div>
            <div class="grid gap-2">
              <Label htmlFor="color">{{ t('stores.brand_color') }}</Label>
              <Input id="color" type="color" v-model="form.primary_color" class="h-10 p-1 cursor-pointer" />
            </div>
          </div>
          <div class="grid gap-2">
            <Label htmlFor="description">{{ t('common.description') }} ({{ t('common.optional') }})</Label>
            <Textarea id="description" v-model="form.description" :placeholder="t('stores.description_placeholder')" />
          </div>
          <div class="flex items-center space-x-2 mt-2">
            <Checkbox
              id="is_active"
              :checked="form.is_active"
              @update:checked="(val: boolean) => (form.is_active = val)"
            />
            <Label htmlFor="is_active" class="text-sm font-normal">
              {{ t('stores.active_in_system') }}
            </Label>
          </div>
        </div>
        <div class="flex justify-end gap-2">
          <Button variant="outline" @click="isDialogOpen = false">{{ t('common.cancel') }}</Button>
          <Button @click="handleSubmit">
            {{ isEditing ? t('common.save_changes') : t('stores.create') }}
          </Button>
        </div>
      </DialogContent>
    </Dialog>

    <ConfirmDialog
      :open="deleteDialogOpen"
      :title="t('stores.delete_confirm')"
      :description="t('stores.delete_desc')"
      :loading="isDeleting"
      @confirm="confirmDelete"
      @update:open="deleteDialogOpen = $event"
    />
  </div>
</template>
