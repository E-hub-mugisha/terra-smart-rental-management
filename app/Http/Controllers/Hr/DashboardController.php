<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Services\Hr\HrAccess;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** HR overview: headcount, today's attendance, pending leave and recent payroll. */
class DashboardController extends Controller
{
    public function __construct(private HrAccess $access)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->access->authorize($user);
        $employer = $request->query('employer');

        $employees = Employee::query();
        $this->access->applyEmployerFilter($employees, $user, $employer);
        $ids = (clone $employees)->pluck('id');
        $activeIds = (clone $employees)->active()->pluck('id');

        $runs = PayrollRun::with('property:id,name');
        $this->access->applyEmployerFilter($runs, $user, $employer);
        $unpaidRuns = (clone $runs)->where('status', '!=', 'paid')->count();

        $today = Attendance::whereIn('employee_id', $activeIds)->whereDate('date', today())->get();

        return view('hr.dashboard', [
            'employer' => $employer,
            'employers' => $this->access->employers($user),
            'headcount' => $activeIds->count(),
            'byTitle' => (clone $employees)->active()->selectRaw('job_title, count(*) as n')->groupBy('job_title')->orderByDesc('n')->limit(6)->pluck('n', 'job_title'),
            'monthlySalaries' => (clone $employees)->active()->selectRaw('currency, sum(base_salary) as total')->groupBy('currency')->pluck('total', 'currency'),
            'attendanceToday' => collect(Attendance::STATUSES)->map(fn ($s, $key) => $today->where('status', $key)->count()),
            'unmarkedToday' => max(0, $activeIds->count() - $today->count()),
            'pendingLeaveCount' => LeaveRequest::whereIn('employee_id', $ids)->where('status', 'pending')->count(),
            'pendingLeave' => LeaveRequest::with('employee:id,full_name')->whereIn('employee_id', $ids)->where('status', 'pending')->orderBy('start_date')->limit(8)->get(),
            'onLeaveToday' => LeaveRequest::whereIn('employee_id', $activeIds)->where('status', 'approved')
                ->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->count(),
            'recentRuns' => $runs->latest('period')->latest('id')->limit(6)->get(),
            'unpaidRuns' => $unpaidRuns,
        ]);
    }
}
