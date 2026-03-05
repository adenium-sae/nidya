<script setup>
import { computed } from 'vue' // Changed from radix-vue Label to simple label for minimal setup if radix-vue missing or complex
// But we installed radix-vue, let's try to use it if possible.
// Actually, simple label with correct classes is enough for now to avoid dependency hell if Label primitive wasn't fully checked.
// Let's stick to simple HTML label with correct classes for "lite" install.

import { cn } from '@/lib/utils'

const props = defineProps({
  class: { type: null, required: false },
})
</script>

<template>
  <label
    :class="cn(
      'text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70',
      props.class,
    )"
  >
    <slot />
  </label>
</template>
