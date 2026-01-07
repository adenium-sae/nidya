<?php

namespace App\Services\Admin\Auth;

use App\Exceptions\Users\InvalidCredentialsException;
use App\Exceptions\Users\InvalidOtpCodeException;
use App\Exceptions\Users\UserNotFoundException;
use App\Models\User;

class SignInService {
    
    public function signInWithEmailAndPassword(array $data): array {
        $user = User::where("email", $data["email"])->first();
        if (!$user) {
            throw new UserNotFoundException();
        }
        if (!$user->checkPassword($data["password"])) {
            throw new InvalidCredentialsException();
        }
        $token = $user->createToken("API Token")->plainTextToken;
        return ["user" => $user, "token" => $token];
    }

    public function signInWithOtp(array $data): array {
        $user = User::where("email", $data["email"])->first();
        if (!$user) {
            throw new UserNotFoundException();
        }
        $otp = $data["otp"];
        if (!$user->validateOtp($otp)) {
            throw new InvalidOtpCodeException();
        }
        $token = $user->createToken("API Token")->plainTextToken;
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();
        return ["user" => $user, "token" => $token];
    }

    public function generateOtp(string $email): string {
        $user = User::where("email", $email)->first();
        if (!$user) {
            throw new UserNotFoundException();
        }
        $otp = $user->generateOtp();
        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(1);
        $user->save();
        return $otp;
    }

    public function signOut(User $user): void {
        $user->tokens()->delete();
    }
}
