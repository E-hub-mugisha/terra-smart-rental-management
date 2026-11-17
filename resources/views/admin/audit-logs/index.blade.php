@extends('layouts.app')

@section('title', 'Audit log')
@section('page_heading', 'Audit log')
@section('page_subtitle', 'Who changed what and when, plus sign-ins and failed login attempts.')

@php
    $eventBadge = ['created' => 'text-bg-success', 'updated' => 'text-bg-primary', 'deleted' => 'text-bg-danger', 'restored' => 'text-bg-info',
                   'login' => 'text-bg-light border', 'logout' => 'text-bg-light border', 'login_failed' => 'text-bg-warning', 'password_reset' => 'text-bg-warning'];
    $show = fn ($v) => is_array($v) ? json_encode($v) : (is_bool($v) ? ($v ? 'true' : 'false') : (string) ($v ?? '∅'));
@endphp

@section('content')
@if($failedLogins24h >= 10)
    <div class="alert alert-warning"><i class="bi bi-shield-exclamation me-1"></i> {{ $failedLogins24h }} failed login attempts in the last 24 hours.
        <a href="{{ route('admin.audit-logs.index', ['event' => 'login_failed']) }}">Review</a></div>
@endif

<div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="card-header bg-white p-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-6 col-lg-2">
                <label class="form-label small mb-1" for="aUser">User</label>
                <select name="user" id="aUser" class="form-select form-select-sm">
                    <option value="">Anyone</option>
                    @foreach($users as $u)<option value="{{ $u->id }}" @selected((int) request('user') === $u->id)>{{ $u->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label small mb-1" for="aEvent">Event</label>
                <select name="event" id="aEvent" class="form-select form-select-sm">
                    <option value="">Any</option>
                    @foreach($events as $e)<option value="{{ $e }}" @selected(request('event') === $e)>{{ str_replace('_', ' ', $e) }}</option>@endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label small mb-1" for="aType">Record type</label>
                <select name="type" id="aType" class="form-select form-select-sm">
                    <option value="">Any</option>
                    @foreach($types as $t)<option value="{{ $t }}" @selected(request('type') === $t)>{{ $t }}</option>@endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-1">
                <label class="form-label small mb-1" for="aSubject">ID</label>
                <input type="number" name="subject" id="aSubject" class="form-control form-control-sm" value="{{ request('subject') }}">
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label small mb-1" for="aFrom">From</label>
                <input type="date" name="from" id="aFrom" class="form-control form-control-sm" value="{{ request('from') }}">
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label small mb-1" for="aTo">To</label>
                <input type="date" name="to" id="aTo" class="form-control form-control-sm" value="{{ request('to') }}">
            </div>
            <div class="col-lg-1 d-grid"><button class="btn btn-sm btn-dark">Filter</button></div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light small"><tr><th>When</th><th>Who</th><th>Event</th><th>Record</th><th>Changes</th><th class="d-none d-lg-table-cell">IP</th></tr></thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="text-nowrap small" title="{{ $log->created_at?->format('d M Y H:i:s') }}">{{ $log->created_at?->format('d M H:i') }}</td>
                        <td class="small">{{ $log->user?->name ?? 'System / guest' }}</td>
                        <td><span class="badge rounded-pill {{ $eventBadge[$log->event] ?? 'text-bg-secondary' }}">{{ str_replace('_', ' ', $log->event) }}</span></td>
                        <td class="small text-nowrap">
                            @if($log->auditable_type)
                                <a href="{{ route('admin.audit-logs.index', ['type' => class_basename($log->auditable_type), 'subject' => $log->auditable_id]) }}">{{ $log->subject_label }}</a>
                            @else — @endif
                        </td>
                        <td class="small" style="max-width:480px">
                            @if($log->event === 'updated' && $log->new_values)
                                @foreach($log->new_values as $field => $value)
                                    <div class="text-truncate"><code>{{ $field }}</code>: <span class="text-danger text-decoration-line-through">{{ \Illuminate\Support\Str::limit($show($log->old_values[$field] ?? null), 60) }}</span> → <span class="text-success">{{ \Illuminate\Support\Str::limit($show($value), 60) }}</span></div>
                                @endforeach
                            @elseif($log->new_values || $log->old_values)
                                <details><summary class="text-secondary">{{ count($log->new_values ?? $log->old_values ?? []) }} fields</summary>
                                    <pre class="small mb-0" style="white-space:pre-wrap">{{ json_encode($log->new_values ?? $log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></details>
                            @endif
                        </td>
                        <td class="small text-secondary d-none d-lg-table-cell">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-secondary py-5">No activity recorded for these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())<div class="p-3 border-top">{{ $logs->links() }}</div>@endif
</div>
@endsection
