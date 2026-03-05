<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { categoriesApi } from '@/api/categories.api';
import { storesApi } from '@/api/stores.api';
import { productsApi } from '@/api/products.api';
import ProductForm from '@/components/products/ProductForm.vue';
import { useToast } from '@/components/ui/toast/use-toast';
import PageHeader from '@/components/app/PageHeader.vue';
import type { Category, Store } from '@/types/models';

const router = useRouter();
const { t } = useI18n();
const { toast } = useToast();

const categories = ref<Category[]>([]);
const stores = ref<Store[]>([]);
const isLoading = ref(false);
const serverErrors = ref<Record<string, string[]>>({});

onMounted(async () => {
  try {
    const [catRes, storeRes] = await Promise.all([
      categoriesApi.list(),
      storesApi.list(),
    ]);
    categories.value = catRes.data.data || catRes.data;
    stores.value = storeRes.data.data || storeRes.data;
  } catch (error) {
    console.error('Error loading data:', error);
  }
});

async function handleSubmit(formData: any, imageFile: File | null) {
  isLoading.value = true;
  serverErrors.value = {};
  try {
    const data = new FormData();
    data.append('name', formData.name);
    data.append('sku', formData.sku);
    if (formData.description) data.append('description', formData.description);
    if (formData.barcode) data.append('barcode', formData.barcode);
    data.append('price', formData.price);
    data.append('cost', formData.cost);
    data.append('category_id', formData.category_id);
    data.append('type', formData.type);
    data.append('target_stores', formData.target_stores);
    if (formData.target_stores === 'single' && formData.store_id) {
      data.append('store_id', formData.store_id);
    }
    if (formData.target_stores === 'multiple' && formData.store_ids?.length > 0) {
      formData.store_ids.forEach((id: string, index: number) => {
        data.append(`store_ids[${index}]`, id);
      });
    }
    if (formData.min_stock) {
      data.append('min_stock', formData.min_stock);
    }
    if (imageFile) {
      data.append('image', imageFile);
    }

    if (formData.target_stores === 'multiple') {
      await productsApi.createMultiple(data);
    } else if (formData.target_stores === 'all') {
      await productsApi.createAll(data);
    } else {
      await productsApi.createSingle(data);
    }

    toast({ title: t('common.success'), description: t('products.created_success') });
    router.push('/panel/inventory/products');
  } catch (error: any) {
    console.error('Error creating product:', error);
    let msg = t('products.create_error');
    if (error.response?.data?.errors) {
      serverErrors.value = error.response.data.errors;
      msg = Object.values(error.response.data.errors).flat().join(', ');
    } else if (error.response?.data?.message) {
      msg = error.response.data.message;
    }
    toast({
      title: t('common.validation_error'),
      description: msg,
      variant: 'destructive',
      duration: 5000,
    });
  } finally {
    isLoading.value = false;
  }
}

function handleCancel() {
  router.back();
}

function handleCategoryCreated(category: Category) {
  categories.value.push(category);
}
</script>

<template>
  <div class="flex flex-col gap-8 w-full max-w-[1100px] mx-auto pb-12">
    <PageHeader
      :title="t('products.new_title')"
      :description="t('products.new_desc')"
      show-back
    />

    <ProductForm
      :categories="categories"
      :stores="stores"
      :is-loading="isLoading"
      :is-edit-mode="false"
      :server-errors="serverErrors"
      :submit-label="t('products.create')"
      @submit="handleSubmit"
      @cancel="handleCancel"
      @category-created="handleCategoryCreated"
    />
  </div>
</template>
