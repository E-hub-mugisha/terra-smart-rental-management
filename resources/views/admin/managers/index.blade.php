@extends('layouts.app')

@section('title', 'Property Managers')

@section('page_heading', 'Property Managers')

@section('page_subtitle', 'Manage manager accounts, employment details and access status.')

@push('styles')
<style>
    .managers-page {
        --mgr-orange: var(--terra-orange, #D05208);
        --mgr-orange-dark: #AD4307;
        --mgr-orange-soft: #FFF0E7;
        --mgr-navy: var(--terra-navy, #19265D);
        --mgr-border: var(--terra-border, #E7EAF1);
        --mgr-muted: var(--terra-muted, #778197);
        color: #263047;
    }

    /* Heading */
    .managers-page .page-intro {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 20px; flex-wrap: wrap; margin-bottom: 24px;
    }
    .managers-page .breadcrumb-line {
        display: flex; align-items: center; flex-wrap: wrap; gap: 8px;
        margin-bottom: 11px; color: var(--mgr-muted); font-size: 11px;
    }
    .managers-page .breadcrumb-line a { color: var(--mgr-muted); text-decoration: none; }
    .managers-page .breadcrumb-line a:hover { color: var(--mgr-orange); }
    .managers-page .breadcrumb-line i { font-size: 10px; color: #AAB1BF; }
    .managers-page .page-title {
        margin: 0; color: var(--mgr-navy); font-size: clamp(23px, 2vw, 29px);
        line-height: 1.25; letter-spacing: -.8px; font-weight: 800;
    }
    .managers-page .page-description { margin: 8px 0 0; color: var(--mgr-muted); font-size: 12px; line-height: 1.7; }

    .managers-page .btn-mgr-primary {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        min-height: 42px; padding: 11px 16px; border: 1px solid var(--mgr-orange);
        border-radius: 10px; background: var(--mgr-orange); color: #fff;
        font-size: 12px; font-weight: 700; text-decoration: none;
        transition: .2s ease; white-space: nowrap;
    }
    .managers-page .btn-mgr-primary:hover {
        background: var(--mgr-orange-dark); border-color: var(--mgr-orange-dark);
        color: #fff; transform: translateY(-1px);
    }

    /* Stat cards */
    .managers-page .mgr-stat-card {
        display: block; position: relative; height: 100%; min-height: 112px; padding: 18px;
        background: #fff; border: 1px solid var(--mgr-border); border-radius: 14px;
        box-shadow: 0 5px 20px rgba(25, 38, 93, .035); color: inherit; text-decoration: none;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }
    .managers-page a.mgr-stat-card:hover {
        transform: translateY(-2px); border-color: #D9DEEA;
        box-shadow: 0 9px 24px rgba(25, 38, 93, .08); color: inherit;
    }
    .managers-page .mgr-stat-card.is-active { border-color: var(--mgr-orange); box-shadow: 0 0 0 3px rgba(208, 82, 8, .08); }
    .managers-page .mgr-stat-top { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
    .managers-page .mgr-stat-label { color: var(--mgr-muted); font-size: 11px; font-weight: 600; }
    .managers-page .mgr-stat-icon {
        display: flex; align-items: center; justify-content: center;
        width: 37px; height: 37px; flex-shrink: 0; border-radius: 11px; font-size: 17px;
    }
    .managers-page .stat-orange { background: #FFF0E7; color: #D05208; }
    .managers-page .stat-green  { background: #E8F7EF; color: #16845B; }
    .managers-page .stat-gray   { background: #F0F1F5; color: #737C8D; }
    .managers-page .stat-navy   { background: #EDF0FB; color: #19265D; }
    .managers-page .mgr-stat-number {
        margin-top: 10px; color: var(--mgr-navy); font-size: 27px;
        line-height: 1.1; font-weight: 800; letter-spacing: -.8px;
    }
    .managers-page .mgr-stat-footnote { margin-top: 6px; color: var(--mgr-muted); font-size: 11px; }

    /* Filter card */
    .managers-page .mgr-filter-card {
        padding: 18px; background: #fff; border: 1px solid var(--mgr-border);
        border-radius: 14px; box-shadow: 0 5px 20px rgba(25, 38, 93, .035);
    }
    .managers-page .form-label { display: block; margin-bottom: 7px; color: #4B556B; font-size: 11px; font-weight: 700; }
    .managers-page .form-control, .managers-page .form-select, .managers-page .input-group-text {
        min-height: 42px; border-color: #E1E5ED; font-size: 12px; box-shadow: none;
    }
    .managers-page .form-control, .managers-page .form-select { color: #30394D; border-radius: 9px; }
    .managers-page .input-group .input-group-text {
        border-radius: 9px 0 0 9px; background: #FAFBFD; color: #7D8799; padding: 0 13px;
    }
    .managers-page .input-group .form-control { border-radius: 0 9px 9px 0; }
    .managers-page .form-control:focus, .managers-page .form-select:focus {
        border-color: #E8A47E; box-shadow: 0 0 0 3px rgba(208, 82, 8, .08);
    }
    .managers-page .btn-filter {
        min-height: 42px; display: inline-flex; align-items: center; justify-content: center; gap: 7px;
        border: 1px solid var(--mgr-orange); border-radius: 9px; padding: 10px 14px;
        background: var(--mgr-orange); color: #fff; font-size: 11px; font-weight: 700; white-space: nowrap;
    }
    .managers-page .btn-filter:hover { background: var(--mgr-orange-dark); color: #fff; }
    .managers-page .btn-reset {
        width: 42px; min-width: 42px; height: 42px; display: inline-flex; align-items: center;
        justify-content: center; border: 1px solid #E1E5ED; border-radius: 9px; background: #fff;
        color: #657086; font-size: 15px; text-decoration: none;
    }
    .managers-page .btn-reset:hover { border-color: #E8A47E; color: var(--mgr-orange); background: #FFF8F3; }

    .managers-page .status-pills { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 14px; }
    .managers-page .status-pill {
        display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px;
        border: 1px solid #E1E5ED; border-radius: 20px; background: #fff; color: #4B556B;
        font-size: 11px; font-weight: 700; text-decoration: none;
    }
    .managers-page .status-pill:hover { background: #F5F7FB; color: var(--mgr-navy); }
    .managers-page .status-pill.active { background: var(--mgr-navy); border-color: var(--mgr-navy); color: #fff; }
    .managers-page .status-pill .pill-count {
        background: #EEF1F7; color: #5B6880; border-radius: 10px; padding: 0 7px; font-size: 11px; font-weight: 800;
    }
    .managers-page .status-pill.active .pill-count { background: rgba(255, 255, 255, .2); color: #fff; }

    /* Directory */
    .managers-page .mgr-directory {
        overflow: hidden; background: #fff; border: 1px solid var(--mgr-border);
        border-radius: 15px; box-shadow: 0 5px 24px rgba(25, 38, 93, .04);
    }
    .managers-page .directory-header {
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;
        gap: 15px; padding: 18px 22px; border-bottom: 1px solid var(--mgr-border);
    }
    .managers-page .directory-title {
        display: flex; align-items: center; gap: 11px; margin: 0; color: var(--mgr-navy);
        font-size: 15px; font-weight: 800; letter-spacing: -.2px;
    }
    .managers-page .directory-title-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 37px; height: 37px; border-radius: 11px; background: #EDF0FB;
        color: var(--mgr-navy); font-size: 17px;
    }
    .managers-page .directory-subtitle { margin: 5px 0 0 48px; color: var(--mgr-muted); font-size: 11px; }
    .managers-page .directory-total {
        display: inline-flex; align-items: center; gap: 7px; padding: 8px 11px;
        border: 1px solid #F1D7C8; border-radius: 8px; background: #FFF7F2;
        color: #A7460E; font-size: 11px; font-weight: 700;
    }

    .managers-page .mgr-table-wrap { width: 100%; overflow-x: auto; }
    .managers-page .mgr-table { width: 100%; min-width: 900px; margin: 0; border-collapse: separate; border-spacing: 0; }
    .managers-page .mgr-table thead th {
        padding: 13px 17px; border-bottom: 1px solid var(--mgr-border); background: #F8F9FC;
        color: #7B8498; font-size: 11px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .55px; white-space: nowrap;
    }
    .managers-page .mgr-table tbody td {
        padding: 14px 17px; border-bottom: 1px solid #EEF0F5; color: #4D586D;
        font-size: 11px; vertical-align: middle;
    }
    .managers-page .mgr-table tbody tr:last-child td { border-bottom: 0; }
    .managers-page .mgr-table tbody tr { transition: background .15s ease; }
    .managers-page .mgr-table tbody tr:hover { background: #FCFCFE; }

    .managers-page .mgr-profile { display: flex; align-items: center; gap: 11px; min-width: 230px; }
    .managers-page .mgr-avatar {
        width: 42px; height: 42px; flex-shrink: 0; display: inline-flex; align-items: center;
        justify-content: center; border-radius: 12px; background: #EDF0FB;
        color: var(--mgr-navy); font-size: 12px; font-weight: 800; border: 1px solid #DDE2F3;
    }
    .managers-page .mgr-name {
        color: var(--mgr-navy); font-size: 12px; font-weight: 800; line-height: 1.4;
        overflow-wrap: anywhere; text-decoration: none; display: inline-block;
    }
    .managers-page a.mgr-name:hover { color: var(--mgr-orange); text-decoration: underline; }
    .managers-page .mgr-meta { margin-top: 3px; color: var(--mgr-muted); font-size: 11px; line-height: 1.5; }
    .managers-page .mgr-number {
        display: inline-block; margin-top: 5px; padding: 2px 7px; border: 1px solid #E8EBF3;
        border-radius: 5px; background: #F8F9FC; color: var(--mgr-navy);
        font-size: 11px; font-weight: 800; white-space: nowrap;
    }

    .managers-page .contact-line {
        display: flex; align-items: flex-start; gap: 8px; margin-bottom: 6px;
        max-width: 235px; overflow-wrap: anywhere; line-height: 1.5;
    }
    .managers-page .contact-line:last-child { margin-bottom: 0; }
    .managers-page .contact-line i { width: 13px; margin-top: 1px; color: #9099AB; flex-shrink: 0; }

    .managers-page .job-title { color: var(--mgr-navy); font-size: 11px; font-weight: 700; }
    .managers-page .employment-chip {
        display: inline-block; margin-top: 5px; padding: 3px 8px; border-radius: 6px;
        background: #F3F5FA; color: #5B6880; font-size: 11px; font-weight: 700;
    }

    .managers-page .property-count {
        display: inline-flex; align-items: center; gap: 6px; padding: 5px 9px;
        border-radius: 7px; background: #F3F5FA; color: var(--mgr-navy);
        font-size: 11.5px; font-weight: 800; white-space: nowrap;
    }
    .managers-page .property-count i { color: #8792A7; font-size: 12px; }

    .managers-page .mgr-status {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        padding: 6px 9px; border-radius: 7px; font-size: 11px; font-weight: 800; white-space: nowrap;
    }
    .managers-page .mgr-status::before {
        content: ""; width: 6px; height: 6px; flex-shrink: 0; border-radius: 50%; background: currentColor;
    }
    .managers-page .mgr-status.active { background: #E8F7EF; color: #16845B; }
    .managers-page .mgr-status.inactive { background: #F0F1F5; color: #737C8D; }
    .managers-page .mgr-status.unknown { background: #FFF4DF; color: #9A6810; }

    /* Actions dropdown (small font) */
    .managers-page .mgr-action-btn {
        width: 33px; height: 33px; display: inline-flex; align-items: center; justify-content: center;
        border: 1px solid #E3E7EF; border-radius: 8px; background: #fff; color: var(--mgr-navy);
        font-size: 14px; transition: all .18s ease;
    }
    .managers-page .mgr-action-btn:hover, .managers-page .mgr-action-btn[aria-expanded="true"] {
        color: var(--mgr-orange); background: #FFF5EF; border-color: #F1C4AA;
    }
    .managers-page .action-menu {
        min-width: 200px; padding: 6px; border: 1px solid var(--mgr-border);
        border-radius: 12px; box-shadow: 0 10px 30px rgba(25, 38, 93, .12); font-size: 11px;
    }
    .managers-page .action-menu .dropdown-item {
        display: flex; align-items: center; gap: 9px; width: 100%; padding: 7px 10px;
        border: 0; border-radius: 8px; background: transparent; color: #35425B;
        font-size: 11px; font-weight: 600; text-align: left;
    }
    .managers-page .action-menu .dropdown-item i { font-size: 13px; color: #7B879C; }
    .managers-page .action-menu .dropdown-item:hover, .managers-page .action-menu .dropdown-item:focus {
        background: #F3F5FA; color: var(--mgr-navy);
    }
    .managers-page .action-menu .item-primary, .managers-page .action-menu .item-primary i { color: var(--mgr-orange-dark); }
    .managers-page .action-menu .item-primary:hover { background: var(--mgr-orange-soft); color: var(--mgr-orange-dark); }
    .managers-page .action-menu .item-danger, .managers-page .action-menu .item-danger i { color: #B42318; }
    .managers-page .action-menu .item-danger:hover { background: #FFF0EF; color: #B42318; }
    .managers-page .action-menu .dropdown-divider { margin: 5px 4px; border-color: #EDF0F5; }
    .managers-page .action-menu form { margin: 0; }

    /* Empty + footer */
    .managers-page .empty-state { padding: 55px 20px; text-align: center; }
    .managers-page .empty-icon {
        display: flex; align-items: center; justify-content: center; width: 62px; height: 62px;
        margin: 0 auto 17px; border-radius: 18px; background: var(--mgr-orange-soft);
        color: var(--mgr-orange); font-size: 25px;
    }
    .managers-page .empty-state h3 { color: var(--mgr-navy); font-size: 15px; font-weight: 800; }
    .managers-page .empty-state p { max-width: 370px; margin: 8px auto 18px; color: var(--mgr-muted); font-size: 12px; line-height: 1.7; }

    .managers-page .directory-footer {
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;
        gap: 12px; padding: 15px 20px; border-top: 1px solid var(--mgr-border); background: #FCFCFE;
    }
    .managers-page .pagination-summary { color: var(--mgr-muted); font-size: 11px; }
    .managers-page .pagination { margin: 0; gap: 4px; }
    .managers-page .pagination .page-link {
        min-width: 32px; min-height: 32px; display: flex; align-items: center; justify-content: center;
        border: 1px solid #E3E7EF; border-radius: 7px !important; color: var(--mgr-navy);
        font-size: 11px; box-shadow: none;
    }
    .managers-page .pagination .page-item.active .page-link { border-color: var(--mgr-orange); background: var(--mgr-orange); color: #fff; }
    .managers-page .pagination .page-item.disabled .page-link { color: #B5BBC8; background: #F8F9FC; }

    @media (max-width: 767.98px) {
        .managers-page .page-intro { align-items: stretch; }
        .managers-page .page-intro > div:last-child, .managers-page .page-intro .btn-mgr-primary { width: 100%; }
        .managers-page .mgr-stat-card { min-height: 104px; padding: 15px; }
        .managers-page .mgr-stat-number { font-size: 24px; }
        .managers-page .mgr-filter-card { padding: 15px; }
        .managers-page .directory-header { padding: 16px; }
        .managers-page .directory-footer { padding: 14px; }
        .managers-page .directory-footer .pagination { width: 100%; flex-wrap: wrap; }
    }
</style>
@endpush

@section('content')
<div class="managers-page">

    @php
        /*
         * Platform-wide figures come straight from the database, so they stay
         * correct across pagination and filters.
         */
        $statusCounts = \Illuminate\Support\Facades\DB::table('property_managers')
            ->whereNull('deleted_at')
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $allManagersCount = $statusCounts->sum();

        $assignedPropertiesTotal = \Illuminate\Support\Facades\DB::table('property_manager_assignments')
            ->where('status', 'active')
            ->distinct()
            ->count('property_id');

        // Active property assignments for the managers on this page (single query).
        $assignmentCounts = \Illuminate\Support\Facades\DB::table('property_manager_assignments')
            ->whereIn('property_manager_id', $managers->getCollection()->pluck('id')->all())
            ->where('status', 'active')
            ->selectRaw('property_manager_id, COUNT(*) as total')
            ->groupBy('property_manager_id')
            ->pluck('total', 'property_manager_id');

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
                <span>Property Managers</span>
            </div>

            <h1 class="page-title">Property Managers</h1>
            <p class="page-description">
                Manage manager accounts, employment details, property assignments and access status.
            </p>
        </div>

        <div class="d-flex align-items-start">
            <a href="{{ route('admin.managers.create') }}" class="btn-mgr-primary">
                <i class="bi bi-person-plus"></i> Add property manager
            </a>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <a href="{{ route('admin.managers.index') }}"
               class="mgr-stat-card {{ !$hasFilters ? 'is-active' : '' }}">
                <div class="mgr-stat-top">
                    <span class="mgr-stat-label">Total managers</span>
                    <span class="mgr-stat-icon stat-orange"><i class="bi bi-people"></i></span>
                </div>
                <div class="mgr-stat-number">{{ number_format($allManagersCount) }}</div>
                <div class="mgr-stat-footnote">All registered managers</div>
            </a>
        </div>

        <div class="col-6 col-xl-3">
            <a href="{{ route('admin.managers.index', ['status' => 'active']) }}"
               class="mgr-stat-card {{ $activeStatus === 'active' ? 'is-active' : '' }}">
                <div class="mgr-stat-top">
                    <span class="mgr-stat-label">Active managers</span>
                    <span class="mgr-stat-icon stat-green"><i class="bi bi-person-check"></i></span>
                </div>
                <div class="mgr-stat-number">{{ number_format($statusCounts['active'] ?? 0) }}</div>
                <div class="mgr-stat-footnote">Click to filter active managers</div>
            </a>
        </div>

        <div class="col-6 col-xl-3">
            <a href="{{ route('admin.managers.index', ['status' => 'inactive']) }}"
               class="mgr-stat-card {{ $activeStatus === 'inactive' ? 'is-active' : '' }}">
                <div class="mgr-stat-top">
                    <span class="mgr-stat-label">Inactive managers</span>
                    <span class="mgr-stat-icon stat-gray"><i class="bi bi-person-dash"></i></span>
                </div>
                <div class="mgr-stat-number">{{ number_format($statusCounts['inactive'] ?? 0) }}</div>
                <div class="mgr-stat-footnote">Click to filter inactive managers</div>
            </a>
        </div>

        <div class="col-6 col-xl-3">
            <div class="mgr-stat-card">
                <div class="mgr-stat-top">
                    <span class="mgr-stat-label">Managed properties</span>
                    <span class="mgr-stat-icon stat-navy"><i class="bi bi-buildings"></i></span>
                </div>
                <div class="mgr-stat-number">{{ number_format($assignedPropertiesTotal) }}</div>
                <div class="mgr-stat-footnote">Properties with an active manager</div>
            </div>
        </div>
    </div>

    {{-- Search and filters --}}
    <form method="GET" action="{{ route('admin.managers.index') }}" class="mgr-filter-card mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-6">
                <label for="managerSearch" class="form-label">Search managers</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input id="managerSearch" type="search" name="search" class="form-control"
                           value="{{ request('search') }}"
                           placeholder="Name, email, manager number or phone">
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label for="managerStatus" class="form-label">Account status</label>
                <select id="managerStatus" name="status" class="form-select">
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
                    <a href="{{ route('admin.managers.index') }}" class="btn-reset"
                       title="Reset filters" aria-label="Reset filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="status-pills">
            <a href="{{ route('admin.managers.index', array_filter(['search' => request('search')])) }}"
               class="status-pill {{ $activeStatus === '' ? 'active' : '' }}">
                All <span class="pill-count">{{ number_format($allManagersCount) }}</span>
            </a>
            <a href="{{ route('admin.managers.index', array_filter(['search' => request('search'), 'status' => 'active'])) }}"
               class="status-pill {{ $activeStatus === 'active' ? 'active' : '' }}">
                Active <span class="pill-count">{{ number_format($statusCounts['active'] ?? 0) }}</span>
            </a>
            <a href="{{ route('admin.managers.index', array_filter(['search' => request('search'), 'status' => 'inactive'])) }}"
               class="status-pill {{ $activeStatus === 'inactive' ? 'active' : '' }}">
                Inactive <span class="pill-count">{{ number_format($statusCounts['inactive'] ?? 0) }}</span>
            </a>

            @if($hasFilters)
                <span class="text-secondary ms-2" style="font-size:11px;">
                    @if(request()->filled('search')) Search: <strong>{{ request('search') }}</strong> @endif
                </span>
                <a href="{{ route('admin.managers.index') }}" class="text-decoration-none"
                   style="font-size:11px;color:var(--mgr-orange);">Clear all</a>
            @endif
        </div>
    </form>

    {{-- Manager directory --}}
    <section class="mgr-directory">

        <div class="directory-header">
            <div>
                <h2 class="directory-title">
                    <span class="directory-title-icon"><i class="bi bi-person-badge"></i></span>
                    Manager directory
                </h2>
                <p class="directory-subtitle">Manager profiles, employment information and account status</p>
            </div>

            <div class="directory-total">
                <i class="bi bi-people"></i>
                {{ number_format($managers->total()) }} {{ $managers->total() === 1 ? 'manager' : 'managers' }}
            </div>
        </div>

        <div class="mgr-table-wrap">
            <table class="mgr-table">
                <thead>
                    <tr>
                        <th>Manager</th>
                        <th>Contact information</th>
                        <th>Position</th>
                        <th>Properties</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($managers as $manager)
                        @php
                            $managerName = $manager->user?->name ?? 'User account unavailable';

                            $managerInitials = collect(preg_split('/\s+/', trim($managerName)))
                                ->filter()->take(2)
                                ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
                                ->implode('');

                            $managerStatus = strtolower($manager->status ?? 'inactive');
                            $statusClass = in_array($managerStatus, ['active', 'inactive'], true)
                                ? $managerStatus : 'unknown';

                            $employment = $manager->employment_type
                                ? ucwords(str_replace('_', ' ', $manager->employment_type))
                                : 'Not specified';

                            $assignedTotal = $assignmentCounts[$manager->id] ?? 0;
                        @endphp

                        <tr>
                            {{-- Manager --}}
                            <td>
                                <div class="mgr-profile">
                                    <div class="mgr-avatar">{{ $managerInitials ?: 'M' }}</div>

                                    <div>
                                        <a href="{{ route('admin.managers.show', $manager) }}" class="mgr-name">
                                            {{ $managerName }}
                                        </a>
                                        <div class="mgr-meta">Property manager</div>
                                        <span class="mgr-number">{{ $manager->manager_number ?: 'Not assigned' }}</span>
                                    </div>
                                </div>
                            </td>

                            {{-- Contact --}}
                            <td>
                                <div class="contact-line">
                                    <i class="bi bi-envelope"></i>
                                    <span>{{ $manager->user?->email ?? 'No email available' }}</span>
                                </div>
                                <div class="contact-line">
                                    <i class="bi bi-telephone"></i>
                                    <span>{{ $manager->phone ?: 'No phone recorded' }}</span>
                                </div>
                            </td>

                            {{-- Position --}}
                            <td>
                                <div class="job-title">{{ $manager->job_title ?: 'Property Manager' }}</div>
                                <span class="employment-chip">{{ $employment }}</span>
                            </td>

                            {{-- Properties --}}
                            <td>
                                <span class="property-count">
                                    <i class="bi bi-buildings"></i>
                                    {{ $assignedTotal }} {{ $assignedTotal === 1 ? 'property' : 'properties' }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td>
                                <span class="mgr-status {{ $statusClass }}">{{ ucfirst($managerStatus) }}</span>
                            </td>

                            {{-- Actions --}}
                            <td class="text-end">
                                <div class="dropdown">
                                    <button type="button" class="mgr-action-btn"
                                            data-bs-toggle="dropdown"
                                            data-bs-popper-config='{"strategy":"fixed"}'
                                            aria-expanded="false"
                                            aria-label="Actions for {{ $managerName }}">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end action-menu">
                                        <li>
                                            <a class="dropdown-item item-primary"
                                               href="{{ route('admin.managers.show', $manager) }}">
                                                <i class="bi bi-person-lines-fill"></i> View manager profile
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item"
                                               href="{{ route('admin.managers.edit', $manager) }}">
                                                <i class="bi bi-pencil-square"></i> Edit manager
                                            </a>
                                        </li>
                                        <li>
                                            <form method="POST"
                                                  action="{{ route('admin.managers.resend-credentials', $manager) }}"
                                                  onsubmit="return confirm('Generate a new password and email it to this manager?')">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="bi bi-envelope-arrow-up"></i> Resend credentials
                                                </button>
                                            </form>
                                        </li>

                                        @if($managerStatus === 'active')
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST"
                                                      action="{{ route('admin.managers.destroy', $manager) }}"
                                                      onsubmit="return confirm('Deactivate this manager account?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item item-danger">
                                                        <i class="bi bi-person-dash"></i> Deactivate manager
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="bi bi-people"></i></div>
                                    <h3>{{ $hasFilters ? 'No matching managers' : 'No managers registered yet' }}</h3>
                                    <p>
                                        @if($hasFilters)
                                            No manager accounts match your current search. Try changing your filters.
                                        @else
                                            Start by registering a property manager to help oversee rental operations.
                                        @endif
                                    </p>

                                    <div class="d-flex flex-wrap justify-content-center gap-2">
                                        @if($hasFilters)
                                            <a href="{{ route('admin.managers.index') }}"
                                               class="btn btn-outline-secondary btn-sm px-3 py-2">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Clear filters
                                            </a>
                                        @endif

                                        <a href="{{ route('admin.managers.create') }}" class="btn-mgr-primary">
                                            <i class="bi bi-person-plus"></i>
                                            {{ $hasFilters ? 'Add property manager' : 'Add first manager' }}
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
        @if($managers->hasPages())
            <div class="directory-footer">
                <div class="pagination-summary">
                    Showing
                    <strong>{{ $managers->firstItem() ?? 0 }}</strong> to
                    <strong>{{ $managers->lastItem() ?? 0 }}</strong> of
                    <strong>{{ $managers->total() }}</strong> managers
                </div>

                <div>{{ $managers->withQueryString()->links('pagination::bootstrap-5') }}</div>
            </div>
        @endif

    </section>
</div>
@endsection