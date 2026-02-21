<?php

namespace App\Actions\Auth;

use App\Models\Branch;
use App\Models\Profile;
use App\Models\Store;
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
            $store = $this->createDefaultStore($user);
            $branch = $this->createDefaultBranch($store, $user);
            $warehouse = $this->createDefaultWarehouse($store, $branch);
            $token = $user->createToken('auth_token')->plainTextToken;
            return [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'full_name' => $user->fullName(),
                    'profile' => $user->profile
                ],
                'store' => [
                    'id' => $store->id,
                    'name' => $store->name,
                    'slug' => $store->slug
                ],
                'branch' => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'code' => $branch->code
                ],
                'warehouse' => [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'code' => $warehouse->code
                ],
                'token' => $token
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

    private function createDefaultStore(User $user): Store
    {
        $firstName = $user->profile->first_name;
        $slug = Str::slug($firstName);
        return Store::create([
            'name' => "{$firstName}'s Store",
            'slug' => $slug . '-store',
            'is_active' => true
        ]);
    }

    private function createDefaultBranch(Store $store, User $user): Branch
    {
        $firstName = $user->profile->first_name;
        return Branch::create([
            'store_id' => $store->id,
            'name' => "{$firstName}'s Branch",
            'code' => 'MAIN',
            'is_active' => true
        ]);
    }

    private function createDefaultWarehouse(Store $store, Branch $branch): Warehouse
    {
        return Warehouse::create([
            'store_id' => $store->id,
            'branch_id' => $branch->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'type' => 'central',
            'is_active' => true
        ]);
    }
}
