<script setup>
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import axios from 'axios';
import { useRouter } from 'vue-router';
import AuthLayout from '@/layouts/AuthLayout.vue';

const router = useRouter();
const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref('');

const handleLogin = async () => {
    loading.value = true;
    error.value = '';

    try {
        await axios.get('/sanctum/csrf-cookie');

        const response = await axios.post('/api/auth/signin', {
            email: email.value,
            password: password.value
        });
        
        if (response.data.data.token) {
            localStorage.setItem('auth_token', response.data.data.token);
             axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.data.token}`;
        }
        router.push('/home');
    } catch (err) {
        console.error('Login failed', err);
        error.value = err.response?.data?.message || 'Invalid credentials.';
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <AuthLayout>
        <div class="w-full max-w-md space-y-8">
            <div class="space-y-2 text-center">
                <h1 class="text-3xl font-bold tracking-tight">
                    Sign In
                </h1>
                <p class="text-muted-foreground">
                    Enter your email and password to access your account
                </p>
            </div>

            <div class="space-y-4">
                <div v-if="error" class="p-3 text-sm text-red-500 bg-red-50 rounded-md">
                    {{ error }}
                </div>
                <div class="space-y-2">
                    <Label for="email">Email</Label>
                    <Input id="email" type="email" placeholder="m@example.com" v-model="email" />
                </div>
                <div class="space-y-2">
                    <Label for="password">Password</Label>
                    <Input id="password" type="password" v-model="password" />
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <Button class="w-full" :disabled="loading" @click="handleLogin">
                    <span v-if="loading">Signing in...</span>
                    <span v-else>Sign In</span>
                </Button>
                
                <div class="text-center text-sm text-muted-foreground">
                    Don't have an account? 
                    <router-link to="/sign-up" class="underline text-primary">Sign up</router-link>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
