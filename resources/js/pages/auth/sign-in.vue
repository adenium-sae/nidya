<script setup>
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref('');

const handleLogin = async () => {
    loading.value = true;
    error.value = '';

    try {
        // CSRF Protection (Sanctum)
        await axios.get('/sanctum/csrf-cookie');

        // Login Request
        const response = await axios.post('/api/auth/signin', {
            email: email.value,
            password: password.value
        });

        // On success, redirect to home/dashboard
        // Assuming backend sets HttpOnly cookie or returns token. 
        // If token returned: localStorage.setItem('token', response.data.token)
        console.log('Login successful', response.data);
        router.push('/');

    } catch (err) {
        console.error('Login failed', err);
        error.value = err.response?.data?.message || 'Invalid credentials or server error.';
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div class="flex items-center justify-center min-h-screen bg-gray-100 dark:bg-zinc-950 p-4">
        <Card class="w-full max-w-md">
            <CardHeader class="space-y-1">
                <CardTitle class="text-2xl font-bold">Sign In</CardTitle>
                <CardDescription>Enter your email and password to access your account</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
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
            </CardContent>
            <CardFooter>
                <Button class="w-full" :disabled="loading" @click="handleLogin">
                    <span v-if="loading">Signing in...</span>
                    <span v-else>Sign In</span>
                </Button>
            </CardFooter>
        </Card>
    </div>
</template>
