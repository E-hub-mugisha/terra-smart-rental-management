<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One employee's attendance for one day. */
class Attendance extends Model
{
    protected $table = 'hr_attendance';

    public const STATUSES = [
        'present' => ['Present', 'success'],
        'late' => ['Late', 'warning'],
        'absent' => ['Absent', 'danger'],
        'leave' => ['On leave', 'info'],
        'off' => ['Day off', 'secondary'],
    ];

    protected $fillable = ['employee_id', 'date', 'status', 'check_in', 'check_out', 'note', 'recorded_by'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}
