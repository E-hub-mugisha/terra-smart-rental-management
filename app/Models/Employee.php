<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * HR employee. With a property: property staff (caretaker, guard, cleaner…).
 * Without one: Terra's own staff, visible to Terra admins only.
 */
class Employee extends Model
{
    use SoftDeletes;

    protected $table = 'hr_employees';

    public const STATUSES = ['active' => 'Active', 'suspended' => 'Suspended', 'terminated' => 'Left'];

    public const EMPLOYMENT_TYPES = [
        'permanent' => 'Permanent',
        'fixed_term' => 'Fixed-term contract',
        'part_time' => 'Part-time',
        'casual' => 'Casual / daily',
    ];

    public const PAYMENT_METHODS = ['bank' => 'Bank transfer', 'mobile_money' => 'Mobile money', 'cash' => 'Cash'];

    /** Common roles, offered as suggestions in the form. */
    public const JOB_TITLES = ['Caretaker', 'Security guard', 'Cleaner', 'Gardener', 'Electrician', 'Plumber', 'Receptionist', 'Accountant', 'Property officer'];

    protected $fillable = [
        'employee_number', 'property_id', 'user_id', 'full_name', 'job_title', 'department', 'phone', 'email', 'national_id',
        'gender', 'date_of_birth', 'hire_date', 'end_date', 'employment_type', 'base_salary', 'currency',
        'payment_method', 'bank_name', 'account_number', 'emergency_contact_name', 'emergency_contact_phone',
        'annual_leave_days', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'end_date' => 'date',
            'base_salary' => 'decimal:2',
            'annual_leave_days' => 'integer',
        ];
    }

    /** Platform roles that can also be employees, with the job title suggested for each. */
    public const LINKABLE_ROLES = [
        'manager' => 'Property manager',
        'accountant' => 'Accountant',
        'agent' => 'Real estate agent',
        'field_staff' => 'Field staff',
    ];

    public function property(): BelongsTo { return $this->belongsTo(Property::class); }
    /** The login account, when this employee is also a platform user. */
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function attendance(): HasMany { return $this->hasMany(Attendance::class); }
    public function leaveRequests(): HasMany { return $this->hasMany(LeaveRequest::class); }
    public function payslips(): HasMany { return $this->hasMany(Payslip::class); }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /** Employees whose employer the user may manage (admins: all, others: their properties only). */
    public function scopeVisibleTo(Builder $query, ?\Illuminate\Support\Collection $propertyIds): Builder
    {
        return $propertyIds === null ? $query : $query->whereIn('property_id', $propertyIds);
    }

    public function isInternal(): bool
    {
        return $this->property_id === null;
    }

    public function getEmployerLabelAttribute(): string
    {
        return $this->property?->name ?? 'Terra (internal staff)';
    }

    /** e.g. "Accountant" for a linked accountant account; null when not linked. */
    public function getAccountRoleLabelAttribute(): ?string
    {
        return $this->user ? (self::LINKABLE_ROLES[$this->user->role] ?? ucfirst(str_replace('_', ' ', $this->user->role))) : null;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'suspended' => 'warning',
            default => 'secondary',
        };
    }

    /** Approved annual-leave working days taken in a calendar year. */
    public function annualLeaveTaken(int $year): float
    {
        return (float) $this->leaveRequests()
            ->where('type', 'annual')->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->sum('days');
    }

    public function annualLeaveBalance(int $year): float
    {
        return max(0, $this->annual_leave_days - $this->annualLeaveTaken($year));
    }

    public static function nextNumber(): string
    {
        $prefix = 'EMP-';
        $last = static::withTrashed()->where('employee_number', 'like', $prefix . '%')->orderByDesc('id')->value('employee_number');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        do {
            $number = $prefix . str_pad((string) $next++, 4, '0', STR_PAD_LEFT);
        } while (static::withTrashed()->where('employee_number', $number)->exists());

        return $number;
    }
}
