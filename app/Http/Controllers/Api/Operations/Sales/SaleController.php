<?php

namespace App\Http\Controllers\Api\Operations\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreSaleRequest;
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

    public function store(StoreSaleRequest $request): JsonResponse
    {
        $sale = $this->saleService->create($request->validated(), Auth::user()->id);

        return response()->json([
            'message' => __('messages.sale_created_successfully'),
            'data' => $sale->load('items.product', 'user', 'branch')
        ], 201);
    }
}
