<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Card, CardContent } from '@/components/ui/card';
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from '@/components/ui/carousel';
import { Mail, Phone, ChevronRight, Sparkles, Package, Heart, ArrowRight, Star, ShieldCheck } from 'lucide-vue-next';
import { RouterLink } from 'vue-router';
import ProductCard from '@/components/shop/ProductCard.vue';

interface LandingSettings {
  hero_title: string | null;
  hero_subtitle: string | null;
  hero_image_url: string | null;
  about_us_text: string | null;
  contact_email: string | null;
  contact_phone: string | null;
}

const settings = ref<LandingSettings | null>(null);
const isLoading = ref(true);
const featuredProducts = ref<any[]>([]);
const isLoadingProducts = ref(true);

async function fetchSettings() {
  try {
    const res = await fetch('/api/shop/landing-page');
    if (res.ok) {
      settings.value = await res.json();
    }
  } catch (error) {
    console.error('Failed to load landing page settings:', error);
  } finally {
    isLoading.value = false;
  }
}

async function fetchFeaturedProducts() {
  try {
    const res = await fetch('/api/shop/catalog/products?per_page=8');
    if (res.ok) {
      const data = await res.json();
      featuredProducts.value = data.data?.slice(0, 8) || [];
    }
  } catch (error) {
    console.error('Failed to load featured products:', error);
  } finally {
    isLoadingProducts.value = false;
  }
}

onMounted(function() {
  fetchSettings();
  fetchFeaturedProducts();
});
</script>

<template>
  <div class="bg-background">
    
    <!-- Loading State -->
    <div v-if="isLoading" class="container mx-auto px-4 py-32 space-y-12">
      <div class="space-y-6 max-w-3xl mx-auto text-center">
        <Skeleton class="h-16 w-3/4 mx-auto rounded-lg" />
        <Skeleton class="h-6 w-1/2 mx-auto rounded-lg" />
        <Skeleton class="h-12 w-48 mx-auto rounded-lg" />
      </div>
    </div>

    <div v-else>

      <!-- ═══════════════════ HERO ═══════════════════ -->
      <section class="relative overflow-hidden">
        <!-- Gradient Background -->
        <div class="absolute inset-0 bg-gradient-to-br from-primary/5 via-background to-primary/10"></div>
        
        <!-- Decorative elements -->
        <div class="absolute top-20 left-10 w-72 h-72 bg-primary/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 bg-primary/5 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-primary/[0.03] rounded-full blur-3xl"></div>

        <!-- Optional Hero Image (subtle overlay) -->
        <div v-if="settings?.hero_image_url" class="absolute inset-0 z-0">
          <img 
            :src="settings.hero_image_url" 
            alt="" 
            class="w-full h-full object-cover opacity-10" 
          />
          <div class="absolute inset-0 bg-gradient-to-t from-background via-background/80 to-background/40"></div>
        </div>
        
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10 py-28 md:py-40">
          <div class="max-w-4xl mx-auto text-center space-y-8">
            <!-- Pill badge -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-primary/10 text-primary text-sm font-medium border border-primary/20 animate-fadeIn">
              <Sparkles class="size-4" />
              Catálogo en línea
            </div>

            <h1 class="text-4xl sm:text-5xl md:text-7xl font-extrabold tracking-tight text-foreground leading-[1.1] animate-fadeInUp">
              {{ settings?.hero_title || 'Bienvenido a nuestra tienda' }}
            </h1>
            
            <p class="text-lg md:text-xl text-muted-foreground leading-relaxed max-w-2xl mx-auto animate-fadeInUp" style="animation-delay: 100ms;">
              {{ settings?.hero_subtitle || 'Descubre productos y servicios pensados para ti.' }}
            </p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4 animate-fadeInUp" style="animation-delay: 200ms;">
              <RouterLink to="/shop/catalog">
                <Button size="lg" class="text-base px-8 h-12 shadow-lg shadow-primary/25 hover:shadow-primary/40 transition-all duration-300 group">
                  Ver catálogo
                  <ChevronRight class="ml-2 size-5 transition-transform group-hover:translate-x-0.5" />
                </Button>
              </RouterLink>
            </div>
          </div>
        </div>

        <!-- Bottom fade -->
        <div class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-background to-transparent"></div>
      </section>

      <!-- ═══════════════════ FEATURES STRIP ═══════════════════ -->
      <section class="border-y bg-muted/20">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
          <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x">
            <div class="flex items-center gap-4 py-8 md:py-10 md:px-8 group">
              <div class="shrink-0 w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center transition-colors group-hover:bg-primary/15">
                <Package class="size-6 text-primary" />
              </div>
              <div>
                <p class="font-semibold text-sm">Productos de calidad</p>
                <p class="text-xs text-muted-foreground">Seleccionados con cuidado para ti</p>
              </div>
            </div>
            <div class="flex items-center gap-4 py-8 md:py-10 md:px-8 group">
              <div class="shrink-0 w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center transition-colors group-hover:bg-primary/15">
                <ShieldCheck class="size-6 text-primary" />
              </div>
              <div>
                <p class="font-semibold text-sm">Garantía de satisfacción</p>
                <p class="text-xs text-muted-foreground">Tu tranquilidad es nuestra prioridad</p>
              </div>
            </div>
            <div class="flex items-center gap-4 py-8 md:py-10 md:px-8 group">
              <div class="shrink-0 w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center transition-colors group-hover:bg-primary/15">
                <Star class="size-6 text-primary" />
              </div>
              <div>
                <p class="font-semibold text-sm">Precios competitivos</p>
                <p class="text-xs text-muted-foreground">Las mejores ofertas del mercado</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══════════════════ FEATURED PRODUCTS ═══════════════════ -->
      <section class="py-16 md:py-24">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex items-end justify-between mb-10">
            <div class="space-y-2">
              <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-muted text-muted-foreground text-xs font-medium uppercase tracking-wider">
                Destacados
              </div>
              <h2 class="text-2xl md:text-3xl font-bold tracking-tight">
                Productos recientes
              </h2>
              <p class="text-muted-foreground text-sm max-w-md">
                Descubre nuestra selección de productos más recientes.
              </p>
            </div>
            <RouterLink to="/shop/catalog" class="hidden sm:inline-flex">
              <Button variant="ghost" class="gap-1.5 group text-sm">
                Ver todo
                <ArrowRight class="size-4 transition-transform group-hover:translate-x-0.5" />
              </Button>
            </RouterLink>
          </div>

          <!-- Loading -->
          <div v-if="isLoadingProducts" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div v-for="n in 4" :key="n" class="space-y-3">
              <Skeleton class="aspect-[4/3] w-full rounded-xl" />
              <div class="space-y-2 px-1">
                <Skeleton class="h-3 w-1/3 rounded" />
                <Skeleton class="h-4 w-2/3 rounded" />
                <Skeleton class="h-5 w-1/3 rounded" />
              </div>
            </div>
          </div>

          <!-- Products Carousel -->
          <Carousel
            v-else-if="featuredProducts.length > 0"
            :opts="{ align: 'start', loop: true }"
            class="w-full"
          >
            <CarouselContent class="-ml-4">
              <CarouselItem 
                v-for="product in featuredProducts" 
                :key="product.id" 
                class="pl-4 basis-full sm:basis-1/2 lg:basis-1/3 xl:basis-1/4"
              >
                <ProductCard :product="product" />
              </CarouselItem>
            </CarouselContent>
            <div class="flex items-center justify-end gap-2 mt-6">
              <CarouselPrevious class="static translate-y-0 translate-x-0" />
              <CarouselNext class="static translate-y-0 translate-x-0" />
            </div>
          </Carousel>

          <!-- Mobile: Ver todo button -->
          <div class="flex sm:hidden justify-center mt-8">
            <RouterLink to="/shop/catalog">
              <Button variant="outline" class="gap-1.5">
                Ver todo el catálogo
                <ArrowRight class="size-4" />
              </Button>
            </RouterLink>
          </div>
        </div>
      </section>

      <!-- ═══════════════════ ABOUT US ═══════════════════ -->
      <section v-if="settings?.about_us_text" class="py-16 md:py-24 border-t">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
          <div class="max-w-3xl mx-auto text-center space-y-6">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-muted text-muted-foreground text-xs font-medium uppercase tracking-wider">
              Sobre nosotros
            </div>
            <h2 class="text-3xl md:text-4xl font-bold tracking-tight">
              Conoce nuestra historia
            </h2>
            <p class="text-muted-foreground text-base md:text-lg leading-relaxed whitespace-pre-line">
              {{ settings.about_us_text }}
            </p>
          </div>
        </div>
      </section>

      <!-- ═══════════════════ CTA BANNER ═══════════════════ -->
      <section class="py-16">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
          <div class="relative overflow-hidden rounded-2xl bg-primary px-8 py-16 md:px-16 text-center">
            <!-- Decorative circles -->
            <div class="absolute -top-12 -right-12 w-48 h-48 bg-white/10 rounded-full"></div>
            <div class="absolute -bottom-16 -left-16 w-64 h-64 bg-white/5 rounded-full"></div>
            <div class="absolute top-1/2 right-1/4 w-32 h-32 bg-white/5 rounded-full"></div>
            
            <div class="relative z-10 space-y-6 max-w-2xl mx-auto">
              <h2 class="text-2xl md:text-3xl font-bold text-primary-foreground">
                ¿Listo para explorar?
              </h2>
              <p class="text-primary-foreground/80">
                Encuentra todo lo que necesitas en nuestro catálogo de productos.
              </p>
              <RouterLink to="/shop/catalog">
                <Button size="lg" variant="secondary" class="text-base px-8 h-12 group">
                  Explorar catálogo
                  <ChevronRight class="ml-2 size-5 transition-transform group-hover:translate-x-0.5" />
                </Button>
              </RouterLink>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══════════════════ CONTACT ═══════════════════ -->
      <section 
        v-if="settings?.contact_email || settings?.contact_phone" 
        class="py-16 border-t"
      >
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
          <div class="max-w-2xl mx-auto text-center space-y-8">
            <div class="space-y-3">
              <h2 class="text-2xl font-bold tracking-tight">Contacto</h2>
              <p class="text-muted-foreground text-sm">Estamos aquí para ayudarte en lo que necesites.</p>
            </div>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
              <a 
                v-if="settings?.contact_email"
                :href="`mailto:${settings.contact_email}`" 
                class="flex items-center gap-3 px-6 py-4 rounded-xl border bg-card hover:bg-muted/50 transition-all duration-200 hover:shadow-md group w-full sm:w-auto"
              >
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center shrink-0 group-hover:bg-primary/15 transition-colors">
                  <Mail class="size-5 text-primary" />
                </div>
                <div class="text-left">
                  <p class="text-xs text-muted-foreground">Email</p>
                  <p class="font-medium text-sm">{{ settings.contact_email }}</p>
                </div>
              </a>
              <a 
                v-if="settings?.contact_phone"
                :href="`tel:${settings.contact_phone}`" 
                class="flex items-center gap-3 px-6 py-4 rounded-xl border bg-card hover:bg-muted/50 transition-all duration-200 hover:shadow-md group w-full sm:w-auto"
              >
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center shrink-0 group-hover:bg-primary/15 transition-colors">
                  <Phone class="size-5 text-primary" />
                </div>
                <div class="text-left">
                  <p class="text-xs text-muted-foreground">Teléfono</p>
                  <p class="font-medium text-sm">{{ settings.contact_phone }}</p>
                </div>
              </a>
            </div>
          </div>
        </div>
      </section>

    </div>
  </div>
</template>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(16px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.6s ease-out both;
}

.animate-fadeInUp {
  animation: fadeInUp 0.7s ease-out both;
}
</style>