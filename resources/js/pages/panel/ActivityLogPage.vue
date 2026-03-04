<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { activityLogApi } from '@/api/activityLog.api';
import { useApiList } from '@/composables/useApiList';
import { DataTable, type Column, type Filter } from '@/components/ui/data-table';
import { ClipboardList } from 'lucide-vue-next';

const { t } = useI18n();

const {
  items: logs,
  isLoading,
  searchQuery,
  filterValues,
  pagination,
  fetch: fetchLogs,
  search,
  filter,
  changePage,
  changePerPage,
} = useApiList(activityLogApi.list, { perPage: 30 });

const typeColors: Record<string, string> = {
  auth:         'bg-blue-100 text-blue-800',
  inventory:    'bg-orange-100 text-orange-800',
  sales:        'bg-green-100 text-green-800',
  catalog:      'bg-purple-100 text-purple-800',
  organization: 'bg-cyan-100 text-cyan-800',
  system:       'bg-gray-100 text-gray-800',
};

const levelColors: Record<string, string> = {
  info:     'bg-blue-50 text-blue-700',
  warning:  'bg-yellow-100 text-yellow-800',
  error:    'bg-red-100 text-red-800',
  critical: 'bg-red-200 text-red-900 font-semibold',
};

const columns = computed<Column[]>(() => [
  { key: 'created_at',  label: t('common.date'),        type: 'date',   sortable: true },
  { key: 'user',        label: t('common.user'),         type: 'custom' },
  { key: 'store',       label: t('sidebar.stores'),      type: 'custom' },
  { key: 'type',        label: t('common.type'),         type: 'custom' },
  { key: 'level',       label: t('activity.level'),      type: 'custom' },
  { key: 'event',       label: t('activity.event'),      type: 'text'   },
  { key: 'description', label: t('common.description'),  type: 'text'   },
]);

const filters = computed<Filter[]>(() => [
  {
    key: 'type',
    label: t('common.type'),
    type: 'select',
    options: [
      { value: 'auth',         label: t('activity.types.auth') },
      { value: 'inventory',    label: t('activity.types.inventory') },
      { value: 'sales',        label: t('activity.types.sales') },
      { value: 'catalog',      label: t('activity.types.catalog') },
      { value: 'organization', label: t('activity.types.organization') },
      { value: 'system',       label: t('activity.types.system') },
    ],
  },
  {
    key: 'level',
    label: t('activity.level'),
    type: 'select',
    options: [
      { value: 'info',     label: t('activity.levels.info') },
      { value: 'warning',  label: t('activity.levels.warning') },
      { value: 'error',    label: t('activity.levels.error') },
      { value: 'critical', label: t('activity.levels.critical') },
    ],
  },
]);

onMounted(() => fetchLogs());
</script>

<template>
  <div class="h-[calc(100vh-120px)] flex flex-col">
    <div class="flex flex-col gap-6 h-full">
      <DataTable
        :columns="columns"
        :data="logs"
        :is-loading="isLoading"
        :search-value="searchQuery"
        :filters="filters"
        :filter-values="filterValues"
        :pagination="pagination"
        :search-placeholder="t('activity.search')"
        :empty-message="t('activity.empty')"
        :empty-icon="ClipboardList"
        class="flex-1 min-h-0"
        @search="search"
        @filter="filter"
        @page-change="changePage"
        @per-page-change="changePerPage"
      >
        <template #cell-user="{ row }">
          {{ row.user
            ? (row.user.profile?.first_name
                ? `${row.user.profile.first_name} ${row.user.profile.last_name ?? ''}`.trim()
                : row.user.email)
            : t('activity.system') }}
        </template>

        <template #cell-store="{ row }">
          {{ row.store?.name || '-' }}
        </template>

        <template #cell-type="{ row }">
          <span
            :class="typeColors[row.type] ?? 'bg-gray-100 text-gray-800'"
            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium uppercase tracking-wide"
          >
            {{ t(`activity.types.${row.type}`, row.type) }}
          </span>
        </template>

        <template #cell-level="{ row }">
          <span
            :class="levelColors[row.level] ?? 'bg-gray-100 text-gray-700'"
            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
          >
            {{ t(`activity.levels.${row.level}`, row.level) }}
          </span>
        </template>
      </DataTable>
    </div>
  </div>
</template>
