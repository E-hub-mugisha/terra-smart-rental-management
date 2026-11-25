<?php

namespace App\Services\Hr;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\PropertyExpense;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Monthly payroll: a run per employer and month, pre-filled from each
 * employee's base salary; deductions are entered by the employer.
 */
class PayrollService
{
    /** Start a draft run with a payslip for every employee on the books that month. */
    public function create(?int $propertyId, CarbonInterface $month, string $currency, int $userId): PayrollRun
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $exists = PayrollRun::whereDate('period', $start->toDateString())->where('currency', $currency)
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId), fn ($q) => $q->whereNull('property_id'))
            ->exists();
        if ($exists) {
            $this->fail('A payroll for this employer, month and currency already exists.');
        }

        $employees = Employee::query()
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId), fn ($q) => $q->whereNull('property_id'))
            ->where('currency', $currency)
            ->where('status', '!=', 'terminated')
            ->whereDate('hire_date', '<=', $end)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $start))
            ->orderBy('full_name')
            ->get();

        if ($employees->isEmpty()) {
            $this->fail('No employees paid in ' . $currency . ' were on the books for ' . $start->format('F Y') . '.');
        }

        return DB::transaction(function () use ($propertyId, $start, $end, $currency, $userId, $employees) {
            $run = PayrollRun::create([
                'run_number' => PayrollRun::nextNumber(),
                'property_id' => $propertyId,
                'period' => $start->toDateString(),
                'currency' => $currency,
                'status' => 'draft',
                'created_by' => $userId,
            ]);

            $absences = Attendance::whereIn('employee_id', $employees->pluck('id'))
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->where('status', 'absent')
                ->selectRaw('employee_id, count(*) as days')->groupBy('employee_id')
                ->pluck('days', 'employee_id');

            foreach ($employees as $employee) {
                $run->payslips()->make([
                    'employee_id' => $employee->id,
                    'base_salary' => $employee->base_salary,
                    'days_absent' => (int) ($absences[$employee->id] ?? 0),
                ])->calculate()->save();
            }

            $run->refreshTotals();

            return $run;
        });
    }

    /** Save edited payslip lines on a draft run. */
    public function updateLines(PayrollRun $run, array $lines): void
    {
        $this->expectStatus($run, 'draft', 'Only a draft payroll can be edited. Reopen it first.');

        DB::transaction(function () use ($run, $lines) {
            foreach ($run->payslips as $payslip) {
                $line = $lines[$payslip->id] ?? null;
                if (! $line) {
                    continue;
                }
                $payslip->fill(collect($line)->only(['base_salary', 'allowances', 'overtime', 'paye', 'pension', 'other_deductions', 'note'])->all())
                    ->calculate();
                if ((float) $payslip->net_pay < 0) {
                    $this->fail('Deductions for ' . $payslip->employee->full_name . ' are higher than their gross pay.');
                }
                $payslip->save();
            }
            $run->refreshTotals();
        });
    }

    public function removeLine(PayrollRun $run, Payslip $payslip): void
    {
        $this->expectStatus($run, 'draft', 'Only a draft payroll can be edited.');
        abort_unless($payslip->payroll_run_id === $run->id, 404);

        $payslip->delete();
        $run->refreshTotals();
    }

    public function approve(PayrollRun $run, int $userId): void
    {
        $this->expectStatus($run, 'draft', 'Only a draft payroll can be approved.');
        if (! $run->payslips()->exists()) {
            $this->fail('This payroll has no payslips.');
        }

        $run->update(['status' => 'approved', 'approved_by' => $userId, 'approved_at' => now()]);
    }

    public function reopen(PayrollRun $run): void
    {
        $this->expectStatus($run, 'approved', 'Only an approved, unpaid payroll can be reopened.');

        $run->update(['status' => 'draft', 'approved_by' => null, 'approved_at' => null]);
    }

    /** Mark as paid; a property's payroll is booked as a "Salaries" property expense. */
    public function markPaid(PayrollRun $run, string $method, CarbonInterface $paidOn, int $userId): void
    {
        $this->expectStatus($run, 'approved', 'Approve the payroll before marking it as paid.');

        DB::transaction(function () use ($run, $method, $paidOn, $userId) {
            $expenseId = null;

            if ($run->property_id) {
                $expenseId = PropertyExpense::create([
                    'property_id' => $run->property_id,
                    'recorded_by' => $userId,
                    'expense_number' => $this->expenseNumber(),
                    'category' => 'Salaries',
                    'title' => 'Staff salaries – ' . $run->period_label,
                    'description' => 'Net pay for ' . $run->payslips()->count() . ' employee(s), payroll ' . $run->run_number . '.',
                    'amount' => $run->total_net,
                    'currency' => $run->currency,
                    'expense_date' => $paidOn->toDateString(),
                    'payment_method' => \App\Models\Employee::PAYMENT_METHODS[$method] ?? 'Other',
                    'reference_number' => $run->run_number,
                    'status' => 'paid',
                ])->id;
            }

            $run->update([
                'status' => 'paid',
                'paid_at' => $paidOn,
                'payment_method' => $method,
                'property_expense_id' => $expenseId,
            ]);
        });
    }

    /** Delete a run that has not been paid. */
    public function delete(PayrollRun $run): void
    {
        if ($run->status === 'paid') {
            $this->fail('A paid payroll is part of the books and cannot be deleted.');
        }

        $run->delete();
    }

    private function expectStatus(PayrollRun $run, string $status, string $message): void
    {
        if ($run->status !== $status) {
            $this->fail($message);
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['payroll' => $message]);
    }

    private function expenseNumber(): string
    {
        do {
            $number = 'EXP-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(5));
        } while (PropertyExpense::withTrashed()->where('expense_number', $number)->exists());

        return $number;
    }
}
