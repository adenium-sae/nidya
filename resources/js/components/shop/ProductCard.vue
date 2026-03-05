<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

const props = defineProps<{
  product: any
}>()

const primaryImage = computed(() => {
  if (props.product.images && props.product.images.length > 0) {
    const primary = props.product.images.find((img: any) => img.is_primary)
    return primary ? primary.url : props.product.images[0].url
  }
  return props.product.image_url || 'https://placehold.co/400x400?text=Sin+imagen'
})

const storeProduct = computed(() => {
  if (props.product.store_products && props.product.store_products.length > 0) {
    return props.product.store_products[0]
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
</script>

<template>
  <RouterLink :to="`/shop/catalog/${product.id}`" class="block group">
    <Card class="flex flex-col h-full overflow-hidden transition-all duration-300 group-hover:shadow-lg group-hover:border-primary/30">
      <!-- Image -->
      <div class="relative bg-muted/20 aspect-square overflow-hidden flex items-center justify-center p-4">
        <img 
          :src="primaryImage" 
          :alt="product.name"
          class="object-contain w-full h-full mix-blend-multiply transition-transform duration-300 group-hover:scale-105"
        />
        
        <!-- Badges -->
        <div class="absolute top-2 left-2 flex flex-col gap-2">
          <Badge v-if="hasDiscount" variant="destructive">
            Oferta
          </Badge>
          <Badge v-if="product.category" variant="secondary" class="opacity-90">
            {{ product.category.name }}
          </Badge>
        </div>
      </div>

      <!-- Content -->
      <CardContent class="flex-1 p-4 space-y-2">
        <div class="text-xs text-muted-foreground line-clamp-1" v-if="product.sku">
          SKU: {{ product.sku }}
        </div>
        <h3 class="font-semibold text-base line-clamp-2 leading-tight group-hover:text-primary transition-colors">
          {{ product.name }}
        </h3>
        
        <!-- Price -->
        <div class="flex items-center gap-2 mt-2" v-if="storeProduct">
          <span class="text-lg font-bold text-primary">{{ formattedPrice }}</span>
          <span v-if="hasDiscount" class="text-sm text-muted-foreground line-through">
            {{ formattedComparePrice }}
          </span>
        </div>
        <div v-else class="text-sm text-muted-foreground mt-2">
          Precio no disponible
        </div>
      </CardContent>
    </Card>
  </RouterLink>
</template>
