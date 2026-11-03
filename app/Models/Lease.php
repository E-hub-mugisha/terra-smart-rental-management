<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lease extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'unit_id',
        'lease_number',
        'start_date',
        'end_date',
        'monthly_rent',
        'deposit_amount',
        'currency',
        'rent_due_day',
        'payment_frequency',
        'status',
        'termination_date',
        'termination_reason',
        'terms',
        'document_path',
        'tenant_application_id',
        'tenant_accepted_at',
        'move_in_confirmed_at',
        'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'termination_date' => 'date',
            'monthly_rent' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'rent_due_day' => 'integer',
            'tenant_accepted_at' => 'datetime',
            'move_in_confirmed_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RentPayment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(RentInvoice::class);
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(RentPaymentAllocation::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(
            TenantApplication::class,
            'tenant_application_id'
        );
    }
}
