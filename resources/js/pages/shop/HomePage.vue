<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Mail, Phone, ChevronRight, Sparkles, Package, Heart } from 'lucide-vue-next'
import { RouterLink } from 'vue-router'

interface LandingSettings {
  hero_title: string | null
  hero_subtitle: string | null
  hero_image_url: string | null
  about_us_text: string | null
  contact_email: string | null
  contact_phone: string | null
}

const settings = ref<LandingSettings | null>(null)
const isLoading = ref(true)

const fetchSettings = async () => {
  try {
    const res = await fetch('/api/shop/landing-page')
    if (res.ok) {
      settings.value = await res.json()
    }
  } catch (error) {
    console.error('Failed to load landing page settings:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchSettings()
})
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
        
        <!-- Decorative blobs -->
        <div class="absolute top-20 left-10 w-72 h-72 bg-primary/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 bg-primary/5 rounded-full blur-3xl"></div>

        <!-- Optional Hero Image (subtle overlay) -->
        <div v-if="settings?.hero_image_url" class="absolute inset-0 z-0">
          <img 
            :src="settings.hero_image_url" 
            alt="" 
            class="w-full h-full object-cover opacity-10" 
          />
          <div class="absolute inset-0 bg-gradient-to-t from-background via-background/80 to-background/40"></div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10 py-28 md:py-40">
          <div class="max-w-4xl mx-auto text-center space-y-8">
            <!-- Pill badge -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-primary/10 text-primary text-sm font-medium border border-primary/20">
              <Sparkles class="size-4" />
              Catálogo en línea
            </div>

            <h1 class="text-4xl sm:text-5xl md:text-7xl font-extrabold tracking-tight text-foreground leading-[1.1]">
              {{ settings?.hero_title || 'Bienvenido a nuestra tienda' }}
            </h1>
            
            <p class="text-lg md:text-xl text-muted-foreground leading-relaxed max-w-2xl mx-auto">
              {{ settings?.hero_subtitle || 'Descubre productos y servicios pensados para ti.' }}
            </p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
              <RouterLink to="/shop/catalog">
                <Button size="lg" class="text-base px-8 h-12 shadow-lg shadow-primary/25 hover:shadow-primary/40 transition-shadow">
                  Ver catálogo
                  <ChevronRight class="ml-2 size-5" />
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
        <div class="container mx-auto px-4">
          <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x">
            <div class="flex items-center gap-4 py-8 md:py-10 md:px-8">
              <div class="shrink-0 w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                <Package class="size-6 text-primary" />
              </div>
              <div>
                <p class="font-semibold text-sm">Productos de calidad</p>
                <p class="text-xs text-muted-foreground">Seleccionados con cuidado para ti</p>
              </div>
            </div>
            <div class="flex items-center gap-4 py-8 md:py-10 md:px-8">
              <div class="shrink-0 w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                <Heart class="size-6 text-primary" />
              </div>
              <div>
                <p class="font-semibold text-sm">Atención personalizada</p>
                <p class="text-xs text-muted-foreground">Te asesoramos en cada compra</p>
              </div>
            </div>
            <div class="flex items-center gap-4 py-8 md:py-10 md:px-8">
              <div class="shrink-0 w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                <Sparkles class="size-6 text-primary" />
              </div>
              <div>
                <p class="font-semibold text-sm">Precios competitivos</p>
                <p class="text-xs text-muted-foreground">Las mejores ofertas del mercado</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══════════════════ ABOUT US ═══════════════════ -->
      <section v-if="settings?.about_us_text" class="py-20 md:py-28">
        <div class="container mx-auto px-4">
          <div class="max-w-3xl mx-auto text-center space-y-6">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-muted text-muted-foreground text-xs font-medium uppercase tracking-wider">
              Sobre nosotros
            </div>
            <h2 class="text-3xl md:text-4xl font-bold tracking-tight">
              Conoce nuestra historia
            </h2>
            <p class="text-muted-foreground text-lg leading-relaxed whitespace-pre-line">
              {{ settings.about_us_text }}
            </p>
          </div>
        </div>
      </section>

      <!-- ═══════════════════ CTA BANNER ═══════════════════ -->
      <section class="py-16">
        <div class="container mx-auto px-4">
          <div class="relative overflow-hidden rounded-2xl bg-primary px-8 py-16 md:px-16 text-center">
            <!-- Decorative circles -->
            <div class="absolute -top-12 -right-12 w-48 h-48 bg-white/10 rounded-full"></div>
            <div class="absolute -bottom-16 -left-16 w-64 h-64 bg-white/5 rounded-full"></div>
            
            <div class="relative z-10 space-y-6 max-w-2xl mx-auto">
              <h2 class="text-2xl md:text-3xl font-bold text-primary-foreground">
                ¿Listo para explorar?
              </h2>
              <p class="text-primary-foreground/80">
                Encuentra todo lo que necesitas en nuestro catálogo de productos.
              </p>
              <RouterLink to="/shop/catalog">
                <Button size="lg" variant="secondary" class="text-base px-8 h-12">
                  Explorar catálogo
                  <ChevronRight class="ml-2 size-5" />
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
        <div class="container mx-auto px-4">
          <div class="max-w-2xl mx-auto text-center space-y-8">
            <div class="space-y-3">
              <h2 class="text-2xl font-bold tracking-tight">Contacto</h2>
              <p class="text-muted-foreground">Estamos aquí para ayudarte.</p>
            </div>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
              <a 
                v-if="settings?.contact_email"
                :href="`mailto:${settings.contact_email}`" 
                class="flex items-center gap-3 px-6 py-3 rounded-xl border bg-card hover:bg-muted/50 transition-colors"
              >
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
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
                class="flex items-center gap-3 px-6 py-3 rounded-xl border bg-card hover:bg-muted/50 transition-colors"
              >
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
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