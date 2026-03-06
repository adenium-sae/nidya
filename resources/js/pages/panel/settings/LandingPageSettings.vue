<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import * as z from 'zod';
import { useToast } from '@/components/ui/toast/use-toast';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Badge } from '@/components/ui/badge';
import { ImageOff, Upload, X, Palette, Image, Sparkles, Check, Loader2 } from 'lucide-vue-next';
import { useBranding } from '@/composables/useBranding';

const { t } = useI18n();
const { toast } = useToast();
const { fetchBranding } = useBranding();

// Hero image
const imagePreview = ref<string | null>(null);
const imageFile = ref<File | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

// Logo
const logoPreview = ref<string | null>(null);
const logoFile = ref<File | null>(null);
const logoInput = ref<HTMLInputElement | null>(null);

// Icon
const iconPreview = ref<string | null>(null);
const iconFile = ref<File | null>(null);
const iconInput = ref<HTMLInputElement | null>(null);

// Color suggestions
const suggestedColors = ref<{ hex: string; role: string }[]>([]);
const isExtractingColors = ref(false);

const hexColorRegex = /^#[0-9A-Fa-f]{6}$/;

const formSchema = toTypedSchema(
  z.object({
    hero_title: z.string().nullable().optional().or(z.literal('')),
    hero_subtitle: z.string().nullable().optional().or(z.literal('')),
    about_us_text: z.string().nullable().optional().or(z.literal('')),
    contact_email: z.string().email(t('products.invalid_email')).nullable().optional().or(z.literal('')),
    contact_phone: z.string().nullable().optional().or(z.literal('')),
    display_name: z.string().nullable().optional().or(z.literal('')),
    primary_color: z.string().regex(hexColorRegex).optional().or(z.literal('')),
    secondary_color: z.string().regex(hexColorRegex).optional().or(z.literal('')),
    accent_color: z.string().regex(hexColorRegex).optional().or(z.literal('')),
  })
);

const form = useForm({
  validationSchema: formSchema,
  initialValues: {
    hero_title: '',
    hero_subtitle: '',
    about_us_text: '',
    contact_email: '',
    contact_phone: '',
    display_name: '',
    primary_color: '#171717',
    secondary_color: '#F5F5F5',
    accent_color: '#F5F5F5',
  },
});

const isLoading = ref(true);
const isSaving = ref(false);

async function fetchSettings() {
  isLoading.value = true;
  try {
    const response = await fetch('/api/admin/settings/landing-page', {
      headers: { 
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Accept': 'application/json'
      },
    });
    if (response.ok) {
      const data = await response.json();
      form.setValues({
        hero_title: data.hero_title || '',
        hero_subtitle: data.hero_subtitle || '',
        about_us_text: data.about_us_text || '',
        contact_email: data.contact_email || '',
        contact_phone: data.contact_phone || '',
        display_name: data.display_name || '',
        primary_color: data.primary_color || '#171717',
        secondary_color: data.secondary_color || '#F5F5F5',
        accent_color: data.accent_color || '#F5F5F5',
      });
      if (data.hero_image_url) {
        imagePreview.value = data.hero_image_url;
      }
      if (data.logo_url) {
        logoPreview.value = data.logo_url;
      }
      if (data.icon_url) {
        iconPreview.value = data.icon_url;
      }
    }
  } catch (error) {
    toast({ variant: 'destructive', title: t('common.error'), description: t('settings.landing_page.load_error') });
  } finally {
    isLoading.value = false;
  }
}

// File change handlers
function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    const file = target.files[0];
    imageFile.value = file;
    const reader = new FileReader();
    reader.onload = function(e) {
      imagePreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
}

function removeImage() {
  imageFile.value = null;
  imagePreview.value = null;
  if (fileInput.value) fileInput.value.value = '';
}

function handleLogoChange(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    const file = target.files[0];
    logoFile.value = file;
    const reader = new FileReader();
    reader.onload = function(e) {
      logoPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
    // Auto extract colors
    extractColorsFromFile(file);
  }
}

function removeLogo() {
  logoFile.value = null;
  logoPreview.value = null;
  suggestedColors.value = [];
  if (logoInput.value) logoInput.value.value = '';
}

function handleIconChange(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    const file = target.files[0];
    iconFile.value = file;
    const reader = new FileReader();
    reader.onload = function(e) {
      iconPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
}

function removeIcon() {
  iconFile.value = null;
  iconPreview.value = null;
  if (iconInput.value) iconInput.value.value = '';
}

async function extractColorsFromFile(file: File) {
  isExtractingColors.value = true;
  suggestedColors.value = [];
  try {
    const formData = new FormData();
    formData.append('image', file);
    const res = await fetch('/api/admin/settings/landing-page/extract-colors', {
      method: 'POST',
      headers: { 
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Accept': 'application/json' // <-- Agrégalo también aquí
      },
      body: formData,
    });
    if (res.ok) {
      const data = await res.json();
      suggestedColors.value = data.colors || [];
    }
  } catch (error) {
    console.error('Error al extraer colores:', error);
  } finally {
    isExtractingColors.value = false;
  }
}

function applySuggestedColors() {
  for (const c of suggestedColors.value) {
    if (c.role === 'primary') form.setFieldValue('primary_color', c.hex);
    if (c.role === 'secondary') form.setFieldValue('secondary_color', c.hex);
    if (c.role === 'accent') form.setFieldValue('accent_color', c.hex);
  }
  toast({ title: '✨ Colores aplicados', description: 'Los colores sugeridos se han aplicado. Recuerda guardar los cambios.' });
}

function applySingleColor(hex: string, role: string) {
  if (role === 'primary') form.setFieldValue('primary_color', hex);
  if (role === 'secondary') form.setFieldValue('secondary_color', hex);
  if (role === 'accent') form.setFieldValue('accent_color', hex);
}

const onSubmit = form.handleSubmit(async function(values) {
  isSaving.value = true;
  try {
    const formData = new FormData();
    formData.append('_method', 'PUT');
    Object.keys(values).forEach(function(key) {
      const val = (values as any)[key];
      formData.append(key, val || '');
    });
    if (imageFile.value) {
      formData.append('hero_image', imageFile.value);
    }
    if (logoFile.value) {
      formData.append('logo', logoFile.value);
    }
    if (iconFile.value) {
      formData.append('icon', iconFile.value);
    }
    const response = await fetch('/api/admin/settings/landing-page', {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${localStorage.getItem('auth_token')}` },
      body: formData
    });
    if (response.ok) {
      toast({ title: t('common.success'), description: t('settings.landing_page.save_success') });
      await fetchSettings();
      await fetchBranding();
    } else {
      throw new Error('Failed to update');
    }
  } catch (error) {
    toast({ variant: 'destructive', title: t('common.error'), description: t('settings.landing_page.save_error') });
  } finally {
    isSaving.value = false;
  }
});

onMounted(function() {
  fetchSettings();
});
</script>

<template>
  <div class="h-full flex-1 flex flex-col space-y-6 md:space-y-8 p-4 md:p-8 max-w-5xl mx-auto w-full">
    <div class="flex flex-col space-y-2">
      <h2 class="text-3xl font-extrabold tracking-tight">{{ t('settings.landing_page.title') }}</h2>
      <p class="text-muted-foreground">
        {{ t('settings.landing_page.description') }}
      </p>
    </div>

    <div v-if="isLoading" class="flex items-center justify-center p-20">
      <div class="flex flex-col items-center gap-4">
        <div class="size-10 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
        <span class="text-sm font-medium text-muted-foreground">{{ t('common.loading') }}</span>
      </div>
    </div>

    <form v-else @submit="onSubmit" class="space-y-8 mx-auto w-full">
      <div class="grid gap-8">

        <!-- Branding Section -->
        <Card class="overflow-hidden border-border/50 shadow-sm">
          <CardHeader class="bg-muted/30">
            <CardTitle class="text-xl flex items-center gap-2">
              <Palette class="size-5 text-primary" />
              {{ t('settings.landing_page.branding_section') }}
            </CardTitle>
            <CardDescription>{{ t('settings.landing_page.branding_description') }}</CardDescription>
          </CardHeader>
          <CardContent class="p-6 space-y-6">
            <!-- Display Name -->
            <FormField v-slot="{ componentField }" name="display_name">
              <FormItem>
                <FormLabel>{{ t('settings.landing_page.display_name') }}</FormLabel>
                <FormControl>
                  <Input type="text" :placeholder="t('settings.landing_page.display_name_pl')" v-bind="componentField" />
                </FormControl>
                <p class="text-xs text-muted-foreground mt-1">{{ t('settings.landing_page.display_name_hint') }}</p>
                <FormMessage />
              </FormItem>
            </FormField>

            <!-- Logo & Icon Upload -->
            <div class="grid sm:grid-cols-2 gap-6">
              <!-- Logo Upload -->
              <div class="space-y-2">
                <Label class="flex items-center gap-2">
                  <Image class="size-4 text-primary" />
                  Logotipo
                </Label>
                <p class="text-xs text-muted-foreground">Se muestra en el header y footer de la tienda. Recomendación: formato horizontal, fondo transparente (PNG).</p>
                <div 
                  @click="logoInput?.click()"
                  class="relative h-32 rounded-xl border-2 border-dashed border-border/60 hover:border-primary/50 hover:bg-muted/20 transition-all cursor-pointer overflow-hidden group flex flex-col items-center justify-center gap-2 bg-muted/10"
                >
                  <template v-if="logoPreview">
                    <img :src="logoPreview" alt="Logo" class="max-h-full max-w-full object-contain p-3 transition-transform group-hover:scale-105" />
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                      <Button type="button" variant="secondary" size="sm" class="gap-2">
                        <Upload class="size-4" />
                        Cambiar logo
                      </Button>
                    </div>
                    <Button 
                      type="button" 
                      variant="destructive" 
                      size="icon" 
                      class="absolute top-2 right-2 size-7 opacity-0 group-hover:opacity-100 transition-opacity"
                      @click.stop="removeLogo"
                    >
                      <X class="size-3.5" />
                    </Button>
                  </template>
                  <template v-else>
                    <div class="size-10 rounded-full bg-muted flex items-center justify-center text-muted-foreground group-hover:bg-primary/10 group-hover:text-primary transition-colors">
                      <Image class="size-5" />
                    </div>
                    <p class="text-xs text-muted-foreground">Clic para subir logotipo</p>
                  </template>
                </div>
                <input 
                  type="file" 
                  ref="logoInput" 
                  class="hidden" 
                  accept="image/png,image/jpeg,image/webp,image/svg+xml"
                  @change="handleLogoChange"
                />
              </div>

              <!-- Icon/Favicon Upload -->
              <div class="space-y-2">
                <Label class="flex items-center gap-2">
                  <Sparkles class="size-4 text-primary" />
                  Ícono / Favicon
                </Label>
                <p class="text-xs text-muted-foreground">Se muestra en la pestaña del navegador. Recomendación: cuadrado, mínimo 32×32px (PNG, ICO).</p>
                <div 
                  @click="iconInput?.click()"
                  class="relative h-32 rounded-xl border-2 border-dashed border-border/60 hover:border-primary/50 hover:bg-muted/20 transition-all cursor-pointer overflow-hidden group flex flex-col items-center justify-center gap-2 bg-muted/10"
                >
                  <template v-if="iconPreview">
                    <img :src="iconPreview" alt="Icon" class="max-h-20 max-w-20 object-contain p-2 transition-transform group-hover:scale-110" />
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                      <Button type="button" variant="secondary" size="sm" class="gap-2">
                        <Upload class="size-4" />
                        Cambiar ícono
                      </Button>
                    </div>
                    <Button 
                      type="button" 
                      variant="destructive" 
                      size="icon" 
                      class="absolute top-2 right-2 size-7 opacity-0 group-hover:opacity-100 transition-opacity"
                      @click.stop="removeIcon"
                    >
                      <X class="size-3.5" />
                    </Button>
                  </template>
                  <template v-else>
                    <div class="size-10 rounded-full bg-muted flex items-center justify-center text-muted-foreground group-hover:bg-primary/10 group-hover:text-primary transition-colors">
                      <Sparkles class="size-5" />
                    </div>
                    <p class="text-xs text-muted-foreground">Clic para subir ícono</p>
                  </template>
                </div>
                <input 
                  type="file" 
                  ref="iconInput" 
                  class="hidden" 
                  accept="image/png,image/jpeg,image/webp,image/x-icon,image/svg+xml"
                  @change="handleIconChange"
                />
              </div>
            </div>

            <!-- Color Suggestions from Logo -->
            <Transition name="fade">
              <div v-if="isExtractingColors || suggestedColors.length > 0" class="rounded-xl border border-primary/20 bg-primary/[0.03] p-5 space-y-4">
                <div class="flex items-center gap-2">
                  <Sparkles class="size-4 text-primary" />
                  <p class="text-sm font-semibold text-foreground">Colores sugeridos de tu logotipo</p>
                </div>
                
                <div v-if="isExtractingColors" class="flex items-center gap-3 py-3">
                  <Loader2 class="size-5 text-primary animate-spin" />
                  <span class="text-sm text-muted-foreground">Analizando colores del logotipo...</span>
                </div>

                <template v-else>
                  <p class="text-xs text-muted-foreground">Hemos extraído los colores dominantes de tu imagen. Puedes aplicarlos uno por uno o todos a la vez.</p>
                  
                  <div class="flex items-center gap-3 flex-wrap">
                    <button 
                      v-for="(color, i) in suggestedColors" 
                      :key="i"
                      type="button"
                      @click="applySingleColor(color.hex, color.role)"
                      class="group/chip flex items-center gap-2.5 px-3 py-2 rounded-xl border bg-card hover:bg-muted/50 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 cursor-pointer"
                    >
                      <div 
                        class="w-8 h-8 rounded-lg shadow-inner border border-black/10 transition-transform group-hover/chip:scale-110"
                        :style="{ backgroundColor: color.hex }"
                      ></div>
                      <div class="text-left">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">
                          {{ color.role === 'primary' ? 'Primario' : color.role === 'secondary' ? 'Secundario' : 'Acento' }}
                        </p>
                        <p class="text-xs font-mono text-foreground">{{ color.hex }}</p>
                      </div>
                      <Check class="size-3.5 text-primary opacity-0 group-hover/chip:opacity-100 transition-opacity" />
                    </button>
                  </div>

                  <Button 
                    type="button" 
                    variant="outline" 
                    size="sm" 
                    class="gap-2 border-primary/30 text-primary hover:bg-primary/10 font-semibold"
                    @click="applySuggestedColors"
                  >
                    <Palette class="size-4" />
                    Aplicar todos los colores sugeridos
                  </Button>
                </template>
              </div>
            </Transition>

            <!-- Color Pickers -->
            <div class="grid sm:grid-cols-3 gap-6">
              <FormField v-slot="{ componentField }" name="primary_color">
                <FormItem>
                  <FormLabel>{{ t('settings.landing_page.primary_color') }}</FormLabel>
                  <div class="flex items-center gap-3">
                    <input
                      type="color"
                      :value="componentField.modelValue"
                      @input="(e: Event) => form.setFieldValue('primary_color', (e.target as HTMLInputElement).value)"
                      class="h-10 w-14 rounded-lg border border-border cursor-pointer bg-transparent p-0.5"
                    />
                    <FormControl>
                      <Input type="text" v-bind="componentField" class="font-mono text-sm uppercase" maxlength="7" />
                    </FormControl>
                  </div>
                  <p class="text-xs text-muted-foreground mt-1">{{ t('settings.landing_page.primary_color_hint') }}</p>
                  <FormMessage />
                </FormItem>
              </FormField>

              <FormField v-slot="{ componentField }" name="secondary_color">
                <FormItem>
                  <FormLabel>{{ t('settings.landing_page.secondary_color') }}</FormLabel>
                  <div class="flex items-center gap-3">
                    <input
                      type="color"
                      :value="componentField.modelValue"
                      @input="(e: Event) => form.setFieldValue('secondary_color', (e.target as HTMLInputElement).value)"
                      class="h-10 w-14 rounded-lg border border-border cursor-pointer bg-transparent p-0.5"
                    />
                    <FormControl>
                      <Input type="text" v-bind="componentField" class="font-mono text-sm uppercase" maxlength="7" />
                    </FormControl>
                  </div>
                  <p class="text-xs text-muted-foreground mt-1">{{ t('settings.landing_page.secondary_color_hint') }}</p>
                  <FormMessage />
                </FormItem>
              </FormField>

              <FormField v-slot="{ componentField }" name="accent_color">
                <FormItem>
                  <FormLabel>{{ t('settings.landing_page.accent_color') }}</FormLabel>
                  <div class="flex items-center gap-3">
                    <input
                      type="color"
                      :value="componentField.modelValue"
                      @input="(e: Event) => form.setFieldValue('accent_color', (e.target as HTMLInputElement).value)"
                      class="h-10 w-14 rounded-lg border border-border cursor-pointer bg-transparent p-0.5"
                    />
                    <FormControl>
                      <Input type="text" v-bind="componentField" class="font-mono text-sm uppercase" maxlength="7" />
                    </FormControl>
                  </div>
                  <p class="text-xs text-muted-foreground mt-1">{{ t('settings.landing_page.accent_color_hint') }}</p>
                  <FormMessage />
                </FormItem>
              </FormField>
            </div>

            <!-- Live Preview -->
            <div class="rounded-xl border bg-muted/10 p-4 space-y-3">
              <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ t('settings.landing_page.color_preview') }}</p>
              <div class="flex items-center gap-3 flex-wrap">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-lg shadow-sm border" :style="{ backgroundColor: form.values.primary_color }"></div>
                  <span class="text-xs text-muted-foreground">Primary</span>
                </div>
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-lg shadow-sm border" :style="{ backgroundColor: form.values.secondary_color }"></div>
                  <span class="text-xs text-muted-foreground">Secondary</span>
                </div>
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-lg shadow-sm border" :style="{ backgroundColor: form.values.accent_color }"></div>
                  <span class="text-xs text-muted-foreground">Accent</span>
                </div>
              </div>
              <!-- Mini mockup -->
              <div class="rounded-lg border overflow-hidden mt-2">
                <div class="h-1.5 w-full" :style="{ background: `linear-gradient(to right, ${form.values.primary_color}, ${form.values.secondary_color}, ${form.values.accent_color})` }"></div>
                <div class="p-3 bg-white flex items-center gap-2">
                  <div class="w-4 h-4 rounded" :style="{ backgroundColor: form.values.primary_color }"></div>
                  <div class="h-2 w-20 rounded-full bg-muted"></div>
                  <div class="ml-auto flex gap-1.5">
                    <div class="h-5 w-14 rounded text-[9px] font-bold flex items-center justify-center text-white" :style="{ backgroundColor: form.values.primary_color }">Botón</div>
                    <div class="h-5 w-14 rounded text-[9px] font-bold flex items-center justify-center border" :style="{ borderColor: form.values.secondary_color, color: form.values.secondary_color }">Link</div>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Hero Section -->
        <Card class="overflow-hidden border-border/50 shadow-sm">
          <CardHeader class="bg-muted/30">
            <CardTitle class="text-xl flex items-center gap-2">
              <Upload class="size-5 text-primary" />
              {{ t('settings.landing_page.hero_section') }}
            </CardTitle>
            <CardDescription>{{ t('settings.landing_page.hero_description') }}</CardDescription>
          </CardHeader>
          <CardContent class="p-6 space-y-6">
            <div class="grid md:grid-cols-2 gap-6">
              <div class="space-y-4">
                <FormField v-slot="{ componentField }" name="hero_title">
                  <FormItem>
                    <FormLabel>{{ t('settings.landing_page.hero_title') }}</FormLabel>
                    <FormControl>
                      <Input type="text" :placeholder="t('settings.landing_page.hero_title_pl')" v-bind="componentField" />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                </FormField>

                <FormField v-slot="{ componentField }" name="hero_subtitle">
                  <FormItem>
                    <FormLabel>{{ t('settings.landing_page.hero_subtitle') }}</FormLabel>
                    <FormControl>
                      <Textarea :placeholder="t('settings.landing_page.hero_subtitle_pl')" v-bind="componentField" class="min-h-[100px]" />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                </FormField>
              </div>

              <!-- Hero Image Upload -->
              <div class="space-y-2">
                <Label>{{ t('settings.landing_page.hero_image') }}</Label>
                <div 
                  @click="fileInput?.click()"
                  class="relative aspect-video rounded-xl border-2 border-dashed border-border/60 hover:border-primary/50 hover:bg-muted/20 transition-all cursor-pointer overflow-hidden group flex flex-col items-center justify-center gap-3 bg-muted/10"
                >
                  <template v-if="imagePreview">
                    <img :src="imagePreview" alt="Hero Preview" class="w-full h-full object-cover transition-transform group-hover:scale-105" />
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                      <Button type="button" variant="secondary" size="sm" class="gap-2">
                        <Upload class="size-4" />
                        Cambiar imagen
                      </Button>
                    </div>
                    <Button 
                      type="button" 
                      variant="destructive" 
                      size="icon" 
                      class="absolute top-2 right-2 size-8 opacity-0 group-hover:opacity-100 transition-opacity"
                      @click.stop="removeImage"
                    >
                      <X class="size-4" />
                    </Button>
                  </template>
                  <template v-else>
                    <div class="size-12 rounded-full bg-muted flex items-center justify-center text-muted-foreground group-hover:bg-primary/10 group-hover:text-primary transition-colors">
                      <ImageOff class="size-6" />
                    </div>
                    <div class="text-center">
                      <p class="text-sm font-medium">{{ t('settings.landing_page.image_upload_hint') }}</p>
                      <p class="text-xs text-muted-foreground mt-1">{{ t('settings.landing_page.image_optional') }}</p>
                    </div>
                  </template>
                </div>
                <input 
                  type="file" 
                  ref="fileInput" 
                  class="hidden" 
                  accept="image/*"
                  @change="handleFileChange"
                />
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- About Us & Contact -->
        <div class="grid md:grid-cols-2 gap-8">
          <!-- About Us Section -->
          <Card class="border-border/50 shadow-sm">
            <CardHeader class="bg-muted/30">
              <CardTitle class="text-xl">{{ t('settings.landing_page.about_us_section') }}</CardTitle>
              <CardDescription>{{ t('settings.landing_page.about_us_description') }}</CardDescription>
            </CardHeader>
            <CardContent class="p-6">
              <FormField v-slot="{ componentField }" name="about_us_text">
                <FormItem>
                  <FormLabel>{{ t('settings.landing_page.about_us_text') }}</FormLabel>
                  <FormControl>
                    <Textarea class="min-h-[200px]" :placeholder="t('settings.landing_page.about_us_pl')" v-bind="componentField" />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              </FormField>
            </CardContent>
          </Card>

          <!-- Contact Section -->
          <Card class="border-border/50 shadow-sm">
            <CardHeader class="bg-muted/30">
              <CardTitle class="text-xl">{{ t('settings.landing_page.contact_section') }}</CardTitle>
              <CardDescription>{{ t('settings.landing_page.contact_description') }}</CardDescription>
            </CardHeader>
            <CardContent class="p-6 space-y-6">
              <FormField v-slot="{ componentField }" name="contact_email">
                <FormItem>
                  <FormLabel>{{ t('settings.landing_page.contact_email') }}</FormLabel>
                  <FormControl>
                    <Input type="email" :placeholder="t('settings.landing_page.contact_email_pl')" v-bind="componentField" />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              </FormField>

              <FormField v-slot="{ componentField }" name="contact_phone">
                <FormItem>
                  <FormLabel>{{ t('settings.landing_page.contact_phone') }}</FormLabel>
                  <FormControl>
                    <Input type="text" :placeholder="t('settings.landing_page.contact_phone_pl')" v-bind="componentField" />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              </FormField>
            </CardContent>
          </Card>
        </div>
      </div>

      <div class="flex justify-end pt-4">
        <Button type="submit" size="lg" :disabled="isSaving" class="px-10 h-12 shadow-md hover:shadow-lg transition-all">
          <template v-if="isSaving">
            <div class="size-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
            {{ t('common.saving') }}
          </template>
          <span v-else>{{ t('common.save_changes') }}</span>
        </Button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.fade-enter-active {
  transition: all 0.3s ease-out;
}
.fade-leave-active {
  transition: all 0.2s ease-in;
}
.fade-enter-from {
  opacity: 0;
  transform: translateY(-8px);
}
.fade-leave-to {
  opacity: 0;
  transform: translateY(4px);
}
</style>
