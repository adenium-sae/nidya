<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { ref, onMounted } from "vue"
import axios from "axios"

import { 
  ChevronRight, 
  ShoppingCart,
  Package,
  Warehouse,
  Users,
  LayoutDashboard,
  Store,
} from "lucide-vue-next"
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/components/ui/collapsible"
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
  SidebarRail,
} from "@/components/ui/sidebar"
import NavUser from "@/components/NavUser.vue"

interface SidebarProps {
  side?: "left" | "right"
  variant?: "sidebar" | "floating" | "inset"
  collapsible?: "offcanvas" | "icon" | "none"
  class?: HTMLAttributes["class"]
}

const props = defineProps<SidebarProps>()

// User data
const user = ref({
  name: '',
  email: '',
  avatar: ''
})

const isLoading = ref(true)

onMounted(async () => {
  try {
    const token = localStorage.getItem('auth_token')
    const response = await axios.get('/api/user', {
      headers: {
        Authorization: `Bearer ${token}`
      }
    })
    
    const userData = response.data
    const profile = userData.profile
    
    // Build full name from profile
    let fullName = userData.email
    if (profile) {
      const nameParts = [profile.first_name]
      if (profile.middle_name) nameParts.push(profile.middle_name)
      if (profile.last_name) nameParts.push(profile.last_name)
      if (profile.second_last_name) nameParts.push(profile.second_last_name)
      fullName = nameParts.join(' ')
    }
    
    user.value = {
      name: fullName,
      email: userData.email,
      avatar: profile?.avatar_url || ''
    }
  } catch (error) {
    console.error('Error fetching user data:', error)
  } finally {
    isLoading.value = false
  }
})

// Navigation data based on database models
const navMain = [
  {
    title: "Dashboard",
    url: "/panel/dashboard",
    icon: LayoutDashboard,
    items: [],
  },
  {
    title: "Ventas",
    url: "#",
    icon: ShoppingCart,
    isOpen: true,
    items: [
      {
        title: "Punto de Venta",
        url: "/panel/sales/pos",
      },
      {
        title: "Historial de Ventas",
        url: "/panel/sales/history",
      },
      {
        title: "Clientes",
        url: "/panel/sales/customers",
      },
      {
        title: "Caja Registradora",
        url: "/panel/sales/cash-register",
      },
      {
        title: "Movimientos de Caja",
        url: "/panel/sales/cash-movements",
      },
    ],
  },
  {
    title: "Compras",
    url: "#",
    icon: Package,
    isOpen: false,
    items: [
      {
        title: "Nueva Compra",
        url: "/panel/purchases/new",
      },
      {
        title: "Historial de Compras",
        url: "/panel/purchases/history",
      },
      {
        title: "Proveedores",
        url: "/panel/purchases/suppliers",
      },
      {
        title: "Órdenes de Compra",
        url: "/panel/purchases/orders",
      },
    ],
  },
  {
    title: "Almacén",
    url: "#",
    icon: Warehouse,
    isOpen: false,
    items: [
      {
        title: "Productos",
        url: "/panel/inventory/products",
      },
      {
        title: "Categorías",
        url: "/panel/inventory/categories",
      },
      {
        title: "Stock",
        url: "/panel/inventory/stock",
      },
      {
        title: "Movimientos",
        url: "/panel/inventory/movements",
      },
      {
        title: "Ajustes de Inventario",
        url: "/panel/inventory/adjustments",
      },
      {
        title: "Almacenes",
        url: "/panel/inventory/warehouses",
      },
    ],
  },
  {
    title: "Usuarios",
    url: "#",
    icon: Users,
    isOpen: false,
    items: [
      {
        title: "Lista de Usuarios",
        url: "/panel/users/list",
      },
      {
        title: "Roles",
        url: "/panel/users/roles",
      },
      {
        title: "Permisos",
        url: "/panel/users/permissions",
      },
    ],
  },
  {
    title: "Organización",
    url: "#",
    icon: Store,
    isOpen: false,
    items: [
      {
        title: "Tiendas",
        url: "/panel/organization/stores",
      },
      {
        title: "Sucursales",
        url: "/panel/organization/branches",
      },
    ],
  },
]
</script>

<template>
  <Sidebar v-bind="props">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <a href="/panel/dashboard">
              <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                <Store class="size-4" />
              </div>
              <div class="flex flex-col gap-0.5 leading-none">
                <span class="font-medium">Nidya</span>
                <span class="text-xs text-muted-foreground">Panel de Control</span>
              </div>
            </a>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>
    <SidebarContent>
      <SidebarGroup>
        <SidebarGroupLabel>Menú Principal</SidebarGroupLabel>
        <SidebarMenu>
          <!-- Dashboard (no subitems) -->
          <SidebarMenuItem>
            <SidebarMenuButton as-child :tooltip="navMain[0].title">
              <a :href="navMain[0].url">
                <component :is="navMain[0].icon" class="size-4" />
                <span>{{ navMain[0].title }}</span>
              </a>
            </SidebarMenuButton>
          </SidebarMenuItem>

          <!-- Collapsible sections -->
          <Collapsible
            v-for="item in navMain.slice(1)"
            :key="item.title"
            as-child
            :default-open="item.isOpen"
            class="group/collapsible"
          >
            <SidebarMenuItem>
              <CollapsibleTrigger as-child>
                <SidebarMenuButton :tooltip="item.title">
                  <component :is="item.icon" class="size-4" />
                  <span>{{ item.title }}</span>
                  <ChevronRight class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                </SidebarMenuButton>
              </CollapsibleTrigger>
              <CollapsibleContent>
                <SidebarMenuSub v-if="item.items?.length">
                  <SidebarMenuSubItem v-for="childItem in item.items" :key="childItem.title">
                    <SidebarMenuSubButton as-child>
                      <a :href="childItem.url">{{ childItem.title }}</a>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                </SidebarMenuSub>
              </CollapsibleContent>
            </SidebarMenuItem>
          </Collapsible>
        </SidebarMenu>
      </SidebarGroup>
    </SidebarContent>
    <SidebarFooter>
      <NavUser v-if="!isLoading" :user="user" />
    </SidebarFooter>
    <SidebarRail />
  </Sidebar>
</template>
