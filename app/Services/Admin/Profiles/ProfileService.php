<?php

namespace App\Services\Admin\Profiles;

use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProfileService {
    
    public function createProfile(array $data): Profile {
        $user_id = Auth::user()->id;
        $profile = Profile::create([
            'user_id' => $user_id,
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'second_last_name' => $data['second_last_name'] ?? null,
            'birth_date' => $data['birth_date'] ? Carbon::createFromFormat('d-m-Y', $data['birth_date']) : null,
        ]);
        return $profile;
    }
}