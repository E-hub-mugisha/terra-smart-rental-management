@extends('layouts.app')

@section('title', 'Tenant Management')

@php
$pageItems = $tenants->getCollection();

$activeCount = $pageItems->where('status', 'active')->count();
$inactiveCount = $pageItems->where('status', '!=', 'active')->count();
$linkedCount = $pageItems->whereNotNull('user_id')->count();
$noAccount = $pageItems->count() - $linkedCount;

$hasFilters = request()->filled('search') || request()->filled('status');
@endphp

@push('styles')
<style>
    /* ============================================================
   TERRA SMART RENTAL MANAGEMENT — TENANT DIRECTORY
   ============================================================ */

    .terra-tenants-page {
        --tenant-orange: var(--terra-orange, #D05208);
        --tenant-navy: var(--terra-navy, #19265d);
        --tenant-muted: var(--terra-muted, #6b7280);
        --tenant-border: var(--terra-border, #e8eaf0);
        --tenant-shadow: 0 8px 26px rgba(25, 38, 93, .055);

        color: #29354a;
        padding-bottom: 30px;
        font-size: .9rem;
    }

    .terra-tenants-page *,
    .terra-tenants-page *::before,
    .terra-tenants-page *::after {
        box-sizing: border-box;
    }

    .terra-tenants-page .min-w-0 {
        min-width: 0;
    }

    /* Page header */

    .terra-tenants-page .tenant-page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 26px;
    }

    .terra-tenants-page .tenant-breadcrumb {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 9px;
        margin-bottom: 12px;
        color: var(--tenant-muted);
        font-size: .77rem;
    }

    .terra-tenants-page .tenant-breadcrumb a {
        color: var(--tenant-muted);
        text-decoration: none;
    }

    .terra-tenants-page .tenant-breadcrumb a:hover {
        color: var(--tenant-orange);
    }

    .terra-tenants-page .tenant-page-title {
        margin: 0 0 8px;
        color: var(--tenant-navy);
        font-size: clamp(1.55rem, 2.5vw, 2rem);
        font-weight: 850;
        letter-spacing: -.045em;
    }

    .terra-tenants-page .tenant-page-description {
        max-width: 650px;
        margin: 0;
        color: var(--tenant-muted);
        font-size: .88rem;
        line-height: 1.65;
    }

    .terra-tenants-page .tenant-primary-btn,
    .terra-tenants-page .tenant-outline-btn {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        min-height: 42px;
        padding: 10px 15px;
        border: 1px solid transparent;
        border-radius: 10px;
        font-size: .83rem;
        font-weight: 750;
        text-decoration: none;
        transition: all .18s ease;
    }

    .terra-tenants-page .tenant-primary-btn {
        color: #fff;
        background: var(--tenant-orange);
        border-color: var(--tenant-orange);
        box-shadow: 0 4px 10px rgba(208, 82, 8, .12);
    }

    .terra-tenants-page .tenant-primary-btn:hover {
        color: #fff;
        background: #b94706;
        border-color: #b94706;
        transform: translateY(-1px);
    }

    .terra-tenants-page .tenant-outline-btn {
        color: var(--tenant-navy);
        background: #fff;
        border-color: var(--tenant-border);
    }

    .terra-tenants-page .tenant-outline-btn:hover {
        color: var(--tenant-orange);
        background: #fff8f3;
        border-color: #efc4a8;
    }

    /* Summary cards */

    .terra-tenants-page .tenant-stat-card {
        position: relative;
        height: 100%;
        min-height: 148px;
        padding: 19px;
        overflow: hidden;
        background: #fff;
        border: 1px solid var(--tenant-border);
        border-radius: 16px;
        box-shadow: var(--tenant-shadow);
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .terra-tenants-page .tenant-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 30px rgba(25, 38, 93, .09);
    }

    .terra-tenants-page .tenant-stat-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 15px;
    }

    .terra-tenants-page .tenant-stat-label {
        color: var(--tenant-muted);
        font-size: .79rem;
        font-weight: 700;
    }

    .terra-tenants-page .tenant-stat-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 39px;
        width: 39px;
        height: 39px;
        border-radius: 12px;
        font-size: 1.05rem;
    }

    .terra-tenants-page .tenant-stat-icon.orange {
        color: var(--tenant-orange);
        background: #fff0e6;
    }

    .terra-tenants-page .tenant-stat-icon.green {
        color: #147a49;
        background: #e8f7ee;
    }

    .terra-tenants-page .tenant-stat-icon.blue {
        color: #1875a8;
        background: #e8f4fd;
    }

    .terra-tenants-page .tenant-stat-icon.navy {
        color: var(--tenant-navy);
        background: #edf0fb;
    }

    .terra-tenants-page .tenant-stat-value {
        color: var(--tenant-navy);
        font-size: clamp(1.65rem, 2.5vw, 2.05rem);
        font-weight: 850;
        letter-spacing: -.045em;
        line-height: 1.15;
    }

    .terra-tenants-page .tenant-stat-foot {
        margin-top: 8px;
        color: var(--tenant-muted);
        font-size: .75rem;
        line-height: 1.5;
    }

    /* Panels */

    .terra-tenants-page .tenant-panel {
        overflow: hidden;
        background: #fff;
        border: 1px solid var(--tenant-border);
        border-radius: 16px;
        box-shadow: var(--tenant-shadow);
    }

    .terra-tenants-page .tenant-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
        padding: 19px 22px;
        border-bottom: 1px solid var(--tenant-border);
    }

    .terra-tenants-page .tenant-panel-title {
        margin: 0;
        color: var(--tenant-navy);
        font-size: .98rem;
        font-weight: 800;
        letter-spacing: -.02em;
    }

    .terra-tenants-page .tenant-panel-subtitle {
        margin-top: 5px;
        color: var(--tenant-muted);
        font-size: .78rem;
        line-height: 1.5;
    }

    .terra-tenants-page .tenant-panel-body {
        padding: 21px;
    }

    /* Filters */

    .terra-tenants-page .tenant-filter-label {
        display: block;
        margin-bottom: 8px;
        color: #39455c;
        font-size: .79rem;
        font-weight: 750;
    }

    .terra-tenants-page .tenant-filter-search {
        display: flex;
        align-items: center;
        min-height: 45px;
        overflow: hidden;
        background: #fff;
        border: 1px solid #dfe4ed;
        border-radius: 10px;
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    .terra-tenants-page .tenant-filter-search:focus-within {
        border-color: var(--tenant-orange);
        box-shadow: 0 0 0 3px rgba(208, 82, 8, .09);
    }

    .terra-tenants-page .tenant-filter-search>i {
        padding-left: 14px;
        color: #8a94a7;
    }

    .terra-tenants-page .tenant-filter-search .form-control {
        min-width: 0;
        min-height: 43px;
        padding-left: 10px;
        border: 0;
        box-shadow: none;
    }

    .terra-tenants-page .form-control,
    .terra-tenants-page .form-select {
        min-height: 45px;
        border-color: #dfe4ed;
        border-radius: 10px;
        color: #29354a;
        font-size: .84rem;
        box-shadow: none;
    }

    .terra-tenants-page .form-control:focus,
    .terra-tenants-page .form-select:focus {
        border-color: var(--tenant-orange);
        box-shadow: 0 0 0 3px rgba(208, 82, 8, .09);
    }

    .terra-tenants-page .tenant-filter-actions {
        display: flex;
        gap: 8px;
    }

    .terra-tenants-page .tenant-filter-actions .tenant-primary-btn {
        flex: 1;
    }

    /* Directory header */

    .terra-tenants-page .tenant-total-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 11px;
        color: #a64108;
        background: #fff0e6;
        border: 1px solid #f6d8c5;
        border-radius: 9px;
        font-size: .76rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .terra-tenants-page .tenant-result-note {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 7px;
        padding: 12px 22px;
        color: var(--tenant-muted);
        background: #fafbfc;
        border-bottom: 1px solid var(--tenant-border);
        font-size: .77rem;
    }

    /* Table */

    .terra-tenants-page .tenant-table-wrap {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .terra-tenants-page .tenant-table {
        min-width: 820px;
        margin: 0;
        vertical-align: middle;
    }

    .terra-tenants-page .tenant-table thead th {
        padding: 13px 14px;
        color: #748096;
        background: #fafbfc;
        border-bottom: 1px solid var(--tenant-border);
        font-size: .69rem;
        font-weight: 800;
        letter-spacing: .045em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .terra-tenants-page .tenant-table tbody td {
        padding: 15px 14px;
        border-bottom: 1px solid #edf0f4;
        font-size: .81rem;
    }

    .terra-tenants-page .tenant-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .terra-tenants-page .tenant-table tbody tr:hover td {
        background: #fcfcfe;
    }

    .terra-tenants-page .tenant-table .tenant-identity-cell {
        min-width: 250px;
    }

    .terra-tenants-page .tenant-profile {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .terra-tenants-page .tenant-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 43px;
        width: 43px;
        height: 43px;
        color: var(--tenant-navy);
        background: linear-gradient(145deg, #f0f2fc, #e5e9fa);
        border: 1px solid #e0e5f6;
        border-radius: 13px;
        font-size: .82rem;
        font-weight: 850;
    }

    .terra-tenants-page .tenant-name {
        display: inline-block;
        max-width: 100%;
        color: var(--tenant-navy);
        font-size: .84rem;
        font-weight: 800;
        text-decoration: none;
        overflow-wrap: anywhere;
    }

    .terra-tenants-page .tenant-name:hover {
        color: var(--tenant-orange);
    }

    .terra-tenants-page .tenant-email {
        margin-top: 4px;
        color: var(--tenant-muted);
        font-size: .75rem;
        overflow-wrap: anywhere;
    }

    .terra-tenants-page .tenant-number {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 9px;
        color: var(--tenant-navy);
        background: #f2f4f9;
        border: 1px solid #e7eaf1;
        border-radius: 7px;
        font-size: .75rem;
        font-weight: 750;
        white-space: nowrap;
    }

    .terra-tenants-page .tenant-phone {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: #46536b;
        font-size: .8rem;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
    }

    .terra-tenants-page .tenant-phone i {
        color: var(--tenant-orange);
    }

    .terra-tenants-page .tenant-phone:hover {
        color: var(--tenant-orange);
    }

    .terra-tenants-page .tenant-muted {
        color: var(--tenant-muted);
        font-size: .78rem;
    }

    /* Status badges */

    .terra-tenants-page .tenant-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 6px 9px;
        border: 1px solid transparent;
        border-radius: 8px;
        font-size: .72rem;
        font-weight: 750;
        white-space: nowrap;
    }

    .terra-tenants-page .tenant-badge.is-success {
        color: #147847;
        background: #eaf8f0;
        border-color: #d0efdd;
    }

    .terra-tenants-page .tenant-badge.is-muted {
        color: #667085;
        background: #f0f2f6;
        border-color: #e3e7ee;
    }

    .terra-tenants-page .tenant-badge.is-warning {
        color: #98600b;
        background: #fff5df;
        border-color: #f6e5b8;
    }

    .terra-tenants-page .tenant-badge.is-orange {
        color: #a64108;
        background: #fff0e6;
        border-color: #f6d8c5;
    }

    /* Row actions */

    .terra-tenants-page .tenant-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 6px;
    }

    .terra-tenants-page .tenant-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-width: 35px;
        min-height: 35px;
        padding: 7px 9px;
        color: var(--tenant-navy);
        background: #fff;
        border: 1px solid var(--tenant-border);
        border-radius: 9px;
        font-size: .77rem;
        font-weight: 700;
        text-decoration: none;
        transition: all .16s ease;
    }

    .terra-tenants-page .tenant-action-btn:hover {
        color: var(--tenant-orange);
        background: #fff8f3;
        border-color: #efc4a8;
    }

    .terra-tenants-page .tenant-action-btn.is-danger {
        color: #a72c25;
    }

    .terra-tenants-page .tenant-action-btn.is-danger:hover {
        color: #a72c25;
        background: #fff0ef;
        border-color: #f2b8b4;
    }

    /* Empty state */

    .terra-tenants-page .tenant-empty {
        padding: 54px 20px;
        text-align: center;
    }

    .terra-tenants-page .tenant-empty-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 62px;
        height: 62px;
        margin: 0 auto 17px;
        color: var(--tenant-navy);
        background: #edf0fb;
        border-radius: 18px;
        font-size: 1.65rem;
    }

    .terra-tenants-page .tenant-empty h3 {
        margin: 0 0 8px;
        color: var(--tenant-navy);
        font-size: 1.02rem;
        font-weight: 800;
    }

    .terra-tenants-page .tenant-empty p {
        max-width: 420px;
        margin: 0 auto 20px;
        color: var(--tenant-muted);
        font-size: .84rem;
        line-height: 1.65;
    }

    /* Pagination */

    .terra-tenants-page .tenant-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
        padding: 16px 22px;
        border-top: 1px solid var(--tenant-border);
    }

    .terra-tenants-page .tenant-pagination-note {
        color: var(--tenant-muted);
        font-size: .78rem;
    }

    .terra-tenants-page .pagination {
        margin: 0;
        gap: 4px;
    }

    .terra-tenants-page .page-link {
        color: var(--tenant-navy);
        border-color: var(--tenant-border);
        border-radius: 7px;
        font-size: .8rem;
    }

    .terra-tenants-page .page-item.active .page-link {
        color: #fff;
        background: var(--tenant-navy);
        border-color: var(--tenant-navy);
    }

    .terra-tenants-page .page-link:focus {
        box-shadow: 0 0 0 3px rgba(25, 38, 93, .1);
    }

    /* Responsive */

    @media (max-width: 767.98px) {
        .terra-tenants-page .tenant-page-header {
            align-items: stretch;
            margin-bottom: 20px;
        }

        .terra-tenants-page .tenant-page-header>div:first-child {
            width: 100%;
        }

        .terra-tenants-page .tenant-page-header>.tenant-primary-btn {
            width: 100%;
        }

        .terra-tenants-page .tenant-stat-card {
            min-height: 135px;
            padding: 15px;
            border-radius: 13px;
        }

        .terra-tenants-page .tenant-stat-icon {
            flex-basis: 34px;
            width: 34px;
            height: 34px;
        }

        .terra-tenants-page .tenant-panel-header,
        .terra-tenants-page .tenant-panel-body {
            padding: 16px;
        }

        .terra-tenants-page .tenant-result-note {
            padding: 11px 16px;
        }

        .terra-tenants-page .tenant-pagination {
            padding: 15px 16px;
        }
    }

    @media (max-width: 400px) {
        .terra-tenants-page .tenant-stat-card {
            padding: 12px;
        }

        .terra-tenants-page .tenant-stat-label {
            font-size: .72rem;
        }

        .terra-tenants-page .tenant-stat-value {
            font-size: 1.55rem;
        }

        .terra-tenants-page .tenant-filter-actions {
            flex-wrap: wrap;
        }
    }

    @media (prefers-reduced-motion: reduce) {

        .terra-tenants-page *,
        .terra-tenants-page *::before,
        .terra-tenants-page *::after {
            transition-duration: .01ms !important;
        }
    }
</style>
@endpush

@section('content')
<div class="terra-tenants-page">

    {{-- Page heading --}}
    <div class="tenant-page-header">
        <div>
            <div class="tenant-breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <i class="bi bi-chevron-right"></i>
                <span>Tenant management</span>
            </div>

            <h1 class="tenant-page-title">Tenant management</h1>
            <p class="tenant-page-description">
                Manage tenant profiles, account access and contact information
                from one central directory.
            </p>
        </div>

        <a href="{{ route('admin.tenants.create') }}" class="tenant-primary-btn">
            <i class="bi bi-plus-lg"></i>
            Add tenant
        </a>
    </div>

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="tenant-stat-card">
                <div class="tenant-stat-top">
                    <span class="tenant-stat-label">Total tenants</span>
                    <span class="tenant-stat-icon orange">
                        <i class="bi bi-people"></i>
                    </span>
                </div>
                <div class="tenant-stat-value">
                    {{ number_format($tenants->total()) }}
                </div>
                <div class="tenant-stat-foot">
                    {{ $hasFilters ? 'Matching your current filters' : 'Registered tenant profiles' }}
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="tenant-stat-card">
                <div class="tenant-stat-top">
                    <span class="tenant-stat-label">Active tenants</span>
                    <span class="tenant-stat-icon green">
                        <i class="bi bi-person-check"></i>
                    </span>
                </div>
                <div class="tenant-stat-value">{{ number_format($activeCount) }}</div>
                <div class="tenant-stat-foot">On the current page</div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="tenant-stat-card">
                <div class="tenant-stat-top">
                    <span class="tenant-stat-label">Account linked</span>
                    <span class="tenant-stat-icon blue">
                        <i class="bi bi-person-lock"></i>
                    </span>
                </div>
                <div class="tenant-stat-value">{{ number_format($linkedCount) }}</div>
                <div class="tenant-stat-foot">On the current page</div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="tenant-stat-card">
                <div class="tenant-stat-top">
                    <span class="tenant-stat-label">No login account</span>
                    <span class="tenant-stat-icon navy">
                        <i class="bi bi-person-exclamation"></i>
                    </span>
                </div>
                <div class="tenant-stat-value">{{ number_format($noAccount) }}</div>
                <div class="tenant-stat-foot">On the current page</div>
            </div>
        </div>
    </div>

    {{-- Search and filters --}}
    <section class="tenant-panel mb-4">
        <div class="tenant-panel-header">
            <div>
                <h2 class="tenant-panel-title">Find tenants</h2>
                <div class="tenant-panel-subtitle">
                    Search by name, contact details or tenant number.
                </div>
            </div>

            <i class="bi bi-funnel fs-5" style="color:var(--tenant-orange)"></i>
        </div>

        <div class="tenant-panel-body">
            <form method="GET" action="{{ route('admin.tenants.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-6">
                        <label for="tenant-search" class="tenant-filter-label">
                            Search directory
                        </label>

                        <div class="tenant-filter-search">
                            <i class="bi bi-search"></i>
                            <input
                                type="search"
                                name="search"
                                id="tenant-search"
                                class="form-control"
                                value="{{ request('search') }}"
                                placeholder="Name, email, phone or tenant number">
                        </div>
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <label for="tenant-status" class="tenant-filter-label">
                            Tenant status
                        </label>

                        <select name="status" id="tenant-status" class="form-select">
                            <option value="">All statuses</option>
                            <option value="active" @selected(request('status')==='active' )>
                                Active
                            </option>
                            <option value="inactive" @selected(request('status')==='inactive' )>
                                Inactive
                            </option>
                        </select>
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <div class="tenant-filter-actions">
                            <button type="submit" class="tenant-primary-btn">
                                <i class="bi bi-search"></i>
                                Search
                            </button>

                            @if($hasFilters)
                            <a
                                href="{{ route('admin.tenants.index') }}"
                                class="tenant-outline-btn"
                                title="Reset filters"
                                aria-label="Reset filters">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    {{-- Tenant directory --}}
    <section class="tenant-panel">
        <div class="tenant-panel-header">
            <div>
                <h2 class="tenant-panel-title">Tenant directory</h2>
                <div class="tenant-panel-subtitle">
                    View profiles, check login access and manage tenant records.
                </div>
            </div>

            <span class="tenant-total-badge">
                <i class="bi bi-people"></i>
                {{ number_format($tenants->total()) }}
                {{ \Illuminate\Support\Str::plural('tenant', $tenants->total()) }}
            </span>
        </div>

        @if($hasFilters)
        <div class="tenant-result-note">
            <i class="bi bi-funnel"></i>
            Showing results for your selected filters.
            <a href="{{ route('admin.tenants.index') }}"
                class="fw-bold text-decoration-none"
                style="color:var(--tenant-orange)">
                Clear filters
            </a>
        </div>
        @endif

        <div class="tenant-table-wrap">
            <table class="table table-hover tenant-table">
                <thead>
                    <tr>
                        <th class="ps-4">Tenant profile</th>
                        <th>Tenant number</th>
                        <th>Contact number</th>
                        <th>Account access</th>
                        <th>Tenant status</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($tenants as $tenant)
                    @php
                    $tenantName = trim((string) $tenant->full_name);

                    $initials = collect(explode(' ', $tenantName))
                    ->filter()
                    ->take(2)
                    ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                    ->implode('');

                    $isActive = $tenant->status === 'active';
                    @endphp

                    <tr>
                        {{-- Tenant profile --}}
                        <td class="ps-4 tenant-identity-cell">
                            <div class="tenant-profile">
                                <span class="tenant-avatar">
                                    {{ $initials ?: 'T' }}
                                </span>

                                <div class="min-w-0">
                                    <a
                                        href="{{ route('admin.tenants.show', $tenant) }}"
                                        class="tenant-name">
                                        {{ $tenant->full_name ?: 'Unnamed tenant' }}
                                    </a>

                                    <div class="tenant-email">
                                        {{ $tenant->email ?: 'No email address provided' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Tenant number --}}
                        <td>
                            <span class="tenant-number">
                                <i class="bi bi-hash"></i>
                                {{ $tenant->tenant_number ?: 'Not assigned' }}
                            </span>
                        </td>

                        {{-- Phone --}}
                        <td>
                            @if($tenant->phone)
                            <a
                                href="tel:{{ preg_replace('/[^\d+]/', '', $tenant->phone) }}"
                                class="tenant-phone">
                                <i class="bi bi-telephone"></i>
                                {{ $tenant->phone }}
                            </a>
                            @else
                            <span class="tenant-muted">Not provided</span>
                            @endif
                        </td>

                        {{-- Account status --}}
                        <td>
                            @if($tenant->user_id)
                            <span class="tenant-badge is-success">
                                <i class="bi bi-check-circle-fill"></i>
                                Linked
                            </span>
                            @else
                            <span class="tenant-badge is-warning">
                                <i class="bi bi-person-dash"></i>
                                No account
                            </span>
                            @endif
                        </td>

                        {{-- Tenant status --}}
                        <td>
                            <span class="tenant-badge is-{{ $isActive ? 'success' : 'muted' }}">
                                <i class="bi {{ $isActive ? 'bi-check-circle-fill' : 'bi-pause-circle-fill' }}"></i>
                                {{ ucfirst($tenant->status ?? 'unknown') }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td class="pe-4">
                            <div class="tenant-actions">
                                <a
                                    href="{{ route('admin.tenants.show', $tenant) }}"
                                    class="tenant-action-btn"
                                    title="View tenant"
                                    aria-label="View tenant">
                                    <i class="bi bi-eye"></i>
                                    <span>View</span>
                                </a>

                                <a
                                    href="{{ route('admin.tenants.edit', $tenant) }}"
                                    class="tenant-action-btn"
                                    title="Edit tenant"
                                    aria-label="Edit tenant">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                @if ($tenant->user_id)
                                <form method="POST" action="{{ route('admin.tenants.resend-credentials', $tenant) }}"
                                    onsubmit="return confirm('Generate a new password and email it to this tenant?')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-secondary">Resend credentials</button>
                                </form>
                                @endif

                                <form
                                    method="POST"
                                    action="{{ route('admin.tenants.destroy', $tenant) }}"
                                    onsubmit="return confirm('Archive this tenant? Their rental history will be retained.');">
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="tenant-action-btn is-danger"
                                        title="Archive tenant"
                                        aria-label="Archive tenant">
                                        <i class="bi bi-archive"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-0">
                            <div class="tenant-empty">
                                <div class="tenant-empty-icon">
                                    <i class="bi bi-people"></i>
                                </div>

                                <h3>No tenants found</h3>

                                <p>
                                    {{ $hasFilters
                                            ? 'No tenant records match your search. Try different keywords or clear the filters.'
                                            : 'Your tenant directory is empty. Add a tenant to start managing profiles and account access.' }}
                                </p>

                                @if($hasFilters)
                                <a
                                    href="{{ route('admin.tenants.index') }}"
                                    class="tenant-outline-btn">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                    Clear filters
                                </a>
                                @else
                                <a
                                    href="{{ route('admin.tenants.create') }}"
                                    class="tenant-primary-btn">
                                    <i class="bi bi-plus-lg"></i>
                                    Add first tenant
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($tenants->hasPages())
        <div class="tenant-pagination">
            <div class="tenant-pagination-note">
                Showing
                <strong>{{ $tenants->firstItem() }}</strong>–<strong>{{ $tenants->lastItem() }}</strong>
                of <strong>{{ number_format($tenants->total()) }}</strong> tenants
            </div>

            <div>
                {{ $tenants->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </section>
</div>
@endsection