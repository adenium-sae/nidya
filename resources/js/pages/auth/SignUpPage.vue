<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Stepper,
  StepperItem,
  StepperTrigger,
  StepperSeparator,
  StepperTitle,
  StepperDescription,
  StepperIndicator,
} from '@/components/ui/stepper'
import { User, Store, MapPin, Package } from 'lucide-vue-next';
import AuthLayout from '@/layouts/AuthLayout.vue';
import axios from 'axios';

const router = useRouter();
const { t } = useI18n();
const currentStep = ref(1);
const loading = ref(false);
const error = ref('');

const form = reactive({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
    store: {
        name: '',
    },
    branch: {
        name: '',
        code: '',
    },
    warehouse: {
        name: '',
        code: '',
    }
});

const steps = [
    {
        step: 1,
        title: () => t('auth.step_account'),
        description: () => t('auth.step_account_desc'),
        icon: User
    },
    {
        step: 2,
        title: () => t('auth.step_store'),
        description: () => t('auth.step_store_desc'),
        icon: Store
    },
    {
        step: 3,
        title: () => t('auth.step_branch'),
        description: () => t('auth.step_branch_desc'),
        icon: MapPin
    },
    {
        step: 4,
        title: () => t('auth.step_warehouse'),
        description: () => t('auth.step_warehouse_desc'),
        icon: Package
    }
];

async function handleNext() {
    error.value = '';
    if (currentStep.value === 1) updateStore();
    else if (currentStep.value === 2) updateBranch();
    else if (currentStep.value === 3) updateWarehouse();
    if (currentStep.value < 4) {
        currentStep.value++;
        return;
    }
    await handleSignUp();
}

async function handleSignUp() {
    loading.value = true;
    try {
        await axios.post('/api/auth/signup', {
            first_name: form.first_name,
            last_name: form.last_name,
            email: form.email,
            password: form.password,
            password_confirmation: form.password_confirmation,
            store_name: form.store.name,
            branch_name: form.branch.name,
            branch_code: form.branch.code,
            warehouse_name: form.warehouse.name,
            warehouse_code: form.warehouse.code
        });
        router.push('/panel');
    } catch (e: any) {
        console.error(e);
        error.value = e.response?.data?.message || t('auth.register_error');
    } finally {
        loading.value = false;
    }
}

function updateStore() {
    if (!form.store.name && form.first_name) {
        form.store.name = `${t('auth.step_store')} de ${form.first_name}`;
    }
}

function updateBranch() {
    if (!form.branch.name) {
        form.branch.name = 'Matriz';
    }
    if (!form.branch.code) {
        form.branch.code = 'SUC-001';
    }
}

function updateWarehouse() {
    if (!form.warehouse.name) {
        form.warehouse.name = 'Almacén General';
    }
    if (!form.warehouse.code) {
        form.warehouse.code = 'ALM-001';
    }
}
</script>

<template>
    <AuthLayout>
        
        <!-- Stepper Header -->
        <div class="w-full max-w-3xl mb-12 mt-10">
             <Stepper v-model="currentStep" class="flex w-full items-start gap-2">
                <StepperItem
                    v-for="item in steps"
                    :key="item.step"
                    class="relative flex w-full flex-col items-center justify-center"
                    :step="item.step"
                >
                    <StepperTrigger>
                        <StepperIndicator v-slot="{ step }" class="bg-muted">
                            <component :is="item.icon" v-if="item.icon" class="size-4" />
                            <span v-else>{{ step }}</span>
                        </StepperIndicator>
                    </StepperTrigger>

                    <StepperSeparator
                        v-if="item.step !== steps[steps.length - 1].step"
                        class="absolute left-[calc(50%+20px)] right-[calc(-50%+10px)] top-5 block h-0.5 shrink-0 rounded-full bg-muted group-data-[state=completed]:bg-primary"
                    />

                    <div class="mt-2 flex flex-col items-center text-center">
                        <StepperTitle>
                            {{ item.title() }}
                        </StepperTitle>
                        <StepperDescription>
                            {{ item.description() }}
                        </StepperDescription>
                    </div>
                </StepperItem>
            </Stepper>
        </div>

        <div class="w-full max-w-md space-y-8">
            <div class="space-y-2 text-center sm:text-left">
                <h1 class="text-3xl font-bold tracking-tight">
                    {{ steps[currentStep - 1].title() }}
                </h1>
                <p class="text-muted-foreground">
                     {{ steps[currentStep - 1].description() }}
                </p>
            </div>
            
            <div class="space-y-4">
                <div v-if="error" class="p-3 text-sm text-red-500 bg-red-50 rounded-md">
                    {{ error }}
                </div>

                <!-- Step 1: Account -->
                <div v-if="currentStep === 1" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="first-name">{{ t('auth.first_name') }}</Label>
                            <Input id="first-name" placeholder="John" v-model="form.first_name" />
                        </div>
                        <div class="space-y-2">
                            <Label for="last-name">{{ t('auth.last_name') }}</Label>
                            <Input id="last-name" placeholder="Doe" v-model="form.last_name" />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="email">{{ t('auth.email') }}</Label>
                        <Input id="email" type="email" placeholder="m@example.com" v-model="form.email" />
                    </div>
                    <div class="space-y-2">
                        <Label for="password">{{ t('auth.password') }}</Label>
                        <Input id="password" type="password" v-model="form.password" />
                    </div>
                    <div class="space-y-2">
                        <Label for="password_confirmation">{{ t('auth.confirm_password') }}</Label>
                        <Input id="password_confirmation" type="password" v-model="form.password_confirmation" />
                    </div>
                </div>

                <!-- Step 2: Store -->
                <div v-if="currentStep === 2" class="space-y-4">
                     <div class="space-y-2">
                        <Label>{{ t('auth.store_name') }}</Label>
                        <Input v-model="form.store.name" placeholder="My Awesome Store" />
                        <p class="text-xs text-muted-foreground">{{ t('auth.store_name_hint') }}</p>
                    </div>
                </div>

                <!-- Step 3: Branch -->
                 <div v-if="currentStep === 3" class="space-y-4">
                    <div class="space-y-2">
                        <Label>{{ t('auth.branch_name') }}</Label>
                        <Input v-model="form.branch.name" placeholder="Main Branch" />
                    </div>
                     <div class="space-y-2">
                        <Label>{{ t('common.code') }}</Label>
                        <Input v-model="form.branch.code" placeholder="BR-001" />
                    </div>
                </div>

                 <!-- Step 4: Warehouse -->
                 <div v-if="currentStep === 4" class="space-y-4">
                    <div class="space-y-2">
                        <Label>{{ t('auth.warehouse_name') }}</Label>
                        <Input v-model="form.warehouse.name" placeholder="Main Warehouse" />
                    </div>
                     <div class="space-y-2">
                        <Label>{{ t('common.code') }}</Label>
                        <Input v-model="form.warehouse.code" placeholder="WH-001" />
                    </div>
                </div>

            </div>
            
            <div class="flex flex-col gap-4">
                <Button class="w-full" :disabled="loading" @click="handleNext">
                    <span v-if="loading">{{ t('common.processing') }}</span>
                    <span v-else>{{ currentStep === 4 ? t('auth.finish_setup') : t('common.next') }}</span>
                </Button>
                
                <div v-if="currentStep === 1" class="text-center text-sm text-muted-foreground">
                    {{ t('auth.already_have_account') }} 
                    <router-link to="/sign-in" class="underline text-primary">{{ t('auth.sign_in') }}</router-link>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
