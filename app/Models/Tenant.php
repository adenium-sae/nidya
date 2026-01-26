<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'tax_id',
        'business_name',
        'phone',
        'email',
        'subscription_status',
        'trial_ends_at',
        'subscription_started_at',
        'subscription_plan',
        'settings',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_started_at' => 'date',
        'settings' => 'array',
    ];

    protected $attributes = [
        'subscription_status' => 'trial',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial' 
            && $this->trial_ends_at 
            && $this->trial_ends_at->isFuture();
    }

    public function isActive(): bool
    {
        return $this->subscription_status === 'active' || $this->isOnTrial();
    }

    public function isSuspended(): bool
    {
        return $this->subscription_status === 'suspended';
    }

    public function trialDaysRemaining(): int
    {
        if (!$this->isOnTrial()) {
            return 0;
        }
        return now()->diffInDays($this->trial_ends_at);
    }
}
