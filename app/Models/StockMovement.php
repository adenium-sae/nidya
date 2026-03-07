<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [

        'product_id',
        'warehouse_id',
        'storage_location_id',
        'type',
        'status',
        'quantity',
        'quantity_before',
        'quantity_after',
        'cost',
        'notes',
        'reference',
        'user_id',
        'related_movement_id',
        'movable_type',
        'movable_id',
    ];

    const TYPE_ENTRY = 'entry';
    const TYPE_EXIT = 'exit';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'cost' => 'decimal:2',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movable(): MorphTo
    {
        return $this->morphTo();
    }

    public function relatedMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'related_movement_id');
    }
}
