<?php

namespace App\Http\Controllers\Api\Management\Inventory\Stock;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\Stock\StockService;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StockController extends Controller
{
    use LogsActivity;

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
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'storage_location_id' => 'nullable|exists:storage_locations,id',
            'type' => 'required|string|in:increase,decrease,recount,adjustment',
            'reason' => 'nullable|string|in:damaged,lost,found,expired,recount,correction,other',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.mode' => 'required|string|in:increment,decrement,absolute',
            'items.*.reason' => 'nullable|string|in:damaged,lost,found,expired,recount,correction,other',
        ]);

        try {
            $adjustment = $this->stockService->adjust($validated, $request->user()->id);

            // Log activity
            $typeDescription = match($validated['type']) {
                'increase' => 'Entrada de inventario',
                'decrease' => 'Salida de inventario',
                'recount' => 'Recuento de inventario',
                'adjustment' => 'Ajuste de inventario',
                default => 'Ajuste de inventario'
            };

            $itemCount = count($validated['items']);
            $this->logActivity(
                type: ActivityLog::TYPE_INVENTORY,
                event: 'stock.adjusted',
                description: "{$typeDescription} registrado ({$itemCount} producto(s))",
                metadata: [
                    'adjustment_id' => $adjustment->id ?? null,
                    'type' => $validated['type'],
                    'item_count' => $itemCount,
                    'warehouse_id' => $validated['warehouse_id'],
                    'reason' => $validated['reason'] ?? null,
                ],
                storeId: $request->user()->store_id
            );

            return response()->json([
                'status' => true,
                'message' => 'Ajuste de inventario registrado correctamente.',
                'data' => $adjustment,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al procesar el ajuste de inventario.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function movements(Request $request): JsonResponse
    {
        $movements = $this->stockService->listMovements($request->all(), $request->get('per_page', 50));
        return response()->json($movements);
    }

    public function transfers(Request $request): JsonResponse
    {
        $transfers = $this->stockService->listTransfers($request->all(), $request->get('per_page', 50));
        return response()->json($transfers);
    }

    public function adjustments(Request $request): JsonResponse
    {
        $adjustments = $this->stockService->listAdjustments($request->all(), $request->get('per_page', 50));
        return response()->json($adjustments);
    }

    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_warehouse_id' => 'required|exists:warehouses,id|different:destination_warehouse_id',
            'destination_warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.source_location_id' => 'nullable|exists:storage_locations,id',
            'items.*.destination_location_id' => 'nullable|exists:storage_locations,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $transfer = $this->stockService->transfer($validated, $request->user()->id);

            // Log activity
            $itemCount = count($validated['items']);
            $this->logActivity(
                type: ActivityLog::TYPE_INVENTORY,
                event: 'stock.transferred',
                description: "Transferencia de inventario registrada ({$itemCount} producto(s))",
                metadata: [
                    'transfer_id' => $transfer->id ?? null,
                    'source_warehouse_id' => $validated['source_warehouse_id'],
                    'destination_warehouse_id' => $validated['destination_warehouse_id'],
                    'item_count' => $itemCount,
                ],
                storeId: $request->user()->store_id
            );

            return response()->json([
                'status' => true,
                'message' => 'Transferencia registrada correctamente.',
                'data' => $transfer,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 422);
        }
    }

    public function updateQuantity(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
            'reason' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $stock = $this->stockService->updateQuantity($id, $validated, $request->user()->id);

            // Log activity
            $this->logActivity(
                type: ActivityLog::TYPE_INVENTORY,
                event: 'stock.quantity_updated',
                description: "Cantidad de stock actualizada. Motivo: {$validated['reason']}",
                metadata: [
                    'stock_id' => $id,
                    'new_quantity' => $validated['quantity'],
                    'reason' => $validated['reason'],
                ],
                storeId: $request->user()->store_id
            );

            return response()->json([
                'status' => true,
                'message' => 'Cantidad actualizada correctamente.',
                'data' => $stock,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function confirmMovement(string $id): JsonResponse
    {
        try {
            $movement = $this->stockService->confirmMovement($id);

            // Log activity
            $this->logActivity(
                type: ActivityLog::TYPE_INVENTORY,
                event: 'movement.confirmed',
                description: "Movimiento de inventario confirmado",
                metadata: ['movement_id' => $id],
                storeId: auth()?->user()?->store_id
            );

            return response()->json([
                'status' => true,
                'message' => 'Movimiento confirmado correctamente.',
                'data' => $movement,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function confirmAdjustment(string $id): JsonResponse
    {
        try {
            $adjustment = $this->stockService->confirmAdjustment($id);

            // Log activity
            $this->logActivity(
                type: ActivityLog::TYPE_INVENTORY,
                event: 'adjustment.confirmed',
                description: "Ajuste de inventario confirmado",
                metadata: ['adjustment_id' => $id],
                storeId: auth()?->user()?->store_id
            );

            return response()->json([
                'status' => true,
                'message' => 'Ajuste confirmado correctamente.',
                'data' => $adjustment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function confirmTransfer(string $id): JsonResponse
    {
        try {
            $transfer = $this->stockService->confirmTransfer($id);

            // Log activity
            $this->logActivity(
                type: ActivityLog::TYPE_INVENTORY,
                event: 'transfer.confirmed',
                description: "Transferencia de inventario confirmada",
                metadata: ['transfer_id' => $id],
                storeId: auth()?->user()?->store_id
            );

            return response()->json([
                'status' => true,
                'message' => 'Transferencia confirmada correctamente.',
                'data' => $transfer,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancelMovement(string $id): JsonResponse
    {
        try {
            $movement = $this->stockService->cancelMovement($id);

            // Log activity
            $this->logActivity(
                type: ActivityLog::TYPE_INVENTORY,
                event: 'movement.cancelled',
                description: "Movimiento de inventario cancelado",
                metadata: ['movement_id' => $id],
                level: ActivityLog::LEVEL_WARNING,
                storeId: auth()?->user()?->store_id
            );

            return response()->json([
                'status' => true,
                'message' => 'Movimiento cancelado correctamente.',
                'data' => $movement,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancelTransfer(string $id): JsonResponse
    {
        try {
            $transfer = $this->stockService->cancelTransfer($id);

            // Log activity
            $this->logActivity(
                type: ActivityLog::TYPE_INVENTORY,
                event: 'transfer.cancelled',
                description: "Transferencia de inventario cancelada",
                metadata: ['transfer_id' => $id],
                level: ActivityLog::LEVEL_WARNING,
                storeId: auth()?->user()?->store_id
            );

            return response()->json([
                'status' => true,
                'message' => 'Transferencia cancelada correctamente.',
                'data' => $transfer,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}