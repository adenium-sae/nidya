<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use BelongsToTenant;
    use HasApiTokens, HasFactory, Notifiable, HasUuids, SoftDeletes;

    protected $fillable = [
        'email',
        'password',
        'otp_code',
        'otp_expires_at',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function cashRegisterSessions(): HasMany
    {
        return $this->hasMany(CashRegisterSession::class);
    }

    public function hasAccessToTenant(string $tenantId): bool
    {
        return $this->tenants()->where('tenant_id', $tenantId)->exists();
    }

    public function isOwnerOfTenant(string $tenantId): bool
    {
        return $this->tenants()
            ->where('tenant_id', $tenantId)
            ->wherePivot('role', 'owner')
            ->exists();
    }

    public function isAdminOfTenant(string $tenantId): bool
    {
        return $this->tenants()
            ->where('tenant_id', $tenantId)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function getCurrentTenantAttribute(): ?Tenant
    {
        $tenantId = session('tenant_id');
        if (!$tenantId) {
            return null;
        }
        return $this->tenants()->find($tenantId);
    }

    public function switchTenant(string $tenantId): bool
    {
        if (!$this->hasAccessToTenant($tenantId)) {
            return false;
        }
        session(['tenant_id' => $tenantId]);
        return true;
    }

    public function generateOtp(): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);
        return $otp;
    }

    public function verifyOtp(string $otp): bool
    {
        if ($this->otp_code !== $otp) {
            return false;
        }
        if ($this->otp_expires_at < now()) {
            return false;
        }
        $this->update([
            'otp_code' => null,
            'otp_expires_at' => null,
            'email_verified_at' => now(),
        ]);
        return true;
    }

    public function fullName(): string
    {
        if (!$this->profile) {
            return $this->email;
        }
        $name = $this->profile->first_name;
        if ($this->profile->middle_name) {
            $name .= ' ' . $this->profile->middle_name;
        }
        $name .= ' ' . $this->profile->last_name;
        if ($this->profile->second_last_name) {
            $name .= ' ' . $this->profile->second_last_name;
        }
        return $name;
    }
}