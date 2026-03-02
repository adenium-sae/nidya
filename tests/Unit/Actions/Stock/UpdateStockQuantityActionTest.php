<?php

use App\Actions\Stock\UpdateStockQuantityAction;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = app(UpdateStockQuantityAction::class);
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create();
    $this->warehouse = Warehouse::factory()->forStore($this->store)->central()->create();
    $this->product = Product::factory()->create();
});

function updateCreateStock(int $quantity, ?string $locationId = null): Stock
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
// BASIC QUANTITY UPDATE
// ──────────────────────────────────────────────

it('updates stock quantity upward', function () {
    $stock = updateCreateStock(10);

    $result = ($this->action)($stock->id, [
        'quantity' => 25,
        'reason' => 'recount',
    ], $this->user->id);

    expect($result->quantity)->toBe(25);

    $fresh = Stock::find($stock->id);
    expect($fresh->quantity)->toBe(25);
});

it('updates stock quantity downward', function () {
    $stock = updateCreateStock(50);

    $result = ($this->action)($stock->id, [
        'quantity' => 20,
        'reason' => 'damaged',
    ], $this->user->id);

    expect($result->quantity)->toBe(20);
});

it('updates stock quantity to zero', function () {
    $stock = updateCreateStock(15);

    $result = ($this->action)($stock->id, [
        'quantity' => 0,
        'reason' => 'lost',
    ], $this->user->id);

    expect($result->quantity)->toBe(0);
});

it('updates stock quantity from zero', function () {
    $stock = updateCreateStock(0);

    $result = ($this->action)($stock->id, [
        'quantity' => 30,
        'reason' => 'found',
    ], $this->user->id);

    expect($result->quantity)->toBe(30);
});

it('updates stock to same quantity (no change)', function () {
    $stock = updateCreateStock(10);

    $result = ($this->action)($stock->id, [
        'quantity' => 10,
        'reason' => 'recount',
    ], $this->user->id);

    expect($result->quantity)->toBe(10);
});

// ──────────────────────────────────────────────
// MOVEMENT LOGGING
// ──────────────────────────────────────────────

it('creates a stock movement on quantity update', function () {
    $stock = updateCreateStock(10);

    ($this->action)($stock->id, [
        'quantity' => 25,
        'reason' => 'recount',
    ], $this->user->id);

    $movement = StockMovement::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();

    expect($movement)->not->toBeNull();
    expect($movement->type)->toBe('adjustment');
    expect($movement->status)->toBe(StockMovement::STATUS_PENDING);
    expect($movement->quantity_before)->toBe(10);
    expect($movement->quantity_after)->toBe(25);
    expect($movement->quantity)->toBe(15); // difference
    expect($movement->user_id)->toBe($this->user->id);
});

it('logs negative difference on decrease', function () {
    $stock = updateCreateStock(50);

    ($this->action)($stock->id, [
        'quantity' => 30,
        'reason' => 'damaged',
    ], $this->user->id);

    $movement = StockMovement::where('product_id', $this->product->id)->first();

    expect($movement->quantity)->toBe(-20);
    expect($movement->quantity_before)->toBe(50);
    expect($movement->quantity_after)->toBe(30);
});

it('logs zero difference when quantity unchanged', function () {
    $stock = updateCreateStock(10);

    ($this->action)($stock->id, [
        'quantity' => 10,
        'reason' => 'recount',
    ], $this->user->id);

    $movement = StockMovement::where('product_id', $this->product->id)->first();

    expect($movement->quantity)->toBe(0);
    expect($movement->quantity_before)->toBe(10);
    expect($movement->quantity_after)->toBe(10);
});

it('records correct product and warehouse on movement', function () {
    $stock = updateCreateStock(10);

    ($this->action)($stock->id, [
        'quantity' => 20,
        'reason' => 'recount',
    ], $this->user->id);

    $movement = StockMovement::first();

    expect($movement->product_id)->toBe($this->product->id);
    expect($movement->warehouse_id)->toBe($this->warehouse->id);
});

it('records storage location on movement when stock has location', function () {
    $location = StorageLocation::create([
        'warehouse_id' => $this->warehouse->id,
        'code' => 'UPD-01',
        'name' => 'Update Test Location',
        'type' => 'shelf',
    ]);

    $stock = updateCreateStock(10, $location->id);

    ($this->action)($stock->id, [
        'quantity' => 20,
        'reason' => 'found',
    ], $this->user->id);

    $movement = StockMovement::first();

    expect($movement->storage_location_id)->toBe($location->id);
});

it('records null storage location on movement when stock has no location', function () {
    $stock = updateCreateStock(10);

    ($this->action)($stock->id, [
        'quantity' => 20,
        'reason' => 'found',
    ], $this->user->id);

    $movement = StockMovement::first();

    expect($movement->storage_location_id)->toBeNull();
});

// ──────────────────────────────────────────────
// MOVEMENT NOTES
// ──────────────────────────────────────────────

it('builds movement note with reason label', function () {
    $stock = updateCreateStock(10);

    ($this->action)($stock->id, [
        'quantity' => 20,
        'reason' => 'damaged',
    ], $this->user->id);

    $movement = StockMovement::first();

    expect($movement->notes)->toContain('Dañado');
});

it('builds movement note with recount reason', function () {
    $stock = updateCreateStock(10);

    ($this->action)($stock->id, [
        'quantity' => 20,
        'reason' => 'recount',
    ], $this->user->id);

    $movement = StockMovement::first();

    expect($movement->notes)->toContain('Recuento');
});

it('appends custom notes to movement notes', function () {
    $stock = updateCreateStock(10);

    ($this->action)($stock->id, [
        'quantity' => 20,
        'reason' => 'recount',
        'notes' => 'Se encontraron unidades extra en bodega',
    ], $this->user->id);

    $movement = StockMovement::first();

    expect($movement->notes)->toContain('Se encontraron unidades extra en bodega');
});

it('does not append notes when notes not provided', function () {
    $stock = updateCreateStock(10);

    ($this->action)($stock->id, [
        'quantity' => 20,
        'reason' => 'recount',
    ], $this->user->id);

    $movement = StockMovement::first();

    // Should not contain the dash separator for appended notes
    expect($movement->notes)->not->toContain('—');
});

it('handles all valid reason labels', function () {
    $reasons = [
        'damaged' => 'Dañado',
        'lost' => 'Pérdida/Robo',
        'found' => 'Hallazgo',
        'expired' => 'Caducado',
        'recount' => 'Recuento',
        'correction' => 'Corrección',
        'other' => 'Otro',
    ];

    foreach ($reasons as $reason => $expectedLabel) {
        $stock = Stock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
            'reserved' => 0,
        ]);

        ($this->action)($stock->id, [
            'quantity' => 15,
            'reason' => $reason,
        ], $this->user->id);

        $movement = StockMovement::where('product_id', $this->product->id)
            ->latest('id')
            ->first();

        expect($movement->notes)->toContain($expectedLabel);

        // Clean up for next iteration
        Stock::where('id', $stock->id)->delete();
        StockMovement::where('product_id', $this->product->id)->delete();
    }
});

// ──────────────────────────────────────────────
// RETURN VALUE & RELATIONSHIPS
// ──────────────────────────────────────────────

it('returns stock with loaded product relationship', function () {
    $stock = updateCreateStock(10);

    $result = ($this->action)($stock->id, [
        'quantity' => 20,
        'reason' => 'recount',
    ], $this->user->id);

    expect($result->relationLoaded('product'))->toBeTrue();
    expect($result->product->id)->toBe($this->product->id);
});

it('returns stock with loaded warehouse relationship', function () {
    $stock = updateCreateStock(10);

    $result = ($this->action)($stock->id, [
        'quantity' => 20,
        'reason' => 'recount',
    ], $this->user->id);

    expect($result->relationLoaded('warehouse'))->toBeTrue();
    expect($result->warehouse->id)->toBe($this->warehouse->id);
});

it('returns stock with loaded storage location relationship', function () {
    $location = StorageLocation::create([
        'warehouse_id' => $this->warehouse->id,
        'code' => 'RET-01',
        'name' => 'Return Test Location',
        'type' => 'shelf',
    ]);

    $stock = updateCreateStock(10, $location->id);

    $result = ($this->action)($stock->id, [
        'quantity' => 20,
        'reason' => 'recount',
    ], $this->user->id);

    expect($result->relationLoaded('storageLocation'))->toBeTrue();
    expect($result->storageLocation->id)->toBe($location->id);
});

// ──────────────────────────────────────────────
// ERROR HANDLING
// ──────────────────────────────────────────────

it('throws exception when stock not found', function () {
    ($this->action)('non-existent-uuid', [
        'quantity' => 10,
        'reason' => 'recount',
    ], $this->user->id);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

// ──────────────────────────────────────────────
// LARGE / EDGE VALUES
// ──────────────────────────────────────────────

it('handles large quantity update', function () {
    $stock = updateCreateStock(100);

    $result = ($this->action)($stock->id, [
        'quantity' => 999999,
        'reason' => 'recount',
    ], $this->user->id);

    expect($result->quantity)->toBe(999999);

    $movement = StockMovement::first();
    expect($movement->quantity)->toBe(999999 - 100);
});

it('handles update from large to small quantity', function () {
    $stock = updateCreateStock(100000);

    $result = ($this->action)($stock->id, [
        'quantity' => 1,
        'reason' => 'recount',
    ], $this->user->id);

    expect($result->quantity)->toBe(1);

    $movement = StockMovement::first();
    expect($movement->quantity)->toBe(1 - 100000);
});

// ──────────────────────────────────────────────
// MULTIPLE UPDATES TO SAME STOCK
// ──────────────────────────────────────────────

it('creates separate movements for each update', function () {
    $stock = updateCreateStock(10);

    ($this->action)($stock->id, [
        'quantity' => 20,
        'reason' => 'recount',
    ], $this->user->id);

    ($this->action)($stock->id, [
        'quantity' => 15,
        'reason' => 'damaged',
    ], $this->user->id);

    ($this->action)($stock->id, [
        'quantity' => 25,
        'reason' => 'found',
    ], $this->user->id);

    $movements = StockMovement::where('product_id', $this->product->id)
        ->orderBy('created_at')
        ->get();

    expect($movements)->toHaveCount(3);

    // First: 10 -> 20
    expect($movements[0]->quantity_before)->toBe(10);
    expect($movements[0]->quantity_after)->toBe(20);
    expect($movements[0]->quantity)->toBe(10);

    // Second: 20 -> 15
    expect($movements[1]->quantity_before)->toBe(20);
    expect($movements[1]->quantity_after)->toBe(15);
    expect($movements[1]->quantity)->toBe(-5);

    // Third: 15 -> 25
    expect($movements[2]->quantity_before)->toBe(15);
    expect($movements[2]->quantity_after)->toBe(25);
    expect($movements[2]->quantity)->toBe(10);

    // Final stock should be 25
    $fresh = Stock::find($stock->id);
    expect($fresh->quantity)->toBe(25);
});

// ──────────────────────────────────────────────
// DATABASE INTEGRITY
// ──────────────────────────────────────────────

it('persists quantity change in database', function () {
    $stock = updateCreateStock(10);

    ($this->action)($stock->id, [
        'quantity' => 42,
        'reason' => 'recount',
    ], $this->user->id);

    $this->assertDatabaseHas('stock', [
        'id' => $stock->id,
        'quantity' => 42,
    ]);
});

it('persists movement in database', function () {
    $stock = updateCreateStock(10);

    ($this->action)($stock->id, [
        'quantity' => 42,
        'reason' => 'recount',
    ], $this->user->id);

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'type' => 'adjustment',
        'status' => 'pending',
        'quantity' => 32,
        'quantity_before' => 10,
        'quantity_after' => 42,
        'user_id' => $this->user->id,
    ]);
});

it('does not create extra stock records', function () {
    $stock = updateCreateStock(10);

    ($this->action)($stock->id, [
        'quantity' => 20,
        'reason' => 'recount',
    ], $this->user->id);

    $stockCount = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->count();

    expect($stockCount)->toBe(1);
});

it('does not modify reserved quantity', function () {
    $stock = Stock::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 50,
        'reserved' => 10,
    ]);

    ($this->action)($stock->id, [
        'quantity' => 30,
        'reason' => 'recount',
    ], $this->user->id);

    $fresh = Stock::find($stock->id);
    expect($fresh->quantity)->toBe(30);
    expect($fresh->reserved)->toBe(10);
});