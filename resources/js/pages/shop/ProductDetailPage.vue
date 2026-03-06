<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ArrowLeft, Tag, ChevronRight, ImageOff, ZoomIn, Package, Info, List } from 'lucide-vue-next';

const route = useRoute();
const product = ref<any>(null);
const isLoading = ref(true);
const selectedImageIndex = ref(0);
const imageError = ref(false);
const isZoomed = ref(false);
const zoomPosition = ref({ x: 50, y: 50 });

async function fetchProduct() {
  isLoading.value = true;
  try {
    const res = await fetch(`/api/shop/catalog/products/${route.params.id}`);
    if (res.ok) {
      product.value = await res.json();
    }
  } catch (error) {
    console.error('Failed to load product:', error);
  } finally {
    isLoading.value = false;
  }
}

const allImages = computed(function() {
  if (!product.value) {
    return [];
  }
  const imgs: { url: string }[] = [];
  if (product.value.images && product.value.images.length > 0) {
    imgs.push(...product.value.images);
  } else if (product.value.image_url) {
    imgs.push({ url: product.value.image_url });
  }
  return imgs;
});

const currentImage = computed(function() {
  if (allImages.value.length > 0) {
    return allImages.value[selectedImageIndex.value]?.url;
  }
  return null;
});

const storeProduct = computed(function() {
  if (product.value?.store_products?.length > 0) {
    return product.value.store_products[0];
  }
  return null;
});

const price = computed(function() {
  return storeProduct.value?.price || 0;
});

const comparePrice = computed(function() {
  return storeProduct.value?.compare_at_price;
});

const hasDiscount = computed(function() {
  return comparePrice.value && comparePrice.value > price.value;
});

const formattedPrice = computed(function() {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: storeProduct.value?.currency || 'MXN',
  }).format(price.value);
});

const formattedComparePrice = computed(function() {
  if (!hasDiscount.value) {
    return '';
  }
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: storeProduct.value?.currency || 'MXN',
  }).format(comparePrice.value);
});

const discountPercentage = computed(function() {
  if (!hasDiscount.value) {
    return 0;
  }
  return Math.round(((comparePrice.value - price.value) / comparePrice.value) * 100);
});

const stockStatus = computed(function() {
  if (!product.value) {
    return null;
  }
  if (!product.value.track_inventory) {
    return { label: 'Disponible', color: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' };
  }
  const qty = product.value.available_stock || 0;
  if (qty <= 0) {
    return { label: 'Agotado', color: 'bg-red-500/10 text-red-600 border-red-500/20' };
  }
  if (qty <= (product.value.min_stock || 5)) {
    return { label: `Stock Crítico: ${qty} unidades`, color: 'bg-amber-500/10 text-amber-600 border-amber-500/20' };
  }
  return { label: `${qty} unidades disponibles`, color: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' };
});

function handleMouseMove(e: MouseEvent) {
  const target = e.currentTarget as HTMLElement;
  const rect = target.getBoundingClientRect();
  zoomPosition.value = {
    x: ((e.clientX - rect.left) / rect.width) * 100,
    y: ((e.clientY - rect.top) / rect.height) * 100,
  };
}

onMounted(function() {
  fetchProduct();
});
</script>

<template>
  <div class="min-h-[80vh]">

    <!-- Loading State -->
    <div v-if="isLoading" class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
      <Skeleton class="h-5 w-64" />
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16">
        <Skeleton class="aspect-square w-full rounded-2xl" />
        <div class="space-y-5">
          <Skeleton class="h-5 w-20 rounded-full" />
          <Skeleton class="h-9 w-3/4" />
          <Skeleton class="h-5 w-24" />
          <div class="space-y-2">
            <Skeleton class="h-10 w-1/3" />
            <Skeleton class="h-4 w-1/4" />
          </div>
          <Skeleton class="h-px w-full" />
          <Skeleton class="h-32 w-full" />
        </div>
      </div>
    </div>

    <!-- Not Found State -->
    <div v-else-if="!product" class="flex flex-col items-center justify-center text-center py-32 space-y-5">
      <div class="relative">
        <div class="absolute inset-0 bg-primary/5 rounded-full blur-xl scale-150"></div>
        <div class="relative inline-flex items-center justify-center size-20 rounded-full bg-muted border">
          <Package class="h-9 w-9 text-muted-foreground" />
        </div>
      </div>
      <div class="space-y-2">
        <h2 class="text-2xl font-bold">Producto no encontrado</h2>
        <p class="text-muted-foreground max-w-sm mx-auto text-sm">El producto que buscas no existe o ya no está disponible.</p>
      </div>
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
      <div class="border-b bg-muted/20">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-3">
          <nav class="flex items-center gap-1.5 text-sm text-muted-foreground" aria-label="Breadcrumb">
            <RouterLink to="/shop/catalog" class="hover:text-foreground transition-colors text-xs">
              Catálogo
            </RouterLink>
            <ChevronRight class="size-3 text-muted-foreground/50" />
            <span v-if="product.category" class="text-xs">
              {{ product.category.name }}
            </span>
            <ChevronRight v-if="product.category" class="size-3 text-muted-foreground/50" />
            <span class="text-foreground font-medium text-xs truncate max-w-[200px]">{{ product.name }}</span>
          </nav>
        </div>
      </div>

      <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-10">
        <!-- Back button at top for better UX -->
        <div class="mb-6 lg:mb-8">
          <RouterLink to="/shop/catalog">
            <Button variant="ghost" size="sm" class="-ml-2.5 text-muted-foreground hover:text-foreground transition-all">
              <ArrowLeft class="size-4 mr-2" />
              Volver al catálogo
            </Button>
          </RouterLink>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16">
          
          <!-- Image Gallery -->
          <div class="space-y-4">
            <!-- Main Image -->
            <div 
              class="relative bg-gradient-to-br from-muted/20 to-muted/5 rounded-2xl aspect-square overflow-hidden flex items-center justify-center border cursor-crosshair"
              @mouseenter="isZoomed = true"
              @mouseleave="isZoomed = false"
              @mousemove="handleMouseMove"
            >
              <!-- No image fallback -->
              <div v-if="!currentImage || imageError" class="flex flex-col items-center justify-center gap-3 text-muted-foreground/40">
                <ImageOff class="size-16" />
                <span class="text-sm font-medium">Sin imagen</span>
              </div>
              
              <img 
                v-else
                :src="currentImage" 
                :alt="product.name"
                class="object-contain w-full h-full p-6 transition-transform duration-300"
                :style="isZoomed ? {
                  transform: 'scale(2)',
                  transformOrigin: `${zoomPosition.x}% ${zoomPosition.y}%`
                } : {}"
                @error="imageError = true"
              />

              <!-- Zoom hint -->
              <div 
                v-if="currentImage && !imageError && !isZoomed" 
                class="absolute bottom-3 right-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-background/80 backdrop-blur-sm text-xs text-muted-foreground border shadow-sm"
              >
                <ZoomIn class="size-3" />
                Pasar el cursor para ampliar
              </div>

              <!-- Discount badge -->
              <Badge v-if="hasDiscount" class="absolute top-3 left-3 bg-red-500 hover:bg-red-500 text-white border-0 text-xs px-2.5 py-1 font-semibold shadow-sm">
                -{{ discountPercentage }}%
              </Badge>
            </div>
            
            <!-- Thumbnails -->
            <div v-if="allImages.length > 1" class="flex gap-2 overflow-x-auto pb-1">
              <button 
                v-for="(img, idx) in allImages" 
                :key="idx"
                class="shrink-0 w-16 h-16 sm:w-20 sm:h-20 rounded-xl border-2 overflow-hidden bg-muted/20 transition-all duration-200 p-1.5"
                :class="selectedImageIndex === idx 
                  ? 'border-primary ring-2 ring-primary/20' 
                  : 'border-transparent hover:border-muted-foreground/30'"
                @click="selectedImageIndex = idx"
              >
                <img :src="img.url" :alt="`Imagen ${idx + 1}`" class="w-full h-full object-contain" />
              </button>
            </div>
          </div>

          <!-- Product Info -->
          <div class="flex flex-col gap-8">
            <div class="space-y-6">
              <!-- Category & Stock -->
              <div class="flex items-center gap-3">
                <Badge v-if="product.category" variant="secondary" class="text-xs px-2.5 py-1">
                  <Tag class="size-3 mr-1.5" />
                  {{ product.category.name }}
                </Badge>
                
                <span 
                  v-if="stockStatus" 
                  class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border shadow-sm transition-all duration-300"
                  :class="stockStatus.color"
                >
                  <Box class="size-3 mr-1.5" />
                  {{ stockStatus.label }}
                </span>
              </div>

              <!-- Name & SKU -->
              <div class="space-y-2">
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight leading-tight">{{ product.name }}</h1>
                <p class="text-sm text-muted-foreground font-mono" v-if="product.sku">
                  SKU: {{ product.sku }}
                </p>
              </div>

              <!-- Price -->
              <div v-if="storeProduct" class="space-y-1.5">
                <div class="flex items-baseline gap-3 flex-wrap">
                  <span class="text-3xl sm:text-4xl font-bold text-foreground">{{ formattedPrice }}</span>
                  <span v-if="hasDiscount" class="text-lg text-muted-foreground line-through">
                    {{ formattedComparePrice }}
                  </span>
                  <Badge v-if="hasDiscount" class="bg-red-500/10 text-red-600 hover:bg-red-500/10 border-0 font-semibold">
                    Ahorra {{ discountPercentage }}%
                  </Badge>
                </div>
                <p class="text-xs text-muted-foreground">Precio de venta al público · IVA incluido</p>
              </div>
              <div v-else class="text-muted-foreground italic text-sm">
                Precio no disponible
              </div>
            </div>

            <Separator />

            <!-- Tabs for description and attributes -->
            <Tabs default-value="description" class="w-full">
              <TabsList class="w-full grid grid-cols-2 h-11 bg-muted/50 p-1">
                <TabsTrigger value="description" class="text-xs sm:text-sm gap-2">
                  <Info class="size-4" />
                  Descripción
                </TabsTrigger>
                <TabsTrigger value="attributes" class="text-xs sm:text-sm gap-2">
                  <List class="size-4" />
                  Características
                </TabsTrigger>
              </TabsList>

              <TabsContent value="description" class="mt-6">
                <div v-if="product.description" class="prose prose-sm max-w-none">
                  <p class="text-muted-foreground whitespace-pre-line leading-relaxed text-sm">
                    {{ product.description }}
                  </p>
                </div>
                <div v-else class="flex flex-col items-center justify-center py-10 px-4 text-center border border-dashed rounded-xl bg-muted/10">
                  <Info class="size-8 text-muted-foreground/30 mb-3" />
                  <p class="text-sm text-muted-foreground max-w-[250px]">
                    Este producto no tiene una descripción detallada disponible en este momento.
                  </p>
                </div>
              </TabsContent>

              <TabsContent value="attributes" class="mt-6">
                <div v-if="product.attributes && product.attributes.length > 0" class="rounded-xl border overflow-hidden shadow-sm">
                  <table class="w-full text-sm">
                    <tbody>
                      <tr 
                        v-for="(attr, idx) in product.attributes" 
                        :key="attr.id"
                        :class="Number(idx) % 2 === 0 ? 'bg-muted/30' : 'bg-background'"
                        class="transition-colors border-b last:border-0"
                      >
                        <td class="px-5 py-3.5 font-medium w-2/5 text-foreground">{{ attr.name }}</td>
                        <td class="px-5 py-3.5 text-muted-foreground">{{ attr.value }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div v-else class="flex flex-col items-center justify-center py-10 px-4 text-center border border-dashed rounded-xl bg-muted/10">
                  <List class="size-8 text-muted-foreground/30 mb-3" />
                  <p class="text-sm text-muted-foreground max-w-[250px]">
                    No se han registrado características técnicas para este producto.
                  </p>
                </div>
              </TabsContent>
            </Tabs>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
