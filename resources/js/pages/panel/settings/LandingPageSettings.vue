<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import * as z from 'zod'
import { useToast } from '@/components/ui/toast/use-toast'

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'

const { toast } = useToast()

const token = localStorage.getItem('auth_token'); // Or however auth token is accessed in this codebase
const apiHeaders: Record<string, string> = token ? { Authorization: `Bearer ${token}` } : {};

const formSchema = toTypedSchema(
  z.object({
    hero_title: z.string().nullable().optional(),
    hero_subtitle: z.string().nullable().optional(),
    hero_image_url: z.string().url('Must be a valid URL').nullable().optional().or(z.literal('')),
    about_us_text: z.string().nullable().optional(),
    contact_email: z.string().email('Must be a valid email').nullable().optional().or(z.literal('')),
    contact_phone: z.string().nullable().optional(),
  })
)

const form = useForm({
  validationSchema: formSchema,
  initialValues: {
    hero_title: '',
    hero_subtitle: '',
    hero_image_url: '',
    about_us_text: '',
    contact_email: '',
    contact_phone: '',
  },
})

const isLoading = ref(true)
const isSaving = ref(false)

const fetchSettings = async () => {
  isLoading.value = true
  try {
    const response = await fetch('/api/admin/settings/landing-page', {
        headers: apiHeaders
    })
    
    if (response.ok) {
        const data = await response.json()
        form.setValues({
            hero_title: data.hero_title || '',
            hero_subtitle: data.hero_subtitle || '',
            hero_image_url: data.hero_image_url || '',
            about_us_text: data.about_us_text || '',
            contact_email: data.contact_email || '',
            contact_phone: data.contact_phone || '',
        })
    }
  } catch (error) {
    toast({ variant: 'destructive', title: 'Error', description: 'Failed to load settings' })
  } finally {
    isLoading.value = false
  }
}

const onSubmit = form.handleSubmit(async (values) => {
  isSaving.value = true
  try {
    const response = await fetch('/api/admin/settings/landing-page', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            ...apiHeaders
        },
        body: JSON.stringify(values)
    })

    if (response.ok) {
        toast({ title: 'Success', description: 'Landing page settings updated successfully' })
    } else {
        throw new Error('Failed to update')
    }
  } catch (error) {
    toast({ variant: 'destructive', title: 'Error', description: 'Failed to update settings' })
  } finally {
    isSaving.value = false
  }
})

onMounted(() => {
  fetchSettings()
})
</script>

<template>
  <div class="h-full flex-1 flex-col space-y-8 p-8 md:flex">
    <div class="flex items-center justify-between space-y-2">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">Landing Page Settings</h2>
        <p class="text-muted-foreground">
          Manage the content displayed on the public shop landing page.
        </p>
      </div>
    </div>

    <div v-if="isLoading" class="flex justify-center p-8">
      <span class="text-muted-foreground">Loading settings...</span>
    </div>

    <form v-else @submit="onSubmit" class="space-y-8">
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <!-- Hero Section -->
        <Card class="col-span-1 md:col-span-2 lg:col-span-3">
          <CardHeader>
            <CardTitle>Hero Section</CardTitle>
            <CardDescription>The main headline and subtitle shown at the top of the page.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <FormField v-slot="{ componentField }" name="hero_title">
              <FormItem>
                <FormLabel>Hero Title</FormLabel>
                <FormControl>
                  <Input type="text" placeholder="Welcome to Nidya" v-bind="componentField" />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="hero_subtitle">
              <FormItem>
                <FormLabel>Hero Subtitle</FormLabel>
                <FormControl>
                  <Textarea placeholder="The best place for your needs..." v-bind="componentField" />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="hero_image_url">
              <FormItem>
                <FormLabel>Hero Image URL</FormLabel>
                <FormControl>
                  <Input type="text" placeholder="https://example.com/image.jpg" v-bind="componentField" />
                </FormControl>
                <p class="text-[0.8rem] text-muted-foreground">Optional background image for the hero section.</p>
                <FormMessage />
              </FormItem>
            </FormField>
          </CardContent>
        </Card>

        <!-- About Us Section -->
        <Card class="col-span-1 md:col-span-2 lg:col-span-3">
          <CardHeader>
            <CardTitle>About Us Section</CardTitle>
            <CardDescription>Information about your store or brand.</CardDescription>
          </CardHeader>
          <CardContent>
            <FormField v-slot="{ componentField }" name="about_us_text">
              <FormItem>
                <FormLabel>About Us Text</FormLabel>
                <FormControl>
                  <Textarea class="min-h-[150px]" placeholder="Briefly describe who you are..." v-bind="componentField" />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>
          </CardContent>
        </Card>

        <!-- Contact Section -->
        <Card class="col-span-1 md:col-span-2 lg:col-span-3">
          <CardHeader>
            <CardTitle>Contact Information</CardTitle>
            <CardDescription>Public contact details shown on the footer.</CardDescription>
          </CardHeader>
          <CardContent class="grid gap-4 md:grid-cols-2">
            <FormField v-slot="{ componentField }" name="contact_email">
              <FormItem>
                <FormLabel>Contact Email</FormLabel>
                <FormControl>
                  <Input type="email" placeholder="contact@nidya.com" v-bind="componentField" />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="contact_phone">
              <FormItem>
                <FormLabel>Contact Phone</FormLabel>
                <FormControl>
                  <Input type="text" placeholder="+1 234 567 890" v-bind="componentField" />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>
          </CardContent>
        </Card>
      </div>

      <div class="flex justify-end">
        <Button type="submit" :disabled="isSaving">
            <span v-if="isSaving">Saving...</span>
            <span v-else>Save Settings</span>
        </Button>
      </div>
    </form>
  </div>
</template>
