<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'lease_id',
        'tenant_id',
        'unit_id',
        'invoice_type',
        'billing_period_start',
        'billing_period_end',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'amount_paid',
        'balance_due',
        'currency',
        'status',
        'description',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'billing_period_start' => 'date',
            'billing_period_end' => 'date',
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(RentPaymentAllocation::class);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->balance_due > 0
            && $this->due_date !== null
            && $this->due_date->isPast()
            && ! $this->due_date->isToday();
    }
}
