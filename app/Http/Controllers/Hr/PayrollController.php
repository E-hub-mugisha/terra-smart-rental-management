<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Services\Hr\HrAccess;
use App\Services\Hr\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(private HrAccess $access, private PayrollService $payroll)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->access->authorize($user);

        $query = PayrollRun::with('property:id,name')->withCount('payslips');
        $this->access->applyEmployerFilter($query, $user, $request->query('employer'));

        return view('hr.payroll.index', [
            'runs' => $query->latest('period')->latest('id')->paginate(20)->withQueryString(),
            'employers' => $this->access->employers($user),
            'currencies' => EmployeeController::CURRENCIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->access->authorize($user);

        $data = $request->validate([
            'property_id' => [$user->isAdmin() ? 'nullable' : 'required', 'integer', Rule::exists('properties', 'id')],
            'month' => ['required', 'date_format:Y-m'],
            'currency' => ['required', Rule::in(EmployeeController::CURRENCIES)],
        ], [], ['property_id' => 'employer']);

        $propertyId = $data['property_id'] ?? null;
        $this->access->authorizeEmployer($user, $propertyId);

        $run = $this->payroll->create($propertyId, Carbon::createFromFormat('Y-m-d', $data['month'] . '-01'), $data['currency'], $user->id);

        return redirect()->route('hr.payroll.show', $run)->with('success', 'Draft payroll created. Check the amounts, add allowances and deductions, then approve it.');
    }

    public function show(Request $request, PayrollRun $run): View
    {
        $this->authorizeRun($request, $run);
        $run->load(['property:id,name', 'expense:id,expense_number', 'approver:id,name', 'creator:id,name']);

        return view('hr.payroll.show', [
            'run' => $run,
            'payslips' => $run->payslips()->with('employee:id,full_name,job_title,employee_number,payment_method')->get()
                ->sortBy(fn ($p) => $p->employee?->full_name)->values(),
            'paymentMethods' => Employee::PAYMENT_METHODS,
        ]);
    }

    public function update(Request $request, PayrollRun $run): RedirectResponse
    {
        $this->authorizeRun($request, $run);

        $amount = ['nullable', 'numeric', 'min:0', 'max:1000000000'];
        $data = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.base_salary' => $amount,
            'lines.*.allowances' => $amount,
            'lines.*.overtime' => $amount,
            'lines.*.paye' => $amount,
            'lines.*.pension' => $amount,
            'lines.*.other_deductions' => $amount,
            'lines.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        $lines = collect($data['lines'])->map(fn ($line) => collect($line)->map(fn ($v, $k) => $k === 'note' ? $v : (float) ($v ?? 0))->all())->all();
        $this->payroll->updateLines($run, $lines);

        return back()->with('success', 'Payroll amounts saved.');
    }

    public function removeLine(Request $request, PayrollRun $run, Payslip $payslip): RedirectResponse
    {
        $this->authorizeRun($request, $run);
        $this->payroll->removeLine($run, $payslip);

        return back()->with('success', 'Employee removed from this payroll.');
    }

    public function approve(Request $request, PayrollRun $run): RedirectResponse
    {
        $this->authorizeRun($request, $run);
        $this->payroll->approve($run, $request->user()->id);

        return back()->with('success', 'Payroll approved. Mark it as paid once the salaries have been sent.');
    }

    public function reopen(Request $request, PayrollRun $run): RedirectResponse
    {
        $this->authorizeRun($request, $run);
        $this->payroll->reopen($run);

        return back()->with('success', 'Payroll reopened for editing.');
    }

    public function pay(Request $request, PayrollRun $run): RedirectResponse
    {
        $this->authorizeRun($request, $run);
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(array_keys(Employee::PAYMENT_METHODS))],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $this->payroll->markPaid($run, $data['payment_method'], Carbon::parse($data['paid_on']), $request->user()->id);

        return back()->with('success', $run->property_id
            ? 'Payroll marked as paid and recorded as a salaries expense for ' . $run->property?->name . '.'
            : 'Payroll marked as paid.');
    }

    public function destroy(Request $request, PayrollRun $run): RedirectResponse
    {
        $this->authorizeRun($request, $run);
        $this->payroll->delete($run);

        return redirect()->route('hr.payroll.index')->with('success', "Payroll {$run->run_number} deleted.");
    }

    /** Printable payslip (save as PDF from the browser). */
    public function payslip(Request $request, Payslip $payslip): View
    {
        $payslip->load(['run.property', 'employee']);
        $this->authorizeRun($request, $payslip->run);

        return view('hr.payroll.payslip', ['payslip' => $payslip, 'run' => $payslip->run, 'employee' => $payslip->employee]);
    }

    private function authorizeRun(Request $request, PayrollRun $run): void
    {
        $this->access->authorizeEmployer($request->user(), $run->property_id);
    }
}
