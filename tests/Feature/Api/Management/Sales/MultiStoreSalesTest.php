<?php

use App\Models\User;
use App\Models\Store;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Role;
use App\Models\StoreUserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('can create a sale with items from different stores', function () {
    Artisan::call('db:seed', ['--class' => 'ProductionSeeder']);
    
    $user = User::factory()->create(['is_superuser' => true]);
    Sanctum::actingAs($user);

    $storeA = Store::factory()->create(['name' => 'Store A']);
    $storeB = Store::factory()->create(['name' => 'Store B']);
    
    $branch = Branch::factory()->create();
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);

    $product = Product::factory()->create(['name' => 'Shared Product', 'track_inventory' => false]);
    
    // Set different prices in different stores
    $product->storeProducts()->create([
        'store_id' => $storeA->id,
        'price' => 100.00,
        'currency' => 'MXN',
        'is_active' => true,
    ]);
    
    $product->storeProducts()->create([
        'store_id' => $storeB->id,
        'price' => 150.00,
        'currency' => 'MXN',
        'is_active' => true,
    ]);

    $payload = [
        'store_id' => $storeA->id,
        'branch_id' => $branch->id,
        'warehouse_id' => $warehouse->id,
        'payment_method' => 'cash',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'store_id' => $storeA->id, // Price 100
            ],
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'store_id' => $storeB->id, // Price 150
            ]
        ]
    ];

    $response = $this->postJson('/api/admin/sales', $payload);

    $response->assertStatus(201);
    
    $saleId = $response->json()['data']['id'];
    $sale = Sale::with('items')->find($saleId);

    expect($sale->items)->toHaveCount(2);
    
    $itemA = $sale->items->where('store_id', $storeA->id)->first();
    $itemB = $sale->items->where('store_id', $storeB->id)->first();

    expect($itemA->unit_price)->toBe("100.00");
    expect($itemB->unit_price)->toBe("150.00");
    
    // Total calculation: (100 + 150) * 1.16 tax = 250 * 1.16 = 290
    expect($sale->total)->toBe("290.00");
});

test('fails if user does not have permission in one of the stores', function () {
    Artisan::call('db:seed', ['--class' => 'ProductionSeeder']);
    
    $user = User::factory()->create(['is_superuser' => false]);
    Sanctum::actingAs($user);

    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();
    
    // Gift permission only for Store A
    $adminRole = Role::where('key', 'admin')->first();
    StoreUserRole::create([
        'user_id' => $user->id,
        'store_id' => $storeA->id,
        'role_id' => $adminRole->id,
    ]);

    $branch = Branch::factory()->create();
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
    $product = Product::factory()->create(['track_inventory' => false]);
    
    $product->storeProducts()->create(['store_id' => $storeA->id, 'price' => 100, 'is_active' => true]);
    $product->storeProducts()->create(['store_id' => $storeB->id, 'price' => 150, 'is_active' => true]);

    $payload = [
        'store_id' => $storeA->id,
        'branch_id' => $branch->id,
        'warehouse_id' => $warehouse->id,
        'payment_method' => 'cash',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1, 'store_id' => $storeA->id],
            ['product_id' => $product->id, 'quantity' => 1, 'store_id' => $storeB->id] // Should fail here
        ]
    ];

    $response = $this->postJson('/api/admin/sales', $payload);

    $response->assertStatus(403);
});
