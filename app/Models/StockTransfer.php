<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class StockTransfer extends Model
{
    use HasUuids;

    protected $fillable = [
        'folio',
        'from_warehouse_id',
        'to_warehouse_id',
        'requested_by',
        'approved_by',
        'received_by',
        'status',
        'approved_at',
        'shipped_at',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'movable');
    }
}