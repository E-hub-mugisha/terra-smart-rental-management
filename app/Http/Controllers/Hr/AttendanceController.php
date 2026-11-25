<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\Hr\HrAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Daily attendance register: one row per active employee, saved in one go. */
class AttendanceController extends Controller
{
    public function __construct(private HrAccess $access)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->access->authorize($user);

        $date = $this->date($request->query('date'));
        $employer = $request->query('employer');

        $query = Employee::active()->with('property:id,name')
            ->whereDate('hire_date', '<=', $date)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date));
        $this->access->applyEmployerFilter($query, $user, $employer);
        $employees = $query->orderBy('property_id')->orderBy('full_name')->get();

        $records = Attendance::whereIn('employee_id', $employees->pluck('id'))->whereDate('date', $date)->get()->keyBy('employee_id');
        $onLeave = LeaveRequest::whereIn('employee_id', $employees->pluck('id'))->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)
            ->pluck('type', 'employee_id');

        return view('hr.attendance.index', [
            'date' => $date,
            'employer' => $employer,
            'employers' => $this->access->employers($user),
            'employees' => $employees,
            'records' => $records,
            'onLeave' => $onLeave,
            'summary' => collect(Attendance::STATUSES)->map(fn ($s, $key) => $records->where('status', $key)->count()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->access->authorize($user);

        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'rows' => ['required', 'array'],
            'rows.*.status' => ['nullable', Rule::in(array_keys(Attendance::STATUSES))],
            'rows.*.check_in' => ['nullable', 'date_format:H:i'],
            'rows.*.check_out' => ['nullable', 'date_format:H:i', 'after_or_equal:rows.*.check_in'],
            'rows.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        $employees = Employee::whereIn('id', array_keys($data['rows']))->get()->keyBy('id');
        $saved = 0;

        DB::transaction(function () use ($data, $employees, $user, &$saved) {
            foreach ($data['rows'] as $employeeId => $row) {
                $employee = $employees->get($employeeId);
                if (! $employee) {
                    continue;
                }
                $this->access->authorizeEmployee($user, $employee);

                if (empty($row['status'])) {
                    // Cleared: remove any mark for that day.
                    Attendance::where('employee_id', $employee->id)->whereDate('date', $data['date'])->delete();
                    continue;
                }

                $worked = in_array($row['status'], ['present', 'late'], true);
                $record = Attendance::where('employee_id', $employee->id)->whereDate('date', $data['date'])->first()
                    ?? new Attendance(['employee_id' => $employee->id, 'date' => $data['date']]);
                $record->fill([
                    'status' => $row['status'],
                    'check_in' => $worked ? ($row['check_in'] ?? null) : null,
                    'check_out' => $worked ? ($row['check_out'] ?? null) : null,
                    'note' => $row['note'] ?? null,
                    'recorded_by' => $user->id,
                ])->save();
                $saved++;
            }
        });

        return redirect()->route('hr.attendance.index', array_filter(['date' => $data['date'], 'employer' => $request->input('employer')]))
            ->with('success', "Attendance saved for {$saved} " . \Illuminate\Support\Str::plural('employee', $saved) . '.');
    }

    private function date(?string $value): Carbon
    {
        try {
            $date = $value ? Carbon::parse($value)->startOfDay() : today();
        } catch (\Throwable) {
            $date = today();
        }

        return $date->gt(today()) ? today() : $date;
    }
}
