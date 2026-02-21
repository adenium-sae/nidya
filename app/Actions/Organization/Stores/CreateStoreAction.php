<?php

namespace App\Actions\Organization\Stores;

use App\Models\Store;

class CreateStoreAction
{
    public function __invoke(array $data): Store
    {
        return Store::create($data);
    }
}
