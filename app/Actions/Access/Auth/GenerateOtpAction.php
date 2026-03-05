<?php

namespace App\Actions\Access\Auth;

use App\Exceptions\Access\Auth\InvalidCredentialsException;
use App\Models\User;

class GenerateOtpAction
{
    public function __invoke(string $email): string
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            throw new InvalidCredentialsException();
        }
        $otp = $user->generateOtp();
        // TODO: Send OTP via email/SMS
        // Mail::to($user->email)->send(new OtpMail($otp));
        return $otp;
    }
}
