<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An online payment attempt through a provider (Mobile Money, gateway…). */
class PaymentIntent extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'tenant_id', 'lease_id', 'unit_id', 'rent_invoice_id', 'provider', 'phone',
        'amount', 'currency', 'status', 'provider_reference', 'failure_reason', 'rent_payment_id',
        'provider_payload', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'provider_payload' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function lease(): BelongsTo { return $this->belongsTo(Lease::class); }
    public function unit(): BelongsTo { return $this->belongsTo(Unit::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(RentInvoice::class, 'rent_invoice_id'); }
    public function payment(): BelongsTo { return $this->belongsTo(RentPayment::class, 'rent_payment_id'); }

    public function isFinal(): bool
    {
        return in_array($this->status, ['successful', 'failed', 'expired'], true);
    }
}
