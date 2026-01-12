<?php

namespace App\Services\Admin\Branches;

use App\Exceptions\Branches\BranchNotFoundException;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class BranchService {

    public function findAll(array $filters) {
        $query = Branch::query();
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('sku', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }
        if (!empty($filters['store_id'])) {
            $query->whereHas('storeProducts', function ($q) use ($filters) {
                $q->where('store_id', $filters['store_id']);
            });
        }
        if (isset($filters['is_active'])) {
            $query->whereHas('storeProducts', function ($q) use ($filters) {
                $q->where('is_active', $filters['is_active']);
            });
        }
        if (isset($filters['min_stock'])) {
            $query->whereHas('stockItems', function ($q) use ($filters) {
                $q->where('stock', '>=', $filters['min_stock']);
            });
        }
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);
        $perPage = $filters['per_page'] ?? 15;
        $paginated = $query->paginate($perPage);
        $requestedColumns = $this->parseColumns($filters['columns'] ?? null);
        $paginated->getCollection()->transform(function ($branch) use ($requestedColumns) {
            return $this->normalizeBranchForTable($branch, $requestedColumns);
        });
        return $paginated;
    }

    public function update(string $id, array $data) {
        DB::beginTransaction();
        try {
            $branch = Branch::findOrFail($id);
            if (isset($data['name'])) $branch->name = $data['name'];
            if (isset($data['store_id'])) $branch->store_id = $data['store_id'];
            if (isset($data['is_active'])) $branch->is_active = $data['is_active'];
            $branch->save();
            DB::commit();
            return $branch;
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                throw new BranchNotFoundException();
            }
            throw $e;
        }
    }

    public function getById(string $id) {
        try {
            return Branch::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new BranchNotFoundException();
        }
    }

    private function parseColumns(?string $columns): array {
        if (empty($columns)) {
            return [];
        }
        $allowed = [
            'id',
            'name',
            'store_id',
            'is_active',
            'created_at',
            'updated_at'
        ];
        $requested = array_map('trim', explode(',', $columns));
        return array_values(array_intersect($requested, $allowed));
    }

    private function normalizeBranchForTable($branch, array $requestedColumns = []) {
        $branch->loadMissing('store');
        $allData = [
            'id' => $branch->id,
            'name' => $branch->name,
            'store_id' => $branch->store_id,
            'store' => $branch->store->name,
            'is_active' => $branch->is_active,
            'created_at' => $branch->created_at,
            'updated_at' => $branch->updated_at,
        ];
        if (empty($requestedColumns)) {
            return $allData;
        }
        $filtered = ['id' => $allData['id']];
        foreach ($requestedColumns as $col) {
            if (isset($allData[$col])) {
                $filtered[$col] = $allData[$col];
            }
        }
        return $filtered;
    }
}
