<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock';

    protected $fillable = [

        'product_id',
        'warehouse_id',
        'storage_location_id',
        'quantity',
        'reserved',
        'avg_cost',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved' => 'integer',
        'avg_cost' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class);
    }

    public function addStock(int $quantity, ?float $cost = null): void
    {
        $this->quantity += $quantity;
        if ($cost !== null) {
            $totalCost = ($this->avg_cost * $this->quantity) + ($cost * $quantity);
            $this->avg_cost = $totalCost / ($this->quantity + $quantity);
        }
        $this->save();
    }

    public function removeStock(int $quantity): void
    {
        if ($quantity > $this->getAvailableQuantity()) {
            throw new \Exception('Stock insuficiente');
        }
        $this->quantity -= $quantity;
        $this->save();
    }

    public function reserveStock(int $quantity): void
    {
        if ($quantity > $this->getAvailableQuantity()) {
            throw new \Exception('Stock insuficiente para reservar');
        }
        $this->reserved += $quantity;
        $this->save();
    }

    public function releaseReserved(int $quantity): void
    {
        $this->reserved -= $quantity;
        $this->save();
    }

    public function getAvailableQuantity(): int
    {
        return $this->quantity - $this->reserved;
    }
}
