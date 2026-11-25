@extends('layouts.app')

@section('title', 'Employees')
@section('page_heading', 'Employees')
@section('page_subtitle', 'Everyone on your payroll: property staff and, for admins, Terra\'s own team.')

@php use App\Support\Money; use App\Models\Employee; @endphp

@section('content')
@include('hr._nav')

@if($notYetEmployees->isNotEmpty())
    <div class="sx-card mb-3">
        <div class="sx-card-head">
            <h2><i class="bi bi-person-plus me-1"></i> Team members not yet on HR</h2>
            <span class="small sx-muted">Property managers, accountants, agents{{ auth()->user()->isAdmin() ? ' and field staff' : '' }} you work with</span>
        </div>
        <div class="sx-card-body d-flex flex-wrap gap-2">
            @foreach($notYetEmployees as $row)
                @php $u = $row['user']; @endphp
                <div class="d-flex align-items-center gap-2 border rounded-3 px-2 py-1">
                    <div class="small">
                        <strong>{{ $u->name }}</strong>
                        <div class="sx-muted">{{ Employee::LINKABLE_ROLES[$u->role] ?? $u->role }}{{ $u->role === 'field_staff' ? ' · Terra' : (isset($employers[$row['employer']]) ? ' · ' . $employers[$row['employer']] : '') }}</div>
                    </div>
                    <a href="{{ route('hr.employees.create', ['user_id' => $u->id, 'property_id' => $row['employer'] ?? '']) }}" class="btn btn-sm btn-light" aria-label="Add {{ $u->name }} to HR"><i class="bi bi-plus-lg"></i> Add</a>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="sx-card">
    <div class="sx-card-head flex-wrap">
        <form method="GET" class="sx-filters">
            @if(request('employer'))<input type="hidden" name="employer" value="{{ request('employer') }}">@endif
            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="max-width:220px" placeholder="Name, job or number" aria-label="Search employees">
            <select name="status" class="form-select form-select-sm" style="max-width:160px" onchange="this.form.submit()" aria-label="Status">
                <option value="">All statuses</option>
                @foreach(Employee::STATUSES as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach
            </select>
        </form>
        <a href="{{ route('hr.employees.create', array_filter(['property_id' => ctype_digit((string) request('employer')) ? request('employer') : null])) }}" class="sx-btn sx-btn-primary"><i class="bi bi-person-plus"></i> Add employee</a>
    </div>

    <div class="table-responsive">
        <table class="sx-table">
            <thead>
                <tr><th>Employee</th><th>Job</th><th>Employer</th><th>Type</th><th class="sx-num">Base salary</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            @forelse($employees as $e)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="sx-avatar">{{ \Illuminate\Support\Str::of($e->full_name)->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}</span>
                            <div>
                                <a href="{{ route('hr.employees.show', $e) }}" class="fw-semibold text-decoration-none">{{ $e->full_name }}</a>
                                @if($e->account_role_label)<span class="sx-pill info ms-1" title="Also a platform user">{{ $e->account_role_label }} · has login</span>@endif
                                <div class="sx-muted small">{{ $e->employee_number }}{{ $e->phone ? ' · ' . \App\Support\Phone::display($e->phone) : '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="small">{{ $e->job_title }}@if($e->department)<div class="sx-muted">{{ $e->department }}</div>@endif</td>
                    <td class="small">{{ $e->employer_label }}</td>
                    <td class="small">{{ Employee::EMPLOYMENT_TYPES[$e->employment_type] ?? $e->employment_type }}</td>
                    <td class="sx-num">{{ Money::format($e->base_salary, $e->currency) }}</td>
                    <td><span class="sx-pill {{ $e->status_tone }}">{{ $e->status_label }}</span></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('hr.employees.edit', $e) }}" class="btn btn-sm btn-light" title="Edit" aria-label="Edit {{ $e->full_name }}"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('hr.employees.destroy', $e) }}" class="d-inline"
                              onsubmit="return confirm('Delete this employee? If they have been paid, their payslips are kept in the payroll history.');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-light text-danger" title="Delete" aria-label="Delete {{ $e->full_name }}"><i class="bi bi-trash3"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="sx-empty"><i class="bi bi-people"></i>No employees found.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())<div class="p-3">{{ $employees->links() }}</div>@endif
</div>
@endsection
