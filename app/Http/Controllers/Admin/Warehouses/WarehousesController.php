<?php

namespace App\Http\Controllers\Admin\Warehouses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Warehouses\UpdateWarehouseRequest;
use App\Services\Admin\Warehouses\WarehouseService;
use Illuminate\Http\Request;

class WarehousesController extends Controller
{
    public function __construct(private readonly WarehouseService $warehouseService) {}

    public function update(UpdateWarehouseRequest $request, string $id)
    {
        $data = $request->validated();
        $warehouse = $this->warehouseService->update($id, $data);
        return response()->json([
            "status" => true,
            "message" => __('messages.warehouse_updated_successfully'),
            "data" => $warehouse
        ]);
    }

    public function show(string $id)
    {
        $warehouse = $this->warehouseService->getById($id);
        return response()->json([
            "status" => true,
            "message" => __('messages.warehouse_retrieved_successfully'),
            "data" => $warehouse
        ]);
    }
}