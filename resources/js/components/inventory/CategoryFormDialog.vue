<script setup lang="ts">
import { ref, reactive, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { categoriesApi } from '@/api/categories.api';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/components/ui/toast/use-toast';

interface Category {
  id: string;
  name: string;
  description?: string;
  is_active?: boolean;
}

const props = defineProps<{
  open: boolean;
  category?: Category | null;
}>();

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'saved', category: Category): void;
}>();

const { toast } = useToast();
const { t } = useI18n();
const isLoading = ref(false);

const form = reactive({
  name: '',
  description: ''
});

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    if (props.category) {
      form.name = props.category.name;
      form.description = props.category.description || '';
    } else {
      form.name = '';
      form.description = '';
    }
  }
});

function handleClose() {
  emit('update:open', false);
}

async function handleSubmit() {
  if (!form.name.trim()) return;

  isLoading.value = true;

  try {
    let response;
    
    if (props.category) {
      response = await categoriesApi.update(props.category.id, {
        name: form.name,
        description: form.description,
        is_active: props.category.is_active ?? true,
      });
      toast({ title: t('common.success'), description: t('categories.updated_successfully') });
    } else {
      response = await categoriesApi.create({
        name: form.name,
        description: form.description,
        is_active: true,
      });
      toast({ title: t('common.success'), description: t('categories.created_successfully') });
    }

    emit('saved', response.data.data);
    handleClose();

  } catch (error: any) {
    console.error('Error saving category:', error);
    let msg = t('common.unexpected_error');
    if (error.response?.data?.message) msg = error.response.data.message;
    
    toast({
      title: t('common.error'),
      description: msg,
      variant: 'destructive',
    });
  } finally {
    isLoading.value = false;
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[425px]">
      <DialogHeader>
        <DialogTitle>{{ category ? t('categories.form_dialog_title_edit') : t('categories.form_dialog_title_new') }}</DialogTitle>
        <DialogDescription>
          {{ t('categories.form_dialog_description') }}
        </DialogDescription>
      </DialogHeader>
      
      <div class="grid gap-4 py-4">
        <div class="space-y-2">
          <Label for="category-name">{{ t('categories.form_label_name') }} <span class="text-destructive">*</span></Label>
          <Input 
            id="category-name" 
            v-model="form.name" 
            :placeholder="t('categories.form_name_placeholder')" 
            @keyup.enter="handleSubmit"
          />
        </div>
        
        <div class="space-y-2">
          <Label for="category-description">{{ t('categories.form_label_description') }}</Label>
          <Input 
            id="category-description" 
            v-model="form.description" 
            :placeholder="t('categories.form_description_placeholder')" 
            @keyup.enter="handleSubmit"
          />
        </div>
      </div>
      
      <DialogFooter>
        <Button variant="outline" @click="handleClose">{{ t('common.cancel') }}</Button>
        <Button @click="handleSubmit" :disabled="isLoading || !form.name">
          {{ isLoading ? t('common.saving') : t('common.save') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>