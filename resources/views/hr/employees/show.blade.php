@extends('layouts.app')

@php
    use App\Support\Money; use App\Support\Phone;
    use App\Models\Employee; use App\Models\Attendance; use App\Models\LeaveRequest; use App\Models\PayrollRun;
    $days = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');
@endphp

@section('title', $employee->full_name)
@section('page_heading', $employee->full_name)
@section('page_subtitle', $employee->job_title . ' · ' . $employee->employer_label)

@section('content')
@include('hr._nav', ['showEmployerFilter' => false])
@include('hr._errors')

<a href="{{ route('hr.employees.index') }}" class="sx-muted small text-decoration-none d-inline-flex align-items-center gap-1 mb-3"><i class="bi bi-arrow-left"></i> All employees</a>

<div class="sx-card mb-3">
    <div class="sx-card-body d-flex flex-wrap justify-content-between gap-3 align-items-center">
        <div class="d-flex gap-3 align-items-center">
            <span class="sx-avatar lg">{{ \Illuminate\Support\Str::of($employee->full_name)->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}</span>
            <div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <strong style="color:var(--terra-navy,#19265d)">{{ $employee->employee_number }}</strong>
                    <span class="sx-pill {{ $employee->status_tone }}">{{ $employee->status_label }}</span>
                    <span class="sx-pill secondary">{{ Employee::EMPLOYMENT_TYPES[$employee->employment_type] ?? $employee->employment_type }}</span>
                    @if($employee->account_role_label)<span class="sx-pill info"><i class="bi bi-person-check"></i> {{ $employee->account_role_label }} · has login</span>@endif
                </div>
                <div class="small sx-muted">Since {{ $employee->hire_date->format('d M Y') }}{{ $employee->end_date ? ' until ' . $employee->end_date->format('d M Y') : '' }}</div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($employee->phone)<a href="tel:{{ $employee->phone }}" class="sx-btn sx-btn-outline"><i class="bi bi-telephone"></i> Call</a>@endif
            <a href="{{ route('hr.employees.edit', $employee) }}" class="sx-btn sx-btn-outline"><i class="bi bi-pencil"></i> Edit</a>
            <form method="POST" action="{{ route('hr.employees.destroy', $employee) }}" class="m-0"
                  onsubmit="return confirm('Delete this employee? If they have been paid, their payslips are kept in the payroll history.');">
                @csrf @method('DELETE')
                <button class="sx-btn sx-btn-danger"><i class="bi bi-trash3"></i> Delete</button>
            </form>
            @if(auth()->user()->isAdmin())
                <x-purge-button type="employee" :id="$employee->id" what="this employee, their payslips, leave and attendance" class="sx-btn sx-btn-danger" />
            @endif
        </div>
    </div>
</div>

<div class="sx-stats">
    <div class="sx-stat"><div class="sx-label">Monthly base salary</div><div class="sx-value">{{ Money::format($employee->base_salary, $employee->currency) }}</div><div class="sx-sub">{{ Employee::PAYMENT_METHODS[$employee->payment_method] ?? 'Payment method not set' }}</div></div>
    <div class="sx-stat"><div class="sx-label">Annual leave left ({{ $year }})</div><div class="sx-value">{{ $days($leaveBalance) }} <span class="fs-6 fw-normal sx-muted">of {{ $employee->annual_leave_days }} days</span></div><div class="sx-sub">{{ $days($leaveTaken) }} taken</div></div>
    <div class="sx-stat {{ $monthSummary['absent'] ? 'sx-danger' : '' }}"><div class="sx-label">This month</div><div class="sx-value">{{ $monthSummary['present'] + $monthSummary['late'] }} <span class="fs-6 fw-normal sx-muted">days at work</span></div><div class="sx-sub">{{ $monthSummary['late'] }} late · {{ $monthSummary['absent'] }} absent · {{ $monthSummary['leave'] }} on leave</div></div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="sx-card mb-3">
            <div class="sx-card-head"><h2>Details</h2></div>
            <div class="sx-card-body">
                <dl class="sx-dl">
                    <dt>Employer</dt><dd>{{ $employee->employer_label }}</dd>
                    @if($employee->user)<dt>Platform account</dt><dd>{{ $employee->user->email }} ({{ $employee->account_role_label }})</dd>@endif
                    <dt>Job</dt><dd>{{ $employee->job_title }}{{ $employee->department ? ' · ' . $employee->department : '' }}</dd>
                    <dt>Phone</dt><dd>{{ $employee->phone ? Phone::display($employee->phone) : '—' }}</dd>
                    <dt>Email</dt><dd>{{ $employee->email ?: '—' }}</dd>
                    <dt>National ID</dt><dd>{{ $employee->national_id ?: '—' }}</dd>
                    <dt>Date of birth</dt><dd>{{ $employee->date_of_birth?->format('d M Y') ?? '—' }}</dd>
                    <dt>Bank / wallet</dt><dd>{{ collect([$employee->bank_name, $employee->account_number])->filter()->implode(' · ') ?: '—' }}</dd>
                    <dt>Emergency contact</dt><dd>{{ collect([$employee->emergency_contact_name, $employee->emergency_contact_phone ? Phone::display($employee->emergency_contact_phone) : null])->filter()->implode(' · ') ?: '—' }}</dd>
                    @if($employee->notes)<dt>Notes</dt><dd>{{ $employee->notes }}</dd>@endif
                </dl>
            </div>
        </div>

        <div class="sx-card mb-3">
            <div class="sx-card-head"><h2>Record leave</h2></div>
            <form method="POST" action="{{ route('hr.leave.store') }}" class="sx-card-body row g-2">
                @csrf
                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                <div class="col-12">
                    <label for="lvType" class="form-label small">Type</label>
                    <select id="lvType" name="type" class="form-select form-select-sm">
                        @foreach($leaveTypes as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                    </select>
                </div>
                <div class="col-6"><label for="lvStart" class="form-label small">From</label><input id="lvStart" type="date" name="start_date" required class="form-control form-control-sm"></div>
                <div class="col-6"><label for="lvEnd" class="form-label small">To</label><input id="lvEnd" type="date" name="end_date" required class="form-control form-control-sm"></div>
                <div class="col-12"><label for="lvReason" class="form-label small">Reason <span class="sx-muted">(optional)</span></label><input id="lvReason" name="reason" maxlength="1000" class="form-control form-control-sm"></div>
                <div class="col-12 form-check ms-1"><input type="checkbox" class="form-check-input" id="lvApprove" name="approve_now" value="1"><label class="form-check-label small" for="lvApprove">Approve now</label></div>
                <div class="col-12 d-grid"><button class="sx-btn sx-btn-primary justify-content-center">Save leave</button></div>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="sx-card mb-3">
            <div class="sx-card-head"><h2>Leave</h2><a href="{{ route('hr.leave.index', ['status' => 'all']) }}" class="small">All leave</a></div>
            <div class="table-responsive">
                <table class="sx-table">
                    <thead><tr><th>Type</th><th>Dates</th><th class="sx-num">Days</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($leaveRequests as $leave)
                        <tr>
                            <td class="small">{{ LeaveRequest::TYPES[$leave->type] ?? $leave->type }}</td>
                            <td class="small">{{ $leave->start_date->format('d M') }} – {{ $leave->end_date->format('d M Y') }}</td>
                            <td class="sx-num">{{ $days($leave->days) }}</td>
                            <td><span class="sx-pill {{ LeaveRequest::STATUSES[$leave->status][1] }}">{{ LeaveRequest::STATUSES[$leave->status][0] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="sx-empty">No leave recorded.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sx-card mb-3">
            <div class="sx-card-head"><h2>Payslips</h2></div>
            <div class="table-responsive">
                <table class="sx-table">
                    <thead><tr><th>Month</th><th class="sx-num">Gross</th><th class="sx-num">Deductions</th><th class="sx-num">Net pay</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($payslips as $slip)
                        <tr>
                            <td class="small"><a href="{{ route('hr.payroll.show', $slip->run) }}">{{ $slip->run->period_label }}</a></td>
                            <td class="sx-num">{{ Money::format($slip->gross_pay, $slip->run->currency) }}</td>
                            <td class="sx-num">{{ Money::format($slip->total_deductions, $slip->run->currency) }}</td>
                            <td class="sx-num fw-semibold">{{ Money::format($slip->net_pay, $slip->run->currency) }}</td>
                            <td><span class="sx-pill {{ PayrollRun::STATUSES[$slip->run->status][1] }}">{{ PayrollRun::STATUSES[$slip->run->status][0] }}</span></td>
                            <td class="text-end"><a href="{{ route('hr.payslips.show', $slip) }}" target="_blank" class="btn btn-sm btn-light" title="Payslip" aria-label="Payslip for {{ $slip->run->period_label }}"><i class="bi bi-file-earmark-text"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="sx-empty">No payslips yet.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sx-card">
            <div class="sx-card-head"><h2>Recent attendance</h2><a href="{{ route('hr.attendance.index') }}" class="small">Attendance register</a></div>
            <div class="sx-card-body d-flex flex-wrap gap-1">
                @forelse($attendance->sortBy('date') as $a)
                    <span class="sx-pill {{ Attendance::STATUSES[$a->status][1] }}" title="{{ Attendance::STATUSES[$a->status][0] }}{{ $a->check_in ? ' · in ' . substr($a->check_in, 0, 5) : '' }}">{{ $a->date->format('d M') }}</span>
                @empty
                    <span class="sx-muted small">No attendance marked yet.</span>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
