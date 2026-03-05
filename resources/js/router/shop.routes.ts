import type { RouteRecordRaw } from 'vue-router'

export const shopRoutes: RouteRecordRaw[] = [
  {
    path: '/shop',
    redirect: '/shop/home',
    component: () => import('@/pages/shop/ShopRoot.vue'),
    children: [
      {
        path: 'home',
        name: 'shop-home',
        component: () => import('@/pages/shop/HomePage.vue'),
      },
      {
        path: 'catalog',
        name: 'shop-catalog',
        component: () => import('@/pages/shop/CatalogPage.vue'),
      },
      {
        path: 'catalog/:id',
        name: 'shop-product-detail',
        component: () => import('@/pages/shop/ProductDetailPage.vue'),
      },
    ],
  },
]
