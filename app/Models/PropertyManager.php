<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PropertyManager extends Model
{
    protected $fillable = [
        'user_id',
        'manager_number',
        'phone',
        'job_title',
        'employment_type',
        'start_date',
        'status',
        'notes',
        'created_by',
        'country',
        'province',
        'district',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(
            Property::class,
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
}
