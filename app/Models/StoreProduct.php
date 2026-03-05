<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreProduct extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        
        'store_id',
        'product_id',
        'price',
        'compare_at_price',
        'currency',
        'is_active',
        'is_visible',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function hasDiscount(): bool
    {
        return $this->compare_at_price && $this->compare_at_price > $this->price;
    }

    public function getDiscountPercentageAttribute(): ?float
    {
        if (!$this->hasDiscount()) {
            return null;
        }
        return round((($this->compare_at_price - $this->price) / $this->compare_at_price) * 100, 2);
    }
}
