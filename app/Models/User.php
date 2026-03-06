<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
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

    public function generateOtp(): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(2),
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

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'branch_user_roles');
    }

    public function getAllPermissionsAbilities(): array
    {
        return $this->roles()->with('permissions')->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('key')
            ->unique()
            ->toArray();
    }

    public function hasRoleInBranch(string $roleKey, string $branchId): bool
    {
        return DB::table('branch_user_roles')
            ->join('roles', 'branch_user_roles.role_id', '=', 'roles.id')
            ->where('branch_user_roles.user_id', $this->id)
            ->where('branch_user_roles.branch_id', $branchId)
            ->where('roles.key', $roleKey)
            ->exists();
    }
}
