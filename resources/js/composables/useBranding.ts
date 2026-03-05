import { ref, onMounted } from 'vue'

export interface Branding {
  display_name: string
  logo_url: string | null
  icon_url: string | null
  primary_color: string
  secondary_color: string
  accent_color: string
}

const CACHE_KEY = 'nidya_branding'

const branding = ref<Branding | null>(null)
const isLoaded = ref(false)

// Restore from cache immediately (synchronous, before any render)
try {
  const cached = localStorage.getItem(CACHE_KEY)
  if (cached) {
    const parsed = JSON.parse(cached) as Branding
    branding.value = parsed
    applyBrandingToDOM(parsed)
  }
} catch {
  // ignore corrupt cache
}

/**
 * Converts a hex color (#RRGGBB) to an HSL string "H S% L%"
 * compatible with the shadcn/ui CSS variable format.
 */
function hexToHsl(hex: string): string {
  const cleaned = hex.replace('#', '')
  const r = parseInt(cleaned.substring(0, 2), 16) / 255
  const g = parseInt(cleaned.substring(2, 4), 16) / 255
  const b = parseInt(cleaned.substring(4, 6), 16) / 255

  const max = Math.max(r, g, b)
  const min = Math.min(r, g, b)
  const l = (max + min) / 2

  let h = 0
  let s = 0

  if (max !== min) {
    const d = max - min
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min)

    switch (max) {
      case r:
        h = ((g - b) / d + (g < b ? 6 : 0)) * 60
        break
      case g:
        h = ((b - r) / d + 2) * 60
        break
      case b:
        h = ((r - g) / d + 4) * 60
        break
    }
  }

  return `${Math.round(h)} ${Math.round(s * 100)}% ${Math.round(l * 100)}%`
}

/**
 * Determines if a color is light enough that white foreground text
 * would be unreadable. Returns an appropriate HSL foreground value.
 */
function foregroundForHex(hex: string): string {
  const cleaned = hex.replace('#', '')
  const r = parseInt(cleaned.substring(0, 2), 16)
  const g = parseInt(cleaned.substring(2, 4), 16)
  const b = parseInt(cleaned.substring(4, 6), 16)
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255
  return luminance > 0.6 ? '0 0% 9%' : '0 0% 98%'
}

function applyBrandingToDOM(brand: Branding) {
  const root = document.documentElement

  root.style.setProperty('--primary', hexToHsl(brand.primary_color))
  root.style.setProperty('--primary-foreground', foregroundForHex(brand.primary_color))

  root.style.setProperty('--ring', hexToHsl(brand.primary_color))

  root.style.setProperty('--sidebar-primary', hexToHsl(brand.primary_color))
  root.style.setProperty('--sidebar-primary-foreground', foregroundForHex(brand.primary_color))

  const secondaryHsl = hexToHsl(brand.secondary_color)
  root.style.setProperty('--secondary', secondaryHsl)
  root.style.setProperty('--secondary-foreground', foregroundForHex(brand.secondary_color))
  root.style.setProperty('--shop-secondary', secondaryHsl)

  const accentHsl = hexToHsl(brand.accent_color)
  root.style.setProperty('--accent', accentHsl)
  root.style.setProperty('--accent-foreground', foregroundForHex(brand.accent_color))
  root.style.setProperty('--shop-accent', accentHsl)
  root.style.setProperty('--shop-accent-foreground', foregroundForHex(brand.accent_color))

  // Browser tab title
  document.title = `${brand.display_name} | Nidya`

  // Dynamic favicon from icon_url
  if (brand.icon_url) {
    let link = document.querySelector<HTMLLinkElement>("link[rel~='icon']")
    if (!link) {
      link = document.createElement('link')
      link.rel = 'icon'
      document.head.appendChild(link)
    }
    link.href = brand.icon_url
  }
}

export function useBranding() {
  async function fetchBranding() {
    try {
      const res = await fetch('/api/shop/landing-page')
      if (res.ok) {
        const data = await res.json()
        if (data.branding) {
          branding.value = data.branding
          applyBrandingToDOM(data.branding)
          localStorage.setItem(CACHE_KEY, JSON.stringify(data.branding))
        }
      }
    } catch (err) {
      console.error('[useBranding] Failed to load branding:', err)
    } finally {
      isLoaded.value = true
    }
  }

  onMounted(fetchBranding)

  return { branding, isLoaded, fetchBranding, applyBrandingToDOM }
}
