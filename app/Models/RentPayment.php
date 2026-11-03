<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_number',
        'tenant_id',
        'lease_id',
        'unit_id',
        'recorded_by',
        'payment_method',
        'provider',
        'transaction_reference',
        'amount',
        'currency',
        'payment_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
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

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(RentPaymentAllocation::class);
    }

    public function getAllocatedAmountAttribute(): float
    {
        return (float) $this->allocations()
            ->whereHas('payment', function ($query) {
                $query->where('status', 'confirmed');
            })
            ->sum('amount');
    }

    public function getUnallocatedAmountAttribute(): float
    {
        if ($this->status !== 'confirmed') {
            return 0.0;
        }

        return max(
            0,
            (float) $this->amount - $this->allocated_amount
        );
    }
}
