<?php

namespace App\Http\Controllers\Api\Management\Organization\Stores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\Organization\Stores\CreateStoreRequest;
use App\Http\Requests\Management\Organization\Stores\UpdateStoreRequest;
use App\Models\ActivityLog;
use App\Services\Organization\StoreService;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;

class StoresController extends Controller
{
    use LogsActivity;
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

        $this->logActivity(
            type: ActivityLog::TYPE_ORGANIZATION,
            event: 'store.created',
            description: "Tienda '{$store->name}' creada",
            metadata: ['store_id' => $store->id, 'name' => $store->name],
            storeId: $store->id,
        );

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

        $this->logActivity(
            type: ActivityLog::TYPE_ORGANIZATION,
            event: 'store.updated',
            description: "Tienda '{$store->name}' actualizada",
            metadata: ['store_id' => $store->id, 'name' => $store->name, 'changes' => array_keys($data)],
            storeId: $store->id,
        );

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
        $store = $this->storeService->getById($id);
        $storeName = $store->name;

        $this->storeService->delete($id);

        $this->logActivity(
            type: ActivityLog::TYPE_ORGANIZATION,
            event: 'store.deleted',
            description: "Tienda '{$storeName}' eliminada",
            metadata: ['store_id' => $id, 'name' => $storeName],
            level: ActivityLog::LEVEL_WARNING,
        );

        return response()->json([
            "status" => true,
            "message" => __('messages.store_deleted_successfully')
        ]);
    }
}
