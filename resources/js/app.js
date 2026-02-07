import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    { path: "/", redirect: "/home" },
    {
        path: "/",
        redirect: "/panel/dashboard"
    },
    {
        path: "/home",
        redirect: "/panel/dashboard"
    },
    {
        path: "/sign-in",
        component: () => import('./pages/auth/SignInPage.vue'),
        meta: { guest: true }
    },
    {
        path: "/sign-up",
        component: () => import('./pages/auth/SignUpPage.vue'),
        meta: { guest: true }
    },
    {
        path: "/panel",
        redirect: "/panel/dashboard",
        component: () => import('./pages/panel/PanelRoot.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: "dashboard",
                component: () => import('./pages/panel/DashboardPage.vue')
            },
            {
                path: "inventory/categories",
                component: () => import('./pages/panel/inventory/CategoriesPage.vue')
            },
            {
                path: "inventory/products",
                component: () => import('./pages/panel/inventory/products/ProductListPage.vue')
            },
            {
                path: "inventory/products/create",
                component: () => import('./pages/panel/inventory/products/CreateProductPage.vue')
            },
            {
                path: "inventory/products/:id/edit",
                component: () => import('./pages/panel/inventory/products/EditProductPage.vue')
            },
            {
                path: "inventory/stock",
                component: () => import('./pages/panel/inventory/StockPage.vue')
            },
            {
                path: "inventory/movements",
                component: () => import('./pages/panel/inventory/MovementsPage.vue')
            },
            {
                path: "inventory/adjustments",
                component: () => import('./pages/panel/inventory/AdjustmentsPage.vue')
            },
            {
                path: "inventory/warehouses",
                component: () => import('./pages/panel/inventory/WarehousesPage.vue')
            }
        ]
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('auth_token');

    if (to.meta.requiresAuth && !token) {
        next('/sign-in');
    } else if (to.meta.guest && token) {
        next('/home');
    } else {
        next();
    }
});

const app = createApp(App);
app.use(router);
app.mount('#app');

