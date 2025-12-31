<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids, HasApiTokens;

    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'otp_expires_at' => 'datetime',
    ];

    public function roles() {
        return $this->belongsToMany(Role::class, 'store_user_roles', 'user_id', 'role_id');
    }

    public function stores() {
        return $this->belongsToMany(Store::class, 'store_user_roles', 'user_id', 'store_id');
    }

    public function checkPassword(string $password): bool {
        return password_verify($password, $this->password);
    }

    public function generateOtp(): string {
        $otp = rand(100000, 999999);
        return (string) $otp;
    }

    public function validateOtp(?string $otp): bool {
        if (!$otp) {
            return false;
        }
        if (!$this->otp_expires_at) {
            return false;
        }
        return $this->otp_code === $otp && $this->otp_expires_at->isFuture();
    }
}
