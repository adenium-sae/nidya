<?php

namespace App\Actions\Access\Auth;

use App\Models\User;

class LogoutAction
{
    public function __invoke(int $userId): void
    {
        /** @var User|null $user */
        $user = User::find($userId);
        if ($user) {
            $user->tokens()->delete();
        }
        session()->forget('tenant_id');
    }
}
