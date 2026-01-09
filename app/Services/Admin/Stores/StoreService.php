<?php

namespace App\Services\Admin\Stores;

use App\Exceptions\Stores\StoreNotFoundException;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class StoreService
{
    public function findAllByAdmin(array $data, string $user_id) {
        $query = Store::where('user_id', $user_id);
        if (isset($data['is_active'])) {
            $query->where('is_active', $data['is_active']);
        }
        return $query->get();
    }

    public function update(string $id, array $data): Store {
        DB::beginTransaction();
        try {
            $store = Store::findOrFail($id);
            if (isset($data['name'])) $store->name = $data['name'];
            if (isset($data['slug'])) $store->slug = $data['slug'];
            if (isset($data['is_active'])) $store->is_active = $data['is_active'];
            $store->save();
            DB::commit();
            return $store;
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                throw new StoreNotFoundException();
            }
            throw $e;
        }
    }

    public function getById(string $id) {
        try {
            return Store::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new StoreNotFoundException();
        }
    }
}