<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Money received from a subscriber. */
class SubscriptionPayment extends Model
{
    public const METHODS = [
        'mobile_money' => 'Mobile money',
        'bank_transfer' => 'Bank transfer',
        'card' => 'Card',
        'cash' => 'Cash',
    ];

    protected $fillable = [
        'reference', 'subscription_invoice_id', 'subscription_contract_id', 'user_id', 'amount', 'currency', 'method',
        'paid_at', 'notes', 'recorded_by',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public function invoice(): BelongsTo { return $this->belongsTo(SubscriptionInvoice::class, 'subscription_invoice_id'); }
    public function contract(): BelongsTo { return $this->belongsTo(SubscriptionContract::class, 'subscription_contract_id'); }
    public function subscriber(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->method] ?? ucfirst(str_replace('_', ' ', $this->method));
    }
}
