<script setup>
import { useI18n } from 'vue-i18n';
import PageHeader from '@/components/app/PageHeader.vue';

const { t } = useI18n();
</script>

<template>
  <div class="flex flex-col gap-8">
    <PageHeader :title="t('users.users_list_title')" :description="t('common.in_development')" />
    <div class="flex items-center justify-center h-[400px] rounded-lg border border-dashed">
      <p class="text-muted-foreground text-lg">{{ t('common.coming_soon') }}</p>
    </div>
  </div>
</template>
