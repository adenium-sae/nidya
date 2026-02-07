<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { useRouter, useRoute } from 'vue-router'

import ProductForm from '@/components/products/ProductForm.vue'
import { Button } from '@/components/ui/button'
import { useToast } from '@/components/ui/toast/use-toast'
import { ChevronLeft } from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const { toast } = useToast()

interface Category {
  id: string
  name: string
}

const productId = route.params.id as string
const categories = ref<Category[]>([])
const isLoading = ref(false)
const isFetching = ref(true)
const initialData = ref<any>({})

onMounted(async function() {
  const token = localStorage.getItem('auth_token');
  try {
    const catRes = await axios.get('/api/admin/categories', { 
      headers: { Authorization: `Bearer ${token}` } 
    });
    categories.value = catRes.data;

    const productRes = await axios.get(`/api/admin/products/${productId}`, {
      headers: { Authorization: `Bearer ${token}` }
    });

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
  const token = localStorage.getItem('auth_token');
  
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
    
    if (imageFile) {
      data.append('image', imageFile);
    }

    data.append('_method', 'PUT');
    
    await axios.post(`/api/admin/products/${productId}`, data, {
      headers: { 
        Authorization: `Bearer ${token}`,
        'Content-Type': 'multipart/form-data'
      }
    });
    
    toast({ title: 'Éxito', description: 'Producto actualizado correctamente.' });
    router.push('/panel/inventory/products');
    
  } catch (error: any) {
    console.error('Error updating product:', error);
    let msg = 'Hubo un error al actualizar el producto.';
    if (error.response?.data?.message) msg = error.response.data.message;
    if (error.response?.data?.errors) {
      msg += ' ' + Object.values(error.response.data.errors).flat().join(', ');
    }
    
    toast({
      title: 'Error',
      description: msg,
      variant: 'destructive',
    });
  } finally {
    isLoading.value = false;
  }
}

function handleCancel() {
  router.back();
}
</script>

<template>
  <div class="flex flex-col gap-8 w-full max-w-[1100px] mx-auto pb-12">
    <!-- Header -->
    <div class="flex items-center gap-4">
      <Button variant="outline" size="icon" @click="router.back()">
        <ChevronLeft class="h-4 w-4" />
      </Button>
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Editar Producto</h1>
        <p class="text-muted-foreground">Modifica la información del producto.</p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isFetching" class="flex items-center justify-center py-20">
      <div class="text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        <p class="text-muted-foreground mt-4">Cargando producto...</p>
      </div>
    </div>

    <ProductForm
      v-else
      :categories="categories"
      :stores="[]"
      :initial-data="initialData"
      :is-loading="isLoading"
      :is-edit-mode="true"
      submit-label="Guardar Cambios"
      @submit="handleSubmit"
      @cancel="handleCancel"
    />
  </div>
</template>
