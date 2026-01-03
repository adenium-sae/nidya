<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        "name",
        "store_id",
        "is_active",
    ];

    public function store() {
        return $this->belongsTo(Store::class);
    }
}
