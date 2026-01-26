<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CashMovement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'cash_register_session_id',
        'type',
        'amount',
        'concept',
        'notes',
        'movable_type',
        'movable_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Relación con la sesión de caja
     */
    public function cashRegisterSession(): BelongsTo
    {
        return $this->belongsTo(CashRegisterSession::class);
    }

    /**
     * Relación polimórfica con el origen del movimiento
     * (Sale, Expense, etc.)
     */
    public function movable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Verificar si es un ingreso
     */
    public function isIncome(): bool
    {
        return in_array($this->type, ['sale', 'deposit']);
    }

    /**
     * Verificar si es un egreso
     */
    public function isExpense(): bool
    {
        return in_array($this->type, ['withdrawal', 'expense']);
    }

    /**
     * Obtener el monto con signo (+ para ingresos, - para egresos)
     */
    public function getSignedAmountAttribute(): float
    {
        return $this->isIncome() ? $this->amount : -$this->amount;
    }

    /**
     * Scope para filtrar solo ingresos
     */
    public function scopeIncomes($query)
    {
        return $query->whereIn('type', ['sale', 'deposit']);
    }

    /**
     * Scope para filtrar solo egresos
     */
    public function scopeExpenses($query)
    {
        return $query->whereIn('type', ['withdrawal', 'expense']);
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}