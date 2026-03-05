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
import { ImageOff, Upload, X } from 'lucide-vue-next';

const { t } = useI18n();
const { toast } = useToast();
const imagePreview = ref<string | null>(null);
const imageFile = ref<File | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const formSchema = toTypedSchema(
  z.object({
    hero_title: z.string().nullable().optional().or(z.literal('')),
    hero_subtitle: z.string().nullable().optional().or(z.literal('')),
    about_us_text: z.string().nullable().optional().or(z.literal('')),
    contact_email: z.string().email(t('products.invalid_email')).nullable().optional().or(z.literal('')),
    contact_phone: z.string().nullable().optional().or(z.literal('')),
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
  },
});

const isLoading = ref(true);
const isSaving = ref(false);

async function fetchSettings() {
  isLoading.value = true;
  try {
    const response = await fetch('/api/admin/settings/landing-page', {
      headers: { 'Authorization': `Bearer ${localStorage.getItem('auth_token')}` }
    });
    if (response.ok) {
      const data = await response.json();
      form.setValues({
        hero_title: data.hero_title || '',
        hero_subtitle: data.hero_subtitle || '',
        about_us_text: data.about_us_text || '',
        contact_email: data.contact_email || '',
        contact_phone: data.contact_phone || '',
      });
      if (data.hero_image_url) {
        imagePreview.value = data.hero_image_url;
      }
    }
  } catch (error) {
    toast({ variant: 'destructive', title: t('common.error'), description: t('settings.landing_page.load_error') });
  } finally {
    isLoading.value = false;
  }
}

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
  if (fileInput.value) {
    fileInput.value.value = '';
  }
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
    const response = await fetch('/api/admin/settings/landing-page', {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${localStorage.getItem('auth_token')}` },
      body: formData
    });
    if (response.ok) {
      toast({ title: t('common.success'), description: t('settings.landing_page.save_success') });
      fetchSettings();
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
  <div class="h-full flex-1 flex-col space-y-8 p-8 md:flex max-w-5xl mx-auto w-full">
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
