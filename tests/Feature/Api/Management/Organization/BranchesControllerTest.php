<?php

use App\Models\Branch;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

test('can list branches', function () {
    Branch::factory()->count(3)->create();

    $response = $this->getJson('/api/admin/branches');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('can create a branch', function () {
    $store = Store::factory()->create();

    $payload = [
        'name' => 'New Branch',
        'code' => 'BR-NEW',
        'store_ids' => [$store->id],
        'is_active' => true,
    ];

    $response = $this->postJson('/api/admin/branches', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'New Branch')
        ->assertJsonPath('data.code', 'BR-NEW');

    $this->assertDatabaseHas('branches', ['code' => 'BR-NEW']);
    $this->assertDatabaseHas('branch_store', ['store_id' => $store->id]);
});

test('can update a branch', function () {
    $store = Store::factory()->create();
    $branch = Branch::factory()->create(['name' => 'Old Name']);

    $payload = [
        'name' => 'Updated Branch',
        'store_ids' => [$store->id],
    ];

    $response = $this->putJson("/api/admin/branches/{$branch->id}", $payload);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Updated Branch');

    $this->assertDatabaseHas('branches', ['id' => $branch->id, 'name' => 'Updated Branch']);
});

test('can delete a branch', function () {
    $branch = Branch::factory()->create();

    $response = $this->deleteJson("/api/admin/branches/{$branch->id}");

    $response->assertStatus(200);
    expect(Branch::withTrashed()->find($branch->id)->trashed())->toBeTrue();
});
