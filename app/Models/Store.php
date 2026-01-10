<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'user_id',
    ];

    public function users() {
        return $this->belongsToMany(User::class, 'store_user_roles', 'store_id', 'user_id');
    }
}
