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

    protected $fillable = [
        'property_owner_id',
        'property_code',
        'name',
        'property_type',
        'description',
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
}
