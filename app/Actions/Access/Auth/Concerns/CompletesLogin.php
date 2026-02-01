<?php

namespace App\Actions\Access\Auth\Concerns;

use App\Models\User;

trait CompletesLogin
{
    protected function completeLogin(User $user): array
    {
        $user->load('profile');
        $tenant = $user->tenants()->wherePivot('is_active', true)->first();
        if ($tenant) {
            session(['tenant_id' => $tenant->id]);
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        return [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'full_name' => $user->fullName(),
                'profile' => $user->profile
            ],
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'subscription_status' => $tenant->subscription_status,
                'role' => $tenant->pivot->role
            ] : null,
            'token' => $token
        ];
    }
}
