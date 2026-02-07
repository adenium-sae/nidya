<?php

namespace App\Http\Controllers\Api\Management\Inventory\Warehouses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\Inventory\Warehouses\StoreWarehouseRequest;
use App\Http\Requests\Management\Inventory\Warehouses\UpdateWarehouseRequest;
use App\Services\Inventory\WarehouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehousesController extends Controller
{
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
        return response()->json([
            "status" => true,
            "message" => __('messages.warehouse_updated_successfully'),
            "data" => $warehouse
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->warehouseService->delete($id);
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