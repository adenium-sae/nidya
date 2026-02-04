<script setup>
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const form = ref({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: ''
});
const loading = ref(false);
const error = ref('');

const handleSignUp = async () => {
    loading.value = true;
    error.value = '';
    try {
        await axios.get('/sanctum/csrf-cookie');
        const response = await axios.post('/api/auth/signup', form.value);
        if (response.data.data.token) {
            localStorage.setItem('auth_token', response.data.data.token);
             axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.data.token}`;
        }
        const { store, branch, warehouse } = response.data.data;
        localStorage.setItem('onboarding_data', JSON.stringify({ store, branch, warehouse }));
        router.push('/onboarding');
    } catch (err) {
        console.error('Signup failed', err);
        error.value = err.response?.data?.message || 'Registration failed. Please try again.';
        if (err.response?.data?.errors) {
             error.value = Object.values(err.response.data.errors).flat().join(' ');
        }
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div class="flex items-center justify-center min-h-screen bg-gray-100 dark:bg-zinc-950 p-4">
        <Card class="w-full max-w-md">
            <CardHeader class="space-y-1">
                <CardTitle class="text-2xl font-bold">Create an account</CardTitle>
                <CardDescription>Enter your information to create your account</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div v-if="error" class="p-3 text-sm text-red-500 bg-red-50 rounded-md">
                    {{ error }}
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="first-name">First name</Label>
                        <Input id="first-name" placeholder="John" v-model="form.first_name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="last-name">Last name</Label>
                        <Input id="last-name" placeholder="Doe" v-model="form.last_name" />
                    </div>
                </div>
                <div class="space-y-2">
                    <Label for="email">Email</Label>
                    <Input id="email" type="email" placeholder="m@example.com" v-model="form.email" />
                </div>
                <div class="space-y-2">
                    <Label for="password">Password</Label>
                    <Input id="password" type="password" v-model="form.password" />
                </div>
                <div class="space-y-2">
                    <Label for="password_confirmation">Confirm Password</Label>
                    <Input id="password_confirmation" type="password" v-model="form.password_confirmation" />
                </div>
            </CardContent>
            <CardFooter class="flex flex-col gap-4">
                <Button class="w-full" :disabled="loading" @click="handleSignUp">
                    <span v-if="loading">Creating account...</span>
                    <span v-else>Sign Up</span>
                </Button>
                <div class="text-center text-sm">
                    Already have an account? 
                    <router-link to="/login" class="underline text-blue-600">Sign in</router-link>
                </div>
            </CardFooter>
        </Card>
    </div>
</template>
