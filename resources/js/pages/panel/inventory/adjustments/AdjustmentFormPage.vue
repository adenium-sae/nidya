<script setup>
import StockAdjustmentForm from '@/components/inventory/StockAdjustmentForm.vue';

const props = defineProps({
  mode: {
    type: String,
    required: true,
    validator: (v) => ['entry', 'exit', 'adjustment'].includes(v),
  },
});
</script>

<template>
  <StockAdjustmentForm :mode="mode" />
</template>
