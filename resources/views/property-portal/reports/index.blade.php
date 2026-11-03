@extends('layouts.app')

@section('title', 'Operational Reports')

@section('content')
@php
    $formatMoney = function ($amount, $currency = 'RWF') {
        return $currency . ' ' . number_format((float) $amount, 0);
    };

    $statusColors = [
        'active' => 'success',
        'draft' => 'secondary',
        'paid' => 'success',
        'partial' => 'warning',
        'unpaid' => 'danger',
        'overdue' => 'danger',
        'open' => 'warning',
        'in_progress' => 'primary',
        'scheduled' => 'info',
        'resolved' => 'success',
        'closed' => 'secondary',
        'rejected' => 'danger',
    ];
@endphp

<div class="container-fluid py-4">

    {{-- Page heading --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Operational Reports</h3>
            <p class="text-muted mb-0">
                Monitor occupancy, rent collections, expenses and maintenance performance.
            </p>
        </div>

        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-2"></i>Print Report
        </button>
    </div>

    {{-- Date filter --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('property-manager.reports.index') }}"
                  method="GET"
                  class="row g-3 align-items-end">

                <div class="col-md-4">
                    <label class="form-label fw-semibold">From date</label>
                    <input type="date"
                           name="from"
                           class="form-control"
                           value="{{ request('from', $from->format('Y-m-d')) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">To date</label>
                    <input type="date"
                           name="to"
                           class="form-control"
                           value="{{ request('to', $to->format('Y-m-d')) }}">
                </div>

                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1" type="submit">
                        <i class="bi bi-funnel me-1"></i> Apply Filter
                    </button>

                    <a href="{{ route('property-manager.reports.index') }}"
                       class="btn btn-light border">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Property overview --}}
    <div class="mb-3">
        <h5 class="fw-bold mb-1">Property Overview</h5>
        <p class="text-muted small mb-0">Current portfolio performance.</p>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-2">Properties</div>
                            <h3 class="fw-bold mb-0">{{ $properties->count() }}</h3>
                        </div>
                        <span class="rounded-3 p-2 bg-light text-primary">
                            <i class="bi bi-buildings fs-5"></i>
                        </span>
                    </div>
                    <div class="text-muted small mt-3">Accessible properties</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-2">Total Units</div>
                            <h3 class="fw-bold mb-0">{{ number_format($totalUnits) }}</h3>
                        </div>
                        <span class="rounded-3 p-2 bg-light text-primary">
                            <i class="bi bi-door-open fs-5"></i>
                        </span>
                    </div>
                    <div class="text-muted small mt-3">
                        {{ number_format($availableUnits) }} available
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-2">Occupied Units</div>
                            <h3 class="fw-bold mb-0">{{ number_format($occupiedUnits) }}</h3>
                        </div>
                        <span class="rounded-3 p-2 bg-light text-success">
                            <i class="bi bi-house-check fs-5"></i>
                        </span>
                    </div>
                    <div class="text-muted small mt-3">
                        {{ $occupancyRate }}% occupancy rate
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-2">Tenants</div>
                            <h3 class="fw-bold mb-0">{{ number_format($tenantCount) }}</h3>
                        </div>
                        <span class="rounded-3 p-2 bg-light text-primary">
                            <i class="bi bi-people fs-5"></i>
                        </span>
                    </div>
                    <div class="text-muted small mt-3">Tenants with lease records</div>
                </div>
            </div>
        </div>

    </div>

    {{-- Occupancy progress --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-1">Portfolio Occupancy</h6>
                    <p class="text-muted small mb-0">Occupied units compared with total units.</p>
                </div>
                <h4 class="fw-bold mb-0">{{ $occupancyRate }}%</h4>
            </div>

            <div class="progress" style="height: 12px;">
                <div class="progress-bar bg-success"
                     role="progressbar"
                     style="width: {{ min($occupancyRate, 100) }}%"
                     aria-valuenow="{{ $occupancyRate }}"
                     aria-valuemin="0"
                     aria-valuemax="100">
                </div>
            </div>

            <div class="d-flex justify-content-between mt-2 small text-muted">
                <span>{{ $occupiedUnits }} occupied</span>
                <span>{{ $availableUnits }} available</span>
            </div>
        </div>
    </div>

    {{-- Lease and rent reports --}}
    <div class="mb-3">
        <h5 class="fw-bold mb-1">Leases and Rent</h5>
        <p class="text-muted small mb-0">
            Lease portfolio and rent activity for the selected period.
        </p>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="text-muted small">Total Leases</span>
                    <h3 class="fw-bold mt-2 mb-0">{{ number_format($totalLeases) }}</h3>
                    <span class="small text-muted">All lease records</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="text-muted small">Active Leases</span>
                    <h3 class="fw-bold text-success mt-2 mb-0">
                        {{ number_format($activeLeases) }}
                    </h3>
                    <span class="small text-muted">Currently active</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="text-muted small">Draft Leases</span>
                    <h3 class="fw-bold mt-2 mb-0">{{ number_format($draftLeases) }}</h3>
                    <span class="small text-muted">Awaiting completion</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="text-muted small">Invoices Issued</span>
                    <h3 class="fw-bold mt-2 mb-0">{{ number_format($invoiceCount) }}</h3>
                    <span class="small text-muted">Selected period</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="text-muted small">Outstanding Invoices</span>
                    <h3 class="fw-bold text-warning mt-2 mb-0">
                        {{ number_format($outstandingInvoices) }}
                    </h3>
                    <span class="small text-muted">Unpaid or partially paid</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="text-muted small">Overdue Invoices</span>
                    <h3 class="fw-bold text-danger mt-2 mb-0">
                        {{ number_format($overdueInvoices) }}
                    </h3>
                    <span class="small text-muted">Past due</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="text-muted small">Payments Received</span>
                    <h3 class="fw-bold text-success mt-2 mb-0">
                        {{ number_format($paymentCount) }}
                    </h3>
                    <span class="small text-muted">Payment transactions</span>
                </div>
            </div>
        </div>

    </div>

    {{-- Collections by currency --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 px-3">
                    <h6 class="fw-bold mb-1">Rent Collections</h6>
                    <p class="text-muted small mb-0">Payments received by currency.</p>
                </div>

                <div class="card-body">
                    @forelse($collectionsByCurrency as $collection)
                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                            <div>
                                <span class="fw-semibold">{{ $collection->currency ?: 'Currency not set' }}</span>
                                <div class="text-muted small">Total collected</div>
                            </div>
                            <h5 class="fw-bold text-success mb-0">
                                {{ $formatMoney($collection->total, $collection->currency ?: '') }}
                            </h5>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            No rent payments recorded during this period.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Expenses by currency --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 px-3">
                    <h6 class="fw-bold mb-1">Property Expenses</h6>
                    <p class="text-muted small mb-0">Recorded expenses by currency.</p>
                </div>

                <div class="card-body">
                    @forelse($expensesByCurrency as $expense)
                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                            <div>
                                <span class="fw-semibold">{{ $expense->currency ?: 'Currency not set' }}</span>
                                <div class="text-muted small">Total recorded expenses</div>
                            </div>
                            <h5 class="fw-bold mb-0">
                                {{ $formatMoney($expense->total, $expense->currency ?: '') }}
                            </h5>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            No property expenses recorded during this period.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Maintenance --}}
    <div class="mb-3">
        <h5 class="fw-bold mb-1">Maintenance Performance</h5>
        <p class="text-muted small mb-0">Maintenance requests created during the selected period.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <span class="text-muted small">Total Requests</span>
                    <h3 class="fw-bold mt-2 mb-0">{{ $maintenanceCount }}</h3>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <span class="text-muted small">Open Requests</span>
                    <h3 class="fw-bold text-warning mt-2 mb-0">{{ $openMaintenance }}</h3>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <span class="text-muted small">Resolved Requests</span>
                    <h3 class="fw-bold text-success mt-2 mb-0">{{ $resolvedMaintenance }}</h3>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <span class="text-muted small">Urgent Open Requests</span>
                    <h3 class="fw-bold text-danger mt-2 mb-0">{{ $urgentMaintenance }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent payments --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 p-3">
            <h6 class="fw-bold mb-0">Recent Rent Payments</h6>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Payment</th>
                        <th>Unit</th>
                        <th>Property</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPayments as $payment)
                        <tr>
                            <td class="fw-semibold">
                                {{ $payment->payment_number ?? 'Payment #' . $payment->id }}
                            </td>
                            <td>{{ $payment->unit?->unit_number ?? '—' }}</td>
                            <td>{{ $payment->unit?->building?->property?->name ?? '—' }}</td>
                            <td class="fw-semibold">
                                {{ $formatMoney($payment->amount ?? 0, $payment->currency ?? 'RWF') }}
                            </td>
                            <td>{{ $payment->created_at?->format('d M Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No payment records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent expenses --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 p-3">
            <h6 class="fw-bold mb-0">Recent Property Expenses</h6>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Expense</th>
                        <th>Property</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Expense Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentExpenses as $expense)
                        <tr>
                            <td class="fw-semibold">
                                {{ $expense->expense_number ?? $expense->title }}
                            </td>
                            <td>{{ $expense->property?->name ?? '—' }}</td>
                            <td>{{ str($expense->category)->replace('_', ' ')->title() }}</td>
                            <td class="fw-semibold">
                                {{ $formatMoney($expense->amount, $expense->currency ?? 'RWF') }}
                            </td>
                            <td>
                                {{ $expense->expense_date
                                    ? \Illuminate\Support\Carbon::parse($expense->expense_date)->format('d M Y')
                                    : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No expense records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent maintenance --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 p-3">
            <h6 class="fw-bold mb-0">Recent Maintenance Requests</h6>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Request</th>
                        <th>Unit</th>
                        <th>Property</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentMaintenance as $maintenance)
                        <tr>
                            <td class="fw-semibold">
                                {{ $maintenance->request_number }}
                                <div class="text-muted small">{{ $maintenance->title }}</div>
                            </td>
                            <td>{{ $maintenance->unit?->unit_number ?? '—' }}</td>
                            <td>{{ $maintenance->unit?->building?->property?->name ?? '—' }}</td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    {{ ucfirst($maintenance->priority) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $statusColors[$maintenance->status] ?? 'secondary' }}">
                                    {{ str($maintenance->status)->replace('_', ' ')->title() }}
                                </span>
                            </td>
                            <td>{{ $maintenance->created_at?->format('d M Y') }}</td>
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

    {{-- Property list --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 p-3">
            <h6 class="fw-bold mb-0">Properties Included in This Report</h6>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
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
                                No accessible properties found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-muted small mt-4">
        Reporting period:
        {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}
    </div>

</div>
@endsection