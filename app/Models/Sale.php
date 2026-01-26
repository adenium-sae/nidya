<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'folio',
        'store_id',
        'branch_id',
        'warehouse_id',
        'customer_id',
        'user_id',
        'subtotal',
        'tax',
        'discount',
        'total',
        'status',
        'payment_method',
        'cash_received',
        'change',
        'notes',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'cash_received' => 'decimal:2',
        'change' => 'decimal:2',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public static function generateFolio(string $tenantId): string
    {
        $year = now()->year;
        $lastSale = self::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->first();
        $number = $lastSale ? ((int) substr($lastSale->folio, -5)) + 1 : 1;
        return 'VENTA-' . $year . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('subtotal');
        $this->tax = $this->items->sum('tax');
        $this->total = $this->subtotal + $this->tax - $this->discount;
        
        if ($this->payment_method === 'cash' && $this->cash_received) {
            $this->change = $this->cash_received - $this->total;
        }
        
        $this->save();
    }

    public function complete(): void
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->save();
    }

    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->cancelled_at = now();
        $this->save();
    }
}