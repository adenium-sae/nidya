<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppSidebar from "@/components/AppSidebar.vue"
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb"
import { Separator } from "@/components/ui/separator"
import {
  SidebarInset,
  SidebarProvider,
  SidebarTrigger,
} from "@/components/ui/sidebar"

// Route-to-breadcrumb mapping
const breadcrumbLabels: Record<string, string> = {
  'panel': 'Panel',
  'dashboard': 'Dashboard',
  'inventory': 'Almacén',
  'products': 'Productos',
  'categories': 'Categorías',
  'stock': 'Existencias',
  'movements': 'Movimientos',
  'adjustments': 'Ajustes',
  'warehouses': 'Almacenes',
  'entry': 'Nueva Entrada',
  'exit': 'Nueva Salida',
  'new-adjustment': 'Nuevo Ajuste',
  'transfer': 'Transferencia',
  'create': 'Crear',
  'edit': 'Editar',
  'sales': 'Ventas',
  'pos': 'Punto de Venta',
  'history': 'Historial',
  'customers': 'Clientes',
  'cash-register': 'Caja',
  'cash-movements': 'Movimientos de Caja',
  'purchases': 'Compras',
  'new': 'Nueva',
  'suppliers': 'Proveedores',
  'orders': 'Órdenes',
  'users': 'Usuarios',
  'list': 'Lista',
  'roles': 'Roles',
  'permissions': 'Permisos',
  'organization': 'Organización',
  'stores': 'Tiendas',
  'branches': 'Sucursales',
}

const route = useRoute()

const breadcrumbs = computed(() => {
  const segments = route.path.split('/').filter(Boolean)

  // Skip 'panel' as it's implicit, skip UUIDs/IDs
  const isId = (s: string) => /^[0-9a-f-]{8,}$/i.test(s) || /^\d+$/.test(s)
  const relevantSegments = segments.filter(s => s !== 'panel' && !isId(s))

  return relevantSegments.map((segment, index) => {
    const path = '/panel/' + segments.slice(1, segments.indexOf(segment) + 1).join('/')
    return {
      label: breadcrumbLabels[segment] || segment.charAt(0).toUpperCase() + segment.slice(1),
      path,
      isLast: index === relevantSegments.length - 1,
    }
  })
})
</script>

<template>
  <SidebarProvider>
    <AppSidebar />
    <SidebarInset class="overflow-x-hidden w-full max-w-full">
      <header class="flex h-16 shrink-0 items-center gap-2 transition-[width,height] ease-linear group-has-[[data-collapsible=icon]]/sidebar-wrapper:h-12 w-full">
        <div class="flex items-center gap-2 px-4 w-full">
          <SidebarTrigger class="-ml-1 flex-shrink-0" />
          <Separator
            orientation="vertical"
            class="mr-2 h-4 flex-shrink-0"
          />
          <Breadcrumb class="min-w-0 flex-1">
            <BreadcrumbList class="flex-nowrap truncate w-full">
              <template v-for="(crumb, index) in breadcrumbs" :key="crumb.path">
                <BreadcrumbSeparator v-if="index > 0" class="hidden md:block flex-shrink-0" />
                <BreadcrumbItem :class="index < breadcrumbs.length - 1 ? 'hidden md:flex' : 'flex min-w-0'">
                  <BreadcrumbPage v-if="crumb.isLast" class="truncate">{{ crumb.label }}</BreadcrumbPage>
                  <BreadcrumbLink v-else :href="crumb.path" class="truncate">{{ crumb.label }}</BreadcrumbLink>
                </BreadcrumbItem>
              </template>
            </BreadcrumbList>
          </Breadcrumb>
        </div>
      </header>
      <div class="flex flex-1 flex-col gap-4 p-2 sm:p-4 pt-0 w-full min-w-0 overflow-x-hidden">
         <slot />
      </div>
    </SidebarInset>
  </SidebarProvider>
</template>
