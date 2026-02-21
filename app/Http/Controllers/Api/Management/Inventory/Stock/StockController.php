<?php

namespace App\Http\Controllers\Api\Management\Inventory\Stock;

use App\Http\Controllers\Controller;
use App\Services\Stock\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(
        protected StockService $stockService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $stock = $this->stockService->list($request->all(), $request->get('per_page', 50));
        return response()->json($stock);
    }

    public function adjust(Request $request): JsonResponse
    {
        $adjustment = $this->stockService->adjust($request->all(), $request->user()->id);
        return response()->json($adjustment);
    }

    public function movements(Request $request): JsonResponse
    {
        $movements = $this->stockService->listMovements($request->all(), $request->get('per_page', 50));
        return response()->json($movements);
    }

    public function adjustments(Request $request): JsonResponse
    {
        $adjustments = $this->stockService->listAdjustments($request->all(), $request->get('per_page', 50));
        return response()->json($adjustments);
    }

    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_warehouse_id' => 'required|exists:warehouses,id',
            'destination_warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $transfer = $this->stockService->transfer($validated, $request->user()->id);

        return response()->json($transfer);
    }

    public function updateQuantity(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $stock = $this->stockService->updateQuantity($id, $validated, $request->user()->id);

        return response()->json($stock);
    }
}
