<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('new users can register', function () {
    $response = $this->postJson('/api/auth/signup', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();

    // Check store was created
    $this->assertDatabaseHas('stores', [
        'slug' => 'test-store',
    ]);
});
