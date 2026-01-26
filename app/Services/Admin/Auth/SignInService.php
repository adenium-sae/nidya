<?php

namespace App\Services\Admin\Auth;

use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\OtpExpiredException;
use App\Exceptions\Auth\OtpInvalidException;
use App\Exceptions\Auth\UserInactiveException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SignInService
{
    public function signInWithEmailAndPassword(array $data): array
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

    public function signInWithOtp(array $data): array
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

    public function generateOtp(string $email): string
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

    public function signOut(int $userId): void
    {
        /** @var User|null $user */
        $user = User::find($userId);
        
        if ($user) {
            // Revoke all tokens
            $user->tokens()->delete();
        }

        // Clear session
        session()->forget('tenant_id');
    }

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
