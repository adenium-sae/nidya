<script setup lang="ts">
import { ref } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth.store';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { useI18n } from 'vue-i18n';
import { useBranding } from '@/composables/useBranding';
import { Store } from 'lucide-vue-next';

const { t } = useI18n();
const router = useRouter();
const authStore = useAuthStore();
const { branding } = useBranding();

const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref('');

async function handleLogin() {
  loading.value = true;
  error.value = '';

  try {
    await authStore.login({
      email: email.value,
      password: password.value,
    });
    router.push('/panel/dashboard');
  } catch (err: any) {
    console.error('Login failed', err);
    error.value = err.response?.data?.message || t('auth.invalid_credentials');
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <AuthLayout>
    <div class="w-full max-w-md space-y-8">
      <div class="space-y-2 text-center">
        <div class="flex justify-center mb-6">
          <template v-if="branding?.logo_url">
            <img :src="branding.logo_url" alt="Logo" class="h-12 w-auto object-contain" />
          </template>
          <template v-else>
            <div class="flex items-center justify-center size-12 rounded-xl bg-primary text-primary-foreground shadow-sm">
              <Store class="size-6" />
            </div>
          </template>
        </div>

        <h1 class="text-3xl font-bold tracking-tight">
          {{ t('auth.sign_in') }} a {{ branding?.display_name || 'Nidya' }}
        </h1>
        <p class="text-muted-foreground">
          {{ t('auth.login_message') }}
        </p>
      </div>

      <div class="space-y-4">
        <div v-if="error" class="p-3 text-sm text-red-500 bg-red-50 rounded-md">
          {{ error }}
        </div>
        <div class="space-y-2">
          <Label for="email">{{ t('auth.email') }}</Label>
          <Input id="email" type="email" placeholder="m@example.com" v-model="email" />
        </div>
        <div class="space-y-2">
          <Label for="password">{{ t('auth.password') }}</Label>
          <Input id="password" type="password" v-model="password" @keyup.enter="handleLogin" />
        </div>
      </div>

      <div class="flex flex-col gap-4">
        <Button class="w-full" :disabled="loading" @click="handleLogin">
          <span v-if="loading">{{ t('auth.signing_in') }}</span>
          <span v-else>{{ t('auth.sign_in') }}</span>
        </Button>

      </div>
    </div>
  </AuthLayout>
</template>
