<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One month's payroll for one employer (a property, or Terra itself).
 * draft → approved → paid. Paying a property's run books a salaries expense.
 */
class PayrollRun extends Model
{
    protected $table = 'hr_payroll_runs';

    public const STATUSES = [
        'draft' => ['Draft', 'secondary'],
        'approved' => ['Approved', 'info'],
        'paid' => ['Paid', 'success'],
    ];

    protected $fillable = [
        'run_number', 'property_id', 'period', 'currency', 'status', 'total_gross', 'total_deductions', 'total_net',
        'approved_by', 'approved_at', 'paid_at', 'payment_method', 'property_expense_id', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period' => 'date',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'total_gross' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net' => 'decimal:2',
        ];
    }

    public function property(): BelongsTo { return $this->belongsTo(Property::class); }
    public function payslips(): HasMany { return $this->hasMany(Payslip::class); }
    public function expense(): BelongsTo { return $this->belongsTo(PropertyExpense::class, 'property_expense_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function getEmployerLabelAttribute(): string
    {
        return $this->property?->name ?? 'Terra (internal staff)';
    }

    public function getPeriodLabelAttribute(): string
    {
        return $this->period->format('F Y');
    }

    /** Recalculate the run totals from its payslips. */
    public function refreshTotals(): void
    {
        $this->update([
            'total_gross' => $this->payslips()->sum('gross_pay'),
            'total_deductions' => $this->payslips()->sum('total_deductions'),
            'total_net' => $this->payslips()->sum('net_pay'),
        ]);
    }

    public static function nextNumber(): string
    {
        $prefix = 'PAY-RUN-' . now()->format('Y') . '-';
        $last = static::where('run_number', 'like', $prefix . '%')->orderByDesc('id')->value('run_number');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        do {
            $number = $prefix . str_pad((string) $next++, 4, '0', STR_PAD_LEFT);
        } while (static::where('run_number', $number)->exists());

        return $number;
    }
}
