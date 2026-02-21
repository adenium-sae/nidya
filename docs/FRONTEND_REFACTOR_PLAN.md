# Nidya Frontend — Plan de Refactorización

## 1. Diagnóstico del Estado Actual

### 1.1 Resumen Técnico

| Aspecto | Actual |
|---------|--------|
| Framework | Vue 3 + Composition API |
| Routing | vue-router 4 |
| UI Kit | shadcn/vue (27 componentes) + Radix |
| Estilos | Tailwind CSS 3 |
| HTTP | axios (directo, sin capa de abstracción) |
| Validación | Zod (solo en ProductForm) |
| Estado global | Ninguno |
| TypeScript | Parcial (algunos archivos `.vue` usan `lang="ts"`, otros no) |
| Componentes custom | `DataTable`, `SearchableSelect`, `ProductForm`, `CategoryFormDialog` |

### 1.2 Inventario de Archivos (20 archivos Vue, ~4,700 líneas)

```
pages/ (15 archivos)
├── auth/
│   ├── SignInPage.vue          (82 líneas)
│   └── SignUpPage.vue          (no revisado, ~300 líneas)
├── panel/
│   ├── PanelRoot.vue           (11 líneas)
│   ├── DashboardPage.vue       (227 líneas)
│   ├── inventory/
│   │   ├── CategoriesPage.vue  (155 líneas)
│   │   ├── StockPage.vue       (316 líneas)
│   │   ├── WarehousesPage.vue  (382 líneas)
│   │   ├── MovementsPage.vue   (268 líneas)
│   │   ├── products/
│   │   │   ├── ProductListPage.vue   (342 líneas)
│   │   │   ├── CreateProductPage.vue (146 líneas)
│   │   │   └── EditProductPage.vue   (146 líneas)
│   │   └── adjustments/
│   │       ├── AdjustmentsPage.vue   (205 líneas)
│   │       ├── AdjustmentForm.vue    (242 líneas)
│   │       ├── EntryForm.vue         (240 líneas)
│   │       ├── ExitForm.vue          (248 líneas)
│   │       └── TransferForm.vue      (268 líneas)
│   └── sales/
│       └── PosPage.vue         (VACÍO)

components/ (4 archivos custom + 27 UI)
├── AppSidebar.vue              (290 líneas)
├── NavMain.vue / NavUser.vue / TeamSwitcher.vue
├── inventory/CategoryFormDialog.vue  (151 líneas)
├── products/ProductForm.vue    (587 líneas)
└── ui/ (27 componentes shadcn)
```

---

## 2. Problemas Identificados

### 🔴 P1 — Sin capa de servicios HTTP (Crítico)

**Problema:** Cada componente hace llamadas `axios.get/post` directas con `localStorage.getItem('auth_token')` repetido **en cada archivo**. Esto genera:

- **70+ repeticiones** de `const token = localStorage.getItem('auth_token')` en todo el código
- URLs hardcodeadas en cada componente
- Sin manejo centralizado de errores HTTP (401, 403, 500)
- Sin interceptores para refresh de token
- Imposible cambiar el mecanismo de auth sin tocar 15+ archivos

```js
// ❌ Patrón actual repetido en CADA archivo
const token = localStorage.getItem('auth_token');
const response = await axios.get('/api/admin/products', {
  headers: { Authorization: `Bearer ${token}` }
});
```

### 🔴 P2 — Sin sistema de estado global (Crítico)

**Problema:** No hay store para compartir estado entre componentes:

- El usuario se carga en `AppSidebar.vue` con un `onMounted` que llama a la API — si otro componente necesita el usuario, vuelve a llamar
- No hay estado compartido para: usuario autenticado, permisos, tienda activa, sucursales/almacenes (datos que se necesitan en múltiples vistas)
- Cada página carga sus propios datos sin cache

### 🔴 P3 — Código masivamente duplicado en formularios de ajuste (Crítico)

**Problema:** `EntryForm.vue`, `ExitForm.vue` y `AdjustmentForm.vue` son **~90% idénticos** (~240 líneas c/u):

| Sección | EntryForm | ExitForm | AdjustmentForm |
|---------|-----------|----------|----------------|
| Header (almacén + ubicación) | ✅ idéntico | ✅ idéntico | ✅ idéntico |
| Lista de items | ✅ idéntico | ✅ idéntico | ✅ idéntico |
| `handleProductSelect()` | ✅ idéntico | ✅ idéntico | ✅ idéntico |
| `addItem() / removeItem()` | ✅ idéntico | ✅ idéntico | ✅ idéntico |
| `handleWarehouseChange()` | ✅ idéntico | ✅ idéntico | ✅ idéntico |
| `handleSubmit()` | mode=`increment` | mode=`decrement` | mode=`absolute` |
| Motivos | hallazgo, recuento | dañado, extravío | recuento, corrección |

**Son 3 archivos de ~240 líneas donde solo cambian ~20 líneas.** La misma lógica está copiada-pegada 3 veces.

### 🟡 P4 — Interfaces TypeScript duplicadas 

Interfaces como `Category`, `Store`, `Warehouse`, `Product`, `StockItem` se definen localmente en **cada componente que las usa**. Si el backend cambia un campo, hay que actualizar 5+ archivos.

### 🟡 P5 — Helpers duplicados

- `formatCurrency()` está definido en `DashboardPage.vue`, `ProductListPage.vue`, `DataTable.vue` (3 veces)
- `getImageUrl()` está en `ProductListPage.vue` sin reusar
- Funciones de label/color de badges (`getTypeLabel`, `getTypeClass`, `getAdjustmentLabel`, etc.) repetidas

### 🟡 P6 — Sin composable para lógica de CRUD

Cada página de listado repite el mismo patrón:
```js
const items = ref([])
const isLoading = ref(true)
const searchQuery = ref('')

async function fetchItems() {
  isLoading.value = true
  try {
    const token = localStorage.getItem('auth_token')
    const response = await axios.get('/api/...', { headers: { ... } })
    items.value = response.data.data || response.data
  } catch (error) { ... }
  finally { isLoading.value = false }
}
```

Este patrón se repite en: `CategoriesPage`, `WarehousesPage`, `ProductListPage`, `StockPage`, `MovementsPage`, `AdjustmentsPage` (6 veces).

### 🟡 P7 — Rutas planas en app.js

Todas las rutas están en un solo archivo `app.js` sin organización por módulo. Funciona ahora con 13 rutas, pero escalar a 30+ será inmantenible.

### 🟡 P8 — Breadcrumbs estáticos

`PanelLayout.vue` tiene breadcrumbs hardcoded a "Dashboard > Overview" sin importar la ruta actual.

### 🟢 P9 — Inconsistencia de idioma

- Auth pages están en inglés ("Sign In", "Don't have an account?")
- El resto del panel está en español
- No hay sistema de i18n en el frontend

---

## 3. Arquitectura Propuesta

### 3.1 Estructura de Directorios Nueva

```
resources/js/
├── app.js                          # Bootstrap (app, router, pinia)
├── App.vue                         # Root component
├── bootstrap.js                    # Axios config
│
├── api/                            # 🆕 Capa de servicios HTTP
│   ├── client.js                   # Instancia axios con interceptores
│   ├── auth.api.js                 # login, logout, register, otp
│   ├── products.api.js             # CRUD productos
│   ├── categories.api.js           # CRUD categorías
│   ├── warehouses.api.js           # CRUD almacenes
│   ├── stock.api.js                # Stock, ajustes, transferencias, movimientos
│   ├── stores.api.js               # CRUD tiendas
│   ├── branches.api.js             # CRUD sucursales
│   ├── dashboard.api.js            # Dashboard stats
│   └── sales.api.js                # Ventas
│
├── stores/                         # 🆕 Estado global (Pinia)
│   ├── auth.store.js               # Usuario, token, permisos
│   └── app.store.js                # Config app, sidebar, breadcrumbs
│
├── composables/                    # 🆕 Lógica reutilizable
│   ├── useApiList.js               # fetch + loading + search + pagination
│   ├── useCrudDialog.js            # open/close dialog, form state, save
│   ├── useConfirmDelete.js         # Dialog de confirmación de borrado
│   └── useFormatters.js            # formatCurrency, formatDate, etc.
│
├── types/                          # 🆕 Interfaces TypeScript centralizadas
│   ├── models.ts                   # Product, Category, Warehouse, Stock, Sale...
│   ├── api.ts                      # ApiResponse, PaginatedResponse, etc.
│   └── ui.ts                       # Column, Action, Filter (de DataTable)
│
├── router/                         # 🆕 Router modular
│   ├── index.js                    # createRouter + guards
│   ├── auth.routes.js              # Rutas de auth
│   └── panel.routes.js             # Rutas del panel admin
│
├── lib/
│   └── utils.js                    # cn() helper (ya existe)
│
├── layouts/
│   ├── AuthLayout.vue              # (ya existe)
│   └── PanelLayout.vue             # ✏️ Breadcrumbs dinámicos
│
├── components/
│   ├── ui/                         # shadcn components (sin cambios)
│   ├── app/                        # 🆕 Componentes de aplicación reutilizables
│   │   ├── PageHeader.vue          # Título + descripción + acciones
│   │   ├── ConfirmDialog.vue       # Dialog genérico de confirmación
│   │   ├── StatusBadge.vue         # Badge activo/inactivo
│   │   └── EmptyState.vue          # Estado vacío reutilizable
│   ├── inventory/
│   │   ├── CategoryFormDialog.vue  # (ya existe)
│   │   └── StockAdjustmentForm.vue # 🆕 Unifica Entry/Exit/Adjustment
│   └── products/
│       └── ProductForm.vue         # (ya existe)
│
└── pages/                          # Páginas (más delgadas)
    ├── auth/
    │   ├── SignInPage.vue
    │   └── SignUpPage.vue
    └── panel/
        ├── PanelRoot.vue
        ├── DashboardPage.vue
        ├── inventory/
        │   ├── CategoriesPage.vue
        │   ├── StockPage.vue
        │   ├── WarehousesPage.vue
        │   ├── MovementsPage.vue
        │   ├── products/
        │   │   ├── ProductListPage.vue
        │   │   ├── CreateProductPage.vue
        │   │   └── EditProductPage.vue
        │   └── adjustments/
        │       ├── AdjustmentsPage.vue
        │       ├── AdjustmentFormPage.vue  # 🆕 Una sola página parametrizada
        │       └── TransferFormPage.vue
        └── sales/
            └── PosPage.vue
```

### 3.2 Diagrama de Capas

```
┌─────────────────────────────────────────────────────────┐
│                      PAGES                              │
│  Orquestan layout, cargan datos, manejan navegación     │
│  No contienen lógica de negocio ni llamadas HTTP        │
└────────────┬──────────────────────┬─────────────────────┘
             │                      │
     ┌───────▼───────┐      ┌──────▼──────┐
     │  COMPOSABLES  │      │ COMPONENTS  │
     │  Lógica       │      │ UI pura,    │
     │  reutilizable │      │ reciben     │
     │  con estado   │      │ props       │
     └───────┬───────┘      └─────────────┘
             │
     ┌───────▼───────┐      ┌─────────────┐
     │    STORES     │◄────►│     API     │
     │   (Pinia)     │      │  (axios)    │
     │  Estado       │      │  Endpoints  │
     │  global       │      │  tipados    │
     └───────────────┘      └──────┬──────┘
                                   │
                            ┌──────▼──────┐
                            │   Backend   │
                            │  Laravel    │
                            └─────────────┘
```

---

## 4. Detalle de Cada Capa

### 4.1 Capa API (`api/`)

#### `api/client.js` — Instancia central de Axios

```js
import axios from 'axios'
import { useAuthStore } from '@/stores/auth.store'
import router from '@/router'

const client = axios.create({
  baseURL: '/api',
  headers: { 'Accept': 'application/json' }
})

// Interceptor: agregar token automáticamente
client.interceptors.request.use((config) => {
  const auth = useAuthStore()
  if (auth.token) {
    config.headers.Authorization = `Bearer ${auth.token}`
  }
  return config
})

// Interceptor: manejar errores globales
client.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const auth = useAuthStore()
      auth.logout()
      router.push('/sign-in')
    }
    return Promise.reject(error)
  }
)

export default client
```

#### `api/products.api.js` — Ejemplo de módulo API

```js
import client from './client'

export const productsApi = {
  list(params = {}) {
    return client.get('/admin/products', { params })
  },
  
  show(id) {
    return client.get(`/admin/products/${id}`)
  },
  
  createSingle(data) {
    return client.post('/admin/products/single', data, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },
  
  update(id, data) {
    return client.put(`/admin/products/${id}`, data)
  },
  
  destroy(id) {
    return client.delete(`/admin/products/${id}`)
  }
}
```

**Impacto:** Elimina ~70 repeticiones de `localStorage.getItem('auth_token')` y centraliza todas las URLs.

### 4.2 Stores (`stores/`)

#### `stores/auth.store.js`

```js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth.api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token'))
  
  const isAuthenticated = computed(() => !!token.value)
  const fullName = computed(() => {
    if (!user.value?.profile) return user.value?.email || ''
    const p = user.value.profile
    return [p.first_name, p.last_name].filter(Boolean).join(' ')
  })

  async function login(credentials) {
    const response = await authApi.login(credentials)
    token.value = response.data.data.token
    localStorage.setItem('auth_token', token.value)
    await fetchUser()
  }
  
  async function fetchUser() {
    const response = await authApi.me()
    user.value = response.data
  }

  function logout() {
    user.value = null
    token.value = null
    localStorage.removeItem('auth_token')
  }

  return { user, token, isAuthenticated, fullName, login, fetchUser, logout }
})
```

**Impacto:** `AppSidebar.vue` pasa de 90 líneas a ~10 (lee `authStore.fullName`). El guard del router usa `authStore.isAuthenticated` en vez de `localStorage`.

### 4.3 Composables (`composables/`)

#### `composables/useApiList.js` — El más impactante

```js
import { ref, watch } from 'vue'

export function useApiList(apiFn, options = {}) {
  const items = ref([])
  const isLoading = ref(false)
  const searchQuery = ref('')
  const filterValues = ref({})
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: options.perPage || 15,
    total: 0
  })

  async function fetch(page = pagination.value.currentPage) {
    isLoading.value = true
    try {
      const params = {
        page,
        per_page: pagination.value.perPage,
        search: searchQuery.value || undefined,
        ...filterValues.value
      }
      const response = await apiFn(params)
      const data = response.data
      
      // Soportar respuestas paginadas y arrays directos
      if (data.data && data.current_page) {
        items.value = data.data
        pagination.value = {
          currentPage: data.current_page,
          lastPage: data.last_page,
          perPage: data.per_page,
          total: data.total
        }
      } else {
        items.value = Array.isArray(data) ? data : data.data || []
      }
    } catch (error) {
      console.error('Error fetching:', error)
      throw error
    } finally {
      isLoading.value = false
    }
  }

  function search(query) {
    searchQuery.value = query
    fetch(1)
  }

  function filter(key, value) {
    if (value) filterValues.value[key] = value
    else delete filterValues.value[key]
    fetch(1)
  }

  function changePage(page) { fetch(page) }

  return {
    items, isLoading, searchQuery, filterValues, pagination,
    fetch, search, filter, changePage
  }
}
```

**Impacto en una página (antes vs después):**

```vue
<!-- ❌ ANTES: CategoriesPage.vue — 70 líneas de lógica -->
<script setup>
const categories = ref([])
const isLoading = ref(true)
const searchQuery = ref('')

async function fetchCategories() {
  isLoading.value = true
  try {
    const token = localStorage.getItem('auth_token')
    const response = await axios.get('/api/admin/categories', {
      headers: { Authorization: `Bearer ${token}` },
      params: { search: searchQuery.value }
    })
    categories.value = response.data
  } catch (error) { ... }
  finally { isLoading.value = false }
}
function handleSearch(value) { searchQuery.value = value; fetchCategories() }
// ...más handlers...
onMounted(() => fetchCategories())
</script>

<!-- ✅ DESPUÉS: CategoriesPage.vue — 5 líneas de lógica -->
<script setup>
import { categoriesApi } from '@/api/categories.api'
import { useApiList } from '@/composables/useApiList'

const { items: categories, isLoading, searchQuery, search, fetch } = useApiList(categoriesApi.list)
fetch()
</script>
```

#### `composables/useFormatters.js`

```js
export function useFormatters() {
  function formatCurrency(value, currency = 'MXN') {
    const num = typeof value === 'string' ? parseFloat(value) : value
    if (isNaN(num)) return '$0.00'
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency }).format(num)
  }

  function formatDate(value, format = 'short') {
    if (!value) return '-'
    return new Date(value).toLocaleDateString('es-MX', {
      year: 'numeric',
      month: format === 'long' ? 'long' : 'short',
      day: 'numeric'
    })
  }

  return { formatCurrency, formatDate }
}
```

### 4.4 Componente Unificado de Ajustes

El componente más impactante: unifica 3 archivos idénticos.

#### `components/inventory/StockAdjustmentForm.vue`

Recibe un **modo** por prop y solo cambia:
- El título y descripción
- Los motivos disponibles
- El `mode` del payload (`increment`, `decrement`, `absolute`)
- El label del campo de cantidad

```vue
<script setup>
const props = defineProps({
  mode: {
    type: String,        // 'entry' | 'exit' | 'adjustment'
    required: true
  }
})

const config = computed(() => ({
  entry: {
    title: 'Nueva Entrada',
    description: 'Registra el ingreso de mercancía al almacén.',
    quantityLabel: 'Cant.',
    apiMode: 'increment',
    type: 'increase',
    reasons: [
      { value: 'found', label: 'Hallazgo' },
      { value: 'recount', label: 'Recuento' },
      { value: 'other', label: 'Otro' },
    ]
  },
  exit: {
    title: 'Nueva Salida',
    description: 'Registra la baja de mercancía del almacén.',
    quantityLabel: 'A retirar',
    apiMode: 'decrement',
    type: 'decrease',
    reasons: [
      { value: 'damaged', label: 'Dañado' },
      { value: 'lost', label: 'Extravío / Robo' },
      { value: 'expired', label: 'Caducado' },
      { value: 'other', label: 'Otro' },
    ]
  },
  adjustment: {
    title: 'Nuevo Ajuste',
    description: 'Reemplaza el valor de stock directamente.',
    quantityLabel: 'Nueva Cant.',
    apiMode: 'absolute',
    type: 'adjustment',
    reasons: [
      { value: 'recount', label: 'Recuento' },
      { value: 'correction', label: 'Corrección' },
      { value: 'other', label: 'Otro' },
    ]
  }
})[props.mode])

// ... toda la lógica compartida una sola vez
</script>
```

**Impacto:** ~720 líneas (3 archivos) → ~250 líneas (1 archivo). Eliminación de ~470 líneas duplicadas.

### 4.5 Componentes de Aplicación Reutilizables

#### `components/app/PageHeader.vue`

```vue
<template>
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-4">
      <Button v-if="showBack" variant="ghost" size="icon" @click="$router.back()">
        <ArrowLeft class="h-4 w-4" />
      </Button>
      <div>
        <h1 class="text-3xl font-bold tracking-tight">{{ title }}</h1>
        <p v-if="description" class="text-muted-foreground">{{ description }}</p>
      </div>
    </div>
    <slot name="actions" />
  </div>
</template>
```

#### `components/app/ConfirmDialog.vue`

Reemplaza los `confirm()` nativos del navegador y los diálogos de borrado inline:

```vue
<template>
  <Dialog v-model:open="model">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ title }}</DialogTitle>
        <DialogDescription>
          <slot>{{ description }}</slot>
        </DialogDescription>
      </DialogHeader>
      <DialogFooter>
        <Button variant="outline" @click="model = false" :disabled="loading">Cancelar</Button>
        <Button :variant="variant" @click="$emit('confirm')" :disabled="loading">
          {{ loading ? loadingText : confirmText }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
```

---

## 5. Plan de Implementación (Fases)

### Fase 1 — Infraestructura Base (no rompe nada existente)
1. Instalar Pinia
2. Crear `api/client.js` con interceptores
3. Crear `stores/auth.store.js`
4. Crear `composables/useFormatters.js`
5. Crear `types/models.ts` con todas las interfaces

### Fase 2 — Migrar Capa API
6. Crear módulos API (`products.api.js`, `categories.api.js`, etc.)
7. Crear `composables/useApiList.js`
8. Crear `composables/useConfirmDelete.js`

### Fase 3 — Migrar Páginas (una por una)
9. Migrar `CategoriesPage.vue` (la más simple, prueba del patrón)
10. Migrar `WarehousesPage.vue`
11. Migrar `ProductListPage.vue`
12. Migrar `StockPage.vue`
13. Migrar `MovementsPage.vue`
14. Migrar `DashboardPage.vue`

### Fase 4 — Unificar Formularios de Ajuste
15. Crear `StockAdjustmentForm.vue` unificado
16. Crear `AdjustmentFormPage.vue` que usa el form por modo
17. Actualizar rutas
18. Eliminar `EntryForm.vue`, `ExitForm.vue`, `AdjustmentForm.vue`

### Fase 5 — Mejoras Transversales
19. Crear `PageHeader.vue` y `ConfirmDialog.vue`
20. Migrar auth pages a español consistente
21. Breadcrumbs dinámicos en `PanelLayout.vue`
22. Modularizar rutas (`router/`)
23. Migrar `AppSidebar.vue` a usar `authStore`
24. Migrar auth pages a usar `authStore`

---

## 6. Impacto Esperado

| Métrica | Antes | Después | Reducción |
|---------|-------|---------|-----------|
| Repeticiones de `localStorage.getItem` | ~70 | 0 | -100% |
| Líneas en formularios de ajuste | ~720 | ~250 | -65% |
| Archivos de ajuste | 3 | 1 | -67% |
| Definiciones duplicadas de interfaces | ~15 | 1 | -93% |
| Definiciones de `formatCurrency` | 3 | 1 | -67% |
| Patrón fetch+loading+error | 6 copias | 1 composable | -83% |
| Archivos a tocar por cambio de auth | 15+ | 1 | -93% |

### Principio de Diseño

> **Las páginas solo orquestan.** No hacen llamadas HTTP, no definen interfaces, no formatean datos. Usan `composables` para la lógica, `api/` para los datos, y `components/` para la presentación.
