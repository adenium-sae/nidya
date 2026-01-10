<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        "name",
        "type",
        "is_active",
        "branch_id",
        "store_id",
    ];

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

    public function store() {
        return $this->belongsTo(Store::class);
    }
}
