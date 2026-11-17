<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentCommission extends Model
{
    public const STATUSES = ['pending' => 'Pending', 'approved' => 'Approved', 'paid' => 'Paid', 'cancelled' => 'Cancelled'];

    protected $fillable = [
        'user_id', 'property_id', 'lease_id', 'amount', 'currency', 'status',
        'description', 'paid_at', 'payment_reference', 'recorded_by',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public function agent(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function property(): BelongsTo { return $this->belongsTo(Property::class); }
    public function lease(): BelongsTo { return $this->belongsTo(Lease::class); }
}
