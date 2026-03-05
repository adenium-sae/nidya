<script setup lang="ts">
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button, type buttonVariants } from '@/components/ui/button'
import { useI18n } from 'vue-i18n'
import type { VariantProps } from 'class-variance-authority'

const { t } = useI18n()

interface Props {
  open: boolean
  title?: string | null
  description?: string | null
  confirmText?: string | null
  cancelText?: string | null
  loadingText?: string | null
  loading?: boolean
  variant?: VariantProps<typeof buttonVariants>['variant']
}

const props = withDefaults(defineProps<Props>(), {
  title: null,
  description: null,
  confirmText: null,
  cancelText: null,
  loadingText: null,
  loading: false,
  variant: 'destructive',
})

const emit = defineEmits(['update:open', 'confirm'])

function handleClose() {
  if (!props.loading) {
    emit('update:open', false)
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="handleClose">
    <DialogContent class="sm:max-w-[425px]">
      <DialogHeader>
        <DialogTitle>{{ title || t('common.confirm') }}</DialogTitle>
        <DialogDescription>
          <slot>{{ description || (t ? t('common.no_results') : '') }}</slot>
        </DialogDescription>
      </DialogHeader>
      <DialogFooter>
        <Button variant="outline" @click="handleClose" :disabled="loading">
          {{ cancelText || t('common.cancel') }}
        </Button>
        <Button :variant="variant" @click="$emit('confirm')" :disabled="loading">
          {{ loading ? (loadingText || t('common.saving')) : (confirmText || t('common.confirm')) }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
