<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Upload, Package, Tag, DollarSign, Store as StoreIcon, Sparkles } from 'lucide-vue-next';

interface Category {
  id: string;
  name: string;
}

interface Store {
  id: string;
  name: string;
}

interface ProductFormData {
  name: string;
  description: string;
  sku: string;
  barcode: string;
  price: string;
  cost: string;
  category_id: string;
  type: string;
  target_stores: string;
  store_id: string;
  store_ids: string[];
  min_stock: string;
  is_active: boolean;
  image_url?: string;
}

const props = withDefaults(defineProps<{
  categories: Category[];
  stores: Store[];
  initialData?: Partial<ProductFormData>;
  isLoading?: boolean;
  isEditMode?: boolean;
  submitLabel?: string;
}>(), {
  categories: () => [],
  stores: () => [],
  initialData: () => ({}),
  isLoading: false,
  isEditMode: false,
  submitLabel: 'Guardar'
});

const emit = defineEmits<{
  (e: 'submit', data: ProductFormData, imageFile: File | null): void;
  (e: 'cancel'): void;
}>();

const previewImage = ref<string | null>(props.initialData.image_url || null);
const imageFile = ref<File | null>(null);

const form = reactive<ProductFormData>({
  name: props.initialData.name || '',
  description: props.initialData.description || '',
  sku: props.initialData.sku || '',
  barcode: props.initialData.barcode || '',
  price: props.initialData.price || '',
  cost: props.initialData.cost || '',
  category_id: props.initialData.category_id || '',
  type: props.initialData.type || 'product',
  target_stores: props.initialData.target_stores || 'single',
  store_id: props.initialData.store_id || '',
  store_ids: props.initialData.store_ids || [],
  min_stock: props.initialData.min_stock || '5',
  is_active: props.initialData.is_active ?? true,
});

watch(() => props.initialData, (newData) => {
  if (newData) {
    Object.assign(form, {
      name: newData.name || '',
      description: newData.description || '',
      sku: newData.sku || '',
      barcode: newData.barcode || '',
      price: newData.price || '',
      cost: newData.cost || '',
      category_id: newData.category_id || '',
      type: newData.type || 'product',
      target_stores: newData.target_stores || 'single',
      store_id: newData.store_id || '',
      store_ids: newData.store_ids || [],
      min_stock: newData.min_stock || '5',
      is_active: newData.is_active ?? true,
    });
    if (newData.image_url) {
      previewImage.value = newData.image_url;
    }
  }
}, { deep: true });

watch(() => props.stores, (stores) => {
  if (stores.length > 0 && !form.store_id && !props.isEditMode) {
    form.store_id = stores[0].id;
  }
}, { immediate: true });

function generateSku() {
  const prefix = form.name 
    ? form.name.substring(0, 3).toUpperCase().replace(/[^A-Z]/g, '') 
    : 'PRD';
  const random = Math.random().toString(36).substring(2, 6).toUpperCase();
  const timestamp = Date.now().toString(36).substring(-4).toUpperCase();
  form.sku = `${prefix}-${random}${timestamp}`.substring(0, 15);
}

function handleImageChange(event: Event) {
  const input = event.target as HTMLInputElement;
  if (input.files && input.files[0]) {
    const file = input.files[0];
    imageFile.value = file;
    previewImage.value = URL.createObjectURL(file);
  }
}

function toggleStoreSelection(storeId: string) {
  const index = form.store_ids.indexOf(storeId);
  if (index === -1) {
    form.store_ids.push(storeId);
  } else {
    form.store_ids.splice(index, 1);
  }
}

function isStoreSelected(storeId: string) {
  return form.store_ids.includes(storeId);
}

function handleSubmit() {
  emit('submit', { ...form }, imageFile.value);
}
</script>

<template>
  <div class="grid lg:grid-cols-[1fr_340px] gap-8">
    <!-- Left Column: Image + Basic Info -->
    <div class="space-y-8 min-w-0">
      <!-- Product Image & Name Card -->
      <div class="bg-card rounded-xl border p-6 shadow-sm">
        <h2 class="text-lg font-semibold mb-6 flex items-center gap-2">
          <Package class="h-5 w-5 text-primary" />
          Información General
        </h2>
        
        <div class="grid md:grid-cols-3 gap-6">
          <!-- Image Upload -->
          <div>
            <Label class="block mb-3 text-sm font-medium">Imagen</Label>
            <div class="border-2 border-dashed rounded-xl flex flex-col items-center justify-center aspect-square cursor-pointer hover:bg-muted/50 hover:border-primary/50 transition-colors relative overflow-hidden bg-muted/20">
              <input type="file" class="absolute inset-0 opacity-0 cursor-pointer z-10" accept="image/*" @change="handleImageChange" />
              
              <img v-if="previewImage" :src="previewImage" class="absolute inset-0 w-full h-full object-cover" />
              
              <div v-else class="flex flex-col items-center text-muted-foreground p-4 text-center">
                <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center mb-3">
                  <Upload class="h-6 w-6 text-primary" />
                </div>
                <span class="text-sm font-medium">Subir imagen</span>
                <span class="text-xs mt-1">PNG, JPG hasta 2MB</span>
              </div>
            </div>
          </div>

          <!-- Basic Fields -->
          <div class="md:col-span-2 space-y-5">
            <div class="space-y-2">
              <Label for="name">Nombre del Producto <span class="text-destructive">*</span></Label>
              <Input id="name" v-model="form.name" placeholder="Ej. Coca Cola 600ml" class="" />
            </div>
            
            <div class="space-y-2">
              <Label for="description">Descripción</Label>
              <Textarea id="description" v-model="form.description" placeholder="Detalles del producto..." class="min-h-[100px] resize-none" />
            </div>
          </div>
        </div>
      </div>

      <!-- Identification Card -->
      <div class="bg-card rounded-xl border p-6 shadow-sm">
        <h2 class="text-lg font-semibold mb-6 flex items-center gap-2">
          <Tag class="h-5 w-5 text-primary" />
          Identificación
        </h2>
        
        <div class="grid md:grid-cols-2 gap-6">
          <div class="space-y-2">
            <Label for="sku">SKU (Código único) <span class="text-destructive">*</span></Label>
            <div class="relative">
              <Input id="sku" v-model="form.sku" placeholder="PROD-001" class=" pr-12" />
              <Button 
                type="button" 
                variant="ghost" 
                size="icon" 
                class="absolute right-1 top-1/2 -translate-y-1/2 h-9 w-9 hover:bg-primary/10"
                @click="generateSku"
                title="Generar SKU automático"
              >
                <Sparkles class="h-4 w-4 text-primary" />
              </Button>
            </div>
            <p class="text-xs text-muted-foreground">Identificador único para tu inventario</p>
          </div>
          <div class="space-y-2">
            <Label for="barcode">Código de Barras</Label>
            <Input id="barcode" v-model="form.barcode" placeholder="7501234567890" class="" />
            <p class="text-xs text-muted-foreground">Código UPC o EAN del producto</p>
          </div>
        </div>
      </div>

      <!-- Pricing Card -->
      <div class="bg-card rounded-xl border p-6 shadow-sm">
        <h2 class="text-lg font-semibold mb-6 flex items-center gap-2">
          <DollarSign class="h-5 w-5 text-primary" />
          Precios
        </h2>
        
        <div class="grid md:grid-cols-2 gap-6">
          <div class="space-y-2" v-if="!isEditMode">
            <Label for="price">Precio de Venta <span class="text-destructive">*</span></Label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">$</span>
              <Input id="price" type="number" step="0.01" v-model="form.price" placeholder="0.00" class="pl-7" />
            </div>
          </div>
          <div class="space-y-2">
            <Label for="cost">Costo de Compra <span class="text-destructive">*</span></Label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">$</span>
              <Input id="cost" type="number" step="0.01" v-model="form.cost" placeholder="0.00" class="pl-7" />
            </div>
            <p v-if="isEditMode" class="text-xs text-muted-foreground">El precio de venta se configura por tienda</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Classification & Stores (Fixed Width) -->
    <div class="space-y-8 w-full lg:w-[340px]">
      <!-- Classification Card -->
      <div class="bg-card rounded-xl border p-6 shadow-sm">
        <h2 class="text-lg font-semibold mb-6">Clasificación</h2>
        
        <div class="space-y-5">
          <div class="space-y-2">
            <Label>Categoría <span class="text-destructive">*</span></Label>
            <Select v-model="form.category_id">
              <SelectTrigger class="">
                <SelectValue placeholder="Seleccionar..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          
          <div class="space-y-2">
            <Label>Tipo de Producto</Label>
            <Select v-model="form.type">
              <SelectTrigger class="">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="product">Producto Físico</SelectItem>
                <SelectItem value="service">Servicio</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="min_stock">Stock Mínimo</Label>
            <Input id="min_stock" type="number" v-model="form.min_stock" class="" />
            <p class="text-xs text-muted-foreground">Alerta cuando el stock baje de este nivel</p>
          </div>
        </div>
      </div>

      <!-- Distribution Card (only for create mode) -->
      <div v-if="!isEditMode" class="bg-card rounded-xl border p-6 shadow-sm">
        <h2 class="text-lg font-semibold mb-6 flex items-center gap-2">
          <StoreIcon class="h-5 w-5 text-primary" />
          Disponibilidad
        </h2>
        
        <div class="space-y-5">
          <div class="space-y-2">
            <Label>Distribución en Tiendas</Label>
            <Select v-model="form.target_stores">
              <SelectTrigger class="">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="single">Una tienda</SelectItem>
                <SelectItem value="multiple">Tiendas específicas</SelectItem>
                <SelectItem value="all">Todas las tiendas</SelectItem>
              </SelectContent>
            </Select>
          </div>
          
          <!-- Single Store Selection -->
          <div class="space-y-2" v-if="form.target_stores === 'single'">
            <Label>Seleccionar Tienda</Label>
            <Select v-model="form.store_id">
              <SelectTrigger class="">
                <SelectValue placeholder="Seleccionar..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="store in stores" :key="store.id" :value="store.id">
                  {{ store.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Multiple Store Selection -->
          <div class="space-y-3" v-if="form.target_stores === 'multiple'">
            <Label>Seleccionar Tiendas</Label>
            <div class="border rounded-lg divide-y max-h-48 overflow-y-auto">
              <label 
                v-for="store in stores" 
                :key="store.id" 
                class="flex items-center gap-3 p-3 hover:bg-muted/50 cursor-pointer transition-colors"
              >
                <Checkbox 
                  :checked="isStoreSelected(store.id)" 
                  @update:checked="toggleStoreSelection(store.id)" 
                />
                <span class="text-sm">{{ store.name }}</span>
              </label>
            </div>
            <p class="text-xs text-muted-foreground" v-if="form.store_ids.length > 0">
              {{ form.store_ids.length }} tienda(s) seleccionada(s)
            </p>
          </div>

          <!-- All Stores Info -->
          <div v-if="form.target_stores === 'all'" class="bg-primary/5 border border-primary/20 rounded-lg p-4">
            <p class="text-sm text-primary">
              El producto estará disponible en todas las tiendas actuales.
            </p>
          </div>
        </div>
      </div>

      <!-- Status Card (only for edit mode) -->
      <div v-if="isEditMode" class="bg-card rounded-xl border p-6 shadow-sm">
        <h2 class="text-lg font-semibold mb-6">Estado</h2>
        
        <div class="flex items-center justify-between p-4 rounded-lg border" :class="form.is_active ? 'bg-green-50 border-green-200 dark:bg-green-950/20 dark:border-green-800' : 'bg-muted'">
          <div>
            <p class="font-medium" :class="form.is_active ? 'text-green-700 dark:text-green-400' : ''">
              {{ form.is_active ? 'Activo' : 'Inactivo' }}
            </p>
            <p class="text-xs text-muted-foreground">
              {{ form.is_active ? 'Visible para venta' : 'Oculto del catálogo' }}
            </p>
          </div>
          <Button 
            :variant="form.is_active ? 'outline' : 'default'" 
            size="sm"
            @click="form.is_active = !form.is_active"
          >
            {{ form.is_active ? 'Desactivar' : 'Activar' }}
          </Button>
        </div>
      </div>
    </div>
  </div>

  <!-- Actions -->
  <div class="flex justify-end gap-4 pt-4 border-t mt-8">
    <Button variant="outline" @click="$emit('cancel')">Cancelar</Button>
    <Button @click="handleSubmit" :disabled="isLoading">
      {{ isLoading ? 'Guardando...' : submitLabel }}
    </Button>
  </div>
</template>
