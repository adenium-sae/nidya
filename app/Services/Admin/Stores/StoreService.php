<?php

namespace App\Services\Admin\Stores;

use App\Exceptions\Stores\StoreNotFoundException;
use App\Models\Store;

class StoreService
{
    public function findAllByAdmin(array $filters, string $userId)
    {
        $query = Store::with(['branches', 'warehouses']);

        if (!empty($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    public function create(array $data, string $userId): Store
    {
        return Store::create($data);
    }

    public function getById(string $id): Store
    {
        $store = Store::with(['branches', 'warehouses', 'products'])->find($id);
        if (!$store) {
            throw new StoreNotFoundException();
        }
        return $store;
    }

    public function update(string $id, array $data): Store
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
