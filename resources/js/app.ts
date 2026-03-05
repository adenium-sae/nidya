import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import i18n from './i18n';
import { useAuthStore } from './stores/auth.store';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(i18n);
app.use(router);

// Pre-fetch user data if token exists
const authStore = useAuthStore();
if (authStore.isAuthenticated) {
  authStore.fetchUser();
}

app.mount('#app');
