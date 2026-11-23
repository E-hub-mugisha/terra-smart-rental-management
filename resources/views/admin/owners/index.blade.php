@extends('layouts.app')

@section('title', 'Property Owners')

@section('page_heading', 'Property Owners')

@section('page_subtitle', 'Manage owner accounts, ownership records and verification.')

@push('styles')
<style>
    .owners-page {
        --owners-orange: var(--terra-orange, #D05208);
        --owners-orange-dark: #AD4307;
        --owners-orange-soft: #FFF0E7;
        --owners-navy: var(--terra-navy, #19265D);
        --owners-border: var(--terra-border, #E7EAF1);
        --owners-muted: var(--terra-muted, #778197);
        color: #263047;
    }

    /* Heading */
    .owners-page .page-intro {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 20px; flex-wrap: wrap; margin-bottom: 24px;
    }
    .owners-page .breadcrumb-line {
        display: flex; align-items: center; flex-wrap: wrap; gap: 8px;
        margin-bottom: 11px; color: var(--owners-muted); font-size: 11px;
    }
    .owners-page .breadcrumb-line a { color: var(--owners-muted); text-decoration: none; }
    .owners-page .breadcrumb-line a:hover { color: var(--owners-orange); }
    .owners-page .breadcrumb-line i { font-size: 10px; color: #AAB1BF; }
    .owners-page .page-title {
        margin: 0; color: var(--owners-navy); font-size: clamp(23px, 2vw, 29px);
        line-height: 1.25; letter-spacing: -.8px; font-weight: 800;
    }
    .owners-page .page-description { margin: 8px 0 0; color: var(--owners-muted); font-size: 12px; line-height: 1.7; }

    .owners-page .btn-owner-primary {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        min-height: 42px; padding: 11px 16px; border: 1px solid var(--owners-orange);
        border-radius: 10px; background: var(--owners-orange); color: #fff;
        font-size: 12px; font-weight: 700; text-decoration: none;
        transition: .2s ease; white-space: nowrap;
    }
    .owners-page .btn-owner-primary:hover {
        background: var(--owners-orange-dark); border-color: var(--owners-orange-dark);
        color: #fff; transform: translateY(-1px);
    }

    /* Stat cards (clickable, platform-wide) */
    .owners-page .owner-stat-card {
        display: block; position: relative; height: 100%; min-height: 112px; padding: 18px;
        background: #fff; border: 1px solid var(--owners-border); border-radius: 14px;
        box-shadow: 0 5px 20px rgba(25, 38, 93, .035); color: inherit; text-decoration: none;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }
    .owners-page a.owner-stat-card:hover {
        transform: translateY(-2px); border-color: #D9DEEA;
        box-shadow: 0 9px 24px rgba(25, 38, 93, .08); color: inherit;
    }
    .owners-page .owner-stat-card.is-active { border-color: var(--owners-orange); box-shadow: 0 0 0 3px rgba(208, 82, 8, .08); }
    .owners-page .owner-stat-top { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
    .owners-page .owner-stat-label { color: var(--owners-muted); font-size: 11px; font-weight: 600; }
    .owners-page .owner-stat-icon {
        display: flex; align-items: center; justify-content: center;
        width: 37px; height: 37px; flex-shrink: 0; border-radius: 11px; font-size: 17px;
    }
    .owners-page .stat-orange { background: #FFF0E7; color: #D05208; }
    .owners-page .stat-green  { background: #E8F7EF; color: #16845B; }
    .owners-page .stat-amber  { background: #FFF4DF; color: #A66A09; }
    .owners-page .stat-navy   { background: #EDF0FB; color: #19265D; }
    .owners-page .owner-stat-number {
        margin-top: 10px; color: var(--owners-navy); font-size: 27px;
        line-height: 1.1; font-weight: 800; letter-spacing: -.8px;
    }
    .owners-page .owner-stat-footnote { margin-top: 6px; color: var(--owners-muted); font-size: 11px; }

    /* Filter card */
    .owners-page .owner-filter-card {
        padding: 18px; background: #fff; border: 1px solid var(--owners-border);
        border-radius: 14px; box-shadow: 0 5px 20px rgba(25, 38, 93, .035);
    }
    .owners-page .form-label { display: block; margin-bottom: 7px; color: #4B556B; font-size: 11px; font-weight: 700; }
    .owners-page .form-control, .owners-page .form-select, .owners-page .input-group-text {
        min-height: 42px; border-color: #E1E5ED; font-size: 12px; box-shadow: none;
    }
    .owners-page .form-control, .owners-page .form-select { color: #30394D; border-radius: 9px; }
    .owners-page .input-group .input-group-text {
        border-radius: 9px 0 0 9px; background: #FAFBFD; color: #7D8799; padding: 0 13px;
    }
    .owners-page .input-group .form-control { border-radius: 0 9px 9px 0; }
    .owners-page .form-control:focus, .owners-page .form-select:focus {
        border-color: #E8A47E; box-shadow: 0 0 0 3px rgba(208, 82, 8, .08);
    }
    .owners-page .btn-filter {
        min-height: 42px; display: inline-flex; align-items: center; justify-content: center; gap: 7px;
        border: 1px solid var(--owners-orange); border-radius: 9px; padding: 10px 14px;
        background: var(--owners-orange); color: #fff; font-size: 11px; font-weight: 700; white-space: nowrap;
    }
    .owners-page .btn-filter:hover { background: var(--owners-orange-dark); color: #fff; }
    .owners-page .btn-reset {
        width: 42px; min-width: 42px; height: 42px; display: inline-flex; align-items: center;
        justify-content: center; border: 1px solid #E1E5ED; border-radius: 9px; background: #fff;
        color: #657086; font-size: 15px; text-decoration: none;
    }
    .owners-page .btn-reset:hover { border-color: #E8A47E; color: var(--owners-orange); background: #FFF8F3; }

    .owners-page .status-pills { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 14px; }
    .owners-page .status-pill {
        display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px;
        border: 1px solid #E1E5ED; border-radius: 20px; background: #fff; color: #4B556B;
        font-size: 11px; font-weight: 700; text-decoration: none;
    }
    .owners-page .status-pill:hover { background: #F5F7FB; color: var(--owners-navy); }
    .owners-page .status-pill.active { background: var(--owners-navy); border-color: var(--owners-navy); color: #fff; }
    .owners-page .status-pill .pill-count {
        background: #EEF1F7; color: #5B6880; border-radius: 10px; padding: 0 7px; font-size: 11px; font-weight: 800;
    }
    .owners-page .status-pill.active .pill-count { background: rgba(255, 255, 255, .2); color: #fff; }

    /* Directory */
    .owners-page .owner-directory {
        overflow: hidden; background: #fff; border: 1px solid var(--owners-border);
        border-radius: 15px; box-shadow: 0 5px 24px rgba(25, 38, 93, .04);
    }
    .owners-page .directory-header {
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;
        gap: 15px; padding: 18px 22px; border-bottom: 1px solid var(--owners-border);
    }
    .owners-page .directory-title {
        display: flex; align-items: center; gap: 11px; margin: 0; color: var(--owners-navy);
        font-size: 15px; font-weight: 800; letter-spacing: -.2px;
    }
    .owners-page .directory-title-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 37px; height: 37px; border-radius: 11px; background: #EDF0FB;
        color: var(--owners-navy); font-size: 17px;
    }
    .owners-page .directory-subtitle { margin: 5px 0 0 48px; color: var(--owners-muted); font-size: 11px; }
    .owners-page .directory-total {
        display: inline-flex; align-items: center; gap: 7px; padding: 8px 11px;
        border: 1px solid #F1D7C8; border-radius: 8px; background: #FFF7F2;
        color: #A7460E; font-size: 11px; font-weight: 700;
    }

    .owners-page .owner-table-wrap { width: 100%; overflow-x: auto; }
    .owners-page .owner-table { width: 100%; min-width: 880px; margin: 0; border-collapse: separate; border-spacing: 0; }
    .owners-page .owner-table thead th {
        padding: 13px 17px; border-bottom: 1px solid var(--owners-border); background: #F8F9FC;
        color: #7B8498; font-size: 11px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .55px; white-space: nowrap;
    }
    .owners-page .owner-table tbody td {
        padding: 14px 17px; border-bottom: 1px solid #EEF0F5; color: #4D586D;
        font-size: 11px; vertical-align: middle;
    }
    .owners-page .owner-table tbody tr:last-child td { border-bottom: 0; }
    .owners-page .owner-table tbody tr { transition: background .15s ease; }
    .owners-page .owner-table tbody tr:hover { background: #FCFCFE; }

    .owners-page .owner-profile { display: flex; align-items: center; gap: 11px; min-width: 210px; }
    .owners-page .owner-avatar {
        width: 42px; height: 42px; flex-shrink: 0; display: inline-flex; align-items: center;
        justify-content: center; border-radius: 12px; background: var(--owners-orange-soft);
        color: var(--owners-orange); font-size: 12px; font-weight: 800; border: 1px solid #F9E1D2;
    }
    .owners-page .owner-avatar.is-company { background: #EDF0FB; color: var(--owners-navy); border-color: #DDE2F3; }
    .owners-page .owner-name {
        color: var(--owners-navy); font-size: 12px; font-weight: 800; line-height: 1.4;
        overflow-wrap: anywhere; text-decoration: none; display: inline-block;
    }
    .owners-page a.owner-name:hover { color: var(--owners-orange); text-decoration: underline; }
    .owners-page .owner-meta { margin-top: 3px; color: var(--owners-muted); font-size: 11px; line-height: 1.5; }
    .owners-page .owner-number {
        display: inline-block; margin-top: 5px; padding: 2px 7px; border: 1px solid #E8EBF3;
        border-radius: 5px; background: #F8F9FC; color: var(--owners-navy);
        font-size: 11px; font-weight: 800; white-space: nowrap;
    }

    .owners-page .contact-line {
        display: flex; align-items: flex-start; gap: 8px; margin-bottom: 6px;
        max-width: 235px; overflow-wrap: anywhere; line-height: 1.5;
    }
    .owners-page .contact-line:last-child { margin-bottom: 0; }
    .owners-page .contact-line i { width: 13px; margin-top: 1px; color: #9099AB; flex-shrink: 0; }

    .owners-page .property-count {
        display: inline-flex; align-items: center; gap: 6px; padding: 5px 9px;
        border-radius: 7px; background: #F3F5FA; color: var(--owners-navy);
        font-size: 11.5px; font-weight: 800; white-space: nowrap;
    }
    .owners-page .property-count i { color: #8792A7; font-size: 12px; }

    .owners-page .owner-status {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        padding: 6px 9px; border-radius: 7px; font-size: 11px; font-weight: 800; white-space: nowrap;
    }
    .owners-page .owner-status::before {
        content: ""; width: 6px; height: 6px; flex-shrink: 0; border-radius: 50%; background: currentColor;
    }
    .owners-page .owner-status.active, .owners-page .owner-status.verified { background: #E8F7EF; color: #16845B; }
    .owners-page .owner-status.inactive, .owners-page .owner-status.rejected { background: #F0F1F5; color: #737C8D; }
    .owners-page .owner-status.pending { background: #FFF4DF; color: #9A6810; }
    .owners-page .owner-status.unknown { background: #EEF1F6; color: #667085; }

    /* Actions dropdown (small font) */
    .owners-page .owner-action-btn {
        width: 33px; height: 33px; display: inline-flex; align-items: center; justify-content: center;
        border: 1px solid #E3E7EF; border-radius: 8px; background: #fff; color: var(--owners-navy);
        font-size: 14px; transition: all .18s ease;
    }
    .owners-page .owner-action-btn:hover, .owners-page .owner-action-btn[aria-expanded="true"] {
        color: var(--owners-orange); background: #FFF5EF; border-color: #F1C4AA;
    }
    .owners-page .action-menu {
        min-width: 200px; padding: 6px; border: 1px solid var(--owners-border);
        border-radius: 12px; box-shadow: 0 10px 30px rgba(25, 38, 93, .12); font-size: 11px;
    }
    .owners-page .action-menu .dropdown-item {
        display: flex; align-items: center; gap: 9px; width: 100%; padding: 7px 10px;
        border: 0; border-radius: 8px; background: transparent; color: #35425B;
        font-size: 11px; font-weight: 600; text-align: left;
    }
    .owners-page .action-menu .dropdown-item i { font-size: 13px; color: #7B879C; }
    .owners-page .action-menu .dropdown-item:hover, .owners-page .action-menu .dropdown-item:focus {
        background: #F3F5FA; color: var(--owners-navy);
    }
    .owners-page .action-menu .item-primary, .owners-page .action-menu .item-primary i { color: var(--owners-orange-dark); }
    .owners-page .action-menu .item-primary:hover { background: var(--owners-orange-soft); color: var(--owners-orange-dark); }
    .owners-page .action-menu .item-danger, .owners-page .action-menu .item-danger i { color: #B42318; }
    .owners-page .action-menu .item-danger:hover { background: #FFF0EF; color: #B42318; }
    .owners-page .action-menu .dropdown-divider { margin: 5px 4px; border-color: #EDF0F5; }
    .owners-page .action-menu form { margin: 0; }

    /* Empty + footer */
    .owners-page .empty-state { padding: 55px 20px; text-align: center; }
    .owners-page .empty-icon {
        display: flex; align-items: center; justify-content: center; width: 62px; height: 62px;
        margin: 0 auto 17px; border-radius: 18px; background: var(--owners-orange-soft);
        color: var(--owners-orange); font-size: 25px;
    }
    .owners-page .empty-state h3 { color: var(--owners-navy); font-size: 15px; font-weight: 800; }
    .owners-page .empty-state p { max-width: 370px; margin: 8px auto 18px; color: var(--owners-muted); font-size: 12px; line-height: 1.7; }

    .owners-page .directory-footer {
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;
        gap: 12px; padding: 15px 20px; border-top: 1px solid var(--owners-border); background: #FCFCFE;
    }
    .owners-page .pagination-summary { color: var(--owners-muted); font-size: 11px; }
    .owners-page .pagination { margin: 0; gap: 4px; }
    .owners-page .pagination .page-link {
        min-width: 32px; min-height: 32px; display: flex; align-items: center; justify-content: center;
        border: 1px solid #E3E7EF; border-radius: 7px !important; color: var(--owners-navy);
        font-size: 11px; box-shadow: none;
    }
    .owners-page .pagination .page-item.active .page-link { border-color: var(--owners-orange); background: var(--owners-orange); color: #fff; }
    .owners-page .pagination .page-item.disabled .page-link { color: #B5BBC8; background: #F8F9FC; }

    @media (max-width: 767.98px) {
        .owners-page .page-intro { align-items: stretch; }
        .owners-page .page-intro > div:last-child, .owners-page .page-intro .btn-owner-primary { width: 100%; }
        .owners-page .owner-stat-card { min-height: 104px; padding: 15px; }
        .owners-page .owner-stat-number { font-size: 24px; }
        .owners-page .owner-filter-card { padding: 15px; }
        .owners-page .directory-header { padding: 16px; }
        .owners-page .directory-footer { padding: 14px; }
        .owners-page .directory-footer .pagination { width: 100%; flex-wrap: wrap; }
    }
</style>
@endpush

@section('content')
<div class="owners-page">

    @php
        /*
         * Platform-wide figures come straight from the database, so they stay
         * correct across pagination and filters.
         */
        $statusCounts = \Illuminate\Support\Facades\DB::table('property_owners')
            ->whereNull('deleted_at')
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $verificationCounts = \Illuminate\Support\Facades\DB::table('property_owners')
            ->whereNull('deleted_at')
            ->selectRaw('verification_status, COUNT(*) as total')
            ->groupBy('verification_status')
            ->pluck('total', 'verification_status');

        $allOwnersCount = $statusCounts->sum();

        // Number of properties for the owners on this page (single query).
        $propertyCounts = \Illuminate\Support\Facades\DB::table('properties')
            ->whereIn('property_owner_id', $owners->getCollection()->pluck('id')->all())
            ->whereNull('deleted_at')
            ->selectRaw('property_owner_id, COUNT(*) as total')
            ->groupBy('property_owner_id')
            ->pluck('total', 'property_owner_id');

        $activeStatus = request('status', '');
        $hasFilters   = request()->filled('search') || request()->filled('status');
    @endphp

    {{-- Page heading --}}
    <div class="page-intro">
        <div>
            <div class="breadcrumb-line">
                <a href="{{ \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/') }}">Dashboard</a>
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
                <i class="bi bi-plus-lg"></i> Register property owner
            </a>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <a href="{{ route('admin.owners.index') }}"
               class="owner-stat-card {{ !$hasFilters ? 'is-active' : '' }}">
                <div class="owner-stat-top">
                    <span class="owner-stat-label">Total owners</span>
                    <span class="owner-stat-icon stat-orange"><i class="bi bi-people"></i></span>
                </div>
                <div class="owner-stat-number">{{ number_format($allOwnersCount) }}</div>
                <div class="owner-stat-footnote">All registered owners</div>
            </a>
        </div>

        <div class="col-6 col-xl-3">
            <a href="{{ route('admin.owners.index', ['status' => 'active']) }}"
               class="owner-stat-card {{ $activeStatus === 'active' ? 'is-active' : '' }}">
                <div class="owner-stat-top">
                    <span class="owner-stat-label">Active accounts</span>
                    <span class="owner-stat-icon stat-green"><i class="bi bi-person-check"></i></span>
                </div>
                <div class="owner-stat-number">{{ number_format($statusCounts['active'] ?? 0) }}</div>
                <div class="owner-stat-footnote">Click to filter active owners</div>
            </a>
        </div>

        <div class="col-6 col-xl-3">
            <div class="owner-stat-card">
                <div class="owner-stat-top">
                    <span class="owner-stat-label">Pending verification</span>
                    <span class="owner-stat-icon stat-amber"><i class="bi bi-shield-exclamation"></i></span>
                </div>
                <div class="owner-stat-number">{{ number_format($verificationCounts['pending'] ?? 0) }}</div>
                <div class="owner-stat-footnote">Awaiting verification</div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="owner-stat-card">
                <div class="owner-stat-top">
                    <span class="owner-stat-label">Verified owners</span>
                    <span class="owner-stat-icon stat-navy"><i class="bi bi-patch-check"></i></span>
                </div>
                <div class="owner-stat-number">{{ number_format($verificationCounts['verified'] ?? 0) }}</div>
                <div class="owner-stat-footnote">Identity verified</div>
            </div>
        </div>
    </div>

    {{-- Search and filters --}}
    <form method="GET" action="{{ route('admin.owners.index') }}" class="owner-filter-card mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-6">
                <label for="ownerSearch" class="form-label">Search owners</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input id="ownerSearch" type="search" name="search" class="form-control"
                           value="{{ request('search') }}"
                           placeholder="Name, owner number, email or phone">
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label for="ownerStatus" class="form-label">Account status</label>
                <select id="ownerStatus" name="status" class="form-select">
                    <option value="">All account statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn-filter flex-grow-1">
                        <i class="bi bi-funnel"></i> Apply filters
                    </button>
                    <a href="{{ route('admin.owners.index') }}" class="btn-reset"
                       title="Reset filters" aria-label="Reset filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="status-pills">
            <a href="{{ route('admin.owners.index', array_filter(['search' => request('search')])) }}"
               class="status-pill {{ $activeStatus === '' ? 'active' : '' }}">
                All <span class="pill-count">{{ number_format($allOwnersCount) }}</span>
            </a>
            <a href="{{ route('admin.owners.index', array_filter(['search' => request('search'), 'status' => 'active'])) }}"
               class="status-pill {{ $activeStatus === 'active' ? 'active' : '' }}">
                Active <span class="pill-count">{{ number_format($statusCounts['active'] ?? 0) }}</span>
            </a>
            <a href="{{ route('admin.owners.index', array_filter(['search' => request('search'), 'status' => 'inactive'])) }}"
               class="status-pill {{ $activeStatus === 'inactive' ? 'active' : '' }}">
                Inactive <span class="pill-count">{{ number_format($statusCounts['inactive'] ?? 0) }}</span>
            </a>

            @if($hasFilters)
                <span class="text-secondary ms-2" style="font-size:11px;">
                    @if(request()->filled('search')) Search: <strong>{{ request('search') }}</strong> @endif
                </span>
                <a href="{{ route('admin.owners.index') }}" class="text-decoration-none"
                   style="font-size:11px;color:var(--owners-orange);">Clear all</a>
            @endif
        </div>
    </form>

    {{-- Owner directory --}}
    <section class="owner-directory">

        <div class="directory-header">
            <div>
                <h2 class="directory-title">
                    <span class="directory-title-icon"><i class="bi bi-person-vcard"></i></span>
                    Owner directory
                </h2>
                <p class="directory-subtitle">Ownership records and account contact information</p>
            </div>

            <div class="directory-total">
                <i class="bi bi-people"></i>
                {{ number_format($owners->total()) }} {{ $owners->total() === 1 ? 'owner' : 'owners' }}
            </div>
        </div>

        <div class="owner-table-wrap">
            <table class="owner-table">
                <thead>
                    <tr>
                        <th>Owner</th>
                        <th>Contact information</th>
                        <th>Properties</th>
                        <th>Verification</th>
                        <th>Account</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($owners as $owner)
                        @php
                            $ownerName = trim($owner->name ?? 'Property Owner');

                            $ownerInitials = collect(preg_split('/\s+/', $ownerName))
                                ->filter()->take(2)
                                ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
                                ->implode('');

                            $verification  = strtolower($owner->verification_status ?? 'pending');
                            $accountStatus = strtolower($owner->status ?? 'inactive');

                            $verificationClass = in_array($verification, ['pending', 'verified', 'rejected'], true)
                                ? $verification : 'unknown';
                            $accountClass = in_array($accountStatus, ['active', 'inactive'], true)
                                ? $accountStatus : 'unknown';

                            $ownerType = ucfirst(str_replace('_', ' ', $owner->owner_type ?? 'individual'));
                            $isCompany = ($owner->owner_type ?? '') === 'company';
                            $propsTotal = $propertyCounts[$owner->id] ?? 0;
                        @endphp

                        <tr>
                            {{-- Owner --}}
                            <td>
                                <div class="owner-profile">
                                    <div class="owner-avatar {{ $isCompany ? 'is-company' : '' }}">
                                        @if($isCompany)
                                            <i class="bi bi-building"></i>
                                        @else
                                            {{ $ownerInitials ?: 'O' }}
                                        @endif
                                    </div>

                                    <div>
                                        <a href="{{ route('admin.owners.show', $owner) }}" class="owner-name">
                                            {{ $owner->name ?: 'Unnamed owner' }}
                                        </a>
                                        <div class="owner-meta">
                                            {{ $ownerType }}
                                            @if($owner->company_name)
                                                &middot; {{ $owner->company_name }}
                                            @endif
                                        </div>
                                        <span class="owner-number">{{ $owner->owner_number ?: 'Not assigned' }}</span>
                                    </div>
                                </div>
                            </td>

                            {{-- Contact --}}
                            <td>
                                <div class="contact-line">
                                    <i class="bi bi-envelope"></i>
                                    <span>{{ $owner->email ?: 'No email provided' }}</span>
                                </div>
                                <div class="contact-line">
                                    <i class="bi bi-telephone"></i>
                                    <span>{{ $owner->phone ?: 'No phone provided' }}</span>
                                </div>
                            </td>

                            {{-- Properties --}}
                            <td>
                                <span class="property-count">
                                    <i class="bi bi-buildings"></i>
                                    {{ $propsTotal }} {{ $propsTotal === 1 ? 'property' : 'properties' }}
                                </span>
                            </td>

                            {{-- Verification --}}
                            <td>
                                <span class="owner-status {{ $verificationClass }}">{{ ucfirst($verification) }}</span>
                            </td>

                            {{-- Account --}}
                            <td>
                                <span class="owner-status {{ $accountClass }}">{{ ucfirst($accountStatus) }}</span>
                            </td>

                            {{-- Actions --}}
                            <td class="text-end">
                                <div class="dropdown">
                                    <button type="button" class="owner-action-btn"
                                            data-bs-toggle="dropdown"
                                            data-bs-popper-config='{"strategy":"fixed"}'
                                            aria-expanded="false"
                                            aria-label="Actions for {{ $ownerName }}">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end action-menu">
                                        <li>
                                            <a class="dropdown-item item-primary"
                                               href="{{ route('admin.owners.show', $owner) }}">
                                                <i class="bi bi-person-lines-fill"></i> View owner profile
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item"
                                               href="{{ route('admin.owners.edit', $owner) }}">
                                                <i class="bi bi-pencil-square"></i> Edit owner
                                            </a>
                                        </li>

                                        @if($owner->user_id)
                                            <li>
                                                <form method="POST"
                                                      action="{{ route('admin.owners.resend-credentials', $owner) }}"
                                                      onsubmit="return confirm('Generate a new password and email it to this owner?')">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-envelope-arrow-up"></i> Resend credentials
                                                    </button>
                                                </form>
                                            </li>
                                        @endif

                                        @if($owner->status === 'active')
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST"
                                                      action="{{ route('admin.owners.destroy', $owner) }}"
                                                      onsubmit="return confirm('Deactivate this property owner?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item item-danger">
                                                        <i class="bi bi-person-dash"></i> Deactivate owner
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                            <li>
                                                <x-purge-button type="owner" :id="$owner->id" what="this owner, their properties" class="dropdown-item item-danger" />
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
                                    <h3>No property owners found</h3>
                                    <p>
                                        No records match your current search.
                                        Try changing your filters or register a new property owner.
                                    </p>

                                    <div class="d-flex flex-wrap justify-content-center gap-2">
                                        @if($hasFilters)
                                            <a href="{{ route('admin.owners.index') }}"
                                               class="btn btn-outline-secondary btn-sm px-3 py-2">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Clear filters
                                            </a>
                                        @endif

                                        <a href="{{ route('admin.owners.create') }}" class="btn-owner-primary">
                                            <i class="bi bi-plus-lg"></i> Register property owner
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
                    <strong>{{ $owners->firstItem() ?? 0 }}</strong> to
                    <strong>{{ $owners->lastItem() ?? 0 }}</strong> of
                    <strong>{{ $owners->total() }}</strong> owners
                </div>

                <div>{{ $owners->withQueryString()->links('pagination::bootstrap-5') }}</div>
            </div>
        @endif

    </section>
</div>
@endsection