<?php

namespace App\Actions\Access\Auth\Concerns;

use App\Models\User;

trait CompletesLogin
{
    protected function completeLogin(User $user): array
    {
        $user->load('profile');
        $abilities = $user->getAllPermissionsAbilities();
        $token = $user->createToken('auth_token', $abilities)->plainTextToken;
        return [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'full_name' => $user->fullName(),
                'profile' => $user->profile,
                'permissions' => $abilities
            ],
            'token' => $token,
            'permissions' => $abilities
        ];
    }
}
