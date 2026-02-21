import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { authRoutes } from './auth.routes'
import { panelRoutes } from './panel.routes'
import { useAuthStore } from '@/stores/auth.store'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    redirect: '/panel/dashboard',
  },
  {
    path: '/home',
    redirect: '/panel/dashboard',
  },
  ...authRoutes,
  ...panelRoutes,
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
    next('/panel/dashboard')
  } else {
    next()
  }
})

export default router
