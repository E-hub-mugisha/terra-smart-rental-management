@extends('layouts.app')

@section('title', 'Property Owners')

@section('page_heading', 'Property Owners')

@section('page_subtitle', 'Manage owner accounts, ownership records and verification.')

@push('styles')
<style>
    .owners-page {
        --owners-orange: var(--terra-orange, #D05208);
        --owners-navy: var(--terra-navy, #19265D);
        --owners-border: var(--terra-border, #E7EAF1);
        --owners-muted: var(--terra-muted, #778197);
        color: #263047;
    }

    .owners-page .page-intro {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
        margin-bottom: 25px;
    }

    .owners-page .breadcrumb-line {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 11px;
        color: var(--owners-muted);
        font-size: 11px;
    }

    .owners-page .breadcrumb-line a {
        color: var(--owners-muted);
        text-decoration: none;
    }

    .owners-page .breadcrumb-line a:hover {
        color: var(--owners-orange);
    }

    .owners-page .breadcrumb-line i {
        font-size: 9px;
        color: #AAB1BF;
    }

    .owners-page .page-title {
        margin: 0;
        color: var(--owners-navy);
        font-size: clamp(23px, 2vw, 29px);
        line-height: 1.25;
        letter-spacing: -.8px;
        font-weight: 800;
    }

    .owners-page .page-description {
        margin: 8px 0 0;
        color: var(--owners-muted);
        font-size: 12px;
        line-height: 1.7;
    }

    .owners-page .btn-owner-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 42px;
        padding: 11px 16px;
        border: 1px solid var(--owners-orange);
        border-radius: 10px;
        background: var(--owners-orange);
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: .2s ease;
        white-space: nowrap;
    }

    .owners-page .btn-owner-primary:hover {
        background: #AD4307;
        border-color: #AD4307;
        color: #fff;
        transform: translateY(-1px);
    }

    /* Summary cards */

    .owners-page .owner-stat-card {
        position: relative;
        height: 100%;
        min-height: 118px;
        padding: 18px;
        overflow: hidden;
        background: #fff;
        border: 1px solid var(--owners-border);
        border-radius: 14px;
        box-shadow: 0 5px 20px rgba(25, 38, 93, .035);
    }

    .owners-page .owner-stat-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
    }

    .owners-page .owner-stat-label {
        color: var(--owners-muted);
        font-size: 11px;
        font-weight: 600;
    }

    .owners-page .owner-stat-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 37px;
        height: 37px;
        flex-shrink: 0;
        border-radius: 11px;
        font-size: 17px;
    }

    .owners-page .stat-orange {
        background: #FFF0E7;
        color: #D05208;
    }

    .owners-page .stat-green {
        background: #E8F7EF;
        color: #16845B;
    }

    .owners-page .stat-amber {
        background: #FFF4DF;
        color: #A66A09;
    }

    .owners-page .stat-navy {
        background: #EDF0FB;
        color: #19265D;
    }

    .owners-page .owner-stat-number {
        margin-top: 10px;
        color: var(--owners-navy);
        font-size: 27px;
        line-height: 1.1;
        font-weight: 800;
        letter-spacing: -.8px;
    }

    .owners-page .owner-stat-footnote {
        margin-top: 6px;
        color: var(--owners-muted);
        font-size: 10px;
    }

    /* Search panel */

    .owners-page .owner-filter-card {
        padding: 19px;
        background: #fff;
        border: 1px solid var(--owners-border);
        border-radius: 14px;
        box-shadow: 0 5px 20px rgba(25, 38, 93, .035);
    }

    .owners-page .filter-heading {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
    }

    .owners-page .filter-heading-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 35px;
        height: 35px;
        border-radius: 10px;
        background: #FFF0E7;
        color: var(--owners-orange);
        font-size: 16px;
    }

    .owners-page .filter-heading-title {
        margin: 0;
        color: var(--owners-navy);
        font-size: 13px;
        font-weight: 800;
    }

    .owners-page .filter-heading-subtitle {
        margin-top: 3px;
        color: var(--owners-muted);
        font-size: 10px;
    }

    .owners-page .form-label {
        display: block;
        margin-bottom: 7px;
        color: #4B556B;
        font-size: 11px;
        font-weight: 700;
    }

    .owners-page .form-control,
    .owners-page .form-select,
    .owners-page .input-group-text {
        min-height: 42px;
        border-color: #E1E5ED;
        font-size: 12px;
        box-shadow: none;
    }

    .owners-page .form-control,
    .owners-page .form-select {
        color: #30394D;
        border-radius: 9px;
    }

    .owners-page .input-group .input-group-text {
        border-radius: 9px 0 0 9px;
        background: #FAFBFD;
        color: #7D8799;
        padding: 0 13px;
    }

    .owners-page .input-group .form-control {
        border-radius: 0 9px 9px 0;
    }

    .owners-page .form-control:focus,
    .owners-page .form-select:focus {
        border-color: #E8A47E;
        box-shadow: 0 0 0 3px rgba(208, 82, 8, .08);
    }

    .owners-page .btn-filter {
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        border: 1px solid var(--owners-orange);
        border-radius: 9px;
        padding: 10px 14px;
        background: var(--owners-orange);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    .owners-page .btn-filter:hover {
        background: #AD4307;
        color: #fff;
    }

    .owners-page .btn-reset {
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

    .owners-page .btn-reset:hover {
        border-color: #E8A47E;
        color: var(--owners-orange);
        background: #FFF8F3;
    }

    /* Directory */

    .owners-page .owner-directory {
        overflow: hidden;
        background: #fff;
        border: 1px solid var(--owners-border);
        border-radius: 15px;
        box-shadow: 0 5px 24px rgba(25, 38, 93, .04);
    }

    .owners-page .directory-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
        padding: 20px 22px;
        border-bottom: 1px solid var(--owners-border);
    }

    .owners-page .directory-title {
        display: flex;
        align-items: center;
        gap: 11px;
        margin: 0;
        color: var(--owners-navy);
        font-size: 15px;
        font-weight: 800;
        letter-spacing: -.2px;
    }

    .owners-page .directory-title-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 37px;
        height: 37px;
        border-radius: 11px;
        background: #EDF0FB;
        color: var(--owners-navy);
        font-size: 17px;
    }

    .owners-page .directory-subtitle {
        margin: 5px 0 0 48px;
        color: var(--owners-muted);
        font-size: 11px;
    }

    .owners-page .directory-total {
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

    .owners-page .directory-total i {
        font-size: 13px;
    }

    .owners-page .owner-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .owners-page .owner-table {
        width: 100%;
        min-width: 940px;
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    .owners-page .owner-table thead th {
        padding: 13px 17px;
        border-bottom: 1px solid var(--owners-border);
        background: #F8F9FC;
        color: #7B8498;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .55px;
        white-space: nowrap;
    }

    .owners-page .owner-table tbody td {
        padding: 15px 17px;
        border-bottom: 1px solid #EEF0F5;
        color: #4D586D;
        font-size: 11px;
        vertical-align: middle;
    }

    .owners-page .owner-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .owners-page .owner-table tbody tr {
        transition: background .15s ease;
    }

    .owners-page .owner-table tbody tr:hover {
        background: #FCFCFE;
    }

    .owners-page .owner-profile {
        display: flex;
        align-items: center;
        gap: 11px;
        min-width: 190px;
    }

    .owners-page .owner-avatar {
        width: 42px;
        height: 42px;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: #FFF0E7;
        color: var(--owners-orange);
        font-size: 12px;
        font-weight: 800;
        border: 1px solid #F9E1D2;
    }

    .owners-page .owner-name {
        color: var(--owners-navy);
        font-size: 12px;
        font-weight: 800;
        line-height: 1.4;
        overflow-wrap: anywhere;
    }

    .owners-page .owner-meta {
        margin-top: 4px;
        color: var(--owners-muted);
        font-size: 10px;
        line-height: 1.5;
    }

    .owners-page .owner-number {
        display: inline-block;
        padding: 6px 8px;
        border: 1px solid #E8EBF3;
        border-radius: 6px;
        background: #F8F9FC;
        color: var(--owners-navy);
        font-size: 10px;
        font-weight: 800;
        white-space: nowrap;
    }

    .owners-page .contact-line {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin-bottom: 7px;
        max-width: 235px;
        overflow-wrap: anywhere;
        line-height: 1.5;
    }

    .owners-page .contact-line:last-child {
        margin-bottom: 0;
    }

    .owners-page .contact-line i {
        width: 13px;
        margin-top: 1px;
        color: #9099AB;
        flex-shrink: 0;
    }

    .owners-page .owner-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 6px 9px;
        border-radius: 7px;
        font-size: 10px;
        font-weight: 800;
        white-space: nowrap;
    }

    .owners-page .owner-status::before {
        content: "";
        width: 6px;
        height: 6px;
        flex-shrink: 0;
        border-radius: 50%;
        background: currentColor;
    }

    .owners-page .owner-status.active,
    .owners-page .owner-status.verified {
        background: #E8F7EF;
        color: #16845B;
    }

    .owners-page .owner-status.inactive,
    .owners-page .owner-status.rejected {
        background: #F0F1F5;
        color: #737C8D;
    }

    .owners-page .owner-status.pending {
        background: #FFF4DF;
        color: #9A6810;
    }

    .owners-page .owner-status.unknown {
        background: #EEF1F6;
        color: #667085;
    }

    .owners-page .owner-actions {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6px;
    }

    .owners-page .owner-action-btn {
        width: 33px;
        height: 33px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #E3E7EF;
        border-radius: 8px;
        background: #fff;
        color: var(--owners-navy);
        font-size: 13px;
        text-decoration: none;
        transition: all .18s ease;
    }

    .owners-page .owner-action-btn:hover {
        color: var(--owners-orange);
        background: #FFF5EF;
        border-color: #F1C4AA;
    }

    .owners-page .owner-action-btn.danger {
        color: #B42318;
    }

    .owners-page .owner-action-btn.danger:hover {
        color: #B42318;
        background: #FFF0EF;
        border-color: #F1C6C2;
    }

    .owners-page .empty-state {
        padding: 55px 20px;
        text-align: center;
    }

    .owners-page .empty-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 62px;
        height: 62px;
        margin: 0 auto 17px;
        border-radius: 18px;
        background: #FFF0E7;
        color: var(--owners-orange);
        font-size: 25px;
    }

    .owners-page .empty-state h3 {
        color: var(--owners-navy);
        font-size: 15px;
        font-weight: 800;
    }

    .owners-page .empty-state p {
        max-width: 370px;
        margin: 8px auto 18px;
        color: var(--owners-muted);
        font-size: 12px;
        line-height: 1.7;
    }

    .owners-page .directory-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        padding: 15px 20px;
        border-top: 1px solid var(--owners-border);
        background: #FCFCFE;
    }

    .owners-page .pagination-summary {
        color: var(--owners-muted);
        font-size: 11px;
    }

    .owners-page .pagination {
        margin: 0;
        gap: 4px;
    }

    .owners-page .pagination .page-link {
        min-width: 32px;
        min-height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #E3E7EF;
        border-radius: 7px !important;
        color: var(--owners-navy);
        font-size: 11px;
        box-shadow: none;
    }

    .owners-page .pagination .page-item.active .page-link {
        border-color: var(--owners-orange);
        background: var(--owners-orange);
        color: #fff;
    }

    .owners-page .pagination .page-item.disabled .page-link {
        color: #B5BBC8;
        background: #F8F9FC;
    }

    @media (max-width: 767.98px) {
        .owners-page .page-intro {
            align-items: stretch;
        }

        .owners-page .page-intro>a {
            width: 100%;
        }

        .owners-page .owner-stat-card {
            min-height: 110px;
            padding: 15px;
        }

        .owners-page .owner-stat-number {
            font-size: 24px;
        }

        .owners-page .owner-filter-card {
            padding: 15px;
        }

        .owners-page .directory-header {
            padding: 16px;
        }

        .owners-page .directory-footer {
            padding: 14px;
        }

        .owners-page .directory-footer .pagination {
            width: 100%;
            flex-wrap: wrap;
        }
    }
</style>
@endpush

@section('content')
<div class="owners-page">

    @php
    /*
    * The counters use the current filtered result set when available.
    * For accurate all-record statistics across pagination, pass these
    * totals from the controller.
    */
    $ownerPageCollection = $owners->getCollection();

    $activeOwnersOnPage = $ownerPageCollection
    ->filter(fn ($owner) => strtolower($owner->status ?? '') === 'active')
    ->count();

    $pendingOwnersOnPage = $ownerPageCollection
    ->filter(fn ($owner) => strtolower($owner->verification_status ?? 'pending') === 'pending')
    ->count();

    $verifiedOwnersOnPage = $ownerPageCollection
    ->filter(fn ($owner) => strtolower($owner->verification_status ?? '') === 'verified')
    ->count();
    @endphp

    {{-- Page heading --}}
    <div class="page-intro">
        <div>
            <div class="breadcrumb-line">
                <a href="{{ \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/') }}">
                    Dashboard
                </a>
                <i class="bi bi-chevron-right"></i>
                <span>Administration</span>
                <i class="bi bi-chevron-right"></i>
                <span>Property Owners</span>
            </div>

            <h1 class="page-title">Property Owners</h1>

            <p class="page-description">
                Manage owner profiles, account access, contact details and verification records.
            </p>
        </div>

        <div class="d-flex align-items-start">
            <a href="{{ route('admin.owners.create') }}" class="btn-owner-primary">
                <i class="bi bi-plus-lg"></i>
                Register property owner
            </a>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">

        <div class="col-6 col-xl-3">
            <div class="owner-stat-card">
                <div class="owner-stat-top">
                    <span class="owner-stat-label">Total owners</span>
                    <span class="owner-stat-icon stat-orange">
                        <i class="bi bi-people"></i>
                    </span>
                </div>

                <div class="owner-stat-number">
                    {{ number_format($owners->total()) }}
                </div>

                <div class="owner-stat-footnote">
                    Owners matching the current filters
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="owner-stat-card">
                <div class="owner-stat-top">
                    <span class="owner-stat-label">Active accounts</span>
                    <span class="owner-stat-icon stat-green">
                        <i class="bi bi-person-check"></i>
                    </span>
                </div>

                <div class="owner-stat-number">
                    {{ number_format($activeOwnersOnPage) }}
                </div>

                <div class="owner-stat-footnote">
                    Active owners on this page
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="owner-stat-card">
                <div class="owner-stat-top">
                    <span class="owner-stat-label">Pending verification</span>
                    <span class="owner-stat-icon stat-amber">
                        <i class="bi bi-shield-exclamation"></i>
                    </span>
                </div>

                <div class="owner-stat-number">
                    {{ number_format($pendingOwnersOnPage) }}
                </div>

                <div class="owner-stat-footnote">
                    Awaiting verification on this page
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="owner-stat-card">
                <div class="owner-stat-top">
                    <span class="owner-stat-label">Verified owners</span>
                    <span class="owner-stat-icon stat-navy">
                        <i class="bi bi-patch-check"></i>
                    </span>
                </div>

                <div class="owner-stat-number">
                    {{ number_format($verifiedOwnersOnPage) }}
                </div>

                <div class="owner-stat-footnote">
                    Verified owners on this page
                </div>
            </div>
        </div>

    </div>

    {{-- Search and filters --}}
    <form method="GET"
        action="{{ route('admin.owners.index') }}"
        class="owner-filter-card mb-4">

        <div class="filter-heading">
            <span class="filter-heading-icon">
                <i class="bi bi-sliders"></i>
            </span>

            <div>
                <h2 class="filter-heading-title">Search and filter</h2>
                <div class="filter-heading-subtitle">
                    Find owner records by name, reference number, contact or account status.
                </div>
            </div>
        </div>

        <div class="row g-3 align-items-end">

            <div class="col-12 col-lg-6">
                <label for="ownerSearch" class="form-label">Search owners</label>

                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-search"></i>
                    </span>

                    <input
                        id="ownerSearch"
                        type="search"
                        name="search"
                        class="form-control"
                        value="{{ request('search') }}"
                        placeholder="Name, owner number, email or phone">
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label for="ownerStatus" class="form-label">Account status</label>

                <select id="ownerStatus" name="status" class="form-select">
                    <option value="">All account statuses</option>
                    <option value="active" @selected(request('status')==='active' )>
                        Active
                    </option>
                    <option value="inactive" @selected(request('status')==='inactive' )>
                        Inactive
                    </option>
                </select>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn-filter flex-grow-1">
                        <i class="bi bi-funnel"></i>
                        Apply filters
                    </button>

                    <a href="{{ route('admin.owners.index') }}"
                        class="btn-reset"
                        title="Reset filters"
                        aria-label="Reset filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </div>

        </div>

        @if(request()->filled('search') || request()->filled('status'))
        <div class="d-flex align-items-center flex-wrap gap-2 mt-3">
            <span class="text-secondary" style="font-size:11px;">
                Active filters:
            </span>

            @if(request()->filled('search'))
            <span class="badge rounded-pill text-bg-light border px-3 py-2">
                Search: {{ request('search') }}
            </span>
            @endif

            @if(request()->filled('status'))
            <span class="badge rounded-pill text-bg-light border px-3 py-2">
                Status: {{ ucfirst(request('status')) }}
            </span>
            @endif

            <a href="{{ route('admin.owners.index') }}"
                class="text-decoration-none ms-1"
                style="font-size:11px;color:var(--owners-orange);">
                Clear all
            </a>
        </div>
        @endif
    </form>

    {{-- Owner directory --}}
    <section class="owner-directory">

        <div class="directory-header">
            <div>
                <h2 class="directory-title">
                    <span class="directory-title-icon">
                        <i class="bi bi-person-vcard"></i>
                    </span>
                    Owner directory
                </h2>

                <p class="directory-subtitle">
                    Ownership records and account contact information
                </p>
            </div>

            <div class="directory-total">
                <i class="bi bi-people"></i>
                {{ number_format($owners->total()) }}
                {{ $owners->total() === 1 ? 'owner' : 'owners' }}
            </div>
        </div>

        <div class="owner-table-wrap">
            <table class="owner-table">
                <thead>
                    <tr>
                        <th>Owner profile</th>
                        <th>Reference</th>
                        <th>Contact information</th>
                        <th>Verification</th>
                        <th>Account</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($owners as $owner)
                    @php
                    $ownerName = trim($owner->name ?? 'Property Owner');

                    $ownerInitials = collect(
                    preg_split('/\s+/', $ownerName)
                    )
                    ->filter()
                    ->take(2)
                    ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                    ->implode('');

                    $verification = strtolower(
                    $owner->verification_status ?? 'pending'
                    );

                    $accountStatus = strtolower(
                    $owner->status ?? 'inactive'
                    );

                    $verificationClass = in_array(
                    $verification,
                    ['pending', 'verified', 'rejected'],
                    true
                    ) ? $verification : 'unknown';

                    $accountClass = in_array(
                    $accountStatus,
                    ['active', 'inactive'],
                    true
                    ) ? $accountStatus : 'unknown';

                    $ownerType = ucfirst(
                    str_replace('_', ' ', $owner->owner_type ?? 'individual')
                    );
                    @endphp

                    <tr>
                        {{-- Owner profile --}}
                        <td>
                            <div class="owner-profile">
                                <div class="owner-avatar">
                                    {{ $ownerInitials ?: 'O' }}
                                </div>

                                <div>
                                    <div class="owner-name">
                                        {{ $owner->name ?: 'Unnamed owner' }}
                                    </div>

                                    <div class="owner-meta">
                                        {{ $ownerType }}

                                        @if($owner->company_name)
                                        <span class="d-block">
                                            {{ $owner->company_name }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Owner number --}}
                        <td>
                            <span class="owner-number">
                                {{ $owner->owner_number ?: 'Not assigned' }}
                            </span>
                        </td>

                        {{-- Contact details --}}
                        <td>
                            <div class="contact-line">
                                <i class="bi bi-envelope"></i>
                                <span>
                                    {{ $owner->email ?: 'No email provided' }}
                                </span>
                            </div>

                            <div class="contact-line">
                                <i class="bi bi-telephone"></i>
                                <span>
                                    {{ $owner->phone ?: 'No phone provided' }}
                                </span>
                            </div>
                        </td>

                        {{-- Verification --}}
                        <td>
                            <span class="owner-status {{ $verificationClass }}">
                                {{ ucfirst($verification) }}
                            </span>
                        </td>

                        {{-- Account status --}}
                        <td>
                            <span class="owner-status {{ $accountClass }}">
                                {{ ucfirst($accountStatus) }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td class="text-end">
                            <div class="owner-actions">

                                <a href="{{ route('admin.owners.show', $owner) }}"
                                    class="owner-action-btn"
                                    title="View owner"
                                    aria-label="View {{ $ownerName }}">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <a href="{{ route('admin.owners.edit', $owner) }}"
                                    class="owner-action-btn"
                                    title="Edit owner"
                                    aria-label="Edit {{ $ownerName }}">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                @if ($owner->user_id)
                                <form method="POST" action="{{ route('admin.owners.resend-credentials', $owner) }}"
                                    onsubmit="return confirm('Generate a new password and email it to this owner?')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-secondary">Resend credentials</button>
                                </form>
                                @endif

                                @if($owner->status === 'active')
                                <form method="POST"
                                    action="{{ route('admin.owners.destroy', $owner) }}"
                                    class="m-0"
                                    onsubmit="return confirm('Deactivate this property owner?')">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        class="owner-action-btn danger"
                                        title="Deactivate owner"
                                        aria-label="Deactivate {{ $ownerName }}">
                                        <i class="bi bi-person-dash"></i>
                                    </button>
                                </form>
                                @endif

                            </div>
                        </td>
                    </tr>

                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="bi bi-people"></i>
                                </div>

                                <h3>No property owners found</h3>

                                <p>
                                    No records match your current search.
                                    Try changing your filters or register a new property owner.
                                </p>

                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                    @if(request()->filled('search') || request()->filled('status'))
                                    <a href="{{ route('admin.owners.index') }}"
                                        class="btn btn-outline-secondary btn-sm px-3 py-2">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>
                                        Clear filters
                                    </a>
                                    @endif

                                    <a href="{{ route('admin.owners.create') }}"
                                        class="btn-owner-primary">
                                        <i class="bi bi-plus-lg"></i>
                                        Register property owner
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
        @if($owners->hasPages())
        <div class="directory-footer">
            <div class="pagination-summary">
                Showing
                <strong>{{ $owners->firstItem() ?? 0 }}</strong>
                to
                <strong>{{ $owners->lastItem() ?? 0 }}</strong>
                of
                <strong>{{ $owners->total() }}</strong>
                owners
            </div>

            <div>
                {{ $owners->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif

    </section>

</div>
@endsection