<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** The subscription agreement between Terra and an owner or manager. */
class SubscriptionContract extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'pending' => 'Pending',
        'active' => 'Active',
        'expired' => 'Expired',
        'terminated' => 'Terminated',
    ];

    /** Billing cycle => months. */
    public const CYCLES = ['monthly' => 1, 'quarterly' => 3, 'annual' => 12];

    public const CYCLE_LABELS = ['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'annual' => 'Annual'];

    protected $fillable = [
        'contract_number', 'user_id', 'subscription_plan_id', 'title', 'start_date', 'end_date', 'billing_cycle',
        'amount', 'currency', 'payment_due_days', 'status', 'auto_renew', 'terms', 'notes', 'signed_at',
        'activated_at', 'terminated_at', 'termination_reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'amount' => 'decimal:2',
            'auto_renew' => 'boolean',
            'signed_at' => 'datetime',
            'activated_at' => 'datetime',
            'terminated_at' => 'datetime',
        ];
    }

    public function subscriber(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function plan(): BelongsTo { return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function invoices(): HasMany { return $this->hasMany(SubscriptionInvoice::class); }
    public function payments(): HasMany { return $this->hasMany(SubscriptionPayment::class); }
    public function documents(): MorphMany { return $this->morphMany(Document::class, 'documentable'); }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'pending' => 'warning',
            'terminated' => 'danger',
            default => 'secondary',
        };
    }

    public function getCycleMonthsAttribute(): int
    {
        return self::CYCLES[$this->billing_cycle] ?? 1;
    }

    /** Unpaid balance across open invoices. */
    public function outstanding(): float
    {
        return (float) $this->invoices()->whereIn('status', ['unpaid', 'partial'])->sum(DB::raw('amount - amount_paid'));
    }

    public function isExpiringWithin(int $days): bool
    {
        return $this->status === 'active' && $this->end_date
            && $this->end_date->between(Carbon::today(), Carbon::today()->addDays($days));
    }

    public static function nextNumber(): string
    {
        $prefix = 'SUB-' . now()->format('Y') . '-';
        $last = static::withTrashed()->where('contract_number', 'like', $prefix . '%')->orderByDesc('id')->value('contract_number');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        do {
            $number = $prefix . str_pad((string) $next++, 4, '0', STR_PAD_LEFT);
        } while (static::withTrashed()->where('contract_number', $number)->exists());

        return $number;
    }
}
