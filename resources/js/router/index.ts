import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { authRoutes } from './auth.routes'
import { panelRoutes } from './panel.routes'
import { useAuthStore } from '@/stores/auth.store'
import { shopRoutes } from './shop.routes'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    redirect: '/shop/home',
  },
  {
    path: '/home',
    redirect: '/shop/home',
  },
  ...authRoutes,
  ...panelRoutes,
  ...shopRoutes
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, _from, next) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    next('/sign-in')
  } else if (to.meta.guest && auth.isAuthenticated) {
    next('/shop/home')
  } else {
    next()
  }
})

export default router
