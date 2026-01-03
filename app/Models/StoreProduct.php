<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StoreProduct extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        "store_id",
        "product_id",
        "price",
        "currency",
        "is_active",
    ];

    public function store() {
        return $this->belongsTo(Store::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }
}