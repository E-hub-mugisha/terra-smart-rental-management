<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'request_number',
        'tenant_id',
        'unit_id',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'assigned_to',
        'preferred_visit_at',
        'scheduled_at',
        'resolved_at',
        'resolution_notes',
        'estimated_cost',
        'actual_cost',
        'attachment_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'preferred_visit_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'resolved_at' => 'datetime',
            'estimated_cost' => 'decimal:2',
            'actual_cost' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(MaintenanceRequestActivity::class)
            ->latest();
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', ['resolved', 'closed', 'rejected']);
    }
}
