import type { RouteRecordRaw } from 'vue-router'

export const panelRoutes: RouteRecordRaw[] = [
  {
    path: '/panel',
    redirect: '/panel/dashboard',
    component: () => import('@/pages/panel/PanelRoot.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('@/pages/panel/DashboardPage.vue'),
      },
      // Inventory
      {
        path: 'inventory/products',
        name: 'products',
        component: () => import('@/pages/panel/inventory/products/ProductListPage.vue'),
      },
      {
        path: 'inventory/products/create',
        name: 'products-create',
        component: () => import('@/pages/panel/inventory/products/CreateProductPage.vue'),
      },
      {
        path: 'inventory/products/:id/edit',
        name: 'products-edit',
        component: () => import('@/pages/panel/inventory/products/EditProductPage.vue'),
      },
      {
        path: 'inventory/categories',
        name: 'categories',
        component: () => import('@/pages/panel/inventory/CategoriesPage.vue'),
      },
      {
        path: 'inventory/stock',
        name: 'stock',
        component: () => import('@/pages/panel/inventory/StockPage.vue'),
      },
      {
        path: 'inventory/movements',
        name: 'movements',
        component: () => import('@/pages/panel/inventory/MovementsPage.vue'),
      },
      {
        path: 'inventory/adjustments',
        name: 'adjustments',
        component: () => import('@/pages/panel/inventory/adjustments/AdjustmentsPage.vue'),
      },
      {
        path: 'inventory/transfers',
        name: 'transfers',
        component: () => import('@/pages/panel/inventory/TransfersPage.vue'),
      },
      {
        path: 'inventory/adjustments/entry',
        name: 'adjustment-entry',
        component: () => import('@/pages/panel/inventory/adjustments/AdjustmentFormPage.vue'),
        props: { mode: 'entry' },
      },
      {
        path: 'inventory/adjustments/exit',
        name: 'adjustment-exit',
        component: () => import('@/pages/panel/inventory/adjustments/AdjustmentFormPage.vue'),
        props: { mode: 'exit' },
      },
      {
        path: 'inventory/adjustments/new-adjustment',
        name: 'adjustment-direct',
        component: () => import('@/pages/panel/inventory/adjustments/AdjustmentFormPage.vue'),
        props: { mode: 'adjustment' },
      },
      {
        path: 'inventory/adjustments/transfer',
        name: 'adjustment-transfer',
        component: () => import('@/pages/panel/inventory/adjustments/TransferFormPage.vue'),
      },
      {
        path: 'inventory/warehouses',
        name: 'warehouses',
        component: () => import('@/pages/panel/inventory/WarehousesPage.vue'),
      },
      // Sales
      {
        path: 'sales/pos',
        name: 'pos',
        component: () => import('@/pages/panel/sales/PosPage.vue'),
      },
      {
        path: 'sales/history',
        name: 'sales-history',
        component: () => import('@/pages/panel/sales/SalesHistoryPage.vue'),
      },
      {
        path: 'sales/customers',
        name: 'customers',
        component: () => import('@/pages/panel/sales/CustomersPage.vue'),
      },
      {
        path: 'sales/cash-register',
        name: 'cash-register',
        component: () => import('@/pages/panel/sales/CashRegisterPage.vue'),
      },
      {
        path: 'sales/cash-movements',
        name: 'cash-movements',
        component: () => import('@/pages/panel/sales/CashMovementsPage.vue'),
      },
      // Purchases
      {
        path: 'purchases/new',
        name: 'purchases-new',
        component: () => import('@/pages/panel/purchases/NewPurchasePage.vue'),
      },
      {
        path: 'purchases/history',
        name: 'purchases-history',
        component: () => import('@/pages/panel/purchases/PurchasesHistoryPage.vue'),
      },
      {
        path: 'purchases/suppliers',
        name: 'suppliers',
        component: () => import('@/pages/panel/purchases/SuppliersPage.vue'),
      },
      {
        path: 'purchases/orders',
        name: 'purchase-orders',
        component: () => import('@/pages/panel/purchases/PurchaseOrdersPage.vue'),
      },
      // Users
      {
        path: 'users/list',
        name: 'users-list',
        component: () => import('@/pages/panel/users/UsersListPage.vue'),
      },
      {
        path: 'users/roles',
        name: 'users-roles',
        component: () => import('@/pages/panel/users/RolesPage.vue'),
      },
      {
        path: 'users/permissions',
        name: 'users-permissions',
        component: () => import('@/pages/panel/users/PermissionsPage.vue'),
      },
      // Activity Log
      {
        path: 'activity-logs',
        name: 'activity-logs',
        component: () => import('@/pages/panel/ActivityLogPage.vue'),
      },
      // Organization
      {
        path: 'organization/stores',
        name: 'stores',
        component: () => import('@/pages/panel/organization/StoresPage.vue'),
      },
      {
        path: 'organization/branches',
        name: 'branches',
        component: () => import('@/pages/panel/organization/BranchesPage.vue'),
      },
    ],
  },
]
