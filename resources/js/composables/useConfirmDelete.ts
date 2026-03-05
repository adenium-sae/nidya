import { ref } from 'vue'
import { useToast } from '@/components/ui/toast/use-toast'
import { useI18n } from 'vue-i18n'

interface ConfirmDeleteOptions {
  successMessage?: string;
  errorMessage?: string;
  onSuccess?: (item: any) => void;
}

export function useConfirmDelete(deleteFn: (id: string | number) => Promise<any>, options: ConfirmDeleteOptions = {}) {
  const { toast } = useToast()
  const { t } = useI18n()

  const isOpen = ref(false)
  const isDeleting = ref(false)
  const itemToDelete = ref<any>(null)

  function openDialog(item: any) {
    itemToDelete.value = item
    isOpen.value = true
  }

  function closeDialog() {
    isOpen.value = false
    itemToDelete.value = null
  }

  async function confirmDelete() {
    if (!itemToDelete.value) return

    isDeleting.value = true
    try {
      await deleteFn(itemToDelete.value.id)
      toast({
        title: t('common.success'),
        description: options.successMessage || t('common.success'),
      })
      if (options.onSuccess) {
        options.onSuccess(itemToDelete.value)
      }
      closeDialog()
    } catch (error: any) {
      console.error('Error deleting:', error)
      const msg =
        error.response?.data?.message ||
        options.errorMessage ||
        t('common.error')
      toast({
        title: t('common.error'),
        description: msg,
        variant: 'destructive',
      })
    } finally {
      isDeleting.value = false
    }
  }

  return {
    isOpen,
    isDeleting,
    itemToDelete,
    openDialog,
    closeDialog,
    confirmDelete,
  }
}
