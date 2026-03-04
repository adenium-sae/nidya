<?php

namespace App\Actions\Organization\Branches;

use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class CreateBranchAction
{
    public function __invoke(array $data): Branch
    {
        return DB::transaction(function () use ($data) {
            $branch = Branch::create($data);

            if (!empty($data['store_ids'])) {
                $branch->stores()->attach($data['store_ids']);
            }

            // Create default warehouse
            $warehouse = Warehouse::create([
                'name' => 'Almacén principal - ' . $branch->name,
                'code' => $branch->code ? $branch->code . '-ALM' : null,
                'type' => 'branch',
                'branch_id' => $branch->id,
                'is_active' => true,
            ]);

            if (!empty($data['store_ids'])) {
                $warehouse->stores()->attach($data['store_ids']);
            }

            return $branch;
        });
    }
}
