<?php

namespace App\Actions\Access\Auth;

use App\Actions\Access\Auth\Concerns\CompletesLogin;
use App\Exceptions\Access\Auth\InvalidCredentialsException;
use App\Exceptions\Access\Auth\UserInactiveException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginAction
{
    use CompletesLogin;

    public function __invoke(array $data): array
    {
        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw new InvalidCredentialsException();
        }
        if (!$user->is_active) {
            throw new UserInactiveException();
        }
        return $this->completeLogin($user);
    }
}
