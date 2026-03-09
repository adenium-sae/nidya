<?php

namespace App\Actions\Organization\Stores;

use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreUserRole;
use Illuminate\Support\Facades\Auth;

class CreateStoreAction
{
    public function __invoke(array $data): Store
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user || !$user->is_superuser) {
            throw new AccessDeniedException();
        }

        $store = Store::create($data);

        $role = Role::where('key', 'admin')->first();
        if ($role) {
            StoreUserRole::create([
                'user_id' => $user->id,
                'store_id' => $store->id,
                'role_id' => $role->id,
            ]);
        }

        return $store;
    }
}
