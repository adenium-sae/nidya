<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        "name",
        "description",
        "sku",
        "type",
    ];

    public function storeProducts() {
        return $this->hasMany(StoreProduct::class);
    }

    public function storageItems() {
        return $this->hasMany(StorageItem::class);
    }

    public function stockItems() {
        return $this->hasMany(StockItem::class);
    }
}
