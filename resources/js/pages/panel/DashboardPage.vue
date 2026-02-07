<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  DollarSign,
  TrendingUp,
  TrendingDown,
  Package,
  AlertTriangle,
  Users,
} from 'lucide-vue-next'

interface DashboardStats {
  todaySales: number
  monthSales: number
  salesChange: number
  totalProducts: number
  lowStockProducts: number
  totalCustomers: number
}

interface SalesByDay {
  date: string
  total: number
  count: number
}

interface TopProduct {
  name: string
  quantity: number
  total: number
}

const stats = ref<DashboardStats>({
  todaySales: 0,
  monthSales: 0,
  salesChange: 0,
  totalProducts: 0,
  lowStockProducts: 0,
  totalCustomers: 0,
})

const salesByDay = ref<SalesByDay[]>([])
const topProducts = ref<TopProduct[]>([])
const isLoading = ref(true)

function formatCurrency(value: number) {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
  }).format(value);
}

const maxSalesByDay = computed(function() {
  if (salesByDay.value.length === 0) return 1;
  return Math.max(...salesByDay.value.map(s => s.total), 1);
});

const maxTopProductTotal = computed(function() {
  if (topProducts.value.length === 0) return 1;
  return Math.max(...topProducts.value.map(p => p.total), 1);
});

onMounted(async function() {
  try {
    const token = localStorage.getItem('auth_token');
    const response = await axios.get('/api/dashboard', {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    stats.value = response.data.stats;
    salesByDay.value = response.data.salesByDay;
    topProducts.value = response.data.topProducts;
  } catch (error) {
    console.error('Error fetching dashboard data:', error);
  } finally {
    isLoading.value = false;
  }
});
</script>

<template>
  <div class="flex flex-col gap-6">
    <!-- Stats Cards -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      <!-- Today's Sales -->
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Ventas de Hoy</CardTitle>
          <DollarSign class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ formatCurrency(stats.todaySales) }}</div>
        </CardContent>
      </Card>

      <!-- Month Sales -->
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Ventas del Mes</CardTitle>
          <component
            :is="stats.salesChange >= 0 ? TrendingUp : TrendingDown"
            :class="[
              'h-4 w-4',
              stats.salesChange >= 0 ? 'text-green-500' : 'text-red-500'
            ]"
          />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ formatCurrency(stats.monthSales) }}</div>
          <p class="text-xs text-muted-foreground">
            <span :class="stats.salesChange >= 0 ? 'text-green-500' : 'text-red-500'">
              {{ stats.salesChange >= 0 ? '+' : '' }}{{ stats.salesChange }}%
            </span>
            vs mes anterior
          </p>
        </CardContent>
      </Card>

      <!-- Products -->
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Productos</CardTitle>
          <Package class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ stats.totalProducts }}</div>
          <p v-if="stats.lowStockProducts > 0" class="text-xs text-amber-500 flex items-center gap-1">
            <AlertTriangle class="h-3 w-3" />
            {{ stats.lowStockProducts }} con stock bajo
          </p>
        </CardContent>
      </Card>

      <!-- Customers -->
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Clientes</CardTitle>
          <Users class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ stats.totalCustomers }}</div>
        </CardContent>
      </Card>
    </div>

    <!-- Charts Row -->
    <div class="grid gap-4 md:grid-cols-2">
      <!-- Sales by Day Chart -->
      <Card>
        <CardHeader>
          <CardTitle>Ventas de la Semana</CardTitle>
          <CardDescription>Últimos 7 días</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="salesByDay.length > 0" class="space-y-3">
            <div
              v-for="day in salesByDay"
              :key="day.date"
              class="flex items-center gap-3"
            >
              <span class="w-10 text-sm text-muted-foreground">{{ day.date }}</span>
              <div class="flex-1 h-8 bg-muted rounded-md overflow-hidden">
                <div
                  class="h-full bg-primary transition-all duration-300"
                  :style="{ width: `${(day.total / maxSalesByDay) * 100}%` }"
                />
              </div>
              <span class="w-24 text-sm text-right font-medium">{{ formatCurrency(day.total) }}</span>
            </div>
          </div>
          <div v-else class="h-48 flex items-center justify-center text-muted-foreground">
            No hay datos de ventas
          </div>
        </CardContent>
      </Card>

      <!-- Top Products -->
      <Card>
        <CardHeader>
          <CardTitle>Productos Más Vendidos</CardTitle>
          <CardDescription>Este mes</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="topProducts.length > 0" class="space-y-3">
            <div
              v-for="(product, index) in topProducts"
              :key="product.name"
              class="flex items-center gap-3"
            >
              <span class="w-6 h-6 rounded-full bg-primary/10 text-primary text-xs flex items-center justify-center font-medium">
                {{ index + 1 }}
              </span>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">{{ product.name }}</p>
                <div class="h-2 bg-muted rounded-full overflow-hidden mt-1">
                  <div
                    class="h-full bg-primary/60 transition-all duration-300"
                    :style="{ width: `${(product.total / maxTopProductTotal) * 100}%` }"
                  />
                </div>
              </div>
              <div class="text-right">
                <p class="text-sm font-medium">{{ formatCurrency(product.total) }}</p>
                <p class="text-xs text-muted-foreground">{{ product.quantity }} uds</p>
              </div>
            </div>
          </div>
          <div v-else class="h-48 flex items-center justify-center text-muted-foreground">
            No hay datos de productos
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
