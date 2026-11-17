<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lease renewal negotiation or tenant move-out notice.
 *
 * renewal:  requested → offered → accepted | declined   (staff may also open with an offer)
 * move_out: requested → acknowledged | withdrawn
 */
class LeaseRequest extends Model
{
    public const TYPE_RENEWAL = 'renewal';
    public const TYPE_MOVE_OUT = 'move_out';

    public const STATUS_LABELS = [
        'requested' => 'Requested',
        'offered' => 'Offer sent',
        'accepted' => 'Accepted',
        'declined' => 'Declined',
        'acknowledged' => 'Acknowledged',
        'withdrawn' => 'Withdrawn',
    ];

    protected $fillable = [
        'lease_id', 'tenant_id', 'type', 'status', 'requested_by', 'requested_months', 'move_out_date', 'message',
        'current_end_date', 'current_rent', 'offered_end_date', 'offered_rent', 'response', 'responded_by',
        'responded_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'move_out_date' => 'date',
            'current_end_date' => 'date',
            'offered_end_date' => 'date',
            'current_rent' => 'decimal:2',
            'offered_rent' => 'decimal:2',
            'responded_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function lease(): BelongsTo { return $this->belongsTo(Lease::class); }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function responder(): BelongsTo { return $this->belongsTo(User::class, 'responded_by'); }

    public function isOpen(): bool
    {
        return in_array($this->status, ['requested', 'offered'], true);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === self::TYPE_RENEWAL ? 'Lease renewal' : 'Move-out notice';
    }
}
