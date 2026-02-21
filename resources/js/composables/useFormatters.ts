import { useI18n } from 'vue-i18n'

export function useFormatters() {
  const { locale } = useI18n()

  const currentLocale = locale.value === 'es' ? 'es-MX' : 'en-US'

  function formatCurrency(value: string | number, currency?: string): string {
    const num = typeof value === 'string' ? parseFloat(value) : value
    if (isNaN(num)) return '$0.00'
    
    // Default currency based on locale if not provided
    const defaultCurrency = locale.value === 'es' ? 'MXN' : 'USD'
    const targetCurrency = currency || defaultCurrency

    return new Intl.NumberFormat(currentLocale, {
      style: 'currency',
      currency: targetCurrency,
    }).format(num)
  }

  function formatDate(value: string | Date | null, format: 'short' | 'long' = 'short'): string {
    if (!value) return '-'
    const date = new Date(value)
    return date.toLocaleDateString(currentLocale, {
      year: 'numeric',
      month: format === 'long' ? 'long' : 'short',
      day: 'numeric',
    })
  }

  function formatDateTime(value: string | Date | null): string {
    if (!value) return '-'
    const date = new Date(value)
    return date.toLocaleDateString(currentLocale, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  function formatNumber(value: string | number): string {
    const num = typeof value === 'string' ? parseFloat(value) : value
    if (isNaN(num)) return '0'
    return new Intl.NumberFormat(currentLocale).format(num)
  }

  return { formatCurrency, formatDate, formatDateTime, formatNumber }
}
