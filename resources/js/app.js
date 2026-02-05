import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    { path: "/", redirect: "/home" },
    { 
        path: "/home", 
        component: () => import('./pages/home.vue'),
        meta: { requiresAuth: true }
    },
    { 
        path: "/sign-in", 
        component: () => import('./pages/auth/sign-in.vue'),
        meta: { guest: true }
    },
    { 
        path: "/sign-up", 
        component: () => import('./pages/auth/sign-up.vue'),
        meta: { guest: true }
    },
    { 
        path: "/onboarding", 
        component: () => import('./pages/onboarding/index.vue'),
        meta: { requiresAuth: true }
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

