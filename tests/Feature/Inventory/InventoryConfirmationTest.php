<?php

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\StockAdjustmentItem;
use App\Models\StorageLocation;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create();
    $this->warehouse = Warehouse::factory()->forStore($this->store)->central()->create();
    $this->warehouse2 = Warehouse::factory()->forStore($this->store)->branch()->create();
    $this->product = Product::factory()->create();
});

function featureCreateStock(int $quantity, ?string $warehouseId = null, ?string $locationId = null): Stock
{
    return Stock::create([
        'product_id' => test()->product->id,
        'warehouse_id' => $warehouseId ?? test()->warehouse->id,
        'storage_location_id' => $locationId,
        'quantity' => $quantity,
        'reserved' => 0,
    ]);
}

// ──────────────────────────────────────────────────────
// ADJUST ENDPOINT — ENTRY (increase / increment)
// ──────────────────────────────────────────────────────

it('creates an increase adjustment via api', function () {
    featureCreateStock(10);

    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
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
        ]);

    $response->assertStatus(200);
    $response->assertJsonPath('status', true);

    // Stock should increase from 10 to 15
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(15);

    // Adjustment should be pending
    $this->assertDatabaseHas('stock_adjustments', [
        'warehouse_id' => $this->warehouse->id,
        'type' => 'increase',
        'status' => 'pending',
        'reason' => 'found',
    ]);

    // Movement should exist with correct type
    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'type' => 'entry',
        'status' => 'pending',
        'quantity' => 5,
        'quantity_before' => 10,
        'quantity_after' => 15,
    ]);
});

it('creates stock when none exists on entry', function () {
    // No stock exists for this product/warehouse combo
    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
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
        ]);

    $response->assertStatus(200);

    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();

    expect($stock)->not->toBeNull();
    expect($stock->quantity)->toBe(20);
    expect($stock->reserved)->toBe(0);
});

// ──────────────────────────────────────────────────────
// ADJUST ENDPOINT — EXIT (decrease / decrement)
// ──────────────────────────────────────────────────────

it('creates a decrease adjustment via api', function () {
    featureCreateStock(30);

    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'decrease',
            'reason' => 'damaged',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 8,
                    'mode' => 'decrement',
                    'reason' => 'damaged',
                ],
            ],
        ]);

    $response->assertStatus(200);

    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(22);

    $this->assertDatabaseHas('stock_adjustments', [
        'type' => 'decrease',
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('stock_movements', [
        'type' => 'exit',
        'status' => 'pending',
        'quantity' => -8,
        'quantity_before' => 30,
        'quantity_after' => 22,
    ]);
});

it('caps decrement at zero via api', function () {
    featureCreateStock(3);

    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'decrease',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 100,
                    'mode' => 'decrement',
                    'reason' => 'lost',
                ],
            ],
        ]);

    $response->assertStatus(200);

    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(0);
});

// ──────────────────────────────────────────────────────
// ADJUST ENDPOINT — RECOUNT (absolute)
// ──────────────────────────────────────────────────────

it('creates a recount adjustment via api', function () {
    featureCreateStock(10);

    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'recount',
            'reason' => 'recount',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 42,
                    'mode' => 'absolute',
                    'reason' => 'recount',
                ],
            ],
        ]);

    $response->assertStatus(200);

    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(42);

    $this->assertDatabaseHas('stock_adjustments', [
        'type' => 'recount',
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('stock_movements', [
        'type' => 'adjustment',
        'quantity' => 32,
        'quantity_before' => 10,
        'quantity_after' => 42,
    ]);
});

it('normalizes frontend adjustment type to recount via api', function () {
    featureCreateStock(10);

    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'adjustment',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 25,
                    'mode' => 'absolute',
                    'reason' => 'correction',
                ],
            ],
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('stock_adjustments', [
        'type' => 'recount',
    ]);

    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(25);
});

// ──────────────────────────────────────────────────────
// ADJUST ENDPOINT — MULTIPLE ITEMS
// ──────────────────────────────────────────────────────

it('adjusts multiple products in one request', function () {
    $product2 = Product::factory()->create();

    featureCreateStock(10);
    Stock::create([
        'product_id' => $product2->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 20,
        'reserved' => 0,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
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
        ]);

    $response->assertStatus(200);

    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(15);
    expect(Stock::where('product_id', $product2->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(30);

    $adjustmentId = $response->json('data.id');
    expect(StockAdjustmentItem::where('stock_adjustment_id', $adjustmentId)->get())->toHaveCount(2);
    expect(StockMovement::where('movable_id', $adjustmentId)
        ->where('movable_type', StockAdjustment::class)->get())->toHaveCount(2);
});

// ──────────────────────────────────────────────────────
// ADJUST ENDPOINT — STORAGE LOCATIONS
// ──────────────────────────────────────────────────────

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

    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'storage_location_id' => $location->id,
            'type' => 'increase',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 7,
                    'mode' => 'increment',
                    'reason' => 'found',
                ],
            ],
        ]);

    $response->assertStatus(200);

    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->where('storage_location_id', $location->id)
        ->first();

    expect($stock->quantity)->toBe(17);
});

it('does not mix stock with and without location', function () {
    $location = StorageLocation::create([
        'warehouse_id' => $this->warehouse->id,
        'code' => 'B-01',
        'name' => 'Estante B-01',
        'type' => 'shelf',
    ]);

    // Stock without location
    featureCreateStock(100);

    // Stock with location
    Stock::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'storage_location_id' => $location->id,
        'quantity' => 50,
        'reserved' => 0,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            // No storage_location_id — should affect null-location stock only
            'type' => 'recount',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 80,
                    'mode' => 'absolute',
                    'reason' => 'recount',
                ],
            ],
        ]);

    $response->assertStatus(200);

    $stockNoLoc = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->whereNull('storage_location_id')
        ->first();
    $stockWithLoc = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->where('storage_location_id', $location->id)
        ->first();

    expect($stockNoLoc->quantity)->toBe(80);
    expect($stockWithLoc->quantity)->toBe(50);
});

// ──────────────────────────────────────────────────────
// ADJUST ENDPOINT — VALIDATION
// ──────────────────────────────────────────────────────

it('validates required warehouse_id', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'type' => 'increase',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'mode' => 'increment',
                    'reason' => 'found',
                ],
            ],
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('warehouse_id');
});

it('validates required type', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'mode' => 'increment',
                    'reason' => 'found',
                ],
            ],
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('type');
});

it('validates invalid type', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'invalid_type',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'mode' => 'increment',
                    'reason' => 'found',
                ],
            ],
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('type');
});

it('validates items are required', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'increase',
            'items' => [],
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('items');
});

it('validates item product_id exists', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'increase',
            'items' => [
                [
                    'product_id' => '00000000-0000-0000-0000-000000000000',
                    'quantity' => 5,
                    'mode' => 'increment',
                    'reason' => 'found',
                ],
            ],
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('items.0.product_id');
});

it('validates item mode is valid', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'increase',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'mode' => 'invalid_mode',
                    'reason' => 'found',
                ],
            ],
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('items.0.mode');
});

it('validates warehouse_id exists in db', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => '00000000-0000-0000-0000-000000000000',
            'type' => 'increase',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'mode' => 'increment',
                    'reason' => 'found',
                ],
            ],
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('warehouse_id');
});

it('validates quantity is numeric', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'increase',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 'not-a-number',
                    'mode' => 'increment',
                    'reason' => 'found',
                ],
            ],
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('items.0.quantity');
});

it('requires authentication for adjust', function () {
    $response = $this->postJson('/api/admin/inventory/stock/adjust', [
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
    ]);

    $response->assertStatus(401);
});

// ──────────────────────────────────────────────────────
// ADJUST ENDPOINT — FOLIO GENERATION
// ──────────────────────────────────────────────────────

it('generates sequential folios', function () {
    featureCreateStock(100);

    $response1 = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'increase',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'mode' => 'increment', 'reason' => 'found'],
            ],
        ]);

    $response2 = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'increase',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'mode' => 'increment', 'reason' => 'found'],
            ],
        ]);

    $year = now()->year;
    $folio1 = $response1->json('data.folio');
    $folio2 = $response2->json('data.folio');

    expect($folio1)->toBe("ADJ-{$year}-00001");
    expect($folio2)->toBe("ADJ-{$year}-00002");
});

// ──────────────────────────────────────────────────────
// TRANSFER ENDPOINT
// ──────────────────────────────────────────────────────

it('creates a transfer between warehouses', function () {
    featureCreateStock(50);

    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 20,
                ],
            ],
        ]);

    $response->assertStatus(200);
    $response->assertJsonPath('status', true);

    // Verify source stock decreased
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(30);

    // Verify destination stock created
    $destStock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse2->id)->first();
    expect($destStock)->not->toBeNull();
    expect($destStock->quantity)->toBe(20);

    // Verify transfer record with correct column names
    $this->assertDatabaseHas('stock_transfers', [
        'from_warehouse_id' => $this->warehouse->id,
        'to_warehouse_id' => $this->warehouse2->id,
        'requested_by' => $this->user->id,
        'status' => 'pending',
    ]);

    // Verify transfer item
    $transferId = $response->json('data.id');
    $this->assertDatabaseHas('stock_transfer_items', [
        'stock_transfer_id' => $transferId,
        'product_id' => $this->product->id,
        'quantity_requested' => 20,
        'quantity_sent' => 20,
    ]);

    // Verify movements use morph columns, not reference columns
    $movements = StockMovement::where('movable_type', StockTransfer::class)
        ->where('movable_id', $transferId)
        ->get();
    expect($movements)->toHaveCount(2);

    // Verify movement types use valid enum 'transfer'
    foreach ($movements as $movement) {
        expect($movement->type)->toBe('transfer');
        expect($movement->status)->toBe('pending');
    }
});

it('creates transfer with folio', function () {
    featureCreateStock(50);

    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 5],
            ],
        ]);

    $response->assertStatus(200);

    $folio = $response->json('data.folio');
    expect($folio)->not->toBeNull();
    $year = now()->year;
    expect($folio)->toMatch('/^TRF-' . $year . '-\d{5}$/');
});

it('conserves total stock on transfer', function () {
    featureCreateStock(75);
    featureCreateStock(25, $this->warehouse2->id);

    $totalBefore = Stock::where('product_id', $this->product->id)->sum('quantity');

    $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 30],
            ],
        ]);

    $totalAfter = Stock::where('product_id', $this->product->id)->sum('quantity');
    expect((int) $totalAfter)->toBe((int) $totalBefore);
});

it('transfers multiple products', function () {
    $product2 = Product::factory()->create();

    featureCreateStock(40);
    Stock::create([
        'product_id' => $product2->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 60,
        'reserved' => 0,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10],
                ['product_id' => $product2->id, 'quantity' => 25],
            ],
        ]);

    $response->assertStatus(200);

    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(30);
    expect(Stock::where('product_id', $product2->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(35);
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse2->id)->first()->quantity)->toBe(10);
    expect(Stock::where('product_id', $product2->id)
        ->where('warehouse_id', $this->warehouse2->id)->first()->quantity)->toBe(25);
});

it('transfers with storage locations', function () {
    $sourceLocation = StorageLocation::create([
        'warehouse_id' => $this->warehouse->id,
        'code' => 'SRC-A',
        'name' => 'Source Shelf A',
        'type' => 'shelf',
    ]);
    $destLocation = StorageLocation::create([
        'warehouse_id' => $this->warehouse2->id,
        'code' => 'DST-B',
        'name' => 'Dest Shelf B',
        'type' => 'shelf',
    ]);

    Stock::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'storage_location_id' => $sourceLocation->id,
        'quantity' => 50,
        'reserved' => 0,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 15,
                    'source_location_id' => $sourceLocation->id,
                    'destination_location_id' => $destLocation->id,
                ],
            ],
        ]);

    $response->assertStatus(200);

    $sourceStock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->where('storage_location_id', $sourceLocation->id)
        ->first();
    expect($sourceStock->quantity)->toBe(35);

    $destStock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse2->id)
        ->where('storage_location_id', $destLocation->id)
        ->first();
    expect($destStock)->not->toBeNull();
    expect($destStock->quantity)->toBe(15);
});

it('stores notes on transfer', function () {
    featureCreateStock(50);

    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'notes' => 'Reposición semanal sucursal norte',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10],
            ],
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('stock_transfers', [
        'notes' => 'Reposición semanal sucursal norte',
    ]);
});

// ──────────────────────────────────────────────────────
// TRANSFER ENDPOINT — VALIDATION
// ──────────────────────────────────────────────────────

it('validates source and destination are different', function () {
    featureCreateStock(50);

    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 5],
            ],
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('source_warehouse_id');
});

it('validates required fields on transfer', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['source_warehouse_id', 'destination_warehouse_id', 'items']);
});

it('validates transfer item quantity min', function () {
    featureCreateStock(50);

    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 0],
            ],
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('items.0.quantity');
});

it('rejects transfer with insufficient stock', function () {
    featureCreateStock(5);

    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 100],
            ],
        ]);

    $response->assertStatus(422);
    $response->assertJsonPath('status', false);
});

it('requires authentication for transfer', function () {
    $response = $this->postJson('/api/admin/inventory/stock/transfer', [
        'source_warehouse_id' => $this->warehouse->id,
        'destination_warehouse_id' => $this->warehouse2->id,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 5],
        ],
    ]);

    $response->assertStatus(401);
});

// ──────────────────────────────────────────────────────
// CONFIRM ADJUSTMENT ENDPOINT
// ──────────────────────────────────────────────────────

it('confirms a pending adjustment', function () {
    featureCreateStock(10);

    $adjustResponse = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'increase',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'mode' => 'increment', 'reason' => 'found'],
            ],
        ]);

    $adjustmentId = $adjustResponse->json('data.id');

    $response = $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/adjustments/{$adjustmentId}/confirm");

    $response->assertStatus(200);
    $response->assertJsonPath('status', true);

    $this->assertDatabaseHas('stock_adjustments', [
        'id' => $adjustmentId,
        'status' => 'completed',
    ]);

    // Related movements should also be completed
    $pendingMovements = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustmentId)
        ->where('status', 'pending')
        ->count();
    expect($pendingMovements)->toBe(0);
});

it('rejects double confirmation of adjustment', function () {
    featureCreateStock(10);

    $adjustResponse = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'increase',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'mode' => 'increment', 'reason' => 'found'],
            ],
        ]);

    $adjustmentId = $adjustResponse->json('data.id');

    // First confirmation
    $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/adjustments/{$adjustmentId}/confirm")
        ->assertStatus(200);

    // Second confirmation should fail
    $response = $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/adjustments/{$adjustmentId}/confirm");

    $response->assertStatus(422);
    $response->assertJsonPath('status', false);
});

// ──────────────────────────────────────────────────────
// CONFIRM TRANSFER ENDPOINT
// ──────────────────────────────────────────────────────

it('confirms a pending transfer', function () {
    featureCreateStock(50);

    $transferResponse = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 20],
            ],
        ]);

    $transferId = $transferResponse->json('data.id');

    $response = $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/transfer/{$transferId}/confirm");

    $response->assertStatus(200);
    $response->assertJsonPath('status', true);

    $this->assertDatabaseHas('stock_transfers', [
        'id' => $transferId,
        'status' => 'completed',
    ]);

    // All movements should be completed
    $pendingMovements = StockMovement::where('movable_type', StockTransfer::class)
        ->where('movable_id', $transferId)
        ->where('status', 'pending')
        ->count();
    expect($pendingMovements)->toBe(0);

    // Transfer should have received_at set
    $transfer = StockTransfer::find($transferId);
    expect($transfer->received_at)->not->toBeNull();
});

it('rejects double confirmation of transfer', function () {
    featureCreateStock(50);

    $transferResponse = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10],
            ],
        ]);

    $transferId = $transferResponse->json('data.id');

    // First confirmation
    $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/transfer/{$transferId}/confirm")
        ->assertStatus(200);

    // Second confirmation should fail
    $response = $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/transfer/{$transferId}/confirm");

    $response->assertStatus(422);
    $response->assertJsonPath('status', false);
});

it('confirming transfer does not move stock again', function () {
    featureCreateStock(50);

    $transferResponse = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 20],
            ],
        ]);

    $transferId = $transferResponse->json('data.id');

    // Stock after creation
    $sourceAfterCreate = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity;
    $destAfterCreate = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse2->id)->first()->quantity;

    expect($sourceAfterCreate)->toBe(30);
    expect($destAfterCreate)->toBe(20);

    // Confirm the transfer
    $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/transfer/{$transferId}/confirm");

    // Stock should not have changed
    $sourceAfterConfirm = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity;
    $destAfterConfirm = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse2->id)->first()->quantity;

    expect($sourceAfterConfirm)->toBe(30);
    expect($destAfterConfirm)->toBe(20);
});

// ──────────────────────────────────────────────────────
// CONFIRM MOVEMENT ENDPOINT
// ──────────────────────────────────────────────────────

it('confirms an individual movement', function () {
    featureCreateStock(10);

    $adjustResponse = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'increase',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'mode' => 'increment', 'reason' => 'found'],
            ],
        ]);

    $adjustmentId = $adjustResponse->json('data.id');

    $movement = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustmentId)
        ->first();

    $response = $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/movements/{$movement->id}/confirm");

    $response->assertStatus(200);
    $response->assertJsonPath('status', true);

    expect($movement->fresh()->status)->toBe('completed');
});

it('rejects double confirmation of movement', function () {
    featureCreateStock(10);

    $adjustResponse = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'increase',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'mode' => 'increment', 'reason' => 'found'],
            ],
        ]);

    $adjustmentId = $adjustResponse->json('data.id');

    $movement = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('movable_id', $adjustmentId)
        ->first();

    // First confirmation
    $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/movements/{$movement->id}/confirm")
        ->assertStatus(200);

    // Second confirmation should fail
    $response = $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/movements/{$movement->id}/confirm");

    $response->assertStatus(422);
    $response->assertJsonPath('status', false);
});

// ──────────────────────────────────────────────────────
// LISTING ENDPOINTS
// ──────────────────────────────────────────────────────

it('lists stock', function () {
    featureCreateStock(25);

    $response = $this->actingAs($this->user)
        ->getJson('/api/admin/inventory/stock');

    $response->assertStatus(200);

    $data = $response->json();
    $items = is_array($data) && isset($data[0]) ? $data : ($data['data'] ?? []);
    expect($items)->not->toBeEmpty();
});

it('lists movements', function () {
    featureCreateStock(10);

    $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'increase',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'mode' => 'increment', 'reason' => 'found'],
            ],
        ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/admin/inventory/stock/movements');

    $response->assertStatus(200);
});

it('lists adjustments', function () {
    featureCreateStock(10);

    $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'increase',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'mode' => 'increment', 'reason' => 'found'],
            ],
        ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/admin/inventory/stock/adjustments');

    $response->assertStatus(200);
});

it('filters movements by warehouse', function () {
    featureCreateStock(50);
    featureCreateStock(50, $this->warehouse2->id);

    // Create movement on warehouse1
    $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'increase',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'mode' => 'increment', 'reason' => 'found'],
            ],
        ]);

    // Create movement on warehouse2
    $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse2->id,
            'type' => 'decrease',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 3, 'mode' => 'decrement', 'reason' => 'damaged'],
            ],
        ]);

    // Filter by warehouse1 only
    $response = $this->actingAs($this->user)
        ->getJson('/api/admin/inventory/stock/movements?warehouse_id=' . $this->warehouse->id);

    $response->assertStatus(200);
    $data = $response->json('data');
    foreach ($data as $movement) {
        expect($movement['warehouse_id'])->toBe($this->warehouse->id);
    }
});

// ──────────────────────────────────────────────────────
// FULL END-TO-END FLOW
// ──────────────────────────────────────────────────────

it('full e2e: entry, exit, recount, transfer and confirm', function () {
    featureCreateStock(100);
    featureCreateStock(50, $this->warehouse2->id);

    // 1. Entry: +20 to warehouse1
    $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'increase',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 20, 'mode' => 'increment', 'reason' => 'found'],
            ],
        ])->assertStatus(200);

    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(120);

    // 2. Exit: -10 from warehouse1
    $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'decrease',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10, 'mode' => 'decrement', 'reason' => 'damaged'],
            ],
        ])->assertStatus(200);

    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(110);

    // 3. Recount: set warehouse2 to 45
    $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse2->id,
            'type' => 'recount',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 45, 'mode' => 'absolute', 'reason' => 'recount'],
            ],
        ])->assertStatus(200);

    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse2->id)->first()->quantity)->toBe(45);

    // 4. Transfer: 30 from warehouse1 to warehouse2
    $transferResponse = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'notes' => 'E2E test transfer',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 30],
            ],
        ]);
    $transferResponse->assertStatus(200);

    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(80);
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse2->id)->first()->quantity)->toBe(75);

    // 5. Confirm the transfer
    $transferId = $transferResponse->json('data.id');
    $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/transfer/{$transferId}/confirm")
        ->assertStatus(200);

    // Stock should not change after confirmation
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)->first()->quantity)->toBe(80);
    expect(Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse2->id)->first()->quantity)->toBe(75);

    // 6. Verify total: original 150, +20 entry, -10 exit, -5 recount = 155
    $totalStock = Stock::where('product_id', $this->product->id)->sum('quantity');
    expect((int) $totalStock)->toBe(155);

    // 7. Verify all data integrity
    $adjustments = StockAdjustment::count();
    expect($adjustments)->toBe(3); // entry, exit, recount

    $transfers = StockTransfer::count();
    expect($transfers)->toBe(1);

    // Movements: 3 from adjustments + 2 from transfer = 5
    $movements = StockMovement::count();
    expect($movements)->toBe(5);

    // Transfer movements should be completed, adjustment movements still pending
    $completedTransferMovements = StockMovement::where('movable_type', StockTransfer::class)
        ->where('status', 'completed')
        ->count();
    expect($completedTransferMovements)->toBe(2);

    $pendingAdjustmentMovements = StockMovement::where('movable_type', StockAdjustment::class)
        ->where('status', 'pending')
        ->count();
    expect($pendingAdjustmentMovements)->toBe(3);
});

it('full e2e: confirm all adjustments independently', function () {
    featureCreateStock(50);

    // Create 3 adjustments
    $adj1 = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'increase',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10, 'mode' => 'increment', 'reason' => 'found'],
            ],
        ])->json('data.id');

    $adj2 = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'decrease',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 3, 'mode' => 'decrement', 'reason' => 'damaged'],
            ],
        ])->json('data.id');

    $adj3 = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'type' => 'recount',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 100, 'mode' => 'absolute', 'reason' => 'recount'],
            ],
        ])->json('data.id');

    // All should be pending
    expect(StockAdjustment::find($adj1)->status)->toBe('pending');
    expect(StockAdjustment::find($adj2)->status)->toBe('pending');
    expect(StockAdjustment::find($adj3)->status)->toBe('pending');

    // Confirm in reverse order
    $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/adjustments/{$adj3}/confirm")
        ->assertStatus(200);

    expect(StockAdjustment::find($adj1)->status)->toBe('pending');
    expect(StockAdjustment::find($adj2)->status)->toBe('pending');
    expect(StockAdjustment::find($adj3)->status)->toBe('completed');

    $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/adjustments/{$adj1}/confirm")
        ->assertStatus(200);

    expect(StockAdjustment::find($adj1)->status)->toBe('completed');
    expect(StockAdjustment::find($adj2)->status)->toBe('pending');

    $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/adjustments/{$adj2}/confirm")
        ->assertStatus(200);

    expect(StockAdjustment::find($adj2)->status)->toBe('completed');

    // All movements should eventually be completed
    $pendingCount = StockMovement::where('status', 'pending')->count();
    expect($pendingCount)->toBe(0);
});

// ──────────────────────────────────────────────────────
// LIST TRANSFERS API
// ──────────────────────────────────────────────────────

it('lists transfers via api', function () {
    featureCreateStock(100);

    // Create two transfers
    $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10],
            ],
        ])
        ->assertStatus(200);

    $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10],
            ],
        ])
        ->assertStatus(200);

    $response = $this->actingAs($this->user)
        ->getJson('/api/admin/inventory/stock/transfers');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(2);
});

it('lists transfers filtered by status via api', function () {
    featureCreateStock(100);

    // Create two transfers
    $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10],
            ],
        ])
        ->assertStatus(200);

    $t2Response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10],
            ],
        ]);

    $t2Response->assertStatus(200);
    $t2Id = $t2Response->json('data.id');

    // Confirm the second transfer
    $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/transfer/{$t2Id}/confirm")
        ->assertStatus(200);

    // Filter by pending — should get 1
    $pendingResponse = $this->actingAs($this->user)
        ->getJson('/api/admin/inventory/stock/transfers?status=pending');
    $pendingResponse->assertStatus(200);
    expect($pendingResponse->json('data'))->toHaveCount(1);

    // Filter by completed — should get 1
    $completedResponse = $this->actingAs($this->user)
        ->getJson('/api/admin/inventory/stock/transfers?status=completed');
    $completedResponse->assertStatus(200);
    expect($completedResponse->json('data'))->toHaveCount(1);
});

it('lists transfers filtered by from_warehouse_id via api', function () {
    featureCreateStock(100);
    featureCreateStock(100, $this->warehouse2->id);

    $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 5],
            ],
        ])
        ->assertStatus(200);

    $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse2->id,
            'destination_warehouse_id' => $this->warehouse->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 5],
            ],
        ])
        ->assertStatus(200);

    $response = $this->actingAs($this->user)
        ->getJson("/api/admin/inventory/stock/transfers?from_warehouse_id={$this->warehouse->id}");

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.from_warehouse_id'))->toBe($this->warehouse->id);
});

it('transfers list includes relationships', function () {
    featureCreateStock(100);

    $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10],
            ],
        ])
        ->assertStatus(200);

    $response = $this->actingAs($this->user)
        ->getJson('/api/admin/inventory/stock/transfers');

    $response->assertStatus(200);
    $transfer = $response->json('data.0');

    expect($transfer)->toHaveKey('source_warehouse');
    expect($transfer)->toHaveKey('destination_warehouse');
    expect($transfer)->toHaveKey('requested_by');
    expect($transfer)->toHaveKey('items');
    expect($transfer['source_warehouse']['id'])->toBe($this->warehouse->id);
    expect($transfer['destination_warehouse']['id'])->toBe($this->warehouse2->id);
});

// ──────────────────────────────────────────────────────
// CANCEL TRANSFER API
// ──────────────────────────────────────────────────────

it('cancels a pending transfer via api', function () {
    featureCreateStock(100);

    $createResponse = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 20],
            ],
        ]);

    $createResponse->assertStatus(200);
    $transferId = $createResponse->json('data.id');

    // Cancel the transfer
    $cancelResponse = $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/transfer/{$transferId}/cancel");

    $cancelResponse->assertStatus(200);
    $cancelResponse->assertJsonPath('status', true);
    $cancelResponse->assertJsonPath('message', 'Transferencia cancelada correctamente.');

    // Verify transfer is cancelled
    expect(StockTransfer::find($transferId)->status)->toBe('cancelled');

    // Verify all related movements are cancelled
    $pendingMovements = StockMovement::where('movable_type', StockTransfer::class)
        ->where('movable_id', $transferId)
        ->where('status', 'pending')
        ->count();
    expect($pendingMovements)->toBe(0);

    $cancelledMovements = StockMovement::where('movable_type', StockTransfer::class)
        ->where('movable_id', $transferId)
        ->where('status', 'cancelled')
        ->count();
    expect($cancelledMovements)->toBe(2);
});

it('rejects cancelling a completed transfer via api', function () {
    featureCreateStock(100);

    $createResponse = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10],
            ],
        ]);

    $transferId = $createResponse->json('data.id');

    // Confirm first
    $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/transfer/{$transferId}/confirm")
        ->assertStatus(200);

    // Try to cancel — should fail
    $cancelResponse = $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/transfer/{$transferId}/cancel");

    $cancelResponse->assertStatus(422);
    $cancelResponse->assertJsonPath('status', false);
});

it('rejects cancelling an already cancelled transfer via api', function () {
    featureCreateStock(100);

    $createResponse = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10],
            ],
        ]);

    $transferId = $createResponse->json('data.id');

    // Cancel first time — should succeed
    $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/transfer/{$transferId}/cancel")
        ->assertStatus(200);

    // Cancel second time — should fail
    $cancelResponse = $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/transfer/{$transferId}/cancel");

    $cancelResponse->assertStatus(422);
    $cancelResponse->assertJsonPath('status', false);
});

// ──────────────────────────────────────────────────────
// CANCEL MOVEMENT API
// ──────────────────────────────────────────────────────

it('cancels a pending movement via api', function () {
    featureCreateStock(10);

    // Create an adjustment so we get a pending movement
    $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
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
        ])
        ->assertStatus(200);

    $movement = StockMovement::where('product_id', $this->product->id)
        ->where('status', 'pending')
        ->first();

    expect($movement)->not->toBeNull();

    // Cancel the movement
    $cancelResponse = $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/movements/{$movement->id}/cancel");

    $cancelResponse->assertStatus(200);
    $cancelResponse->assertJsonPath('status', true);
    $cancelResponse->assertJsonPath('message', 'Movimiento cancelado correctamente.');

    expect($movement->fresh()->status)->toBe('cancelled');
});

it('rejects cancelling a completed movement via api', function () {
    featureCreateStock(10);

    // Create an adjustment
    $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/adjust', [
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
        ])
        ->assertStatus(200);

    $movement = StockMovement::where('product_id', $this->product->id)
        ->where('status', 'pending')
        ->first();

    // Confirm first
    $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/movements/{$movement->id}/confirm")
        ->assertStatus(200);

    // Try to cancel — should fail
    $cancelResponse = $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/movements/{$movement->id}/cancel");

    $cancelResponse->assertStatus(422);
    $cancelResponse->assertJsonPath('status', false);
});

// ──────────────────────────────────────────────────────
// FULL FLOW: CREATE, LIST, CANCEL / CONFIRM TRANSFERS API
// ──────────────────────────────────────────────────────

it('full flow: create transfers, list, cancel one, confirm other via api', function () {
    featureCreateStock(200);

    // Create transfer 1
    $t1Response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 30],
            ],
        ]);
    $t1Response->assertStatus(200);
    $t1Id = $t1Response->json('data.id');

    // Create transfer 2
    $t2Response = $this->actingAs($this->user)
        ->postJson('/api/admin/inventory/stock/transfer', [
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouse2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 50],
            ],
        ]);
    $t2Response->assertStatus(200);
    $t2Id = $t2Response->json('data.id');

    // List all — should get 2
    $listAll = $this->actingAs($this->user)
        ->getJson('/api/admin/inventory/stock/transfers');
    $listAll->assertStatus(200);
    expect($listAll->json('data'))->toHaveCount(2);

    // Cancel transfer 1
    $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/transfer/{$t1Id}/cancel")
        ->assertStatus(200);

    // Confirm transfer 2
    $this->actingAs($this->user)
        ->postJson("/api/admin/inventory/stock/transfer/{$t2Id}/confirm")
        ->assertStatus(200);

    // List pending — should get 0
    $listPending = $this->actingAs($this->user)
        ->getJson('/api/admin/inventory/stock/transfers?status=pending');
    expect($listPending->json('data'))->toHaveCount(0);

    // List completed — should get 1
    $listCompleted = $this->actingAs($this->user)
        ->getJson('/api/admin/inventory/stock/transfers?status=completed');
    expect($listCompleted->json('data'))->toHaveCount(1);
    expect($listCompleted->json('data.0.id'))->toBe($t2Id);

    // List cancelled — should get 1
    $listCancelled = $this->actingAs($this->user)
        ->getJson('/api/admin/inventory/stock/transfers?status=cancelled');
    expect($listCancelled->json('data'))->toHaveCount(1);
    expect($listCancelled->json('data.0.id'))->toBe($t1Id);

    // Verify final states
    expect(StockTransfer::find($t1Id)->status)->toBe('cancelled');
    expect(StockTransfer::find($t2Id)->status)->toBe('completed');
});