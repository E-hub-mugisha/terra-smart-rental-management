<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One employee's pay for one payroll run. */
class Payslip extends Model
{
    protected $table = 'hr_payslips';

    protected $fillable = [
        'payroll_run_id', 'employee_id', 'base_salary', 'allowances', 'overtime', 'gross_pay',
        'paye', 'pension', 'other_deductions', 'total_deductions', 'net_pay', 'days_absent', 'note',
    ];

    protected function casts(): array
    {
        return collect(['base_salary', 'allowances', 'overtime', 'gross_pay', 'paye', 'pension', 'other_deductions', 'total_deductions', 'net_pay'])
            ->mapWithKeys(fn ($c) => [$c => 'decimal:2'])->all() + ['days_absent' => 'integer'];
    }

    public function run(): BelongsTo { return $this->belongsTo(PayrollRun::class, 'payroll_run_id'); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class)->withTrashed(); }

    /** Fill gross, total deductions and net pay from the line amounts. */
    public function calculate(): static
    {
        $gross = round((float) $this->base_salary + (float) $this->allowances + (float) $this->overtime, 2);
        $deductions = round((float) $this->paye + (float) $this->pension + (float) $this->other_deductions, 2);

        $this->gross_pay = $gross;
        $this->total_deductions = $deductions;
        $this->net_pay = round($gross - $deductions, 2);

        return $this;
    }
}
