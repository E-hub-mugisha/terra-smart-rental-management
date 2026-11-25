@extends('layouts.app')

@section('title', 'Attendance')
@section('page_heading', 'Attendance register')
@section('page_subtitle', 'Mark who came to work each day. Staff on approved leave are filled in for you.')

@php use App\Models\Attendance; @endphp

@section('content')
@include('hr._nav')
@include('hr._errors')

<div class="sx-card">
    <div class="sx-card-head flex-wrap">
        <form method="GET" class="sx-filters align-items-center">
            @if($employer)<input type="hidden" name="employer" value="{{ $employer }}">@endif
            <a href="{{ route('hr.attendance.index', array_filter(['employer' => $employer, 'date' => $date->copy()->subDay()->toDateString()])) }}" class="btn btn-sm btn-light" aria-label="Previous day"><i class="bi bi-chevron-left"></i></a>
            <label for="attDate" class="visually-hidden">Date</label>
            <input id="attDate" type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control form-control-sm" style="width:auto" onchange="this.form.submit()">
            @if($date->lt(today()))
                <a href="{{ route('hr.attendance.index', array_filter(['employer' => $employer, 'date' => $date->copy()->addDay()->toDateString()])) }}" class="btn btn-sm btn-light" aria-label="Next day"><i class="bi bi-chevron-right"></i></a>
            @endif
            <strong class="ms-1" style="color:var(--terra-navy,#19265d)">{{ $date->isToday() ? 'Today' : $date->format('l') }}, {{ $date->format('d M Y') }}</strong>
        </form>
        <div class="d-flex flex-wrap gap-1">
            @foreach(Attendance::STATUSES as $key => [$label, $tone])
                <span class="sx-pill {{ $tone }}">{{ $summary[$key] }} {{ strtolower($label) }}</span>
            @endforeach
        </div>
    </div>

    @if($employees->isEmpty())
        <div class="sx-empty"><i class="bi bi-people"></i>No active employees for this employer. <a href="{{ route('hr.employees.create') }}">Add one</a>.</div>
    @else
        <form method="POST" action="{{ route('hr.attendance.store') }}">
            @csrf
            <input type="hidden" name="date" value="{{ $date->toDateString() }}">
            @if($employer)<input type="hidden" name="employer" value="{{ $employer }}">@endif

            <div class="px-3 pt-3 d-flex flex-wrap gap-2 small">
                <span class="sx-muted">Mark all:</span>
                @foreach(['present', 'absent', 'off'] as $key)
                    <button type="button" class="btn btn-sm btn-light" data-mark-all="{{ $key }}">{{ Attendance::STATUSES[$key][0] }}</button>
                @endforeach
            </div>

            <div class="table-responsive position-relative">
                <table class="sx-table">
                    <thead><tr><th>Employee</th><th>Status</th><th>In</th><th>Out</th><th>Note</th></tr></thead>
                    <tbody>
                    @foreach($employees as $e)
                        @php
                            $record = $records->get($e->id);
                            $leaveType = $onLeave->get($e->id);
                            $current = old("rows.{$e->id}.status", $record?->status ?? ($leaveType ? 'leave' : ''));
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('hr.employees.show', $e) }}" class="fw-semibold text-decoration-none">{{ $e->full_name }}</a>
                                <div class="sx-muted small">{{ $e->job_title }} · {{ $e->employer_label }}</div>
                                @if($leaveType)<span class="sx-pill info mt-1">{{ \App\Models\LeaveRequest::TYPES[$leaveType] ?? 'On leave' }}</span>@endif
                            </td>
                            <td style="min-width:150px">
                                <label for="st{{ $e->id }}" class="visually-hidden">Status for {{ $e->full_name }}</label>
                                <select id="st{{ $e->id }}" name="rows[{{ $e->id }}][status]" class="form-select form-select-sm" data-status>
                                    <option value="">Not marked</option>
                                    @foreach(Attendance::STATUSES as $key => [$label])
                                        <option value="{{ $key }}" @selected($current === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="min-width:110px">
                                <label for="in{{ $e->id }}" class="visually-hidden">Check-in for {{ $e->full_name }}</label>
                                <input id="in{{ $e->id }}" type="time" name="rows[{{ $e->id }}][check_in]" class="form-control form-control-sm" value="{{ old("rows.{$e->id}.check_in", $record?->check_in ? substr($record->check_in, 0, 5) : '') }}">
                            </td>
                            <td style="min-width:110px">
                                <label for="out{{ $e->id }}" class="visually-hidden">Check-out for {{ $e->full_name }}</label>
                                <input id="out{{ $e->id }}" type="time" name="rows[{{ $e->id }}][check_out]" class="form-control form-control-sm" value="{{ old("rows.{$e->id}.check_out", $record?->check_out ? substr($record->check_out, 0, 5) : '') }}">
                            </td>
                            <td style="min-width:180px">
                                <label for="nt{{ $e->id }}" class="visually-hidden">Note for {{ $e->full_name }}</label>
                                <input id="nt{{ $e->id }}" name="rows[{{ $e->id }}][note]" maxlength="255" class="form-control form-control-sm" value="{{ old("rows.{$e->id}.note", $record?->note) }}">
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3 d-flex justify-content-end">
                <button class="sx-btn sx-btn-primary"><i class="bi bi-check2"></i> Save attendance</button>
            </div>
        </form>
    @endif
</div>

@push('scripts')
<script>
    document.querySelectorAll('[data-mark-all]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('select[data-status]').forEach(function (select) {
                // Keep anyone already marked as on leave.
                if (select.value !== 'leave') { select.value = btn.dataset.markAll; }
            });
        });
    });
</script>
@endpush
@endsection
