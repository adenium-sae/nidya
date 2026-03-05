<?php

use App\Actions\Stock\AdjustStockAction;
use App\Actions\Stock\TransferStockAction;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StorageLocation;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Stock\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(StockService::class);
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create();
    $this->warehouse = Warehouse::factory()->forStore($this->store)->central()->create();
    $this->warehouse2 = Warehouse::factory()->forStore($this->store)->branch()->create();
    $this->product = Product::factory()->create();
    $this->product2 = Product::factory()->create();
});

function serviceCreateStock(
    string $productId,
    string $warehouseId,
    int $quantity,
    ?string $locationId = null
): Stock {
    return Stock::create([
        'product_id' => $productId,
        'warehouse_id' => $warehouseId,
        'storage_location_id' => $locationId,
        'quantity' => $quantity,
        'reserved' => 0,
    ]);
}

function serviceCreateAdjustment(string $warehouseId, string $userId, string $type = 'increase'): StockAdjustment
{
    return app(AdjustStockAction::class)([
        'warehouse_id' => $warehouseId,
        'type' => $type,
        'reason' => 'recount',
        'items' => [
            [
                'product_id' => test()->product->id,
                'quantity' => 5,
                'mode' => $type === 'decrease' ? 'decrement' : 'increment',
                'reason' => 'recount',
            ],
        ],
    ], $userId);
}

function serviceCreateTransfer(string $srcId, string $dstId, string $userId, int $qty = 10): StockTransfer
{
    return app(TransferStockAction::class)([
        'source_warehouse_id' => $srcId,
        'destination_warehouse_id' => $dstId,
        'items' => [
            [
                'product_id' => test()->product->id,
                'quantity' => $qty,
            ],
        ],
    ], $userId);
}

// ──────────────────────────────────────────────
// LIST STOCK
// ──────────────────────────────────────────────

it('lists all stock records', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 10);
    serviceCreateStock($this->product2->id, $this->warehouse->id, 20);

    $result = $this->service->list([], 50);

    expect($result)->toHaveCount(2);
});

it('filters stock by warehouse_id', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 10);
    serviceCreateStock($this->product->id, $this->warehouse2->id, 20);

    $result = $this->service->list(['warehouse_id' => $this->warehouse->id], 50);

    expect($result)->toHaveCount(1);
    expect($result->first()->warehouse_id)->toBe($this->warehouse->id);
});

it('filters stock by product_id', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 10);
    serviceCreateStock($this->product2->id, $this->warehouse->id, 20);

    $result = $this->service->list(['product_id' => $this->product->id], 50);

    expect($result)->toHaveCount(1);
    expect($result->first()->product_id)->toBe($this->product->id);
});

it('filters stock by both warehouse and product', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 10);
    serviceCreateStock($this->product->id, $this->warehouse2->id, 20);
    serviceCreateStock($this->product2->id, $this->warehouse->id, 30);

    $result = $this->service->list([
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $this->product->id,
    ], 50);

    expect($result)->toHaveCount(1);
    expect($result->first()->quantity)->toBe(10);
});

it('filters stock by storage_location_id null', function () {
    $location = StorageLocation::create([
        'warehouse_id' => $this->warehouse->id,
        'code' => 'SVC-01',
        'name' => 'Service Test Location',
        'type' => 'shelf',
    ]);

    serviceCreateStock($this->product->id, $this->warehouse->id, 10, null);
    serviceCreateStock($this->product->id, $this->warehouse->id, 20, $location->id);

    $result = $this->service->list(['storage_location_id' => null], 50);

    expect($result)->toHaveCount(1);
    expect($result->first()->storage_location_id)->toBeNull();
});

it('filters stock by specific storage_location_id', function () {
    $location = StorageLocation::create([
        'warehouse_id' => $this->warehouse->id,
        'code' => 'SVC-02',
        'name' => 'Service Test Location 2',
        'type' => 'shelf',
    ]);

    serviceCreateStock($this->product->id, $this->warehouse->id, 10, null);
    serviceCreateStock($this->product->id, $this->warehouse->id, 20, $location->id);

    $result = $this->service->list(['storage_location_id' => $location->id], 50);

    expect($result)->toHaveCount(1);
    expect($result->first()->storage_location_id)->toBe($location->id);
});

it('returns empty collection when no stock exists', function () {
    $result = $this->service->list([], 50);

    expect($result)->toHaveCount(0);
});

it('loads product, warehouse, and storageLocation relationships on list', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 10);

    $result = $this->service->list([], 50);

    expect($result->first()->relationLoaded('product'))->toBeTrue();
    expect($result->first()->relationLoaded('warehouse'))->toBeTrue();
    expect($result->first()->relationLoaded('storageLocation'))->toBeTrue();
});

// ──────────────────────────────────────────────
// ADJUST (delegates to AdjustStockAction)
// ──────────────────────────────────────────────

it('delegates adjust to AdjustStockAction', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 10);

    $result = $this->service->adjust([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'increase',
        'reason' => 'found',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 5,
                'mode' => 'increment',
                'reason' => 'found',
            ],
        ],
    ], $this->user->id);

    expect($result)->toBeInstanceOf(StockAdjustment::class);
    expect($result->type)->toBe('increase');
    expect($result->status)->toBe('pending');

    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect($stock->quantity)->toBe(15);
});

// ──────────────────────────────────────────────
// TRANSFER (delegates to TransferStockAction)
// ──────────────────────────────────────────────

it('delegates transfer to TransferStockAction', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 50);

    $result = $this->service->transfer([
        'source_warehouse_id' => $this->warehouse->id,
        'destination_warehouse_id' => $this->warehouse2->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 20],
        ],
    ], $this->user->id);

    expect($result)->toBeInstanceOf(StockTransfer::class);
    expect($result->status)->toBe('pending');

    $srcStock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect($srcStock->quantity)->toBe(30);

    $dstStock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse2->id)
        ->first();
    expect($dstStock->quantity)->toBe(20);
});

// ──────────────────────────────────────────────
// UPDATE QUANTITY (delegates to UpdateStockQuantityAction)
// ──────────────────────────────────────────────

it('delegates updateQuantity to UpdateStockQuantityAction', function () {
    $stock = serviceCreateStock($this->product->id, $this->warehouse->id, 10);

    $result = $this->service->updateQuantity($stock->id, [
        'quantity' => 42,
        'reason' => 'recount',
    ], $this->user->id);

    expect($result)->toBeInstanceOf(Stock::class);
    expect($result->quantity)->toBe(42);
});

// ──────────────────────────────────────────────
// CONFIRM MOVEMENT
// ──────────────────────────────────────────────

it('delegates confirmMovement to ConfirmMovementAction', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 10);

    $adjustment = serviceCreateAdjustment($this->warehouse->id, $this->user->id);

    $movement = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustment->id)
        ->first();

    $result = $this->service->confirmMovement($movement->id);

    expect($result)->toBeInstanceOf(StockMovement::class);
    expect($result->status)->toBe('completed');
});

// ──────────────────────────────────────────────
// CONFIRM ADJUSTMENT
// ──────────────────────────────────────────────

it('delegates confirmAdjustment to ConfirmMovementAction', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 10);

    $adjustment = serviceCreateAdjustment($this->warehouse->id, $this->user->id);

    $result = $this->service->confirmAdjustment($adjustment->id);

    expect($result)->toBeInstanceOf(StockAdjustment::class);
    expect($result->status)->toBe('completed');

    // Related movements should be completed too
    $pending = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustment->id)
        ->where('status', 'pending')
        ->count();
    expect($pending)->toBe(0);
});

// ──────────────────────────────────────────────
// CONFIRM TRANSFER
// ──────────────────────────────────────────────

it('delegates confirmTransfer to ConfirmMovementAction', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 50);

    $transfer = serviceCreateTransfer(
        $this->warehouse->id,
        $this->warehouse2->id,
        $this->user->id,
        10
    );

    $result = $this->service->confirmTransfer($transfer->id);

    expect($result)->toBeInstanceOf(StockTransfer::class);
    expect($result->status)->toBe('completed');
    expect($result->received_at)->not->toBeNull();

    // Related movements should be completed
    $pending = StockMovement::where('movable_type', StockTransfer::class)
        ->where('movable_id', $transfer->id)
        ->where('status', 'pending')
        ->count();
    expect($pending)->toBe(0);
});

// ──────────────────────────────────────────────
// LIST MOVEMENTS
// ──────────────────────────────────────────────

it('lists movements with pagination', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);
    serviceCreateAdjustment($this->warehouse->id, $this->user->id, 'increase');

    $result = $this->service->listMovements([], 50);

    expect($result->total())->toBeGreaterThanOrEqual(1);
});

it('filters movements by product_id', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);
    serviceCreateStock($this->product2->id, $this->warehouse->id, 100);

    serviceCreateAdjustment($this->warehouse->id, $this->user->id, 'increase');

    // Create a second adjustment for product2
    app(AdjustStockAction::class)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'increase',
        'reason' => 'recount',
        'items' => [
            [
                'product_id' => $this->product2->id,
                'quantity' => 3,
                'mode' => 'increment',
                'reason' => 'recount',
            ],
        ],
    ], $this->user->id);

    $result = $this->service->listMovements(['product_id' => $this->product->id], 50);

    foreach ($result->items() as $movement) {
        expect($movement->product_id)->toBe($this->product->id);
    }
});

it('filters movements by warehouse_id', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);
    serviceCreateStock($this->product->id, $this->warehouse2->id, 100);

    serviceCreateAdjustment($this->warehouse->id, $this->user->id, 'increase');

    // Adjustment on warehouse2
    app(AdjustStockAction::class)([
        'warehouse_id' => $this->warehouse2->id,
        'type' => 'decrease',
        'reason' => 'damaged',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 2,
                'mode' => 'decrement',
                'reason' => 'damaged',
            ],
        ],
    ], $this->user->id);

    $result = $this->service->listMovements(['warehouse_id' => $this->warehouse->id], 50);

    foreach ($result->items() as $movement) {
        expect($movement->warehouse_id)->toBe($this->warehouse->id);
    }
});

it('filters movements by type', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);

    serviceCreateAdjustment($this->warehouse->id, $this->user->id, 'increase');
    serviceCreateTransfer($this->warehouse->id, $this->warehouse2->id, $this->user->id, 5);

    $result = $this->service->listMovements(['type' => 'transfer'], 50);

    foreach ($result->items() as $movement) {
        expect($movement->type)->toBe('transfer');
    }
});

it('lists movements ordered by latest first', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);

    serviceCreateAdjustment($this->warehouse->id, $this->user->id, 'increase');
    serviceCreateAdjustment($this->warehouse->id, $this->user->id, 'decrease');

    $result = $this->service->listMovements([], 50);
    $items = $result->items();

    if (count($items) > 1) {
        // Most recent should come first
        expect($items[0]->created_at->gte($items[count($items) - 1]->created_at))->toBeTrue();
    }
});

it('loads product, warehouse, user on movements', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);
    serviceCreateAdjustment($this->warehouse->id, $this->user->id, 'increase');

    $result = $this->service->listMovements([], 50);
    $movement = $result->items()[0];

    expect($movement->relationLoaded('product'))->toBeTrue();
    expect($movement->relationLoaded('warehouse'))->toBeTrue();
    expect($movement->relationLoaded('user'))->toBeTrue();
});

// ──────────────────────────────────────────────
// LIST ADJUSTMENTS
// ──────────────────────────────────────────────

it('lists adjustments with pagination', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);
    serviceCreateAdjustment($this->warehouse->id, $this->user->id, 'increase');

    $result = $this->service->listAdjustments([], 50);

    expect($result->total())->toBeGreaterThanOrEqual(1);
});

it('filters adjustments by warehouse_id', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);
    serviceCreateStock($this->product->id, $this->warehouse2->id, 100);

    serviceCreateAdjustment($this->warehouse->id, $this->user->id, 'increase');

    app(AdjustStockAction::class)([
        'warehouse_id' => $this->warehouse2->id,
        'type' => 'decrease',
        'reason' => 'damaged',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 2,
                'mode' => 'decrement',
                'reason' => 'damaged',
            ],
        ],
    ], $this->user->id);

    $result = $this->service->listAdjustments(['warehouse_id' => $this->warehouse->id], 50);

    foreach ($result->items() as $adj) {
        expect($adj->warehouse_id)->toBe($this->warehouse->id);
    }
});

it('filters adjustments by type', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);

    serviceCreateAdjustment($this->warehouse->id, $this->user->id, 'increase');
    serviceCreateAdjustment($this->warehouse->id, $this->user->id, 'decrease');

    $result = $this->service->listAdjustments(['type' => 'increase'], 50);

    foreach ($result->items() as $adj) {
        expect($adj->type)->toBe('increase');
    }
});

it('filters adjustments by reason', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);

    // Adjustment with 'recount' reason
    serviceCreateAdjustment($this->warehouse->id, $this->user->id, 'increase');

    // Adjustment with 'damaged' reason
    app(AdjustStockAction::class)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'decrease',
        'reason' => 'damaged',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 2,
                'mode' => 'decrement',
                'reason' => 'damaged',
            ],
        ],
    ], $this->user->id);

    $result = $this->service->listAdjustments(['reason' => 'damaged'], 50);

    foreach ($result->items() as $adj) {
        expect($adj->reason)->toBe('damaged');
    }
});

it('loads warehouse, user, and items.product on adjustments', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);
    serviceCreateAdjustment($this->warehouse->id, $this->user->id, 'increase');

    $result = $this->service->listAdjustments([], 50);
    $adj = $result->items()[0];

    expect($adj->relationLoaded('warehouse'))->toBeTrue();
    expect($adj->relationLoaded('user'))->toBeTrue();
    expect($adj->relationLoaded('items'))->toBeTrue();
    expect($adj->items->first()->relationLoaded('product'))->toBeTrue();
});

it('lists adjustments ordered by latest first', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);

    serviceCreateAdjustment($this->warehouse->id, $this->user->id, 'increase');
    serviceCreateAdjustment($this->warehouse->id, $this->user->id, 'decrease');

    $result = $this->service->listAdjustments([], 50);
    $items = $result->items();

    if (count($items) > 1) {
        expect($items[0]->created_at->gte($items[count($items) - 1]->created_at))->toBeTrue();
    }
});

// ──────────────────────────────────────────────
// INTEGRATION: FULL FLOW VIA SERVICE
// ──────────────────────────────────────────────

// ──────────────────────────────────────────────
// LIST TRANSFERS
// ──────────────────────────────────────────────

it('lists transfers with pagination', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);
    serviceCreateTransfer($this->warehouse->id, $this->warehouse2->id, $this->user->id, 5);
    serviceCreateTransfer($this->warehouse->id, $this->warehouse2->id, $this->user->id, 5);

    $result = $this->service->listTransfers([], 50);

    expect($result->count())->toBe(2);
});

it('filters transfers by from_warehouse_id', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);
    serviceCreateStock($this->product->id, $this->warehouse2->id, 100);
    serviceCreateTransfer($this->warehouse->id, $this->warehouse2->id, $this->user->id, 5);
    serviceCreateTransfer($this->warehouse2->id, $this->warehouse->id, $this->user->id, 5);

    $result = $this->service->listTransfers(['from_warehouse_id' => $this->warehouse->id], 50);

    expect($result->count())->toBe(1);
    expect($result->first()->from_warehouse_id)->toBe($this->warehouse->id);
});

it('filters transfers by to_warehouse_id', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);
    serviceCreateStock($this->product->id, $this->warehouse2->id, 100);
    serviceCreateTransfer($this->warehouse->id, $this->warehouse2->id, $this->user->id, 5);
    serviceCreateTransfer($this->warehouse2->id, $this->warehouse->id, $this->user->id, 5);

    $result = $this->service->listTransfers(['to_warehouse_id' => $this->warehouse2->id], 50);

    expect($result->count())->toBe(1);
    expect($result->first()->to_warehouse_id)->toBe($this->warehouse2->id);
});

it('filters transfers by status', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);
    $t1 = serviceCreateTransfer($this->warehouse->id, $this->warehouse2->id, $this->user->id, 5);
    $t2 = serviceCreateTransfer($this->warehouse->id, $this->warehouse2->id, $this->user->id, 5);

    $this->service->confirmTransfer($t1->id);

    $pending = $this->service->listTransfers(['status' => 'pending'], 50);
    $completed = $this->service->listTransfers(['status' => 'completed'], 50);

    expect($pending->count())->toBe(1);
    expect($completed->count())->toBe(1);
});

it('full flow: create transfer, cancel, verify state', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);

    // 1. Create transfer
    $transfer = $this->service->transfer([
        'source_warehouse_id' => $this->warehouse->id,
        'destination_warehouse_id' => $this->warehouse2->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 20],
        ],
    ], $this->user->id);

    // Stock is already moved (pending confirmation)
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(80);
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse2->id)->first()->quantity)->toBe(20);

    // 2. Cancel transfer
    $cancelled = $this->service->cancelTransfer($transfer->id);
    expect($cancelled->status)->toBe('cancelled');

    // 3. All movements should be cancelled
    $pendingMovements = StockMovement::where('movable_type', StockTransfer::class)
        ->where('movable_id', $transfer->id)
        ->where('status', 'pending')
        ->count();
    expect($pendingMovements)->toBe(0);

    $cancelledMovements = StockMovement::where('movable_type', StockTransfer::class)
        ->where('movable_id', $transfer->id)
        ->where('status', 'cancelled')
        ->count();
    expect($cancelledMovements)->toBe(2);

    // 4. Transfer should appear in filtered list
    $cancelledList = $this->service->listTransfers(['status' => 'cancelled'], 50);
    expect($cancelledList->count())->toBe(1);
    expect($cancelledList->first()->id)->toBe($transfer->id);
});

it('loads relationships on transfers list', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);
    serviceCreateTransfer($this->warehouse->id, $this->warehouse2->id, $this->user->id, 5);

    $result = $this->service->listTransfers([], 50);
    $transfer = $result->first();

    expect($transfer->relationLoaded('sourceWarehouse'))->toBeTrue();
    expect($transfer->relationLoaded('destinationWarehouse'))->toBeTrue();
    expect($transfer->relationLoaded('requestedBy'))->toBeTrue();
    expect($transfer->relationLoaded('items'))->toBeTrue();
    expect($transfer->sourceWarehouse->id)->toBe($this->warehouse->id);
    expect($transfer->destinationWarehouse->id)->toBe($this->warehouse2->id);
});

it('lists transfers ordered by latest first', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);
    $t1 = serviceCreateTransfer($this->warehouse->id, $this->warehouse2->id, $this->user->id, 5);

    // Manually push t1 to the past so ordering is deterministic
    StockTransfer::where('id', $t1->id)->update(['created_at' => now()->subMinute()]);

    $t2 = serviceCreateTransfer($this->warehouse->id, $this->warehouse2->id, $this->user->id, 5);

    $result = $this->service->listTransfers([], 50);

    expect($result->first()->id)->toBe($t2->id);
    expect($result->last()->id)->toBe($t1->id);
});

// ──────────────────────────────────────────────
// CANCEL MOVEMENT
// ──────────────────────────────────────────────

it('cancels a pending movement via service', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 50);
    $adjustment = serviceCreateAdjustment($this->warehouse->id, $this->user->id);

    $movement = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustment->id)
        ->first();

    $result = $this->service->cancelMovement($movement->id);

    expect($result->status)->toBe(StockMovement::STATUS_CANCELLED);
    expect($movement->fresh()->status)->toBe(StockMovement::STATUS_CANCELLED);
});

it('throws when cancelling completed movement via service', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 50);
    $adjustment = serviceCreateAdjustment($this->warehouse->id, $this->user->id);

    $movement = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustment->id)
        ->first();

    $this->service->confirmMovement($movement->id);

    $this->service->cancelMovement($movement->id);
})->throws(\Exception::class, 'Solo se pueden cancelar movimientos pendientes.');

// ──────────────────────────────────────────────
// CANCEL TRANSFER
// ──────────────────────────────────────────────

it('cancels a pending transfer via service', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);
    $transfer = serviceCreateTransfer($this->warehouse->id, $this->warehouse2->id, $this->user->id, 10);

    $result = $this->service->cancelTransfer($transfer->id);

    expect($result->status)->toBe(StockTransfer::STATUS_CANCELLED);
    expect($transfer->fresh()->status)->toBe(StockTransfer::STATUS_CANCELLED);
});

it('cancels all related pending movements when cancelling transfer', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);
    $transfer = serviceCreateTransfer($this->warehouse->id, $this->warehouse2->id, $this->user->id, 10);

    $this->service->cancelTransfer($transfer->id);

    $pendingMovements = StockMovement::where('movable_type', StockTransfer::class)
        ->where('movable_id', $transfer->id)
        ->where('status', StockMovement::STATUS_PENDING)
        ->count();

    $cancelledMovements = StockMovement::where('movable_type', StockTransfer::class)
        ->where('movable_id', $transfer->id)
        ->where('status', StockMovement::STATUS_CANCELLED)
        ->count();

    expect($pendingMovements)->toBe(0);
    expect($cancelledMovements)->toBe(2); // out + in movements
});

it('throws when cancelling completed transfer', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);
    $transfer = serviceCreateTransfer($this->warehouse->id, $this->warehouse2->id, $this->user->id, 10);

    $this->service->confirmTransfer($transfer->id);

    $this->service->cancelTransfer($transfer->id);
})->throws(\Exception::class, 'Solo se pueden cancelar transferencias pendientes.');

it('throws when cancelling already cancelled transfer', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);
    $transfer = serviceCreateTransfer($this->warehouse->id, $this->warehouse2->id, $this->user->id, 10);

    $this->service->cancelTransfer($transfer->id);

    $this->service->cancelTransfer($transfer->id);
})->throws(\Exception::class, 'Solo se pueden cancelar transferencias pendientes.');

it('returns transfer with loaded relationships after cancel', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);
    $transfer = serviceCreateTransfer($this->warehouse->id, $this->warehouse2->id, $this->user->id, 10);

    $result = $this->service->cancelTransfer($transfer->id);

    expect($result->relationLoaded('sourceWarehouse'))->toBeTrue();
    expect($result->relationLoaded('destinationWarehouse'))->toBeTrue();
    expect($result->relationLoaded('items'))->toBeTrue();
});

// ──────────────────────────────────────────────
// FULL FLOW
// ──────────────────────────────────────────────

it('full flow: adjust, transfer, confirm all via service', function () {
    serviceCreateStock($this->product->id, $this->warehouse->id, 100);

    // 1. Adjust (increase by 10)
    $adjustment = $this->service->adjust([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'increase',
        'reason' => 'found',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 10,
                'mode' => 'increment',
                'reason' => 'found',
            ],
        ],
    ], $this->user->id);

    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(110);

    // 2. Transfer 30 units to warehouse2
    $transfer = $this->service->transfer([
        'source_warehouse_id' => $this->warehouse->id,
        'destination_warehouse_id' => $this->warehouse2->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 30],
        ],
    ], $this->user->id);

    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(80);
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse2->id)->first()->quantity)->toBe(30);

    // 3. Confirm adjustment
    $confirmedAdj = $this->service->confirmAdjustment($adjustment->id);
    expect($confirmedAdj->status)->toBe('completed');

    // 4. Confirm transfer
    $confirmedTransfer = $this->service->confirmTransfer($transfer->id);
    expect($confirmedTransfer->status)->toBe('completed');

    // 5. All movements should be completed
    $pendingMovements = StockMovement::where('status', 'pending')->count();
    expect($pendingMovements)->toBe(0);

    // 6. Stock balances should be correct
    $totalStock = Stock::where('product_id', $this->product->id)->sum('quantity');
    expect($totalStock)->toBe(110); // 80 + 30
});