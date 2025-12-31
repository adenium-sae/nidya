<?php

namespace App\Services\Admin\Auth;

use App\Exceptions\Users\InvalidCredentialsException;
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
        $token = $user->createToken("auth_token", ['admin'])->plainTextToken;
        return ["user" => $user, "token" => $token];
    }
}
