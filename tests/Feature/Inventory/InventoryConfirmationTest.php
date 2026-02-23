<?php

namespace Tests\Feature\Inventory;

use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\StockMovement;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $warehouse;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
        $this->product = Product::factory()->create();
        
        // Initialize stock
        Stock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
        ]);
    }

    /** @test */
    public function test_it_creates_an_adjustment_as_pending()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/inventory/stock/adjust', [
                'warehouse_id' => $this->warehouse->id,
                'type' => 'increase',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 5,
                        'mode' => 'increment',
                        'reason' => 'found'
                    ]
                ]
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('stock_adjustments', [
            'status' => 'pending'
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'status' => 'pending'
        ]);
        
        // Stock should be updated immediately as per request
        $this->assertEquals(15, Stock::first()->quantity);
    }

    /** @test */
    public function it_can_confirm_an_adjustment()
    {
        $adjustment = StockAdjustment::create([
            'folio' => 'ADJ-2026-00001',
            'warehouse_id' => $this->warehouse->id,
            'type' => 'increase',
            'status' => 'pending',
            'user_id' => $this->user->id,
        ]);

        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'adjustment',
            'status' => 'pending',
            'quantity' => 5,
            'quantity_before' => 10,
            'quantity_after' => 15,
            'user_id' => $this->user->id,
            'movable_type' => StockAdjustment::class,
            'movable_id' => $adjustment->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/inventory/stock/adjustments/{$adjustment->id}/confirm");

        $response->assertStatus(200);
        $this->assertEquals('completed', $adjustment->fresh()->status);
        $this->assertEquals('completed', StockMovement::first()->status);
    }

    /** @test */
    public function it_creates_a_transfer_as_pending()
    {
        $warehouse2 = Warehouse::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/inventory/stock/transfer', [
                'source_warehouse_id' => $this->warehouse->id,
                'destination_warehouse_id' => $warehouse2->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 5
                    ]
                ]
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('stock_transfers', [
            'status' => 'pending'
        ]);
        
        // Stock should be updated
        $this->assertEquals(5, Stock::where('warehouse_id', $this->warehouse->id)->first()->quantity);
        $this->assertEquals(5, Stock::where('warehouse_id', $warehouse2->id)->first()->quantity);
    }
}
