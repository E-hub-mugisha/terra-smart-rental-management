@extends('layouts.app')

@section('title', 'Property Managers')
@section('page_heading', 'Property Managers')
@section('page_subtitle', 'Manage manager accounts, employment details and access status.')

@push('styles')
<style>
    .manager-page {
        --manager-orange: var(--terra-orange, #D05208);
        --manager-navy: var(--terra-navy, #19265d);
        --manager-muted: var(--terra-muted, #748094);
        --manager-border: var(--terra-border, #e7eaf0);
        color: var(--manager-navy);
    }

    .manager-hero {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 25px 28px;
        margin-bottom: 22px;
        border: 1px solid var(--manager-border);
        border-radius: 17px;
        background: linear-gradient(120deg, #fff 0%, #fff9f5 100%);
    }

    .manager-hero::after {
        content: "";
        position: absolute;
        top: -95px;
        right: -50px;
        width: 210px;
        height: 210px;
        border-radius: 50%;
        background: rgba(208, 82, 8, .055);
        pointer-events: none;
    }

    .manager-eyebrow {
        margin-bottom: 9px;
        color: var(--manager-orange);
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 1.4px;
        text-transform: uppercase;
    }

    .manager-page-title {
        margin: 0 0 8px;
        color: var(--manager-navy);
        font-size: clamp(23px, 3vw, 29px);
        font-weight: 800;
        letter-spacing: -.6px;
    }

    .manager-page-description {
        margin: 0;
        color: var(--manager-muted);
        font-size: 13px;
        line-height: 1.7;
    }

    .manager-hero-icon {
        z-index: 1;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        width: 65px;
        height: 65px;
        border-radius: 18px;
        background: #fff0e5;
        color: var(--manager-orange);
        font-size: 27px;
    }

    .manager-primary-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 43px;
        padding: 11px 17px;
        border: 1px solid var(--manager-orange);
        border-radius: 9px;
        background: var(--manager-orange);
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: all .2s ease;
    }

    .manager-primary-btn:hover {
        border-color: #b84607;
        background: #b84607;
        color: #fff;
        transform: translateY(-1px);
    }

    .manager-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 15px;
        margin-bottom: 22px;
    }

    .manager-stat-card {
        display: flex;
        align-items: center;
        gap: 15px;
        min-width: 0;
        padding: 21px;
        border: 1px solid var(--manager-border);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 3px 15px rgba(25, 38, 93, .025);
    }

    .manager-stat-icon {
        display: grid;
        place-items: center;
        flex-shrink: 0;
        width: 47px;
        height: 47px;
        border-radius: 13px;
        font-size: 20px;
    }

    .manager-stat-icon.orange {
        background: #fff0e5;
        color: #D05208;
    }

    .manager-stat-icon.green {
        background: #e9f7ef;
        color: #16845b;
    }

    .manager-stat-icon.navy {
        background: #edf0fa;
        color: #19265d;
    }

    .manager-stat-label {
        margin-bottom: 5px;
        color: var(--manager-muted);
        font-size: 11px;
    }

    .manager-stat-value {
        color: var(--manager-navy);
        font-size: 25px;
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: -.5px;
    }

    .manager-stat-caption {
        margin-top: 4px;
        color: var(--manager-muted);
        font-size: 10px;
    }

    .manager-panel {
        margin-bottom: 22px;
        overflow: visible;
        border: 1px solid var(--manager-border);
        border-radius: 15px;
        background: #fff;
        box-shadow: 0 3px 15px rgba(25, 38, 93, .025);
    }

    .manager-filter-body {
        padding: 21px;
    }

    .manager-form-label {
        display: block;
        margin-bottom: 8px;
        color: var(--manager-navy);
        font-size: 11px;
        font-weight: 700;
    }

    .manager-page .form-control,
    .manager-page .form-select,
    .manager-page .input-group-text {
        min-height: 43px;
        border-color: var(--manager-border);
        border-radius: 9px;
        color: var(--manager-navy);
        font-size: 12px;
        box-shadow: none;
    }

    .manager-page .input-group-text {
        display: flex;
        align-items: center;
        padding: 0 13px;
        background: #fff;
        color: var(--manager-muted);
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .manager-page .input-group .form-control {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    .manager-page .form-control:focus,
    .manager-page .form-select:focus {
        border-color: var(--manager-orange);
        box-shadow: 0 0 0 3px rgba(208, 82, 8, .08);
    }

    .manager-filter-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        width: 100%;
        min-height: 43px;
        padding: 10px 15px;
        border: 1px solid var(--manager-navy);
        border-radius: 9px;
        background: var(--manager-navy);
        color: #fff;
        font-size: 12px;
        font-weight: 700;
    }

    .manager-filter-btn:hover {
        background: #111c49;
        color: #fff;
    }

    .manager-reset-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 43px;
        height: 43px;
        border: 1px solid var(--manager-border);
        border-radius: 9px;
        background: #fff;
        color: var(--manager-muted);
        text-decoration: none;
    }

    .manager-reset-btn:hover {
        border-color: var(--manager-orange);
        color: var(--manager-orange);
    }

    .manager-directory {
        overflow: visible;
        border: 1px solid var(--manager-border);
        border-radius: 15px;
        background: #fff;
        box-shadow: 0 3px 15px rgba(25, 38, 93, .025);
    }

    .manager-directory-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
        padding: 21px 23px;
        border-bottom: 1px solid var(--manager-border);
    }

    .manager-directory-title {
        margin: 0 0 5px;
        color: var(--manager-navy);
        font-size: 15px;
        font-weight: 800;
    }

    .manager-directory-description {
        margin: 0;
        color: var(--manager-muted);
        font-size: 11px;
    }

    .manager-result-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 12px;
        border-radius: 30px;
        background: #fff0e5;
        color: var(--manager-orange);
        font-size: 11px;
        font-weight: 800;
    }

    .manager-table-wrap {
        overflow-x: auto;
        border-radius: 0 0 15px 15px;
    }

    .manager-table {
        width: 100%;
        min-width: 850px;
        margin: 0;
        vertical-align: middle;
    }

    .manager-table thead th {
        padding: 14px 16px;
        border-bottom: 1px solid var(--manager-border);
        background: #f8f9fc;
        color: #7a8497;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .45px;
        white-space: nowrap;
    }

    .manager-table thead th:first-child,
    .manager-table tbody td:first-child {
        padding-left: 23px;
    }

    .manager-table thead th:last-child,
    .manager-table tbody td:last-child {
        padding-right: 23px;
    }

    .manager-table tbody td {
        padding: 16px;
        border-bottom: 1px solid #f0f2f6;
        color: var(--manager-navy);
        font-size: 12px;
    }

    .manager-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .manager-table tbody tr {
        transition: background .15s ease;
    }

    .manager-table tbody tr:hover {
        background: #fcfcfe;
    }

    .manager-person {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 230px;
    }

    .manager-avatar {
        display: grid;
        place-items: center;
        flex-shrink: 0;
        width: 43px;
        height: 43px;
        border: 1px solid #e2e7f5;
        border-radius: 13px;
        background: #edf0fa;
        color: var(--manager-navy);
        font-size: 14px;
        font-weight: 800;
    }

    .manager-person-name {
        display: inline-block;
        max-width: 240px;
        overflow-wrap: anywhere;
        margin-bottom: 4px;
        color: var(--manager-navy);
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
    }

    .manager-person-name:hover {
        color: var(--manager-orange);
    }

    .manager-person-email {
        max-width: 240px;
        overflow-wrap: anywhere;
        margin-bottom: 4px;
        color: var(--manager-muted);
        font-size: 10px;
    }

    .manager-person-phone {
        color: var(--manager-muted);
        font-size: 10px;
    }

    .manager-number {
        display: inline-block;
        padding: 6px 8px;
        border: 1px solid var(--manager-border);
        border-radius: 7px;
        background: #fafbfc;
        color: var(--manager-navy);
        font-size: 10px;
        font-weight: 800;
        white-space: nowrap;
    }

    .manager-job-title {
        color: var(--manager-navy);
        font-size: 12px;
        font-weight: 700;
    }

    .manager-employment {
        color: var(--manager-muted);
        font-size: 11px;
    }

    .manager-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 10px;
        border-radius: 25px;
        font-size: 10px;
        font-weight: 800;
        white-space: nowrap;
    }

    .manager-status.active {
        background: #e9f7ef;
        color: #167544;
    }

    .manager-status.inactive {
        background: #eef0f3;
        color: #596273;
    }

    .manager-status.unknown {
        background: #fff5d9;
        color: #946200;
    }

    .manager-action-btn {
        display: inline-grid;
        place-items: center;
        width: 35px;
        height: 35px;
        border: 1px solid var(--manager-border);
        border-radius: 9px;
        background: #fff;
        color: var(--manager-navy);
    }

    .manager-action-btn:hover,
    .manager-action-btn[aria-expanded="true"] {
        border-color: #f0c9af;
        background: #fff7f0;
        color: var(--manager-orange);
    }

    .manager-directory .dropdown-menu {
        z-index: 1050;
        min-width: 185px;
        padding: 6px;
        border: 1px solid var(--manager-border);
        border-radius: 11px;
        box-shadow: 0 12px 32px rgba(25, 38, 93, .12);
    }

    .manager-directory .dropdown-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px;
        border-radius: 7px;
        font-size: 11px;
    }

    .manager-directory .dropdown-item:hover {
        background: #f7f8fb;
    }

    .manager-empty {
        padding: 55px 20px;
        text-align: center;
    }

    .manager-empty-icon {
        display: grid;
        place-items: center;
        width: 65px;
        height: 65px;
        margin: 0 auto 17px;
        border-radius: 19px;
        background: #fff0e5;
        color: var(--manager-orange);
        font-size: 26px;
    }

    .manager-empty-title {
        margin: 0 0 8px;
        color: var(--manager-navy);
        font-size: 15px;
        font-weight: 800;
    }

    .manager-empty-description {
        max-width: 370px;
        margin: 0 auto 18px;
        color: var(--manager-muted);
        font-size: 12px;
        line-height: 1.7;
    }

    .manager-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
        padding: 17px 23px;
        border-top: 1px solid var(--manager-border);
    }

    .manager-pagination-info {
        color: var(--manager-muted);
        font-size: 11px;
    }

    .manager-pagination .pagination {
        margin: 0;
        flex-wrap: wrap;
        gap: 4px;
    }

    .manager-pagination .page-link {
        border-color: var(--manager-border);
        border-radius: 7px;
        color: var(--manager-navy);
        font-size: 11px;
    }

    .manager-pagination .page-item.active .page-link {
        border-color: var(--manager-navy);
        background: var(--manager-navy);
        color: #fff;
    }

    @media (max-width: 991.98px) {
        .manager-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .manager-stats> :last-child {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 575.98px) {
        .manager-hero {
            align-items: flex-start;
            padding: 20px;
        }

        .manager-hero-icon {
            display: none;
        }

        .manager-hero-content {
            width: 100%;
        }

        .manager-hero-content .manager-primary-btn {
            width: 100%;
            margin-top: 17px;
        }

        .manager-stats {
            grid-template-columns: minmax(0, 1fr);
            gap: 10px;
        }

        .manager-stats> :last-child {
            grid-column: auto;
        }

        .manager-stat-card {
            padding: 17px;
        }

        .manager-filter-body {
            padding: 17px;
        }

        .manager-directory-header {
            padding: 18px;
        }

        .manager-pagination {
            padding: 16px;
        }
    }
</style>
@endpush

@section('content')
<div class="manager-page">

    @php
    $managerCollection = $managers->getCollection();

    $activeManagersOnPage = $managerCollection->filter(
    fn ($manager) => strtolower($manager->status ?? '') === 'active'
    )->count();

    $inactiveManagersOnPage = $managerCollection->filter(
    fn ($manager) => strtolower($manager->status ?? '') === 'inactive'
    )->count();

    $hasManagerFilters = request()->filled('search')
    || request()->filled('status');
    @endphp

    {{-- Page header --}}
    <section class="manager-hero">
        <div class="manager-hero-content">
            <div class="manager-eyebrow">Terra Smart Rental Management</div>

            <h1 class="manager-page-title">Property Managers</h1>

            <p class="manager-page-description">
                Manage manager accounts, employment details and access status
                from one central directory.
            </p>

            <a href="{{ route('admin.managers.create') }}"
                class="manager-primary-btn mt-3">
                <i class="bi bi-person-plus"></i>
                Add property manager
            </a>
        </div>

        <div class="manager-hero-icon" aria-hidden="true">
            <i class="bi bi-people"></i>
        </div>
    </section>

    {{-- Summary cards --}}
    <section class="manager-stats">
        <div class="manager-stat-card">
            <div class="manager-stat-icon orange">
                <i class="bi bi-people"></i>
            </div>

            <div>
                <div class="manager-stat-label">Total managers</div>
                <div class="manager-stat-value">
                    {{ number_format($managers->total()) }}
                </div>
                <div class="manager-stat-caption">Matching your current filters</div>
            </div>
        </div>

        <div class="manager-stat-card">
            <div class="manager-stat-icon green">
                <i class="bi bi-person-check"></i>
            </div>

            <div>
                <div class="manager-stat-label">Active on this page</div>
                <div class="manager-stat-value">
                    {{ number_format($activeManagersOnPage) }}
                </div>
                <div class="manager-stat-caption">Currently active manager records</div>
            </div>
        </div>

        <div class="manager-stat-card">
            <div class="manager-stat-icon navy">
                <i class="bi bi-person-dash"></i>
            </div>

            <div>
                <div class="manager-stat-label">Inactive on this page</div>
                <div class="manager-stat-value">
                    {{ number_format($inactiveManagersOnPage) }}
                </div>
                <div class="manager-stat-caption">Currently inactive manager records</div>
            </div>
        </div>
    </section>

    {{-- Search and filters --}}
    <section class="manager-panel">
        <div class="manager-filter-body">
            <form method="GET" action="{{ route('admin.managers.index') }}">
                <div class="row g-3 align-items-end">

                    <div class="col-12 col-lg-7">
                        <label for="manager-search" class="manager-form-label">
                            Search managers
                        </label>

                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>

                            <input
                                id="manager-search"
                                type="search"
                                name="search"
                                class="form-control"
                                value="{{ request('search') }}"
                                placeholder="Name, email, manager number or phone">
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <label for="manager-status" class="manager-form-label">
                            Account status
                        </label>

                        <select id="manager-status" name="status" class="form-select">
                            <option value="">All statuses</option>
                            <option value="active" @selected(request('status')==='active' )>
                                Active
                            </option>
                            <option value="inactive" @selected(request('status')==='inactive' )>
                                Inactive
                            </option>
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="manager-filter-btn">
                                <i class="bi bi-funnel"></i>
                                Apply filters
                            </button>

                            <a href="{{ route('admin.managers.index') }}"
                                class="manager-reset-btn"
                                title="Reset filters"
                                aria-label="Reset filters">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </section>

    {{-- Manager directory --}}
    <section class="manager-directory">

        <div class="manager-directory-header">
            <div>
                <h2 class="manager-directory-title">Manager directory</h2>
                <p class="manager-directory-description">
                    View manager profiles, employment information and account status.
                </p>
            </div>

            <span class="manager-result-badge">
                <i class="bi bi-list-ul"></i>
                {{ number_format($managers->total()) }}
                {{ $managers->total() === 1 ? 'result' : 'results' }}
            </span>
        </div>

        <div class="manager-table-wrap">
            <table class="table manager-table">
                <thead>
                    <tr>
                        <th>MANAGER</th>
                        <th>MANAGER NO.</th>
                        <th>POSITION</th>
                        <th>EMPLOYMENT</th>
                        <th>STATUS</th>
                        <th class="text-end">ACTIONS</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($managers as $manager)
                    @php
                    $managerName = $manager->user?->name
                    ?? 'User account unavailable';

                    $managerInitials = collect(
                    preg_split('/\s+/', trim($managerName))
                    )
                    ->filter()
                    ->take(2)
                    ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                    ->implode('');

                    $managerStatus = strtolower($manager->status ?? 'inactive');

                    $managerStatusClass = in_array(
                    $managerStatus,
                    ['active', 'inactive'],
                    true
                    ) ? $managerStatus : 'unknown';
                    @endphp

                    <tr>
                        {{-- Manager identity --}}
                        <td>
                            <div class="manager-person">
                                <div class="manager-avatar">
                                    {{ $managerInitials ?: 'M' }}
                                </div>

                                <div>
                                    <a href="{{ route('admin.managers.show', $manager) }}"
                                        class="manager-person-name">
                                        {{ $managerName }}
                                    </a>

                                    <div class="manager-person-email">
                                        {{ $manager->user?->email ?? 'No email available' }}
                                    </div>

                                    <div class="manager-person-phone">
                                        <i class="bi bi-telephone me-1"></i>
                                        {{ $manager->phone ?: 'No phone recorded' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Manager number --}}
                        <td>
                            <span class="manager-number">
                                {{ $manager->manager_number ?: 'Not assigned' }}
                            </span>
                        </td>

                        {{-- Position --}}
                        <td>
                            <span class="manager-job-title">
                                {{ $manager->job_title ?: 'Property Manager' }}
                            </span>
                        </td>

                        {{-- Employment type --}}
                        <td>
                            <span class="manager-employment">
                                {{ $manager->employment_type
                                        ? ucwords(str_replace('_', ' ', $manager->employment_type))
                                        : 'Not specified' }}
                            </span>
                        </td>

                        {{-- Account status --}}
                        <td>
                            <span class="manager-status {{ $managerStatusClass }}">
                                @if($managerStatus === 'active')
                                <i class="bi bi-check-circle"></i>
                                @elseif($managerStatus === 'inactive')
                                <i class="bi bi-pause-circle"></i>
                                @else
                                <i class="bi bi-question-circle"></i>
                                @endif

                                {{ ucfirst($managerStatus) }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td class="text-end">
                            <div class="dropdown">
                                <button
                                    class="manager-action-btn"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    data-bs-boundary="viewport"
                                    aria-expanded="false"
                                    aria-label="Actions for {{ $managerName }}">
                                    <i class="bi bi-three-dots"></i>
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item"
                                            href="{{ route('admin.managers.show', $manager) }}">
                                            <i class="bi bi-eye"></i>
                                            View profile
                                        </a>
                                    </li>

                                    <form method="POST" action="{{ route('admin.managers.resend-credentials', $manager) }}"
                                        onsubmit="return confirm('Generate a new password and email it to this manager?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-secondary">Resend credentials</button>
                                    </form>

                                    <li>
                                        <a class="dropdown-item"
                                            href="{{ route('admin.managers.edit', $manager) }}">
                                            <i class="bi bi-pencil-square"></i>
                                            Edit manager
                                        </a>
                                    </li>

                                    @if($managerStatus === 'active')
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>

                                    <li>
                                        <form
                                            method="POST"
                                            action="{{ route('admin.managers.destroy', $manager) }}"
                                            onsubmit="return confirm('Deactivate this manager account?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                class="dropdown-item text-danger">
                                                <i class="bi bi-person-dash"></i>
                                                Deactivate manager
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
                            <div class="manager-empty">
                                <div class="manager-empty-icon">
                                    <i class="bi bi-people"></i>
                                </div>

                                <h3 class="manager-empty-title">
                                    {{ $hasManagerFilters
                                            ? 'No matching managers'
                                            : 'No managers registered yet' }}
                                </h3>

                                <p class="manager-empty-description">
                                    @if($hasManagerFilters)
                                    No manager accounts match your current
                                    search criteria. Try changing your filters.
                                    @else
                                    Start by registering a property manager
                                    to help oversee rental operations.
                                    @endif
                                </p>

                                @if($hasManagerFilters)
                                <a href="{{ route('admin.managers.index') }}"
                                    class="manager-primary-btn">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                    Clear filters
                                </a>
                                @else
                                <a href="{{ route('admin.managers.create') }}"
                                    class="manager-primary-btn">
                                    <i class="bi bi-person-plus"></i>
                                    Add first manager
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
        @if($managers->hasPages())
        <div class="manager-pagination">
            <div class="manager-pagination-info">
                Showing
                <strong>{{ $managers->firstItem() }}</strong>–<strong>{{ $managers->lastItem() }}</strong>
                of <strong>{{ number_format($managers->total()) }}</strong> managers
            </div>

            <div>
                {{ $managers->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif

    </section>
</div>
@endsection