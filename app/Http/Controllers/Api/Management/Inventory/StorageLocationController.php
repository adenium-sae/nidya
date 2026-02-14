<?php

namespace App\Http\Controllers\Api\Management\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\StorageLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorageLocationController extends Controller
{
    public function __construct(
        protected StorageLocationService $storageLocationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $locations = $this->storageLocationService->list($request->all());
        return response()->json([
            'data' => $locations
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|in:shelf,box,pallet,display,floor,other',
            'aisle' => 'nullable|string|max:50',
            'section' => 'nullable|string|max:50',
        ]);

        $location = $this->storageLocationService->create($validated);

        return response()->json([
            'message' => 'Ubicación creada correctamente',
            'data' => $location
        ], 201);
    }
}
