<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue'
import { ArrowLeft } from 'lucide-vue-next'
import { useRouter } from 'vue-router'

const props = defineProps({
  title: { type: String },
  description: { type: String, default: '' },
  showBack: { type: Boolean, default: false },
})

const router = useRouter()
</script>

<template>
  <div class="flex items-center justify-between flex-shrink-0">
    <div class="flex items-center gap-4">
      <Button v-if="showBack" variant="ghost" size="icon" @click="router.back()">
        <ArrowLeft class="h-4 w-4" />
      </Button>
      <div>
        <h1 v-if="title" class="text-3xl font-bold tracking-tight">{{ title }}</h1>
        <p v-if="description" class="text-muted-foreground">{{ description }}</p>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>
