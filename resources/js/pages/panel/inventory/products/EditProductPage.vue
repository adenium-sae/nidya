<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { categoriesApi } from '@/api/categories.api';
import { productsApi } from '@/api/products.api';
import client from '@/api/client';
import ProductForm from '@/components/products/ProductForm.vue';
import { useToast } from '@/components/ui/toast/use-toast';
import PageHeader from '@/components/app/PageHeader.vue';
import type { Category } from '@/types/models';

const router = useRouter();
const route = useRoute();
const { toast } = useToast();

const productId = route.params.id as string;
const categories = ref<Category[]>([]);
const isLoading = ref(false);
const isFetching = ref(true);
const initialData = ref<any>({});
const serverErrors = ref<Record<string, string[]>>({});

onMounted(async () => {
  try {
    const [catRes, productRes] = await Promise.all([
      categoriesApi.list(),
      productsApi.show(productId),
    ]);
    categories.value = catRes.data.data || catRes.data;

    const product = productRes.data;
    initialData.value = {
      name: product.name,
      description: product.description || '',
      sku: product.sku,
      barcode: product.barcode || '',
      cost: product.cost,
      category_id: product.category_id,
      type: product.type,
      min_stock: product.min_stock?.toString() || '0',
      is_active: product.is_active,
      image_url: product.image_url,
    };
  } catch (error) {
    console.error('Error loading data:', error);
    toast({
      title: 'Error',
      description: 'No se pudo cargar la información del producto.',
      variant: 'destructive',
    });
  } finally {
    isFetching.value = false;
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
    data.append('cost', formData.cost);
    data.append('category_id', formData.category_id);
    data.append('type', formData.type);
    data.append('is_active', formData.is_active ? '1' : '0');
    if (formData.min_stock) data.append('min_stock', formData.min_stock);
    if (imageFile) data.append('image', imageFile);
    data.append('_method', 'PUT');

    await client.post(`/admin/products/${productId}`, data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    toast({ title: 'Éxito', description: 'Producto actualizado correctamente.' });
    router.push('/panel/inventory/products');
  } catch (error: any) {
    console.error('Error updating product:', error);
    let msg = 'Hubo un error al actualizar el producto.';
    if (error.response?.data?.errors) {
      serverErrors.value = error.response.data.errors;
      msg = Object.values(error.response.data.errors).flat().join(', ');
    } else if (error.response?.data?.message) {
      msg = error.response.data.message;
    }
    toast({
      title: 'Error de validación',
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
      title="Editar Producto"
      description="Modifica la información del producto."
      show-back
    />

    <ProductForm
      :categories="categories"
      :stores="[]"
      :initial-data="initialData"
      :is-loading="isLoading"
      :is-edit-mode="true"
      :server-errors="serverErrors"
      submit-label="Guardar Cambios"
      @submit="handleSubmit"
      @cancel="handleCancel"
      @category-created="handleCategoryCreated"
    />
  </div>
</template>
