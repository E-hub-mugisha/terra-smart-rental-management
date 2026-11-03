<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TenantApplication extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'unit_id',
        'reviewed_by',
        'application_number',
        'application_date',
        'desired_move_in_date',
        'number_of_occupants',
        'offered_rent',
        'currency',
        'employment_status',
        'status',
        'reviewed_at',
        'decision_notes',
    ];

    protected function casts(): array
    {
        return [
            'application_date' => 'date',
            'desired_move_in_date' => 'date',
            'reviewed_at' => 'datetime',
            'number_of_occupants' => 'integer',
            'offered_rent' => 'decimal:2',
        ];
    }

    /**
     * Tenant who submitted the application.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Unit the tenant is applying to rent.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * User who reviewed the application.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope applications by status.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function lease(): HasOne
    {
        return $this->hasOne(Lease::class, 'tenant_application_id');
    }
}
