<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\Hr\HrAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Leave requests recorded by the employer, then approved or rejected. */
class LeaveController extends Controller
{
    public function __construct(private HrAccess $access)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->access->authorize($user);

        $employeeQuery = Employee::query();
        $this->access->applyEmployerFilter($employeeQuery, $user, $request->query('employer'));
        $employeeIds = $employeeQuery->pluck('id');

        $status = $request->query('status', 'pending');

        return view('hr.leave.index', [
            'requests' => LeaveRequest::with(['employee.property:id,name', 'reviewer:id,name'])
                ->whereIn('employee_id', $employeeIds)
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->when($request->filled('type'), fn ($q) => $q->where('type', $request->query('type')))
                ->orderByRaw("status = 'pending' desc")->latest('start_date')
                ->paginate(25)->withQueryString(),
            'status' => $status,
            'counts' => LeaveRequest::whereIn('employee_id', $employeeIds)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
            'employers' => $this->access->employers($user),
            'employees' => Employee::active()->whereIn('id', $employeeIds)->orderBy('full_name')->get(['id', 'full_name', 'job_title']),
            'onLeaveToday' => LeaveRequest::with('employee:id,full_name')->whereIn('employee_id', $employeeIds)->where('status', 'approved')
                ->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->access->authorize($user);

        $data = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('hr_employees', 'id')->whereNull('deleted_at')],
            'type' => ['required', Rule::in(array_keys(LeaveRequest::TYPES))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'approve_now' => ['nullable', 'boolean'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $this->access->authorizeEmployee($user, $employee);

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $days = LeaveRequest::workingDays($start, $end);

        if ($days === 0) {
            throw ValidationException::withMessages(['end_date' => 'The selected dates fall only on a weekend.']);
        }

        $overlap = LeaveRequest::where('employee_id', $employee->id)->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['start_date' => "{$employee->full_name} already has leave booked in that period."]);
        }

        $approveNow = $request->boolean('approve_now');
        if ($approveNow) {
            $this->assertBalance($employee, $data['type'], $start, $days);
        }

        LeaveRequest::create([
            'employee_id' => $employee->id,
            'type' => $data['type'],
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'days' => $days,
            'reason' => $data['reason'] ?? null,
            'status' => $approveNow ? 'approved' : 'pending',
            'reviewed_by' => $approveNow ? $user->id : null,
            'reviewed_at' => $approveNow ? now() : null,
            'created_by' => $user->id,
        ]);

        return back()->with('success', "Leave of {$days} working " . \Illuminate\Support\Str::plural('day', $days) . " recorded for {$employee->full_name}" . ($approveNow ? ' and approved.' : '. It is waiting for approval.'));
    }

    public function decide(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $user = $request->user();
        $this->access->authorizeEmployee($user, $leave->employee);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject', 'cancel'])],
            'decision_note' => ['nullable', 'string', 'max:500'],
        ]);

        $allowed = match ($data['decision']) {
            'approve', 'reject' => $leave->status === 'pending',
            'cancel' => in_array($leave->status, ['pending', 'approved'], true),
        };
        if (! $allowed) {
            return back()->with('error', 'This leave request has already been decided.');
        }

        if ($data['decision'] === 'approve') {
            $this->assertBalance($leave->employee, $leave->type, $leave->start_date, (float) $leave->days);
        }

        $leave->update([
            'status' => ['approve' => 'approved', 'reject' => 'rejected', 'cancel' => 'cancelled'][$data['decision']],
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'decision_note' => $data['decision_note'] ?? null,
        ]);

        return back()->with('success', 'Leave ' . ['approve' => 'approved', 'reject' => 'rejected', 'cancel' => 'cancelled'][$data['decision']] . '.');
    }

    public function destroy(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $this->access->authorizeEmployee($request->user(), $leave->employee);
        $leave->delete();

        return back()->with('success', 'Leave request deleted.');
    }

    /** Annual leave cannot go beyond the yearly entitlement. */
    private function assertBalance(Employee $employee, string $type, Carbon $start, float $days): void
    {
        if ($type !== 'annual') {
            return;
        }

        $balance = $employee->annualLeaveBalance($start->year);
        if ($days > $balance) {
            throw ValidationException::withMessages([
                'leave' => "{$employee->full_name} has {$balance} annual leave " . \Illuminate\Support\Str::plural('day', (int) $balance) . " left for {$start->year}; this request needs {$days}.",
            ]);
        }
    }
}
