<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'features',
        'electricity_meter',
        'water_meter',
        'inventory',
        'status'
    ];

    /** Unit statuses from the brief (section 5): key => label. */
    public const STATUSES = [
        'available' => 'Available',
        'reserved' => 'Reserved',
        'occupied' => 'Occupied',
        'maintenance' => 'Under maintenance',
        'vacant' => 'Vacant',
        'inactive' => 'Inactive',
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

    /**
     * Key features, stored as a JSON list. Accepts an array or a
     * newline-separated string (as typed in the admin textarea).
     */
    protected function features(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? (json_decode($value, true) ?: []) : [],
            set: function ($value) {
                $items = is_array($value) ? $value : preg_split('/\r\n|\r|\n/', (string) $value);
                $items = array_values(array_filter(array_map(
                    fn ($item) => mb_substr(trim((string) $item), 0, 120),
                    $items
                ), fn ($item) => $item !== ''));

                return $items ? json_encode(array_slice($items, 0, 20)) : null;
            },
        );
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function building()
    {
        return $this->belongsTo(\App\Models\Building::class);
    }

    public function floor()
    {
        return $this->belongsTo(\App\Models\Floor::class);
    }

    public function documents(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Document::class, 'documentable')->latest();
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
