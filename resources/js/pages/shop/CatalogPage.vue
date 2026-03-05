<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Search, FilterX } from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'

import ProductCard from '@/components/shop/ProductCard.vue'

interface Category {
  id: string
  name: string
  slug: string
}

const route = useRoute()
const router = useRouter()

const products = ref<any[]>([])
const categories = ref<Category[]>([])
const isLoadingProducts = ref(true)
const isLoadingCategories = ref(true)
const totalProducts = ref(0)

const searchQuery = ref((route.query.search as string) || '')
const currentCategory = ref((route.query.category as string) || '')

const fetchCategories = async () => {
    isLoadingCategories.value = true
    try {
        const res = await fetch('/api/shop/categories')
        if (res.ok) {
            categories.value = await res.json()
        }
    } catch(e) {
        console.error(e)
    } finally {
        isLoadingCategories.value = false
    }
}

const fetchProducts = async () => {
  isLoadingProducts.value = true
  try {
    const params = new URLSearchParams()
    if (searchQuery.value) params.append('search', searchQuery.value)
    if (currentCategory.value) params.append('category', currentCategory.value)

    const res = await fetch(`/api/shop/catalog/products?${params.toString()}`)
    if (res.ok) {
      const data = await res.json()
      products.value = data.data
      totalProducts.value = data.total
    }
  } catch (error) {
    console.error('Failed to fetch products', error)
  } finally {
    isLoadingProducts.value = false
  }
}

// Update URL parameters without completely reloading the page
const updateFilters = () => {
    const query = { ...route.query }
    if (searchQuery.value) {
        query.search = searchQuery.value
    } else {
        delete query.search
    }

    if (currentCategory.value) {
        query.category = currentCategory.value
    } else {
        delete query.category
    }

    router.push({ query })
}

const handleSearch = () => {
    updateFilters()
}

const selectCategory = (categoryId: string) => {
    currentCategory.value = currentCategory.value === categoryId ? '' : categoryId
    updateFilters()
}

const clearFilters = () => {
    searchQuery.value = ''
    currentCategory.value = ''
    updateFilters()
}

watch(
  () => route.query,
  () => {
    fetchProducts()
  },
  { deep: true }
)

onMounted(() => {
  fetchCategories()
  fetchProducts()
})

</script>

<template>
  <div class="container mx-auto px-4 py-8">
    
    <!-- Page Header & Global Search -->
    <div class="mb-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Catálogo</h1>
        <p class="text-muted-foreground mt-1">
          Explora {{ totalProducts }} productos disponibles.
        </p>
      </div>

      <div class="relative w-full md:w-96">
        <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
        <Input 
          v-model="searchQuery" 
          placeholder="Buscar productos..." 
          class="pl-10"
          @keyup.enter="handleSearch"
        />
        <Button 
            v-if="searchQuery" 
            variant="ghost" 
            size="sm" 
            class="absolute right-1 top-1 h-8 w-8 p-0"
            @click="() => { searchQuery = ''; handleSearch() }"
        >
            <FilterX class="h-4 w-4" />
        </Button>
      </div>
    </div>

    <div class="flex flex-col md:flex-row gap-8">
      
      <!-- Sidebar Filters -->
      <aside class="w-full md:w-64 shrink-0 space-y-6">
        <div>
          <div class="flex items-center justify-between mb-4">
              <h3 class="font-semibold text-lg">Categorías</h3>
              <Button 
                  v-if="currentCategory" 
                  variant="link" 
                  size="sm" 
                  class="h-auto p-0"
                  @click="clearFilters"
              >
                  Limpiar
              </Button>
          </div>
          
          <div v-if="isLoadingCategories" class="space-y-2">
              <Skeleton class="h-8 w-full" v-for="i in 5" :key="i" />
          </div>
          
          <div v-else class="space-y-1">
            <Button
              v-for="category in categories"
              :key="category.id"
              variant="ghost"
              class="w-full justify-start font-normal"
              :class="currentCategory === category.id ? 'bg-muted font-medium text-primary' : ''"
              @click="selectCategory(category.id)"
            >
              {{ category.name }}
            </Button>
            <p v-if="categories.length === 0" class="text-sm text-muted-foreground">
                No se encontraron categorías.
            </p>
          </div>
        </div>
      </aside>

      <!-- Products Grid -->
      <div class="flex-1">
          <!-- Loading State Grid -->
          <div v-if="isLoadingProducts" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
              <div v-for="n in 8" :key="n" class="space-y-4">
                  <Skeleton class="h-[250px] w-full rounded-xl" />
                  <div class="space-y-2">
                      <Skeleton class="h-4 w-2/3" />
                      <Skeleton class="h-4 w-1/2" />
                  </div>
              </div>
          </div>
          
          <!-- Empty State -->
          <div v-else-if="products.length === 0" class="text-center py-24 space-y-4 border rounded-xl bg-muted/10">
              <div class="inline-flex items-center justify-center size-16 rounded-full bg-muted">
                  <Search class="h-8 w-8 text-muted-foreground" />
              </div>
              <h3 class="text-xl font-semibold">No se encontraron productos</h3>
              <p class="text-muted-foreground max-w-sm mx-auto">
                  No encontramos resultados con los filtros actuales. Intenta ajustar tu búsqueda o selecciona otra categoría.
              </p>
              <Button variant="outline" @click="clearFilters">Limpiar filtros</Button>
          </div>

          <!-- Product Grid -->
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <ProductCard 
                v-for="product in products" 
                :key="product.id" 
                :product="product" 
            />
          </div>
      </div>
    </div>
  </div>
</template>
