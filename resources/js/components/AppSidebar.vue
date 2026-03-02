<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { computed } from "vue"
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { useI18n } from 'vue-i18n'

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

const { t } = useI18n()

interface SidebarProps {
  side?: "left" | "right"
  variant?: "sidebar" | "floating" | "inset"
  collapsible?: "offcanvas" | "icon" | "none"
  class?: HTMLAttributes["class"]
}

const props = defineProps<SidebarProps>()

// User data from auth store (no more duplicate API call)
const authStore = useAuthStore()

const user = computed(() => ({
  name: authStore.fullName,
  email: authStore.email,
  avatar: authStore.avatarUrl,
}))

const isLoading = computed(() => authStore.isLoadingUser)

// Navigation data based on database models
const navMain = computed(() => [
  {
    title: t('sidebar.dashboard'),
    url: "/panel/dashboard",
    icon: LayoutDashboard,
    items: [],
  },
  {
    title: t('sidebar.sales'),
    url: "#",
    icon: ShoppingCart,
    isOpen: true,
    items: [
      {
        title: t('sidebar.pos'),
        url: "/panel/sales/pos",
      },
      {
        title: t('sidebar.history'),
        url: "/panel/sales/history",
      },
      {
        title: t('dashboard.customers'),
        url: "/panel/sales/customers",
      },
      {
        title: "Caja Registradora",
        url: "/panel/sales/cash-register",
      },
      {
        title: t('sidebar.movements'),
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
        title: t('sidebar.history'),
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
    title: t('sidebar.inventory'),
    url: "#",
    icon: Warehouse,
    isOpen: false,
    items: [
      {
        title: t('dashboard.products'),
        url: "/panel/inventory/products",
      },
      {
        title: t('dashboard.categories'),
        url: "/panel/inventory/categories",
      },
      {
        title: t('sidebar.stock'),
        url: "/panel/inventory/stock",
      },
      {
        title: t('sidebar.movements'),
        url: "/panel/inventory/movements",
      },
      {
        title: t('sidebar.adjustments'),
        url: "/panel/inventory/adjustments",
      },
      {
        title: t('sidebar.transfers'),
        url: "/panel/inventory/transfers",
      },
      {
        title: t('sidebar.warehouses'),
        url: "/panel/inventory/warehouses",
      },
    ],
  },
  {
    title: t('sidebar.users'),
    url: "#",
    icon: Users,
    isOpen: false,
    items: [
      {
        title: "Lista de Usuarios",
        url: "/panel/users/list",
      },
      {
        title: t('sidebar.roles'),
        url: "/panel/users/roles",
      },
      {
        title: t('sidebar.permissions'),
        url: "/panel/users/permissions",
      },
    ],
  },
  {
    title: t('sidebar.organization'),
    url: "#",
    icon: Store,
    isOpen: false,
    items: [
      {
        title: t('sidebar.stores'),
        url: "/panel/organization/stores",
      },
      {
        title: t('sidebar.branches'),
        url: "/panel/organization/branches",
      },
    ],
  },
])
</script>

<template>
  <Sidebar v-bind="props">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <RouterLink to="/panel/dashboard">
              <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                <Store class="size-4" />
              </div>
              <div class="flex flex-col gap-0.5 leading-none">
                <span class="font-medium">Nidya</span>
                <span class="text-xs text-muted-foreground">Panel de Control</span>
              </div>
            </RouterLink>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>
    <SidebarContent>
      <SidebarGroup>
        <SidebarGroupLabel>{{ t('sidebar.main_menu') }}</SidebarGroupLabel>
        <SidebarMenu>
          <!-- Dashboard (no subitems) -->
          <SidebarMenuItem>
            <SidebarMenuButton as-child :tooltip="navMain[0].title">
              <RouterLink :to="navMain[0].url">
                <component :is="navMain[0].icon" class="size-4" />
                <span>{{ navMain[0].title }}</span>
              </RouterLink>
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
                      <RouterLink :to="childItem.url">{{ childItem.title }}</RouterLink>
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
