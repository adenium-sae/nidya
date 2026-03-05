<?php

use App\Actions\Organization\Branches\CreateBranchAction;
use App\Models\Branch;
use App\Models\Store;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it creates a branch and an associated warehouse with stores synced', function () {
    $store1 = Store::factory()->create();
    $store2 = Store::factory()->create();

    $action = new CreateBranchAction();

    $data = [
        'name' => 'Main Branch',
        'code' => 'MB-001',
        'store_ids' => [$store1->id, $store2->id],
        'is_active' => true,
    ];

    $branch = $action($data);

    // Assert branch created
    expect($branch)->toBeInstanceOf(Branch::class)
        ->name->toBe('Main Branch')
        ->code->toBe('MB-001');

    // Assert stores attached to branch
    expect($branch->stores)->toHaveCount(2)
        ->pluck('id')->toContain($store1->id, $store2->id);

    // Assert warehouse auto-created
    $warehouse = Warehouse::where('branch_id', $branch->id)->first();
    expect($warehouse)->not->toBeNull()
        ->name->toBe('Almacén principal - Main Branch')
        ->code->toBe('MB-001-ALM')
        ->type->toBe('branch');

    // Assert stores attached to warehouse
    expect($warehouse->stores)->toHaveCount(2)
        ->pluck('id')->toContain($store1->id, $store2->id);
});
