<?php

namespace App\Services\Organization;

use App\Actions\Organization\Stores\CreateStoreAction;
use App\Actions\Organization\Stores\UpdateStoreAction;
use App\Exceptions\Organization\Stores\StoreNotFoundException;
use App\Models\Store;

class StoreService
{
    public function __construct(
        protected CreateStoreAction $createStoreAction,
        protected UpdateStoreAction $updateStoreAction,
        protected \App\Actions\Organization\Stores\DeleteStoreAction $deleteStoreAction,
    ) {}

    // --- Queries ---

    public function findAll(array $filters)
    {
        $query = Store::with(['branches', 'warehouses']);
        if (!empty($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(slug) LIKE ?', ["%{$search}%"]);
            });
        }
        return $query->get();
    }

    public function getById(string $id): Store
    {
        $store = Store::with(['branches', 'warehouses', 'products'])->find($id);
        if (!$store) {
            throw new StoreNotFoundException();
        }
        return $store;
    }

    // --- Mutations (delegated to Actions) ---

    public function create(array $data): Store
    {
        return ($this->createStoreAction)($data);
    }

    public function update(string $id, array $data): Store
    {
        return ($this->updateStoreAction)($id, $data);
    }

    public function delete(string $id): void
    {
        ($this->deleteStoreAction)($id);
    }
}
