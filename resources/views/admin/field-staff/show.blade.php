@extends('layouts.app')

@section('title', $member->user->name . ' · Field staff')
@section('page_heading', $member->user->name)
@section('page_subtitle', 'Field staff ' . $member->staff_number . ' · registrations and activity')

@use('App\Support\Phone')
@include('partials.sx-styles')

@section('content')
@php
    $max = max(1, max($months));
    $owners = $registrations->where('type', 'Owner')->count();
    $managers = $registrations->where('type', 'Manager')->count();
    $digits = Phone::digits($member->phone);
@endphp
<a href="{{ route('admin.field-staff.index') }}" class="sx-muted small text-decoration-none d-inline-flex align-items-center gap-1 mb-3"><i class="bi bi-arrow-left"></i> All field staff</a>

<div class="sx-card mb-3">
    <div class="sx-card-body d-flex flex-wrap justify-content-between gap-3 align-items-center">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="h5 m-0" style="color:var(--terra-navy,#19265d)">{{ $member->user->name }}</h2>
                <span class="sx-pill {{ $member->isActive() ? 'success' : 'danger' }}">{{ \App\Models\FieldStaff::STATUSES[$member->status] }}</span>
            </div>
            <div class="sx-muted small mt-1">
                {{ $member->staff_number }} · {{ $member->user->email }} · {{ $member->phone ? Phone::display($member->phone) : 'no phone' }}
                · {{ collect([$member->district, $member->region])->filter()->implode(', ') ?: 'no area set' }}
            </div>
            <div class="sx-muted small">Last active {{ $member->last_active_at?->diffForHumans() ?? 'never' }} · added {{ $member->created_at->format('d M Y') }}{{ $member->creator ? ' by ' . $member->creator->name : '' }}</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($digits)<a href="tel:+{{ $digits }}" class="sx-btn sx-btn-outline"><i class="bi bi-telephone"></i> Call</a>@endif
            <a href="{{ route('messages.create', ['to' => $member->user_id]) }}" class="sx-btn sx-btn-outline"><i class="bi bi-chat-dots"></i> Message</a>
            <a href="{{ route('admin.field-staff.edit', $member) }}" class="sx-btn sx-btn-outline"><i class="bi bi-pencil"></i> Edit</a>
            <x-hr-add-button :user="$member->user" class="sx-btn sx-btn-outline" />
            <form method="POST" action="{{ route('admin.field-staff.resend-credentials', $member) }}" class="m-0" onsubmit="return confirm('Send {{ $member->user->name }} new login details by email? Their current password will stop working.');">@csrf
                <button class="sx-btn sx-btn-outline"><i class="bi bi-envelope-arrow-up"></i> Resend credentials</button></form>
            <form method="POST" action="{{ route('admin.field-staff.toggle', $member) }}" class="m-0">@csrf
                <button class="sx-btn {{ $member->isActive() ? 'sx-btn-danger' : 'sx-btn-success' }}"><i class="bi {{ $member->isActive() ? 'bi-pause-circle' : 'bi-play-circle' }}"></i> {{ $member->isActive() ? 'Suspend' : 'Reactivate' }}</button></form>
            <form method="POST" action="{{ route('admin.field-staff.destroy', $member) }}" class="m-0" onsubmit="return confirm('Delete this field staff account permanently? Owners and managers they registered keep their accounts, but will no longer show who registered them.');">@csrf @method('DELETE')
                <button class="sx-btn sx-btn-outline text-danger"><i class="bi bi-trash3"></i> Delete</button></form>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-5">
        <div class="sx-stats m-0" style="grid-template-columns:repeat(2,1fr)">
            <div class="sx-stat"><div class="sx-label">Total registrations</div><div class="sx-value">{{ $registrations->count() }}</div></div>
            <div class="sx-stat"><div class="sx-label">This month</div><div class="sx-value">{{ end($months) }}</div></div>
            <div class="sx-stat"><div class="sx-label">Owners</div><div class="sx-value">{{ $owners }}</div></div>
            <div class="sx-stat"><div class="sx-label">Managers</div><div class="sx-value">{{ $managers }}</div></div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="sx-card h-100"><div class="sx-card-body py-2">
            <div class="sx-muted small">Registrations per month</div>
            <div class="sx-bar" role="img" aria-label="Registrations over the last six months">
                @foreach($months as $m => $v)
                    <div><strong>{{ $v }}</strong><span class="b" style="height:{{ max(2, round($v / $max * 70)) }}px"></span>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $m)->format('M') }}</div>
                @endforeach
            </div>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="sx-card">
            <div class="sx-card-head"><h2>Registered by {{ $member->user->name }}</h2></div>
            <div class="table-responsive">
                <table class="sx-table">
                    <thead><tr><th>Name</th><th>Type</th><th>Contact</th><th>Status</th><th>Registered</th><th></th></tr></thead>
                    <tbody>
                    @forelse($registrations as $r)
                        <tr>
                            <td><strong>{{ $r['name'] }}</strong><div class="sx-muted small">{{ $r['number'] }}</div></td>
                            <td><span class="sx-pill {{ $r['type'] === 'Owner' ? 'info' : 'secondary' }}">{{ $r['type'] }}</span></td>
                            <td class="small">{{ $r['email'] }}<div>{{ $r['phone'] ? Phone::display($r['phone']) : '' }}</div></td>
                            <td class="small">{{ ucfirst((string) $r['status']) }}</td>
                            <td class="small">{{ $r['created_at']?->format('d M Y H:i') }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ $r['url'] }}" class="btn btn-sm btn-light">Profile</a>
                                @if($r['user_id'])<a href="{{ route('admin.subscribers.show', $r['user_id']) }}" class="btn btn-sm btn-light">Subscription</a>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="sx-empty"><i class="bi bi-person-plus"></i>No registrations yet.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        @php $lastLogin = $activity->firstWhere('event', 'login'); @endphp
        <div class="sx-card mb-3" id="credentials">
            <div class="sx-card-head"><h2><i class="bi bi-key me-1"></i> Login credentials</h2></div>
            <div class="sx-card-body">
                <label class="form-label small sx-muted mb-1" for="fsUsername">Username (sign-in email)</label>
                <div class="input-group input-group-sm mb-3">
                    <input type="text" id="fsUsername" class="form-control font-monospace" value="{{ $member->user->email }}" readonly>
                    <button class="btn btn-outline-secondary" type="button" data-copy-field="#fsUsername" aria-label="Copy username"><i class="bi bi-clipboard"></i></button>
                </div>

                <label class="form-label small sx-muted mb-1">Password</label>
                <div class="small mb-3 p-2 rounded" style="background:var(--terra-soft,#f5f7fb)">
                    <i class="bi bi-shield-lock"></i> Stored encrypted — nobody, including admins, can read it.
                    To give the staff member a password, resend credentials or set a new one; it is shown to you once.
                </div>

                <dl class="sx-dl mb-3">
                    <dt>Last sign-in</dt><dd>{{ $lastLogin?->created_at?->format('d M Y H:i') ?? 'Never signed in' }}</dd>
                    <dt>Account</dt><dd>{{ $member->isActive() ? 'Can sign in' : 'Suspended — cannot sign in' }}</dd>
                </dl>

                <div class="d-grid gap-2">
                    <form method="POST" action="{{ route('admin.field-staff.resend-credentials', $member) }}" class="d-grid" onsubmit="return confirm('Send {{ $member->user->name }} new login details by email? Their current password will stop working.');">
                        @csrf
                        <button class="sx-btn sx-btn-primary justify-content-center"><i class="bi bi-envelope-arrow-up"></i> Resend credentials by email</button>
                    </form>
                    <button type="button" class="sx-btn sx-btn-outline justify-content-center" data-bs-toggle="modal" data-bs-target="#setPasswordModal"><i class="bi bi-pencil-square"></i> Set password manually</button>
                </div>
            </div>
        </div>

        <div class="sx-card">
            <div class="sx-card-head"><h2>Recent activity</h2></div>
            <div class="sx-card-body">
                <ul class="sx-timeline">
                    @forelse($activity as $a)
                        <li>
                            <span class="sx-ico"><i class="bi {{ ['login' => 'bi-box-arrow-in-right', 'logout' => 'bi-box-arrow-right', 'created' => 'bi-plus-lg', 'updated' => 'bi-pencil', 'login_failed' => 'bi-shield-exclamation'][$a->event] ?? 'bi-dot' }}"></i></span>
                            <div class="small">
                                <div><strong>{{ ucfirst(str_replace('_', ' ', $a->event)) }}</strong> {{ $a->auditable_type ? class_basename($a->auditable_type) . ($a->auditable_id ? ' #' . $a->auditable_id : '') : '' }}</div>
                                <div class="sx-muted">{{ $a->created_at?->format('d M Y H:i') }}{{ $a->ip_address ? ' · ' . $a->ip_address : '' }}</div>
                            </div>
                        </li>
                    @empty
                        <li class="sx-empty d-block">No activity recorded yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Set password --}}
<div class="modal fade" id="setPasswordModal" tabindex="-1" aria-labelledby="setPasswordLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admin.field-staff.password', $member) }}" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title" id="setPasswordLabel">Set a password for {{ $member->user->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="spPassword">New password</label>
                @include('admin.field-staff._password-input', ['id' => 'spPassword', 'name' => 'password', 'required' => true])
                <div class="form-check mt-3">
                    <input type="hidden" name="send_email" value="0">
                    <input class="form-check-input" type="checkbox" name="send_email" value="1" id="spEmail" checked>
                    <label class="form-check-label" for="spEmail">Email the new login details to {{ $member->user->email }}</label>
                </div>
                <p class="small sx-muted mt-2 mb-0">The current password stops working immediately. You will see the new password once after saving.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="sx-btn sx-btn-outline" data-bs-dismiss="modal">Cancel</button>
                <button class="sx-btn sx-btn-primary"><i class="bi bi-check2-circle"></i> Save password</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-copy-field]').forEach(function (b) {
    b.addEventListener('click', function () {
        var v = document.querySelector(b.dataset.copyField).value;
        if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(v);
        b.querySelector('i').className = 'bi bi-check2';
        setTimeout(function () { b.querySelector('i').className = 'bi bi-clipboard'; }, 1500);
    });
});
@if($errors->has('password'))
document.addEventListener('DOMContentLoaded', function () { if (window.bootstrap) new bootstrap.Modal(document.getElementById('setPasswordModal')).show(); });
@endif
</script>
@endpush
