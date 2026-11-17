<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Property extends Model
{
    use SoftDeletes;

    /** Amenities a property can advertise: key => [label, bootstrap icon]. */
    public const AMENITIES = [
        'parking' => ['Parking', 'bi-car-front'],
        'security' => ['24/7 Security', 'bi-shield-check'],
        'cctv' => ['CCTV', 'bi-camera-video'],
        'backup_power' => ['Backup power', 'bi-lightning-charge'],
        'water_tank' => ['Water tank', 'bi-droplet-half'],
        'elevator' => ['Elevator', 'bi-arrow-down-up'],
        'internet' => ['Internet / Wi-Fi', 'bi-wifi'],
        'garden' => ['Garden', 'bi-tree'],
        'playground' => ['Playground', 'bi-balloon'],
        'gym' => ['Gym', 'bi-heart-pulse'],
        'swimming_pool' => ['Swimming pool', 'bi-water'],
        'furnished' => ['Furnished options', 'bi-lamp'],
    ];

    /** Utilities supplied at the property: key => [label, bootstrap icon]. */
    public const UTILITIES = [
        'electricity' => ['Electricity (prepaid)', 'bi-plug'],
        'water' => ['Piped water', 'bi-droplet'],
        'internet' => ['Fibre internet', 'bi-router'],
        'waste' => ['Waste collection', 'bi-trash3'],
        'gas' => ['Cooking gas', 'bi-fire'],
    ];

    protected $fillable = [
        'property_owner_id',
        'property_code',
        'upi',
        'name',
        'property_type',
        'description',
        'amenities',
        'utilities',
        'video_url',
        'country',
        'province',
        'district',
        'sector',
        'cell',
        'village',
        'address',
        'latitude',
        'longitude',
        'year_built',
        'total_buildings',
        'total_units',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'year_built' => 'integer',
            'total_buildings' => 'integer',
            'total_units' => 'integer',
            'amenities' => 'array',
            'utilities' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(PropertyOwner::class, 'property_owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(
            PropertyManager::class,
            'property_manager_assignments'
        )->withPivot([
            'id',
            'start_date',
            'end_date',
            'status',
            'assigned_by',
            'notes',
        ])->withTimestamps();
    }

    public function activeManagers(): BelongsToMany
    {
        return $this->managers()
            ->wherePivot('status', 'active')
            ->wherePivotNull('end_date');
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    public function units(): HasManyThrough
    {
        return $this->hasManyThrough(Unit::class, Building::class);
    }

    public function documents(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Document::class, 'documentable')->latest();
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    public function managerAssignments(): HasMany
    {
        return $this->hasMany(PropertyManagerAssignment::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(PropertyExpense::class);
    }

    /** Human-readable "Sector, District" style location line. */
    public function getLocationLineAttribute(): string
    {
        return collect([$this->sector, $this->district ?: $this->province])
            ->filter()
            ->unique()
            ->implode(', ') ?: ($this->address ?: $this->country ?: '');
    }

    /** Amenities resolved to [key, label, icon] for display. */
    public function amenityList(): array
    {
        return collect($this->amenities ?? [])
            ->filter(fn ($key) => isset(self::AMENITIES[$key]))
            ->map(fn ($key) => ['key' => $key, 'label' => self::AMENITIES[$key][0], 'icon' => self::AMENITIES[$key][1]])
            ->values()
            ->all();
    }

    /** Utilities resolved to [key, label, icon] for display. */
    public function utilityList(): array
    {
        return collect($this->utilities ?? [])
            ->filter(fn ($key) => isset(self::UTILITIES[$key]))
            ->map(fn ($key) => ['key' => $key, 'label' => self::UTILITIES[$key][0], 'icon' => self::UTILITIES[$key][1]])
            ->values()
            ->all();
    }
}
