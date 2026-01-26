<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'street',
        'ext_number',
        'int_number',
        'neighborhood',
        'city',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function getFullAddressAttribute(): string
    {
        $parts = [
            $this->street . ' ' . $this->ext_number,
            $this->int_number ? 'Int. ' . $this->int_number : null,
            $this->neighborhood,
            $this->city,
            $this->state,
            'C.P. ' . $this->postal_code,
            $this->country,
        ];
        return implode(', ', array_filter($parts));
    }
}
