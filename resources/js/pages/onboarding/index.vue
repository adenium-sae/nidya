<script setup>
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const step = ref(1);
const loading = ref(false);
const message = ref('');

const data = ref({
    store: { id: null, name: '' },
    branch: { id: null, name: '' },
    warehouse: { id: null, name: '' }
});

onMounted(() => {
    const stored = localStorage.getItem('onboarding_data');
    if (stored) {
        const parsed = JSON.parse(stored);
        data.value.store = { ...parsed.store };
        data.value.branch = { ...parsed.branch };
        data.value.warehouse = { ...parsed.warehouse };
    } else {
        router.push('/');
    }
    const token = localStorage.getItem('auth_token');
    if (token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    }
});

const updateStore = async () => {
    loading.value = true;
    try {
        await axios.put(`/api/admin/stores/${data.value.store.id}`, {
            name: data.value.store.name,
            is_active: true
        });
        step.value = 2;
    } catch (err) {
        console.error(err);
        message.value = 'Failed to update Store.';
    } finally {
        loading.value = false;
    }
};

const updateBranch = async () => {
    loading.value = true;
    try {
        await axios.put(`/api/admin/branches/${data.value.branch.id}`, {
            name: data.value.branch.name,
            store_id: data.value.store.id,
            code: data.value.branch.code,
            is_active: true
        });
        step.value = 3;
    } catch (err) {
        console.error(err);
        message.value = 'Failed to update Branch.';
    } finally {
        loading.value = false;
    }
};

const updateWarehouse = async () => {
    loading.value = true;
    try {
        await axios.put(`/api/admin/warehouses/${data.value.warehouse.id}`, {
            name: data.value.warehouse.name,
            branch_id: data.value.branch.id,
            store_id: data.value.store.id,
            code: data.value.warehouse.code,
            is_active: true
        });
        localStorage.removeItem('onboarding_data');
        router.push('/home');
    } catch (err) {
        console.error(err);
        message.value = 'Failed to update Warehouse.';
    } finally {
        loading.value = false;
    }
};

const skip = () => {
     localStorage.removeItem('onboarding_data');
     router.push('/home');
};
</script>

<template>
    <div class="flex items-center justify-center min-h-screen bg-gray-50 dark:bg-zinc-950 p-4">
        <Card class="w-full max-w-md">
            <CardHeader>
                <CardTitle>Welcome! Let's set up your business</CardTitle>
                <CardDescription>
                    Step {{ step }} of 3: 
                    <span v-if="step === 1">Your Store</span>
                    <span v-if="step === 2">Your Main Branch</span>
                    <span v-if="step === 3">Your Main Warehouse</span>
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div v-if="step === 1">
                    <div class="space-y-2">
                        <Label>Store Name</Label>
                        <Input v-model="data.store.name" />
                        <p class="text-xs text-gray-500">This is the public name of your business.</p>
                    </div>
                </div>

                <div v-if="step === 2">
                    <div class="space-y-2">
                        <Label>Branch Name</Label>
                        <Input v-model="data.branch.name" />
                        <p class="text-xs text-gray-500">e.g. "Main Office", "Downtown Branch"</p>
                    </div>
                     <div class="space-y-2 mt-2">
                        <Label>Code</Label>
                        <Input v-model="data.branch.code" />
                    </div>
                </div>

                <div v-if="step === 3">
                    <div class="space-y-2">
                        <Label>Warehouse Name</Label>
                        <Input v-model="data.warehouse.name" />
                    </div>
                     <div class="space-y-2 mt-2">
                        <Label>Code</Label>
                        <Input v-model="data.warehouse.code" />
                    </div>
                </div>

                <div v-if="message" class="text-red-500 text-sm mt-2">{{ message }}</div>
            </CardContent>
            <CardFooter class="flex justify-between">
                <Button variant="ghost" @click="skip">Skip setup</Button>
                
                <Button v-if="step === 1" @click="updateStore" :disabled="loading">Next</Button>
                <Button v-if="step === 2" @click="updateBranch" :disabled="loading">Next</Button>
                <Button v-if="step === 3" @click="updateWarehouse" :disabled="loading">Finish</Button>
            </CardFooter>
        </Card>
    </div>
</template>
