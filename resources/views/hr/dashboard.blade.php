@extends('layouts.app')

@section('title', 'HR')
@section('page_heading', 'Human resources')
@section('page_subtitle', 'Staff, attendance, leave and payroll for your properties' . (auth()->user()->isAdmin() ? ' and for Terra.' : '.'))

@php use App\Support\Money; use App\Models\Attendance; @endphp

@section('content')
@include('hr._nav')

<div class="sx-stats">
    <a href="{{ route('hr.employees.index', array_filter(['employer' => $employer, 'status' => 'active'])) }}" class="sx-stat">
        <div class="sx-label"><i class="bi bi-people"></i> Active staff</div>
        <div class="sx-value">{{ number_format($headcount) }}</div>
        <div class="sx-sub">{{ $byTitle->map(fn ($n, $t) => "$n $t")->take(3)->implode(' · ') ?: 'No employees yet' }}</div>
    </a>
    <div class="sx-stat">
        <div class="sx-label"><i class="bi bi-wallet2"></i> Monthly base salaries</div>
        <div class="sx-value">{{ $monthlySalaries->isEmpty() ? '—' : $monthlySalaries->map(fn ($t, $c) => Money::format($t, $c))->implode(' + ') }}</div>
        <div class="sx-sub">Before allowances and deductions</div>
    </div>
    <a href="{{ route('hr.attendance.index', array_filter(['employer' => $employer])) }}" class="sx-stat {{ $attendanceToday['absent'] > 0 ? 'sx-danger' : '' }}">
        <div class="sx-label"><i class="bi bi-calendar-check"></i> Today</div>
        <div class="sx-value">{{ $attendanceToday['present'] + $attendanceToday['late'] }} <span class="fs-6 fw-normal sx-muted">at work</span></div>
        <div class="sx-sub">{{ $attendanceToday['absent'] }} absent · {{ $onLeaveToday }} on leave · {{ $unmarkedToday }} not marked</div>
    </a>
    <a href="{{ route('hr.leave.index', array_filter(['employer' => $employer])) }}" class="sx-stat {{ $pendingLeaveCount ? 'sx-warn' : '' }}">
        <div class="sx-label"><i class="bi bi-airplane"></i> Leave to review</div>
        <div class="sx-value">{{ $pendingLeaveCount }}</div>
        <div class="sx-sub">Pending requests</div>
    </a>
    <a href="{{ route('hr.payroll.index', array_filter(['employer' => $employer])) }}" class="sx-stat {{ $unpaidRuns ? 'sx-warn' : '' }}">
        <div class="sx-label"><i class="bi bi-cash-coin"></i> Payroll not yet paid</div>
        <div class="sx-value">{{ $unpaidRuns }}</div>
        <div class="sx-sub">Draft or approved runs</div>
    </a>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="sx-card h-100">
            <div class="sx-card-head"><h2>Pending leave</h2><a href="{{ route('hr.leave.index', array_filter(['employer' => $employer])) }}" class="small">All leave</a></div>
            @forelse($pendingLeave as $leave)
                <div class="d-flex justify-content-between gap-2 px-3 py-2 border-bottom small">
                    <div>
                        <strong>{{ $leave->employee?->full_name }}</strong>
                        <div class="sx-muted">{{ \App\Models\LeaveRequest::TYPES[$leave->type] ?? $leave->type }} · {{ $leave->start_date->format('d M') }} – {{ $leave->end_date->format('d M Y') }}</div>
                    </div>
                    <span class="sx-pill warning align-self-start">{{ rtrim(rtrim(number_format((float) $leave->days, 1), '0'), '.') }} days</span>
                </div>
            @empty
                <div class="sx-empty"><i class="bi bi-check2-circle"></i>No leave waiting for a decision.</div>
            @endforelse
        </div>
    </div>
    <div class="col-lg-6">
        <div class="sx-card h-100">
            <div class="sx-card-head"><h2>Recent payroll</h2><a href="{{ route('hr.payroll.index', array_filter(['employer' => $employer])) }}" class="small">All payroll</a></div>
            @forelse($recentRuns as $run)
                <a href="{{ route('hr.payroll.show', $run) }}" class="d-flex justify-content-between gap-2 px-3 py-2 border-bottom small text-decoration-none text-reset">
                    <div>
                        <strong>{{ $run->period_label }}</strong>
                        <div class="sx-muted">{{ $run->employer_label }} · {{ $run->run_number }}</div>
                    </div>
                    <div class="text-end">
                        <div class="sx-num fw-semibold">{{ Money::format($run->total_net, $run->currency) }}</div>
                        <span class="sx-pill {{ \App\Models\PayrollRun::STATUSES[$run->status][1] }}">{{ \App\Models\PayrollRun::STATUSES[$run->status][0] }}</span>
                    </div>
                </a>
            @empty
                <div class="sx-empty"><i class="bi bi-cash-coin"></i>No payroll yet. <a href="{{ route('hr.payroll.index') }}">Run the first one</a>.</div>
            @endforelse
        </div>
    </div>
</div>

@if($headcount === 0)
    <div class="sx-card mt-3"><div class="sx-empty">
        <i class="bi bi-person-plus"></i>
        Start by adding your caretakers, guards, cleaners and other staff.
        <div class="mt-2"><a href="{{ route('hr.employees.create') }}" class="sx-btn sx-btn-primary"><i class="bi bi-plus-lg"></i> Add employee</a></div>
    </div></div>
@endif
@endsection
