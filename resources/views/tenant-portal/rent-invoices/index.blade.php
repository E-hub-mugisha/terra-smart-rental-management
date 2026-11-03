
@extends('layouts.app')

@section('title', 'My Rent Invoices')
@section('page_title', 'My Rent Invoices')
@section('page_subtitle', 'View your rent bills, due dates and outstanding balances.')

@section('content')
@php
    $money = fn ($amount, $currency = 'RWF') =>
        ($currency ?: 'RWF') . ' ' . number_format((float) $amount, 0);

    $statusFor = function ($invoice) {
        if ((float) $invoice->balance_due <= 0) {
            return ['Paid', 'success'];
        }

        if ($invoice->is_overdue) {
            return ['Overdue', 'danger'];
        }

        if ((float) $invoice->amount_paid > 0) {
            return ['Partially Paid', 'warning'];
        }

        return ['Unpaid', 'secondary'];
    };
@endphp

<style>
    .invoice-page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 24px;
    }

    .invoice-stat {
        height: 100%;
        padding: 20px;
        border: 1px solid var(--terra-border);
        border-radius: 14px;
        background: #fff;
        box-shadow: var(--terra-shadow);
    }

    .invoice-stat-label {
        color: var(--terra-muted);
        font-size: 12px;
        font-weight: 600;
    }

    .invoice-stat-value {
        margin-top: 10px;
        color: var(--terra-navy);
        font-size: clamp(20px, 2vw, 27px);
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .invoice-stat-icon {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: var(--terra-orange-light);
        color: var(--terra-orange);
        font-size: 19px;
    }

    .invoice-panel {
        overflow: hidden;
        border: 1px solid var(--terra-border);
        border-radius: 14px;
        background: #fff;
        box-shadow: var(--terra-shadow);
    }

    .invoice-table th {
        padding: 14px 16px;
        background: #F8F9FC;
        color: #778196;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .6px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .invoice-table td {
        padding: 16px;
        border-color: #EEF0F5;
        vertical-align: middle;
        font-size: 12px;
    }

    .invoice-number {
        color: var(--terra-navy);
        font-weight: 800;
    }

    .invoice-meta {
        margin-top: 4px;
        color: var(--terra-muted);
        font-size: 11px;
    }

    .invoice-balance {
        color: var(--terra-navy);
        font-weight: 800;
        white-space: nowrap;
    }

    .invoice-filter .form-control,
    .invoice-filter .form-select {
        min-height: 42px;
        border-color: var(--terra-border);
        border-radius: 9px;
        font-size: 12px;
    }

    .invoice-filter .btn {
        min-height: 42px;
        border-radius: 9px;
        font-size: 12px;
        font-weight: 700;
    }

    .invoice-empty {
        padding: 60px 20px;
        text-align: center;
    }

    .invoice-empty-icon {
        width: 64px;
        height: 64px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
        background: var(--terra-orange-light);
        color: var(--terra-orange);
        font-size: 28px;
    }
</style>

<div class="invoice-page-header">
    <div>
        <h2 class="h5 fw-bold mb-1">Invoice overview</h2>
        <p class="text-muted mb-0 small">
            Keep track of your rental charges and payment status.
        </p>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        @if (\Illuminate\Support\Facades\Route::has('tenant-portal.payments.index'))
            <a href="{{ route('tenant-portal.payments.index') }}"
               class="btn btn-outline-secondary btn-sm px-3 py-2">
                <i class="bi bi-cash-stack me-1"></i> My Payments
            </a>
        @endif
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="invoice-stat">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <span class="invoice-stat-label">Total Invoices</span>
                <span class="invoice-stat-icon"><i class="bi bi-receipt"></i></span>
            </div>
            <div class="invoice-stat-value">{{ number_format($summary['total']) }}</div>
            <div class="invoice-meta">All issued invoices</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="invoice-stat">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <span class="invoice-stat-label">Outstanding</span>
                <span class="invoice-stat-icon"><i class="bi bi-wallet2"></i></span>
            </div>
            <div class="invoice-stat-value" style="color: var(--terra-orange);">
                {{ number_format($summary['outstanding'], 0) }}
            </div>
            <div class="invoice-meta">Amount still due</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="invoice-stat">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <span class="invoice-stat-label">Payments Recorded</span>
                <span class="invoice-stat-icon"><i class="bi bi-check2-circle"></i></span>
            </div>
            <div class="invoice-stat-value">{{ number_format($summary['paid'], 0) }}</div>
            <div class="invoice-meta">Total allocated payments</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="invoice-stat">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <span class="invoice-stat-label">Overdue Invoices</span>
                <span class="invoice-stat-icon"><i class="bi bi-exclamation-circle"></i></span>
            </div>
            <div class="invoice-stat-value"
                 style="{{ $summary['overdue'] > 0 ? 'color:#C0392B;' : '' }}">
                {{ number_format($summary['overdue']) }}
            </div>
            <div class="invoice-meta">Past their due date</div>
        </div>
    </div>
</div>

<div class="invoice-panel">
    <div class="p-3 p-lg-4 border-bottom">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h3 class="h6 fw-bold mb-1">Invoice history</h3>
                <p class="text-muted small mb-0">Search and filter your invoices.</p>
            </div>
            <span class="badge text-bg-light border">
                {{ $invoices->total() }} records
            </span>
        </div>

        <form method="GET"
              action="{{ route('tenant-portal.rent-invoices.index') }}"
              class="row g-2 invoice-filter">
            <div class="col-12 col-md-6 col-lg-7">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="search"
                           name="search"
                           class="form-control border-start-0"
                           value="{{ request('search') }}"
                           placeholder="Search invoice number, unit or description">
                </div>
            </div>

            <div class="col-8 col-md-4 col-lg-3">
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    <option value="unpaid" @selected(request('status') === 'unpaid')>Unpaid</option>
                    <option value="partial" @selected(request('status') === 'partial')>Partially paid</option>
                    <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                    <option value="overdue" @selected(request('status') === 'overdue')>Overdue</option>
                </select>
            </div>

            <div class="col-4 col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    Filter
                </button>
            </div>

            @if (request()->filled('search') || request()->filled('status'))
                <div class="col-12">
                    <a href="{{ route('tenant-portal.rent-invoices.index') }}"
                       class="small text-decoration-none">
                        <i class="bi bi-x-circle me-1"></i>Clear filters
                    </a>
                </div>
            @endif
        </form>
    </div>

    @if ($invoices->count())
        <div class="table-responsive">
            <table class="table invoice-table mb-0">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Billing period</th>
                        <th>Due date</th>
                        <th>Total</th>
                        <th>Balance due</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoices as $invoice)
                        @php
                            [$statusLabel, $statusColor] = $statusFor($invoice);
                        @endphp

                        <tr>
                            <td>
                                <a href="{{ route('tenant-portal.rent-invoices.show', $invoice->id) }}"
                                   class="invoice-number">
                                    {{ $invoice->invoice_number }}
                                </a>
                                <div class="invoice-meta">
                                    {{ \Illuminate\Support\Str::headline($invoice->invoice_type ?? 'rent') }}
                                    @if ($invoice->unit)
                                        · Unit {{ $invoice->unit->unit_number }}
                                    @endif
                                </div>
                            </td>

                            <td>
                                @if ($invoice->billing_period_start)
                                    {{ $invoice->billing_period_start->format('d M Y') }}
                                @else
                                    —
                                @endif
                                <div class="invoice-meta">
                                    to {{ $invoice->billing_period_end?->format('d M Y') ?? '—' }}
                                </div>
                            </td>

                            <td>
                                {{ $invoice->due_date?->format('d M Y') ?? '—' }}
                                @if ($invoice->is_overdue)
                                    <div class="invoice-meta text-danger">
                                        <i class="bi bi-exclamation-circle me-1"></i>Past due
                                    </div>
                                @endif
                            </td>

                            <td class="fw-semibold">
                                {{ $money($invoice->total_amount, $invoice->currency) }}
                            </td>

                            <td class="invoice-balance">
                                {{ $money($invoice->balance_due, $invoice->currency) }}
                            </td>

                            <td>
                                <span class="badge rounded-pill text-bg-{{ $statusColor }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            <td class="text-end">
                                <a href="{{ route('tenant-portal.rent-invoices.show', $invoice->id) }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    View <i class="bi bi-arrow-up-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 p-3 border-top">
            <span class="small text-muted">
                Showing {{ $invoices->firstItem() }}–{{ $invoices->lastItem() }}
                of {{ $invoices->total() }} invoices
            </span>
            {{ $invoices->links() }}
        </div>
    @else
        <div class="invoice-empty">
            <div class="invoice-empty-icon mb-3">
                <i class="bi bi-receipt-cutoff"></i>
            </div>
            <h3 class="h6 fw-bold">No invoices found</h3>
            <p class="text-muted small mb-3">
                There are no invoices matching your search or filter.
            </p>

            @if (request()->filled('search') || request()->filled('status'))
                <a href="{{ route('tenant-portal.rent-invoices.index') }}"
                   class="btn btn-outline-secondary btn-sm">
                    Clear filters
                </a>
            @endif
        </div>
    @endif
</div>
@endsection
