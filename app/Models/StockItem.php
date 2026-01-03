<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StockItem extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        "product_id",
        "storage_item_id",
        "stock",
    ];

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function storageItem() {
        return $this->belongsTo(StorageItem::class);
    }
}
