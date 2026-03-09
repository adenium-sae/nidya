<?php

namespace App\Actions\Auth;

use App\Models\Branch;
use App\Models\Profile;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreUserRole;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterUserAction
{
    public function __invoke(array $data): array
    {
        return $this->register($data);
    }

    private function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = $this->createUser($data);
            return [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'full_name' => $user->fullName(),
                    'profile' => $user->profile
                ]
            ];
        });
    }

    private function createUser(array $data): User
    {
        $user = User::create([
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => true
        ]);
        Profile::create([
            'user_id' => $user->id,
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'second_last_name' => $data['second_last_name'] ?? null,
            'birth_date' => $data['birth_date'] ?? null
        ]);
        return $user->load('profile');
    }
}
