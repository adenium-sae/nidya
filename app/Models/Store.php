<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'display_name',
        'slug',
        'description',
        'logo_url',
        'primary_color',
        'secondary_color',
        'accent_color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branches()
    {
        return $this->belongsToMany(Branch::class);
    }

    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(StoreProduct::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
