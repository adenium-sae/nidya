<script setup>
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
import { useRouter } from 'vue-router';
import AuthLayout from '@/layouts/AuthLayout.vue';

const handleNext = () => {
    if (currentStep.value === 1) handleSignUp();
    else if (currentStep.value === 2) updateStore();
    else if (currentStep.value === 3) updateBranch();
    else if (currentStep.value === 4) updateWarehouse();
};

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
                            {{ item.title }}
                        </StepperTitle>
                        <StepperDescription>
                            {{ item.description }}
                        </StepperDescription>
                    </div>
                </StepperItem>
            </Stepper>
        </div>

        <div class="w-full max-w-md space-y-8">
            <div class="space-y-2 text-center sm:text-left">
                <h1 class="text-3xl font-bold tracking-tight">
                    {{ steps[currentStep - 1].title }}
                </h1>
                <p class="text-muted-foreground">
                     {{ steps[currentStep - 1].description }}
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
                </div>

                <!-- Step 2: Store -->
                <div v-if="currentStep === 2" class="space-y-4">
                     <div class="space-y-2">
                        <Label>Store Name</Label>
                        <Input v-model="form.store.name" placeholder="My Awesome Store" />
                        <p class="text-xs text-muted-foreground">This is the public name of your business.</p>
                    </div>
                </div>

                <!-- Step 3: Branch -->
                 <div v-if="currentStep === 3" class="space-y-4">
                    <div class="space-y-2">
                        <Label>Branch Name</Label>
                        <Input v-model="form.branch.name" placeholder="Main Branch" />
                    </div>
                     <div class="space-y-2">
                        <Label>Code</Label>
                        <Input v-model="form.branch.code" placeholder="BR-001" />
                    </div>
                </div>

                 <!-- Step 4: Warehouse -->
                 <div v-if="currentStep === 4" class="space-y-4">
                    <div class="space-y-2">
                        <Label>Warehouse Name</Label>
                        <Input v-model="form.warehouse.name" placeholder="Main Warehouse" />
                    </div>
                     <div class="space-y-2">
                        <Label>Code</Label>
                        <Input v-model="form.warehouse.code" placeholder="WH-001" />
                    </div>
                </div>

            </div>
            
            <div class="flex flex-col gap-4">
                <Button class="w-full" :disabled="loading" @click="handleNext">
                    <span v-if="loading">Processing...</span>
                    <span v-else>{{ currentStep === 4 ? 'Finish Setup' : 'Next' }}</span>
                </Button>
                
                <div v-if="currentStep === 1" class="text-center text-sm text-muted-foreground">
                    Already have an account? 
                    <router-link to="/sign-in" class="underline text-primary">Sign in</router-link>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
