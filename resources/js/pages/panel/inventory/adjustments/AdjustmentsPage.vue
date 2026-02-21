<script setup lang="ts">
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { stockApi } from '@/api/stock.api';
import { useApiList } from '@/composables/useApiList';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  TrendingUp,
  TrendingDown,
  Settings2,
  ArrowRightLeft,
} from 'lucide-vue-next';

import { StockAdjustment } from '@/types/models';

const router = useRouter();

const {
  items: adjustments,
  isLoading,
  fetch: fetchAdjustments,
} = useApiList<StockAdjustment>(stockApi.adjustments);

function getAdjustmentLabel(type: string): string {
  const labels: Record<string, string> = {
    increase: 'Entrada',
    decrease: 'Salida',
    adjustment: 'Ajuste Directo',
    recount: 'Recuento',
  };
  return labels[type] || type;
}

function getAdjustmentClass(type: string): string {
  const classes: Record<string, string> = {
    increase: 'bg-green-100 text-green-800',
    decrease: 'bg-red-100 text-red-800',
    adjustment: 'bg-blue-100 text-blue-800',
    recount: 'bg-yellow-100 text-yellow-800',
  };
  return classes[type] || 'bg-gray-100 text-gray-800';
}

onMounted(() => fetchAdjustments());
</script>

<template>
  <div class="flex flex-col gap-8">
    <div>
      <h1 class="text-3xl font-bold tracking-tight">Ajustes de Inventario</h1>
      <p class="text-muted-foreground">Gestiona las entradas, salidas y correcciones de stock.</p>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <Card class="hover:border-primary transition-colors cursor-pointer" @click="router.push('/panel/inventory/adjustments/entry')">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Nueva Entrada</CardTitle>
          <TrendingUp class="h-4 w-4 text-green-500" />
        </CardHeader>
        <CardContent>
          <p class="text-xs text-muted-foreground">Registra el ingreso de mercancía.</p>
        </CardContent>
      </Card>

      <Card class="hover:border-primary transition-colors cursor-pointer" @click="router.push('/panel/inventory/adjustments/exit')">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Nueva Salida</CardTitle>
          <TrendingDown class="h-4 w-4 text-red-500" />
        </CardHeader>
        <CardContent>
          <p class="text-xs text-muted-foreground">Registra la baja de mercancía.</p>
        </CardContent>
      </Card>

      <Card class="hover:border-primary transition-colors cursor-pointer" @click="router.push('/panel/inventory/adjustments/new-adjustment')">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Nuevo Ajuste</CardTitle>
          <Settings2 class="h-4 w-4 text-blue-500" />
        </CardHeader>
        <CardContent>
          <p class="text-xs text-muted-foreground">Corrige el stock directamente.</p>
        </CardContent>
      </Card>

      <Card class="hover:border-primary transition-colors cursor-pointer" @click="router.push('/panel/inventory/adjustments/transfer')">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Transferencia</CardTitle>
          <ArrowRightLeft class="h-4 w-4 text-purple-500" />
        </CardHeader>
        <CardContent>
          <p class="text-xs text-muted-foreground">Mueve stock entre almacenes.</p>
        </CardContent>
      </Card>
    </div>

    <!-- History Table -->
    <Card>
      <CardHeader>
        <CardTitle>Historial de Ajustes</CardTitle>
        <CardDescription>Resumen de los últimos movimientos realizados.</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="rounded-md border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Folio</TableHead>
                <TableHead>Fecha</TableHead>
                <TableHead>Tipo</TableHead>
                <TableHead>Almacén</TableHead>
                <TableHead>Productos</TableHead>
                <TableHead>Usuario</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="adjustments.length === 0 && !isLoading">
                <TableCell colspan="6" class="text-center h-24 text-muted-foreground">
                  No hay ajustes registrados.
                </TableCell>
              </TableRow>
              <TableRow v-for="adj in adjustments" :key="adj.id">
                <TableCell class="font-mono text-xs">{{ adj.folio }}</TableCell>
                <TableCell>{{ new Date(adj.created_at).toLocaleDateString() }}</TableCell>
                <TableCell>
                  <span
                    :class="getAdjustmentClass(adj.type)"
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                  >
                    {{ getAdjustmentLabel(adj.type) }}
                  </span>
                </TableCell>
                <TableCell>{{ adj.warehouse?.name }}</TableCell>
                <TableCell>
                  <div class="max-w-[300px] truncate">
                    {{ adj.items?.map((i: any) => i.product?.name).join(', ') }}
                  </div>
                </TableCell>
                <TableCell class="text-xs">{{ adj.user?.email }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
