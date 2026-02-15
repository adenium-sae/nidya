<script setup lang="ts">
import { ref, reactive, watch } from 'vue';
import axios from 'axios';
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
  is_active: boolean;
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
  const token = localStorage.getItem('auth_token');

  try {
    let response;
    
    if (props.category) {
      // Edit
      response = await axios.put(`/api/admin/categories/${props.category.id}`, {
        name: form.name,
        description: form.description,
        is_active: props.category.is_active
      }, {
        headers: { Authorization: `Bearer ${token}` }
      });
      
      toast({ title: 'Éxito', description: 'Categoría actualizada correctamente.' });
    } else {
      // Create
      response = await axios.post('/api/admin/categories', {
        name: form.name,
        description: form.description,
        is_active: true
      }, {
        headers: { Authorization: `Bearer ${token}` }
      });
      
      toast({ title: 'Éxito', description: 'Categoría creada correctamente.' });
    }

    emit('saved', response.data.data);
    handleClose();

  } catch (error: any) {
    console.error('Error saving category:', error);
    let msg = 'Hubo un error al guardar la categoría.';
    if (error.response?.data?.message) msg = error.response.data.message;
    
    toast({
      title: 'Error',
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
        <DialogTitle>{{ category ? 'Editar Categoría' : 'Nueva Categoría' }}</DialogTitle>
        <DialogDescription>
          Completa los detalles de la categoría aquí.
        </DialogDescription>
      </DialogHeader>
      
      <div class="grid gap-4 py-4">
        <div class="space-y-2">
          <Label for="category-name">Nombre <span class="text-destructive">*</span></Label>
          <Input 
            id="category-name" 
            v-model="form.name" 
            placeholder="Ej. Bebidas" 
            @keyup.enter="handleSubmit"
          />
        </div>
        
        <div class="space-y-2">
          <Label for="category-description">Descripción</Label>
          <Input 
            id="category-description" 
            v-model="form.description" 
            placeholder="Descripción opcional" 
            @keyup.enter="handleSubmit"
          />
        </div>
      </div>
      
      <DialogFooter>
        <Button variant="outline" @click="handleClose">Cancelar</Button>
        <Button @click="handleSubmit" :disabled="isLoading || !form.name">
          {{ isLoading ? 'Guardando...' : 'Guardar' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
