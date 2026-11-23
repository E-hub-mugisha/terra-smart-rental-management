@extends('layouts.app')

@section('title', 'Tenant Management')

@section('page_heading', 'Tenant Management')

@section('page_subtitle', 'Manage tenant profiles, account access and contact information.')

@push('styles')
<style>
    .tenants-page {
        --tnt-orange: var(--terra-orange, #D05208);
        --tnt-orange-dark: #AD4307;
        --tnt-orange-soft: #FFF0E7;
        --tnt-navy: var(--terra-navy, #19265D);
        --tnt-border: var(--terra-border, #E7EAF1);
        --tnt-muted: var(--terra-muted, #778197);
        color: #263047;
    }

    /* Heading */
    .tenants-page .page-intro {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
        margin-bottom: 24px;
    }

    .tenants-page .breadcrumb-line {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 11px;
        color: var(--tnt-muted);
        font-size: 11px;
    }

    .tenants-page .breadcrumb-line a {
        color: var(--tnt-muted);
        text-decoration: none;
    }

    .tenants-page .breadcrumb-line a:hover {
        color: var(--tnt-orange);
    }

    .tenants-page .breadcrumb-line i {
        font-size: 10px;
        color: #AAB1BF;
    }

    .tenants-page .page-title {
        margin: 0;
        color: var(--tnt-navy);
        font-size: clamp(23px, 2vw, 29px);
        line-height: 1.25;
        letter-spacing: -.8px;
        font-weight: 800;
    }

    .tenants-page .page-description {
        margin: 8px 0 0;
        color: var(--tnt-muted);
        font-size: 12px;
        line-height: 1.7;
    }

    .tenants-page .btn-tnt-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 42px;
        padding: 11px 16px;
        border: 1px solid var(--tnt-orange);
        border-radius: 10px;
        background: var(--tnt-orange);
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: .2s ease;
        white-space: nowrap;
    }

    .tenants-page .btn-tnt-primary:hover {
        background: var(--tnt-orange-dark);
        border-color: var(--tnt-orange-dark);
        color: #fff;
        transform: translateY(-1px);
    }

    /* Stat cards */
    .tenants-page .tnt-stat-card {
        display: block;
        position: relative;
        height: 100%;
        min-height: 112px;
        padding: 18px;
        background: #fff;
        border: 1px solid var(--tnt-border);
        border-radius: 14px;
        box-shadow: 0 5px 20px rgba(25, 38, 93, .035);
        color: inherit;
        text-decoration: none;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }

    .tenants-page a.tnt-stat-card:hover {
        transform: translateY(-2px);
        border-color: #D9DEEA;
        box-shadow: 0 9px 24px rgba(25, 38, 93, .08);
        color: inherit;
    }

    .tenants-page .tnt-stat-card.is-active {
        border-color: var(--tnt-orange);
        box-shadow: 0 0 0 3px rgba(208, 82, 8, .08);
    }

    .tenants-page .tnt-stat-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
    }

    .tenants-page .tnt-stat-label {
        color: var(--tnt-muted);
        font-size: 11px;
        font-weight: 600;
    }

    .tenants-page .tnt-stat-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 37px;
        height: 37px;
        flex-shrink: 0;
        border-radius: 11px;
        font-size: 17px;
    }

    .tenants-page .stat-orange {
        background: #FFF0E7;
        color: #D05208;
    }

    .tenants-page .stat-green {
        background: #E8F7EF;
        color: #16845B;
    }

    .tenants-page .stat-blue {
        background: #E8F4FD;
        color: #1875A8;
    }

    .tenants-page .stat-amber {
        background: #FFF4DF;
        color: #A66A09;
    }

    .tenants-page .tnt-stat-number {
        margin-top: 10px;
        color: var(--tnt-navy);
        font-size: 27px;
        line-height: 1.1;
        font-weight: 800;
        letter-spacing: -.8px;
    }

    .tenants-page .tnt-stat-footnote {
        margin-top: 6px;
        color: var(--tnt-muted);
        font-size: 11px;
    }

    /* Filter card */
    .tenants-page .tnt-filter-card {
        padding: 18px;
        background: #fff;
        border: 1px solid var(--tnt-border);
        border-radius: 14px;
        box-shadow: 0 5px 20px rgba(25, 38, 93, .035);
    }

    .tenants-page .form-label {
        display: block;
        margin-bottom: 7px;
        color: #4B556B;
        font-size: 11px;
        font-weight: 700;
    }

    .tenants-page .form-control,
    .tenants-page .form-select,
    .tenants-page .input-group-text {
        min-height: 42px;
        border-color: #E1E5ED;
        font-size: 12px;
        box-shadow: none;
    }

    .tenants-page .form-control,
    .tenants-page .form-select {
        color: #30394D;
        border-radius: 9px;
    }

    .tenants-page .input-group .input-group-text {
        border-radius: 9px 0 0 9px;
        background: #FAFBFD;
        color: #7D8799;
        padding: 0 13px;
    }

    .tenants-page .input-group .form-control {
        border-radius: 0 9px 9px 0;
    }

    .tenants-page .form-control:focus,
    .tenants-page .form-select:focus {
        border-color: #E8A47E;
        box-shadow: 0 0 0 3px rgba(208, 82, 8, .08);
    }

    .tenants-page .btn-filter {
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        border: 1px solid var(--tnt-orange);
        border-radius: 9px;
        padding: 10px 14px;
        background: var(--tnt-orange);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    .tenants-page .btn-filter:hover {
        background: var(--tnt-orange-dark);
        color: #fff;
    }

    .tenants-page .btn-reset {
        width: 42px;
        min-width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #E1E5ED;
        border-radius: 9px;
        background: #fff;
        color: #657086;
        font-size: 15px;
        text-decoration: none;
    }

    .tenants-page .btn-reset:hover {
        border-color: #E8A47E;
        color: var(--tnt-orange);
        background: #FFF8F3;
    }

    .tenants-page .status-pills {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-top: 14px;
    }

    .tenants-page .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border: 1px solid #E1E5ED;
        border-radius: 20px;
        background: #fff;
        color: #4B556B;
        font-size: 11px;
        font-weight: 700;
        text-decoration: none;
    }

    .tenants-page .status-pill:hover {
        background: #F5F7FB;
        color: var(--tnt-navy);
    }

    .tenants-page .status-pill.active {
        background: var(--tnt-navy);
        border-color: var(--tnt-navy);
        color: #fff;
    }

    .tenants-page .status-pill .pill-count {
        background: #EEF1F7;
        color: #5B6880;
        border-radius: 10px;
        padding: 0 7px;
        font-size: 11px;
        font-weight: 800;
    }

    .tenants-page .status-pill.active .pill-count {
        background: rgba(255, 255, 255, .2);
        color: #fff;
    }

    /* Directory */
    .tenants-page .tnt-directory {
        overflow: hidden;
        background: #fff;
        border: 1px solid var(--tnt-border);
        border-radius: 15px;
        box-shadow: 0 5px 24px rgba(25, 38, 93, .04);
    }

    .tenants-page .directory-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
        padding: 18px 22px;
        border-bottom: 1px solid var(--tnt-border);
    }

    .tenants-page .directory-title {
        display: flex;
        align-items: center;
        gap: 11px;
        margin: 0;
        color: var(--tnt-navy);
        font-size: 15px;
        font-weight: 800;
        letter-spacing: -.2px;
    }

    .tenants-page .directory-title-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 37px;
        height: 37px;
        border-radius: 11px;
        background: #EDF0FB;
        color: var(--tnt-navy);
        font-size: 17px;
    }

    .tenants-page .directory-subtitle {
        margin: 5px 0 0 48px;
        color: var(--tnt-muted);
        font-size: 11px;
    }

    .tenants-page .directory-total {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 11px;
        border: 1px solid #F1D7C8;
        border-radius: 8px;
        background: #FFF7F2;
        color: #A7460E;
        font-size: 11px;
        font-weight: 700;
    }

    .tenants-page .tnt-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .tenants-page .tnt-table {
        width: 100%;
        min-width: 900px;
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    .tenants-page .tnt-table thead th {
        padding: 13px 17px;
        border-bottom: 1px solid var(--tnt-border);
        background: #F8F9FC;
        color: #7B8498;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .55px;
        white-space: nowrap;
    }

    .tenants-page .tnt-table tbody td {
        padding: 14px 17px;
        border-bottom: 1px solid #EEF0F5;
        color: #4D586D;
        font-size: 11px;
        vertical-align: middle;
    }

    .tenants-page .tnt-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .tenants-page .tnt-table tbody tr {
        transition: background .15s ease;
    }

    .tenants-page .tnt-table tbody tr:hover {
        background: #FCFCFE;
    }

    .tenants-page .tnt-profile {
        display: flex;
        align-items: center;
        gap: 11px;
        min-width: 230px;
    }

    .tenants-page .tnt-avatar {
        width: 42px;
        height: 42px;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: #E8F7EF;
        color: #16845B;
        font-size: 12px;
        font-weight: 800;
        border: 1px solid #D0EFDD;
    }

    .tenants-page .tnt-name {
        color: var(--tnt-navy);
        font-size: 12px;
        font-weight: 800;
        line-height: 1.4;
        overflow-wrap: anywhere;
        text-decoration: none;
        display: inline-block;
    }

    .tenants-page a.tnt-name:hover {
        color: var(--tnt-orange);
        text-decoration: underline;
    }

    .tenants-page .tnt-meta {
        margin-top: 3px;
        color: var(--tnt-muted);
        font-size: 11px;
        line-height: 1.5;
    }

    .tenants-page .tnt-number {
        display: inline-block;
        margin-top: 5px;
        padding: 2px 7px;
        border: 1px solid #E8EBF3;
        border-radius: 5px;
        background: #F8F9FC;
        color: var(--tnt-navy);
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .tenants-page .contact-line {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin-bottom: 6px;
        max-width: 235px;
        overflow-wrap: anywhere;
        line-height: 1.5;
    }

    .tenants-page .contact-line:last-child {
        margin-bottom: 0;
    }

    .tenants-page .contact-line i {
        width: 13px;
        margin-top: 1px;
        color: #9099AB;
        flex-shrink: 0;
    }

    .tenants-page .contact-line a {
        color: inherit;
        text-decoration: none;
    }

    .tenants-page .contact-line a:hover {
        color: var(--tnt-orange);
    }

    .tenants-page .lease-count {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 9px;
        border-radius: 7px;
        background: #F3F5FA;
        color: var(--tnt-navy);
        font-size: 11.5px;
        font-weight: 800;
        white-space: nowrap;
    }

    .tenants-page .lease-count i {
        color: #8792A7;
        font-size: 12px;
    }

    .tenants-page .lease-count.has-lease {
        background: #E8F7EF;
        color: #16845B;
    }

    .tenants-page .lease-count.has-lease i {
        color: #16845B;
    }

    .tenants-page .tnt-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 6px 9px;
        border-radius: 7px;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .tenants-page .tnt-status::before {
        content: "";
        width: 6px;
        height: 6px;
        flex-shrink: 0;
        border-radius: 50%;
        background: currentColor;
    }

    .tenants-page .tnt-status.active,
    .tenants-page .tnt-status.linked {
        background: #E8F7EF;
        color: #16845B;
    }

    .tenants-page .tnt-status.inactive {
        background: #F0F1F5;
        color: #737C8D;
    }

    .tenants-page .tnt-status.no-account {
        background: #FFF4DF;
        color: #9A6810;
    }

    .tenants-page .tnt-status.unknown {
        background: #EEF1F6;
        color: #667085;
    }

    /* Actions dropdown (small font) */
    .tenants-page .tnt-action-btn {
        width: 33px;
        height: 33px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #E3E7EF;
        border-radius: 8px;
        background: #fff;
        color: var(--tnt-navy);
        font-size: 14px;
        transition: all .18s ease;
    }

    .tenants-page .tnt-action-btn:hover,
    .tenants-page .tnt-action-btn[aria-expanded="true"] {
        color: var(--tnt-orange);
        background: #FFF5EF;
        border-color: #F1C4AA;
    }

    .tenants-page .action-menu {
        min-width: 200px;
        padding: 6px;
        border: 1px solid var(--tnt-border);
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(25, 38, 93, .12);
        font-size: 11px;
    }

    .tenants-page .action-menu .dropdown-item {
        display: flex;
        align-items: center;
        gap: 9px;
        width: 100%;
        padding: 7px 10px;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: #35425B;
        font-size: 11px;
        font-weight: 600;
        text-align: left;
    }

    .tenants-page .action-menu .dropdown-item i {
        font-size: 13px;
        color: #7B879C;
    }

    .tenants-page .action-menu .dropdown-item:hover,
    .tenants-page .action-menu .dropdown-item:focus {
        background: #F3F5FA;
        color: var(--tnt-navy);
    }

    .tenants-page .action-menu .item-primary,
    .tenants-page .action-menu .item-primary i {
        color: var(--tnt-orange-dark);
    }

    .tenants-page .action-menu .item-primary:hover {
        background: var(--tnt-orange-soft);
        color: var(--tnt-orange-dark);
    }

    .tenants-page .action-menu .item-danger,
    .tenants-page .action-menu .item-danger i {
        color: #B42318;
    }

    .tenants-page .action-menu .item-danger:hover {
        background: #FFF0EF;
        color: #B42318;
    }

    .tenants-page .action-menu .dropdown-divider {
        margin: 5px 4px;
        border-color: #EDF0F5;
    }

    .tenants-page .action-menu form {
        margin: 0;
    }

    /* Empty + footer */
    .tenants-page .empty-state {
        padding: 55px 20px;
        text-align: center;
    }

    .tenants-page .empty-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 62px;
        height: 62px;
        margin: 0 auto 17px;
        border-radius: 18px;
        background: var(--tnt-orange-soft);
        color: var(--tnt-orange);
        font-size: 25px;
    }

    .tenants-page .empty-state h3 {
        color: var(--tnt-navy);
        font-size: 15px;
        font-weight: 800;
    }

    .tenants-page .empty-state p {
        max-width: 390px;
        margin: 8px auto 18px;
        color: var(--tnt-muted);
        font-size: 12px;
        line-height: 1.7;
    }

    .tenants-page .directory-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        padding: 15px 20px;
        border-top: 1px solid var(--tnt-border);
        background: #FCFCFE;
    }

    .tenants-page .pagination-summary {
        color: var(--tnt-muted);
        font-size: 11px;
    }

    .tenants-page .pagination {
        margin: 0;
        gap: 4px;
    }

    .tenants-page .pagination .page-link {
        min-width: 32px;
        min-height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #E3E7EF;
        border-radius: 7px !important;
        color: var(--tnt-navy);
        font-size: 11px;
        box-shadow: none;
    }

    .tenants-page .pagination .page-item.active .page-link {
        border-color: var(--tnt-orange);
        background: var(--tnt-orange);
        color: #fff;
    }

    .tenants-page .pagination .page-item.disabled .page-link {
        color: #B5BBC8;
        background: #F8F9FC;
    }

    @media (max-width: 767.98px) {
        .tenants-page .page-intro {
            align-items: stretch;
        }

        .tenants-page .page-intro>div:last-child,
        .tenants-page .page-intro .btn-tnt-primary {
            width: 100%;
        }

        .tenants-page .tnt-stat-card {
            min-height: 104px;
            padding: 15px;
        }

        .tenants-page .tnt-stat-number {
            font-size: 24px;
        }

        .tenants-page .tnt-filter-card {
            padding: 15px;
        }

        .tenants-page .directory-header {
            padding: 16px;
        }

        .tenants-page .directory-footer {
            padding: 14px;
        }

        .tenants-page .directory-footer .pagination {
            width: 100%;
            flex-wrap: wrap;
        }
    }
</style>
@endpush

@section('content')
<div class="tenants-page">

    @php
    /*
    * Platform-wide figures come straight from the database, so they stay
    * correct across pagination and filters.
    */
    $statusCounts = \Illuminate\Support\Facades\DB::table('tenants')
    ->whereNull('deleted_at')
    ->selectRaw('status, COUNT(*) as total')
    ->groupBy('status')
    ->pluck('total', 'status');

    $allTenantsCount = $statusCounts->sum();

    $linkedTenantsCount = \Illuminate\Support\Facades\DB::table('tenants')
    ->whereNull('deleted_at')
    ->whereNotNull('user_id')
    ->count();

    $noAccountCount = $allTenantsCount - $linkedTenantsCount;

    // Active leases for the tenants on this page (single query).
    $leaseCounts = \Illuminate\Support\Facades\DB::table('leases')
    ->whereIn('tenant_id', $tenants->getCollection()->pluck('id')->all())
    ->where('status', 'active')
    ->whereNull('deleted_at')
    ->selectRaw('tenant_id, COUNT(*) as total')
    ->groupBy('tenant_id')
    ->pluck('total', 'tenant_id');

    $activeStatus = request('status', '');
    $hasFilters = request()->filled('search') || request()->filled('status');
    @endphp

    {{-- Page heading --}}
    <div class="page-intro">
        <div>
            <div class="breadcrumb-line">
                <a href="{{ \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/') }}">Dashboard</a>
                <i class="bi bi-chevron-right"></i>
                <span>Administration</span>
                <i class="bi bi-chevron-right"></i>
                <span>Tenants</span>
            </div>

            <h1 class="page-title">Tenant Management</h1>
            <p class="page-description">
                Manage tenant profiles, login access, active leases and contact information.
            </p>
        </div>

        <div class="d-flex align-items-start">
            <a href="{{ route('admin.tenants.create') }}" class="btn-tnt-primary">
                <i class="bi bi-person-plus"></i> Add tenant
            </a>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <a href="{{ route('admin.tenants.index') }}"
                class="tnt-stat-card {{ !$hasFilters ? 'is-active' : '' }}">
                <div class="tnt-stat-top">
                    <span class="tnt-stat-label">Total tenants</span>
                    <span class="tnt-stat-icon stat-orange"><i class="bi bi-people"></i></span>
                </div>
                <div class="tnt-stat-number">{{ number_format($allTenantsCount) }}</div>
                <div class="tnt-stat-footnote">All registered tenants</div>
            </a>
        </div>

        <div class="col-6 col-xl-3">
            <a href="{{ route('admin.tenants.index', ['status' => 'active']) }}"
                class="tnt-stat-card {{ $activeStatus === 'active' ? 'is-active' : '' }}">
                <div class="tnt-stat-top">
                    <span class="tnt-stat-label">Active tenants</span>
                    <span class="tnt-stat-icon stat-green"><i class="bi bi-person-check"></i></span>
                </div>
                <div class="tnt-stat-number">{{ number_format($statusCounts['active'] ?? 0) }}</div>
                <div class="tnt-stat-footnote">Click to filter active tenants</div>
            </a>
        </div>

        <div class="col-6 col-xl-3">
            <div class="tnt-stat-card">
                <div class="tnt-stat-top">
                    <span class="tnt-stat-label">Account linked</span>
                    <span class="tnt-stat-icon stat-blue"><i class="bi bi-person-lock"></i></span>
                </div>
                <div class="tnt-stat-number">{{ number_format($linkedTenantsCount) }}</div>
                <div class="tnt-stat-footnote">Tenants who can log in</div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="tnt-stat-card">
                <div class="tnt-stat-top">
                    <span class="tnt-stat-label">No login account</span>
                    <span class="tnt-stat-icon stat-amber"><i class="bi bi-person-exclamation"></i></span>
                </div>
                <div class="tnt-stat-number">{{ number_format($noAccountCount) }}</div>
                <div class="tnt-stat-footnote">Profile exists without access</div>
            </div>
        </div>
    </div>

    {{-- Search and filters --}}
    <form method="GET" action="{{ route('admin.tenants.index') }}" class="tnt-filter-card mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-6">
                <label for="tenantSearch" class="form-label">Search tenants</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input id="tenantSearch" type="search" name="search" class="form-control"
                        value="{{ request('search') }}"
                        placeholder="Name, email, phone or tenant number">
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label for="tenantStatus" class="form-label">Tenant status</label>
                <select id="tenantStatus" name="status" class="form-select">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status')==='active' )>Active</option>
                    <option value="inactive" @selected(request('status')==='inactive' )>Inactive</option>
                </select>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn-filter flex-grow-1">
                        <i class="bi bi-funnel"></i> Apply filters
                    </button>
                    <a href="{{ route('admin.tenants.index') }}" class="btn-reset"
                        title="Reset filters" aria-label="Reset filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="status-pills">
            <a href="{{ route('admin.tenants.index', array_filter(['search' => request('search')])) }}"
                class="status-pill {{ $activeStatus === '' ? 'active' : '' }}">
                All <span class="pill-count">{{ number_format($allTenantsCount) }}</span>
            </a>
            <a href="{{ route('admin.tenants.index', array_filter(['search' => request('search'), 'status' => 'active'])) }}"
                class="status-pill {{ $activeStatus === 'active' ? 'active' : '' }}">
                Active <span class="pill-count">{{ number_format($statusCounts['active'] ?? 0) }}</span>
            </a>
            <a href="{{ route('admin.tenants.index', array_filter(['search' => request('search'), 'status' => 'inactive'])) }}"
                class="status-pill {{ $activeStatus === 'inactive' ? 'active' : '' }}">
                Inactive <span class="pill-count">{{ number_format($statusCounts['inactive'] ?? 0) }}</span>
            </a>

            @if($hasFilters)
            <span class="text-secondary ms-2" style="font-size:11px;">
                @if(request()->filled('search')) Search: <strong>{{ request('search') }}</strong> @endif
            </span>
            <a href="{{ route('admin.tenants.index') }}" class="text-decoration-none"
                style="font-size:11px;color:var(--tnt-orange);">Clear all</a>
            @endif
        </div>
    </form>

    {{-- Tenant directory --}}
    <section class="tnt-directory">

        <div class="directory-header">
            <div>
                <h2 class="directory-title">
                    <span class="directory-title-icon"><i class="bi bi-person-lines-fill"></i></span>
                    Tenant directory
                </h2>
                <p class="directory-subtitle">Tenant profiles, login access and rental activity</p>
            </div>

            <div class="directory-total">
                <i class="bi bi-people"></i>
                {{ number_format($tenants->total()) }}
                {{ \Illuminate\Support\Str::plural('tenant', $tenants->total()) }}
            </div>
        </div>

        <div class="tnt-table-wrap">
            <table class="tnt-table">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Contact information</th>
                        <th>Active leases</th>
                        <th>Account access</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($tenants as $tenant)
                    @php
                    $tenantName = trim((string) $tenant->full_name) ?: 'Unnamed tenant';

                    $initials = collect(preg_split('/\s+/', $tenantName))
                    ->filter()->take(2)
                    ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                    ->implode('');

                    $tenantStatus = strtolower($tenant->status ?? 'unknown');
                    $statusClass = in_array($tenantStatus, ['active', 'inactive'], true)
                    ? $tenantStatus : 'unknown';

                    $location = collect([$tenant->district, $tenant->province])->filter()->implode(', ');
                    $leasesTotal = $leaseCounts[$tenant->id] ?? 0;
                    @endphp

                    <tr>
                        {{-- Tenant --}}
                        <td>
                            <div class="tnt-profile">
                                <div class="tnt-avatar">{{ $initials ?: 'T' }}</div>

                                <div>
                                    <a href="{{ route('admin.tenants.show', $tenant) }}" class="tnt-name">
                                        {{ $tenantName }}
                                    </a>
                                    @if($location)
                                    <div class="tnt-meta"><i class="bi bi-geo-alt"></i> {{ $location }}</div>
                                    @endif
                                    <span class="tnt-number">{{ $tenant->tenant_number ?: 'Not assigned' }}</span>
                                </div>
                            </div>
                        </td>

                        {{-- Contact --}}
                        <td>
                            <div class="contact-line">
                                <i class="bi bi-envelope"></i>
                                <span>{{ $tenant->email ?: 'No email provided' }}</span>
                            </div>
                            <div class="contact-line">
                                <i class="bi bi-telephone"></i>
                                @if($tenant->phone)
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $tenant->phone) }}">{{ $tenant->phone }}</a>
                                @else
                                <span>No phone provided</span>
                                @endif
                            </div>
                        </td>

                        {{-- Leases --}}
                        <td>
                            <span class="lease-count {{ $leasesTotal > 0 ? 'has-lease' : '' }}">
                                <i class="bi bi-file-earmark-text"></i>
                                {{ $leasesTotal }} {{ $leasesTotal === 1 ? 'lease' : 'leases' }}
                            </span>
                        </td>

                        {{-- Account access --}}
                        <td>
                            @if($tenant->user_id)
                            <span class="tnt-status linked">Linked</span>
                            @else
                            <span class="tnt-status no-account">No account</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td>
                            <span class="tnt-status {{ $statusClass }}">{{ ucfirst($tenantStatus) }}</span>
                        </td>

                        {{-- Actions --}}
                        <td class="text-end">
                            <div class="dropdown">
                                <button type="button" class="tnt-action-btn"
                                    data-bs-toggle="dropdown"
                                    data-bs-popper-config='{"strategy":"fixed"}'
                                    aria-expanded="false"
                                    aria-label="Actions for {{ $tenantName }}">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end action-menu">
                                    <li>
                                        <a class="dropdown-item item-primary"
                                            href="{{ route('admin.tenants.show', $tenant) }}">
                                            <i class="bi bi-person-lines-fill"></i> View tenant profile
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item"
                                            href="{{ route('admin.tenants.edit', $tenant) }}">
                                            <i class="bi bi-pencil-square"></i> Edit tenant
                                        </a>
                                    </li>

                                    @if($tenant->user_id)
                                    <li>
                                        <form method="POST"
                                            action="{{ route('admin.tenants.resend-credentials', $tenant) }}"
                                            onsubmit="return confirm('Generate a new password and email it to this tenant?')">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="bi bi-envelope-arrow-up"></i> Resend credentials
                                            </button>
                                        </form>
                                    </li>
                                    @endif

                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <form method="POST"
                                            action="{{ route('admin.tenants.destroy', $tenant) }}"
                                            onsubmit="return confirm('Archive this tenant? Their rental history will be retained.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item item-danger">
                                                <i class="bi bi-archive"></i> Archive tenant
                                            </button>
                                        </form>
                                    </li>
                                    <li>
                                        <x-purge-button type="tenant" :id="$tenant->id" what="this tenant, their leases" class="dropdown-item item-danger" />
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-people"></i></div>
                                <h3>{{ $hasFilters ? 'No matching tenants' : 'No tenants found' }}</h3>
                                <p>
                                    {{ $hasFilters
                                            ? 'No tenant records match your search. Try different keywords or clear the filters.'
                                            : 'Your tenant directory is empty. Add a tenant to start managing profiles and account access.' }}
                                </p>

                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                    @if($hasFilters)
                                    <a href="{{ route('admin.tenants.index') }}"
                                        class="btn btn-outline-secondary btn-sm px-3 py-2">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Clear filters
                                    </a>
                                    @endif

                                    <a href="{{ route('admin.tenants.create') }}" class="btn-tnt-primary">
                                        <i class="bi bi-person-plus"></i>
                                        {{ $hasFilters ? 'Add tenant' : 'Add first tenant' }}
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($tenants->hasPages())
        <div class="directory-footer">
            <div class="pagination-summary">
                Showing
                <strong>{{ $tenants->firstItem() ?? 0 }}</strong> to
                <strong>{{ $tenants->lastItem() ?? 0 }}</strong> of
                <strong>{{ number_format($tenants->total()) }}</strong> tenants
            </div>

            <div>{{ $tenants->withQueryString()->links('pagination::bootstrap-5') }}</div>
        </div>
        @endif

    </section>
</div>
@endsection