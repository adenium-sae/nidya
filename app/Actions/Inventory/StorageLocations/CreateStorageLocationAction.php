<?php

namespace App\Actions\Inventory\StorageLocations;

use App\Models\StorageLocation;

class CreateStorageLocationAction
{
    public function __invoke(array $data): StorageLocation
    {
        return StorageLocation::create($data);
    }
}
