
@extends('layouts.app')

@section('title', 'Tenant Details | Terra Smart Rental Management')

@section('content')
@php
    $leases = $tenant->leases ?? collect();
    $applications = $tenant->applications ?? collect();
    $payments = $tenant->payments ?? collect();
    $invoices = $tenant->invoices ?? collect();

    $activeLeases = $leases->filter(function ($lease) {
        return strtolower((string) $lease->status) === 'active';
    });

    $tenantStatus = strtolower((string) $tenant->status);

    $statusClasses = [
        'active' => 'tenant-status-active',
        'inactive' => 'tenant-status-inactive',
        'pending' => 'tenant-status-pending',
        'suspended' => 'tenant-status-suspended',
    ];

    $statusClass = $statusClasses[$tenantStatus] ?? 'tenant-status-default';

    $initials = collect(explode(' ', trim($tenant->full_name ?? 'Tenant')))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');

    $formatMoney = function ($amount, $currency = 'RWF') {
        if (!is_numeric($amount)) {
            return '—';
        }

        return ($currency ?: 'RWF') . ' ' . number_format((float) $amount, 0);
    };

    $formatDate = function ($date) {
        if (!$date) {
            return '—';
        }

        try {
            return \Illuminate\Support\Carbon::parse($date)->format('d M Y');
        } catch (\Throwable $e) {
            return (string) $date;
        }
    };

    $display = fn ($value) => filled($value) ? $value : 'Not provided';
@endphp

<style>
    .tenant-page {
        --terra-orange: #D05208;
        --terra-navy: #19265d;
        --terra-muted: #718096;
        --terra-border: #e8edf3;
        --terra-surface: #f5f7fb;
        color: #243047;
        font-size: .92rem;
    }

    .tenant-page .page-heading {
        color: var(--terra-navy);
        font-weight: 750;
        letter-spacing: -.6px;
    }

    .tenant-page .text-muted {
        color: var(--terra-muted) !important;
    }

    .tenant-page .btn-terra {
        color: #fff;
        background: var(--terra-orange);
        border-color: var(--terra-orange);
        font-weight: 600;
    }

    .tenant-page .btn-terra:hover {
        color: #fff;
        background: #b74706;
        border-color: #b74706;
    }

    .tenant-page .btn-terra-outline {
        color: var(--terra-navy);
        border: 1px solid #dce3ed;
        background: #fff;
        font-weight: 600;
    }

    .tenant-page .btn-terra-outline:hover {
        background: #f3f5fa;
        border-color: #c7d0df;
    }

    .tenant-page .tenant-hero {
        position: relative;
        overflow: hidden;
        border: 1px solid var(--terra-border);
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 5px 22px rgba(25, 38, 93, .045);
    }

    .tenant-page .hero-accent {
        height: 5px;
        background: linear-gradient(90deg, var(--terra-orange), #f1a16d 55%, var(--terra-navy));
    }

    .tenant-page .tenant-avatar {
        display: flex;
        width: 76px;
        height: 76px;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        border: 4px solid #fff;
        border-radius: 20px;
        background: #fff0e7;
        color: var(--terra-orange);
        font-size: 1.65rem;
        font-weight: 800;
        box-shadow: 0 0 0 1px #f4d9c9;
    }

    .tenant-page .tenant-name {
        color: var(--terra-navy);
        font-size: 1.5rem;
        font-weight: 750;
        letter-spacing: -.5px;
        overflow-wrap: anywhere;
    }

    .tenant-page .tenant-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 20px;
        font-size: .75rem;
        font-weight: 700;
        text-transform: capitalize;
    }

    .tenant-page .tenant-status::before {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
        content: "";
    }

    .tenant-page .tenant-status-active {
        color: #18794e;
        background: #e8f7ef;
    }

    .tenant-page .tenant-status-inactive {
        color: #64748b;
        background: #eef2f7;
    }

    .tenant-page .tenant-status-pending {
        color: #9a6700;
        background: #fff6db;
    }

    .tenant-page .tenant-status-suspended {
        color: #b42318;
        background: #feeeee;
    }

    .tenant-page .tenant-status-default {
        color: #475569;
        background: #eef2f7;
    }

    .tenant-page .summary-card {
        height: 100%;
        padding: 18px;
        border: 1px solid var(--terra-border);
        border-radius: 14px;
        background: #fff;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .tenant-page .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 7px 20px rgba(25, 38, 93, .06);
    }

    .tenant-page .summary-icon {
        display: flex;
        width: 42px;
        height: 42px;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 1.15rem;
    }

    .tenant-page .icon-orange {
        color: var(--terra-orange);
        background: #fff0e7;
    }

    .tenant-page .icon-navy {
        color: var(--terra-navy);
        background: #edf0fb;
    }

    .tenant-page .icon-green {
        color: #16805d;
        background: #e8f7ef;
    }

    .tenant-page .icon-blue {
        color: #2463a7;
        background: #eaf3ff;
    }

    .tenant-page .summary-number {
        margin-top: 14px;
        color: var(--terra-navy);
        font-size: 1.7rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .tenant-page .summary-label {
        margin-top: 5px;
        color: var(--terra-muted);
        font-size: .82rem;
    }

    .tenant-page .section-card {
        overflow: hidden;
        border: 1px solid var(--terra-border);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 4px 16px rgba(25, 38, 93, .035);
    }

    .tenant-page .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 18px 20px;
        border-bottom: 1px solid var(--terra-border);
    }

    .tenant-page .section-title {
        margin: 0;
        color: var(--terra-navy);
        font-size: 1rem;
        font-weight: 750;
    }

    .tenant-page .section-subtitle {
        margin: 4px 0 0;
        color: var(--terra-muted);
        font-size: .8rem;
    }

    .tenant-page .section-body {
        padding: 20px;
    }

    .tenant-page .detail-item {
        min-width: 0;
        padding: 13px 14px;
        border: 1px solid #edf0f5;
        border-radius: 11px;
        background: #fbfcfe;
        height: 100%;
    }

    .tenant-page .detail-label {
        display: block;
        margin-bottom: 7px;
        color: var(--terra-muted);
        font-size: .76rem;
        font-weight: 600;
    }

    .tenant-page .detail-value {
        color: #26334a;
        font-size: .9rem;
        font-weight: 650;
        overflow-wrap: anywhere;
        white-space: pre-line;
    }

    .tenant-page .detail-value a {
        color: var(--terra-navy);
        text-decoration: none;
    }

    .tenant-page .detail-value a:hover {
        color: var(--terra-orange);
    }

    .tenant-page .account-panel {
        border: 1px solid #e7eaf5;
        border-radius: 13px;
        background: #f8f9fe;
        padding: 16px;
    }

    .tenant-page .account-icon {
        display: flex;
        width: 45px;
        height: 45px;
        align-items: center;
        justify-content: center;
        border-radius: 13px;
        color: var(--terra-navy);
        background: #e9edfb;
        font-size: 1.25rem;
    }

    .tenant-page .account-linked {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #18794e;
        font-size: .78rem;
        font-weight: 700;
    }

    .tenant-page .credential-panel {
        border: 1px solid #f0d8a5;
        border-radius: 14px;
        background: #fffbf1;
        padding: 20px;
    }

    .tenant-page .credential-panel .form-control {
        min-width: 0;
        border-color: #e7d8b8;
        background: #fff;
    }

    .tenant-page .credential-warning {
        color: #8b5b06;
        font-size: .82rem;
    }

    .tenant-page .table {
        --bs-table-bg: transparent;
        margin-bottom: 0;
    }

    .tenant-page .table thead th {
        padding: 12px 16px;
        border-bottom: 1px solid var(--terra-border);
        background: #f8f9fc;
        color: #718096;
        font-size: .72rem;
        font-weight: 750;
        letter-spacing: .4px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .tenant-page .table tbody td {
        padding: 14px 16px;
        border-color: #edf0f5;
        vertical-align: middle;
        font-size: .84rem;
    }

    .tenant-page .record-primary {
        color: var(--terra-navy);
        font-weight: 700;
    }

    .tenant-page .record-secondary {
        margin-top: 3px;
        color: var(--terra-muted);
        font-size: .76rem;
    }

    .tenant-page .record-status {
        display: inline-block;
        padding: 5px 9px;
        border-radius: 20px;
        background: #eef2f7;
        color: #475569;
        font-size: .72rem;
        font-weight: 700;
        text-transform: capitalize;
    }

    .tenant-page .empty-state {
        padding: 28px 20px;
        text-align: center;
        color: var(--terra-muted);
    }

    .tenant-page .empty-state i {
        display: block;
        margin-bottom: 9px;
        color: #aab4c5;
        font-size: 1.7rem;
    }

    .tenant-page .empty-state p {
        margin-bottom: 0;
        font-size: .85rem;
    }

    .tenant-page .record-meta {
        display: flex;
        justify-content: space-between;
        gap: 15px;
        padding: 11px 0;
        border-bottom: 1px solid #edf0f5;
        font-size: .83rem;
    }

    .tenant-page .record-meta:last-child {
        border-bottom: 0;
    }

    .tenant-page .record-meta span:first-child {
        color: var(--terra-muted);
    }

    .tenant-page .record-meta span:last-child {
        color: var(--terra-navy);
        font-weight: 650;
        text-align: right;
        overflow-wrap: anywhere;
    }

    .tenant-page .quick-action {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 12px 0;
        color: #344054;
        text-decoration: none;
        border-bottom: 1px solid #edf0f5;
        font-weight: 600;
        font-size: .84rem;
    }

    .tenant-page .quick-action:last-child {
        border-bottom: 0;
    }

    .tenant-page .quick-action i {
        color: var(--terra-orange);
        font-size: 1.05rem;
    }

    .tenant-page .quick-action:hover {
        color: var(--terra-orange);
    }

    .tenant-page .credential-panel .form-label {
        color: #6d5019;
        font-size: .78rem;
        font-weight: 700;
    }

    @media (max-width: 767.98px) {
        .tenant-page .section-body {
            padding: 15px;
        }

        .tenant-page .section-header {
            padding: 15px;
        }

        .tenant-page .tenant-avatar {
            width: 62px;
            height: 62px;
            border-radius: 16px;
            font-size: 1.35rem;
        }

        .tenant-page .tenant-name {
            font-size: 1.2rem;
        }

        .tenant-page .summary-card {
            padding: 14px;
        }

        .tenant-page .summary-number {
            font-size: 1.45rem;
        }
    }
</style>

<div class="container-fluid py-4 tenant-page">

    {{-- Page heading --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="small text-muted mb-1">
                <i class="bi bi-house-door me-1"></i>
                Terra Smart Rental Management
                <span class="mx-1">/</span>
                <a href="{{ route('admin.tenants.index') }}"
                   class="text-decoration-none"
                   style="color:#D05208;">Tenants</a>
            </div>

            <h2 class="page-heading mb-1">Tenant Profile</h2>
            <p class="text-muted mb-0">
                View tenant information, account access, rental activity and financial records.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.tenants.index') }}"
               class="btn btn-terra-outline">
                <i class="bi bi-arrow-left me-1"></i> Back to Tenants
            </a>

            @if($tenant->user)
                <form method="POST" action="{{ route('admin.tenants.resend-credentials', $tenant) }}" class="m-0"
                      onsubmit="return confirm('Send {{ $tenant->full_name }} new login details by email? Their current password will stop working.');">
                    @csrf
                    <button class="btn btn-terra-outline"><i class="bi bi-envelope-arrow-up me-1"></i> Resend credentials</button>
                </form>
            @endif

            <a href="{{ route('admin.tenants.edit', $tenant) }}"
               class="btn btn-terra">
                <i class="bi bi-pencil-square me-1"></i> Edit Tenant
            </a>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4"
             role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"
                    aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4"
             role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"
                    aria-label="Close"></button>
        </div>
    @endif

    {{-- Newly generated credentials --}}
    @if(session()->has('generated_password'))
        <div class="credential-panel mb-4">
            <div class="d-flex align-items-start gap-3 mb-3">
                <div class="summary-icon"
                     style="background:#ffefc5;color:#8b5b06;">
                    <i class="bi bi-shield-lock"></i>
                </div>

                <div>
                    <h5 class="mb-1 fw-bold" style="color:#74500a;">
                        New Tenant Login Credentials
                    </h5>
                    <p class="credential-warning mb-0">
                        Copy these credentials now and share them securely with the tenant.
                        The temporary password will not be available after this session message expires.
                    </p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <label for="generatedEmail" class="form-label">Login email</label>
                    <div class="input-group">
                        <input type="text"
                               class="form-control"
                               id="generatedEmail"
                               value="{{ session('generated_email') }}"
                               readonly>
                        <button type="button"
                                class="btn btn-outline-secondary"
                                onclick="copyCredential('generatedEmail', this)">
                            <i class="bi bi-clipboard me-1"></i> Copy
                        </button>
                    </div>
                </div>

                <div class="col-lg-6">
                    <label for="generatedPassword" class="form-label">Temporary password</label>
                    <div class="input-group">
                        <input type="password"
                               class="form-control"
                               id="generatedPassword"
                               value="{{ session('generated_password') }}"
                               autocomplete="new-password"
                               readonly>

                        <button type="button"
                                class="btn btn-outline-secondary"
                                id="togglePassword"
                                onclick="toggleGeneratedPassword()">
                            Show
                        </button>

                        <button type="button"
                                class="btn btn-outline-secondary"
                                onclick="copyCredential('generatedPassword', this)">
                            <i class="bi bi-clipboard me-1"></i> Copy
                        </button>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
                <button type="button"
                        class="btn btn-dark"
                        onclick="copyAllCredentials(this)">
                    <i class="bi bi-clipboard-check me-1"></i>
                    Copy Both Credentials
                </button>

                <span class="small text-muted">
                    <i class="bi bi-lock me-1"></i>
                    Do not share credentials publicly.
                </span>
            </div>

            <div id="credentialFeedback" class="small mt-2" aria-live="polite"></div>
        </div>
    @endif

    {{-- Tenant hero --}}
    <div class="tenant-hero mb-4">
        <div class="hero-accent"></div>

        <div class="p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="tenant-avatar">
                        {{ $initials ?: 'T' }}
                    </div>

                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="tenant-status {{ $statusClass }}">
                                {{ $tenant->status ?: 'Unknown' }}
                            </span>

                            @if($tenant->user)
                                <span class="account-linked">
                                    <i class="bi bi-patch-check-fill"></i>
                                    Login linked
                                </span>
                            @else
                                <span class="record-status">No login account</span>
                            @endif
                        </div>

                        <h3 class="tenant-name mb-1">
                            {{ $tenant->full_name }}
                        </h3>

                        <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
                            <span>
                                <i class="bi bi-person-badge me-1"></i>
                                {{ $tenant->tenant_number }}
                            </span>

                            @if($tenant->email)
                                <span>
                                    <i class="bi bi-envelope me-1"></i>
                                    {{ $tenant->email }}
                                </span>
                            @endif

                            @if($tenant->phone)
                                <span>
                                    <i class="bi bi-telephone me-1"></i>
                                    {{ $tenant->phone }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @if($tenant->email)
                        <a href="mailto:{{ $tenant->email }}"
                           class="btn btn-terra-outline btn-sm">
                            <i class="bi bi-envelope me-1"></i> Email Tenant
                        </a>
                    @endif

                    @if($tenant->phone)
                        <a href="tel:{{ $tenant->phone }}"
                           class="btn btn-terra-outline btn-sm">
                            <i class="bi bi-telephone me-1"></i> Call Tenant
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Overview metrics --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl">
            <div class="summary-card">
                <div class="summary-icon icon-orange">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div class="summary-number">{{ $leases->count() }}</div>
                <div class="summary-label">Total Leases</div>
            </div>
        </div>

        <div class="col-6 col-xl">
            <div class="summary-card">
                <div class="summary-icon icon-green">
                    <i class="bi bi-house-check"></i>
                </div>
                <div class="summary-number">{{ $activeLeases->count() }}</div>
                <div class="summary-label">Active Leases</div>
            </div>
        </div>

        <div class="col-6 col-xl">
            <div class="summary-card">
                <div class="summary-icon icon-navy">
                    <i class="bi bi-send"></i>
                </div>
                <div class="summary-number">{{ $applications->count() }}</div>
                <div class="summary-label">Applications</div>
            </div>
        </div>

        <div class="col-6 col-xl">
            <div class="summary-card">
                <div class="summary-icon icon-blue">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="summary-number">{{ $payments->count() }}</div>
                <div class="summary-label">Payment Records</div>
            </div>
        </div>

        <div class="col-6 col-xl">
            <div class="summary-card">
                <div class="summary-icon icon-orange">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="summary-number">{{ $invoices->count() }}</div>
                <div class="summary-label">Invoices</div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Main content --}}
        <div class="col-xl-8">

            {{-- Personal information --}}
            <div class="section-card mb-4">
                <div class="section-header">
                    <div>
                        <h5 class="section-title">
                            <i class="bi bi-person-vcard me-2" style="color:#D05208;"></i>
                            Personal Information
                        </h5>
                        <p class="section-subtitle">Tenant identity and contact details</p>
                    </div>

                    <a href="{{ route('admin.tenants.edit', $tenant) }}"
                       class="btn btn-sm btn-terra-outline">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                </div>

                <div class="section-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Full Name</span>
                                <div class="detail-value">{{ $tenant->full_name }}</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Tenant Number</span>
                                <div class="detail-value">{{ $tenant->tenant_number }}</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Email Address</span>
                                <div class="detail-value">
                                    @if($tenant->email)
                                        <a href="mailto:{{ $tenant->email }}">
                                            {{ $tenant->email }}
                                        </a>
                                    @else
                                        Not provided
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Primary Phone</span>
                                <div class="detail-value">
                                    @if($tenant->phone)
                                        <a href="tel:{{ $tenant->phone }}">
                                            {{ $tenant->phone }}
                                        </a>
                                    @else
                                        Not provided
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Alternative Phone</span>
                                <div class="detail-value">
                                    {{ $display($tenant->alternative_phone) }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Tenant Status</span>
                                <div class="detail-value">
                                    <span class="tenant-status {{ $statusClass }}">
                                        {{ $tenant->status ?: 'Unknown' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="detail-item">
                                <span class="detail-label">Residential Address</span>
                                <div class="detail-value">{{ $display($tenant->address) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Emergency contact --}}
            <div class="section-card mb-4">
                <div class="section-header">
                    <div>
                        <h5 class="section-title">
                            <i class="bi bi-telephone-forward me-2" style="color:#D05208;"></i>
                            Emergency Contact
                        </h5>
                        <p class="section-subtitle">Person to contact in an emergency</p>
                    </div>
                </div>

                <div class="section-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Contact Name</span>
                                <div class="detail-value">
                                    {{ $display($tenant->emergency_contact_name) }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Contact Phone</span>
                                <div class="detail-value">
                                    @if($tenant->emergency_contact_phone)
                                        <a href="tel:{{ $tenant->emergency_contact_phone }}">
                                            {{ $tenant->emergency_contact_phone }}
                                        </a>
                                    @else
                                        Not provided
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Lease history --}}
            <div class="section-card mb-4">
                <div class="section-header">
                    <div>
                        <h5 class="section-title">
                            <i class="bi bi-file-earmark-check me-2" style="color:#D05208;"></i>
                            Lease History
                        </h5>
                        <p class="section-subtitle">Rental agreements associated with this tenant</p>
                    </div>
                    <span class="record-status">{{ $leases->count() }} records</span>
                </div>

                @if($leases->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Lease</th>
                                    <th>Unit</th>
                                    <th>Rental Period</th>
                                    <th>Monthly Rent</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($leases as $lease)
                                    <tr>
                                        <td>
                                            <div class="record-primary">
                                                {{ data_get($lease, 'lease_number') ?: ('Lease #' . $lease->id) }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="record-primary">
                                                {{ data_get($lease, 'unit.unit_number') ?: (data_get($lease, 'unit_id') ? 'Unit #' . data_get($lease, 'unit_id') : '—') }}
                                            </div>
                                            @if(data_get($lease, 'unit.managedProperty.name'))
                                                <div class="record-secondary">
                                                    {{ data_get($lease, 'unit.managedProperty.name') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $formatDate(data_get($lease, 'start_date')) }}</div>
                                            <div class="record-secondary">
                                                to {{ $formatDate(data_get($lease, 'end_date')) }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="record-primary">
                                                {{ $formatMoney(data_get($lease, 'monthly_rent'), data_get($lease, 'currency', 'RWF')) }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="record-status">
                                                {{ data_get($lease, 'status') ?: 'Unknown' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bi bi-file-earmark-x"></i>
                        <p class="fw-semibold text-dark">No leases found</p>
                        <p>This tenant does not have any lease records yet.</p>
                    </div>
                @endif
            </div>

            {{-- Applications --}}
            <div class="section-card mb-4">
                <div class="section-header">
                    <div>
                        <h5 class="section-title">
                            <i class="bi bi-clipboard2-check me-2" style="color:#D05208;"></i>
                            Rental Applications
                        </h5>
                        <p class="section-subtitle">Applications submitted by this tenant</p>
                    </div>
                    <span class="record-status">{{ $applications->count() }} records</span>
                </div>

                @if($applications->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Application</th>
                                    <th>Unit</th>
                                    <th>Applied On</th>
                                    <th>Move-in Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($applications as $application)
                                    <tr>
                                        <td>
                                            <div class="record-primary">
                                                {{ data_get($application, 'application_number') ?: ('Application #' . $application->id) }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="record-primary">
                                                {{ data_get($application, 'unit.unit_number') ?: (data_get($application, 'unit_id') ? 'Unit #' . data_get($application, 'unit_id') : '—') }}
                                            </div>
                                            @if(data_get($application, 'unit.managedProperty.name'))
                                                <div class="record-secondary">
                                                    {{ data_get($application, 'unit.managedProperty.name') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ $formatDate(data_get($application, 'application_date')) }}</td>
                                        <td>{{ $formatDate(data_get($application, 'desired_move_in_date')) }}</td>
                                        <td>
                                            <span class="record-status">
                                                {{ data_get($application, 'status') ?: 'Unknown' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bi bi-clipboard2-x"></i>
                        <p class="fw-semibold text-dark">No applications found</p>
                        <p>No rental applications are linked to this tenant.</p>
                    </div>
                @endif
            </div>

            {{-- Payment history --}}
            <div class="section-card mb-4">
                <div class="section-header">
                    <div>
                        <h5 class="section-title">
                            <i class="bi bi-wallet2 me-2" style="color:#D05208;"></i>
                            Rent Payment History
                        </h5>
                        <p class="section-subtitle">Recorded payments associated with this tenant</p>
                    </div>
                    <span class="record-status">{{ $payments->count() }} records</span>
                </div>

                @if($payments->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Receipt</th>
                                    <th>Payment Date</th>
                                    <th>Method</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payments as $payment)
                                    <tr>
                                        <td>
                                            <div class="record-primary">
                                                {{ data_get($payment, 'receipt_number') ?: (data_get($payment, 'payment_number') ?: ('Payment #' . $payment->id)) }}
                                            </div>
                                            @if(data_get($payment, 'transaction_reference'))
                                                <div class="record-secondary">
                                                    Ref: {{ data_get($payment, 'transaction_reference') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $formatDate(data_get($payment, 'payment_date') ?: data_get($payment, 'paid_at') ?: data_get($payment, 'date')) }}
                                        </td>
                                        <td>
                                            {{ ucwords(str_replace('_', ' ', data_get($payment, 'payment_method') ?: data_get($payment, 'method') ?: '—')) }}
                                        </td>
                                        <td>
                                            <div class="record-primary">
                                                {{ $formatMoney(data_get($payment, 'amount'), data_get($payment, 'currency', 'RWF')) }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="record-status">
                                                {{ data_get($payment, 'status') ?: 'Unknown' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bi bi-wallet"></i>
                        <p class="fw-semibold text-dark">No payment records</p>
                        <p>Payments recorded against this tenant will appear here.</p>
                    </div>
                @endif
            </div>

            {{-- Invoice history --}}
            <div class="section-card mb-4">
                <div class="section-header">
                    <div>
                        <h5 class="section-title">
                            <i class="bi bi-receipt me-2" style="color:#D05208;"></i>
                            Invoice History
                        </h5>
                        <p class="section-subtitle">Invoices associated with this tenant</p>
                    </div>
                    <span class="record-status">{{ $invoices->count() }} records</span>
                </div>

                @if($invoices->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Invoice Type</th>
                                    <th>Billing Period</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoices as $invoice)
                                    <tr>
                                        <td>
                                            <div class="record-primary">
                                                {{ data_get($invoice, 'invoice_number') ?: ('Invoice #' . $invoice->id) }}
                                            </div>
                                        </td>
                                        <td>
                                            {{ ucwords(str_replace('_', ' ', data_get($invoice, 'invoice_type') ?: 'Invoice')) }}
                                        </td>
                                        <td>
                                            <div>{{ $formatDate(data_get($invoice, 'billing_period_start')) }}</div>
                                            <div class="record-secondary">
                                                to {{ $formatDate(data_get($invoice, 'billing_period_end')) }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="record-primary">
                                                {{ $formatMoney(data_get($invoice, 'total_amount') ?? data_get($invoice, 'amount') ?? data_get($invoice, 'invoice_amount'), data_get($invoice, 'currency', 'RWF')) }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="record-status">
                                                {{ data_get($invoice, 'status') ?: 'Unknown' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bi bi-receipt-cutoff"></i>
                        <p class="fw-semibold text-dark">No invoices found</p>
                        <p>Invoices linked to this tenant will appear here.</p>
                    </div>
                @endif
            </div>

            {{-- Internal notes --}}
            <div class="section-card mb-4">
                <div class="section-header">
                    <div>
                        <h5 class="section-title">
                            <i class="bi bi-journal-text me-2" style="color:#D05208;"></i>
                            Internal Notes
                        </h5>
                        <p class="section-subtitle">Private notes for property management staff</p>
                    </div>
                </div>

                <div class="section-body">
                    @if(filled($tenant->notes))
                        <div class="detail-item">
                            <div class="detail-value fw-normal">{{ $tenant->notes }}</div>
                        </div>
                    @else
                        <div class="text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            No internal notes have been added for this tenant.
                        </div>
                    @endif
                </div>
            </div>
            {{-- Documents & contracts --}}
            @include('documents._profile-panel', [
                'owner' => $tenant,
                'ownerKey' => 'tenant',
                'documents' => $tenant->documents()->with('uploader:id,name')->latest()->get(),
                'canUpload' => true,
                'heading' => 'Tenant documents & contracts',
            ])
        </div>

        {{-- Sidebar --}}
        <div class="col-xl-4">

            {{-- Login account --}}
            <div class="section-card mb-4">
                <div class="section-header">
                    <div>
                        <h5 class="section-title">
                            <i class="bi bi-shield-lock me-2" style="color:#D05208;"></i>
                            Login Account
                        </h5>
                        <p class="section-subtitle">Tenant portal access</p>
                    </div>
                </div>

                <div class="section-body">
                    @if($tenant->user)
                        <div class="account-panel">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="account-icon">
                                    <i class="bi bi-person-check"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="fw-bold text-dark text-break">
                                        {{ $tenant->user->name }}
                                    </div>
                                    <div class="small text-muted">Linked user account</div>
                                </div>
                            </div>

                            <div class="record-meta">
                                <span>Account email</span>
                                <span>{{ $tenant->user->email }}</span>
                            </div>

                            <div class="record-meta">
                                <span>User ID</span>
                                <span>{{ $tenant->user_id }}</span>
                            </div>

                            <div class="mt-3">
                                <span class="account-linked">
                                    <i class="bi bi-check-circle-fill"></i>
                                    Account linked successfully
                                </span>
                            </div>
                        </div>

                        <div class="small text-muted mt-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Passwords for existing accounts cannot be viewed. Use your password-reset workflow if the tenant needs a new password.
                        </div>
                    @else
                        <div class="empty-state py-3">
                            <i class="bi bi-person-lock"></i>
                            <p class="fw-semibold text-dark">No account linked</p>
                            <p>This tenant does not currently have a linked login account.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Tenant record --}}
            <div class="section-card mb-4">
                <div class="section-header">
                    <div>
                        <h5 class="section-title">
                            <i class="bi bi-info-circle me-2" style="color:#D05208;"></i>
                            Tenant Record
                        </h5>
                        <p class="section-subtitle">Record identification and timestamps</p>
                    </div>
                </div>

                <div class="section-body">
                    <div class="record-meta">
                        <span>Tenant ID</span>
                        <span>#{{ $tenant->id }}</span>
                    </div>

                    <div class="record-meta">
                        <span>Tenant number</span>
                        <span>{{ $tenant->tenant_number }}</span>
                    </div>

                    <div class="record-meta">
                        <span>Created on</span>
                        <span>{{ $formatDate($tenant->created_at) }}</span>
                    </div>

                    <div class="record-meta">
                        <span>Last updated</span>
                        <span>{{ $formatDate($tenant->updated_at) }}</span>
                    </div>

                    @if($tenant->deleted_at)
                        <div class="record-meta">
                            <span>Deleted on</span>
                            <span class="text-danger">
                                {{ $formatDate($tenant->deleted_at) }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick actions --}}
            <div class="section-card mb-4">
                <div class="section-header">
                    <div>
                        <h5 class="section-title">
                            <i class="bi bi-lightning-charge me-2" style="color:#D05208;"></i>
                            Quick Actions
                        </h5>
                        <p class="section-subtitle">Contact and manage this tenant</p>
                    </div>
                </div>

                <div class="section-body py-2">
                    <a href="{{ route('admin.tenants.edit', $tenant) }}"
                       class="quick-action">
                        <i class="bi bi-pencil-square"></i>
                        <span>Edit tenant information</span>
                        <i class="bi bi-chevron-right ms-auto"></i>
                    </a>

                    @if($tenant->email)
                        <a href="mailto:{{ $tenant->email }}"
                           class="quick-action">
                            <i class="bi bi-envelope"></i>
                            <span>Send email</span>
                            <i class="bi bi-chevron-right ms-auto"></i>
                        </a>
                    @endif

                    @if($tenant->phone)
                        <a href="tel:{{ $tenant->phone }}"
                           class="quick-action">
                            <i class="bi bi-telephone"></i>
                            <span>Call tenant</span>
                            <i class="bi bi-chevron-right ms-auto"></i>
                        </a>
                    @endif

                    @if($tenant->emergency_contact_phone)
                        <a href="tel:{{ $tenant->emergency_contact_phone }}"
                           class="quick-action">
                            <i class="bi bi-telephone-forward"></i>
                            <span>Call emergency contact</span>
                            <i class="bi bi-chevron-right ms-auto"></i>
                        </a>
                    @endif

                    <a href="{{ route('admin.tenants.index') }}"
                       class="quick-action">
                        <i class="bi bi-people"></i>
                        <span>View all tenants</span>
                        <i class="bi bi-chevron-right ms-auto"></i>
                    </a>
                </div>
            </div>

            {{-- Privacy reminder --}}
            <div class="p-3 rounded-3"
                 style="background:#f1f4fb;border:1px solid #e3e8f5;">
                <div class="d-flex gap-2">
                    <i class="bi bi-shield-check fs-5"
                       style="color:#19265d;"></i>
                    <div>
                        <div class="fw-bold small mb-1" style="color:#19265d;">
                            Tenant data protection
                        </div>
                        <p class="text-muted small mb-0">
                            Keep contact details, emergency information, internal notes and login credentials confidential.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function showCredentialFeedback(message, isError = false) {
        const feedback = document.getElementById('credentialFeedback');

        if (!feedback) {
            return;
        }

        feedback.textContent = message;
        feedback.className = 'small mt-2 ' + (isError ? 'text-danger' : 'text-success');
    }

    function toggleGeneratedPassword() {
        const input = document.getElementById('generatedPassword');
        const button = document.getElementById('togglePassword');

        if (!input || !button) {
            return;
        }

        const shouldShow = input.type === 'password';

        input.type = shouldShow ? 'text' : 'password';
        button.textContent = shouldShow ? 'Hide' : 'Show';
    }

    async function copyTextToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return;
        }

        const temporaryInput = document.createElement('textarea');

        temporaryInput.value = text;
        temporaryInput.setAttribute('readonly', '');
        temporaryInput.style.position = 'fixed';
        temporaryInput.style.opacity = '0';

        document.body.appendChild(temporaryInput);
        temporaryInput.select();

        const copied = document.execCommand('copy');

        temporaryInput.remove();

        if (!copied) {
            throw new Error('Clipboard access was unsuccessful.');
        }
    }

    async function copyCredential(id, button) {
        const input = document.getElementById(id);

        if (!input) {
            return;
        }

        try {
            await copyTextToClipboard(input.value);
            showCredentialFeedback(
                id === 'generatedEmail'
                    ? 'Login email copied to clipboard.'
                    : 'Temporary password copied to clipboard.'
            );
        } catch (error) {
            input.type = 'text';
            input.focus();
            input.select();

            showCredentialFeedback(
                'Automatic copying failed. Select and copy the text manually.',
                true
            );
        }
    }

    async function copyAllCredentials(button) {
        const email = document.getElementById('generatedEmail');
        const password = document.getElementById('generatedPassword');

        if (!email || !password) {
            return;
        }

        const credentials =
            'Email: ' + email.value +
            '\nTemporary password: ' + password.value;

        try {
            await copyTextToClipboard(credentials);
            showCredentialFeedback(
                'Both credentials copied. Share them securely with the tenant.'
            );
        } catch (error) {
            showCredentialFeedback(
                'Automatic copying failed. Copy the email and password separately.',
                true
            );
        }
    }
</script>
@endsection
