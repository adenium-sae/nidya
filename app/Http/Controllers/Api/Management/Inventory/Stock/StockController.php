<?php

namespace App\Http\Controllers\Api\Management\Inventory\Stock;

use App\Actions\Stock\AdjustStockAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\Inventory\Stock\AdjustStockRequest;
use App\Services\Stock\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockController extends Controller
{
    public function __construct(
        protected StockService $stockService,
        protected AdjustStockAction $adjustStockAction
    ) {}

    public function index(Request $request): JsonResponse
    {
        $stock = $this->stockService->list($request->all(), $request->get('per_page', 50));
        return response()->json($stock);
    }

    public function adjust(AdjustStockRequest $request): JsonResponse
    {
        $adjustment = ($this->adjustStockAction)($request->validated(), Auth::user()->id);

        return response()->json([
            'message' => __('messages.stock_adjusted_successfully'),
            'data' => $adjustment->load('items.product')
        ], 201);
    }

    public function movements(Request $request): JsonResponse
    {
        $movements = $this->stockService->listMovements($request->all(), $request->get('per_page', 50));
        return response()->json($movements);
    }
}
