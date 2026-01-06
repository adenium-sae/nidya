<?php

namespace App\Services\Admin\Branches;

use App\Exceptions\Branches\BranchNotFoundException;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class BranchService
{
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
}