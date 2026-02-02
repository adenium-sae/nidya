<?php

use App\Models\User;
use App\Models\Tenant;
use App\Enums\TenantRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('new users can register as tenant owners', function () {
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
    
    // Check Tenant created
    $this->assertDatabaseHas('tenants', [
        'email' => 'test@example.com',
    ]);
    
    $tenant = Tenant::where('email', 'test@example.com')->first();

    // Check Pivot Role
    $this->assertDatabaseHas('tenant_users', [
        'user_id' => $user->id,
        'tenant_id' => $tenant->id,
        'role' => TenantRole::OWNER->value,
    ]);

    // Check helper method
    expect($user->isOwnerOfTenant($tenant->id))->toBeTrue();
});
