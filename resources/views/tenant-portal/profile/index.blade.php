@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="container-fluid py-4 tenant-profile">

    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="text-muted small mb-2">
                Tenant Portal / My Profile
            </div>
            <h1 class="h3 fw-bold mb-1">My Profile</h1>
            <p class="text-muted mb-0">
                View your personal information and tenant account details.
            </p>
        </div>

        <a href="{{ route('tenant-portal.profile.edit') }}"
           class="btn btn-primary">
            <i class="bi bi-pencil-square me-2"></i>Edit Profile
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $name = $tenant->full_name ?? auth()->user()->name ?? 'Tenant';

        $initials = collect(explode(' ', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');

        $status = strtolower($tenant->status ?? 'active');

        $statusClass = match ($status) {
            'active' => 'success',
            'inactive' => 'secondary',
            'suspended' => 'danger',
            default => 'secondary',
        };
    @endphp

    <div class="row g-4">

        {{-- Left profile card --}}
        <div class="col-lg-4 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body text-center p-4">
                    <div class="profile-avatar mx-auto mb-3">
                        {{ $initials ?: 'T' }}
                    </div>

                    <h2 class="h5 fw-bold mb-1">{{ $name }}</h2>

                    <p class="text-muted small mb-3">
                        {{ $tenant->email ?? auth()->user()->email }}
                    </p>

                    <span class="badge rounded-pill text-bg-{{ $statusClass }} px-3 py-2">
                        {{ ucfirst($status) }} account
                    </span>

                    <hr class="my-4">

                    <div class="profile-summary">
                        <div class="summary-row">
                            <span>Tenant number</span>
                            <strong>{{ $tenant->tenant_number ?? 'Not assigned' }}</strong>
                        </div>

                        <div class="summary-row">
                            <span>Member since</span>
                            <strong>{{ $tenant->created_at?->format('d M Y') ?? '—' }}</strong>
                        </div>

                        <div class="summary-row">
                            <span>Phone number</span>
                            <strong>{{ $tenant->phone ?: 'Not provided' }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="profile-notice rounded-4 p-4">
                <div class="notice-icon mb-3">
                    <i class="bi bi-shield-check"></i>
                </div>

                <h3 class="h6 fw-bold">Your account information</h3>
                <p class="small text-muted mb-0">
                    Keep your contact information accurate so your property manager
                    can reach you about your rental and maintenance requests.
                </p>
            </div>
        </div>

        {{-- Right information panels --}}
        <div class="col-lg-8 col-xl-9">

            {{-- Personal information --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 px-4 pt-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="section-icon">
                            <i class="bi bi-person"></i>
                        </div>
                        <div>
                            <h2 class="h5 fw-bold mb-1">Personal information</h2>
                            <p class="text-muted small mb-0">
                                Your basic personal details.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Full name</span>
                                <div class="info-value">{{ $name }}</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Email address</span>
                                <div class="info-value">
                                    {{ $tenant->email ?? auth()->user()->email ?? '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Primary phone</span>
                                <div class="info-value">{{ $tenant->phone ?: '—' }}</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Alternative phone</span>
                                <div class="info-value">
                                    {{ $tenant->alternative_phone ?: '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="info-item">
                                <span class="info-label">Residential address</span>
                                <div class="info-value">
                                    {{ $tenant->address ?: 'No address provided' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Emergency contact --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 px-4 pt-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="section-icon">
                            <i class="bi bi-telephone"></i>
                        </div>
                        <div>
                            <h2 class="h5 fw-bold mb-1">Emergency contact</h2>
                            <p class="text-muted small mb-0">
                                Contact details provided for emergencies.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="info-item">
                                <span class="info-label">Contact name</span>
                                <div class="info-value">
                                    {{ $tenant->emergency_contact_name ?: 'Not provided' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="info-item">
                                <span class="info-label">Contact phone</span>
                                <div class="info-value">
                                    {{ $tenant->emergency_contact_phone ?: 'Not provided' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="info-item">
                                <span class="info-label">Relationship</span>
                                <div class="info-value">
                                    {{ $tenant->emergency_contact_relationship ?: 'Not specified' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('tenant-portal.profile.edit') }}"
                           class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-pencil me-1"></i>Update emergency contact
                        </a>
                    </div>
                </div>
            </div>

            {{-- Account information --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 px-4 pt-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="section-icon">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <div>
                            <h2 class="h5 fw-bold mb-1">Tenant account</h2>
                            <p class="text-muted small mb-0">
                                Information associated with your tenant record.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Tenant ID</span>
                                <div class="info-value">
                                    {{ $tenant->tenant_number ?? 'Not assigned' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Account status</span>
                                <div class="info-value">
                                    <span class="badge text-bg-{{ $statusClass }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Profile created</span>
                                <div class="info-value">
                                    {{ $tenant->created_at?->format('d M Y, h:i A') ?? '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Last updated</span>
                                <div class="info-value">
                                    {{ $tenant->updated_at?->format('d M Y, h:i A') ?? '—' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a href="{{ route('tenant-portal.rentals.index') }}"
                           class="btn btn-light border">
                            <i class="bi bi-house-door me-2"></i>My rentals
                        </a>

                        <a href="{{ route('tenant-portal.maintenance-requests.index') }}"
                           class="btn btn-light border">
                            <i class="bi bi-tools me-2"></i>Maintenance requests
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .tenant-profile {
        color: #202938;
        font-family: "DM Sans", sans-serif;
    }

    .tenant-profile .card {
        background: #fff;
    }

    .tenant-profile .btn-primary {
        background: #D05208;
        border-color: #D05208;
        border-radius: 10px;
    }

    .tenant-profile .btn-primary:hover {
        background: #b84808;
        border-color: #b84808;
    }

    .tenant-profile .btn-outline-primary {
        color: #D05208;
        border-color: #D05208;
        border-radius: 9px;
    }

    .tenant-profile .btn-outline-primary:hover {
        background: #D05208;
        color: #fff;
    }

    .profile-avatar {
        width: 92px;
        height: 92px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #fff0e6;
        border: 4px solid #fff;
        outline: 1px solid #f2d7c5;
        color: #D05208;
        font-size: 1.9rem;
        font-weight: 750;
    }

    .profile-summary {
        text-align: left;
    }

    .summary-row {
        display: flex;
        flex-direction: column;
        gap: 5px;
        padding: 13px 0;
        border-bottom: 1px solid #edf0f3;
    }

    .summary-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .summary-row span,
    .info-label {
        font-size: .78rem;
        color: #737d8b;
    }

    .summary-row strong {
        font-size: .88rem;
        overflow-wrap: anywhere;
    }

    .section-icon,
    .notice-icon {
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border-radius: 12px;
        background: #fff0e6;
        color: #D05208;
        font-size: 1.2rem;
    }

    .info-item {
        display: flex;
        flex-direction: column;
        gap: 7px;
        min-width: 0;
    }

    .info-value {
        font-size: .92rem;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .profile-notice {
        background: #fff8f3;
        border: 1px solid #fbe5d7;
    }

    @media (max-width: 575.98px) {
        .tenant-profile .card-body {
            padding: 1.15rem !important;
        }

        .tenant-profile .card-header {
            padding: 1.15rem 1.15rem 0 !important;
        }
    }
</style>
@endsection
