<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StoreUserRole extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'role_id',
        'store_id',
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role() {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function store() {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
