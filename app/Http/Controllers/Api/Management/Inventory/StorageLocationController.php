<?php

namespace App\Http\Controllers\Api\Management\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\Inventory\StorageLocationService;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StorageLocationController extends Controller
{
    use LogsActivity;
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

        $storeId = $location->warehouse->stores()->first()?->id;

        $this->logActivity(
            type: ActivityLog::TYPE_INVENTORY,
            event: 'storage_location.created',
            description: "Ubicación '{$location->name}' creada en almacén {$location->warehouse_id}",
            metadata: ['location_id' => $location->id, 'name' => $location->name, 'code' => $location->code, 'warehouse_id' => $location->warehouse_id],
            storeId: $storeId,
        );
        return response()->json([
            'message' => 'Ubicación creada correctamente',
            'data' => $location
        ], 201);
    }
}
