@extends('layouts.app')

@section('title', 'Rent Invoices')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Rent Invoices</h1>
            <p class="text-muted mb-0">Monitor rent charges, due dates and outstanding balances.</p>
        </div>

        <a href="{{ route('property-manager.payments.create') }}"
           class="btn btn-terra">
            <i class="bi bi-plus-lg me-1"></i> Record Payment
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
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
            <div class="invoice-stat">
                <span>Total invoices</span>
                <strong>{{ number_format($summary['all']) }}</strong>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="invoice-stat">
                <span>Outstanding invoices</span>
                <strong>{{ number_format($summary['outstanding']) }}</strong>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="invoice-stat">
                <span>Overdue invoices</span>
                <strong>{{ number_format($summary['overdue']) }}</strong>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="invoice-stat">
                <span>Outstanding amount</span>
                <strong class="amount-value">
                    {{ number_format((float) $summary['outstanding_amount'], 0) }}
                </strong>
                <small>Across assigned properties; mixed currencies are not converted.</small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('property-manager.invoices.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4">
                        <label class="form-label">Search</label>
                        <input type="text"
                               name="search"
                               class="form-control"
                               value="{{ request('search') }}"
                               placeholder="Invoice, tenant, phone or unit">
                    </div>

                    <div class="col-md-4 col-lg-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All statuses</option>
                            @foreach(['draft', 'issued', 'partially_paid', 'paid', 'overdue', 'cancelled'] as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>
                                    {{ str($status)->replace('_', ' ')->title() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 col-lg-2">
                        <label class="form-label">Invoice type</label>
                        <select name="invoice_type" class="form-select">
                            <option value="">All types</option>
                            @foreach(['rent', 'deposit', 'service_charge', 'other'] as $type)
                                <option value="{{ $type }}" @selected(request('invoice_type') === $type)>
                                    {{ str($type)->replace('_', ' ')->title() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="form-label">Due from</label>
                        <input type="date" name="due_from" class="form-control"
                               value="{{ request('due_from') }}">
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="form-label">Due to</label>
                        <input type="date" name="due_to" class="form-control"
                               value="{{ request('due_to') }}">
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-terra" type="submit">Apply filters</button>
                        <a href="{{ route('property-manager.invoices.index') }}"
                           class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h2 class="h6 fw-bold mb-0">Invoice register</h2>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Invoice</th>
                        <th>Tenant / Unit</th>
                        <th>Property</th>
                        <th>Due date</th>
                        <th>Total</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        @php
                            $isOverdue = !in_array($invoice->status, ['paid', 'cancelled', 'draft'])
                                && $invoice->balance_due > 0
                                && $invoice->due_date
                                && \Illuminate\Support\Carbon::parse($invoice->due_date)->isPast()
                                && !\Illuminate\Support\Carbon::parse($invoice->due_date)->isToday();

                            $displayStatus = $isOverdue ? 'overdue' : $invoice->status;
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('property-manager.invoices.show', $invoice->id) }}"
                                   class="fw-semibold text-decoration-none">
                                    {{ $invoice->invoice_number }}
                                </a>
                                <div class="small text-muted">
                                    {{ str($invoice->invoice_type)->replace('_', ' ')->title() }}
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $invoice->tenant?->full_name ?? 'Tenant unavailable' }}</div>
                                <div class="small text-muted">
                                    {{ $invoice->unit?->unit_number ?? '—' }}
                                </div>
                            </td>
                            <td>
                                <div>{{ $invoice->unit?->building?->property?->name ?? '—' }}</div>
                                <div class="small text-muted">
                                    {{ $invoice->unit?->building?->name ?? '—' }}
                                </div>
                            </td>
                            <td>{{ $invoice->due_date ? \Illuminate\Support\Carbon::parse($invoice->due_date)->format('d M Y') : '—' }}</td>
                            <td class="text-nowrap">
                                {{ $invoice->currency }} {{ number_format((float) $invoice->total_amount, 2) }}
                            </td>
                            <td class="text-nowrap fw-semibold">
                                {{ $invoice->currency }} {{ number_format((float) $invoice->balance_due, 2) }}
                            </td>
                            <td>
                                <span class="status-pill status-{{ $displayStatus }}">
                                    {{ str($displayStatus)->replace('_', ' ')->title() }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('property-manager.invoices.show', $invoice->id) }}"
                                   class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="fw-semibold mb-1">No invoices found</div>
                                <p class="text-muted small mb-0">Try changing your filters or check whether invoices have been generated for active leases.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="card-footer bg-white border-0">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    .btn-terra {
        background: #D05208;
        border-color: #D05208;
        color: #fff;
    }
    .btn-terra:hover {
        background: #ad4306;
        border-color: #ad4306;
        color: #fff;
    }
    .invoice-stat {
        height: 100%;
        background: #fff;
        border: 1px solid #e9edf2;
        border-radius: 12px;
        padding: 18px;
    }
    .invoice-stat span {
        display: block;
        color: #6b7280;
        font-size: .85rem;
        margin-bottom: 8px;
    }
    .invoice-stat strong {
        display: block;
        font-size: 1.45rem;
        color: #19265d;
        overflow-wrap: anywhere;
    }
    .invoice-stat small {
        display: block;
        color: #6b7280;
        font-size: .72rem;
        margin-top: 6px;
    }
    .status-pill {
        display: inline-block;
        border-radius: 30px;
        padding: 5px 10px;
        font-size: .75rem;
        font-weight: 600;
        white-space: nowrap;
        background: #eef0f3;
        color: #4b5563;
    }
    .status-paid { background: #e3f6eb; color: #17663b; }
    .status-issued { background: #e9efff; color: #2448a5; }
    .status-partially_paid { background: #fff1d6; color: #885600; }
    .status-overdue { background: #fde8e7; color: #a12520; }
    .status-cancelled { background: #f0f0f0; color: #6b7280; }
    @media (max-width: 575px) {
        .invoice-stat { padding: 13px; }
        .invoice-stat strong { font-size: 1.1rem; }
    }
</style>
@endsection