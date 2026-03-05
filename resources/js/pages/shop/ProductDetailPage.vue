<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Separator } from '@/components/ui/separator'
import { ArrowLeft, Tag } from 'lucide-vue-next'

const route = useRoute()

const product = ref<any>(null)
const isLoading = ref(true)
const selectedImageIndex = ref(0)

const fetchProduct = async () => {
  isLoading.value = true
  try {
    const res = await fetch(`/api/shop/catalog/products/${route.params.id}`)
    if (res.ok) {
      product.value = await res.json()
    }
  } catch (error) {
    console.error('Failed to load product:', error)
  } finally {
    isLoading.value = false
  }
}

const allImages = computed(() => {
  if (!product.value) return []
  const imgs: { url: string }[] = []
  if (product.value.images && product.value.images.length > 0) {
    imgs.push(...product.value.images)
  } else if (product.value.image_url) {
    imgs.push({ url: product.value.image_url })
  }
  return imgs
})

const currentImage = computed(() => {
  if (allImages.value.length > 0) {
    return allImages.value[selectedImageIndex.value]?.url
  }
  return 'https://placehold.co/600x600?text=Sin+imagen'
})

const storeProduct = computed(() => {
  if (product.value?.store_products?.length > 0) {
    return product.value.store_products[0]
  }
  return null
})

const price = computed(() => storeProduct.value?.price || 0)
const comparePrice = computed(() => storeProduct.value?.compare_at_price)
const hasDiscount = computed(() => comparePrice.value && comparePrice.value > price.value)

const formattedPrice = computed(() => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: storeProduct.value?.currency || 'MXN',
  }).format(price.value)
})

const formattedComparePrice = computed(() => {
  if (!hasDiscount.value) return ''
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: storeProduct.value?.currency || 'MXN',
  }).format(comparePrice.value)
})

const discountPercentage = computed(() => {
  if (!hasDiscount.value) return 0
  return Math.round(((comparePrice.value - price.value) / comparePrice.value) * 100)
})

onMounted(() => {
  fetchProduct()
})
</script>

<template>
  <div class="container mx-auto px-4 py-8">

    <!-- Loading State -->
    <div v-if="isLoading" class="space-y-8">
      <Skeleton class="h-8 w-48" />
      <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
        <Skeleton class="aspect-square w-full rounded-xl" />
        <div class="space-y-4">
          <Skeleton class="h-10 w-3/4" />
          <Skeleton class="h-6 w-1/4" />
          <Skeleton class="h-6 w-1/3" />
          <Skeleton class="h-24 w-full" />
        </div>
      </div>
    </div>

    <!-- Not Found State -->
    <div v-else-if="!product" class="text-center py-24 space-y-4">
      <h2 class="text-2xl font-bold">Producto no encontrado</h2>
      <p class="text-muted-foreground">El producto que buscas no existe o ya no está disponible.</p>
      <RouterLink to="/shop/catalog">
        <Button variant="outline">
          <ArrowLeft class="size-4 mr-2" />
          Volver al catálogo
        </Button>
      </RouterLink>
    </div>

    <!-- Product Detail -->
    <div v-else>
      <!-- Breadcrumb -->
      <div class="flex items-center gap-2 mb-8 text-sm text-muted-foreground">
        <RouterLink to="/shop/catalog" class="hover:text-foreground transition-colors">
          Catálogo
        </RouterLink>
        <span>/</span>
        <span v-if="product.category" class="hover:text-foreground transition-colors">
          {{ product.category.name }}
        </span>
        <span v-if="product.category">/</span>
        <span class="text-foreground font-medium">{{ product.name }}</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
        
        <!-- Image Gallery -->
        <div class="space-y-4">
          <!-- Main Image -->
          <div class="bg-muted/20 rounded-xl aspect-square overflow-hidden flex items-center justify-center p-6 border">
            <img 
              :src="currentImage" 
              :alt="product.name"
              class="object-contain w-full h-full mix-blend-multiply"
            />
          </div>
          
          <!-- Thumbnails -->
          <div v-if="allImages.length > 1" class="flex gap-3 overflow-x-auto pb-2">
            <button 
              v-for="(img, idx) in allImages" 
              :key="idx"
              class="shrink-0 w-20 h-20 rounded-lg border-2 overflow-hidden bg-muted/20 transition-all p-1"
              :class="selectedImageIndex === idx ? 'border-primary' : 'border-transparent hover:border-muted-foreground/30'"
              @click="selectedImageIndex = idx"
            >
              <img :src="img.url" :alt="`Imagen ${idx + 1}`" class="w-full h-full object-contain" />
            </button>
          </div>
        </div>

        <!-- Product Info -->
        <div class="space-y-6">
          <!-- Category -->
          <Badge v-if="product.category" variant="secondary">
            <Tag class="size-3 mr-1" />
            {{ product.category.name }}
          </Badge>

          <!-- Name -->
          <h1 class="text-3xl font-bold tracking-tight">{{ product.name }}</h1>

          <!-- SKU -->
          <p class="text-sm text-muted-foreground" v-if="product.sku">
            SKU: {{ product.sku }}
          </p>

          <!-- Price -->
          <div v-if="storeProduct" class="space-y-1">
            <div class="flex items-center gap-3">
              <span class="text-3xl font-bold text-primary">{{ formattedPrice }}</span>
              <span v-if="hasDiscount" class="text-lg text-muted-foreground line-through">
                {{ formattedComparePrice }}
              </span>
              <Badge v-if="hasDiscount" variant="destructive">
                -{{ discountPercentage }}%
              </Badge>
            </div>
            <p class="text-xs text-muted-foreground">Precio de venta al público</p>
          </div>
          <div v-else class="text-muted-foreground">
            Precio no disponible
          </div>

          <Separator />

          <!-- Description -->
          <div v-if="product.description" class="space-y-2">
            <h3 class="font-semibold text-lg">Descripción</h3>
            <p class="text-muted-foreground whitespace-pre-line leading-relaxed">
              {{ product.description }}
            </p>
          </div>

          <!-- Attributes -->
          <div v-if="product.attributes && product.attributes.length > 0" class="space-y-3">
            <h3 class="font-semibold text-lg">Características</h3>
            <div class="rounded-lg border overflow-hidden">
              <table class="w-full text-sm">
                <tbody>
                  <tr 
                    v-for="(attr, idx) in product.attributes" 
                    :key="attr.id"
                    :class="Number(idx) % 2 === 0 ? 'bg-muted/30' : ''"
                  >
                    <td class="px-4 py-2.5 font-medium w-1/3">{{ attr.name }}</td>
                    <td class="px-4 py-2.5 text-muted-foreground">{{ attr.value }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Back button -->
          <div class="pt-4">
            <RouterLink to="/shop/catalog">
              <Button variant="outline">
                <ArrowLeft class="size-4 mr-2" />
                Volver al catálogo
              </Button>
            </RouterLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
