<?php

namespace App\Actions\Organization\Stores;

use App\Exceptions\Organization\Stores\StoreNotFoundException;
use App\Models\Store;

class UpdateStoreAction
{
    public function __invoke(string $id, array $data): Store
    {
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
