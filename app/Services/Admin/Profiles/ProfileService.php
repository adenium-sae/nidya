<?php

namespace App\Services\Admin\Profiles;

use Illuminate\Support\Facades\Auth;

class ProfileService {
    
    public function createProfile(array $data) {
        $user = Auth::user();
        // TODO: Implement profile creation
        return [];
    }
}