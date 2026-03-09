<?php

namespace App\Actions\Organization\Stores;

use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Exceptions\Organization\Stores\StoreNotFoundException;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;

class UpdateStoreAction
{
    public function __invoke(string $id, array $data): Store
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user || !$user->is_superuser) {
            throw new AccessDeniedException();
        }

        /** @var Store|null $store */
        $store = Store::find($id);
        if (!$store) {
            throw new StoreNotFoundException();
        }
        $store->fill($data);
        $store->save();
        return $store->fresh();
    }
}
