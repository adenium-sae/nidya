<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Upload, Package, Tag, DollarSign, Store as StoreIcon, Sparkles, Plus, Info } from 'lucide-vue-next';
import { useToast } from '@/components/ui/toast/use-toast';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
import CategoryFormDialog from '@/components/inventory/CategoryFormDialog.vue';
import { z } from 'zod';

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
  serverErrors?: Record<string, string[]>;
}>(), {
  categories: () => [],
  stores: () => [],
  initialData: () => ({}),
  isLoading: false,
  isEditMode: false,
  submitLabel: '',
  serverErrors: () => ({})
});

const emit = defineEmits<{
  (e: 'submit', data: ProductFormData, imageFile: File | null): void;
  (e: 'cancel'): void;
  (e: 'category-created', category: Category): void;
}>();

const { t } = useI18n();
const { toast } = useToast();
const previewImage = ref<string | null>(props.initialData.image_url || null);
const imageFile = ref<File | null>(null);
const isCreatingCategory = ref(false);
const fieldErrors = reactive<Record<string, string>>({});

const actualSubmitLabel = computed(() => props.submitLabel || t('common.save'));

const typeLabel = computed(() => {
  if (form.type === 'product') return t('products.type_physical');
  if (form.type === 'service') return t('products.type_service');
  return t('common.select');
});

const storeDistributionLabel = computed(() => {
  if (form.target_stores === 'single') return t('products.dist_single');
  if (form.target_stores === 'multiple') return t('products.dist_multiple');
  if (form.target_stores === 'all') return t('products.dist_all');
  return t('common.select');
});

const selectedStoreName = computed(() => {
  if (!form.store_id) return t('common.select');
  const store = props.stores.find(s => String(s.id) === form.store_id);
  return store?.name || t('common.select');
});

function clearFieldError(field: string) {
  if (fieldErrors[field]) {
    delete fieldErrors[field];
  }
}

function clearAllErrors() {
  Object.keys(fieldErrors).forEach(key => delete fieldErrors[key]);
}

watch(() => props.serverErrors, (errors) => {
  if (errors && Object.keys(errors).length > 0) {
    Object.entries(errors).forEach(([field, messages]) => {
      fieldErrors[field] = Array.isArray(messages) ? messages[0] : messages;
    });
  }
}, { deep: true });

const form = reactive<ProductFormData>({
  name: props.initialData.name || '',
  description: props.initialData.description || '',
  sku: props.initialData.sku || '',
  barcode: props.initialData.barcode || '',
  price: props.initialData.price ? Number(props.initialData.price).toFixed(2) : '',
  cost: props.initialData.cost ? Number(props.initialData.cost).toFixed(2) : '',
  category_id: props.initialData.category_id || '',
  type: props.initialData.type || 'product',
  target_stores: props.initialData.target_stores || 'single',
  store_id: props.initialData.store_id || '',
  store_ids: props.initialData.store_ids ? props.initialData.store_ids.map((id: any) => String(id)) : [],
  min_stock: props.initialData.min_stock || '0',
  is_active: props.initialData.is_active ?? true,
});

// Map categories for SearchableSelect
const categoryOptions = computed(() => {
  return props.categories.map(cat => ({
    value: String(cat.id),
    label: cat.name
  }));
});

watch(() => props.initialData, (newData) => {
  if (newData) {
    Object.assign(form, {
      name: newData.name || '',
      description: newData.description || '',
      sku: newData.sku || '',
      barcode: newData.barcode || '',
      price: newData.price ? Number(newData.price).toFixed(2) : '',
      cost: newData.cost ? Number(newData.cost).toFixed(2) : '',
      category_id: newData.category_id || '',
      type: newData.type || 'product',
      target_stores: newData.target_stores || 'single',
      store_id: newData.store_id ? String(newData.store_id) : '',
      store_ids: newData.store_ids ? newData.store_ids.map((id: any) => String(id)) : [],
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
    form.store_id = String(stores[0].id);
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

function updateStoreSelection(storeId: string, checked: boolean | 'indeterminate') {
  const id = String(storeId);
  if (checked === true) {
    if (!form.store_ids.includes(id)) {
      form.store_ids.push(id);
    }
  } else {
    const index = form.store_ids.findIndex((s: string) => String(s) === id);
    if (index !== -1) {
      form.store_ids.splice(index, 1);
    }
  }
}

function isStoreSelected(storeId: string) {
  const id = String(storeId);
  return form.store_ids.some((s: string) => String(s) === id);
}

// Route row clicks through reka-ui's own button so its visual state always updates
function handleStoreRowClick(event: MouseEvent) {
  const target = event.target as HTMLElement;
  // If click landed on the checkbox button itself, reka-ui already handles it
  if (target.closest('[role="checkbox"]')) return;
  // Otherwise programmatically click the checkbox button in this row
  const row = event.currentTarget as HTMLElement;
  const btn = row.querySelector('[role="checkbox"]') as HTMLElement | null;
  btn?.click();
}

const currencyInputSchema = z.string().regex(/^\d*\.?\d{0,2}$/, "Formato inválido (máx. 2 decimales)");

const currencyFormatSchema = z.coerce.number().transform((val) => {
  return val.toFixed(2);
});

const productFormSchema = computed(() => {
  let schema: z.ZodType<any> = z.object({
    name: z.string().min(1, t('products.name_required')),
    description: z.string().optional(),
    sku: z.string().min(1, t('products.sku_required')),
    barcode: z.string().optional(),
    price: props.isEditMode 
      ? z.string().optional() 
      : z.string().refine((val) => !isNaN(parseFloat(val)) && parseFloat(val) >= 0, t('products.invalid_price')),
    cost: z.string().refine((val) => !isNaN(parseFloat(val)) && parseFloat(val) >= 0, t('products.invalid_cost')),
    category_id: z.string().min(1, t('products.category_required')),
    type: z.string(),
    target_stores: z.string(),
    store_id: z.string().optional(),
    store_ids: z.array(z.string()).optional(),
    min_stock: z.coerce.string().refine((val) => !isNaN(parseInt(val)) && parseInt(val) >= 0, t('products.invalid_min_stock')),
    is_active: z.boolean(),
    image_url: z.string().optional(),
  });
  if (!props.isEditMode) {
    schema = schema.refine((data) => {
      if (data.target_stores === 'single' && !data.store_id) {
        return false;
      }
      return true;
    }, {
      message: t('products.must_select_store'),
      path: ["store_id"],
    }).refine((data) => {
      if (data.target_stores === 'multiple' && (!data.store_ids || data.store_ids.length === 0)) {
        return false;
      }
      return true;
    }, {
      message: t('products.must_select_stores'),
      path: ["store_ids"],
    });
  }
  return schema;
});


function handlePriceKeydown(e: KeyboardEvent) {
  if (['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', 'Home', 'End'].includes(e.key)) return;
  if (e.ctrlKey || e.metaKey) return;
  const input = e.target as HTMLInputElement;
  const key = e.key;
  if (!/[\d.]/.test(key)) {
    e.preventDefault();
    return;
  }
  if (key === '.' && input.value.includes('.')) {
    e.preventDefault();
    return;
  }
  if (input.value.includes('.')) {
    const parts = input.value.split('.');
    const decimalPart = parts[1];
    if (decimalPart && decimalPart.length >= 2) {
      const cursorStart = input.selectionStart || 0;
      const dotIndex = input.value.indexOf('.');
      if (cursorStart > dotIndex && input.selectionStart === input.selectionEnd) {
         e.preventDefault();
      }
    }
  }
}

function validateDecimals(field: 'price' | 'cost') {
  let value = form[field];
  if (!value) return;
  const sanitized = value.replace(/[^0-9.]/g, '');
  if (sanitized !== value) {
    value = sanitized;
    form[field] = value;
  }
  if (value.includes('.')) {
    const parts = value.split('.');
    if (parts[1].length > 2) {
      form[field] = parts[0] + '.' + parts[1].substring(0, 2);
    }
  }
} 

function formatPrice(field: 'price' | 'cost') {
  if (!form[field]) return;
  const result = currencyFormatSchema.safeParse(form[field]);
  if (result.success) {
    form[field] = result.data;
  }
}

async function handleSubmit() {
  clearAllErrors();
  // Use a plain JS copy of the form to avoid reactive Proxy issues
  const plain = {
    ...JSON.parse(JSON.stringify(form)),
    store_ids: form.store_ids ? form.store_ids.map((s: any) => String(s)) : [],
    store_id: form.store_id ? String(form.store_id) : '',
  };
  const result = productFormSchema.value.safeParse(plain);
  if (!result.success) {
    const messages: string[] = [];
    result.error.errors.forEach(err => {
      const field = String(err.path?.[0] || 'form');
      const message = err.message || t('common.invalid_field');
      messages.push(message);
      if (field && !fieldErrors[field]) {
        fieldErrors[field] = message;
      }
    });
    const uniqueMessages = Array.from(new Set(messages));
    toast({
      title: t('common.validation_error'),
      description: uniqueMessages.join(' — '),
      variant: 'destructive',
    });
    console.warn('ProductForm validation errors:', result.error, { store_ids: form.store_ids, target_stores: form.target_stores, store_id: form.store_id });
    return;
  }
  emit('submit', plain, imageFile.value);
}

function handleCategoryCreated(newCategory: Category) {
  emit('category-created', newCategory);
  form.category_id = String(newCategory.id);
}
</script>

<template>
  <div class="grid lg:grid-cols-[1fr_340px] gap-8">
    <div class="space-y-8 min-w-0">
      <div class="bg-card rounded-xl border p-6 shadow-sm">
        <h2 class="text-lg font-semibold mb-6 flex items-center gap-2">
          <Package class="h-5 w-5 text-primary" />
          {{ t('products.general_info') }}
        </h2>
        
        <div class="grid md:grid-cols-3 gap-6">
          <div>
            <Label class="block mb-3 text-sm font-medium">{{ t('products.image') }}</Label>
            <div class="border-2 border-dashed rounded-xl flex flex-col items-center justify-center aspect-square cursor-pointer hover:bg-muted/50 hover:border-primary/50 transition-colors relative overflow-hidden bg-muted/20">
              <input type="file" class="absolute inset-0 opacity-0 cursor-pointer z-10" accept="image/*" @change="handleImageChange" />
              
              <img v-if="previewImage" :src="previewImage" class="absolute inset-0 w-full h-full object-cover" />
              
              <div v-else class="flex flex-col items-center text-muted-foreground p-4 text-center">
                <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center mb-3">
                  <Upload class="h-6 w-6 text-primary" />
                </div>
                <span class="text-sm font-medium">{{ t('products.upload_image') }}</span>
                <span class="text-xs mt-1">{{ t('products.image_hint') }}</span>
              </div>
            </div>
          </div>

          <div class="md:col-span-2 space-y-5">
            <div class="space-y-2">
              <Label for="name">{{ t('products.name') }} <span class="text-destructive">*</span></Label>
              <Input id="name" v-model="form.name" :placeholder="t('products.name_placeholder')" :class="{ 'border-destructive': fieldErrors.name }" @input="clearFieldError('name')" />
              <p v-if="fieldErrors.name" class="text-xs text-destructive">{{ fieldErrors.name }}</p>
            </div>
            
            <div class="space-y-2">
              <Label for="description">{{ t('common.description') }}</Label>
              <Textarea id="description" v-model="form.description" :placeholder="t('products.desc_placeholder')" class="min-h-[100px] resize-none" />
            </div>
          </div>
        </div>
      </div>

      <div class="bg-card rounded-xl border p-6 shadow-sm">
        <h2 class="text-lg font-semibold mb-6 flex items-center gap-2">
          <Tag class="h-5 w-5 text-primary" />
          {{ t('products.identification') }}
        </h2>
        
        <div class="grid md:grid-cols-2 gap-6">
          <div class="space-y-2">
            <Label for="sku">{{ t('products.sku') }} <span class="text-destructive">*</span></Label>
            <div class="relative">
              <Input id="sku" v-model="form.sku" :placeholder="t('products.sku_placeholder')" :class="['pr-12', { 'border-destructive': fieldErrors.sku }]" @input="clearFieldError('sku')" />
              <Button 
                type="button" 
                variant="ghost" 
                size="icon" 
                class="absolute right-1 top-1/2 -translate-y-1/2 h-9 w-9 hover:bg-primary/10"
                @click="generateSku"
                :title="t('products.generate_sku')"
              >
                <Sparkles class="h-4 w-4 text-primary" />
              </Button>
            </div>
            <p v-if="fieldErrors.sku" class="text-xs text-destructive">{{ fieldErrors.sku }}</p>
            <p v-else class="text-xs text-muted-foreground">{{ t('products.sku_hint') }}</p>
          </div>
          <div class="space-y-2">
            <Label for="barcode">{{ t('products.barcode') }}</Label>
            <Input id="barcode" v-model="form.barcode" :placeholder="t('products.barcode_placeholder')" class="" />
            <p class="text-xs text-muted-foreground">{{ t('products.barcode_hint') }}</p>
          </div>
        </div>
      </div>

      <div class="bg-card rounded-xl border p-6 shadow-sm">
        <h2 class="text-lg font-semibold mb-6 flex items-center gap-2">
          <DollarSign class="h-5 w-5 text-primary" />
          {{ t('products.prices') }}
        </h2>
        
        <div class="grid md:grid-cols-2 gap-6">
          <div class="space-y-2" v-if="!isEditMode">
            <Label for="price">{{ t('products.sale_price') }} <span class="text-destructive">*</span></Label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">$</span>
              <Input 
                id="price" 
                type="text" 
                inputmode="decimal"
                v-model="form.price" 
                :placeholder="t('products.price_placeholder')" 
                :class="['pl-7', { 'border-destructive': fieldErrors.price }]" 
                @blur="formatPrice('price')"
                @input="validateDecimals('price'); clearFieldError('price')"
                @keydown="handlePriceKeydown"
              />
            </div>
            <p v-if="fieldErrors.price" class="text-xs text-destructive">{{ fieldErrors.price }}</p>
          </div>
          <div class="space-y-2">
            <Label for="cost">{{ t('products.purchase_cost') }} <span class="text-destructive">*</span></Label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">$</span>
              <Input 
                id="cost" 
                type="text" 
                inputmode="decimal"
                v-model="form.cost" 
                :placeholder="t('products.cost_placeholder')" 
                :class="['pl-7', { 'border-destructive': fieldErrors.cost }]" 
                @blur="formatPrice('cost')"
                @input="validateDecimals('cost'); clearFieldError('cost')"
                @keydown="handlePriceKeydown"
              />
            </div>
            <p v-if="fieldErrors.cost" class="text-xs text-destructive">{{ fieldErrors.cost }}</p>
            <p v-else-if="isEditMode" class="text-xs text-muted-foreground">{{ t('products.price_per_store_hint') }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="space-y-8 w-full lg:w-[340px]">
      <div class="bg-card rounded-xl border p-6 shadow-sm">
        <h2 class="text-lg font-semibold mb-6">{{ t('products.classification') }}</h2>
        
        <div class="space-y-5">
          <div class="space-y-2">
            <Label>{{ t('products.category') }} <span class="text-destructive">*</span></Label>
            <SearchableSelect
              v-model="form.category_id"
              :options="categoryOptions"
              :placeholder="t('products.select_category')"
              :search-placeholder="t('common.search')"
              show-add-option
              :add-option-label="t('products.new_category')"
              @add-click="isCreatingCategory = true"
              @update:model-value="clearFieldError('category_id')"
            />
            <p v-if="fieldErrors.category_id" class="text-xs text-destructive">{{ fieldErrors.category_id }}</p>
          </div>
          
          <div class="space-y-2">
            <Label>{{ t('products.product_type') }}</Label>
            <Select v-model="form.type">
              <SelectTrigger class="">
                <SelectValue :placeholder="typeLabel" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="product">{{ t('products.type_physical') }}</SelectItem>
                <SelectItem value="service">{{ t('products.type_service') }}</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <div class="flex items-center gap-2">
              <Label for="min_stock">{{ t('products.min_stock') }}</Label>
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger as-child>
                    <Info class="h-4 w-4 text-muted-foreground cursor-help" />
                  </TooltipTrigger>
                  <TooltipContent>
                    <p>{{ t('products.min_stock_hint') }}</p>
                  </TooltipContent>
                </Tooltip>
              </TooltipProvider>
            </div>
            <Input id="min_stock" type="number" v-model="form.min_stock" class="" />
          </div>
        </div>
      </div>

      <div v-if="!isEditMode" class="bg-card rounded-xl border p-6 shadow-sm">
        <h2 class="text-lg font-semibold mb-6 flex items-center gap-2">
          <StoreIcon class="h-5 w-5 text-primary" />
          {{ t('products.availability') }}
        </h2>
        
        <div class="space-y-5">
          <div class="space-y-2">
            <Label>{{ t('products.store_distribution') }}</Label>
            <Select v-model="form.target_stores">
              <SelectTrigger class="">
                <SelectValue :placeholder="storeDistributionLabel" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="single">{{ t('products.dist_single') }}</SelectItem>
                <SelectItem value="multiple">{{ t('products.dist_multiple') }}</SelectItem>
                <SelectItem value="all">{{ t('products.dist_all') }}</SelectItem>
              </SelectContent>
            </Select>
          </div>
          
          <div class="space-y-2" v-if="form.target_stores === 'single'">
            <Label>{{ t('products.select_store') }} <span class="text-destructive">*</span></Label>
            <Select v-model="form.store_id" @update:model-value="clearFieldError('store_id')">
              <SelectTrigger :class="{ 'border-destructive': fieldErrors.store_id }">
                <SelectValue :placeholder="selectedStoreName" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="store in stores" :key="store.id" :value="String(store.id)">
                  {{ store.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="fieldErrors.store_id" class="text-xs text-destructive">{{ fieldErrors.store_id }}</p>
          </div>

          <div class="space-y-3" v-if="form.target_stores === 'multiple'">
            <Label>{{ t('products.select_stores') }}</Label>
            <div class="border rounded-lg divide-y max-h-48 overflow-y-auto">
              <div 
                v-for="store in stores" 
                :key="store.id" 
                class="flex items-center gap-3 p-3 hover:bg-muted/50 cursor-pointer transition-colors"
                @click="handleStoreRowClick($event)"
              >
                <Checkbox 
                  :checked="isStoreSelected(store.id)" 
                  @update:checked="(val) => updateStoreSelection(store.id, val)"
                />
                <span class="text-sm">{{ store.name }}</span>
              </div>
            </div>
            <p class="text-xs text-muted-foreground" v-if="form.store_ids.length > 0">
              {{ form.store_ids.length }} {{ t('products.stores_selected') }}
            </p>
          </div>

          <div v-if="form.target_stores === 'all'" class="bg-primary/5 border border-primary/20 rounded-lg p-4">
            <p class="text-sm text-primary">
              {{ t('products.dist_all_hint') }}
            </p>
          </div>
        </div>
      </div>

      <div v-if="isEditMode" class="bg-card rounded-xl border p-6 shadow-sm">
        <h2 class="text-lg font-semibold mb-6">{{ t('common.status') }}</h2>
        
        <div class="flex items-center justify-between p-4 rounded-lg border" :class="form.is_active ? 'bg-green-50 border-green-200 dark:bg-green-950/20 dark:border-green-800' : 'bg-muted'">
          <div>
            <p class="font-medium" :class="form.is_active ? 'text-green-700 dark:text-green-400' : ''">
              {{ form.is_active ? t('common.active_m') : t('common.inactive_m') }}
            </p>
            <p class="text-xs text-muted-foreground">
              {{ form.is_active ? t('products.status_active_hint') : t('products.status_inactive_hint') }}
            </p>
          </div>
          <Button 
            :variant="form.is_active ? 'outline' : 'default'" 
            size="sm"
            @click="form.is_active = !form.is_active"
          >
            {{ form.is_active ? t('common.deactivate') : t('common.activate') }}
          </Button>
        </div>
      </div>
    </div>
  </div>

  <div class="flex justify-end gap-4 pt-4 border-t mt-8">
    <Button variant="outline" @click="$emit('cancel')">{{ t('common.cancel') }}</Button>
    <Button @click="handleSubmit" :disabled="isLoading">
      {{ isLoading ? t('common.saving') : actualSubmitLabel }}
    </Button>
  </div>

  <CategoryFormDialog 
    v-model:open="isCreatingCategory"
    @saved="handleCategoryCreated"
  />
</template>
