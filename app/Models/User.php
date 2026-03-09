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
        'is_superuser',
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
        'is_superuser' => 'boolean',
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

    public function storeRoles(): HasMany
    {
        return $this->hasMany(StoreUserRole::class);
    }

    public function branchRoles(): HasMany
    {
        return $this->hasMany(BranchUserRole::class);
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

    public function hasRoleInBranch(string $roleKey, string $branchId): bool
    {
        return DB::table('branch_user_roles')
            ->join('roles', 'branch_user_roles.role_id', '=', 'roles.id')
            ->where('branch_user_roles.user_id', $this->id)
            ->where('branch_user_roles.branch_id', $branchId)
            ->where('roles.key', $roleKey)
            ->exists();
    }

    /**
     * Check if the user has a specific permission in a specific store.
     */
    public function hasPermissionInStore(string $permissionKey, string $storeId): bool
    {
        if ($this->is_superuser) {
            return true;
        }

        return DB::table('store_user_roles')
            ->join('role_permissions', 'store_user_roles.role_id', '=', 'role_permissions.role_id')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('store_user_roles.user_id', $this->id)
            ->where('store_user_roles.store_id', $storeId)
            ->where('permissions.key', $permissionKey)
            ->exists();
    }

    /**
     * Get IDs of stores where the user has a specific permission.
     */
    public function getAccessibleStoreIds(?string $permissionKey = null): array
    {
        $query = DB::table('store_user_roles')
            ->where('user_id', $this->id);
        if ($permissionKey) {
            $query->join('role_permissions', 'store_user_roles.role_id', '=', 'role_permissions.role_id')
                ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
                ->where('permissions.key', $permissionKey);
        }
        return $query->distinct()->pluck('store_id')->toArray();
    }
}
