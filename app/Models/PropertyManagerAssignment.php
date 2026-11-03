<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyManagerAssignment extends Model
{
    protected $fillable = [
        'property_id',
        'property_manager_id',
        'start_date',
        'end_date',
        'status',
        'assigned_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(
            PropertyManager::class,
            'property_manager_id'
        );
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}