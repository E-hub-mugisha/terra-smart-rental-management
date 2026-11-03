@extends('layouts.app')

@section('title', 'Rent Invoice Management')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="mb-1">Rent Invoice Management</h3>
            <p class="text-muted mb-0">
                Monitor tenant invoices, collections, outstanding balances, and overdue rent.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <form method="POST"
                action="{{ route('admin.rent-invoices.generate') }}"
                onsubmit="return confirm('Generate all missing monthly rent invoices for active leases up to the current month?')">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-arrow-repeat me-1"></i>
                    Generate Rent Invoices
                </button>
            </form>

            <a href="{{ route('admin.payments.index') }}"
                class="btn btn-outline-primary">
                <i class="bi bi-wallet2 me-1"></i>
                Rent Payments
            </a>
        </div>

    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show">
        {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Invoices</div>
                    <div class="fs-3 fw-bold">{{ number_format($totals['invoices']) }}</div>
                    <div class="small text-muted">All recorded invoices</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Invoiced</div>
                    <div class="fs-4 fw-bold">
                        {{ number_format($totals['invoiced'], 2) }}
                    </div>
                    <div class="small text-muted">Across all currencies</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Amount Collected</div>
                    <div class="fs-4 fw-bold text-success">
                        {{ number_format($totals['collected'], 2) }}
                    </div>
                    <div class="small text-muted">Recorded invoice payments</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Outstanding Balance</div>
                    <div class="fs-4 fw-bold text-danger">
                        {{ number_format($totals['outstanding'], 2) }}
                    </div>
                    <div class="small text-muted">
                        {{ number_format($totals['unpaid_count']) }} invoice(s) with a balance
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <a href="{{ route('admin.rent-invoices.index', ['overdue' => 'yes']) }}"
                class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Overdue Invoices</div>
                            <div class="fs-4 fw-bold text-danger">
                                {{ number_format($totals['overdue_count']) }}
                            </div>
                            <div class="small text-muted">Past due date with an outstanding balance</div>
                        </div>
                        <i class="bi bi-exclamation-circle fs-2 text-danger"></i>
                    </div>
                    <div class="card-footer bg-white border-0 pt-0">
                        <strong>{{ number_format($totals['overdue_amount'], 2) }}</strong>
                        <span class="text-muted small">outstanding across all currencies</span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6">
            <a href="{{ route('admin.rent-invoices.index', ['status' => 'paid']) }}"
                class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Paid Invoices</div>
                            <div class="fs-4 fw-bold text-success">
                                {{ number_format($totals['paid_count']) }}
                            </div>
                            <div class="small text-muted">No outstanding balance</div>
                        </div>
                        <i class="bi bi-check-circle fs-2 text-success"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Invoices</h5>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('admin.rent-invoices.index') }}"
                class="row g-3 mb-4">
                <div class="col-lg-4 col-md-6">
                    <label class="form-label">Search</label>
                    <input type="search" name="search" class="form-control"
                        value="{{ request('search') }}"
                        placeholder="Invoice, tenant, tenant number, lease">
                </div>

                <div class="col-lg-2 col-md-6">
                    <label class="form-label">Invoice Type</label>
                    <select name="invoice_type" class="form-select">
                        <option value="">All types</option>
                        @foreach([
                        'rent' => 'Rent',
                        'deposit' => 'Deposit',
                        'service_charge' => 'Service charge',
                        'other' => 'Other',
                        ] as $value => $label)
                        <option value="{{ $value }}"
                            @selected(request('invoice_type')===$value)>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach([
                        'unpaid' => 'Unpaid',
                        'partially_paid' => 'Partially paid',
                        'paid' => 'Paid',
                        ] as $value => $label)
                        <option value="{{ $value }}"
                            @selected(request('status')===$value)>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-6">
                    <label class="form-label">Overdue</label>
                    <select name="overdue" class="form-select">
                        <option value="">All</option>
                        <option value="yes" @selected(request('overdue')==='yes' )>
                            Overdue only
                        </option>
                        <option value="no" @selected(request('overdue')==='no' )>
                            Not overdue
                        </option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-6">
                    <label class="form-label">Currency</label>
                    <select name="currency" class="form-select">
                        <option value="">All currencies</option>
                        @foreach(['RWF', 'USD'] as $currency)
                        <option value="{{ $currency }}"
                            @selected(request('currency')===$currency)>
                            {{ $currency }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Issued from</label>
                    <input type="date" name="from_date" class="form-control"
                        value="{{ request('from_date') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Issued to</label>
                    <input type="date" name="to_date" class="form-control"
                        value="{{ request('to_date') }}">
                </div>

                <div class="col-md-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i> Apply Filters
                    </button>
                    <a href="{{ route('admin.rent-invoices.index') }}"
                        class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice</th>
                            <th>Tenant</th>
                            <th>Lease / Unit</th>
                            <th>Due Date</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                        @php
                        $balance = (float) $invoice->balance_due;
                        $overdue = $balance > 0
                        && $invoice->due_date
                        && $invoice->due_date->lt(today());

                        $statusLabel = $overdue
                        ? 'Overdue'
                        : ucfirst(str_replace('_', ' ', $invoice->status));

                        $statusClass = $overdue
                        ? 'danger'
                        : match($invoice->status) {
                        'paid' => 'success',
                        'partially_paid' => 'info',
                        'unpaid' => 'warning',
                        default => 'secondary',
                        };
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $invoice->invoice_number }}</strong>
                                <div class="small text-muted">
                                    {{ ucfirst(str_replace('_', ' ', $invoice->invoice_type)) }}
                                </div>
                                <div class="small text-muted">
                                    Issued {{ $invoice->issue_date?->format('d M Y') ?? '—' }}
                                </div>
                            </td>
                            <td>
                                {{ $invoice->tenant?->full_name ?? '—' }}
                                <div class="small text-muted">
                                    {{ $invoice->tenant?->tenant_number }}
                                </div>
                            </td>
                            <td>
                                {{ $invoice->lease?->lease_number ?? '—' }}
                                <div class="small text-muted">
                                    Unit {{ $invoice->unit?->unit_number ?? '—' }}
                                </div>
                            </td>
                            <td class="{{ $overdue ? 'text-danger fw-semibold' : '' }}">
                                {{ $invoice->due_date?->format('d M Y') ?? '—' }}
                                @if($overdue)
                                <div class="small">Past due</div>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                {{ $invoice->currency }}
                                {{ number_format((float)$invoice->total_amount, 2) }}
                            </td>
                            <td class="text-nowrap text-success">
                                {{ $invoice->currency }}
                                {{ number_format((float)$invoice->amount_paid, 2) }}
                            </td>
                            <td class="text-nowrap {{ $balance > 0 ? 'text-danger fw-semibold' : '' }}">
                                {{ $invoice->currency }}
                                {{ number_format($balance, 2) }}
                            </td>
                            <td>
                                <span class="badge text-bg-{{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.rent-invoices.show', $invoice) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="bi bi-receipt fs-2 d-block mb-2"></i>
                                No invoices match your filters.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="small text-muted">
                    Showing {{ $invoices->firstItem() ?? 0 }}
                    to {{ $invoices->lastItem() ?? 0 }}
                    of {{ $invoices->total() }} invoices
                </div>
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</div>
@endsection