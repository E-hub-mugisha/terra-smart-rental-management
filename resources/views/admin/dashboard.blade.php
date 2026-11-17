@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('page_title', 'Admin Dashboard')
@section('page_subtitle', 'Monitor and manage your Terra Smart Rental Management platform.')

@php
    $adminCards = [
        [
            'label' => 'Total Users',
            'value' => $stats['users'],
            'icon' => 'bi-people',
            'color' => 'navy',
            'route' => 'admin.users.index',
            'description' => 'Registered platform users',
        ],
        [
            'label' => 'Property Owners',
            'value' => $stats['owners'],
            'icon' => 'bi-person-badge',
            'color' => 'orange',
            'route' => 'admin.owners.index',
            'description' => 'Registered property owners',
        ],
        [
            'label' => 'Property Managers',
            'value' => $stats['managers'],
            'icon' => 'bi-person-gear',
            'color' => 'blue',
            'route' => 'admin.managers.index',
            'description' => 'Registered property managers',
        ],
        [
            'label' => 'Tenants',
            'value' => $stats['tenants'],
            'icon' => 'bi-person-vcard',
            'color' => 'purple',
            'route' => 'admin.tenants.index',
            'description' => 'Registered tenants',
        ],
        [
            'label' => 'Properties',
            'value' => $stats['properties'],
            'icon' => 'bi-buildings',
            'color' => 'green',
            'route' => 'admin.properties.index',
            'description' => 'Managed properties',
        ],
        [
            'label' => 'Rental Units',
            'value' => $stats['units'],
            'icon' => 'bi-door-open',
            'color' => 'cyan',
            'route' => null,
            'description' => 'Units across properties',
        ],
    ];

    $quickLinks = [
        [
            'label' => 'Manage Users',
            'description' => 'Manage platform accounts and roles',
            'icon' => 'bi-people',
            'route' => 'admin.users.index',
        ],
        [
            'label' => 'Property Owners',
            'description' => 'View and manage property owners',
            'icon' => 'bi-person-badge',
            'route' => 'admin.owners.index',
        ],
        [
            'label' => 'Manage Properties',
            'description' => 'Review and manage properties',
            'icon' => 'bi-buildings',
            'route' => 'admin.properties.index',
        ],
        [
            'label' => 'Tenant Applications',
            'description' => 'Review rental applications',
            'icon' => 'bi-file-earmark-person',
            'route' => 'admin.tenant-applications.index',
        ],
        [
            'label' => 'Rent Payments',
            'description' => 'Monitor rent payment records',
            'icon' => 'bi-cash-stack',
            'route' => 'admin.payments.index',
        ],
        [
            'label' => 'Financial Reports',
            'description' => 'View platform financial reports',
            'icon' => 'bi-graph-up-arrow',
            'route' => 'admin.financial-reports.index',
        ],
    ];

    $formatMoney = fn ($amount, $currency = 'RWF') =>
        $currency . ' ' . number_format((float) $amount, 0);

    $statusClass = function ($status) {
        return match (strtolower((string) $status)) {
            'active', 'approved', 'paid', 'completed', 'resolved' => 'success',
            'pending', 'pending_approval', 'scheduled' => 'warning',
            'rejected', 'overdue', 'cancelled', 'inactive', 'closed' => 'danger',
            'in_progress', 'partially_paid' => 'info',
            default => 'secondary',
        };
    };
@endphp

@push('styles')
<style>
    .admin-welcome {
        position: relative;
        overflow: hidden;
        padding: 28px 30px;
        border-radius: 17px;
        background: linear-gradient(115deg, #19265d, #263a83);
        color: #fff;
    }

    .admin-welcome::after {
        content: "";
        position: absolute;
        width: 250px;
        height: 250px;
        right: 8%;
        top: -155px;
        border: 35px solid rgba(255,255,255,.07);
        border-radius: 50%;
    }

    .admin-welcome h2 {
        font-size: 23px;
        font-weight: 800;
        margin-bottom: 8px;
    }

    .admin-welcome p {
        max-width: 570px;
        margin-bottom: 0;
        color: rgba(255,255,255,.75);
        font-size: 13px;
    }

    .welcome-date {
        color: rgba(255,255,255,.75);
        font-size: 12px;
    }

    .admin-stat-card {
        height: 100%;
        padding: 19px;
        background: #fff;
        border: 1px solid #e9edf4;
        border-radius: 14px;
        box-shadow: 0 5px 20px rgba(25,38,93,.035);
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .admin-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(25,38,93,.08);
    }

    .stat-icon {
        width: 43px;
        height: 43px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 19px;
    }

    .stat-icon.orange { background: #fff0e6; color: #d05208; }
    .stat-icon.navy { background: #edf0fb; color: #19265d; }
    .stat-icon.blue { background: #eaf3ff; color: #2872c7; }
    .stat-icon.purple { background: #f3edff; color: #7853bd; }
    .stat-icon.green { background: #e9f8f0; color: #23935b; }
    .stat-icon.cyan { background: #e8f8fb; color: #168ca3; }

    .stat-label {
        color: #737d91;
        font-size: 12px;
        font-weight: 600;
    }

    .stat-value {
        margin-top: 8px;
        color: #19265d;
        font-size: 25px;
        font-weight: 800;
        line-height: 1.2;
    }

    .stat-description {
        margin-top: 7px;
        color: #98a1b2;
        font-size: 11px;
    }

    .dashboard-section-title {
        color: #19265d;
        font-size: 15px;
        font-weight: 800;
        margin-bottom: 4px;
    }

    .dashboard-section-subtitle {
        color: #8791a4;
        font-size: 12px;
    }

    .dashboard-card {
        height: 100%;
        padding: 20px;
        background: #fff;
        border: 1px solid #e9edf4;
        border-radius: 14px;
        box-shadow: 0 5px 20px rgba(25,38,93,.035);
    }

    .chart-container {
        position: relative;
        height: 290px;
        width: 100%;
    }

    .chart-container.small {
        height: 235px;
    }

    .financial-card {
        padding: 19px;
        border: 1px solid #e9edf4;
        border-radius: 13px;
        background: #fff;
        height: 100%;
    }

    .financial-card .financial-label {
        color: #7d8799;
        font-size: 12px;
        font-weight: 600;
    }

    .financial-card .financial-value {
        color: #19265d;
        font-size: 21px;
        font-weight: 800;
        margin-top: 10px;
        word-break: break-word;
    }

    .financial-card .financial-icon {
        width: 37px;
        height: 37px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: #fff0e6;
        color: #d05208;
        font-size: 17px;
    }

    .financial-currency {
        padding: 8px 0;
        border-bottom: 1px solid #f0f2f6;
    }

    .financial-currency:last-child {
        border-bottom: 0;
    }

    .quick-link {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 13px;
        border: 1px solid #edf0f5;
        border-radius: 11px;
        color: #20283a;
        transition: .2s ease;
    }

    .quick-link:hover {
        border-color: #f0c3a7;
        background: #fffaf6;
        color: #d05208;
    }

    .quick-link-icon {
        width: 39px;
        height: 39px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border-radius: 10px;
        background: #fff0e6;
        color: #d05208;
        font-size: 17px;
    }

    .quick-link-title {
        font-size: 12px;
        font-weight: 800;
    }

    .quick-link-description {
        margin-top: 3px;
        color: #8791a4;
        font-size: 11px;
    }

    .dashboard-table {
        vertical-align: middle;
        white-space: nowrap;
    }

    .dashboard-table thead th {
        padding: 12px 14px;
        background: #f8f9fc;
        color: #7d8799;
        border-bottom: 1px solid #edf0f5;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .45px;
    }

    .dashboard-table tbody td {
        padding: 13px 14px;
        border-bottom: 1px solid #f0f2f6;
        color: #4c566b;
        font-size: 12px;
    }

    .dashboard-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .dashboard-table .primary-text {
        color: #19265d;
        font-weight: 700;
    }

    .dashboard-badge {
        padding: 5px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
    }

    .activity-avatar {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: #edf0fb;
        color: #19265d;
        font-size: 13px;
    }

    .empty-state {
        padding: 30px 15px;
        color: #8a94a6;
        text-align: center;
        font-size: 12px;
    }

    .empty-state i {
        display: block;
        margin-bottom: 8px;
        font-size: 25px;
        color: #c4cad5;
    }

    @media(max-width: 575px) {
        .admin-welcome {
            padding: 22px 19px;
        }

        .admin-welcome h2 {
            font-size: 19px;
        }

        .stat-value {
            font-size: 22px;
        }

        .dashboard-card {
            padding: 15px;
        }
    }
</style>
@endpush

@section('content')

<div class="container-fluid px-0">

    @include('admin._platform-monitor')

    {{-- Welcome banner --}}
    <div class="admin-welcome mb-4">
        <div class="row align-items-center position-relative" style="z-index:1;">
            <div class="col-lg-8">
                <div class="welcome-date mb-2">
                    {{ now()->format('l, d F Y') }}
                </div>

                <h2>
                    Welcome back, {{ auth()->user()->name }}.
                </h2>

                <p>
                    Here is an overview of your rental management platform.
                    Monitor properties, users, rental operations and financial performance.
                </p>
            </div>

            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                @if(\Illuminate\Support\Facades\Route::has('admin.properties.index'))
                    <a href="{{ route('admin.properties.index') }}"
                       class="btn btn-light btn-sm fw-bold px-3 py-2">
                        <i class="bi bi-buildings me-1"></i>
                        Manage Properties
                    </a>
                @endif

                @if(\Illuminate\Support\Facades\Route::has('admin.users.index'))
                    <a href="{{ route('admin.users.index') }}"
                       class="btn btn-outline-light btn-sm fw-bold px-3 py-2 ms-1">
                        <i class="bi bi-people me-1"></i>
                        Users
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Main statistics --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="dashboard-section-title">Platform Overview</h5>
            <div class="dashboard-section-subtitle">
                Key statistics across your platform
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach($adminCards as $card)
            <div class="col-6 col-md-4 col-xl-2">
                @if($card['route'] && \Illuminate\Support\Facades\Route::has($card['route']))
                    <a href="{{ route($card['route']) }}" class="text-decoration-none">
                @endif

                <div class="admin-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="stat-label">{{ $card['label'] }}</div>

                        <div class="stat-icon {{ $card['color'] }}">
                            <i class="bi {{ $card['icon'] }}"></i>
                        </div>
                    </div>

                    <div class="stat-value">
                        {{ number_format($card['value']) }}
                    </div>

                    <div class="stat-description">
                        {{ $card['description'] }}
                    </div>
                </div>

                @if($card['route'] && \Illuminate\Support\Facades\Route::has($card['route']))
                    </a>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Financial overview --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="dashboard-section-title">Financial Overview</h5>
            <div class="dashboard-section-subtitle">
                Financial performance grouped by currency
            </div>
        </div>

        @if(\Illuminate\Support\Facades\Route::has('admin.financial-reports.index'))
            <a href="{{ route('admin.financial-reports.index') }}"
               class="small fw-bold text-decoration-none"
               style="color:#D05208;">
                View Reports <i class="bi bi-arrow-right"></i>
            </a>
        @endif
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="financial-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="financial-label">Total Rent Collections</div>
                    <div class="financial-icon">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>

                @forelse($collectionsByCurrency as $currency => $amount)
                    <div class="financial-currency">
                        <div class="financial-value">
                            {{ $formatMoney($amount, $currency) }}
                        </div>
                    </div>
                @empty
                    <div class="financial-value">RWF 0</div>
                @endforelse

                <div class="text-muted small mt-2">
                    Recorded payment amounts
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="financial-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="financial-label">Outstanding Invoices</div>
                    <div class="financial-icon">
                        <i class="bi bi-receipt"></i>
                    </div>
                </div>

                @forelse($outstandingByCurrency as $currency => $amount)
                    <div class="financial-currency">
                        <div class="financial-value">
                            {{ $formatMoney($amount, $currency) }}
                        </div>
                    </div>
                @empty
                    <div class="financial-value">RWF 0</div>
                @endforelse

                <div class="text-muted small mt-2">
                    {{ number_format($stats['pending_invoices']) }} pending invoices
                    · {{ number_format($stats['overdue_invoices']) }} overdue
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="financial-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="financial-label">Property Expenses</div>
                    <div class="financial-icon">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>

                @forelse($expensesByCurrency as $currency => $amount)
                    <div class="financial-currency">
                        <div class="financial-value">
                            {{ $formatMoney($amount, $currency) }}
                        </div>
                    </div>
                @empty
                    <div class="financial-value">RWF 0</div>
                @endforelse

                <div class="text-muted small mt-2">
                    Recorded property expenses
                </div>
            </div>
        </div>

    </div>

    {{-- Operational indicators --}}
    <div class="row g-3 mb-4">

        @php
            $operationalStats = [
                [
                    'label' => 'Pending Applications',
                    'value' => $stats['pending_applications'],
                    'icon' => 'bi-file-earmark-person',
                    'route' => 'admin.tenant-applications.index',
                    'color' => '#D05208',
                ],
                [
                    'label' => 'Active Leases',
                    'value' => $stats['active_leases'],
                    'icon' => 'bi-file-earmark-check',
                    'route' => 'admin.leases.index',
                    'color' => '#23935B',
                ],
                [
                    'label' => 'Pending Invoices',
                    'value' => $stats['pending_invoices'],
                    'icon' => 'bi-receipt',
                    'route' => 'admin.rent-invoices.index',
                    'color' => '#2872C7',
                ],
                [
                    'label' => 'Overdue Invoices',
                    'value' => $stats['overdue_invoices'],
                    'icon' => 'bi-exclamation-circle',
                    'route' => 'admin.rent-invoices.index',
                    'color' => '#C43D45',
                ],
                [
                    'label' => 'Open Maintenance',
                    'value' => $stats['open_maintenance'],
                    'icon' => 'bi-tools',
                    'route' => null,
                    'color' => '#7853BD',
                ],
                [
                    'label' => 'Pending Properties',
                    'value' => $stats['pending_properties'],
                    'icon' => 'bi-house-exclamation',
                    'route' => 'admin.properties.index',
                    'color' => '#168CA3',
                ],
            ];
        @endphp

        @foreach($operationalStats as $item)
            <div class="col-6 col-md-4 col-xl-2">
                @if($item['route'] && \Illuminate\Support\Facades\Route::has($item['route']))
                    <a href="{{ route($item['route']) }}" class="text-decoration-none">
                @endif

                <div class="dashboard-card py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi {{ $item['icon'] }}"
                           style="font-size:18px;color:{{ $item['color'] }};"></i>

                        <span class="stat-label">{{ $item['label'] }}</span>
                    </div>

                    <div class="stat-value">
                        {{ number_format($item['value']) }}
                    </div>
                </div>

                @if($item['route'] && \Illuminate\Support\Facades\Route::has($item['route']))
                    </a>
                @endif
            </div>
        @endforeach

    </div>

    {{-- Charts --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-8">
            <div class="dashboard-card">
                <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="dashboard-section-title">Rent Collection Trends</h5>
                        <div class="dashboard-section-subtitle">
                            Payment records over the last six months
                        </div>
                    </div>
                </div>

                <div class="chart-container">
                    <canvas id="collectionsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="dashboard-card">
                <div class="mb-3">
                    <h5 class="dashboard-section-title">Operational Summary</h5>
                    <div class="dashboard-section-subtitle">
                        Current platform activity
                    </div>
                </div>

                <div class="chart-container small">
                    <canvas id="operationsChart"></canvas>
                </div>
            </div>
        </div>

    </div>

    {{-- Quick links --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-5">
            <div class="dashboard-card">
                <div class="mb-3">
                    <h5 class="dashboard-section-title">Quick Access</h5>
                    <div class="dashboard-section-subtitle">
                        Frequently used administration modules
                    </div>
                </div>

                <div class="row g-2">
                    @foreach($quickLinks as $link)
                        @if(\Illuminate\Support\Facades\Route::has($link['route']))
                            <div class="col-12 col-sm-6">
                                <a href="{{ route($link['route']) }}" class="quick-link">
                                    <span class="quick-link-icon">
                                        <i class="bi {{ $link['icon'] }}"></i>
                                    </span>

                                    <span class="min-w-0">
                                        <span class="quick-link-title d-block">
                                            {{ $link['label'] }}
                                        </span>

                                        <span class="quick-link-description d-block">
                                            {{ $link['description'] }}
                                        </span>
                                    </span>
                                </a>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="dashboard-card">
                <div class="mb-3">
                    <h5 class="dashboard-section-title">Platform Activity</h5>
                    <div class="dashboard-section-subtitle">
                        Latest platform records
                    </div>
                </div>

                <div class="row g-3">

                    <div class="col-6">
                        <div class="p-3 rounded-3" style="background:#f7f8fc;">
                            <div class="stat-label">Buildings</div>
                            <div class="stat-value">
                                {{ number_format($stats['buildings']) }}
                            </div>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="p-3 rounded-3" style="background:#fff7f1;">
                            <div class="stat-label">Total Leases</div>
                            <div class="stat-value">
                                {{ number_format($stats['leases']) }}
                            </div>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="p-3 rounded-3" style="background:#f0f9f4;">
                            <div class="stat-label">Payments Recorded</div>
                            <div class="stat-value">
                                {{ number_format($stats['payments']) }}
                            </div>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="p-3 rounded-3" style="background:#f3effb;">
                            <div class="stat-label">Maintenance Requests</div>
                            <div class="stat-value">
                                {{ number_format($stats['maintenance']) }}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    {{-- Recent users --}}
    <div class="dashboard-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="dashboard-section-title">Recently Registered Users</h5>
                <div class="dashboard-section-subtitle">
                    Latest accounts registered on Terra
                </div>
            </div>

            @if(\Illuminate\Support\Facades\Route::has('admin.users.index'))
                <a href="{{ route('admin.users.index') }}"
                   class="small fw-bold text-decoration-none"
                   style="color:#D05208;">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table dashboard-table mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($recentUsers as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="activity-avatar">
                                        <i class="bi bi-person"></i>
                                    </span>

                                    <span class="primary-text">
                                        {{ $user->name ?? 'Unnamed User' }}
                                    </span>
                                </div>
                            </td>

                            <td>{{ $user->email ?? '—' }}</td>

                            <td>
                                <span class="badge bg-light text-dark dashboard-badge">
                                    {{ ucwords(str_replace('_', ' ', $user->role ?? 'user')) }}
                                </span>
                            </td>

                            <td>
                                {{ $user->created_at
                                    ? \Carbon\Carbon::parse($user->created_at)->format('d M Y')
                                    : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <i class="bi bi-people"></i>
                                    No user registrations found.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent properties and payments --}}
    <div class="row g-3">

        <div class="col-lg-6">
            <div class="dashboard-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="dashboard-section-title">Recent Properties</h5>
                        <div class="dashboard-section-subtitle">
                            Recently added properties
                        </div>
                    </div>

                    @if(\Illuminate\Support\Facades\Route::has('admin.properties.index'))
                        <a href="{{ route('admin.properties.index') }}"
                           class="small fw-bold text-decoration-none"
                           style="color:#D05208;">
                            View All
                        </a>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table dashboard-table mb-0">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Code</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($recentProperties as $property)
                                <tr>
                                    <td class="primary-text">
                                        {{ $property->name ?? 'Unnamed Property' }}
                                    </td>

                                    <td>{{ $property->property_code ?? '—' }}</td>

                                    <td>
                                        @php
                                            $propertyStatus = $property->status ?? 'Unknown';
                                        @endphp

                                        <span class="badge bg-{{ $statusClass($propertyStatus) }}-subtle text-{{ $statusClass($propertyStatus) }} dashboard-badge">
                                            {{ ucwords(str_replace('_', ' ', $propertyStatus)) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">
                                        <div class="empty-state">
                                            <i class="bi bi-buildings"></i>
                                            No properties found.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="dashboard-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="dashboard-section-title">Recent Payments</h5>
                        <div class="dashboard-section-subtitle">
                            Latest recorded rent payments
                        </div>
                    </div>

                    @if(\Illuminate\Support\Facades\Route::has('admin.payments.index'))
                        <a href="{{ route('admin.payments.index') }}"
                           class="small fw-bold text-decoration-none"
                           style="color:#D05208;">
                            View All
                        </a>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table dashboard-table mb-0">
                        <thead>
                            <tr>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($recentPayments as $payment)
                                @php
                                    $paymentAmount = $payment->amount
                                        ?? $payment->payment_amount
                                        ?? $payment->amount_paid
                                        ?? 0;

                                    $paymentCurrency = $payment->currency ?? 'RWF';
                                    $paymentStatus = $payment->status ?? 'Recorded';
                                @endphp

                                <tr>
                                    <td class="primary-text">
                                        {{ $formatMoney($paymentAmount, $paymentCurrency) }}
                                    </td>

                                    <td>
                                        <span class="badge bg-{{ $statusClass($paymentStatus) }}-subtle text-{{ $statusClass($paymentStatus) }} dashboard-badge">
                                            {{ ucwords(str_replace('_', ' ', $paymentStatus)) }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ $payment->created_at
                                            ? \Carbon\Carbon::parse($payment->created_at)->format('d M Y')
                                            : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">
                                        <div class="empty-state">
                                            <i class="bi bi-cash-stack"></i>
                                            No payment records found.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const chartFont = {
        family: "'Source Serif 4', Georgia, serif",
        size: 11
    };

    const chartColors = {
        orange: '#D05208',
        navy: '#19265d',
        green: '#23935B',
        blue: '#2872C7',
        purple: '#7853BD',
        red: '#C43D45',
        gray: '#E8EBF2'
    };

    // Monthly rent collections
    const collectionData = @json($monthlyCollections);

    const collectionCanvas = document.getElementById('collectionsChart');

    if (collectionCanvas) {
        new Chart(collectionCanvas, {
            type: 'line',

            data: {
                labels: collectionData.labels,

                datasets: [
                    {
                        label: 'RWF',
                        data: collectionData.RWF,
                        borderColor: chartColors.orange,
                        backgroundColor: 'rgba(208, 82, 8, .08)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: .38,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    },
                    {
                        label: 'USD',
                        data: collectionData.USD,
                        borderColor: chartColors.navy,
                        backgroundColor: 'rgba(25, 38, 93, .05)',
                        borderWidth: 2.5,
                        fill: false,
                        tension: .38,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }
                ]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                interaction: {
                    intersect: false,
                    mode: 'index'
                },

                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 7,
                            font: chartFont
                        }
                    },

                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' +
                                    new Intl.NumberFormat().format(context.raw);
                            }
                        }
                    }
                },

                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: chartFont }
                    },

                    y: {
                        beginAtZero: true,
                        grid: { color: '#f0f2f6' },
                        ticks: {
                            font: chartFont,
                            callback: function(value) {
                                return new Intl.NumberFormat('en', {
                                    notation: 'compact'
                                }).format(value);
                            }
                        }
                    }
                }
            }
        });
    }

    // Operational overview
    const operationsCanvas = document.getElementById('operationsChart');

    if (operationsCanvas) {
        new Chart(operationsCanvas, {
            type: 'doughnut',

            data: {
                labels: [
                    'Active Leases',
                    'Pending Applications',
                    'Pending Invoices',
                    'Open Maintenance'
                ],

                datasets: [{
                    data: [
                        {{ $stats['active_leases'] }},
                        {{ $stats['pending_applications'] }},
                        {{ $stats['pending_invoices'] }},
                        {{ $stats['open_maintenance'] }}
                    ],

                    backgroundColor: [
                        chartColors.green,
                        chartColors.orange,
                        chartColors.blue,
                        chartColors.purple
                    ],

                    borderWidth: 0,
                    hoverOffset: 5
                }]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',

                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 7,
                            padding: 15,
                            font: chartFont
                        }
                    }
                }
            }
        });
    }

});
</script>
@endpush