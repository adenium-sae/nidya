<?php

namespace App\Http\Controllers\Api\Management\Inventory\Warehouses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\Inventory\Warehouses\StoreWarehouseRequest;
use App\Http\Requests\Management\Inventory\Warehouses\UpdateWarehouseRequest;
use App\Models\ActivityLog;
use App\Services\Inventory\WarehouseService;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehousesController extends Controller
{
    use LogsActivity;
    public function __construct(
        private readonly WarehouseService $warehouseService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $warehouses = $this->warehouseService->findAll($request->all());
        return response()->json([
            "status" => true,
            "data" => $warehouses
        ]);
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $warehouse = $this->warehouseService->create($request->validated());

        $this->logActivity(
            type: ActivityLog::TYPE_INVENTORY,
            event: 'warehouse.created',
            description: "Almacén '{$warehouse->name}' creado",
            metadata: ['warehouse_id' => $warehouse->id, 'name' => $warehouse->name, 'type' => $warehouse->type],
            storeId: Auth::user()?->store_id,
        );

        return response()->json([
            "status" => true,
            "message" => __('messages.warehouse_created_successfully'),
            "data" => $warehouse->load(['store', 'branch', 'address'])
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $warehouse = $this->warehouseService->getById($id);
        return response()->json([
            "status" => true,
            "message" => __('messages.warehouse_retrieved_successfully'),
            "data" => $warehouse
        ]);
    }

    public function update(UpdateWarehouseRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();
        $warehouse = $this->warehouseService->update($id, $data);

        $this->logActivity(
            type: ActivityLog::TYPE_INVENTORY,
            event: 'warehouse.updated',
            description: "Almacén '{$warehouse->name}' actualizado",
            metadata: ['warehouse_id' => $warehouse->id, 'name' => $warehouse->name, 'changes' => array_keys($data)],
            storeId: Auth::user()?->store_id,
        );

        return response()->json([
            "status" => true,
            "message" => __('messages.warehouse_updated_successfully'),
            "data" => $warehouse
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $warehouse = $this->warehouseService->getById($id);
        $warehouseName = $warehouse->name;

        $this->warehouseService->delete($id);

        $this->logActivity(
            type: ActivityLog::TYPE_INVENTORY,
            event: 'warehouse.deleted',
            description: "Almacén '{$warehouseName}' eliminado",
            metadata: ['warehouse_id' => $id, 'name' => $warehouseName],
            level: ActivityLog::LEVEL_WARNING,
            storeId: Auth::user()?->store_id,
        );

        return response()->json([
            "status" => true,
            "message" => __('messages.warehouse_deleted_successfully')
        ]);
    }

    public function getTypes(): JsonResponse
    {
        $types = [
            ['id' => 'central', 'name' => 'Central'],
            ['id' => 'branch', 'name' => 'Sucursal'],
            ['id' => 'distribution', 'name' => 'Distribución'],
        ];

        return response()->json([
            "status" => true,
            "data" => $types
        ]);
    }
}