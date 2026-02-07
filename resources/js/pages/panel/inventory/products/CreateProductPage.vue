<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

import ProductForm from '@/components/products/ProductForm.vue'
import { Button } from '@/components/ui/button'
import { useToast } from '@/components/ui/toast/use-toast'
import { ChevronLeft } from 'lucide-vue-next'

const router = useRouter()
const { toast } = useToast()

interface Category {
  id: string
  name: string
}

interface Store {
  id: string
  name: string
}

const categories = ref<Category[]>([])
const stores = ref<Store[]>([])
const isLoading = ref(false)

onMounted(async function() {
  const token = localStorage.getItem('auth_token');
  try {
    const [catRes, storeRes] = await Promise.all([
      axios.get('/api/admin/categories', { headers: { Authorization: `Bearer ${token}` } }),
      axios.get('/api/admin/stores', { headers: { Authorization: `Bearer ${token}` } })
    ]);
    categories.value = catRes.data;
    stores.value = storeRes.data.data || storeRes.data;
  } catch (error) {
    console.error('Error loading data:', error);
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
    
    data.append('price', formData.price);
    data.append('cost', formData.cost);
    data.append('category_id', formData.category_id);
    data.append('type', formData.type);
    data.append('target_stores', formData.target_stores);
    
    if (formData.target_stores === 'single' && formData.store_id) {
      data.append('store_id', formData.store_id);
    }
    
    if (formData.target_stores === 'multiple' && formData.store_ids.length > 0) {
      formData.store_ids.forEach((id: string, index: number) => {
        data.append(`store_ids[${index}]`, id);
      });
    }

    if (formData.min_stock) data.append('min_stock', formData.min_stock);
    
    if (imageFile) {
      data.append('image', imageFile);
    }

    let endpoint = '/api/admin/products/single';
    if (formData.target_stores === 'multiple') endpoint = '/api/admin/products/multiple';
    if (formData.target_stores === 'all') endpoint = '/api/admin/products/all';
    
    await axios.post(endpoint, data, {
      headers: { 
        Authorization: `Bearer ${token}`,
        'Content-Type': 'multipart/form-data'
      }
    });
    
    toast({ title: 'Éxito', description: 'Producto creado correctamente.' });
    router.push('/panel/inventory/products');
    
  } catch (error: any) {
    console.error('Error creating product:', error);
    let msg = 'Hubo un error al guardar el producto.';
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
        <h1 class="text-3xl font-bold tracking-tight">Nuevo Producto</h1>
        <p class="text-muted-foreground">Completa la información para crear un nuevo producto.</p>
      </div>
    </div>

    <ProductForm
      :categories="categories"
      :stores="stores"
      :is-loading="isLoading"
      :is-edit-mode="false"
      submit-label="Crear Producto"
      @submit="handleSubmit"
      @cancel="handleCancel"
    />
  </div>
</template>
