<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

import { DataTable, type Column, type Action, type Filter, type Pagination } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { useToast } from '@/components/ui/toast/use-toast';
import { Plus, Package, Trash2, AlertCircle } from 'lucide-vue-next';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';

const router = useRouter();
const { toast } = useToast();

interface Product {
  id: string;
  name: string;
  sku: string;
  image_url: string | null;
  cost: string;
  category: { id: string; name: string } | null;
  stock: any[];
  store_products: any[];
  is_active: boolean;
  total_stock: number;
}

const products = ref<Product[]>([]);
const isLoading = ref(true);

const searchQuery = ref('');
const filterValues = ref<Record<string, string>>({});
const categories = ref<{ id: string; name: string }[]>([]);

const deleteDialogOpen = ref(false);
const productToDelete = ref<Product | null>(null);
const isDeleting = ref(false);

const pagination = ref<Pagination>({
  currentPage: 1,
  lastPage: 1,
  perPage: 15,
  total: 0
});

const columns: Column[] = [
  { 
    key: 'image_url', 
    label: '', 
    type: 'custom',
    width: 'w-[60px]'
  },
  { 
    key: 'name', 
    label: 'Nombre', 
    sortable: true,
    type: 'custom'
  },
  { 
    key: 'sku', 
    label: 'SKU',
    type: 'text'
  },
  { 
    key: 'category', 
    label: 'Categoría',
    type: 'custom'
  },
  { 
    key: 'price', 
    label: 'Precio',
    type: 'custom',
    align: 'right'
  },
  {
    key: 'total_stock',
    label: 'Existencias',
    type: 'custom',
    align: 'right'
  }
];

const actions: Action[] = [
  { key: 'edit', label: 'Editar' },
  { key: 'delete', label: 'Eliminar', variant: 'destructive' }
];

const filters = computed<Filter[]>(() => [
  {
    key: 'category_id',
    label: 'Categoría',
    type: 'select',
    placeholder: 'Todas las categorías',
    options: categories.value.map(c => ({ value: c.id, label: c.name }))
  }
]);

async function fetchProducts(page = 1) {
  isLoading.value = true;
  const token = localStorage.getItem('auth_token');
  
  try {
    const params: Record<string, any> = {
      page,
      per_page: pagination.value.perPage
    };
    
    if (searchQuery.value) params.search = searchQuery.value;
    if (filterValues.value.category_id) params.category_id = filterValues.value.category_id;
    
    const res = await axios.get('/api/admin/products', {
      headers: { Authorization: `Bearer ${token}` },
      params
    });
    
    products.value = res.data.data;
    pagination.value = {
      currentPage: res.data.current_page,
      lastPage: res.data.last_page,
      perPage: res.data.per_page,
      total: res.data.total
    };
  } catch (error) {
    console.error('Error fetching products:', error);
    toast({
      title: 'Error',
      description: 'No se pudieron cargar los productos.',
      variant: 'destructive'
    });
  } finally {
    isLoading.value = false;
  }
}

async function fetchCategories() {
  const token = localStorage.getItem('auth_token');
  try {
    const res = await axios.get('/api/admin/categories', {
      headers: { Authorization: `Bearer ${token}` }
    });
    categories.value = res.data;
  } catch (error) {
    console.error('Error fetching categories:', error);
  }
}

onMounted(() => {
  fetchProducts();
  fetchCategories();
});

function handleSearch(value: string) {
  searchQuery.value = value;
  fetchProducts(1);
}

function handleFilter(key: string, value: string) {
  filterValues.value[key] = value;
  fetchProducts(1);
}

function handlePageChange(page: number) {
  fetchProducts(page);
}

function handlePerPageChange(perPage: number) {
  pagination.value.perPage = perPage;
  fetchProducts(1);
}

function handleAction(action: string, row: Product) {
  if (action === 'edit') {
    router.push(`/panel/inventory/products/${row.id}/edit`);
  } else if (action === 'delete') {
    productToDelete.value = row;
    deleteDialogOpen.value = true;
  }
}

async function confirmDelete() {
  if (!productToDelete.value) return;
  isDeleting.value = true;
  const token = localStorage.getItem('auth_token');
  try {
    await axios.delete(`/api/admin/products/${productToDelete.value.id}`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    toast({ title: 'Éxito', description: 'Producto eliminado correctamente.' });
    if (products.value.length === 1 && pagination.value.currentPage > 1) {
      pagination.value.currentPage--;
    }
    await fetchProducts(pagination.value.currentPage);
    deleteDialogOpen.value = false;
  } catch (error) {
    console.error('Error deleting product:', error);
    toast({
      title: 'Error',
      description: 'No se pudo eliminar el producto.',
      variant: 'destructive'
    });
  } finally {
    isDeleting.value = false;
  }
}

function goToCreate() {
  router.push('/panel/inventory/products/create');
}

function getProductPrice(product: Product) {
  if (product.store_products && product.store_products.length > 0) {
    const price = parseFloat(product.store_products[0].price);
    return new Intl.NumberFormat('es-MX', {
      style: 'currency',
      currency: 'MXN'
    }).format(price);
  }
  return '-';
}

function getImageUrl(path: string | null) {
  if (!path) return null;
  if (path.startsWith('http')) return path;
  const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://127.0.0.1:8000';
  const cleanPath = path.startsWith('/') ? path.substring(1) : path;
  const cleanBase = backendUrl.endsWith('/') ? backendUrl.substring(0, backendUrl.length - 1) : backendUrl;
  return `${cleanBase}/${cleanPath}`;
}
</script>

<template>
  <div class="h-[calc(100vh-120px)] flex flex-col">
    <div class="flex flex-col gap-6 h-full">
      <DataTable
        :columns="columns"
        :data="products"
        :actions="actions"
        :filters="filters"
        :pagination="pagination"
        :is-loading="isLoading"
        :search-value="searchQuery"
        :filter-values="filterValues"
        search-placeholder="Buscar por nombre o SKU..."
        empty-message="No hay productos registrados."
        :empty-icon="Package"
        class="flex-1 min-h-0"
        @search="handleSearch"
        @filter="handleFilter"
        @page-change="handlePageChange"
        @per-page-change="handlePerPageChange"
        @action="handleAction"
      >
        <!-- Create button in toolbar -->
        <template #toolbar-end>
          <Button @click="goToCreate">
            <Plus class="mr-2 h-4 w-4" />
            Nuevo Producto
          </Button>
        </template>

        <!-- Custom cell for image_url -->
        <template #cell-image_url="{ row }">
          <div class="flex items-center justify-center">
            <div class="w-10 h-10 rounded-lg bg-muted overflow-hidden flex-shrink-0 border relative">
              <img 
                v-if="row.image_url" 
                :src="getImageUrl(row.image_url)" 
                :alt="row.name || ''"
                class="w-full h-full object-cover"
                @error="$event.target.style.display='none'"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-muted-foreground">
                <Package class="h-5 w-5 opacity-20" />
              </div>
            </div>
          </div>
        </template>

        <!-- Custom cell for name with link styling -->
        <template #cell-name="{ row }">
          <span class="font-medium text-primary hover:underline cursor-pointer">
            {{ row.name }}
          </span>
        </template>

        <!-- Custom cell for category -->
        <template #cell-category="{ row }">
          <span v-if="row.category" class="text-muted-foreground">
            {{ row.category.name }}
          </span>
          <span v-else class="text-muted-foreground/50">Sin categoría</span>
        </template>

        <!-- Custom cell for price -->
        <template #cell-price="{ row }">
          <span class="font-medium tabular-nums">
            {{ getProductPrice(row) }}
          </span>
        </template>

        <!-- Custom cell for total stock -->
        <template #cell-total_stock="{ row }">
          <span :class="[
            'font-bold tabular-nums',
            (row.total_stock || 0) <= 0 ? 'text-destructive' : 'text-primary'
          ]">
            {{ row.total_stock || 0 }}
          </span>
        </template>
      </DataTable>
    </div>

    <Dialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Confirmar eliminación</DialogTitle>
          <DialogDescription>
            ¿Estás seguro de que deseas eliminar el producto <span class="font-medium text-foreground">"{{ productToDelete?.name }}"</span>?
            Esta acción no se puede deshacer.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" @click="deleteDialogOpen = false" :disabled="isDeleting">
            Cancelar
          </Button>
          <Button variant="destructive" @click="confirmDelete" :disabled="isDeleting">
            <span v-if="isDeleting">Eliminando...</span>
            <span v-else>Eliminar</span>
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
