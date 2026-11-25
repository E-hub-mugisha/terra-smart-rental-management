<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A leave request for an employee; approved annual leave reduces the yearly balance. */
class LeaveRequest extends Model
{
    protected $table = 'hr_leave_requests';

    public const TYPES = [
        'annual' => 'Annual leave',
        'sick' => 'Sick leave',
        'maternity' => 'Maternity leave',
        'paternity' => 'Paternity leave',
        'compassionate' => 'Compassionate leave',
        'unpaid' => 'Unpaid leave',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'pending' => ['Pending', 'warning'],
        'approved' => ['Approved', 'success'],
        'rejected' => ['Rejected', 'danger'],
        'cancelled' => ['Cancelled', 'secondary'],
    ];

    protected $fillable = [
        'employee_id', 'type', 'start_date', 'end_date', 'days', 'reason', 'status',
        'reviewed_by', 'reviewed_at', 'decision_note', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'days' => 'decimal:1',
            'reviewed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class)->withTrashed(); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }

    /** Working days (Monday–Friday) between two dates, inclusive. */
    public static function workingDays(CarbonInterface $start, CarbonInterface $end): int
    {
        return collect(CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()))
            ->reject(fn (CarbonInterface $day) => $day->isWeekend())
            ->count();
    }
}
