<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A bill issued to a subscriber under their subscription contract. */
class SubscriptionInvoice extends Model
{
    public const STATUSES = ['unpaid' => 'Unpaid', 'partial' => 'Partly paid', 'paid' => 'Paid', 'void' => 'Void'];

    protected $fillable = [
        'invoice_number', 'subscription_contract_id', 'user_id', 'period_start', 'period_end', 'issue_date', 'due_date',
        'amount', 'amount_paid', 'currency', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'issue_date' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    public function contract(): BelongsTo { return $this->belongsTo(SubscriptionContract::class, 'subscription_contract_id'); }
    public function subscriber(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function payments(): HasMany { return $this->hasMany(SubscriptionPayment::class); }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['unpaid', 'partial']);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereIn('status', ['unpaid', 'partial'])->whereDate('due_date', '<', today());
    }

    public function getBalanceAttribute(): float
    {
        return $this->status === 'void' ? 0.0 : max(0, round((float) $this->amount - (float) $this->amount_paid, 2));
    }

    public function getIsOverdueAttribute(): bool
    {
        return in_array($this->status, ['unpaid', 'partial'], true) && (bool) $this->due_date?->lt(today());
    }

    public function getDisplayStatusAttribute(): string
    {
        return $this->is_overdue ? 'Overdue' : (self::STATUSES[$this->status] ?? ucfirst($this->status));
    }

    public function getStatusToneAttribute(): string
    {
        return match (true) {
            $this->is_overdue => 'danger',
            $this->status === 'paid' => 'success',
            $this->status === 'partial' => 'warning',
            $this->status === 'void' => 'secondary',
            default => 'info',
        };
    }

    /** Recalculates amount_paid and status from the recorded payments. */
    public function refreshTotals(): void
    {
        if ($this->status === 'void') {
            return;
        }

        $paid = round((float) $this->payments()->sum('amount'), 2);
        $this->forceFill([
            'amount_paid' => $paid,
            'status' => $paid <= 0 ? 'unpaid' : ($paid + 0.009 >= (float) $this->amount ? 'paid' : 'partial'),
        ])->save();
    }

    public static function nextNumber(): string
    {
        $prefix = 'SINV-' . now()->format('Ym') . '-';
        $last = static::where('invoice_number', 'like', $prefix . '%')->orderByDesc('id')->value('invoice_number');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        do {
            $number = $prefix . str_pad((string) $next++, 4, '0', STR_PAD_LEFT);
        } while (static::where('invoice_number', $number)->exists());

        return $number;
    }
}
