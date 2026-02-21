<?php

namespace App\Services\Organization;

use App\Actions\Organization\Branches\CreateBranchAction;
use App\Actions\Organization\Branches\UpdateBranchAction;
use App\Exceptions\Organization\Branches\BranchNotFoundException;
use App\Models\Branch;

class BranchService
{
    public function __construct(
        protected CreateBranchAction $createBranchAction,
        protected UpdateBranchAction $updateBranchAction,
    ) {}

    // --- Queries ---

    public function findAll(array $filters)
    {
        $query = Branch::with(['store', 'address', 'warehouses']);
        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }
        if (!empty($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }
        return $query->get();
    }

    public function getById(string $id): Branch
    {
        $branch = Branch::with(['store', 'address', 'warehouses', 'cashRegisters'])->find($id);
        if (!$branch) {
            throw new BranchNotFoundException();
        }
        return $branch;
    }

    // --- Mutations (delegated to Actions) ---

    public function create(array $data): Branch
    {
        return ($this->createBranchAction)($data);
    }

    public function update(string $id, array $data): Branch
    {
        return ($this->updateBranchAction)($id, $data);
    }
}
