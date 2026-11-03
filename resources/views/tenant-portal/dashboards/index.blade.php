@extends('layouts.app')

@section('title', 'Tenant Dashboard | Terra Smart Rental Management')

@section('content')
@php
$user = auth()->user();
$tenantName = $tenant->full_name ?? $user->name ?? 'Tenant';
$firstName = explode(' ', trim($tenantName))[0] ?? 'Tenant';

$currency = function ($amount, $code = 'RWF') {
return ($code ?: 'RWF') . ' ' . number_format((float) $amount, 0);
};

$statusClass = function ($status) {
return match (strtolower((string) $status)) {
'active', 'resolved', 'completed', 'paid' => 'status-success',
'draft', 'pending', 'submitted', 'acknowledged' => 'status-pending',
'scheduled', 'in_progress', 'in progress' => 'status-info',
'rejected', 'overdue', 'cancelled', 'canceled' => 'status-danger',
default => 'status-neutral',
};
};

$statusLabel = fn ($status) => ucfirst(str_replace('_', ' ', $status ?: 'Unknown'));

$currentRentals = $leases->filter(fn ($lease) => $lease->status === 'active');
$pendingLeases = $leases->filter(fn ($lease) => $lease->status === 'draft');
@endphp

<style>
    .tenant-dashboard {
        --terra-primary: #D05208;
        --terra-navy: #19265d;
        --terra-text: #20283b;
        --terra-muted: #748094;
        --terra-border: #e8ebf1;
        color: var(--terra-text);
        font-family: "DM Sans", sans-serif;
    }

    .tenant-dashboard .page-heading {
        font-size: clamp(1.5rem, 2.5vw, 2rem);
        font-weight: 750;
        letter-spacing: -.04em;
        color: var(--terra-navy);
    }

    .tenant-dashboard .muted {
        color: var(--terra-muted);
    }

    .tenant-dashboard .welcome-panel {
        position: relative;
        overflow: hidden;
        border-radius: 20px;
        padding: 28px;
        background: linear-gradient(115deg, #19265d 0%, #273a80 100%);
        color: #fff;
    }

    .tenant-dashboard .welcome-panel::after {
        content: "";
        position: absolute;
        width: 230px;
        height: 230px;
        border: 1px solid rgba(255, 255, 255, .13);
        border-radius: 50%;
        right: -65px;
        top: -115px;
        box-shadow: 0 0 0 25px rgba(255, 255, 255, .035),
            0 0 0 50px rgba(255, 255, 255, .025);
        pointer-events: none;
    }

    .tenant-dashboard .welcome-panel .welcome-content {
        position: relative;
        z-index: 1;
    }

    .tenant-dashboard .welcome-eyebrow {
        color: #dce3ff;
        font-size: .76rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
    }

    .tenant-dashboard .welcome-title {
        font-size: clamp(1.45rem, 3vw, 2rem);
        font-weight: 750;
        letter-spacing: -.04em;
        margin: 8px 0;
    }

    .tenant-dashboard .welcome-description {
        max-width: 580px;
        color: #e1e6fa;
        margin-bottom: 20px;
    }

    .tenant-dashboard .btn-terra {
        background: var(--terra-primary);
        border-color: var(--terra-primary);
        color: #fff;
        font-weight: 650;
        border-radius: 10px;
        padding: 10px 15px;
    }

    .tenant-dashboard .btn-terra:hover {
        background: #b94706;
        border-color: #b94706;
        color: #fff;
    }

    .tenant-dashboard .btn-light-outline {
        border: 1px solid rgba(255, 255, 255, .45);
        color: #fff;
        border-radius: 10px;
        padding: 10px 15px;
        font-weight: 600;
    }

    .tenant-dashboard .btn-light-outline:hover {
        background: rgba(255, 255, 255, .12);
        color: #fff;
    }

    .tenant-dashboard .dashboard-card {
        height: 100%;
        background: #fff;
        border: 1px solid var(--terra-border);
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 18px rgba(25, 38, 93, .035);
    }

    .tenant-dashboard .stat-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 43px;
        height: 43px;
        border-radius: 12px;
        background: #fff1e8;
        color: var(--terra-primary);
        font-size: 1.2rem;
    }

    .tenant-dashboard .stat-label {
        color: var(--terra-muted);
        font-size: .84rem;
        font-weight: 600;
    }

    .tenant-dashboard .stat-value {
        color: var(--terra-navy);
        font-size: 1.7rem;
        font-weight: 750;
        letter-spacing: -.035em;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }

    .tenant-dashboard .section-title {
        font-size: 1.05rem;
        font-weight: 750;
        color: var(--terra-navy);
        margin: 0;
    }

    .tenant-dashboard .section-link {
        color: var(--terra-primary);
        font-size: .86rem;
        font-weight: 700;
        text-decoration: none;
    }

    .tenant-dashboard .section-link:hover {
        text-decoration: underline;
    }

    .tenant-dashboard .rental-item,
    .tenant-dashboard .request-item {
        border: 1px solid var(--terra-border);
        border-radius: 13px;
        padding: 16px;
    }

    .tenant-dashboard .rental-item+.rental-item,
    .tenant-dashboard .request-item+.request-item {
        margin-top: 12px;
    }

    .tenant-dashboard .rental-icon {
        display: flex;
        flex: 0 0 48px;
        width: 48px;
        height: 48px;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: #f1f3fa;
        color: var(--terra-navy);
        font-size: 1.35rem;
    }

    .tenant-dashboard .rental-name {
        color: var(--terra-navy);
        font-weight: 750;
        text-decoration: none;
    }

    .tenant-dashboard .rental-name:hover {
        color: var(--terra-primary);
    }

    .tenant-dashboard .status-pill {
        display: inline-flex;
        align-items: center;
        padding: 5px 9px;
        border-radius: 30px;
        font-size: .72rem;
        font-weight: 750;
        white-space: nowrap;
    }

    .tenant-dashboard .status-success {
        color: #167347;
        background: #e5f7ee;
    }

    .tenant-dashboard .status-pending {
        color: #936000;
        background: #fff4d7;
    }

    .tenant-dashboard .status-info {
        color: #2057a7;
        background: #e8f1ff;
    }

    .tenant-dashboard .status-danger {
        color: #b42318;
        background: #ffebe9;
    }

    .tenant-dashboard .status-neutral {
        color: #5d6678;
        background: #edf0f4;
    }

    .tenant-dashboard .quick-action {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 15px;
        color: inherit;
        text-decoration: none;
        border: 1px solid var(--terra-border);
        border-radius: 13px;
        transition: border-color .2s, background .2s, transform .2s;
    }

    .tenant-dashboard .quick-action:hover {
        border-color: #edb18e;
        background: #fffaf6;
        transform: translateY(-1px);
    }

    .tenant-dashboard .quick-action-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 42px;
        height: 42px;
        border-radius: 11px;
        background: #fff1e8;
        color: var(--terra-primary);
        font-size: 1.1rem;
    }

    .tenant-dashboard .quick-action-title {
        display: block;
        color: var(--terra-navy);
        font-size: .9rem;
        font-weight: 750;
    }

    .tenant-dashboard .quick-action-description {
        display: block;
        color: var(--terra-muted);
        font-size: .78rem;
        margin-top: 2px;
    }

    .tenant-dashboard .empty-state {
        text-align: center;
        padding: 28px 15px;
        border: 1px dashed #dce1eb;
        border-radius: 13px;
        background: #fafbfe;
    }

    .tenant-dashboard .empty-state i {
        font-size: 1.8rem;
        color: #98a2b3;
    }

    .tenant-dashboard .empty-state h3 {
        color: var(--terra-navy);
        font-size: .95rem;
        font-weight: 750;
        margin-top: 10px;
    }

    .tenant-dashboard .profile-summary {
        display: flex;
        align-items: center;
        gap: 13px;
    }

    .tenant-dashboard .profile-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        flex: 0 0 50px;
        border-radius: 50%;
        background: #fff1e8;
        color: var(--terra-primary);
        font-size: 1.15rem;
        font-weight: 800;
    }

    .tenant-dashboard .info-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 0;
        border-bottom: 1px solid #f0f1f5;
        font-size: .85rem;
    }

    .tenant-dashboard .info-row:last-child {
        border-bottom: 0;
    }

    .tenant-dashboard .info-value {
        color: var(--terra-navy);
        font-weight: 650;
        text-align: right;
        overflow-wrap: anywhere;
    }

    @media (max-width: 575.98px) {
        .tenant-dashboard .welcome-panel {
            padding: 22px;
        }

        .tenant-dashboard .dashboard-card {
            padding: 16px;
        }

        .tenant-dashboard .stat-value {
            font-size: 1.45rem;
        }

        .tenant-dashboard .rental-item {
            padding: 13px;
        }
    }
</style>

<div class="container-fluid py-4 tenant-dashboard">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="muted small mb-1">Tenant portal</div>
            <h1 class="page-heading mb-1">Dashboard</h1>
            <p class="muted mb-0">Manage your home, rent and maintenance in one place.</p>
        </div>

        @if(Route::has('tenant-portal.profile.index'))
        <a href="{{ route('tenant-portal.profile.index') }}" class="btn btn-outline-secondary rounded-3">
            <i class="bi bi-person-circle me-1"></i> My profile
        </a>
        @endif
    </div>

    {{-- Welcome --}}
    <section class="welcome-panel mb-4">
        <div class="welcome-content">
            <div class="welcome-eyebrow">Terra Smart Rental Management</div>
            <h2 class="welcome-title">Welcome back, {{ $firstName }}.</h2>
            <p class="welcome-description">
                Here is an overview of your rental information. You can check your lease,
                follow maintenance requests and keep your profile up to date.
            </p>

            <div class="d-flex flex-wrap gap-2">
                @if(Route::has('tenant-portal.rentals.index'))
                <a href="{{ route('tenant-portal.rentals.index') }}" class="btn btn-terra">
                    <i class="bi bi-house-door me-1"></i> My rentals
                </a>
                @endif

                @if(Route::has('tenant-portal.maintenance-requests.create'))
                <a href="{{ route('tenant-portal.maintenance-requests.create') }}" class="btn btn-light-outline">
                    <i class="bi bi-tools me-1"></i> Report a problem
                </a>
                @endif
            </div>
        </div>
    </section>

    {{-- Statistics --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="dashboard-card">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <span class="stat-label">Active rentals</span>
                    <span class="stat-icon"><i class="bi bi-house-check"></i></span>
                </div>
                <div class="stat-value">{{ $activeLeaseCount }}</div>
                <div class="muted small mt-1">Currently active leases</div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="dashboard-card">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <span class="stat-label">Monthly rent</span>
                    <span class="stat-icon"><i class="bi bi-wallet2"></i></span>
                </div>
                <div class="stat-value" style="font-size:clamp(1.05rem, 2vw, 1.7rem)">
                    {{ $currency($monthlyRentRwf, 'RWF') }}
                </div>

                @if($monthlyRentUsd > 0)
                <div class="muted small mt-1">
                    + {{ $currency($monthlyRentUsd, 'USD') }}
                </div>
                @endif
            </div>
            <div class="muted small mt-1">Combined active lease rent</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="dashboard-card">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                <span class="stat-label">Open requests</span>
                <span class="stat-icon"><i class="bi bi-tools"></i></span>
            </div>
            <div class="stat-value">{{ $openMaintenanceCount }}</div>
            <div class="muted small mt-1">Maintenance not yet resolved</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="dashboard-card">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                <span class="stat-label">Pending leases</span>
                <span class="stat-icon"><i class="bi bi-file-earmark-text"></i></span>
            </div>
            <div class="stat-value">{{ $pendingLeaseCount }}</div>
            <div class="muted small mt-1">Leases awaiting activation</div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Rentals --}}
    <div class="col-12 col-xl-8">
        <section class="dashboard-card">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h2 class="section-title">My rentals</h2>
                    <p class="muted small mb-0 mt-1">Your latest rental agreements and units.</p>
                </div>

                @if(Route::has('tenant-portal.rentals.index'))
                <a href="{{ route('tenant-portal.rentals.index') }}" class="section-link">
                    View all <i class="bi bi-arrow-right ms-1"></i>
                </a>
                @endif
            </div>

            @forelse($recentLeases as $lease)
            @php
            $unit = $lease->unit;
            $building = $unit?->building;
            $property = $building?->property;

            $unitLabel = $unit?->unit_number
            ?? $unit?->name
            ?? ('Unit #' . ($unit?->id ?? '—'));

            $propertyName = $property?->name
            ?? $building?->name
            ?? 'Rental property';

            $locationParts = array_filter([
            $property?->sector,
            $property?->district,
            $property?->province,
            ]);

            $leaseCurrency = $lease->currency ?: 'RWF';
            @endphp

            <div class="rental-item">
                <div class="d-flex align-items-start gap-3">
                    <div class="rental-icon">
                        <i class="bi bi-building"></i>
                    </div>

                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                            <div>
                                <a href="{{ Route::has('tenant-portal.leases.show') ? route('tenant-portal.leases.show', $lease->id) : (Route::has('tenant-portal.rentals.index') ? route('tenant-portal.rentals.index') : '#') }}"
                                    class="rental-name">
                                    {{ $propertyName }}
                                </a>
                                <div class="muted small mt-1">
                                    <i class="bi bi-door-open me-1"></i>{{ $unitLabel }}
                                    @if($building && $property)
                                    <span class="mx-1">·</span>{{ $building->name }}
                                    @endif
                                </div>
                            </div>

                            <span class="status-pill {{ $statusClass($lease->status) }}">
                                {{ $statusLabel($lease->status) }}
                            </span>
                        </div>

                        @if(count($locationParts))
                        <div class="muted small mt-2">
                            <i class="bi bi-geo-alt me-1"></i>{{ implode(', ', $locationParts) }}
                        </div>
                        @endif

                        <div class="row g-3 mt-1">
                            <div class="col-sm-6">
                                <div class="muted small">Monthly rent</div>
                                <div class="fw-bold">{{ $currency($lease->monthly_rent, $leaseCurrency) }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="muted small">Lease period</div>
                                <div class="fw-semibold small">
                                    {{ $lease->start_date ? \Illuminate\Support\Carbon::parse($lease->start_date)->format('d M Y') : '—' }}
                                    <span class="muted mx-1">to</span>
                                    {{ $lease->end_date ? \Illuminate\Support\Carbon::parse($lease->end_date)->format('d M Y') : 'Ongoing' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="empty-state">
                <i class="bi bi-house"></i>
                <h3>No rental agreements yet</h3>
                <p class="muted small mb-3">
                    Your rental agreements will appear here once they are available on your account.
                </p>
                @if(Route::has('tenant-portal.applications.index'))
                <a href="{{ route('tenant-portal.applications.index') }}" class="btn btn-terra btn-sm">
                    View my applications
                </a>
                @endif
            </div>
            @endforelse
        </section>

        {{-- Maintenance requests --}}
        <section class="dashboard-card mt-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h2 class="section-title">Recent maintenance</h2>
                    <p class="muted small mb-0 mt-1">Track repair and maintenance requests.</p>
                </div>

                @if(Route::has('tenant-portal.maintenance-requests.index'))
                <a href="{{ route('tenant-portal.maintenance-requests.index') }}" class="section-link">
                    View all <i class="bi bi-arrow-right ms-1"></i>
                </a>
                @endif
            </div>

            @forelse($recentMaintenanceRequests as $maintenanceRequest)
            @php
            $requestUnit = $maintenanceRequest->unit;
            $requestBuilding = $requestUnit?->building;
            @endphp

            <div class="request-item">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div class="flex-grow-1">
                        <div class="fw-bold">{{ $maintenanceRequest->title }}</div>
                        <div class="muted small mt-1">
                            {{ $requestUnit?->unit_number ?? $requestUnit?->name ?? 'Rental unit' }}
                            @if($requestBuilding)
                            <span class="mx-1">·</span>{{ $requestBuilding->name }}
                            @endif
                        </div>
                        <div class="muted small mt-2">
                            Submitted
                            {{ $maintenanceRequest->created_at?->format('d M Y') ?? '—' }}
                        </div>
                    </div>

                    <span class="status-pill {{ $statusClass($maintenanceRequest->status) }}">
                        {{ $statusLabel($maintenanceRequest->status) }}
                    </span>
                </div>

                @if(Route::has('tenant-portal.maintenance-requests.show'))
                <div class="mt-3">
                    <a href="{{ route('tenant-portal.maintenance-requests.show', $maintenanceRequest->id) }}"
                        class="section-link">
                        View request <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                @endif
            </div>
            @empty
            <div class="empty-state">
                <i class="bi bi-wrench-adjustable"></i>
                <h3>No maintenance requests</h3>
                <p class="muted small mb-3">Requests you submit will appear here so you can follow their progress.</p>
                @if(Route::has('tenant-portal.maintenance-requests.create'))
                <a href="{{ route('tenant-portal.maintenance-requests.create') }}" class="btn btn-terra btn-sm">
                    Submit a request
                </a>
                @endif
            </div>
            @endforelse
        </section>
    </div>

    {{-- Sidebar --}}
    <div class="col-12 col-xl-4">
        <section class="dashboard-card mb-4">
            <h2 class="section-title mb-3">Quick actions</h2>

            <div class="d-grid gap-2">
                @if(Route::has('tenant-portal.rentals.index'))
                <a href="{{ route('tenant-portal.rentals.index') }}" class="quick-action">
                    <span class="quick-action-icon"><i class="bi bi-house-door"></i></span>
                    <span class="flex-grow-1">
                        <span class="quick-action-title">My rentals</span>
                        <span class="quick-action-description">View your units and lease details</span>
                    </span>
                    <i class="bi bi-chevron-right muted"></i>
                </a>
                @endif

                @if(Route::has('tenant-portal.applications.index'))
                <a href="{{ route('tenant-portal.applications.index') }}" class="quick-action">
                    <span class="quick-action-icon"><i class="bi bi-file-earmark-text"></i></span>
                    <span class="flex-grow-1">
                        <span class="quick-action-title">My applications</span>
                        <span class="quick-action-description">Check your rental applications</span>
                    </span>
                    <i class="bi bi-chevron-right muted"></i>
                </a>
                @endif

                @if(Route::has('tenant-portal.maintenance-requests.index'))
                <a href="{{ route('tenant-portal.maintenance-requests.index') }}" class="quick-action">
                    <span class="quick-action-icon"><i class="bi bi-tools"></i></span>
                    <span class="flex-grow-1">
                        <span class="quick-action-title">Maintenance</span>
                        <span class="quick-action-description">Report an issue and track progress</span>
                    </span>
                    <i class="bi bi-chevron-right muted"></i>
                </a>
                @endif

                @if(Route::has('tenant-portal.profile.index'))
                <a href="{{ route('tenant-portal.profile.index') }}" class="quick-action">
                    <span class="quick-action-icon"><i class="bi bi-person-gear"></i></span>
                    <span class="flex-grow-1">
                        <span class="quick-action-title">My profile</span>
                        <span class="quick-action-description">Review your personal information</span>
                    </span>
                    <i class="bi bi-chevron-right muted"></i>
                </a>
                @endif
            </div>
        </section>

        {{-- Tenant information --}}
        <section class="dashboard-card">
            <div class="profile-summary mb-3">
                <div class="profile-avatar">
                    {{ strtoupper(substr($firstName, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <div class="fw-bold text-truncate">{{ $tenantName }}</div>
                    <div class="muted small">Tenant account</div>
                </div>
            </div>

            <div class="info-row">
                <span class="muted">Tenant number</span>
                <span class="info-value">{{ $tenant->tenant_number ?: 'Not assigned' }}</span>
            </div>

            <div class="info-row">
                <span class="muted">Phone</span>
                <span class="info-value">{{ $tenant->phone ?: 'Not provided' }}</span>
            </div>

            <div class="info-row">
                <span class="muted">Email</span>
                <span class="info-value">{{ $tenant->user?->email ?? auth()->user()->email ?? 'Not provided' }}</span>
            </div>

            <div class="info-row">
                <span class="muted">Account status</span>
                <span>
                    <span class="status-pill {{ $statusClass($tenant->status) }}">
                        {{ $statusLabel($tenant->status) }}
                    </span>
                </span>
            </div>

            @if(Route::has('tenant-portal.profile.index'))
            <a href="{{ route('tenant-portal.profile.index') }}"
                class="btn btn-outline-secondary w-100 rounded-3 mt-3">
                View profile <i class="bi bi-arrow-right ms-1"></i>
            </a>
            @endif
        </section>
    </div>
</div>

<div class="text-center muted small mt-4">
    Terra Smart Rental Management
    <span class="mx-1">·</span>
    Need help with your rental? Submit a maintenance request from your dashboard.
</div>
</div>
@endsection