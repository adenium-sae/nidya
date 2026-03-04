<?php

use App\Actions\Organization\Branches\UpdateBranchAction;
use App\Exceptions\Organization\Branches\BranchNotFoundException;
use App\Models\Branch;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it updates a branch and syncs stores', function () {
    $store1 = Store::factory()->create();
    $store2 = Store::factory()->create();

    $branch = Branch::factory()->create(['name' => 'Old Name']);
    $branch->stores()->attach($store1->id);

    $action = new UpdateBranchAction();

    $data = [
        'name' => 'New Name',
        'store_ids' => [$store2->id], // Replacing store1 with store2
    ];

    $updatedBranch = $action($branch->id, $data);

    expect($updatedBranch->name)->toBe('New Name');

    // Verify stores were synced properly
    expect($updatedBranch->stores)->toHaveCount(1)
        ->first()->id->toBe($store2->id);
});

test('it throws exception if branch not found', function () {
    $action = new UpdateBranchAction();

    expect(fn() => $action('invalid-id', []))->toThrow(BranchNotFoundException::class);
});
