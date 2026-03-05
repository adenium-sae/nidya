<?php

use App\Actions\Stock\TransferStockAction;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\StorageLocation;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = app(TransferStockAction::class);
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create();
    $this->sourceWarehouse = Warehouse::factory()->forStore($this->store)->central()->create();
    $this->destWarehouse = Warehouse::factory()->forStore($this->store)->branch()->create();
    $this->product = Product::factory()->create();
});

function createTransferStock(
    int $quantity,
    ?string $warehouseId = null,
    ?string $locationId = null,
    ?string $productId = null
): Stock {
    return Stock::create([
        'product_id' => $productId ?? test()->product->id,
        'warehouse_id' => $warehouseId ?? test()->sourceWarehouse->id,
        'storage_location_id' => $locationId,
        'quantity' => $quantity,
        'reserved' => 0,
    ]);
}

// ──────────────────────────────────────────────
// BASIC TRANSFER
// ──────────────────────────────────────────────

it('transfers stock between two warehouses', function () {
    createTransferStock(50);

    $result = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 20,
            ],
        ],
    ], $this->user->id);

    // Verify transfer record
    expect($result)->toBeInstanceOf(StockTransfer::class);
    expect($result->from_warehouse_id)->toBe($this->sourceWarehouse->id);
    expect($result->to_warehouse_id)->toBe($this->destWarehouse->id);
    expect($result->requested_by)->toBe($this->user->id);
    expect($result->status)->toBe(StockTransfer::STATUS_PENDING);
    expect($result->folio)->toStartWith('TRF-');
    expect($result->folio)->not->toBeNull();

    // Verify source stock decreased
    $sourceStock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->sourceWarehouse->id)
        ->first();
    expect($sourceStock->quantity)->toBe(30);

    // Verify destination stock created/increased
    $destStock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->destWarehouse->id)
        ->first();
    expect($destStock)->not->toBeNull();
    expect($destStock->quantity)->toBe(20);
});

it('creates transfer item records', function () {
    createTransferStock(50);

    $result = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 15,
            ],
        ],
    ], $this->user->id);

    $transferItems = StockTransferItem::where('stock_transfer_id', $result->id)->get();
    expect($transferItems)->toHaveCount(1);

    $item = $transferItems->first();
    expect($item->product_id)->toBe($this->product->id);
    expect($item->quantity_requested)->toBe(15);
    expect($item->quantity_sent)->toBe(15);
});

it('creates two stock movements per transfer item', function () {
    createTransferStock(50);

    $result = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 10,
            ],
        ],
    ], $this->user->id);

    $movements = StockMovement::where('movable_type', StockTransfer::class)
        ->where('movable_id', $result->id)
        ->get();

    expect($movements)->toHaveCount(2);

    // Out movement (source)
    $outMovement = $movements->where('quantity', -10)->first();
    expect($outMovement)->not->toBeNull();
    expect($outMovement->type)->toBe('transfer');
    expect($outMovement->status)->toBe(StockMovement::STATUS_PENDING);
    expect($outMovement->warehouse_id)->toBe($this->sourceWarehouse->id);
    expect($outMovement->product_id)->toBe($this->product->id);
    expect($outMovement->quantity_before)->toBe(50);
    expect($outMovement->quantity_after)->toBe(40);
    expect($outMovement->user_id)->toBe($this->user->id);

    // In movement (destination)
    $inMovement = $movements->where('quantity', 10)->first();
    expect($inMovement)->not->toBeNull();
    expect($inMovement->type)->toBe('transfer');
    expect($inMovement->status)->toBe(StockMovement::STATUS_PENDING);
    expect($inMovement->warehouse_id)->toBe($this->destWarehouse->id);
    expect($inMovement->product_id)->toBe($this->product->id);
    expect($inMovement->quantity_before)->toBe(0);
    expect($inMovement->quantity_after)->toBe(10);
});

it('links movements via related_movement_id', function () {
    createTransferStock(50);

    $result = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 10,
            ],
        ],
    ], $this->user->id);

    $movements = StockMovement::where('movable_type', StockTransfer::class)
        ->where('movable_id', $result->id)
        ->get();

    $outMovement = $movements->where('quantity', -10)->first();
    $inMovement = $movements->where('quantity', 10)->first();

    // They should reference each other
    expect($outMovement->related_movement_id)->toBe($inMovement->id);
    expect($inMovement->related_movement_id)->toBe($outMovement->id);
});

it('uses morph relationship for movements', function () {
    createTransferStock(30);

    $result = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 5,
            ],
        ],
    ], $this->user->id);

    $movements = StockMovement::where('movable_type', StockTransfer::class)
        ->where('movable_id', $result->id)
        ->get();

    foreach ($movements as $movement) {
        expect($movement->movable_type)->toBe(StockTransfer::class);
        expect($movement->movable_id)->toBe($result->id);
    }
});

// ──────────────────────────────────────────────
// TRANSFER TO WAREHOUSE WITH EXISTING STOCK
// ──────────────────────────────────────────────

it('increments existing stock in destination warehouse', function () {
    createTransferStock(50); // source
    createTransferStock(30, $this->destWarehouse->id); // destination already has stock

    ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 15,
            ],
        ],
    ], $this->user->id);

    $sourceStock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->sourceWarehouse->id)
        ->first();
    $destStock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->destWarehouse->id)
        ->first();

    expect($sourceStock->quantity)->toBe(35);
    expect($destStock->quantity)->toBe(45);
});

it('records correct before/after when destination has existing stock', function () {
    createTransferStock(50); // source
    createTransferStock(30, $this->destWarehouse->id); // dest

    $result = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 10,
            ],
        ],
    ], $this->user->id);

    $movements = StockMovement::where('movable_id', $result->id)->get();
    $inMovement = $movements->where('quantity', 10)->first();

    expect($inMovement->quantity_before)->toBe(30);
    expect($inMovement->quantity_after)->toBe(40);
});

// ──────────────────────────────────────────────
// MULTIPLE PRODUCTS
// ──────────────────────────────────────────────

it('transfers multiple products in one transfer', function () {
    $product2 = Product::factory()->create();

    createTransferStock(40);
    createTransferStock(60, $this->sourceWarehouse->id, null, $product2->id);

    $result = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 10,
            ],
            [
                'product_id' => $product2->id,
                'quantity' => 25,
            ],
        ],
    ], $this->user->id);

    // Verify source stocks
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->sourceWarehouse->id)->first()->quantity)->toBe(30);
    expect(Stock::where('product_id', $product2->id)
        ->where('warehouse_id', $this->sourceWarehouse->id)->first()->quantity)->toBe(35);

    // Verify destination stocks
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->destWarehouse->id)->first()->quantity)->toBe(10);
    expect(Stock::where('product_id', $product2->id)
        ->where('warehouse_id', $this->destWarehouse->id)->first()->quantity)->toBe(25);

    // Verify transfer items
    $transferItems = StockTransferItem::where('stock_transfer_id', $result->id)->get();
    expect($transferItems)->toHaveCount(2);

    // Verify movements (2 per product = 4 total)
    $movements = StockMovement::where('movable_id', $result->id)
        ->where('movable_type', StockTransfer::class)
        ->get();
    expect($movements)->toHaveCount(4);
});

// ──────────────────────────────────────────────
// STORAGE LOCATIONS
// ──────────────────────────────────────────────

it('transfers from specific source location', function () {
    $sourceLocation = StorageLocation::create([
        'warehouse_id' => $this->sourceWarehouse->id,
        'code' => 'SRC-A1',
        'name' => 'Source Shelf A1',
        'type' => 'shelf',
    ]);

    createTransferStock(40, $this->sourceWarehouse->id, $sourceLocation->id);

    $result = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 15,
                'source_location_id' => $sourceLocation->id,
            ],
        ],
    ], $this->user->id);

    $sourceStock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->sourceWarehouse->id)
        ->where('storage_location_id', $sourceLocation->id)
        ->first();

    expect($sourceStock->quantity)->toBe(25);

    // Destination should have null location since no dest location specified
    $destStock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->destWarehouse->id)
        ->whereNull('storage_location_id')
        ->first();
    expect($destStock)->not->toBeNull();
    expect($destStock->quantity)->toBe(15);
});

it('transfers to specific destination location', function () {
    $destLocation = StorageLocation::create([
        'warehouse_id' => $this->destWarehouse->id,
        'code' => 'DST-B1',
        'name' => 'Dest Shelf B1',
        'type' => 'shelf',
    ]);

    createTransferStock(30);

    ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 10,
                'destination_location_id' => $destLocation->id,
            ],
        ],
    ], $this->user->id);

    $destStock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->destWarehouse->id)
        ->where('storage_location_id', $destLocation->id)
        ->first();

    expect($destStock)->not->toBeNull();
    expect($destStock->quantity)->toBe(10);
});

it('transfers between specific locations', function () {
    $sourceLocation = StorageLocation::create([
        'warehouse_id' => $this->sourceWarehouse->id,
        'code' => 'SRC-C1',
        'name' => 'Source Shelf C1',
        'type' => 'shelf',
    ]);
    $destLocation = StorageLocation::create([
        'warehouse_id' => $this->destWarehouse->id,
        'code' => 'DST-D1',
        'name' => 'Dest Shelf D1',
        'type' => 'shelf',
    ]);

    createTransferStock(100, $this->sourceWarehouse->id, $sourceLocation->id);

    ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 40,
                'source_location_id' => $sourceLocation->id,
                'destination_location_id' => $destLocation->id,
            ],
        ],
    ], $this->user->id);

    $sourceStock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->sourceWarehouse->id)
        ->where('storage_location_id', $sourceLocation->id)
        ->first();
    $destStock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->destWarehouse->id)
        ->where('storage_location_id', $destLocation->id)
        ->first();

    expect($sourceStock->quantity)->toBe(60);
    expect($destStock->quantity)->toBe(40);
});

it('does not affect stock at other locations in source', function () {
    $locationA = StorageLocation::create([
        'warehouse_id' => $this->sourceWarehouse->id,
        'code' => 'LOC-A',
        'name' => 'Location A',
        'type' => 'shelf',
    ]);
    $locationB = StorageLocation::create([
        'warehouse_id' => $this->sourceWarehouse->id,
        'code' => 'LOC-B',
        'name' => 'Location B',
        'type' => 'shelf',
    ]);

    // Stock at location A
    createTransferStock(50, $this->sourceWarehouse->id, $locationA->id);
    // Stock at location B
    createTransferStock(30, $this->sourceWarehouse->id, $locationB->id);
    // Stock without location
    createTransferStock(20);

    ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 10,
                'source_location_id' => $locationA->id,
            ],
        ],
    ], $this->user->id);

    // Only location A should be affected
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->sourceWarehouse->id)
        ->where('storage_location_id', $locationA->id)
        ->first()->quantity)->toBe(40);

    // Location B unchanged
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->sourceWarehouse->id)
        ->where('storage_location_id', $locationB->id)
        ->first()->quantity)->toBe(30);

    // No location unchanged
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->sourceWarehouse->id)
        ->whereNull('storage_location_id')
        ->first()->quantity)->toBe(20);
});

// ──────────────────────────────────────────────
// FOLIO GENERATION
// ──────────────────────────────────────────────

it('generates folio with TRF prefix', function () {
    createTransferStock(50);

    $result = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 5],
        ],
    ], $this->user->id);

    $year = now()->year;
    expect($result->folio)->toMatch('/^TRF-' . $year . '-\d{5}$/');
});

it('generates sequential transfer folios', function () {
    createTransferStock(100);

    $result1 = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 1],
        ],
    ], $this->user->id);

    $result2 = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 1],
        ],
    ], $this->user->id);

    $result3 = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 1],
        ],
    ], $this->user->id);

    $year = now()->year;
    expect($result1->folio)->toBe("TRF-{$year}-00001");
    expect($result2->folio)->toBe("TRF-{$year}-00002");
    expect($result3->folio)->toBe("TRF-{$year}-00003");
});

// ──────────────────────────────────────────────
// INSUFFICIENT STOCK
// ──────────────────────────────────────────────

it('throws exception when source stock is insufficient', function () {
    createTransferStock(5);

    ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 20,
            ],
        ],
    ], $this->user->id);
})->throws(\Exception::class, 'Stock insuficiente');

it('throws exception when no stock exists at source', function () {
    // No stock created at all

    ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 1,
            ],
        ],
    ], $this->user->id);
})->throws(\Exception::class, 'Stock insuficiente');

it('throws exception when stock at specific location is insufficient', function () {
    $location = StorageLocation::create([
        'warehouse_id' => $this->sourceWarehouse->id,
        'code' => 'LOC-X',
        'name' => 'Location X',
        'type' => 'shelf',
    ]);

    // Stock exists at location but not enough
    createTransferStock(3, $this->sourceWarehouse->id, $location->id);
    // Stock exists without location but we're requesting from the specific location
    createTransferStock(100);

    ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 10,
                'source_location_id' => $location->id,
            ],
        ],
    ], $this->user->id);
})->throws(\Exception::class, 'Stock insuficiente');

it('throws when transfer exact amount but second item overdraws', function () {
    $product2 = Product::factory()->create();

    createTransferStock(10);
    createTransferStock(5, $this->sourceWarehouse->id, null, $product2->id);

    ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 10, // exact match, OK
            ],
            [
                'product_id' => $product2->id,
                'quantity' => 10, // exceeds available 5
            ],
        ],
    ], $this->user->id);
})->throws(\Exception::class, 'Stock insuficiente');

// ──────────────────────────────────────────────
// TRANSFER EXACT AMOUNT (BOUNDARY)
// ──────────────────────────────────────────────

it('allows transfer of exact available stock', function () {
    createTransferStock(25);

    $result = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 25,
            ],
        ],
    ], $this->user->id);

    $sourceStock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->sourceWarehouse->id)
        ->first();
    $destStock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->destWarehouse->id)
        ->first();

    expect($sourceStock->quantity)->toBe(0);
    expect($destStock->quantity)->toBe(25);
    expect($result)->not->toBeNull();
});

it('transfers a single unit', function () {
    createTransferStock(1);

    ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 1,
            ],
        ],
    ], $this->user->id);

    $sourceStock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->sourceWarehouse->id)
        ->first();
    $destStock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->destWarehouse->id)
        ->first();

    expect($sourceStock->quantity)->toBe(0);
    expect($destStock->quantity)->toBe(1);
});

// ──────────────────────────────────────────────
// TRANSACTION INTEGRITY / ROLLBACK
// ──────────────────────────────────────────────

it('rolls back on failure in multi-item transfer', function () {
    $product2 = Product::factory()->create();

    createTransferStock(50);
    createTransferStock(2, $this->sourceWarehouse->id, null, $product2->id);

    $initialSourceStock1 = 50;
    $initialSourceStock2 = 2;
    $initialTransferCount = StockTransfer::count();
    $initialTransferItemCount = StockTransferItem::count();
    $initialMovementCount = StockMovement::count();

    try {
        ($this->action)([
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'destination_warehouse_id' => $this->destWarehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10, // this would succeed
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 100, // this will fail — insufficient
                ],
            ],
        ], $this->user->id);
    } catch (\Exception $e) {
        // Expected
    }

    // Nothing should have been committed
    expect(StockTransfer::count())->toBe($initialTransferCount);
    expect(StockTransferItem::count())->toBe($initialTransferItemCount);
    expect(StockMovement::count())->toBe($initialMovementCount);

    // Stock should remain unchanged
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->sourceWarehouse->id)->first()->quantity)->toBe($initialSourceStock1);
    expect(Stock::where('product_id', $product2->id)
        ->where('warehouse_id', $this->sourceWarehouse->id)->first()->quantity)->toBe($initialSourceStock2);
});

// ──────────────────────────────────────────────
// NOTES
// ──────────────────────────────────────────────

it('stores notes on transfer', function () {
    createTransferStock(50);

    $result = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'notes' => 'Reposición semanal de la sucursal norte',
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ],
    ], $this->user->id);

    expect($result->notes)->toBe('Reposición semanal de la sucursal norte');
});

it('stores null notes when not provided', function () {
    createTransferStock(50);

    $result = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ],
    ], $this->user->id);

    expect($result->notes)->toBeNull();
});

// ──────────────────────────────────────────────
// MOVEMENT NOTES INCLUDE FOLIO
// ──────────────────────────────────────────────

it('includes folio in movement notes', function () {
    createTransferStock(50);

    $result = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ],
    ], $this->user->id);

    $movements = StockMovement::where('movable_id', $result->id)->get();

    foreach ($movements as $movement) {
        expect($movement->notes)->toContain($result->folio);
    }
});

// ──────────────────────────────────────────────
// EAGER LOADED RELATIONSHIPS
// ──────────────────────────────────────────────

it('returns transfer with loaded relationships', function () {
    createTransferStock(50);

    $result = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 5],
        ],
    ], $this->user->id);

    expect($result->relationLoaded('sourceWarehouse'))->toBeTrue();
    expect($result->relationLoaded('destinationWarehouse'))->toBeTrue();
    expect($result->relationLoaded('items'))->toBeTrue();
});

// ──────────────────────────────────────────────
// LARGE TRANSFER
// ──────────────────────────────────────────────

it('handles large quantity transfer', function () {
    createTransferStock(999999);

    ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 999999],
        ],
    ], $this->user->id);

    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->sourceWarehouse->id)->first()->quantity)->toBe(0);
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->destWarehouse->id)->first()->quantity)->toBe(999999);
});

// ──────────────────────────────────────────────
// STATUS
// ──────────────────────────────────────────────

it('creates transfer with pending status', function () {
    createTransferStock(50);

    $result = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ],
    ], $this->user->id);

    expect($result->status)->toBe(StockTransfer::STATUS_PENDING);
});

it('creates movements with pending status', function () {
    createTransferStock(50);

    $result = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ],
    ], $this->user->id);

    $movements = StockMovement::where('movable_id', $result->id)->get();
    foreach ($movements as $movement) {
        expect($movement->status)->toBe(StockMovement::STATUS_PENDING);
    }
});

// ──────────────────────────────────────────────
// TRANSFER BETWEEN SAME STORE DIFFERENT WAREHOUSES
// ──────────────────────────────────────────────

it('transfers between three warehouses sequentially', function () {
    $warehouseC = Warehouse::factory()->forStore($this->store)->create();
    createTransferStock(100);

    // Transfer A → B
    ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 40],
        ],
    ], $this->user->id);

    // Transfer B → C
    ($this->action)([
        'source_warehouse_id' => $this->destWarehouse->id,
        'destination_warehouse_id' => $warehouseC->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 15],
        ],
    ], $this->user->id);

    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->sourceWarehouse->id)->first()->quantity)->toBe(60);
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->destWarehouse->id)->first()->quantity)->toBe(25);
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $warehouseC->id)->first()->quantity)->toBe(15);

    // Total stock should remain 100
    $totalStock = Stock::where('product_id', $this->product->id)->sum('quantity');
    expect((int) $totalStock)->toBe(100);
});

// ──────────────────────────────────────────────
// STOCK CONSERVATION LAW
// ──────────────────────────────────────────────

it('conserves total stock across all warehouses after transfer', function () {
    createTransferStock(75);
    createTransferStock(25, $this->destWarehouse->id);

    $totalBefore = Stock::where('product_id', $this->product->id)->sum('quantity');

    ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 30],
        ],
    ], $this->user->id);

    $totalAfter = Stock::where('product_id', $this->product->id)->sum('quantity');

    expect((int) $totalAfter)->toBe((int) $totalBefore);
    expect((int) $totalAfter)->toBe(100);
});

it('conserves total stock with multiple products', function () {
    $product2 = Product::factory()->create();

    createTransferStock(50);
    createTransferStock(80, $this->sourceWarehouse->id, null, $product2->id);

    $total1Before = (int) Stock::where('product_id', $this->product->id)->sum('quantity');
    $total2Before = (int) Stock::where('product_id', $product2->id)->sum('quantity');

    ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 20],
            ['product_id' => $product2->id, 'quantity' => 35],
        ],
    ], $this->user->id);

    expect((int) Stock::where('product_id', $this->product->id)->sum('quantity'))->toBe($total1Before);
    expect((int) Stock::where('product_id', $product2->id)->sum('quantity'))->toBe($total2Before);
});

// ──────────────────────────────────────────────
// NULL LOCATION HANDLING (no mixing with located stock)
// ──────────────────────────────────────────────

it('uses null location stock when no source location specified', function () {
    $location = StorageLocation::create([
        'warehouse_id' => $this->sourceWarehouse->id,
        'code' => 'LOCATED',
        'name' => 'Located Shelf',
        'type' => 'shelf',
    ]);

    // Stock at specific location
    createTransferStock(100, $this->sourceWarehouse->id, $location->id);
    // Stock without location
    createTransferStock(10);

    ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 5,
                // no source_location_id → should use null-location stock
            ],
        ],
    ], $this->user->id);

    // Located stock should be untouched
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->sourceWarehouse->id)
        ->where('storage_location_id', $location->id)
        ->first()->quantity)->toBe(100);

    // Null-location stock should be decremented
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->sourceWarehouse->id)
        ->whereNull('storage_location_id')
        ->first()->quantity)->toBe(5);
});

// ──────────────────────────────────────────────
// DB COLUMN MAPPING SMOKE TEST
// ──────────────────────────────────────────────

it('saves to correct database columns', function () {
    createTransferStock(50);

    $result = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'notes' => 'Column mapping test',
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 5],
        ],
    ], $this->user->id);

    // Verify the actual DB columns are used (not the old ones)
    $this->assertDatabaseHas('stock_transfers', [
        'id' => $result->id,
        'from_warehouse_id' => $this->sourceWarehouse->id,
        'to_warehouse_id' => $this->destWarehouse->id,
        'requested_by' => $this->user->id,
        'status' => 'pending',
        'notes' => 'Column mapping test',
    ]);

    // Ensure old column names are not used
    $this->assertDatabaseMissing('stock_transfers', [
        'source_warehouse_id' => $this->sourceWarehouse->id,
    ]);
});

it('uses movable morph not reference columns', function () {
    createTransferStock(50);

    $result = ($this->action)([
        'source_warehouse_id' => $this->sourceWarehouse->id,
        'destination_warehouse_id' => $this->destWarehouse->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 5],
        ],
    ], $this->user->id);

    $movements = StockMovement::where('movable_id', $result->id)->get();
    expect($movements->count())->toBeGreaterThan(0);

    foreach ($movements as $movement) {
        expect($movement->movable_type)->toBe(StockTransfer::class);
        expect($movement->movable_id)->toBe($result->id);
    }
});