<script setup lang="ts">
import { ref, reactive, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import client from '@/api/client';
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Sparkles } from 'lucide-vue-next';
import { useToast } from '@/components/ui/toast/use-toast';

interface StorageLocation {
  id: string;
  name: string;
  code: string;
  type: string;
  warehouse_id: string;
}

const props = defineProps<{
  open: boolean;
  warehouseId: string;
}>();

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'saved', location: StorageLocation): void;
}>();

const { toast } = useToast();
const { t } = useI18n();
const isLoading = ref(false);

const form = reactive({
  name: '',
  code: '',
  type: 'shelf',
  aisle: '',
  section: '',
});

const locationTypes = [
  { value: 'shelf', label: t('locations.type_shelf') },
  { value: 'box', label: t('locations.type_box') },
  { value: 'pallet', label: t('locations.type_pallet') },
  { value: 'display', label: t('locations.type_display') },
  { value: 'floor', label: t('locations.type_floor') },
  { value: 'other', label: t('locations.type_other') },
];

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    form.name = '';
    form.code = '';
    form.type = 'shelf';
    form.aisle = '';
    form.section = '';
  }
});

const typeCodeMap: Record<string, string> = {
  shelf: 'EST', box: 'CAJ', pallet: 'PAL', display: 'EXP', floor: 'SUE', other: 'OTR',
};

function generateCode() {
  const prefix = typeCodeMap[form.type] || 'LOC';
  const namePart = form.name
    ? form.name.substring(0, 3).toUpperCase().replace(/[^A-Z0-9]/g, '')
    : '';
  const random = Math.random().toString(36).substring(2, 5).toUpperCase();
  form.code = `${prefix}-${namePart || random}${namePart ? random : ''}`.substring(0, 12);
}

function handleClose() {
  emit('update:open', false);
}

async function handleSubmit() {
  if (!form.name.trim() || !form.code.trim()) return;

  isLoading.value = true;
  try {
    const response = await client.post('/admin/inventory/locations', {
      warehouse_id: props.warehouseId,
      name: form.name,
      code: form.code,
      type: form.type,
      aisle: form.aisle || undefined,
      section: form.section || undefined,
    });

    toast({ title: t('common.success'), description: t('locations.created_successfully') });
    emit('saved', response.data.data);
    handleClose();
  } catch (error: any) {
    let msg = t('common.unexpected_error');
    if (error.response?.data?.message) msg = error.response.data.message;
    if (error.response?.data?.errors) {
      msg = Object.values(error.response.data.errors).flat().join(', ');
    }
    toast({ title: t('common.error'), description: msg, variant: 'destructive' });
  } finally {
    isLoading.value = false;
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[480px]">
      <DialogHeader>
        <DialogTitle>{{ t('locations.new') }}</DialogTitle>
        <DialogDescription>{{ t('locations.form_desc') }}</DialogDescription>
      </DialogHeader>

      <div class="grid gap-4 py-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label for="loc-name">{{ t('common.name') }} <span class="text-destructive">*</span></Label>
            <Input id="loc-name" v-model="form.name" placeholder="Ej. Estante A1" @keyup.enter="handleSubmit" />
          </div>
          <div class="space-y-2">
            <Label for="loc-code">{{ t('locations.code') }} <span class="text-destructive">*</span></Label>
            <div class="relative">
              <Input id="loc-code" v-model="form.code" placeholder="Ej. EST-A1" class="pr-10" @keyup.enter="handleSubmit" />
              <Button
                type="button"
                variant="ghost"
                size="icon"
                class="absolute right-1 top-1/2 -translate-y-1/2 h-7 w-7 hover:bg-primary/10"
                :title="t('products.generate_sku')"
                @click="generateCode"
              >
                <Sparkles class="h-4 w-4 text-primary" />
              </Button>
            </div>
          </div>
        </div>

        <div class="space-y-2">
          <Label>{{ t('locations.type') }}</Label>
          <Select v-model="form.type">
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="lt in locationTypes" :key="lt.value" :value="lt.value">
                {{ lt.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label for="loc-aisle">{{ t('locations.aisle') }} ({{ t('common.optional') }})</Label>
            <Input id="loc-aisle" v-model="form.aisle" placeholder="Ej. A" @keyup.enter="handleSubmit" />
          </div>
          <div class="space-y-2">
            <Label for="loc-section">{{ t('locations.section') }} ({{ t('common.optional') }})</Label>
            <Input id="loc-section" v-model="form.section" placeholder="Ej. 3" @keyup.enter="handleSubmit" />
          </div>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="handleClose">{{ t('common.cancel') }}</Button>
        <Button @click="handleSubmit" :disabled="isLoading || !form.name.trim() || !form.code.trim()">
          {{ isLoading ? t('common.saving') : t('common.create') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
