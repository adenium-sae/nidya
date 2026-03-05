<?php

use App\Actions\Stock\AdjustStockAction;
use App\Actions\Stock\ConfirmMovementAction;
use App\Actions\Stock\TransferStockAction;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = app(ConfirmMovementAction::class);
    $this->adjustAction = app(AdjustStockAction::class);
    $this->transferAction = app(TransferStockAction::class);
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create();
    $this->warehouse = Warehouse::factory()->forStore($this->store)->central()->create();
    $this->warehouse2 = Warehouse::factory()->forStore($this->store)->branch()->create();
    $this->product = Product::factory()->create();
});

function confirmCreateStock(int $quantity, ?string $warehouseId = null): Stock
{
    return Stock::create([
        'product_id' => test()->product->id,
        'warehouse_id' => $warehouseId ?? test()->warehouse->id,
        'quantity' => $quantity,
        'reserved' => 0,
    ]);
}

function confirmCreateAdjustment(string $type = 'increase', int $qty = 5): StockAdjustment
{
    confirmCreateStock(10);

    $modeMap = [
        'increase' => 'increment',
        'decrease' => 'decrement',
        'recount' => 'absolute',
    ];

    return (test()->adjustAction)([
        'warehouse_id' => test()->warehouse->id,
        'type' => $type,
        'reason' => 'recount',
        'items' => [
            [
                'product_id' => test()->product->id,
                'quantity' => $qty,
                'mode' => $modeMap[$type] ?? 'increment',
                'reason' => 'recount',
            ],
        ],
    ], test()->user->id);
}

function confirmCreateTransfer(int $qty = 10): StockTransfer
{
    confirmCreateStock(50);

    return (test()->transferAction)([
        'source_warehouse_id' => test()->warehouse->id,
        'destination_warehouse_id' => test()->warehouse2->id,
        'items' => [
            [
                'product_id' => test()->product->id,
                'quantity' => $qty,
            ],
        ],
    ], test()->user->id);
}

// ──────────────────────────────────────────────
// CONFIRM INDIVIDUAL MOVEMENT
// ──────────────────────────────────────────────

it('confirms a pending movement', function () {
    $adjustment = confirmCreateAdjustment();

    $movement = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustment->id)
        ->first();

    expect($movement->status)->toBe('pending');

    $result = $this->action->confirmMovement($movement->id);

    expect($result)->toBeInstanceOf(StockMovement::class);
    expect($result->status)->toBe('completed');
    expect($movement->fresh()->status)->toBe('completed');
});

it('throws when confirming already completed movement', function () {
    $adjustment = confirmCreateAdjustment();

    $movement = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustment->id)
        ->first();

    // Confirm once
    $this->action->confirmMovement($movement->id);

    // Attempt to confirm again
    $this->action->confirmMovement($movement->id);
})->throws(\Exception::class, 'ya fue confirmado');

it('throws when confirming cancelled movement', function () {
    $adjustment = confirmCreateAdjustment();

    $movement = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustment->id)
        ->first();

    // Cancel it first
    $movement->status = StockMovement::STATUS_CANCELLED;
    $movement->save();

    $this->action->confirmMovement($movement->id);
})->throws(\Exception::class, 'cancelado');

it('throws when movement not found', function () {
    $this->action->confirmMovement('00000000-0000-0000-0000-000000000000');
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

// ──────────────────────────────────────────────
// CONFIRM ADJUSTMENT
// ──────────────────────────────────────────────

it('confirms a pending adjustment', function () {
    $adjustment = confirmCreateAdjustment();

    expect($adjustment->status)->toBe('pending');

    $result = $this->action->confirmAdjustment($adjustment->id);

    expect($result)->toBeInstanceOf(StockAdjustment::class);
    expect($result->status)->toBe('completed');
    expect($adjustment->fresh()->status)->toBe('completed');
});

it('confirms related movements when confirming adjustment', function () {
    $adjustment = confirmCreateAdjustment();

    // Verify movements are pending before confirmation
    $pendingMovements = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustment->id)
        ->where('status', 'pending')
        ->count();
    expect($pendingMovements)->toBeGreaterThan(0);

    $this->action->confirmAdjustment($adjustment->id);

    // All related movements should now be completed
    $stillPending = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustment->id)
        ->where('status', 'pending')
        ->count();
    expect($stillPending)->toBe(0);

    $completed = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustment->id)
        ->where('status', 'completed')
        ->count();
    expect($completed)->toBe($pendingMovements);
});

it('confirms adjustment with multiple items', function () {
    $product2 = Product::factory()->create();

    Stock::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
        'reserved' => 0,
    ]);
    Stock::create([
        'product_id' => $product2->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 20,
        'reserved' => 0,
    ]);

    $adjustment = ($this->adjustAction)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'increase',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 5,
                'mode' => 'increment',
                'reason' => 'found',
            ],
            [
                'product_id' => $product2->id,
                'quantity' => 10,
                'mode' => 'increment',
                'reason' => 'found',
            ],
        ],
    ], $this->user->id);

    expect($adjustment->status)->toBe('pending');

    $result = $this->action->confirmAdjustment($adjustment->id);

    expect($result->status)->toBe('completed');

    // Both movements should be confirmed
    $movements = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustment->id)
        ->get();
    expect($movements)->toHaveCount(2);
    foreach ($movements as $movement) {
        expect($movement->status)->toBe('completed');
    }
});

it('throws when confirming already completed adjustment', function () {
    $adjustment = confirmCreateAdjustment();

    $this->action->confirmAdjustment($adjustment->id);

    $this->action->confirmAdjustment($adjustment->id);
})->throws(\Exception::class, 'ya fue confirmado');

it('throws when confirming cancelled adjustment', function () {
    $adjustment = confirmCreateAdjustment();

    $adjustment->status = 'cancelled';
    $adjustment->save();

    $this->action->confirmAdjustment($adjustment->id);
})->throws(\Exception::class, 'cancelado');

it('throws when adjustment not found', function () {
    $this->action->confirmAdjustment('00000000-0000-0000-0000-000000000000');
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('returns adjustment with loaded relationships', function () {
    $adjustment = confirmCreateAdjustment();

    $result = $this->action->confirmAdjustment($adjustment->id);

    expect($result->relationLoaded('items'))->toBeTrue();
    expect($result->relationLoaded('warehouse'))->toBeTrue();
});

it('only confirms pending movements not already completed ones', function () {
    $adjustment = confirmCreateAdjustment();

    // Manually complete one of the movements beforehand
    $movement = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustment->id)
        ->first();
    $movement->status = 'completed';
    $movement->save();

    // Should not throw even though one movement is already completed
    $result = $this->action->confirmAdjustment($adjustment->id);

    expect($result->status)->toBe('completed');

    // The already-completed one should still be completed
    expect($movement->fresh()->status)->toBe('completed');
});

// ──────────────────────────────────────────────
// CONFIRM ADJUSTMENT TYPE VARIATIONS
// ──────────────────────────────────────────────

it('confirms increase adjustment', function () {
    $adjustment = confirmCreateAdjustment('increase', 10);

    $result = $this->action->confirmAdjustment($adjustment->id);

    expect($result->status)->toBe('completed');
    expect($result->type)->toBe('increase');
});

it('confirms decrease adjustment', function () {
    $adjustment = confirmCreateAdjustment('decrease', 3);

    $result = $this->action->confirmAdjustment($adjustment->id);

    expect($result->status)->toBe('completed');
    expect($result->type)->toBe('decrease');
});

it('confirms recount adjustment', function () {
    $adjustment = confirmCreateAdjustment('recount', 25);

    $result = $this->action->confirmAdjustment($adjustment->id);

    expect($result->status)->toBe('completed');
    expect($result->type)->toBe('recount');
});

// ──────────────────────────────────────────────
// CONFIRM TRANSFER
// ──────────────────────────────────────────────

it('confirms a pending transfer', function () {
    $transfer = confirmCreateTransfer();

    expect($transfer->status)->toBe(StockTransfer::STATUS_PENDING);

    $result = $this->action->confirmTransfer($transfer->id);

    expect($result)->toBeInstanceOf(StockTransfer::class);
    expect($result->status)->toBe(StockTransfer::STATUS_COMPLETED);
    expect($transfer->fresh()->status)->toBe(StockTransfer::STATUS_COMPLETED);
});

it('sets received_at timestamp on transfer confirmation', function () {
    $transfer = confirmCreateTransfer();

    expect($transfer->received_at)->toBeNull();

    $result = $this->action->confirmTransfer($transfer->id);

    expect($result->received_at)->not->toBeNull();
    expect($result->received_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

it('confirms all related transfer movements', function () {
    $transfer = confirmCreateTransfer();

    // Verify movements are pending
    $movements = StockMovement::where('movable_type', StockTransfer::class)
        ->where('movable_id', $transfer->id)
        ->get();
    expect($movements)->toHaveCount(2);
    foreach ($movements as $movement) {
        expect($movement->status)->toBe('pending');
    }

    $this->action->confirmTransfer($transfer->id);

    // All related movements should now be completed
    $movements = StockMovement::where('movable_type', StockTransfer::class)
        ->where('movable_id', $transfer->id)
        ->get();
    foreach ($movements as $movement) {
        expect($movement->fresh()->status)->toBe('completed');
    }
});

it('confirms transfer with multiple products', function () {
    $product2 = Product::factory()->create();

    Stock::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 50,
        'reserved' => 0,
    ]);
    Stock::create([
        'product_id' => $product2->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 30,
        'reserved' => 0,
    ]);

    $transfer = ($this->transferAction)([
        'source_warehouse_id' => $this->warehouse->id,
        'destination_warehouse_id' => $this->warehouse2->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 10],
            ['product_id' => $product2->id, 'quantity' => 5],
        ],
    ], $this->user->id);

    $result = $this->action->confirmTransfer($transfer->id);

    expect($result->status)->toBe('completed');

    // All 4 movements (2 products × 2 directions) should be completed
    $completedCount = StockMovement::where('movable_type', StockTransfer::class)
        ->where('movable_id', $transfer->id)
        ->where('status', 'completed')
        ->count();
    expect($completedCount)->toBe(4);
});

it('throws when confirming already completed transfer', function () {
    $transfer = confirmCreateTransfer();

    $this->action->confirmTransfer($transfer->id);

    $this->action->confirmTransfer($transfer->id);
})->throws(\Exception::class, 'ya fue confirmada');

it('throws when confirming cancelled transfer', function () {
    $transfer = confirmCreateTransfer();

    $transfer->status = StockTransfer::STATUS_CANCELLED;
    $transfer->save();

    $this->action->confirmTransfer($transfer->id);
})->throws(\Exception::class, 'cancelada');

it('throws when transfer not found', function () {
    $this->action->confirmTransfer('00000000-0000-0000-0000-000000000000');
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('returns transfer with loaded relationships', function () {
    $transfer = confirmCreateTransfer();

    $result = $this->action->confirmTransfer($transfer->id);

    expect($result->relationLoaded('sourceWarehouse'))->toBeTrue();
    expect($result->relationLoaded('destinationWarehouse'))->toBeTrue();
    expect($result->relationLoaded('items'))->toBeTrue();
});

it('does not affect stock quantities on transfer confirmation', function () {
    confirmCreateStock(50);

    $transfer = ($this->transferAction)([
        'source_warehouse_id' => $this->warehouse->id,
        'destination_warehouse_id' => $this->warehouse2->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 20],
        ],
    ], $this->user->id);

    // Stock was already moved when the transfer was created
    $sourceStockBefore = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity;
    $destStockBefore = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse2->id)->first()->quantity;

    expect($sourceStockBefore)->toBe(30);
    expect($destStockBefore)->toBe(20);

    // Confirming should not move stock again
    $this->action->confirmTransfer($transfer->id);

    $sourceStockAfter = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity;
    $destStockAfter = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse2->id)->first()->quantity;

    expect($sourceStockAfter)->toBe($sourceStockBefore);
    expect($destStockAfter)->toBe($destStockBefore);
});

// ──────────────────────────────────────────────
// CANCEL MOVEMENT
// ──────────────────────────────────────────────

it('cancels a pending movement', function () {
    $adjustment = confirmCreateAdjustment();

    $movement = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustment->id)
        ->first();

    $result = $this->action->cancelMovement($movement->id);

    expect($result->status)->toBe(StockMovement::STATUS_CANCELLED);
    expect($movement->fresh()->status)->toBe(StockMovement::STATUS_CANCELLED);
});

it('throws when cancelling completed movement', function () {
    $adjustment = confirmCreateAdjustment();

    $movement = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustment->id)
        ->first();

    $movement->status = StockMovement::STATUS_COMPLETED;
    $movement->save();

    $this->action->cancelMovement($movement->id);
})->throws(\Exception::class, 'pendientes');

it('throws when cancelling already cancelled movement', function () {
    $adjustment = confirmCreateAdjustment();

    $movement = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustment->id)
        ->first();

    $movement->status = StockMovement::STATUS_CANCELLED;
    $movement->save();

    $this->action->cancelMovement($movement->id);
})->throws(\Exception::class, 'pendientes');

// ──────────────────────────────────────────────
// ISOLATION: CONFIRMING ONE DOESN'T AFFECT OTHERS
// ──────────────────────────────────────────────

it('confirming one adjustment does not affect another', function () {
    // Create two separate adjustments
    confirmCreateStock(10);

    $adj1 = ($this->adjustAction)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'increase',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 5,
                'mode' => 'increment',
                'reason' => 'found',
            ],
        ],
    ], $this->user->id);

    $adj2 = ($this->adjustAction)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'increase',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 3,
                'mode' => 'increment',
                'reason' => 'found',
            ],
        ],
    ], $this->user->id);

    // Confirm only the first one
    $this->action->confirmAdjustment($adj1->id);

    // First adjustment should be completed
    expect($adj1->fresh()->status)->toBe('completed');

    // Second adjustment should still be pending
    expect($adj2->fresh()->status)->toBe('pending');

    // Movements for adj1 should be completed
    $adj1Movements = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adj1->id)
        ->get();
    foreach ($adj1Movements as $m) {
        expect($m->status)->toBe('completed');
    }

    // Movements for adj2 should still be pending
    $adj2Movements = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adj2->id)
        ->get();
    foreach ($adj2Movements as $m) {
        expect($m->status)->toBe('pending');
    }
});

it('confirming transfer does not affect adjustment movements', function () {
    confirmCreateStock(100);

    // Create an adjustment
    $adjustment = ($this->adjustAction)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'increase',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 5,
                'mode' => 'increment',
                'reason' => 'found',
            ],
        ],
    ], $this->user->id);

    // Create a transfer
    $transfer = ($this->transferAction)([
        'source_warehouse_id' => $this->warehouse->id,
        'destination_warehouse_id' => $this->warehouse2->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ],
    ], $this->user->id);

    // Confirm only the transfer
    $this->action->confirmTransfer($transfer->id);

    // Transfer movements should be completed
    $transferMovements = StockMovement::where('movable_type', StockTransfer::class)
        ->where('movable_id', $transfer->id)
        ->get();
    foreach ($transferMovements as $m) {
        expect($m->status)->toBe('completed');
    }

    // Adjustment movements should still be pending
    $adjustmentMovements = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustment->id)
        ->get();
    foreach ($adjustmentMovements as $m) {
        expect($m->status)->toBe('pending');
    }
});

// ──────────────────────────────────────────────
// CONCURRENCY / IDEMPOTENCY EDGE CASES
// ──────────────────────────────────────────────

it('does not double confirm movements on adjustment', function () {
    $adjustment = confirmCreateAdjustment();

    // Manually set one movement to completed before confirming the adjustment
    $movement = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustment->id)
        ->first();

    $movement->status = 'completed';
    $movement->save();

    // Confirming the adjustment should not cause issues
    $result = $this->action->confirmAdjustment($adjustment->id);
    expect($result->status)->toBe('completed');

    // Movement should still be completed (not double-processed)
    expect($movement->fresh()->status)->toBe('completed');
});

// ──────────────────────────────────────────────
// FULL END-TO-END FLOW: CREATE → CONFIRM
// ──────────────────────────────────────────────

it('full flow: create adjustment then confirm', function () {
    confirmCreateStock(100);

    // 1. Create the adjustment (entry of 25 units)
    $adjustment = ($this->adjustAction)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'increase',
        'reason' => 'found',
        'notes' => 'Found in back warehouse',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 25,
                'mode' => 'increment',
                'reason' => 'found',
            ],
        ],
    ], $this->user->id);

    // Assert pending state
    expect($adjustment->status)->toBe('pending');
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(125);

    // 2. Confirm it
    $confirmed = $this->action->confirmAdjustment($adjustment->id);

    // Assert completed state
    expect($confirmed->status)->toBe('completed');
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(125);

    // All movements confirmed
    $allCompleted = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustment->id)
        ->where('status', '!=', 'completed')
        ->count();
    expect($allCompleted)->toBe(0);
});

it('full flow: create transfer then confirm', function () {
    confirmCreateStock(80);
    confirmCreateStock(20, $this->warehouse2->id);

    // 1. Create transfer (move 30 from warehouse1 to warehouse2)
    $transfer = ($this->transferAction)([
        'source_warehouse_id' => $this->warehouse->id,
        'destination_warehouse_id' => $this->warehouse2->id,
        'notes' => 'Weekly restock',
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 30],
        ],
    ], $this->user->id);

    // Assert pending state
    expect($transfer->status)->toBe('pending');
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(50);
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse2->id)->first()->quantity)->toBe(50);

    // 2. Confirm it
    $confirmed = $this->action->confirmTransfer($transfer->id);

    // Assert completed state
    expect($confirmed->status)->toBe('completed');
    expect($confirmed->received_at)->not->toBeNull();

    // Stock unchanged (was already moved on creation)
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(50);
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse2->id)->first()->quantity)->toBe(50);

    // Total conserved
    $total = Stock::where('product_id', $this->product->id)->sum('quantity');
    expect((int) $total)->toBe(100);

    // All movements completed
    $allCompleted = StockMovement::where('movable_type', StockTransfer::class)
        ->where('movable_id', $transfer->id)
        ->where('status', 'completed')
        ->count();
    expect($allCompleted)->toBe(2);
});

it('full flow: multiple adjustments and transfers confirmed independently', function () {
    confirmCreateStock(100);

    // Create adjustment 1: entry +20
    $adj1 = ($this->adjustAction)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'increase',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 20,
                'mode' => 'increment',
                'reason' => 'found',
            ],
        ],
    ], $this->user->id);
    // Stock now: 120

    // Create transfer: move 40 to warehouse2
    $transfer = ($this->transferAction)([
        'source_warehouse_id' => $this->warehouse->id,
        'destination_warehouse_id' => $this->warehouse2->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 40],
        ],
    ], $this->user->id);
    // Stock now: warehouse=80, warehouse2=40

    // Create adjustment 2: exit -5
    $adj2 = ($this->adjustAction)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'decrease',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 5,
                'mode' => 'decrement',
                'reason' => 'damaged',
            ],
        ],
    ], $this->user->id);
    // Stock now: warehouse=75, warehouse2=40

    // Verify current state: all pending
    expect($adj1->fresh()->status)->toBe('pending');
    expect($transfer->fresh()->status)->toBe('pending');
    expect($adj2->fresh()->status)->toBe('pending');

    // Confirm in different order: transfer first, then adj2, then adj1
    $this->action->confirmTransfer($transfer->id);
    expect($transfer->fresh()->status)->toBe('completed');
    expect($adj1->fresh()->status)->toBe('pending');
    expect($adj2->fresh()->status)->toBe('pending');

    $this->action->confirmAdjustment($adj2->id);
    expect($adj2->fresh()->status)->toBe('completed');
    expect($adj1->fresh()->status)->toBe('pending');

    $this->action->confirmAdjustment($adj1->id);
    expect($adj1->fresh()->status)->toBe('completed');

    // Final stock quantities
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(75);
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse2->id)->first()->quantity)->toBe(40);

    // Total: 75 + 40 = 115 (original 100 + 20 entry - 5 exit)
    $total = Stock::where('product_id', $this->product->id)->sum('quantity');
    expect((int) $total)->toBe(115);
});