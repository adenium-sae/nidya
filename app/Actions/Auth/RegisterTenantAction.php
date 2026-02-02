<?php

namespace App\Actions\Auth;

use App\Enums\TenantRole;
use App\Models\Branch;
use App\Models\Profile;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterTenantAction
{
    public function __invoke(array $data): void
    {
        $this->register($data);
    }

    private function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $tenant = $this->createTenant($data);
            $user = $this->createUser($data);
            $tenant->users()->attach($user->id, [
                'id' => (string) Str::uuid(),
                'role' => TenantRole::OWNER->value,
                'is_active' => true
            ]);
            session(['tenant_id' => $tenant->id]);
            $store = $this->createDefaultStore($tenant, $user);
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
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'subscription_status' => $tenant->subscription_status
                ],
                'store' => [
                    'id' => $store->id,
                    'name' => $store->name,
                    'slug' => $store->slug
                ],
                'token' => $token
            ];
        });
    }

    private function createTenant(array $data): Tenant
    {
        $firstName = $data['first_name'];
        $lastName = $data['last_name'] ?? '';
        $fullName = trim("$firstName $lastName");
        return Tenant::create([
            'name' => $fullName,
            'slug' => Str::slug($fullName) . '-' . Str::random(6),
            'email' => $data['email'],
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(30)
        ]);
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

    private function createDefaultStore(Tenant $tenant, User $user): Store
    {
        $firstName = $user->profile->first_name;
        $slug = Str::slug($firstName);
        return Store::create([
            'tenant_id' => $tenant->id,
            'name' => "{$firstName}'s Store",
            'slug' => $slug . '-store',
            'is_active' => true
        ]);
    }

    private function createDefaultBranch(Store $store, User $user): Branch
    {
        $firstName = $user->profile->first_name;
        return Branch::create([
            'tenant_id' => $store->tenant_id,
            'store_id' => $store->id,
            'name' => "{$firstName}'s Branch",
            'code' => 'MAIN',
            'is_active' => true
        ]);
    }

    private function createDefaultWarehouse(Store $store, Branch $branch): Warehouse
    {
        return Warehouse::create([
            'tenant_id' => $store->tenant_id,
            'store_id' => $store->id,
            'branch_id' => $branch->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'type' => 'central',
            'is_active' => true
        ]);
    }
}