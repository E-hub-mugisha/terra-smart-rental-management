@extends('layouts.app')

@section('title', 'Field dashboard')
@section('page_heading', 'Hello, ' . explode(' ', auth()->user()->name)[0])
@section('page_subtitle', 'Register property owners and managers you meet in the field.')

@use('App\Support\Phone')
@include('partials.sx-styles')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <a href="{{ route('field-staff.owners.create') }}" class="sx-card d-flex align-items-center gap-3 p-3 text-decoration-none h-100" style="border-color:var(--terra-orange,#D05208)">
            <span class="sx-avatar lg"><i class="bi bi-person-badge"></i></span>
            <span>
                <strong class="d-block" style="color:var(--terra-navy,#19265d);font-size:1.05rem">Register a property owner</strong>
                <span class="sx-muted small">Landlords and companies that own rental property</span>
            </span>
            <i class="bi bi-chevron-right ms-auto sx-muted"></i>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('field-staff.managers.create') }}" class="sx-card d-flex align-items-center gap-3 p-3 text-decoration-none h-100">
            <span class="sx-avatar lg"><i class="bi bi-person-gear"></i></span>
            <span>
                <strong class="d-block" style="color:var(--terra-navy,#19265d);font-size:1.05rem">Register a property manager</strong>
                <span class="sx-muted small">People who run properties for owners</span>
            </span>
            <i class="bi bi-chevron-right ms-auto sx-muted"></i>
        </a>
    </div>
</div>

<div class="sx-stats">
    <div class="sx-stat"><div class="sx-label">Today</div><div class="sx-value">{{ $stats['today'] }}</div></div>
    <div class="sx-stat"><div class="sx-label">This month</div><div class="sx-value">{{ $stats['month'] }}</div></div>
    <div class="sx-stat"><div class="sx-label">Owners registered</div><div class="sx-value">{{ $stats['owners'] }}</div><div class="sx-sub">{{ $stats['verified'] }} verified by Terra</div></div>
    <div class="sx-stat"><div class="sx-label">Managers registered</div><div class="sx-value">{{ $stats['managers'] }}</div></div>
</div>

<div class="sx-card">
    <div class="sx-card-head">
        <h2>Your latest registrations</h2>
        <a href="{{ route('field-staff.registrations') }}" class="small">See all</a>
    </div>
    <div class="table-responsive">
        <table class="sx-table">
            <thead><tr><th>Name</th><th>Type</th><th>Phone</th><th>Status</th><th>When</th></tr></thead>
            <tbody>
            @forelse($recent as $r)
                <tr>
                    <td><strong>{{ $r['name'] }}</strong><div class="sx-muted small">{{ $r['email'] }}</div></td>
                    <td><span class="sx-pill {{ $r['type'] === 'Owner' ? 'info' : 'secondary' }}">{{ $r['type'] }}</span></td>
                    <td class="small">{{ $r['phone'] ? Phone::display($r['phone']) : '—' }}</td>
                    <td class="small">{{ $r['status'] }}</td>
                    <td class="small">{{ $r['created_at']?->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="sx-empty"><i class="bi bi-person-plus"></i>You haven't registered anyone yet.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($member)
    <p class="small sx-muted mt-3 mb-0">Staff number {{ $member->staff_number }}{{ $member->district ? ' · ' . $member->district : '' }}. Need help? <a href="{{ route('messages.index') }}">Message Terra</a>.</p>
@endif
@endsection
