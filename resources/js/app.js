import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    { path: "/", redirect: "/home" },
    { path: "/home", component: () => import('./pages/home.vue') },
    { path: "/sign-in", component: () => import('./pages/auth/sign-in.vue') },
    { path: "/sign-up", component: () => import('./pages/auth/sign-up.vue') },
    { path: "/onboarding", component: () => import('./pages/onboarding/index.vue') }
];

const app = createApp(App);
app.use(createRouter({
    history: createWebHistory(),
    routes,
}));
app.mount('#app');

