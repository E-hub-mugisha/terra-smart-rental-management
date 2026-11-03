
@extends('layouts.app')

@section('title', 'Properties')
@section('page_heading', 'Property Directory')
@section('page_subtitle', 'Manage property records, ownership, location and assigned managers.')

@section('content')
@php
    $pillMap = [
        'active' => ['class' => 'success', 'icon' => 'bi-check-circle-fill'],
        'inactive' => ['class' => 'muted', 'icon' => 'bi-pause-circle-fill'],
        'under_maintenance' => ['class' => 'warning', 'icon' => 'bi-tools'],
    ];

    $pageItems = $properties->getCollection();

    $activeCount = $pageItems->where('status', 'active')->count();
    $maintCount = $pageItems->where('status', 'under_maintenance')->count();
    $inactCount = $pageItems->where('status', 'inactive')->count();

    $hasFilters = request()->filled('search') || request()->filled('status');
@endphp

<style>
    .terra-properties {
        --prop-orange: #D05208;
        --prop-navy: #19265d;
        --prop-text: #273249;
        --prop-muted: #758095;
        --prop-border: #e6eaf1;
        --prop-soft: #f7f8fc;
        color: var(--prop-text);
    }

    .terra-properties * {
        box-sizing: border-box;
    }

    .terra-properties .prop-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--prop-muted);
        font-size: 12px;
        font-weight: 650;
        text-decoration: none;
        margin-bottom: 19px;
    }

    .terra-properties .prop-back:hover {
        color: var(--prop-orange);
    }

    .terra-properties .prop-heading {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 27px;
    }

    .terra-properties .prop-eyebrow {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 9px;
        color: var(--prop-orange);
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .13em;
        text-transform: uppercase;
    }

    .terra-properties .prop-eyebrow::before {
        content: "";
        width: 21px;
        height: 2px;
        border-radius: 5px;
        background: var(--prop-orange);
    }

    .terra-properties .prop-title {
        color: var(--prop-navy);
        font-size: clamp(24px, 3vw, 32px);
        font-weight: 800;
        letter-spacing: -.8px;
        margin: 0 0 8px;
    }

    .terra-properties .prop-subtitle {
        color: var(--prop-muted);
        font-size: 13px;
        line-height: 1.7;
        max-width: 650px;
        margin: 0;
    }

    .terra-properties .prop-btn-primary {
        min-height: 43px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 11px 17px;
        border: 1px solid var(--prop-orange);
        border-radius: 10px;
        background: var(--prop-orange);
        color: #fff;
        text-decoration: none;
        font-size: 12px;
        font-weight: 750;
        white-space: nowrap;
        transition: .2s ease;
    }

    .terra-properties .prop-btn-primary:hover {
        background: #b94707;
        border-color: #b94707;
        color: #fff;
        transform: translateY(-1px);
    }

    .terra-properties .prop-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 15px;
        margin-bottom: 23px;
    }

    .terra-properties .prop-stat {
        min-width: 0;
        position: relative;
        overflow: hidden;
        background: #fff;
        border: 1px solid var(--prop-border);
        border-radius: 15px;
        padding: 19px;
        box-shadow: 0 4px 20px rgba(25, 38, 93, .025);
    }

    .terra-properties .prop-stat::after {
        content: "";
        position: absolute;
        right: -23px;
        bottom: -36px;
        width: 85px;
        height: 85px;
        border-radius: 50%;
        background: var(--stat-tint, #fff0e7);
        opacity: .65;
        pointer-events: none;
    }

    .terra-properties .prop-stat-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
        position: relative;
        z-index: 1;
    }

    .terra-properties .prop-stat-label {
        color: var(--prop-muted);
        font-size: 11px;
        font-weight: 650;
        line-height: 1.5;
    }

    .terra-properties .prop-stat-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 39px;
        height: 39px;
        flex: 0 0 39px;
        border-radius: 12px;
        background: var(--stat-tint, #fff0e7);
        color: var(--stat-color, var(--prop-orange));
        font-size: 18px;
    }

    .terra-properties .prop-stat-value {
        color: var(--prop-navy);
        font-size: 29px;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -.7px;
        margin-bottom: 10px;
        position: relative;
        z-index: 1;
    }

    .terra-properties .prop-stat-foot {
        color: var(--prop-muted);
        font-size: 10px;
        line-height: 1.5;
        position: relative;
        z-index: 1;
    }

    .terra-properties .prop-panel {
        background: #fff;
        border: 1px solid var(--prop-border);
        border-radius: 16px;
        box-shadow: 0 5px 22px rgba(25, 38, 93, .025);
        overflow: hidden;
        margin-bottom: 23px;
    }

    .terra-properties .prop-panel-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 20px 23px;
        border-bottom: 1px solid var(--prop-border);
    }

    .terra-properties .prop-panel-title {
        color: var(--prop-navy);
        font-size: 14px;
        font-weight: 800;
        margin: 0 0 5px;
    }

    .terra-properties .prop-panel-subtitle {
        color: var(--prop-muted);
        font-size: 11px;
        line-height: 1.6;
        margin: 0;
    }

    .terra-properties .prop-panel-body {
        padding: 21px 23px;
    }

    .terra-properties .prop-filter-label {
        display: block;
        color: #374258;
        font-size: 11px;
        font-weight: 750;
        margin-bottom: 8px;
    }

    .terra-properties .prop-control {
        width: 100%;
        min-height: 44px;
        border: 1px solid #dfe4ed;
        border-radius: 10px;
        background: #fff;
        color: var(--prop-text);
        padding: 10px 12px;
        font-size: 12px;
        box-shadow: none;
    }

    .terra-properties .prop-control:focus {
        border-color: var(--prop-orange);
        box-shadow: 0 0 0 3px rgba(208, 82, 8, .09);
        outline: none;
    }

    .terra-properties .prop-search-wrap {
        position: relative;
    }

    .terra-properties .prop-search-wrap > i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #929bad;
        font-size: 14px;
        pointer-events: none;
    }

    .terra-properties .prop-search-wrap input {
        padding-left: 39px;
    }

    .terra-properties .prop-filter-btn {
        min-height: 44px;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        border: 1px solid var(--prop-orange);
        border-radius: 10px;
        padding: 10px 15px;
        background: var(--prop-orange);
        color: #fff;
        font-size: 12px;
        font-weight: 750;
        white-space: nowrap;
    }

    .terra-properties .prop-filter-btn:hover {
        background: #b94707;
        color: #fff;
    }

    .terra-properties .prop-reset-btn {
        min-height: 44px;
        width: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--prop-border);
        border-radius: 10px;
        background: #fff;
        color: var(--prop-muted);
        font-size: 15px;
        text-decoration: none;
    }

    .terra-properties .prop-reset-btn:hover {
        color: var(--prop-orange);
        background: #fff7f1;
    }

    .terra-properties .prop-filter-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        margin-top: 16px;
    }

    .terra-properties .prop-filter-tag {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        border: 1px solid #f0d4c3;
        border-radius: 30px;
        padding: 6px 10px;
        background: #fff8f3;
        color: #9d470d;
        font-size: 10px;
        font-weight: 700;
    }

    .terra-properties .prop-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .terra-properties .prop-table {
        width: 100%;
        min-width: 920px;
        border-collapse: separate;
        border-spacing: 0;
        margin: 0;
    }

    .terra-properties .prop-table thead th {
        background: #f8f9fc;
        border-bottom: 1px solid var(--prop-border);
        padding: 13px 15px;
        color: #7b8598;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .075em;
        white-space: nowrap;
    }

    .terra-properties .prop-table tbody td {
        padding: 16px 15px;
        border-bottom: 1px solid #edf0f5;
        vertical-align: middle;
        color: #39445a;
        font-size: 11px;
    }

    .terra-properties .prop-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .terra-properties .prop-table tbody tr {
        transition: background .15s ease;
    }

    .terra-properties .prop-table tbody tr:hover {
        background: #fcfcfe;
    }

    .terra-properties .prop-photo {
        width: 70px;
        height: 62px;
        flex: 0 0 70px;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f0f2f8;
        border: 1px solid #e9ecf3;
        border-radius: 11px;
        text-decoration: none;
    }

    .terra-properties .prop-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform .25s ease;
    }

    .terra-properties .prop-table tbody tr:hover .prop-photo img {
        transform: scale(1.05);
    }

    .terra-properties .prop-photo-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        color: #9aa3b6;
        font-size: 23px;
    }

    .terra-properties .prop-photo-count {
        position: absolute;
        bottom: 4px;
        right: 4px;
        padding: 3px 5px;
        border-radius: 5px;
        background: rgba(25, 38, 93, .87);
        color: #fff;
        font-size: 9px;
        font-weight: 750;
    }

    .terra-properties .prop-name {
        display: inline-block;
        color: var(--prop-navy);
        font-size: 12px;
        font-weight: 800;
        line-height: 1.5;
        text-decoration: none;
        overflow-wrap: anywhere;
        margin-bottom: 4px;
    }

    .terra-properties .prop-name:hover {
        color: var(--prop-orange);
    }

    .terra-properties .prop-sub {
        color: var(--prop-muted);
        font-size: 10px;
        line-height: 1.6;
        overflow-wrap: anywhere;
    }

    .terra-properties .prop-owner {
        display: flex;
        align-items: center;
        gap: 9px;
        min-width: 135px;
    }

    .terra-properties .prop-owner-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        flex: 0 0 32px;
        border-radius: 10px;
        background: #edf0fa;
        color: var(--prop-navy);
        font-size: 14px;
    }

    .terra-properties .prop-strong {
        color: #354057;
        font-weight: 750;
        font-size: 11px;
        line-height: 1.6;
        overflow-wrap: anywhere;
    }

    .terra-properties .prop-location {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        min-width: 120px;
    }

    .terra-properties .prop-location > i {
        color: var(--prop-orange);
        margin-top: 2px;
    }

    .terra-properties .prop-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 6px 9px;
        border-radius: 7px;
        font-size: 10px;
        line-height: 1.3;
        font-weight: 750;
        white-space: nowrap;
    }

    .terra-properties .prop-pill.success {
        color: #167548;
        background: #e8f7ee;
    }

    .terra-properties .prop-pill.warning {
        color: #97600c;
        background: #fff4df;
    }

    .terra-properties .prop-pill.muted {
        color: #687386;
        background: #f0f2f5;
    }

    .terra-properties .prop-pill.navy {
        color: var(--prop-navy);
        background: #edf0fa;
    }

    .terra-properties .prop-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .terra-properties .prop-action-btn {
        min-width: 33px;
        height: 33px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 0 9px;
        border: 1px solid var(--prop-border);
        border-radius: 8px;
        background: #fff;
        color: #657087;
        text-decoration: none;
        font-size: 12px;
        transition: .15s ease;
    }

    .terra-properties .prop-action-btn:hover {
        border-color: #d5dbea;
        background: #f5f7fb;
        color: var(--prop-navy);
    }

    .terra-properties .prop-action-btn.danger {
        color: #c33f48;
    }

    .terra-properties .prop-action-btn.danger:hover {
        background: #fff1f1;
        border-color: #f0c8ca;
    }

    .terra-properties .prop-directory-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
        padding: 17px 22px;
        border-top: 1px solid var(--prop-border);
    }

    .terra-properties .prop-pagination-text {
        color: var(--prop-muted);
        font-size: 11px;
    }

    .terra-properties .pagination {
        margin-bottom: 0;
        gap: 4px;
    }

    .terra-properties .pagination .page-link {
        border-radius: 7px;
        border-color: var(--prop-border);
        color: var(--prop-navy);
        font-size: 11px;
        box-shadow: none;
    }

    .terra-properties .pagination .page-item.active .page-link {
        background: var(--prop-orange);
        border-color: var(--prop-orange);
        color: #fff;
    }

    .terra-properties .prop-empty {
        padding: 55px 20px;
        text-align: center;
    }

    .terra-properties .prop-empty-icon {
        width: 63px;
        height: 63px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 17px;
        border-radius: 18px;
        background: #fff0e7;
        color: var(--prop-orange);
        font-size: 26px;
    }

    .terra-properties .prop-empty-title {
        color: var(--prop-navy);
        font-size: 15px;
        font-weight: 800;
        margin-bottom: 7px;
    }

    .terra-properties .prop-empty-text {
        max-width: 380px;
        margin: 0 auto 20px;
        color: var(--prop-muted);
        font-size: 12px;
        line-height: 1.7;
    }

    .terra-properties .prop-muted {
        color: var(--prop-muted);
    }

    @media (max-width: 1199.98px) {
        .terra-properties .prop-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .terra-properties .prop-heading {
            flex-direction: column;
            align-items: stretch;
        }

        .terra-properties .prop-heading .prop-btn-primary {
            align-self: flex-start;
        }

        .terra-properties .prop-panel-heading,
        .terra-properties .prop-panel-body {
            padding: 17px;
        }

        .terra-properties .prop-stat {
            padding: 15px;
        }

        .terra-properties .prop-stat-value {
            font-size: 25px;
        }

        .terra-properties .prop-stat-top {
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .terra-properties .prop-stat-icon {
            width: 34px;
            height: 34px;
            flex-basis: 34px;
            border-radius: 10px;
            font-size: 15px;
        }

        .terra-properties .prop-directory-footer {
            padding: 15px;
        }
    }

    @media (max-width: 420px) {
        .terra-properties .prop-stats {
            gap: 9px;
        }

        .terra-properties .prop-stat {
            padding: 13px 11px;
        }

        .terra-properties .prop-stat-label {
            font-size: 10px;
        }

        .terra-properties .prop-stat-value {
            font-size: 23px;
        }

        .terra-properties .prop-stat-foot {
            font-size: 9px;
        }
    }
</style>

<div class="terra-properties">

    {{-- Page heading --}}
    <div class="prop-heading">
        <div>
            <div class="prop-eyebrow">Property administration</div>
            <h1 class="prop-title">Property directory</h1>
            <p class="prop-subtitle">
                Manage your property portfolio, review ownership and location details,
                and keep track of assigned managers.
            </p>
        </div>

        <a href="{{ route('admin.properties.create') }}" class="prop-btn-primary">
            <i class="bi bi-plus-lg"></i>
            Add property
        </a>
    </div>

    {{-- Summary cards --}}
    <div class="prop-stats">
        <div class="prop-stat" style="--stat-tint:#fff0e7;--stat-color:#D05208;">
            <div class="prop-stat-top">
                <span class="prop-stat-label">Matching properties</span>
                <span class="prop-stat-icon">
                    <i class="bi bi-buildings"></i>
                </span>
            </div>
            <div class="prop-stat-value">{{ number_format($properties->total()) }}</div>
            <div class="prop-stat-foot">
                {{ $hasFilters ? 'Results matching your filters' : 'All registered properties' }}
            </div>
        </div>

        <div class="prop-stat" style="--stat-tint:#e8f7ee;--stat-color:#16804b;">
            <div class="prop-stat-top">
                <span class="prop-stat-label">Active</span>
                <span class="prop-stat-icon">
                    <i class="bi bi-check-circle"></i>
                </span>
            </div>
            <div class="prop-stat-value">{{ number_format($activeCount) }}</div>
            <div class="prop-stat-foot">On the current page</div>
        </div>

        <div class="prop-stat" style="--stat-tint:#edf0fa;--stat-color:#19265d;">
            <div class="prop-stat-top">
                <span class="prop-stat-label">Under maintenance</span>
                <span class="prop-stat-icon">
                    <i class="bi bi-tools"></i>
                </span>
            </div>
            <div class="prop-stat-value">{{ number_format($maintCount) }}</div>
            <div class="prop-stat-foot">On the current page</div>
        </div>

        <div class="prop-stat" style="--stat-tint:#f0f2f5;--stat-color:#687386;">
            <div class="prop-stat-top">
                <span class="prop-stat-label">Inactive</span>
                <span class="prop-stat-icon">
                    <i class="bi bi-building-slash"></i>
                </span>
            </div>
            <div class="prop-stat-value">{{ number_format($inactCount) }}</div>
            <div class="prop-stat-foot">On the current page</div>
        </div>
    </div>

    {{-- Search and filters --}}
    <section class="prop-panel">
        <div class="prop-panel-heading">
            <div>
                <h2 class="prop-panel-title">Find a property</h2>
                <p class="prop-panel-subtitle">
                    Search your directory or narrow results by property status.
                </p>
            </div>
            <i class="bi bi-sliders2" style="font-size:20px;color:#929bad;"></i>
        </div>

        <div class="prop-panel-body">
            <form method="GET" action="{{ route('admin.properties.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-6">
                        <label for="property-search" class="prop-filter-label">
                            Search by keyword
                        </label>
                        <div class="prop-search-wrap">
                            <i class="bi bi-search"></i>
                            <input
                                type="search"
                                name="search"
                                id="property-search"
                                class="prop-control"
                                value="{{ request('search') }}"
                                placeholder="Property name, code, owner or district"
                            >
                        </div>
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <label for="property-status" class="prop-filter-label">
                            Property status
                        </label>
                        <select name="status" id="property-status" class="prop-control">
                            <option value="">All statuses</option>
                            <option value="active" @selected(request('status') === 'active')>
                                Active
                            </option>
                            <option value="inactive" @selected(request('status') === 'inactive')>
                                Inactive
                            </option>
                            <option value="under_maintenance" @selected(request('status') === 'under_maintenance')>
                                Under maintenance
                            </option>
                        </select>
                    </div>

                    <div class="col-sm-6 col-lg-3 d-flex gap-2">
                        <button type="submit" class="prop-filter-btn flex-grow-1">
                            <i class="bi bi-funnel"></i>
                            Apply filters
                        </button>

                        @if($hasFilters)
                            <a
                                href="{{ route('admin.properties.index') }}"
                                class="prop-reset-btn"
                                title="Reset filters"
                                aria-label="Reset filters"
                            >
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        @endif
                    </div>
                </div>

                @if($hasFilters)
                    <div class="prop-filter-tags">
                        @if(request()->filled('search'))
                            <span class="prop-filter-tag">
                                <i class="bi bi-search"></i>
                                Search: {{ request('search') }}
                            </span>
                        @endif

                        @if(request()->filled('status'))
                            <span class="prop-filter-tag">
                                <i class="bi bi-circle-half"></i>
                                Status: {{ ucwords(str_replace('_', ' ', request('status'))) }}
                            </span>
                        @endif
                    </div>
                @endif
            </form>
        </div>
    </section>

    {{-- Property directory --}}
    <section class="prop-panel">
        <div class="prop-panel-heading">
            <div>
                <h2 class="prop-panel-title">Property directory</h2>
                <p class="prop-panel-subtitle">
                    Property images, owners, location and management status.
                </p>
            </div>

            <span class="prop-pill navy">
                <i class="bi bi-buildings"></i>
                {{ number_format($properties->total()) }}
                {{ \Illuminate\Support\Str::plural('property', $properties->total()) }}
            </span>
        </div>

        <div class="prop-table-wrap">
            <table class="prop-table">
                <thead>
                    <tr>
                        <th style="padding-left:22px;">Property</th>
                        <th>Owner</th>
                        <th>Location</th>
                        <th>Managers</th>
                        <th>Status</th>
                        <th style="text-align:right;padding-right:22px;">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($properties as $property)
                        @php
                            $status = $property->status ?? 'inactive';
                            $statusStyle = $pillMap[$status] ?? $pillMap['inactive'];

                            $managerCount = $property->activeManagers->count();

                            $image = $property->images->firstWhere('is_primary', true)
                                ?? $property->images->first();

                            $imageCount = $property->images->count();
                        @endphp

                        <tr>
                            {{-- Property information --}}
                            <td style="padding-left:22px;">
                                <div class="d-flex align-items-center gap-3">
                                    <a
                                        href="{{ route('admin.properties.show', $property) }}"
                                        class="prop-photo"
                                        tabindex="-1"
                                        aria-hidden="true"
                                    >
                                        @if($image)
                                            <img
                                                src="{{ asset('storage/' . $image->image_path) }}"
                                                alt="{{ $image->caption ?? $property->name }}"
                                                loading="lazy"
                                            >

                                            @if($imageCount > 1)
                                                <span class="prop-photo-count">
                                                    <i class="bi bi-images"></i>
                                                    {{ $imageCount }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="prop-photo-empty">
                                                <i class="bi bi-building"></i>
                                            </span>
                                        @endif
                                    </a>

                                    <div class="min-w-0">
                                        <a
                                            href="{{ route('admin.properties.show', $property) }}"
                                            class="prop-name"
                                        >
                                            {{ $property->name ?: 'Unnamed property' }}
                                        </a>

                                        <div class="prop-sub">
                                            <i class="bi bi-hash"></i>
                                            {{ $property->property_code ?: 'No property code' }}
                                        </div>

                                        <div class="prop-sub">
                                            {{ ucwords(str_replace('_', ' ', $property->property_type ?? 'Property')) }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Owner --}}
                            <td>
                                <div class="prop-owner">
                                    <span class="prop-owner-icon">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <span class="prop-strong">
                                        {{ $property->owner?->name ?? 'Owner unavailable' }}
                                    </span>
                                </div>
                            </td>

                            {{-- Location --}}
                            <td>
                                <div class="prop-location">
                                    <i class="bi bi-geo-alt"></i>
                                    <div>
                                        <div class="prop-strong">
                                            {{ $property->district ?: 'District not specified' }}
                                        </div>
                                        <div class="prop-sub">
                                            {{ $property->province ?: 'Province not specified' }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Assigned managers --}}
                            <td>
                                <span class="prop-pill navy">
                                    <i class="bi bi-people"></i>
                                    {{ $managerCount }}
                                    {{ $managerCount === 1 ? 'manager' : 'managers' }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td>
                                <span class="prop-pill {{ $statusStyle['class'] }}">
                                    <i class="bi {{ $statusStyle['icon'] }}"></i>
                                    {{ ucwords(str_replace('_', ' ', $status)) }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td style="padding-right:22px;">
                                <div class="prop-actions">
                                    <a
                                        href="{{ route('admin.properties.show', $property) }}"
                                        class="prop-action-btn"
                                        title="View property"
                                        aria-label="View property"
                                    >
                                        <i class="bi bi-eye"></i>
                                        <span>View</span>
                                    </a>

                                    <a
                                        href="{{ route('admin.properties.edit', $property) }}"
                                        class="prop-action-btn"
                                        title="Edit property"
                                        aria-label="Edit property"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    @if($status !== 'inactive')
                                        <form
                                            method="POST"
                                            action="{{ route('admin.properties.destroy', $property) }}"
                                            onsubmit="return confirm('Deactivate this property? Its records will be preserved.')"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="prop-action-btn danger"
                                                title="Deactivate property"
                                                aria-label="Deactivate property"
                                            >
                                                <i class="bi bi-pause-circle"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:0;">
                                <div class="prop-empty">
                                    <div class="prop-empty-icon">
                                        <i class="bi bi-buildings"></i>
                                    </div>

                                    <h3 class="prop-empty-title">
                                        {{ $hasFilters ? 'No matching properties' : 'Your property directory is empty' }}
                                    </h3>

                                    <p class="prop-empty-text">
                                        {{ $hasFilters
                                            ? 'No properties match the selected criteria. Try another keyword or clear the filters.'
                                            : 'Start building your portfolio by adding your first property.' }}
                                    </p>

                                    @if($hasFilters)
                                        <a
                                            href="{{ route('admin.properties.index') }}"
                                            class="prop-btn-primary"
                                        >
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                            Clear filters
                                        </a>
                                    @else
                                        <a
                                            href="{{ route('admin.properties.create') }}"
                                            class="prop-btn-primary"
                                        >
                                            <i class="bi bi-plus-lg"></i>
                                            Add your first property
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
        @if($properties->hasPages() || $properties->total() > 0)
            <div class="prop-directory-footer">
                <div class="prop-pagination-text">
                    @if($properties->total() > 0)
                        Showing
                        <strong>{{ $properties->firstItem() }}</strong>
                        to
                        <strong>{{ $properties->lastItem() }}</strong>
                        of
                        <strong>{{ number_format($properties->total()) }}</strong>
                        properties
                    @else
                        No properties to display
                    @endif
                </div>

                @if($properties->hasPages())
                    <div>
                        {{ $properties->withQueryString()->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        @endif
    </section>

</div>
@endsection
