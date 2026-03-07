<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [

        'category_id',
        'name',
        'description',
        'sku',
        'barcode',
        'type',
        'parent_id',
        'track_inventory',
        'min_stock',
        'max_stock',
        'cost',
        'image_url',
        'is_active',
    ];

    protected $appends = ['available_stock', 'total_stock'];

    protected $casts = [
        'track_inventory' => 'boolean',
        'is_active' => 'boolean',
        'cost' => 'decimal:2',
        'min_stock' => 'integer',
        'max_stock' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Product::class, 'parent_id');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function storeProducts(): HasMany
    {
        return $this->hasMany(StoreProduct::class);
    }

    public function getTotalStockAttribute(): int
    {
        return $this->stock()->sum('quantity');
    }

    public function getAvailableStockAttribute(): int
    {
        return $this->stock()->sum('available');
    }

    public function needsRestock(): bool
    {
        return $this->track_inventory && $this->available_stock <= $this->min_stock;
    }
}
