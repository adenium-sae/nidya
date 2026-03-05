<script setup lang="ts">
import { RouterLink, useRoute } from 'vue-router'
import { computed } from 'vue'
import { Store, Menu, X } from 'lucide-vue-next'
import { ref } from 'vue'
import { Button } from '@/components/ui/button'

const route = useRoute()
const mobileMenuOpen = ref(false)

const navLinks = [
  { label: 'Inicio', to: '/shop/home' },
  { label: 'Catálogo', to: '/shop/catalog' },
]

const isActive = (path: string) => route.path === path
</script>

<template>
  <div class="min-h-screen flex flex-col bg-background">
    <!-- Header -->
    <header class="sticky top-0 z-50 w-full border-b bg-background/80 backdrop-blur-md supports-[backdrop-filter]:bg-background/60">
      <div class="container mx-auto flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
        
        <!-- Logo -->
        <RouterLink to="/shop/home" class="flex items-center gap-2.5 group">
          <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary text-primary-foreground">
            <Store class="size-4" />
          </div>
          <div class="flex items-baseline gap-1.5">
            <span class="text-lg font-bold tracking-tight text-foreground">Nidya</span>
            <span class="text-xs font-medium text-muted-foreground uppercase tracking-widest">Shop</span>
          </div>
        </RouterLink>

        <!-- Desktop Nav -->
        <nav class="hidden md:flex items-center gap-1">
          <RouterLink 
            v-for="link in navLinks" 
            :key="link.to"
            :to="link.to" 
            class="px-4 py-2 rounded-md text-sm font-medium transition-colors"
            :class="isActive(link.to) 
              ? 'bg-primary/10 text-primary' 
              : 'text-muted-foreground hover:text-foreground hover:bg-muted/50'"
          >
            {{ link.label }}
          </RouterLink>
        </nav>

        <!-- Mobile Menu Toggle -->
        <Button 
          variant="ghost" 
          size="sm" 
          class="md:hidden" 
          @click="mobileMenuOpen = !mobileMenuOpen"
        >
          <X v-if="mobileMenuOpen" class="size-5" />
          <Menu v-else class="size-5" />
        </Button>
      </div>

      <!-- Mobile Nav -->
      <div 
        v-if="mobileMenuOpen" 
        class="md:hidden border-t bg-background px-4 py-4 space-y-1"
      >
        <RouterLink 
          v-for="link in navLinks" 
          :key="link.to"
          :to="link.to" 
          class="block px-4 py-2.5 rounded-md text-sm font-medium transition-colors"
          :class="isActive(link.to) 
            ? 'bg-primary/10 text-primary' 
            : 'text-muted-foreground hover:text-foreground hover:bg-muted/50'"
          @click="mobileMenuOpen = false"
        >
          {{ link.label }}
        </RouterLink>
      </div>
    </header>

    <!-- Main (no container – let each page define its own width) -->
    <main class="flex-1">
      <slot />
    </main>

    <!-- Global Footer -->
    <footer class="border-t bg-muted/30">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
          <div class="flex items-center gap-2.5">
            <div class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary text-primary-foreground">
              <Store class="size-3.5" />
            </div>
            <span class="text-sm font-semibold text-foreground">Nidya Shop</span>
          </div>
          <p class="text-sm text-muted-foreground">
            &copy; {{ new Date().getFullYear() }} Todos los derechos reservados.
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>