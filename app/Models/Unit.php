<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'building_id',
        'floor_id',
        'unit_number',
        'unit_type',
        'bedrooms',
        'bathrooms',
        'area_sqm',
        'rent_amount',
        'deposit_amount',
        'currency',
        'description',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'bedrooms' => 'integer',
            'bathrooms' => 'integer',
            'area_sqm' => 'decimal:2',
            'rent_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
        ];
    }

    public function building()
    {
        return $this->belongsTo(\App\Models\Building::class);
    }

    public function floor()
    {
        return $this->belongsTo(\App\Models\Floor::class);
    }

    public function images()
    {
        return $this->hasMany(\App\Models\UnitImage::class)->orderBy('sort_order');
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(TenantApplication::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(RentInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RentPayment::class);
    }
}
