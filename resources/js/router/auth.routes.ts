import type { RouteRecordRaw } from 'vue-router'

export const authRoutes: RouteRecordRaw[] = [
  {
    path: '/sign-in',
    name: 'sign-in',
    component: () => import('@/pages/auth/SignInPage.vue'),
    meta: { guest: true },
  },
  {
    path: '/sign-up',
    name: 'sign-up',
    component: () => import('@/pages/auth/SignUpPage.vue'),
    meta: { guest: true },
  },
]
