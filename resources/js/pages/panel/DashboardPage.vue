<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { dashboardApi } from '@/api/dashboard.api';
import { useFormatters } from '@/composables/useFormatters';
import { useI18n } from 'vue-i18n';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  DollarSign,
  Package,
  Users,
  ShoppingCart,
} from 'lucide-vue-next';
import { VisXYContainer, VisLine, VisAxis, VisArea } from '@unovis/vue';

const { t } = useI18n();
const { formatCurrency, formatNumber } = useFormatters();
const isLoading = ref(true);
const stats = ref({
  total_sales: 0,
  total_products: 0,
  total_customers: 0,
  total_categories: 0,
  recent_sales: [] as any[],
  top_products: [] as any[],
  sales_by_day: [] as any[],
});

async function fetchStats() {
  isLoading.value = true;
  try {
    const response = await dashboardApi.getStats();
    stats.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching dashboard stats:', error);
  } finally {
    isLoading.value = false;
  }
}

const chartData = computed(() => {
  return stats.value.sales_by_day.map((item: any, index: number) => ({
    x: index,
    y: parseFloat(item.total) || 0,
    label: item.date,
  }));
});

const x = (d: any) => d.x;
const y = (d: any) => d.y;

onMounted(() => fetchStats());
</script>

<template>
  <div class="flex flex-col gap-8">
    <div>
      <h1 class="text-3xl font-bold tracking-tight">{{ t('dashboard.title') }}</h1>
      <p class="text-muted-foreground">{{ t('dashboard.subtitle') }}</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">{{ t('dashboard.total_sales') }}</CardTitle>
          <DollarSign class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ formatCurrency(stats.total_sales) }}</div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">{{ t('dashboard.products') }}</CardTitle>
          <Package class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ formatNumber(stats.total_products) }}</div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">{{ t('dashboard.customers') }}</CardTitle>
          <Users class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ formatNumber(stats.total_customers) }}</div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">{{ t('dashboard.categories') }}</CardTitle>
          <ShoppingCart class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ formatNumber(stats.total_categories) }}</div>
        </CardContent>
      </Card>
    </div>

    <!-- Charts -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
      <Card class="col-span-4">
        <CardHeader>
          <CardTitle>{{ t('dashboard.sales_by_day') }}</CardTitle>
          <CardDescription>{{ t('dashboard.sales_by_day') }}</CardDescription>
        </CardHeader>
        <CardContent class="pl-2">
          <div class="h-[300px]" v-if="chartData.length > 0">
            <VisXYContainer :data="chartData" :height="280">
              <VisArea :x="x" :y="y" color="#8884d8" :opacity="0.15" />
              <VisLine :x="x" :y="y" color="#8884d8" />
              <VisAxis type="x" />
              <VisAxis type="y" />
            </VisXYContainer>
          </div>
          <div v-else class="h-[300px] flex items-center justify-center text-muted-foreground">
            {{ t('common.no_results') }}
          </div>
        </CardContent>
      </Card>

      <Card class="col-span-3">
        <CardHeader>
          <CardTitle>{{ t('dashboard.top_products') }}</CardTitle>
          <CardDescription>{{ t('dashboard.top_products') }}</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-if="stats.top_products.length === 0" class="text-center text-muted-foreground py-8">
              {{ t('common.no_results') }}
            </div>
            <div v-for="product in stats.top_products" :key="product.id" class="flex items-center">
              <div class="space-y-1 flex-1 min-w-0">
                <p class="text-sm font-medium leading-none truncate">{{ product.name }}</p>
                <p class="text-sm text-muted-foreground">
                  {{ product.total_sold || product.quantity_sold }} vendidos
                </p>
              </div>
              <div class="ml-auto font-medium">
                {{ formatCurrency(product.total_revenue || product.revenue) }}
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
