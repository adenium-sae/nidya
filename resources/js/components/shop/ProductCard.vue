<script setup lang="ts">
import { computed, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Eye, ImageOff, Box } from 'lucide-vue-next';

const props = defineProps<{
  product: any;
}>();

const imageError = ref(false);

const primaryImage = computed(function() {
  if (props.product.images && props.product.images.length > 0) {
    const primary = props.product.images.find(function(img: any) {
      return img.is_primary;
    });
    return primary ? primary.url : props.product.images[0].url;
  }
  return props.product.image_url || null;
});

const storeProduct = computed(function() {
  if (props.product.store_products && props.product.store_products.length > 0) {
    return props.product.store_products[0];
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
  if (!props.product.track_inventory) {
    return { label: 'Disponible', color: 'text-emerald-600 bg-emerald-500/10' };
  }
  const qty = props.product.available_stock || 0;
  if (qty <= 0) {
    return { label: 'Agotado', color: 'text-red-600 bg-red-500/10' };
  }
  if (qty <= (props.product.min_stock || 5)) {
    return { label: `Pocas unidades (${qty})`, color: 'text-amber-600 bg-amber-500/10' };
  }
  return { label: `${qty} disponibles`, color: 'text-emerald-600 bg-emerald-500/10' };
});
</script>

<template>
  <RouterLink :to="`/shop/catalog/${product.id}`" class="block group outline-none">
    <Card class="product-card flex flex-col h-full overflow-hidden border border-border/60 bg-card transition-all duration-300 group-hover:shadow-xl group-hover:shadow-primary/5 group-hover:border-primary/25 group-focus-visible:ring-2 group-focus-visible:ring-ring">
      <!-- Image -->
      <div class="relative bg-gradient-to-br from-muted/30 to-muted/10 aspect-[4/3] overflow-hidden flex items-center justify-center">
        <!-- No image fallback -->
        <div v-if="!primaryImage || imageError" class="flex flex-col items-center justify-center gap-2 text-muted-foreground/40">
          <ImageOff class="size-10" />
        </div>
        
        <img 
          v-else
          :src="primaryImage" 
          :alt="product.name"
          class="object-contain w-full h-full p-4 transition-transform duration-500 ease-out group-hover:scale-110"
          @error="imageError = true"
        />
        
        <!-- Overlay on hover -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end justify-center pb-4">
          <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/90 text-xs font-medium text-foreground shadow-sm backdrop-blur-sm">
            <Eye class="size-3.5" />
            Ver detalle
          </span>
        </div>
        
        <!-- Badges -->
        <div class="absolute top-2.5 left-2.5 flex flex-col gap-1.5">
          <Badge v-if="hasDiscount" class="bg-red-500 hover:bg-red-500 text-white border-0 text-[11px] px-2 py-0.5 font-semibold shadow-sm">
            -{{ discountPercentage }}%
          </Badge>
        </div>

        <!-- Stock Indicator Overlay -->
        <div class="absolute top-2.5 right-2.5">
          <div 
            class="px-2 py-0.5 rounded-full text-[10px] font-bold backdrop-blur-md shadow-sm border border-white/20"
            :class="stockStatus.color"
          >
            {{ stockStatus.label }}
          </div>
        </div>
      </div>

      <!-- Content -->
      <CardContent class="flex-1 p-4 flex flex-col gap-1.5">
        <!-- Category -->
        <span v-if="product.category" class="text-[11px] font-medium text-primary/70 uppercase tracking-wider">
          {{ product.category.name }}
        </span>

        <h3 class="font-semibold text-sm line-clamp-2 leading-snug text-foreground group-hover:text-primary transition-colors duration-200">
          {{ product.name }}
        </h3>

        <div class="text-[11px] text-muted-foreground/70 tabular-nums" v-if="product.sku">
          {{ product.sku }}
        </div>
        
        <!-- Price -->
        <div class="mt-auto pt-2">
          <div class="flex items-baseline justify-between gap-2" v-if="storeProduct">
            <div class="flex items-baseline gap-2">
              <span class="text-lg font-bold text-foreground">{{ formattedPrice }}</span>
              <span v-if="hasDiscount" class="text-xs text-muted-foreground line-through">
                {{ formattedComparePrice }}
              </span>
            </div>
          </div>
          <div v-else class="text-xs text-muted-foreground italic">
            Precio no disponible
          </div>
        </div>
      </CardContent>
    </Card>
  </RouterLink>
</template>

<style scoped>
.product-card {
  will-change: transform, box-shadow;
}
</style>
