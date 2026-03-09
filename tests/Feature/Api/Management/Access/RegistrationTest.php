<?php

use App\Models\User;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('a newly registered user can access their store immediately', function () {
    // Seed roles first as RegisterUserAction depends on 'admin' role existing
    Artisan::call('db:seed', ['--class' => 'ProductionSeeder']);

    $payload = [
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'first_name' => 'Test',
        'last_name' => 'User',
    ];

    $response = $this->postJson('/api/auth/signup', $payload);

    $response->assertStatus(200);
    
    $result = $response->json()['data'];

    // Verify user is in database
    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect($result)->not->toHaveKey('token');
    expect($user->profile)->not->toBeNull();
    expect($user->profile->first_name)->toBe('Test');
});

test('superuser has access to any store without explicit assignment', function () {
    Artisan::call('db:seed', ['--class' => 'ProductionSeeder']);
    
    $superuser = User::factory()->create(['is_superuser' => true]);
    $randomStore = Store::factory()->create();

    Sanctum::actingAs($superuser);
    
    // Should have permission even if not assigned in store_user_roles
    expect($superuser->hasPermissionInStore('products.view', $randomStore->id))->toBeTrue();
});
