<?php

namespace App\Http\Controllers\Api\Management\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Models\ActivityLog;
use App\Models\Sale;
use App\Services\Sales\SaleService;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SaleController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected SaleService $saleService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $sales = $this->saleService->list($request->all(), $request->get('per_page', 15));
        return response()->json($sales);
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        $sale = $this->saleService->create($request->validated());
        $this->logActivity(
            type: ActivityLog::TYPE_SALES,
            event: 'sale.created',
            description: "Venta {$sale->folio} registrada por $" . number_format($sale->total, 2),
            metadata: [
                'sale_id' => $sale->id,
                'folio' => $sale->folio,
                'total' => $sale->total,
                'item_count' => count($sale->items ?? []),
                'customer_id' => $sale->customer_id ?? null,
            ],
            storeId: $sale->store_id
        );

        return response()->json([
            'message' => __('messages.sale_created_successfully'),
            'data' => $sale->load(['items.product', 'user', 'customer', 'branch']),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $sale = $this->saleService->getById($id);
        $sale->load(['items.product', 'user', 'customer', 'branch', 'warehouse', 'payments']);
        return response()->json($sale);
    }

    public function cancel(string $id): JsonResponse
    {
        $sale = $this->saleService->getById($id);
        $saleFolio = $sale->folio;
        $saleTotal = $sale->total;

        $sale = $this->saleService->cancel($sale);

        // Log activity
        $this->logActivity(
            type: ActivityLog::TYPE_SALES,
            event: 'sale.cancelled',
            description: "Venta {$saleFolio} cancelada. Total: $" . number_format($saleTotal, 2),
            metadata: [
                'sale_id' => $sale->id,
                'folio' => $saleFolio,
                'total' => $saleTotal,
            ],
            level: ActivityLog::LEVEL_WARNING,
            storeId: $sale->store_id
        );

        return response()->json([
            'message' => __('messages.sale_cancelled_successfully'),
            'data' => $sale,
        ]);
    }

    public function dailySummary(Request $request): JsonResponse
    {
        $summary = $this->saleService->getDailySummary(
            $request->get('branch_id'),
            $request->get('date', now()->toDateString()),
            Auth::user()
        );
        return response()->json($summary);
    }
}
