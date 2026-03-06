<script setup lang="ts">
import { Toaster } from '@/components/ui/toast'
import { useBranding } from '@/composables/useBranding'

useBranding()
</script>

<template>
    <RouterView />
    <Toaster />
</template>