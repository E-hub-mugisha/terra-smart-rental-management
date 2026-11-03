@extends('layouts.app')

@section('title', 'Financial Dashboard & Reports')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1">Financial Dashboard</h2>
            <p class="text-muted mb-0">
                Rental income, outstanding invoices, expenses and collections.
            </p>
        </div>

        <a href="{{ route('admin.financial-reports.export', request()->query()) }}"
           class="btn btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>
            Export CSV
        </a>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET"
                  action="{{ route('admin.financial-reports.index') }}"
                  class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label for="start_date" class="form-label">From date</label>
                    <input type="date"
                           class="form-control"
                           id="start_date"
                           name="start_date"
                           value="{{ $startDate }}">
                </div>

                <div class="col-md-3">
                    <label for="end_date" class="form-label">To date</label>
                    <input type="date"
                           class="form-control"
                           id="end_date"
                           name="end_date"
                           value="{{ $endDate }}">
                </div>

                <div class="col-md-3">
                    <label for="property_id" class="form-label">Managed property</label>
                    <select name="property_id" id="property_id" class="form-select">
                        <option value="">All properties</option>
                        @foreach ($properties as $property)
                            <option value="{{ $property->id }}"
                                @selected((string) $propertyId === (string) $property->id)>
                                {{ $property->name }}
                                @if ($property->property_code)
                                    — {{ $property->property_code }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="currency" class="form-label">Currency</label>
                    <select name="currency" id="currency" class="form-select">
                        <option value="">All currencies</option>
                        <option value="RWF" @selected($currency === 'RWF')>RWF</option>
                        <option value="USD" @selected($currency === 'USD')>USD</option>
                    </select>
                </div>

                <div class="col-md-1 d-grid">
                    <button type="submit" class="btn btn-primary">
                        Filter
                    </button>
                </div>
            </form>

            <div class="mt-3">
                <a href="{{ route('admin.financial-reports.index') }}"
                   class="small text-decoration-none">
                    Clear filters
                </a>
            </div>
        </div>
    </div>

    {{-- KPI cards --}}
    @foreach ($financialSummary as $summary)
        <div class="mb-4">
            <h5 class="fw-bold mb-3">
                Financial summary — {{ $summary['currency'] }}
            </h5>

            <div class="row g-3">
                <div class="col-xl-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted small">Rent Invoiced</div>
                            <h3 class="fw-bold mt-2 mb-0">
                                {{ number_format($summary['invoiced'], 2) }}
                                <small class="fs-6 text-muted">{{ $summary['currency'] }}</small>
                            </h3>
                            <div class="small text-muted mt-2">
                                Invoices issued during the selected period
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted small">Rent Collected</div>
                            <h3 class="fw-bold text-success mt-2 mb-0">
                                {{ number_format($summary['collected'], 2) }}
                                <small class="fs-6 text-muted">{{ $summary['currency'] }}</small>
                            </h3>
                            <div class="small text-muted mt-2">
                                Confirmed payments allocated to invoices
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted small">Outstanding Balance</div>
                            <h3 class="fw-bold text-warning mt-2 mb-0">
                                {{ number_format($summary['outstanding'], 2) }}
                                <small class="fs-6 text-muted">{{ $summary['currency'] }}</small>
                            </h3>
                            <div class="small text-muted mt-2">
                                Unpaid balances on invoices issued by the end date
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted small">Overdue Balance</div>
                            <h3 class="fw-bold text-danger mt-2 mb-0">
                                {{ number_format($summary['overdue'], 2) }}
                                <small class="fs-6 text-muted">{{ $summary['currency'] }}</small>
                            </h3>
                            <div class="small text-muted mt-2">
                                Outstanding invoices due before the selected end date
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted small">Property Expenses</div>
                            <h3 class="fw-bold mt-2 mb-0">
                                {{ number_format($summary['expenses'], 2) }}
                                <small class="fs-6 text-muted">{{ $summary['currency'] }}</small>
                            </h3>
                            <div class="small text-muted mt-2">
                                Recorded expenses during the selected period
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted small">Net Cash Result</div>
                            <h3 class="fw-bold mt-2 mb-0
                                {{ $summary['net_income'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($summary['net_income'], 2) }}
                                <small class="fs-6 text-muted">{{ $summary['currency'] }}</small>
                            </h3>
                            <div class="small text-muted mt-2">
                                Confirmed allocated collections minus recorded expenses
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <div class="d-flex justify-content-between mb-1">
                    <span class="small text-muted">Collection rate against invoices issued</span>
                    <strong>{{ number_format($summary['collection_rate'], 2) }}%</strong>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-success"
                         role="progressbar"
                         style="width: {{ min(100, max(0, $summary['collection_rate'])) }}%"
                         aria-valuenow="{{ $summary['collection_rate'] }}"
                         aria-valuemin="0"
                         aria-valuemax="100">
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    {{-- Monthly collection report --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0">Monthly Collections</h5>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Month</th>
                            <th>Currency</th>
                            <th class="text-end pe-3">Collected</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($monthlyCollections as $row)
                            <tr>
                                <td class="ps-3">
                                    {{ \Carbon\Carbon::createFromFormat('Y-m', $row->month)->format('F Y') }}
                                </td>
                                <td>{{ $row->currency }}</td>
                                <td class="text-end pe-3 fw-semibold">
                                    {{ number_format((float) $row->total, 2) }}
                                    {{ $row->currency }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                                    No confirmed invoice allocations were collected during this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Outstanding invoices --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0">Outstanding Invoices</h5>
            <small class="text-muted">Showing up to 100 invoices with an outstanding balance.</small>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Invoice</th>
                            <th>Tenant</th>
                            <th>Lease / Unit</th>
                            <th>Due Date</th>
                            <th>Currency</th>
                            <th class="text-end">Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($outstandingInvoices as $invoice)
                            @php
                                $isOverdue = $invoice->due_date
                                    && $invoice->due_date < $endDate;
                            @endphp

                            <tr>
                                <td class="ps-3 fw-semibold">
                                    {{ $invoice->invoice_number }}
                                </td>
                                <td>{{ $invoice->tenant_name ?? 'Tenant unavailable' }}</td>
                                <td>
                                    <div>{{ $invoice->lease_number ?? '—' }}</div>
                                    <small class="text-muted">
                                        Unit {{ $invoice->unit_number ?? '—' }}
                                    </small>
                                </td>
                                <td>
                                    {{ $invoice->due_date
                                        ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y')
                                        : '—' }}
                                </td>
                                <td>{{ $invoice->currency }}</td>
                                <td class="text-end fw-semibold">
                                    {{ number_format((float) $invoice->balance_due, 2) }}
                                </td>
                                <td>
                                    @if ($isOverdue)
                                        <span class="badge bg-danger">Overdue</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Outstanding</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No outstanding invoices match these filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Expense breakdown --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0">Expenses by Category</h5>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Category</th>
                            <th>Currency</th>
                            <th class="text-end pe-3">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($expenseBreakdown as $expense)
                            <tr>
                                <td class="ps-3">
                                    {{ \Illuminate\Support\Str::headline($expense->category ?: 'Uncategorized') }}
                                </td>
                                <td>{{ $expense->currency }}</td>
                                <td class="text-end pe-3 fw-semibold">
                                    {{ number_format((float) $expense->total, 2) }}
                                    {{ $expense->currency }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                                    No expenses match these filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection