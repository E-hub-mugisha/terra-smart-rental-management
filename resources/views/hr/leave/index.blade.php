@extends('layouts.app')

@section('title', 'Leave')
@section('page_heading', 'Leave')
@section('page_subtitle', 'Record leave for your staff and approve or reject requests. Annual leave cannot go beyond each person\'s yearly entitlement.')

@php
    use App\Models\LeaveRequest;
    $days = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');
@endphp

@section('content')
@include('hr._nav')
@include('hr._errors')

<div class="row g-3">
    <div class="col-xl-8">
        <div class="sx-chipbar">
            @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled', 'all' => 'All'] as $key => $label)
                <a href="{{ route('hr.leave.index', array_filter(['status' => $key, 'employer' => request('employer')])) }}" class="sx-chip {{ $status === $key ? 'active' : '' }}">
                    {{ $label }}@if($key !== 'all') ({{ $counts[$key] ?? 0 }})@endif
                </a>
            @endforeach
        </div>

        <div class="sx-card">
            <div class="table-responsive">
                <table class="sx-table">
                    <thead><tr><th>Employee</th><th>Leave</th><th class="sx-num">Days</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($requests as $leave)
                        <tr>
                            <td>
                                @if($leave->employee)
                                    <a href="{{ route('hr.employees.show', $leave->employee) }}" class="fw-semibold text-decoration-none">{{ $leave->employee->full_name }}</a>
                                    <div class="sx-muted small">{{ $leave->employee->employer_label }}</div>
                                @endif
                            </td>
                            <td class="small">
                                {{ LeaveRequest::TYPES[$leave->type] ?? $leave->type }}
                                <div class="sx-muted">{{ $leave->start_date->format('d M') }} – {{ $leave->end_date->format('d M Y') }}</div>
                                @if($leave->reason)<div class="sx-muted fst-italic">{{ \Illuminate\Support\Str::limit($leave->reason, 80) }}</div>@endif
                            </td>
                            <td class="sx-num">{{ $days($leave->days) }}</td>
                            <td>
                                <span class="sx-pill {{ LeaveRequest::STATUSES[$leave->status][1] }}">{{ LeaveRequest::STATUSES[$leave->status][0] }}</span>
                                @if($leave->reviewer)<div class="sx-muted small">by {{ $leave->reviewer->name }}</div>@endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if($leave->status === 'pending')
                                    <form method="POST" action="{{ route('hr.leave.decide', $leave) }}" class="d-inline">@csrf
                                        <input type="hidden" name="decision" value="approve">
                                        <button class="btn btn-sm btn-success" aria-label="Approve leave for {{ $leave->employee?->full_name }}">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('hr.leave.decide', $leave) }}" class="d-inline" onsubmit="return confirm('Reject this leave request?');">@csrf
                                        <input type="hidden" name="decision" value="reject">
                                        <button class="btn btn-sm btn-light text-danger" aria-label="Reject leave for {{ $leave->employee?->full_name }}">Reject</button>
                                    </form>
                                @elseif($leave->status === 'approved')
                                    <form method="POST" action="{{ route('hr.leave.decide', $leave) }}" class="d-inline" onsubmit="return confirm('Cancel this approved leave? The days go back to the balance.');">@csrf
                                        <input type="hidden" name="decision" value="cancel">
                                        <button class="btn btn-sm btn-light">Cancel</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('hr.leave.destroy', $leave) }}" class="d-inline" onsubmit="return confirm('Delete this leave record?');">@csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light text-danger" title="Delete" aria-label="Delete leave record"><i class="bi bi-trash3"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="sx-empty"><i class="bi bi-airplane"></i>No leave {{ $status === 'all' ? '' : $status }} here.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($requests->hasPages())<div class="p-3">{{ $requests->links() }}</div>@endif
        </div>
    </div>

    <div class="col-xl-4">
        <div class="sx-card mb-3">
            <div class="sx-card-head"><h2>Record leave</h2></div>
            @if($employees->isEmpty())
                <div class="sx-empty">Add employees first.</div>
            @else
                <form method="POST" action="{{ route('hr.leave.store') }}" class="sx-card-body row g-2">
                    @csrf
                    <div class="col-12">
                        <label for="lvEmployee" class="form-label small">Employee</label>
                        <select id="lvEmployee" name="employee_id" required class="form-select form-select-sm">
                            <option value="">Choose…</option>
                            @foreach($employees as $e)<option value="{{ $e->id }}" @selected(old('employee_id') == $e->id)>{{ $e->full_name }} · {{ $e->job_title }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="lvType" class="form-label small">Type</label>
                        <select id="lvType" name="type" class="form-select form-select-sm">
                            @foreach(LeaveRequest::TYPES as $k => $l)<option value="{{ $k }}" @selected(old('type') === $k)>{{ $l }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-6"><label for="lvStart" class="form-label small">From</label><input id="lvStart" type="date" name="start_date" required value="{{ old('start_date') }}" class="form-control form-control-sm"></div>
                    <div class="col-6"><label for="lvEnd" class="form-label small">To</label><input id="lvEnd" type="date" name="end_date" required value="{{ old('end_date') }}" class="form-control form-control-sm"></div>
                    <div class="col-12"><label for="lvReason" class="form-label small">Reason <span class="sx-muted">(optional)</span></label><textarea id="lvReason" name="reason" rows="2" maxlength="1000" class="form-control form-control-sm">{{ old('reason') }}</textarea></div>
                    <div class="col-12 form-check ms-1"><input type="checkbox" class="form-check-input" id="lvApprove" name="approve_now" value="1"><label class="form-check-label small" for="lvApprove">Approve now</label></div>
                    <div class="col-12 small sx-muted">Days are counted Monday to Friday.</div>
                    <div class="col-12 d-grid"><button class="sx-btn sx-btn-primary justify-content-center">Save leave</button></div>
                </form>
            @endif
        </div>

        <div class="sx-card">
            <div class="sx-card-head"><h2>On leave today</h2></div>
            @forelse($onLeaveToday as $leave)
                <div class="px-3 py-2 border-bottom small d-flex justify-content-between">
                    <span>{{ $leave->employee?->full_name }}</span>
                    <span class="sx-muted">back {{ $leave->end_date->copy()->addDay()->format('d M') }}</span>
                </div>
            @empty
                <div class="sx-empty">Everyone is in today.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
