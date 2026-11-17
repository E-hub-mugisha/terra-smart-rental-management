@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $currencyFormat = function ($amount, $currency = 'RWF') {
        return $currency . ' ' . number_format((float) $amount, 0);
    };

    $invoiceStatusLabels = [
        'paid' => 'Paid',
        'unpaid' => 'Unpaid',
        'partial' => 'Partially Paid',
        'overdue' => 'Overdue',
    ];

    $maintenanceStatusLabels = [
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'scheduled' => 'Scheduled',
        'on_hold' => 'On Hold',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
        'rejected' => 'Rejected',
    ];

    $statusColors = [
        'active' => 'success',
        'draft' => 'secondary',
        'pending' => 'warning',
        'paid' => 'success',
        'partial' => 'warning',
        'unpaid' => 'danger',
        'overdue' => 'danger',
        'open' => 'warning',
        'in_progress' => 'primary',
        'scheduled' => 'info',
        'on_hold' => 'secondary',
        'resolved' => 'success',
        'closed' => 'secondary',
        'rejected' => 'danger',
    ];
@endphp

<div class="container-fluid py-4 dashboard-page">

    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Dashboard</h3>
            <p class="text-muted mb-0">
                Welcome back, {{ auth()->user()->name }}. Here's your property portfolio overview.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('property-manager.reports.index') }}"
               class="btn btn-outline-secondary">
                <i class="bi bi-bar-chart-line me-2"></i>Reports
            </a>

            <span class="btn btn-light border text-muted">
                <i class="bi bi-calendar3 me-2"></i>{{ now()->format('d M Y') }}
            </span>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">

        <div class="col-6 col-xl-3">
            <div class="dashboard-stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-label">Properties</p>
                        <h3 class="stat-value">{{ number_format($properties->count()) }}</h3>
                        <span class="stat-description">Managed portfolio</span>
                    </div>
                    <div class="stat-icon stat-icon-orange">
                        <i class="bi bi-buildings"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="dashboard-stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-label">Total Units</p>
                        <h3 class="stat-value">{{ number_format($totalUnits) }}</h3>
                        <span class="stat-description">
                            {{ number_format($availableUnits) }} available
                        </span>
                    </div>
                    <div class="stat-icon stat-icon-blue">
                        <i class="bi bi-door-open"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="dashboard-stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-label">Active Leases</p>
                        <h3 class="stat-value">{{ number_format($activeLeases) }}</h3>
                        <span class="stat-description">
                            {{ number_format($pendingLeases) }} pending or draft
                        </span>
                    </div>
                    <div class="stat-icon stat-icon-green">
                        <i class="bi bi-file-earmark-check"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="dashboard-stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-label">Tenants</p>
                        <h3 class="stat-value">{{ number_format($tenantCount) }}</h3>
                        <span class="stat-description">With lease records</span>
                    </div>
                    <div class="stat-icon stat-icon-purple">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Secondary statistics --}}
    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="dashboard-mini-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="mini-icon text-success bg-success-subtle">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Rent Collected This Month</div>
                        <div class="fw-bold fs-5">
                            {{ $currentMonthPayments->count() }} payments
                        </div>
                        <div class="small text-muted">By currency</div>
                    </div>
                </div>

                @forelse($collectionsThisMonth as $currency => $amount)
                    <div class="d-flex justify-content-between mt-3 pt-2 border-top">
                        <span class="small text-muted">{{ $currency }}</span>
                        <strong>{{ $currencyFormat($amount, $currency) }}</strong>
                    </div>
                @empty
                    <div class="small text-muted mt-3">No payments recorded this month.</div>
                @endforelse
            </div>
        </div>

        <div class="col-md-4">
            <div class="dashboard-mini-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="mini-icon text-warning bg-warning-subtle">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Outstanding Invoices</div>
                        <div class="fw-bold fs-4">{{ number_format($outstandingInvoices) }}</div>
                        <div class="small text-muted">Unpaid, partial or overdue</div>
                    </div>
                </div>

                <div class="mt-3 pt-2 border-top">
                    <span class="text-muted small">Overdue invoices</span>
                    <strong class="text-danger float-end">
                        {{ number_format($overdueInvoices) }}
                    </strong>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="dashboard-mini-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="mini-icon text-danger bg-danger-subtle">
                        <i class="bi bi-tools"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Open Maintenance</div>
                        <div class="fw-bold fs-4">{{ number_format($openMaintenance) }}</div>
                        <div class="small text-muted">Requests requiring attention</div>
                    </div>
                </div>

                <div class="mt-3 pt-2 border-top">
                    <span class="text-muted small">Urgent requests</span>
                    <strong class="text-danger float-end">
                        {{ number_format($urgentMaintenance) }}
                    </strong>
                </div>
            </div>
        </div>

    </div>

    {{-- Charts row --}}
    <div class="row g-3 mb-4">

        {{-- Rent collection chart --}}
        <div class="col-xl-8">
            <div class="dashboard-panel h-100">
                <div class="panel-heading">
                    <div>
                        <h5 class="panel-title">Rent Collection Trends</h5>
                        <p class="panel-subtitle">Monthly payments received over the last six months.</p>
                    </div>
                    <span class="panel-icon">
                        <i class="bi bi-graph-up"></i>
                    </span>
                </div>

                <div class="chart-container chart-container-large">
                    <canvas id="rentCollectionChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Occupancy chart --}}
        <div class="col-xl-4">
            <div class="dashboard-panel h-100">
                <div class="panel-heading">
                    <div>
                        <h5 class="panel-title">Unit Occupancy</h5>
                        <p class="panel-subtitle">Current portfolio occupancy.</p>
                    </div>
                    <span class="panel-icon">
                        <i class="bi bi-pie-chart"></i>
                    </span>
                </div>

                <div class="chart-container chart-container-doughnut">
                    <canvas id="occupancyChart"></canvas>
                </div>

                <div class="occupancy-summary">
                    <div>
                        <span class="legend-dot bg-success"></span>
                        <span>Occupied</span>
                        <strong>{{ $occupiedUnits }}</strong>
                    </div>
                    <div>
                        <span class="legend-dot bg-primary"></span>
                        <span>Available</span>
                        <strong>{{ $availableUnits }}</strong>
                    </div>
                    <div>
                        <span class="legend-dot bg-secondary"></span>
                        <span>Other</span>
                        <strong>{{ $otherUnits }}</strong>
                    </div>
                </div>

                <div class="occupancy-rate">
                    <span>Occupancy rate</span>
                    <strong>{{ $occupancyRate }}%</strong>
                </div>
            </div>
        </div>

    </div>

    {{-- Invoice and maintenance charts --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-6">
            <div class="dashboard-panel h-100">
                <div class="panel-heading">
                    <div>
                        <h5 class="panel-title">Invoice Overview</h5>
                        <p class="panel-subtitle">Distribution of rent invoice statuses.</p>
                    </div>
                    <span class="panel-icon">
                        <i class="bi bi-receipt-cutoff"></i>
                    </span>
                </div>

                <div class="chart-container chart-container-medium">
                    <canvas id="invoiceChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="dashboard-panel h-100">
                <div class="panel-heading">
                    <div>
                        <h5 class="panel-title">Maintenance Overview</h5>
                        <p class="panel-subtitle">Maintenance requests by current status.</p>
                    </div>
                    <span class="panel-icon">
                        <i class="bi bi-wrench-adjustable"></i>
                    </span>
                </div>

                <div class="chart-container chart-container-medium">
                    <canvas id="maintenanceChart"></canvas>
                </div>
            </div>
        </div>

    </div>

    {{-- Recent activity --}}
    <div class="row g-3 mb-4">

        {{-- Recent payments --}}
        <div class="col-xl-6">
            <div class="dashboard-panel h-100">
                <div class="panel-heading">
                    <div>
                        <h5 class="panel-title">Recent Payments</h5>
                        <p class="panel-subtitle">Latest rent payment transactions.</p>
                    </div>
                    <a href="{{ route('property-manager.payments.index') }}"
                       class="panel-link">View all</a>
                </div>

                <div class="table-responsive">
                    <table class="table dashboard-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Unit / Property</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPayments as $payment)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $payment->unit?->unit_number ?? 'Unit —' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $payment->unit?->building?->property?->name ?? '—' }}
                                        </div>
                                    </td>
                                    <td class="fw-semibold text-success">
                                        {{ $currencyFormat($payment->amount, $payment->currency ?? 'RWF') }}
                                    </td>
                                    <td class="text-muted small">
                                        {{ $payment->created_at?->format('d M Y') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        No payment transactions yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Recent leases --}}
        <div class="col-xl-6">
            <div class="dashboard-panel h-100">
                <div class="panel-heading">
                    <div>
                        <h5 class="panel-title">Recent Leases</h5>
                        <p class="panel-subtitle">Latest lease records in your portfolio.</p>
                    </div>
                    <a href="{{ route('property-manager.leases.index') }}"
                       class="panel-link">View all</a>
                </div>

                <div class="table-responsive">
                    <table class="table dashboard-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tenant / Unit</th>
                                <th>Monthly Rent</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLeases as $lease)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $lease->tenant?->full_name ?? 'Tenant —' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $lease->unit?->unit_number ?? 'Unit —' }}
                                        </div>
                                    </td>
                                    <td class="fw-semibold">
                                        {{ $currencyFormat($lease->monthly_rent, $lease->currency ?? 'RWF') }}
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $statusColors[$lease->status] ?? 'secondary' }}-subtle text-{{ $statusColors[$lease->status] ?? 'secondary' }}">
                                            {{ str($lease->status)->replace('_', ' ')->title() }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        No lease records yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- Maintenance requests --}}
    <div class="dashboard-panel mb-4">
        <div class="panel-heading">
            <div>
                <h5 class="panel-title">Recent Maintenance Requests</h5>
                <p class="panel-subtitle">Latest maintenance activities requiring attention.</p>
            </div>
            <a href="{{ route('property-manager.maintenance-requests.index') }}"
               class="panel-link">View all</a>
        </div>

        <div class="table-responsive">
            <table class="table dashboard-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Tenant</th>
                        <th>Property / Unit</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentMaintenance as $maintenance)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $maintenance->request_number }}</div>
                                <div class="small text-muted">{{ $maintenance->title }}</div>
                            </td>
                            <td>{{ $maintenance->tenant?->full_name ?? '—' }}</td>
                            <td>
                                <div>{{ $maintenance->unit?->building?->property?->name ?? '—' }}</div>
                                <div class="small text-muted">
                                    {{ $maintenance->unit?->unit_number ?? '—' }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    {{ ucfirst($maintenance->priority) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $statusColors[$maintenance->status] ?? 'secondary' }}-subtle text-{{ $statusColors[$maintenance->status] ?? 'secondary' }}">
                                    {{ str($maintenance->status)->replace('_', ' ')->title() }}
                                </span>
                            </td>
                            <td class="text-muted small">
                                {{ $maintenance->created_at?->format('d M Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No maintenance requests found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Portfolio properties --}}
    <div class="dashboard-panel">
        <div class="panel-heading">
            <div>
                <h5 class="panel-title">Your Properties</h5>
                <p class="panel-subtitle">Properties included in your dashboard.</p>
            </div>
            <a href="{{ route('property-manager.properties.index') }}"
               class="panel-link">View properties</a>
        </div>

        <div class="table-responsive">
            <table class="table dashboard-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Property</th>
                        <th>Code</th>
                        <th>Buildings</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($properties as $property)
                        <tr>
                            <td class="fw-semibold">{{ $property->name }}</td>
                            <td>{{ $property->code ?? '—' }}</td>
                            <td>{{ $property->buildings_count }}</td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    {{ str($property->status ?? 'active')->replace('_', ' ')->title() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                No properties are currently assigned to your account.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    .dashboard-page {
        --terra-orange: #D05208;
        --terra-navy: #19265d;
        color: #253047;
    }

    .dashboard-stat-card,
    .dashboard-mini-card,
    .dashboard-panel {
        background: #fff;
        border: 1px solid #edf0f4;
        border-radius: 13px;
        box-shadow: 0 3px 14px rgba(25, 38, 93, .045);
    }

    .dashboard-stat-card {
        padding: 20px;
        height: 100%;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .dashboard-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 22px rgba(25, 38, 93, .08);
    }

    .stat-label {
        color: #788397;
        font-size: .83rem;
        margin-bottom: 9px;
    }

    .stat-value {
        color: var(--terra-navy);
        font-size: 1.8rem;
        font-weight: 750;
        margin-bottom: 5px;
    }

    .stat-description {
        color: #8993a4;
        font-size: .76rem;
    }

    .stat-icon {
        width: 43px;
        height: 43px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 11px;
        font-size: 1.2rem;
    }

    .stat-icon-orange {
        background: #fff0e8;
        color: var(--terra-orange);
    }

    .stat-icon-blue {
        background: #edf2ff;
        color: #3156a6;
    }

    .stat-icon-green {
        background: #e9f8f0;
        color: #198754;
    }

    .stat-icon-purple {
        background: #f2edff;
        color: #7652bd;
    }

    .dashboard-mini-card {
        padding: 19px;
        height: 100%;
    }

    .mini-icon {
        height: 44px;
        width: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 11px;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .dashboard-panel {
        padding: 20px;
    }

    .panel-heading {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 18px;
    }

    .panel-title {
        font-size: 1rem;
        color: var(--terra-navy);
        font-weight: 700;
        margin-bottom: 5px;
    }

    .panel-subtitle {
        color: #8993a4;
        font-size: .78rem;
        margin-bottom: 0;
    }

    .panel-icon {
        width: 37px;
        height: 37px;
        background: #fff0e8;
        color: var(--terra-orange);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .panel-link {
        color: var(--terra-orange);
        font-size: .82rem;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
    }

    .panel-link:hover {
        color: var(--terra-navy);
    }

    .chart-container {
        position: relative;
        width: 100%;
    }

    .chart-container-large {
        height: 310px;
    }

    .chart-container-medium {
        height: 280px;
    }

    .chart-container-doughnut {
        height: 205px;
        max-width: 290px;
        margin: 0 auto;
    }

    .occupancy-summary {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
        margin-top: 15px;
    }

    .occupancy-summary > div {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: .8rem;
        color: #6c778a;
    }

    .occupancy-summary strong {
        margin-left: auto;
        color: var(--terra-navy);
    }

    .legend-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        display: inline-block;
    }

    .occupancy-rate {
        border-top: 1px solid #edf0f4;
        padding-top: 13px;
        margin-top: 14px;
        display: flex;
        justify-content: space-between;
        font-size: .85rem;
        color: #6c778a;
    }

    .occupancy-rate strong {
        color: var(--terra-navy);
    }

    .dashboard-table {
        font-size: .83rem;
    }

    .dashboard-table thead th {
        background: #f8f9fb;
        color: #778296;
        font-size: .73rem;
        font-weight: 650;
        text-transform: uppercase;
        letter-spacing: .035em;
        border-bottom: 1px solid #edf0f4;
        padding: 12px 14px;
        white-space: nowrap;
    }

    .dashboard-table tbody td {
        padding: 13px 14px;
        border-color: #f0f2f5;
    }

    .dashboard-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .dashboard-table .badge {
        font-weight: 600;
        padding: 6px 9px;
    }

    @media (max-width: 767px) {
        .dashboard-stat-card {
            padding: 15px;
        }

        .stat-value {
            font-size: 1.5rem;
        }

        .dashboard-panel {
            padding: 15px;
        }

        .chart-container-large {
            height: 260px;
        }
    }

    @media print {
        .btn,
        .panel-link,
        .sidebar,
        nav {
            display: none !important;
        }

        .dashboard-panel,
        .dashboard-stat-card,
        .dashboard-mini-card {
            box-shadow: none !important;
            break-inside: avoid;
        }

        .dashboard-page {
            padding: 0 !important;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const chartFont = {
        family: "'Source Serif 4', Georgia, serif",
        size: 11
    };

    const gridColor = '#edf0f4';
    const textColor = '#8993a4';

    // Monthly rent collections.
    const monthLabels = @json($months->values());

    const collectionSeries = @json($monthlyCollections);

    const collectionColors = [
        '#D05208',
        '#3156a6',
        '#198754',
        '#7652bd'
    ];

    const collectionDatasets = collectionSeries.map((series, index) => ({
        label: series.currency,
        data: series.values,
        borderColor: collectionColors[index % collectionColors.length],
        backgroundColor: collectionColors[index % collectionColors.length],
        borderWidth: 2.5,
        tension: 0.35,
        fill: false,
        pointRadius: 4,
        pointHoverRadius: 6
    }));

    new Chart(document.getElementById('rentCollectionChart'), {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: collectionDatasets
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
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 18,
                        font: chartFont
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = Number(context.raw || 0).toLocaleString();
                            return context.dataset.label + ': ' + value;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: textColor, font: chartFont }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: {
                        color: textColor,
                        font: chartFont,
                        callback: value => Number(value).toLocaleString()
                    }
                }
            }
        }
    });

    // Unit occupancy.
    new Chart(document.getElementById('occupancyChart'), {
        type: 'doughnut',
        data: {
            labels: ['Occupied', 'Available', 'Other'],
            datasets: [{
                data: [
                    {{ $occupiedUnits }},
                    {{ $availableUnits }},
                    {{ $otherUnits }}
                ],
                backgroundColor: ['#198754', '#3156a6', '#aab2bf'],
                borderWidth: 0,
                hoverOffset: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: context => context.label + ': ' + context.raw
                    }
                }
            }
        }
    });

    // Invoice status distribution.
    const invoiceStatusData = @json($invoiceStatuses);

    const invoiceLabels = Object.keys(invoiceStatusData).map(
        status => ({
            paid: 'Paid',
            unpaid: 'Unpaid',
            partial: 'Partially Paid',
            overdue: 'Overdue'
        }[status] || status.replaceAll('_', ' ').replace(/\b\w/g, letter => letter.toUpperCase()))
    );

    const invoiceValues = Object.values(invoiceStatusData);

    new Chart(document.getElementById('invoiceChart'), {
        type: 'doughnut',
        data: {
            labels: invoiceLabels,
            datasets: [{
                data: invoiceValues,
                backgroundColor: ['#198754', '#D05208', '#e6a23c', '#dc3545', '#3156a6'],
                borderWidth: 0,
                hoverOffset: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 16,
                        font: chartFont
                    }
                }
            }
        }
    });

    // Maintenance status distribution.
    const maintenanceStatusData = @json($maintenanceStatuses);

    const maintenanceLabels = Object.keys(maintenanceStatusData).map(
        status => status.replaceAll('_', ' ').replace(/\b\w/g, letter => letter.toUpperCase())
    );

    new Chart(document.getElementById('maintenanceChart'), {
        type: 'bar',
        data: {
            labels: maintenanceLabels,
            datasets: [{
                label: 'Requests',
                data: Object.values(maintenanceStatusData),
                backgroundColor: [
                    '#e6a23c',
                    '#3156a6',
                    '#48a9a6',
                    '#aab2bf',
                    '#198754',
                    '#6c757d',
                    '#dc3545'
                ],
                borderRadius: 6,
                maxBarThickness: 42
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        color: textColor,
                        font: chartFont
                    },
                    grid: { color: gridColor }
                },
                y: {
                    ticks: {
                        color: textColor,
                        font: chartFont
                    },
                    grid: { display: false }
                }
            }
        }
    });

});
</script>
@endpush