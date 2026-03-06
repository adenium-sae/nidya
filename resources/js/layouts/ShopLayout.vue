<script setup lang="ts">
import { RouterLink, useRoute } from 'vue-router'
import { computed, ref, watch } from 'vue'
import { Store, Menu, X, Home, LayoutGrid } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { useBranding } from '@/composables/useBranding'

const route = useRoute()
const mobileMenuOpen = ref(false)
const { branding } = useBranding()

const storeName = computed(() => branding.value?.display_name || 'Nidya')

const navLinks = [
  { label: 'Inicio', to: '/shop/home', icon: Home },
  { label: 'Catálogo', to: '/shop/catalog', icon: LayoutGrid },
]

const isActive = (path: string) => {
  if (path === '/shop/catalog') {
    return route.path.startsWith('/shop/catalog')
  }
  return route.path === path
}

// Close mobile menu on route change
watch(() => route.path, () => {
  mobileMenuOpen.value = false
})
</script>

<template>
  <div class="min-h-screen flex flex-col bg-white">
    <!-- Header -->
    <header class="sticky top-0 z-50 w-full border-b bg-white/80 backdrop-blur-lg supports-[backdrop-filter]:bg-white/60">
      <div class="container mx-auto flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
        
        <!-- Logo -->
        <RouterLink to="/shop/home" class="flex items-center gap-2.5 group">
          <template v-if="branding?.logo_url">
            <img :src="branding.logo_url" :alt="storeName" class="h-8 w-auto object-contain transition-transform duration-200 group-hover:scale-105" />
          </template>
          <template v-else>
            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary text-primary-foreground transition-transform duration-200 group-hover:scale-105">
              <Store class="size-4" />
            </div>
          </template>
          <!-- <div class="flex items-baseline gap-1.5">
            <span class="text-lg font-bold tracking-tight text-foreground">{{ storeName }}</span>
          </div> -->
        </RouterLink>

        <!-- Desktop Nav -->
        <nav class="hidden md:flex items-center gap-1">
          <RouterLink 
            v-for="link in navLinks" 
            :key="link.to"
            :to="link.to" 
            class="relative px-4 py-2 rounded-md text-sm font-medium transition-colors"
            :class="isActive(link.to) 
              ? 'text-primary' 
              : 'text-muted-foreground hover:text-foreground hover:bg-muted/50'"
          >
            {{ link.label }}
            <!-- Active indicator -->
            <span 
              v-if="isActive(link.to)" 
              class="absolute bottom-0 left-1/2 -translate-x-1/2 w-4 h-0.5 bg-primary rounded-full"
            ></span>
          </RouterLink>
        </nav>

        <!-- Mobile Menu Toggle -->
        <Button 
          variant="ghost" 
          size="icon" 
          class="md:hidden h-9 w-9" 
          @click="mobileMenuOpen = !mobileMenuOpen"
        >
          <Transition name="rotate" mode="out-in">
            <X v-if="mobileMenuOpen" class="size-5" :key="'close'" />
            <Menu v-else class="size-5" :key="'menu'" />
          </Transition>
        </Button>
      </div>

      <!-- Mobile Nav -->
      <Transition name="slideDown">
        <div 
          v-if="mobileMenuOpen" 
          class="md:hidden border-t bg-white px-4 py-3 space-y-0.5"
        >
          <RouterLink 
            v-for="link in navLinks" 
            :key="link.to"
            :to="link.to" 
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors"
            :class="isActive(link.to) 
              ? 'bg-primary/10 text-primary' 
              : 'text-muted-foreground hover:text-foreground hover:bg-muted/50'"
            @click="mobileMenuOpen = false"
          >
            <component :is="link.icon" class="size-4" />
            {{ link.label }}
          </RouterLink>
        </div>
      </Transition>
    </header>

    <!-- Main -->
    <main class="flex-1">
      <slot />
    </main>

    <!-- Global Footer -->
    <footer class="border-t bg-muted/20">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
          <!-- Brand -->
          <div class="flex items-center gap-2.5">
            <template v-if="branding?.logo_url">
              <img :src="branding.logo_url" :alt="storeName" class="h-7 w-auto object-contain" />
            </template>
            <template v-else>
              <div class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary text-primary-foreground">
                <Store class="size-3.5" />
              </div>
            </template>
            <span class="text-sm font-semibold text-foreground">{{ storeName }}</span>
          </div>

          <!-- Nav Links -->
          <nav class="flex items-center gap-6">
            <RouterLink 
              v-for="link in navLinks" 
              :key="link.to"
              :to="link.to"
              class="text-sm text-muted-foreground hover:text-foreground transition-colors"
            >
              {{ link.label }}
            </RouterLink>
          </nav>
        </div>
        
        <Separator class="my-6" />
        
        <p class="text-xs text-muted-foreground text-center md:text-left">
          &copy; {{ new Date().getFullYear() }} {{ storeName }}. Todos los derechos reservados.
        </p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
/* Slide down animation for mobile menu */
.slideDown-enter-active {
  transition: all 0.2s ease-out;
}
.slideDown-leave-active {
  transition: all 0.15s ease-in;
}
.slideDown-enter-from {
  opacity: 0;
  transform: translateY(-8px);
}
.slideDown-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

/* Rotate animation for menu icon swap */
.rotate-enter-active {
  transition: all 0.15s ease-out;
}
.rotate-leave-active {
  transition: all 0.1s ease-in;
}
.rotate-enter-from {
  opacity: 0;
  transform: rotate(-90deg) scale(0.8);
}
.rotate-leave-to {
  opacity: 0;
  transform: rotate(90deg) scale(0.8);
}
</style>