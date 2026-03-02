<?php

use App\Actions\Stock\AdjustStockAction;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = app(AdjustStockAction::class);
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create();
    $this->warehouse = Warehouse::factory()->forStore($this->store)->central()->create();
    $this->product = Product::factory()->create();
});

function createStock(int $quantity, ?string $locationId = null): Stock
{
    return Stock::create([
        'product_id' => test()->product->id,
        'warehouse_id' => test()->warehouse->id,
        'storage_location_id' => $locationId,
        'quantity' => $quantity,
        'reserved' => 0,
    ]);
}

// ──────────────────────────────────────────────
// ENTRY (increase / increment)
// ──────────────────────────────────────────────

it('creates an increase adjustment with increment mode', function () {
    createStock(10);

    $result = ($this->action)([
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

    // Verify the adjustment record
    expect($result)->toBeInstanceOf(StockAdjustment::class);
    expect($result->type)->toBe('increase');
    expect($result->status)->toBe('pending');
    expect($result->warehouse_id)->toBe($this->warehouse->id);
    expect($result->user_id)->toBe($this->user->id);
    expect($result->folio)->toStartWith('ADJ-');

    // Verify stock was updated
    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect($stock->quantity)->toBe(15);

    // Verify adjustment item was created
    $item = StockAdjustmentItem::where('stock_adjustment_id', $result->id)->first();
    expect($item)->not->toBeNull();
    expect($item->product_id)->toBe($this->product->id);
    expect($item->quantity_before)->toBe(10);
    expect($item->quantity_after)->toBe(15);

    // Verify stock movement was created with correct type
    $movement = StockMovement::where('movable_id', $result->id)
        ->where('movable_type', StockAdjustment::class)
        ->first();
    expect($movement)->not->toBeNull();
    expect($movement->type)->toBe('entry');
    expect($movement->status)->toBe('pending');
    expect($movement->quantity)->toBe(5);
    expect($movement->quantity_before)->toBe(10);
    expect($movement->quantity_after)->toBe(15);
});

it('creates stock record when none exists on increment', function () {
    // No stock exists yet for this product in this warehouse
    $result = ($this->action)([
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

    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();

    expect($stock)->not->toBeNull();
    expect($stock->quantity)->toBe(20);
    expect($stock->reserved)->toBe(0);
});

it('increments zero stock', function () {
    createStock(0);

    ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'increase',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 7,
                'mode' => 'increment',
                'reason' => 'found',
            ],
        ],
    ], $this->user->id);

    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect($stock->quantity)->toBe(7);
});

// ──────────────────────────────────────────────
// EXIT (decrease / decrement)
// ──────────────────────────────────────────────

it('creates a decrease adjustment with decrement mode', function () {
    createStock(20);

    $result = ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'decrease',
        'reason' => 'damaged',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 5,
                'mode' => 'decrement',
                'reason' => 'damaged',
            ],
        ],
    ], $this->user->id);

    expect($result->type)->toBe('decrease');
    expect($result->status)->toBe('pending');

    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect($stock->quantity)->toBe(15);

    $movement = StockMovement::where('movable_id', $result->id)->first();
    expect($movement->type)->toBe('exit');
    expect($movement->quantity)->toBe(-5);
    expect($movement->quantity_before)->toBe(20);
    expect($movement->quantity_after)->toBe(15);
});

it('caps decrement at zero when exceeding available stock', function () {
    createStock(3);

    $result = ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'decrease',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 10,
                'mode' => 'decrement',
                'reason' => 'lost',
            ],
        ],
    ], $this->user->id);

    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect($stock->quantity)->toBe(0);

    $item = StockAdjustmentItem::where('stock_adjustment_id', $result->id)->first();
    expect($item->quantity_before)->toBe(3);
    expect($item->quantity_after)->toBe(0);
});

it('decrements from zero stock stays at zero', function () {
    createStock(0);

    ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'decrease',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 5,
                'mode' => 'decrement',
                'reason' => 'lost',
            ],
        ],
    ], $this->user->id);

    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect($stock->quantity)->toBe(0);
});

// ──────────────────────────────────────────────
// RECOUNT (absolute)
// ──────────────────────────────────────────────

it('creates a recount adjustment with absolute mode', function () {
    createStock(10);

    $result = ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'recount',
        'reason' => 'recount',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 25,
                'mode' => 'absolute',
                'reason' => 'recount',
            ],
        ],
    ], $this->user->id);

    expect($result->type)->toBe('recount');

    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect($stock->quantity)->toBe(25);

    $movement = StockMovement::where('movable_id', $result->id)->first();
    expect($movement->type)->toBe('adjustment');
    expect($movement->quantity)->toBe(15); // difference: 25 - 10
    expect($movement->quantity_before)->toBe(10);
    expect($movement->quantity_after)->toBe(25);
});

it('handles recount to lower value', function () {
    createStock(50);

    $result = ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'recount',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 12,
                'mode' => 'absolute',
                'reason' => 'recount',
            ],
        ],
    ], $this->user->id);

    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect($stock->quantity)->toBe(12);

    $item = StockAdjustmentItem::where('stock_adjustment_id', $result->id)->first();
    expect($item->quantity_before)->toBe(50);
    expect($item->quantity_after)->toBe(12);
});

it('handles recount to zero', function () {
    createStock(15);

    ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'recount',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 0,
                'mode' => 'absolute',
                'reason' => 'recount',
            ],
        ],
    ], $this->user->id);

    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect($stock->quantity)->toBe(0);
});

it('handles recount same quantity no change', function () {
    createStock(10);

    $result = ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'recount',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 10,
                'mode' => 'absolute',
                'reason' => 'recount',
            ],
        ],
    ], $this->user->id);

    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect($stock->quantity)->toBe(10);

    $movement = StockMovement::where('movable_id', $result->id)->first();
    expect($movement->quantity)->toBe(0); // no change
});

// ──────────────────────────────────────────────
// FRONTEND 'adjustment' TYPE NORMALIZATION
// ──────────────────────────────────────────────

it('normalizes frontend adjustment type to recount', function () {
    createStock(10);

    $result = ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'adjustment',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 30,
                'mode' => 'absolute',
                'reason' => 'correction',
            ],
        ],
    ], $this->user->id);

    // 'adjustment' should be normalized to 'recount' in the DB
    expect($result->type)->toBe('recount');

    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect($stock->quantity)->toBe(30);
});

// ──────────────────────────────────────────────
// MULTIPLE ITEMS
// ──────────────────────────────────────────────

it('adjusts multiple products in one adjustment', function () {
    $product2 = Product::factory()->create();

    createStock(10);
    Stock::create([
        'product_id' => $product2->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 20,
        'reserved' => 0,
    ]);

    $result = ($this->action)([
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

    $stock1 = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    $stock2 = Stock::where('product_id', $product2->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();

    expect($stock1->quantity)->toBe(15);
    expect($stock2->quantity)->toBe(30);

    $items = StockAdjustmentItem::where('stock_adjustment_id', $result->id)->get();
    expect($items)->toHaveCount(2);

    $movements = StockMovement::where('movable_id', $result->id)
        ->where('movable_type', StockAdjustment::class)
        ->get();
    expect($movements)->toHaveCount(2);
});

it('handles mixed modes across items', function () {
    $product2 = Product::factory()->create();

    createStock(10);
    Stock::create([
        'product_id' => $product2->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 20,
        'reserved' => 0,
    ]);

    $result = ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'recount',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 5,
                'mode' => 'increment',
                'reason' => 'found',
            ],
            [
                'product_id' => $product2->id,
                'quantity' => 3,
                'mode' => 'decrement',
                'reason' => 'damaged',
            ],
        ],
    ], $this->user->id);

    $stock1 = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    $stock2 = Stock::where('product_id', $product2->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();

    expect($stock1->quantity)->toBe(15);
    expect($stock2->quantity)->toBe(17);

    // Verify the movement types are correct per-item based on mode
    $movements = StockMovement::where('movable_id', $result->id)
        ->where('movable_type', StockAdjustment::class)
        ->orderBy('quantity', 'desc')
        ->get();

    $entryMovement = $movements->firstWhere('quantity', 5);
    $exitMovement = $movements->firstWhere('quantity', -3);

    expect($entryMovement->type)->toBe('entry');
    expect($exitMovement->type)->toBe('exit');
});

// ──────────────────────────────────────────────
// STORAGE LOCATIONS
// ──────────────────────────────────────────────

it('adjusts stock at specific storage location', function () {
    $location = StorageLocation::create([
        'warehouse_id' => $this->warehouse->id,
        'code' => 'A-01',
        'name' => 'Estante A-01',
        'type' => 'shelf',
    ]);

    Stock::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'storage_location_id' => $location->id,
        'quantity' => 10,
        'reserved' => 0,
    ]);

    ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'storage_location_id' => $location->id,
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

    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->where('storage_location_id', $location->id)
        ->first();
    expect($stock->quantity)->toBe(15);
});

it('does not mix up stock with and without location', function () {
    $location = StorageLocation::create([
        'warehouse_id' => $this->warehouse->id,
        'code' => 'B-01',
        'name' => 'Estante B-01',
        'type' => 'shelf',
    ]);

    // Stock without location
    Stock::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'storage_location_id' => null,
        'quantity' => 100,
        'reserved' => 0,
    ]);

    // Stock with location
    Stock::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'storage_location_id' => $location->id,
        'quantity' => 50,
        'reserved' => 0,
    ]);

    // Adjust only the stock without location
    ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'storage_location_id' => null,
        'type' => 'recount',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 80,
                'mode' => 'absolute',
                'reason' => 'recount',
            ],
        ],
    ], $this->user->id);

    $stockNoLocation = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->whereNull('storage_location_id')
        ->first();
    $stockWithLocation = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->where('storage_location_id', $location->id)
        ->first();

    expect($stockNoLocation->quantity)->toBe(80);
    expect($stockWithLocation->quantity)->toBe(50); // unchanged
});

it('creates stock at location when none exists', function () {
    $location = StorageLocation::create([
        'warehouse_id' => $this->warehouse->id,
        'code' => 'C-01',
        'name' => 'Estante C-01',
        'type' => 'shelf',
    ]);

    ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'storage_location_id' => $location->id,
        'type' => 'increase',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 15,
                'mode' => 'increment',
                'reason' => 'found',
            ],
        ],
    ], $this->user->id);

    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->where('storage_location_id', $location->id)
        ->first();

    expect($stock)->not->toBeNull();
    expect($stock->quantity)->toBe(15);
});

// ──────────────────────────────────────────────
// FOLIO GENERATION
// ──────────────────────────────────────────────

it('generates sequential folios', function () {
    createStock(10);

    $result1 = ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'increase',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 1,
                'mode' => 'increment',
                'reason' => 'found',
            ],
        ],
    ], $this->user->id);

    $result2 = ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'increase',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 1,
                'mode' => 'increment',
                'reason' => 'found',
            ],
        ],
    ], $this->user->id);

    $year = now()->year;
    expect($result1->folio)->toBe("ADJ-{$year}-00001");
    expect($result2->folio)->toBe("ADJ-{$year}-00002");
});

// ──────────────────────────────────────────────
// VALIDATION
// ──────────────────────────────────────────────

it('throws exception when warehouse_id is missing', function () {
    ($this->action)([
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
})->throws(\InvalidArgumentException::class);

it('throws exception when items are empty', function () {
    ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'increase',
        'items' => [],
    ], $this->user->id);
})->throws(\InvalidArgumentException::class);

it('throws exception when product_id is missing in item', function () {
    ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'increase',
        'items' => [
            [
                'quantity' => 5,
                'mode' => 'increment',
                'reason' => 'found',
            ],
        ],
    ], $this->user->id);
})->throws(\InvalidArgumentException::class);

it('throws exception when quantity is missing in item', function () {
    ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'increase',
        'items' => [
            [
                'product_id' => $this->product->id,
                'mode' => 'increment',
                'reason' => 'found',
            ],
        ],
    ], $this->user->id);
})->throws(\InvalidArgumentException::class);

// ──────────────────────────────────────────────
// NOTES AND REASON
// ──────────────────────────────────────────────

it('stores notes on adjustment', function () {
    createStock(10);

    $result = ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'increase',
        'notes' => 'Mercancía encontrada en bodega trasera',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 5,
                'mode' => 'increment',
                'reason' => 'found',
            ],
        ],
    ], $this->user->id);

    expect($result->notes)->toBe('Mercancía encontrada en bodega trasera');
});

it('uses item reason when top level reason is missing', function () {
    createStock(10);

    $result = ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'decrease',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 3,
                'mode' => 'decrement',
                'reason' => 'expired',
            ],
        ],
    ], $this->user->id);

    expect($result->reason)->toBe('expired');
});

it('uses top level reason when provided', function () {
    createStock(10);

    $result = ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'decrease',
        'reason' => 'damaged',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 3,
                'mode' => 'decrement',
                'reason' => 'expired',
            ],
        ],
    ], $this->user->id);

    expect($result->reason)->toBe('damaged');
});

// ──────────────────────────────────────────────
// MOVEMENT TYPE MAPPING
// ──────────────────────────────────────────────

it('increment mode produces entry movement type', function () {
    createStock(10);

    $result = ($this->action)([
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

    $movement = StockMovement::where('movable_id', $result->id)->first();
    expect($movement->type)->toBe('entry');
});

it('decrement mode produces exit movement type', function () {
    createStock(10);

    $result = ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'decrease',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 3,
                'mode' => 'decrement',
                'reason' => 'lost',
            ],
        ],
    ], $this->user->id);

    $movement = StockMovement::where('movable_id', $result->id)->first();
    expect($movement->type)->toBe('exit');
});

it('absolute mode with recount type produces adjustment movement type', function () {
    createStock(10);

    $result = ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'recount',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 25,
                'mode' => 'absolute',
                'reason' => 'recount',
            ],
        ],
    ], $this->user->id);

    $movement = StockMovement::where('movable_id', $result->id)->first();
    expect($movement->type)->toBe('adjustment');
});

// ──────────────────────────────────────────────
// TRANSACTION INTEGRITY
// ──────────────────────────────────────────────

it('rolls back everything on failure', function () {
    createStock(10);

    $initialAdjustmentCount = StockAdjustment::count();
    $initialItemCount = StockAdjustmentItem::count();
    $initialMovementCount = StockMovement::count();

    try {
        ($this->action)([
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
                    // This will cause an error since product_id doesn't exist
                    'product_id' => '00000000-0000-0000-0000-000000000000',
                    'quantity' => 3,
                    'mode' => 'increment',
                    'reason' => 'found',
                ],
            ],
        ], $this->user->id);
    } catch (\Throwable $e) {
        // Expected to fail
    }

    // Nothing should have been committed
    expect(StockAdjustment::count())->toBe($initialAdjustmentCount);
    expect(StockAdjustmentItem::count())->toBe($initialItemCount);
    expect(StockMovement::count())->toBe($initialMovementCount);

    // Stock should remain unchanged
    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect($stock->quantity)->toBe(10);
});

// ──────────────────────────────────────────────
// EMPTY STRING STORAGE LOCATION NORMALIZATION
// ──────────────────────────────────────────────

it('normalizes empty string storage location to null', function () {
    createStock(10);

    $result = ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'storage_location_id' => '',
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

    // Should have found the stock with null location and incremented it
    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->whereNull('storage_location_id')
        ->first();
    expect($stock->quantity)->toBe(15);
});

// ──────────────────────────────────────────────
// LARGE QUANTITIES
// ──────────────────────────────────────────────

it('handles large increment quantities', function () {
    createStock(0);

    ($this->action)([
        'warehouse_id' => $this->warehouse->id,
        'type' => 'increase',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 999999,
                'mode' => 'increment',
                'reason' => 'found',
            ],
        ],
    ], $this->user->id);

    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect($stock->quantity)->toBe(999999);
});

// ──────────────────────────────────────────────
// EAGERLY LOADED RELATIONSHIPS
// ──────────────────────────────────────────────

it('returns adjustment with loaded relationships', function () {
    createStock(10);

    $result = ($this->action)([
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

    expect($result->relationLoaded('items'))->toBeTrue();
    expect($result->relationLoaded('warehouse'))->toBeTrue();
    expect($result->relationLoaded('user'))->toBeTrue();
    expect($result->items->first()->product)->not->toBeNull();
});