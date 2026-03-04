<?php

namespace App\Http\Controllers\Api\Management\Organization\Stores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\Organization\Stores\CreateStoreRequest;
use App\Http\Requests\Management\Organization\Stores\UpdateStoreRequest;
use App\Services\Organization\StoreService;
use Illuminate\Http\Request;

class StoresController extends Controller
{
    public function __construct(private readonly StoreService $storeService) {}

    public function index(Request $request)
    {
        $result = $this->storeService->findAll($request->all());
        return response()->json([
            "status" => true,
            "message" => __('messages.stores_retrieved_successfully'),
            "data" => $result
        ]);
    }

    public function store(CreateStoreRequest $request)
    {
        $data = $request->validated();
        $store = $this->storeService->create($data);
        return response()->json([
            "status" => true,
            "message" => __('messages.store_created_successfully'),
            "data" => $store
        ], 201);
    }

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

    public function destroy(string $id)
    {
        $this->storeService->delete($id);
        return response()->json([
            "status" => true,
            "message" => __('messages.store_deleted_successfully')
        ]);
    }
}
