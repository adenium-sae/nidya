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
  ArrowRightLeft,
  CheckCircle,
  Settings2,
} from 'lucide-vue-next';
import { useToast } from '@/components/ui/toast/use-toast';
import Button from '@/components/ui/button/Button.vue';

import { StockAdjustment } from '@/types/models';

const router = useRouter();
const { toast } = useToast();

const statusLabels: Record<string, string> = {
  pending: 'Pendiente',
  completed: 'Completado',
  cancelled: 'Cancelado',
};

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

function getStatusClass(status: string): string {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
}

async function confirmAdjustment(adj: any) {
  try {
    if (adj.type === 'transfer') {
        // Find transfer ID if it's a transfer adjustment
        // Actually adjustments list might not include transfers if they were created via StockTransfer
        // I should check if StockAdjustment can be a transfer.
        // Based on my migration, I only added status to stock_adjustments and stock_movements.
        await stockApi.confirmAdjustment(adj.id);
    } else {
        await stockApi.confirmAdjustment(adj.id);
    }
    toast({ title: 'Éxito', description: 'Ajuste confirmado correctamente.' });
    fetchAdjustments();
  } catch (error) {
    toast({ title: 'Error', description: 'No se pudo confirmar el ajuste.', variant: 'destructive' });
  }
}

onMounted(() => fetchAdjustments());
</script>

<template>
  <div class="flex flex-col gap-8">
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
                <TableCell>
                    <span
                      :class="getStatusClass(adj.status)"
                      class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                    >
                      {{ statusLabels[adj.status] || adj.status }}
                    </span>
                 </TableCell>
                 <TableCell class="text-xs">{{ adj.user?.email }}</TableCell>
                 <TableCell class="text-right">
                    <Button
                        v-if="adj.status === 'pending'"
                        variant="ghost"
                        size="sm"
                        class="h-8 px-2 text-green-600 hover:text-green-700 hover:bg-green-50"
                        @click="confirmAdjustment(adj)"
                    >
                        <CheckCircle class="h-4 w-4 mr-1" />
                        Confirmar
                    </Button>
                 </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
