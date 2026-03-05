<?php

namespace App\Actions\Access\Auth;

use App\Actions\Access\Auth\Concerns\CompletesLogin;
use App\Exceptions\Access\Auth\InvalidCredentialsException;
use App\Exceptions\Access\Auth\OtpExpiredException;
use App\Exceptions\Access\Auth\OtpInvalidException;
use App\Exceptions\Access\Auth\UserInactiveException;
use App\Models\User;

class LoginWithOtpAction
{
    use CompletesLogin;

    public function __invoke(array $data): array
    {
        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            throw new InvalidCredentialsException();
        }
        if (!$user->verifyOtp($data['otp'])) {
            if ($user->otp_expires_at && $user->otp_expires_at < now()) {
                throw new OtpExpiredException();
            }
            throw new OtpInvalidException();
        }
        if (!$user->is_active) {
            throw new UserInactiveException();
        }
        return $this->completeLogin($user);
    }
}
