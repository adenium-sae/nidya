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
import { AreaChart } from '@/components/ui/chart';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Skeleton } from '@/components/ui/skeleton';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  DollarSign,
  Package,
  Users,
  AlertTriangle,
  Activity,
} from 'lucide-vue-next';
import { VisXYContainer, VisLine, VisAxis, VisArea } from '@unovis/vue';

const { t } = useI18n();
const { formatCurrency, formatNumber, formatDateTime } = useFormatters();
const isLoading = ref(true);
const period = ref('7d');

const stats = ref({
  total_sales: 0,
  today_sales: 0,
  total_products: 0,
  total_customers: 0,
  total_categories: 0,
  low_stock_count: 0,
  sales_by_day: [] as any[],
  top_products: [] as any[],
  recent_activity: [] as any[],
});

async function fetchStats() {
  isLoading.value = true;
  try {
    const response = await dashboardApi.getStats(period.value);
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

// Using Unovis tick formatter for x axis
const formatTick = (val: number) => {
  return chartData.value[val]?.label || '';
}

const x = (d: any) => d.x;
const y = (d: any) => d.y;

onMounted(() => fetchStats());

import { watch } from 'vue';
watch(period, () => fetchStats());
</script>

<template>
  <div class="flex flex-col gap-8">
    <!-- Stats Cards -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">{{ t('dashboard.month_sales') }}</CardTitle>
          <DollarSign class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div v-if="isLoading" class="h-8 w-24">
            <Skeleton class="h-full w-full" />
          </div>
          <div v-else class="text-2xl font-bold">{{ formatCurrency(stats.total_sales) }}</div>
          <p v-if="!isLoading" class="text-xs text-muted-foreground mt-1">
            {{ t('dashboard.today_sales') }}: {{ formatCurrency(stats.today_sales) }}
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">{{ t('dashboard.products') }}</CardTitle>
          <Package class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div v-if="isLoading" class="h-8 w-16">
            <Skeleton class="h-full w-full" />
          </div>
          <div v-else class="text-2xl font-bold">{{ formatNumber(stats.total_products) }}</div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">{{ t('dashboard.customers') }}</CardTitle>
          <Users class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div v-if="isLoading" class="h-8 w-16">
            <Skeleton class="h-full w-full" />
          </div>
          <div v-else class="text-2xl font-bold">{{ formatNumber(stats.total_customers) }}</div>
        </CardContent>
      </Card>

      <Card :class="stats.low_stock_count > 0 ? 'border-destructive' : ''">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium" :class="stats.low_stock_count > 0 ? 'text-destructive' : ''">
            {{ t('dashboard.low_stock') }}
          </CardTitle>
          <AlertTriangle class="h-4 w-4" :class="stats.low_stock_count > 0 ? 'text-destructive' : 'text-muted-foreground'" />
        </CardHeader>
        <CardContent>
          <div v-if="isLoading" class="h-8 w-16">
            <Skeleton class="h-full w-full" />
          </div>
          <div v-else class="text-2xl font-bold" :class="stats.low_stock_count > 0 ? 'text-destructive' : ''">
            {{ formatNumber(stats.low_stock_count) }}
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Charts -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
      <Card class="col-span-4">
        <CardHeader class="flex flex-row items-center justify-between space-y-0">
          <CardTitle>{{ t('dashboard.sales_summary') }} - {{ t(`dashboard.periods.${period}`) }}</CardTitle>
          <Select v-model="period">
            <SelectTrigger class="w-[180px]">
              <SelectValue :placeholder="t('common.select')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="today">{{ t('dashboard.periods.today') }}</SelectItem>
              <SelectItem value="7d">{{ t('dashboard.periods.7d') }}</SelectItem>
              <SelectItem value="30d">{{ t('dashboard.periods.30d') }}</SelectItem>
              <SelectItem value="year">{{ t('dashboard.periods.year') }}</SelectItem>
            </SelectContent>
          </Select>
        </CardHeader>
        <CardContent class="px-2">
          <div v-if="isLoading" class="h-[300px] w-full p-4">
            <Skeleton class="h-full w-full" />
          </div>
          <div v-else-if="chartData.length > 0" class="h-[300px]">
            <AreaChart
              :data="chartData"
              index="label"
              :categories="['y']"
              :x-formatter="(tick: number | Date) => chartData[tick as number]?.label"
              :y-formatter="(val: number | Date) => formatCurrency(val as number)"
              :show-legend="false"
              :margin="{ top: 10, bottom: 30, left: 80, right: 20 }"
              class="h-[280px]"
            />
          </div>
          <div v-else class="h-[300px] flex items-center justify-center text-muted-foreground">
            {{ t('common.no_results') }}
          </div>
        </CardContent>
      </Card>

      <Card class="col-span-3">
        <CardHeader>
          <CardTitle>{{ t('dashboard.top_products') }}</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="isLoading" class="space-y-4">
            <Skeleton v-for="i in 5" :key="i" class="h-10 w-full" />
          </div>
          <div v-else class="space-y-4">
            <div v-if="stats.top_products.length === 0" class="text-center text-muted-foreground py-8">
              {{ t('common.no_results') }}
            </div>
            <div v-for="product in stats.top_products" :key="product.id" class="flex items-center">
              <div class="space-y-1 flex-1 min-w-0 pr-4">
                <p class="text-sm font-medium leading-none truncate" :title="product.name">{{ product.name }}</p>
                <p class="text-sm text-muted-foreground">
                  {{ formatNumber(product.total_sold) }} vendidos
                </p>
              </div>
              <div class="ml-auto font-medium">
                {{ formatCurrency(product.total_revenue) }}
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Recent Activity -->
    <Card>
      <CardHeader>
        <div class="flex items-center justify-between">
          <CardTitle class="flex items-center gap-2">
            <Activity class="h-5 w-5" />
            {{ t('dashboard.recent_activity') }}
          </CardTitle>
        </div>
      </CardHeader>
      <CardContent>
        <div v-if="isLoading" class="space-y-4">
           <Skeleton v-for="i in 3" :key="i" class="h-12 w-full" />
        </div>
        <Table v-else>
          <TableHeader>
            <TableRow>
              <TableHead>{{ t('common.date') }}</TableHead>
              <TableHead>{{ t('common.user') }}</TableHead>
              <TableHead>{{ t('common.type') }}</TableHead>
              <TableHead>{{ t('common.description') }}</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-if="stats.recent_activity.length === 0">
              <TableCell colspan="4" class="text-center text-muted-foreground py-8">
                {{ t('common.no_results') }}
              </TableCell>
            </TableRow>
            <TableRow v-for="log in stats.recent_activity" :key="log.id">
              <TableCell class="whitespace-nowrap">{{ formatDateTime(log.created_at) }}</TableCell>
              <TableCell>{{ log.user }}</TableCell>
              <TableCell>
                <span class="inline-flex items-center rounded-md bg-muted px-2 py-1 text-xs font-medium ring-1 ring-inset ring-muted-foreground/20 uppercase">
                  {{ t(`activity.types.${log.type}`) }}
                </span>
              </TableCell>
              <TableCell>{{ log.description }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
    </Card>

  </div>
</template>
