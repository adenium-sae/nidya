<?php

namespace App\Http\Controllers\Admin\Stores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Stores\UpdateStoreRequest;
use App\Services\Admin\Stores\StoreService;
use Illuminate\Http\Request;

class StoresController extends Controller
{
    public function __construct(private readonly StoreService $storeService) {}

    public function update(UpdateStoreRequest $request, string $id)
    {
        $data = $request->validated();
        $store = $this->storeService->update($id, $data);
        return response()->json([
            "status" => true,
            "message" => __('messages.store_updated_successfully'),
            "data" => $store
        ]);
    }

    public function show(string $id)
    {
        $store = $this->storeService->getById($id);
        return response()->json([
            "status" => true,
            "message" => __('messages.store_retrieved_successfully'),
            "data" => $store
        ]);
    }
}