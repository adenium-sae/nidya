<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Search, FilterX, SlidersHorizontal, LayoutGrid, X, ChevronLeft, ChevronRight, Package } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Sheet, SheetClose, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import ProductCard from '@/components/shop/ProductCard.vue';

interface Category {
  id: string;
  name: string;
  slug: string;
  products_count?: number;
}

const route = useRoute();
const router = useRouter();
const products = ref<any[]>([]);
const categories = ref<Category[]>([]);
const isLoadingProducts = ref(true);
const isLoadingCategories = ref(true);
const totalProducts = ref(0);
const currentPage = ref(Number(route.query.page) || 1);
const lastPage = ref(1);
const searchQuery = ref((route.query.search as string) || '');
const currentCategory = ref((route.query.category as string) || '');

const activeCategoryName = computed(function() {
  if (!currentCategory.value) {
    return null;
  }
  const found = categories.value.find(function(c) {
    return c.id === currentCategory.value;
  });
  return found?.name || null;
});

async function fetchCategories() {
  isLoadingCategories.value = true;
  try {
    const res = await fetch('/api/shop/categories');
    if (res.ok) {
      categories.value = await res.json();
    }
  } catch(e) {
    console.error(e);
  } finally {
    isLoadingCategories.value = false;
  }
}

async function fetchProducts() {
  isLoadingProducts.value = true;
  try {
    const params = new URLSearchParams();
    if (searchQuery.value) {
      params.append('search', searchQuery.value);
    }
    if (currentCategory.value) {
      params.append('category', currentCategory.value);
    }
    params.append('page', String(currentPage.value));
    const res = await fetch(`/api/shop/catalog/products?${params.toString()}`);
    if (res.ok) {
      const data = await res.json();
      products.value = data.data;
      totalProducts.value = data.total;
      lastPage.value = data.last_page;
      currentPage.value = data.current_page;
    }
  } catch (error) {
    console.error('Failed to fetch products', error);
  } finally {
    isLoadingProducts.value = false;
  }
}

function updateFilters() {
  const query: Record<string, string> = {};
  if (searchQuery.value) {
    query.search = searchQuery.value;
  }
  if (currentCategory.value) {
    query.category = currentCategory.value;
  }
  if (currentPage.value > 1) {
    query.page = String(currentPage.value);
  }
  router.push({ query });
}

function handleSearch() {
  currentPage.value = 1;
  updateFilters();
}

function selectCategory(categoryId: string) {
  currentCategory.value = currentCategory.value === categoryId ? '' : categoryId;
  currentPage.value = 1;
  updateFilters();
}

function clearFilters() {
  searchQuery.value = '';
  currentCategory.value = '';
  currentPage.value = 1;
  updateFilters();
}

function goToPage(page: number) {
  if (page < 1 || page > lastPage.value) {
    return;
  }
  currentPage.value = page;
  updateFilters();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

const paginationPages = computed(function() {
  const pages: (number | string)[] = [];
  const total = lastPage.value;
  const current = currentPage.value;
  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pages.push(i);
    }
  } else {
    pages.push(1);
    if (current > 3) {
      pages.push('...');
    }
    for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
      pages.push(i);
    }
    if (current < total - 2) {
      pages.push('...');
    }
    pages.push(total);
  }
  return pages;
});

const hasActiveFilters = computed(function() {
  return searchQuery.value || currentCategory.value;
});

watch(
  function() {
    return route.query;
  },
  function(newQuery) {
    searchQuery.value = (newQuery.search as string) || '';
    currentCategory.value = (newQuery.category as string) || '';
    currentPage.value = Number(newQuery.page) || 1;
    fetchProducts();
  },
  { deep: true }
);

onMounted(function() {
  fetchCategories();
  fetchProducts();
});
</script>

<template>
  <div class="min-h-[80vh]">
    <!-- Page Header -->
    <div class="border-b bg-gradient-to-b from-muted/30 to-background">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col gap-6">
          <div class="flex flex-col md:flex-row items-start md:items-end justify-between gap-4">
            <div>
              <h1 class="text-3xl font-bold tracking-tight">Catálogo</h1>
              <p class="text-muted-foreground mt-1 text-sm">
                {{ isLoadingProducts ? 'Cargando...' : `${totalProducts} producto${totalProducts !== 1 ? 's' : ''} disponible${totalProducts !== 1 ? 's' : ''}` }}
              </p>
            </div>
            
            <!-- Search bar -->
            <div class="relative w-full md:w-80">
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
              <Input 
                v-model="searchQuery" 
                placeholder="Buscar productos..." 
                class="pl-9 pr-9 h-10 bg-background"
                @keyup.enter="handleSearch"
              />
              <button 
                v-if="searchQuery" 
                class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded-sm hover:bg-muted transition-colors"
                @click="() => { searchQuery = ''; handleSearch() }"
              >
                <X class="h-3.5 w-3.5 text-muted-foreground" />
              </button>
            </div>
          </div>

          <!-- Active filters -->
          <div v-if="hasActiveFilters" class="flex items-center gap-2 flex-wrap">
            <span class="text-xs text-muted-foreground">Filtros activos:</span>
            <Badge 
              v-if="activeCategoryName" 
              variant="secondary" 
              class="gap-1 pl-2.5 pr-1.5 py-1 text-xs cursor-pointer hover:bg-muted transition-colors"
              @click="selectCategory(currentCategory)"
            >
              {{ activeCategoryName }}
              <X class="size-3 ml-0.5" />
            </Badge>
            <Badge 
              v-if="searchQuery" 
              variant="secondary" 
              class="gap-1 pl-2.5 pr-1.5 py-1 text-xs cursor-pointer hover:bg-muted transition-colors"
              @click="() => { searchQuery = ''; handleSearch() }"
            >
              "{{ searchQuery }}"
              <X class="size-3 ml-0.5" />
            </Badge>
            <button 
              class="text-xs text-primary hover:underline underline-offset-4"
              @click="clearFilters"
            >
              Limpiar todo
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex gap-8">
        
        <!-- Sidebar Filters (Desktop) -->
        <aside class="hidden md:block w-56 shrink-0">
          <div class="sticky top-24 space-y-6">
            <div>
              <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground">Categorías</h3>
              </div>
              
              <div v-if="isLoadingCategories" class="space-y-2">
                <Skeleton class="h-8 w-full rounded-md" v-for="i in 6" :key="i" />
              </div>
              
              <ScrollArea v-else class="max-h-[60vh]">
                <div class="space-y-0.5 pr-3">
                  <button
                    v-for="category in categories"
                    :key="category.id"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm transition-all duration-200"
                    :class="currentCategory === category.id 
                      ? 'bg-primary text-primary-foreground font-medium shadow-sm' 
                      : 'text-muted-foreground hover:text-foreground hover:bg-muted/60'"
                    @click="selectCategory(category.id)"
                  >
                    <span class="truncate">{{ category.name }}</span>
                    <span 
                      v-if="category.products_count" 
                      class="text-[10px] tabular-nums shrink-0 ml-2"
                      :class="currentCategory === category.id ? 'text-primary-foreground/70' : 'text-muted-foreground/60'"
                    >
                      {{ category.products_count }}
                    </span>
                  </button>
                  <p v-if="categories.length === 0" class="text-sm text-muted-foreground px-3 py-4">
                    No se encontraron categorías.
                  </p>
                </div>
              </ScrollArea>
            </div>
          </div>
        </aside>

        <!-- Mobile Filters (Sheet) -->
        <Sheet>
          <SheetTrigger as-child>
            <Button 
              variant="outline" 
              size="sm" 
              class="md:hidden fixed bottom-6 right-6 z-40 shadow-lg rounded-full h-12 w-12 p-0"
            >
              <SlidersHorizontal class="size-5" />
            </Button>
          </SheetTrigger>
          <SheetContent side="left" class="w-72 p-0">
            <SheetHeader class="px-5 py-4 border-b">
              <SheetTitle class="text-left">Filtrar</SheetTitle>
            </SheetHeader>
            <ScrollArea class="h-[calc(100vh-80px)]">
              <div class="px-5 py-4 space-y-4">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Categorías</h4>
                <div class="space-y-0.5">
                  <SheetClose as-child v-for="category in categories" :key="category.id">
                    <button
                      class="w-full flex items-center justify-between px-3 py-2.5 rounded-md text-sm transition-all duration-200"
                      :class="currentCategory === category.id 
                        ? 'bg-primary text-primary-foreground font-medium' 
                        : 'text-muted-foreground hover:text-foreground hover:bg-muted/60'"
                      @click="selectCategory(category.id)"
                    >
                      <span>{{ category.name }}</span>
                    </button>
                  </SheetClose>
                </div>
                <Separator />
                <SheetClose as-child>
                  <Button variant="outline" class="w-full" @click="clearFilters">
                    <FilterX class="size-4 mr-2" />
                    Limpiar filtros
                  </Button>
                </SheetClose>
              </div>
            </ScrollArea>
          </SheetContent>
        </Sheet>

        <!-- Products Grid -->
        <div class="flex-1 min-w-0">
          <!-- Loading State Grid -->
          <div v-if="isLoadingProducts" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            <div v-for="n in 12" :key="n" class="space-y-3">
              <Skeleton class="aspect-[4/3] w-full rounded-xl" />
              <div class="space-y-2 px-1">
                <Skeleton class="h-3 w-1/3 rounded" />
                <Skeleton class="h-4 w-2/3 rounded" />
                <Skeleton class="h-5 w-1/3 rounded" />
              </div>
            </div>
          </div>
          
          <!-- Empty State -->
          <div v-else-if="products.length === 0" class="flex flex-col items-center justify-center text-center py-24 space-y-5">
            <div class="relative">
              <div class="absolute inset-0 bg-primary/5 rounded-full blur-xl scale-150"></div>
              <div class="relative inline-flex items-center justify-center size-20 rounded-full bg-muted border">
                <Package class="h-9 w-9 text-muted-foreground" />
              </div>
            </div>
            <div class="space-y-2">
              <h3 class="text-xl font-semibold">No se encontraron productos</h3>
              <p class="text-muted-foreground max-w-sm mx-auto text-sm leading-relaxed">
                No encontramos resultados con los filtros actuales. Intenta ajustar tu búsqueda o selecciona otra categoría.
              </p>
            </div>
            <Button variant="outline" @click="clearFilters" class="mt-2">
              <FilterX class="size-4 mr-2" />
              Limpiar filtros
            </Button>
          </div>

          <!-- Product Grid -->
          <div v-else>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
              <div 
                v-for="(product, index) in products" 
                :key="product.id" 
                class="animate-fadeInUp"
                :style="{ animationDelay: `${index * 40}ms` }"
              >
                <ProductCard :product="product" />
              </div>
            </div>

            <!-- Pagination -->
            <div v-if="lastPage > 1" class="flex items-center justify-center gap-1.5 mt-12 pt-8 border-t">
              <Button 
                variant="outline" 
                size="icon"
                class="h-9 w-9"
                :disabled="currentPage <= 1"
                @click="goToPage(currentPage - 1)"
              >
                <ChevronLeft class="size-4" />
              </Button>

              <template v-for="(page, idx) in paginationPages" :key="idx">
                <span v-if="page === '...'" class="px-1 text-muted-foreground text-sm select-none">…</span>
                <Button 
                  v-else
                  :variant="page === currentPage ? 'default' : 'ghost'"
                  size="sm"
                  class="h-9 w-9 p-0 text-sm"
                  @click="goToPage(page as number)"
                >
                  {{ page }}
                </Button>
              </template>

              <Button 
                variant="outline" 
                size="icon"
                class="h-9 w-9"
                :disabled="currentPage >= lastPage"
                @click="goToPage(currentPage + 1)"
              >
                <ChevronRight class="size-4" />
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeInUp {
  animation: fadeInUp 0.4s ease-out both;
}
</style>
