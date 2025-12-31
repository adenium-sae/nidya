<?php

namespace App\Services\Admin\Auth;

use App\Exceptions\Users\UserNotFoundException;
use App\Models\User;

class SignInService {
    
    public function signIn(array $data) {
        $user = User::where("email", $data["email"])->first();
        if (!$user) {
            throw new UserNotFoundException();
        }
    }
}
