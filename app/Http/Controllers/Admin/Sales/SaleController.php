<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Sales\StoreSaleRequest;
use App\Models\Sale;
use App\Services\Sales\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SaleController extends Controller
{
    public function __construct(
        protected SaleService $saleService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $sales = $this->saleService->list($request->all(), $request->get('per_page', 15));
        return response()->json($sales);
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        $sale = $this->saleService->create($request->validated(), Auth::user()->id);

        return response()->json([
            'message' => __('messages.sale_created_successfully'),
            'data' => $sale->load('items.product', 'user', 'branch')
        ], 201);
    }

    public function show(Sale $sale): JsonResponse
    {
        $sale->load(['items.product', 'user', 'customer', 'branch', 'warehouse', 'payments']);
        return response()->json($sale);
    }

    public function cancel(Sale $sale): JsonResponse
    {
        $sale = $this->saleService->cancel($sale, Auth::user()->id);
        return response()->json([
            'message' => __('messages.sale_cancelled_successfully'),
            'data' => $sale
        ]);
    }

    public function dailySummary(Request $request): JsonResponse
    {
        $summary = $this->saleService->getDailySummary(
            $request->get('branch_id'),
            $request->get('date', now()->toDateString())
        );
        return response()->json($summary);
    }
}
