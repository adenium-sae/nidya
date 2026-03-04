<?php

namespace App\Actions\Organization\Stores;

use App\Exceptions\Organization\Stores\StoreNotFoundException;
use App\Models\Store;

class DeleteStoreAction
{
    public function __invoke(string $id): void
    {
        /** @var Store|null $store */
        $store = Store::find($id);

        if (!$store) {
            throw new StoreNotFoundException();
        }

        $store->delete();
    }
}
