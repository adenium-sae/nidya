<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'store_id',
        'type',
        'event',
        'description',
        'metadata',
        'level',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    // --- Types ---
    const TYPE_AUTH = 'auth';
    const TYPE_INVENTORY = 'inventory';
    const TYPE_SALES = 'sales';
    const TYPE_CATALOG = 'catalog';
    const TYPE_ORGANIZATION = 'organization';
    const TYPE_SYSTEM = 'system';

    // --- Levels ---
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
