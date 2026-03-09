<?php

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

test('can list stores', function () {
    Store::factory()->count(3)->create();

    $response = $this->getJson('/api/admin/stores');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('can create a store', function () {
    $this->user->update(['is_superuser' => true]);

    $payload = [
        'name' => 'New Store',
        'slug' => 'new-store',
        'is_active' => true,
        'primary_color' => '#FFFFFF',
    ];

    $response = $this->postJson('/api/admin/stores', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'New Store')
        ->assertJsonPath('data.slug', 'new-store');

    $this->assertDatabaseHas('stores', ['slug' => 'new-store']);
});

test('normal user cannot create a store', function () {
    $payload = [
        'name' => 'New Store',
        'slug' => 'new-store',
        'is_active' => true,
        'primary_color' => '#FFFFFF',
    ];

    $response = $this->postJson('/api/admin/stores', $payload);

    $response->assertStatus(403);
});

test('can update a store', function () {
    $this->user->update(['is_superuser' => true]);
    $store = Store::factory()->create(['name' => 'Old Name']);

    $payload = [
        'name' => 'Updated Store',
        'slug' => $store->slug,
        'is_active' => false,
    ];

    $response = $this->putJson("/api/admin/stores/{$store->id}", $payload);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Updated Store');

    $this->assertDatabaseHas('stores', ['id' => $store->id, 'name' => 'Updated Store']);
});

test('normal user cannot update a store', function () {
    $store = Store::factory()->create(['name' => 'Old Name']);

    $payload = [
        'name' => 'Updated Store',
        'slug' => $store->slug,
        'is_active' => false,
    ];

    $response = $this->putJson("/api/admin/stores/{$store->id}", $payload);

    $response->assertStatus(403);
});

test('can delete a store', function () {
    $this->user->update(['is_superuser' => true]);
    $store = Store::factory()->create();

    $response = $this->deleteJson("/api/admin/stores/{$store->id}");

    $response->assertStatus(200);
    expect(Store::withTrashed()->find($store->id)->trashed())->toBeTrue();
});

test('normal user cannot delete a store', function () {
    $store = Store::factory()->create();

    $response = $this->deleteJson("/api/admin/stores/{$store->id}");

    $response->assertStatus(403);
});
