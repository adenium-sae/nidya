<?php

namespace App\Actions\Access\Auth\Concerns;

use App\Models\User;

trait CompletesLogin
{
    protected function completeLogin(User $user): array
    {
        $user->load('profile');
        $token = $user->createToken('auth_token')->plainTextToken;
        return [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'full_name' => $user->fullName(),
                'profile' => $user->profile
            ],
            'token' => $token
        ];
    }
}
