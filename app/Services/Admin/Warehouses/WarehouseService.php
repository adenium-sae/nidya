<?php

namespace App\Services\Admin\Warehouses;

use App\Exceptions\Warehouses\WarehouseNotFoundException;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class WarehouseService
{
    public function update(string $id, array $data) {
        DB::beginTransaction();
        try {
            $warehouse = Warehouse::findOrFail($id);
            if (isset($data['name'])) $warehouse->name = $data['name'];
            if (isset($data['type'])) $warehouse->type = $data['type'];
            if (isset($data['is_active'])) $warehouse->is_active = $data['is_active'];
            if (isset($data['branch_id'])) $warehouse->branch_id = $data['branch_id'];
            $warehouse->save();
            DB::commit();
            return $warehouse;
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                throw new WarehouseNotFoundException();
            }
            throw $e;
        }
    }

    public function getById(string $id) {
        try {
            return Warehouse::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new WarehouseNotFoundException();
        }
    }
}