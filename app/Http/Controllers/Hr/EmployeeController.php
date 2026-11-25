<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Rules\InternationalPhone;
use App\Services\Hr\HrAccess;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public const CURRENCIES = ['RWF', 'USD', 'EUR', 'KES', 'UGX', 'TZS'];

    public function __construct(private HrAccess $access)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->access->authorize($user);

        $query = Employee::with('property:id,name', 'user:id,role');
        $this->access->applyEmployerFilter($query, $user, $request->query('employer'));

        $employees = $query
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('full_name', 'like', '%' . $request->query('q') . '%')
                ->orWhere('job_title', 'like', '%' . $request->query('q') . '%')
                ->orWhere('employee_number', 'like', '%' . $request->query('q') . '%')))
            ->orderByRaw("status = 'active' desc")->orderBy('full_name')
            ->paginate(25)->withQueryString();

        return view('hr.employees.index', [
            'employees' => $employees,
            'employers' => $this->access->employers($user),
            'notYetEmployees' => $this->notYetEmployees($user),
        ]);
    }

    /** Linkable team members who have no HR record yet with their suggested employer. */
    private function notYetEmployees($user)
    {
        $accounts = $this->access->linkableAccounts($user);
        $linked = Employee::whereIn('user_id', $accounts->keys())->get(['user_id', 'property_id'])
            ->map(fn ($e) => $e->user_id . ':' . ($e->property_id ?? 'terra'))->flip();

        return $accounts->reject(fn ($row) => $linked->has($row['user']->id . ':' . ($row['employer'] ?? 'terra')))
            ->values();
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $this->access->authorize($user);

        $employee = new Employee([
            'hire_date' => today(), 'currency' => 'RWF', 'employment_type' => 'permanent',
            'annual_leave_days' => 18, 'status' => 'active',
            'property_id' => $request->integer('property_id') ?: null,
        ]);

        // "Add to HR" from a team member: prefill from their account.
        if ($request->filled('user_id')) {
            $account = $this->access->linkableAccounts($user)->get($request->integer('user_id'));
            abort_unless($account, 403);
            $employee->fill([
                'user_id' => $account['user']->id,
                'full_name' => $account['user']->name,
                'email' => $account['user']->email,
                'phone' => $account['user']->contactPhone(),
                'job_title' => Employee::LINKABLE_ROLES[$account['user']->role] ?? null,
                'property_id' => $request->has('property_id') ? ($request->integer('property_id') ?: null) : $account['employer'],
            ]);
        }

        return view('hr.employees.form', $this->formData($request, $employee));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->access->authorize($request->user());
        $data = $this->validated($request);
        $this->access->authorizeEmployer($request->user(), $data['property_id']);
        $this->checkAccountLink($request, $data);

        $employee = Employee::create($data + [
            'employee_number' => Employee::nextNumber(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('hr.employees.show', $employee)->with('success', "{$employee->full_name} was added.");
    }

    public function show(Request $request, Employee $employee): View
    {
        $this->access->authorizeEmployee($request->user(), $employee);
        $employee->load('property:id,name', 'creator:id,name');
        $year = (int) $request->query('year', now()->year);

        $monthStart = today()->startOfMonth();
        $monthAttendance = $employee->attendance()->whereBetween('date', [$monthStart, today()])->get();

        return view('hr.employees.show', [
            'employee' => $employee,
            'year' => $year,
            'leaveTaken' => $employee->annualLeaveTaken($year),
            'leaveBalance' => $employee->annualLeaveBalance($year),
            'leaveRequests' => $employee->leaveRequests()->latest('start_date')->limit(20)->get(),
            'attendance' => $employee->attendance()->latest('date')->limit(31)->get(),
            'monthSummary' => collect(Attendance::STATUSES)->map(fn ($s, $key) => $monthAttendance->where('status', $key)->count()),
            'payslips' => $employee->payslips()->with('run')->latest('id')->limit(12)->get(),
            'leaveTypes' => LeaveRequest::TYPES,
        ]);
    }

    public function edit(Request $request, Employee $employee): View
    {
        $this->access->authorizeEmployee($request->user(), $employee);

        return view('hr.employees.form', $this->formData($request, $employee));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->access->authorizeEmployee($request->user(), $employee);
        $data = $this->validated($request);
        $this->access->authorizeEmployer($request->user(), $data['property_id']);
        $this->checkAccountLink($request, $data, $employee);

        $employee->update($data);

        return redirect()->route('hr.employees.show', $employee)->with('success', 'Employee details saved.');
    }

    /**
     * Remove an employee. Anyone without payslips is deleted completely;
     * someone who has been paid is kept for the payroll history and marked as left.
     */
    public function destroy(Request $request, Employee $employee): RedirectResponse
    {
        $this->access->authorizeEmployee($request->user(), $employee);
        $name = $employee->full_name;

        if ($employee->payslips()->exists()) {
            $employee->update(['status' => 'terminated', 'end_date' => $employee->end_date ?? today()]);
            $employee->delete();

            return redirect()->route('hr.employees.index')
                ->with('success', "{$name} was removed. Their payslips are kept in the payroll history.");
        }

        $employee->forceDelete();

        return redirect()->route('hr.employees.index')->with('success', "{$name} was deleted.");
    }

    /**
     * A linked account must be one the user may add, field staff can only be
     * Terra staff, and nobody is on the same employer's books twice.
     */
    private function checkAccountLink(Request $request, array $data, ?Employee $current = null): void
    {
        if (empty($data['user_id'])) {
            return;
        }

        $unchanged = $current && (int) $current->user_id === (int) $data['user_id'];
        $account = $this->access->linkableAccounts($request->user())->get((int) $data['user_id']);
        if (! $account && ! $unchanged) {
            throw \Illuminate\Validation\ValidationException::withMessages(['user_id' => 'You can only add managers, accountants and agents who work on your properties.']);
        }

        $role = $account['user']->role ?? $current?->user?->role;
        if ($role === 'field_staff' && $data['property_id'] !== null) {
            throw \Illuminate\Validation\ValidationException::withMessages(['property_id' => 'Field staff work for Terra: choose "Terra (internal staff)" as the employer.']);
        }

        $duplicate = Employee::where('user_id', $data['user_id'])
            ->when($data['property_id'], fn ($q) => $q->where('property_id', $data['property_id']), fn ($q) => $q->whereNull('property_id'))
            ->when($current, fn ($q) => $q->whereKeyNot($current->id))
            ->exists();
        if ($duplicate) {
            throw \Illuminate\Validation\ValidationException::withMessages(['user_id' => 'This person is already an employee of that employer.']);
        }
    }

    private function formData(Request $request, Employee $employee): array
    {
        $accounts = $this->access->linkableAccounts($request->user());

        // Keep an existing link visible even if the account is no longer linkable by this user.
        if ($employee->user_id && ! $accounts->has($employee->user_id) && $employee->user) {
            $accounts->put($employee->user_id, ['user' => $employee->user, 'property_ids' => [], 'employer' => $employee->property_id]);
        }

        return [
            'employee' => $employee,
            'employers' => $this->access->employers($request->user()),
            'currencies' => self::CURRENCIES,
            'accounts' => $accounts,
        ];
    }

    private function validated(Request $request): array
    {
        Phone::normalizeRequest($request, ['phone', 'emergency_contact_phone']);

        $data = $request->validate([
            // Only admins can employ Terra's own staff (no property).
            'property_id' => [$request->user()->isAdmin() ? 'nullable' : 'required', 'integer', Rule::exists('properties', 'id')],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->whereIn('role', array_keys(Employee::LINKABLE_ROLES))],
            'full_name' => ['required', 'string', 'max:150'],
            'job_title' => ['required', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30', new InternationalPhone()],
            'email' => ['nullable', 'email', 'max:150'],
            'national_id' => ['nullable', 'string', 'max:40'],
            'gender' => ['nullable', Rule::in(['female', 'male', 'other'])],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'hire_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
            'employment_type' => ['required', Rule::in(array_keys(Employee::EMPLOYMENT_TYPES))],
            'base_salary' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'currency' => ['required', Rule::in(self::CURRENCIES)],
            'payment_method' => ['nullable', Rule::in(array_keys(Employee::PAYMENT_METHODS))],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:60'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30', new InternationalPhone()],
            'annual_leave_days' => ['required', 'integer', 'min:0', 'max:60'],
            'status' => ['required', Rule::in(array_keys(Employee::STATUSES))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [], ['property_id' => 'employer']);

        $data['property_id'] = $data['property_id'] ?? null;
        $data['user_id'] = $data['user_id'] ?? null;

        return $data;
    }
}
