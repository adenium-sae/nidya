<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StorageItem extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        "label",
        "batch_type",
        "warehouse_id",
        "store_id",
        "product_id",
    ];

    public function warehouse() {
        return $this->belongsTo(Warehouse::class);
    }

    public function store() {
        return $this->belongsTo(Store::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }
}
