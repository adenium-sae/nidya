<?php

namespace App;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Auto-asignar tenant_id al crear
        static::creating(function ($model) {
            if (!$model->tenant_id && Auth::check()) {
                $model->tenant_id = Auth::user()->current_tenant_id ?? session('tenant_id');
            }
        });

        // Scope global para filtrar por tenant automáticamente
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (Auth::check() && Auth::user()->current_tenant_id) {
                $builder->where('tenant_id', Auth::user()->current_tenant_id);
            } elseif (session()->has('tenant_id')) {
                $builder->where('tenant_id', session('tenant_id'));
            }
        });
    }

    /**
     * Relación con el tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope para queries sin filtro de tenant
     */
    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }

    /**
     * Scope para filtrar por un tenant específico
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->withoutGlobalScope('tenant')->where('tenant_id', $tenantId);
    }
}
