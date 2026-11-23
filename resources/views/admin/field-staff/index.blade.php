@extends('layouts.app')

@section('title', 'Field staff')
@section('page_heading', 'Field staff')
@section('page_subtitle', 'Agents who register property owners and managers in the field. They cannot reach admin, finance or settings.')

@use('App\Support\Phone')
@include('partials.sx-styles')

@section('content')
<div class="sx-stats">
    <div class="sx-stat"><div class="sx-label"><i class="bi bi-person-walking"></i> Field staff</div><div class="sx-value">{{ $stats['total'] }}</div><div class="sx-sub">{{ $stats['active'] }} active</div></div>
    <div class="sx-stat sx-ok"><div class="sx-label"><i class="bi bi-activity"></i> Active today</div><div class="sx-value">{{ $stats['active_today'] }}</div></div>
    <div class="sx-stat"><div class="sx-label"><i class="bi bi-person-badge"></i> Owners registered</div><div class="sx-value">{{ $stats['owners'] }}</div></div>
    <div class="sx-stat"><div class="sx-label"><i class="bi bi-person-gear"></i> Managers registered</div><div class="sx-value">{{ $stats['managers'] }}</div></div>
    <div class="sx-stat sx-warn"><div class="sx-label"><i class="bi bi-calendar3"></i> This month</div><div class="sx-value">{{ $stats['this_month'] }}</div><div class="sx-sub">registrations</div></div>
</div>

<div class="sx-card">
    <div class="sx-card-head">
        <form method="GET" class="sx-filters flex-grow-1">
            <div style="min-width:220px;" class="flex-grow-1"><label class="visually-hidden" for="fq">Search</label>
                <input type="search" id="fq" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Name, email, staff number or area"></div>
            <div><label class="visually-hidden" for="fs">Status</label>
                <select id="fs" name="status" class="form-select form-select-sm"><option value="">All statuses</option>@foreach(\App\Models\FieldStaff::STATUSES as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select></div>
            <button class="sx-btn sx-btn-outline"><i class="bi bi-search"></i> Filter</button>
        </form>
        <a href="{{ route('admin.field-staff.create') }}" class="sx-btn sx-btn-primary"><i class="bi bi-person-plus"></i> Add field staff</a>
    </div>
    <div class="table-responsive">
        <table class="sx-table">
            <thead><tr><th>Staff member</th><th>Area</th><th>Phone</th><th class="sx-num">Owners</th><th class="sx-num">Managers</th><th class="sx-num">This month</th><th>Last active</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($staff as $m)
                <tr>
                    <td>
                        <a href="{{ route('admin.field-staff.show', $m) }}" class="text-decoration-none"><strong style="color:var(--terra-navy,#19265d)">{{ $m->user?->name }}</strong></a>
                        <div class="sx-muted small">{{ $m->staff_number }} · {{ $m->user?->email }}</div>
                    </td>
                    <td class="small">{{ collect([$m->district, $m->region])->filter()->implode(', ') ?: '—' }}</td>
                    <td class="small">{{ $m->phone ? Phone::display($m->phone) : '—' }}</td>
                    <td class="sx-num">{{ $m->owners_count }}</td>
                    <td class="sx-num">{{ $m->managers_count }}</td>
                    <td class="sx-num"><strong>{{ $m->owners_month_count + $m->managers_month_count }}</strong></td>
                    <td class="small">{{ $m->last_active_at?->diffForHumans() ?? 'Never' }}</td>
                    <td><span class="sx-pill {{ $m->isActive() ? 'success' : 'danger' }}">{{ \App\Models\FieldStaff::STATUSES[$m->status] ?? $m->status }}</span></td>
                    <td class="text-end text-nowrap">
                        <form method="POST" action="{{ route('admin.field-staff.resend-credentials', $m) }}" class="d-inline" onsubmit="return confirm('Send {{ $m->user?->name }} new login details by email? Their current password will stop working.');">
                            @csrf
                            <button class="btn btn-sm btn-light" title="Resend credentials by email" aria-label="Resend credentials to {{ $m->user?->name }}"><i class="bi bi-envelope-arrow-up"></i></button>
                        </form>
                        <a href="{{ route('admin.field-staff.show', $m) }}#credentials" class="btn btn-sm btn-light" title="Login credentials" aria-label="Login credentials for {{ $m->user?->name }}"><i class="bi bi-key"></i></a>
                        <a href="{{ route('admin.field-staff.show', $m) }}" class="btn btn-sm btn-light">Open</a>
                        <form method="POST" action="{{ route('admin.field-staff.destroy', $m) }}" class="d-inline" onsubmit="return confirm('Delete this field staff account permanently? Owners and managers they registered keep their accounts.');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-light text-danger" title="Delete account" aria-label="Delete field staff {{ $m->user?->name }}"><i class="bi bi-trash3"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9"><div class="sx-empty"><i class="bi bi-person-walking"></i>No field staff yet.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($staff->hasPages())<div class="p-3">{{ $staff->links() }}</div>@endif
</div>
@endsection
