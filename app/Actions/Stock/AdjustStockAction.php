<?php

namespace App\Actions\Stock;

use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class AdjustStockAction
{
    /**
     * Valid adjustment types mapped to their corresponding stock_movements.type enum values.
     *
     * Migration enum for stock_movements.type:
     *   ['entry', 'exit', 'transfer', 'adjustment', 'sale', 'return', 'damage', 'production']
     *
     * Migration enum for stock_adjustments.type:
     *   ['increase', 'decrease', 'recount']
     */
    private const TYPE_TO_MOVEMENT_TYPE = [
        'increase' => 'entry',
        'decrease' => 'exit',
        'recount'  => 'adjustment',
    ];

    public function __invoke(array $data, string $userId): StockAdjustment
    {
        $this->validateData($data);

        return DB::transaction(function () use ($data, $userId) {
            $folio = $this->generateFolio();

            // Normalize type: map 'adjustment' from frontend to 'recount' for DB enum
            $adjustmentType = $this->normalizeAdjustmentType($data['type'] ?? 'recount');

            $adjustment = StockAdjustment::create([
                'folio' => $folio,
                'warehouse_id' => $data['warehouse_id'],
                'type' => $adjustmentType,
                'status' => 'pending',
                'reason' => $data['reason'] ?? $data['items'][0]['reason'] ?? 'other',
                'user_id' => $userId,
                'notes' => $data['notes'] ?? null,
            ]);

            $storageLocationId = $data['storage_location_id'] ?? null;
            if ($storageLocationId === '' || $storageLocationId === 'undefined') {
                $storageLocationId = null;
            }

            foreach ($data['items'] as $itemData) {
                $this->processAdjustmentItem(
                    $adjustment,
                    $itemData,
                    $data['warehouse_id'],
                    $storageLocationId,
                    $itemData['reason'] ?? $data['reason'] ?? 'other',
                    $userId
                );
            }

            return $adjustment->load(['items.product', 'warehouse', 'user']);
        });
    }

    /**
     * Normalize the adjustment type coming from frontend to a valid DB enum value.
     *
     * The frontend may send 'adjustment' (from StockAdjustmentForm in 'adjustment' mode),
     * but the DB enum only allows: increase, decrease, recount.
     */
    private function normalizeAdjustmentType(string $type): string
    {
        $map = [
            'increase'   => 'increase',
            'decrease'   => 'decrease',
            'recount'    => 'recount',
            'adjustment' => 'recount',  // frontend sends 'adjustment', map to valid enum
        ];

        return $map[$type] ?? 'recount';
    }

    /**
     * Map the adjustment type to the valid stock_movements.type enum value.
     */
    private function getMovementType(string $adjustmentType, string $mode): string
    {
        // If mode gives us a clear direction, use that
        if ($mode === 'increment') {
            return 'entry';
        }

        if ($mode === 'decrement') {
            return 'exit';
        }

        // For absolute mode, use the adjustment type mapping
        return self::TYPE_TO_MOVEMENT_TYPE[$adjustmentType] ?? 'adjustment';
    }

    private function generateFolio(): string
    {
        $year = now()->year;
        $lastAdjustment = StockAdjustment::where('folio', 'like', "ADJ-{$year}-%")
            ->orderByDesc('folio')
            ->first();

        $number = 1;
        if ($lastAdjustment && preg_match('/(\d{5})$/', $lastAdjustment->folio, $matches)) {
            $number = ((int) $matches[1]) + 1;
        }

        return 'ADJ-' . $year . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    private function validateData(array $data): void
    {
        if (empty($data['warehouse_id'])) {
            throw new \InvalidArgumentException('El almacén es obligatorio.');
        }

        if (empty($data['items']) || !is_array($data['items'])) {
            throw new \InvalidArgumentException('Debe incluir al menos un producto.');
        }

        foreach ($data['items'] as $index => $item) {
            if (empty($item['product_id'])) {
                throw new \InvalidArgumentException("El producto es obligatorio en el item #{$index}.");
            }

            if (!isset($item['quantity']) || !is_numeric($item['quantity'])) {
                throw new \InvalidArgumentException("La cantidad es obligatoria y debe ser numérica en el item #{$index}.");
            }
        }
    }

    private function processAdjustmentItem(
        StockAdjustment $adjustment,
        array $itemData,
        string $warehouseId,
        ?string $storageLocationId,
        string $reason,
        string $userId
    ): void {
        // Build the stock query matching exact location (null-safe)
        $stockQuery = Stock::where('product_id', $itemData['product_id'])
            ->where('warehouse_id', $warehouseId);

        if ($storageLocationId) {
            $stockQuery->where('storage_location_id', $storageLocationId);
        } else {
            $stockQuery->whereNull('storage_location_id');
        }

        $stock = $stockQuery->lockForUpdate()->first();

        $quantityBefore = $stock ? $stock->quantity : 0;
        $mode = $itemData['mode'] ?? 'absolute';
        $inputQuantity = (int) ($itemData['quantity'] ?? $itemData['quantity_after'] ?? 0);

        $quantityAfter = match ($mode) {
            'increment' => $quantityBefore + $inputQuantity,
            'decrement' => max(0, $quantityBefore - $inputQuantity),
            'absolute'  => max(0, $inputQuantity),
            default     => max(0, $inputQuantity),
        };

        // Validate decrement doesn't exceed available stock
        if ($mode === 'decrement' && $inputQuantity > $quantityBefore) {
            // Allow it but cap at 0 (already handled by max(0, ...))
            // Log a note about this
        }

        $difference = $quantityAfter - $quantityBefore;

        // Create the adjustment item record
        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $itemData['product_id'],
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
        ]);

        // Do NOT update stock yet — stock is only applied when the adjustment is confirmed.

        // Determine the correct movement type based on the DB enum
        $movementType = $this->getMovementType($adjustment->type, $mode);

        // Create the stock movement log
        StockMovement::create([
            'product_id' => $itemData['product_id'],
            'warehouse_id' => $warehouseId,
            'storage_location_id' => $storageLocationId,
            'type' => $movementType,
            'status' => StockMovement::STATUS_PENDING,
            'quantity' => $difference,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'notes' => $this->buildMovementNote($adjustment->type, $reason, $adjustment->folio),
            'user_id' => $userId,
            'movable_type' => StockAdjustment::class,
            'movable_id' => $adjustment->id,
        ]);
    }

    private function buildMovementNote(string $type, string $reason, string $folio): string
    {
        $typeLabels = [
            'increase' => 'Entrada',
            'decrease' => 'Salida',
            'recount'  => 'Ajuste/Recuento',
        ];

        $reasonLabels = [
            'damaged' => 'Dañado',
            'lost'    => 'Pérdida/Robo',
            'found'   => 'Hallazgo',
            'expired' => 'Caducado',
            'recount' => 'Recuento',
            'correction' => 'Corrección',
            'other'   => 'Otro',
        ];

        $typeLabel = $typeLabels[$type] ?? $type;
        $reasonLabel = $reasonLabels[$reason] ?? $reason;

        return "{$typeLabel} por {$reasonLabel} (folio: {$folio})";
    }
}