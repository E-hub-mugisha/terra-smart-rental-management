@extends('layouts.app')

@section('title', 'Rent Payments')

@section('content')
@php
    $statusStyles = [
        'pending'   => 'warning',
        'confirmed' => 'success',
        'completed' => 'success',
        'failed'    => 'danger',
        'refunded'  => 'secondary',
    ];

    $statusLabels = [
        'pending'   => 'Pending',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'failed'    => 'Failed',
        'refunded'  => 'Refunded',
    ];

    $paymentMethods = [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank transfer',
        'mobile_money' => 'Mobile money',
        'card' => 'Card',
        'payment_gateway' => 'Payment gateway',
        'cheque' => 'Cheque',
        'other' => 'Other',
    ];

    $filters = [
        'status' => request('status', ''),
        'payment_method' => request('payment_method', ''),
        'search' => request('search', ''),
        'date_from' => request('date_from', ''),
        'date_to' => request('date_to', ''),
    ];
@endphp

<style>
    .payments-page {
        --terra-orange: #D05208;
        --terra-navy: #19265d;
        --terra-border: #e8ebf0;
        --terra-muted: #6b7280;
        color: #202638;
    }

    .payments-page .page-title {
        color: var(--terra-navy);
        font-weight: 750;
        letter-spacing: -.035em;
    }

    .payments-page .page-subtitle {
        color: var(--terra-muted);
        font-size: .94rem;
    }

    .payments-page .panel {
        background: #fff;
        border: 1px solid var(--terra-border);
        border-radius: 14px;
        box-shadow: 0 3px 14px rgba(25, 38, 93, .035);
    }

    .payments-page .summary-card {
        background: #fff;
        border: 1px solid var(--terra-border);
        border-radius: 13px;
        padding: 1.1rem 1.2rem;
        height: 100%;
        position: relative;
        overflow: hidden;
    }

    .payments-page .summary-label {
        color: var(--terra-muted);
        font-size: .82rem;
        font-weight: 600;
        margin-bottom: .55rem;
    }

    .payments-page .summary-value {
        color: var(--terra-navy);
        font-size: 1.55rem;
        font-weight: 750;
        letter-spacing: -.035em;
        overflow-wrap: anywhere;
    }

    .payments-page .summary-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        border-radius: 11px;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .payments-page .panel-header {
        padding: 1.15rem 1.25rem;
        border-bottom: 1px solid var(--terra-border);
    }

    .payments-page .panel-title {
        color: var(--terra-navy);
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: .2rem;
    }

    .payments-page .muted-note {
        color: var(--terra-muted);
        font-size: .82rem;
    }

    .payments-page .filter-label {
        display: block;
        color: #374151;
        font-size: .8rem;
        font-weight: 650;
        margin-bottom: .4rem;
    }

    .payments-page .form-control,
    .payments-page .form-select {
        min-height: 42px;
        border-color: #dce1e9;
        border-radius: 8px;
        font-size: .88rem;
    }

    .payments-page .form-control:focus,
    .payments-page .form-select:focus {
        border-color: var(--terra-orange);
        box-shadow: 0 0 0 .2rem rgba(208, 82, 8, .1);
    }

    .payments-page .btn-terra {
        color: #fff;
        background: var(--terra-orange);
        border-color: var(--terra-orange);
    }

    .payments-page .btn-terra:hover {
        color: #fff;
        background: #b94807;
        border-color: #b94807;
    }

    .payments-page .btn-outline-terra {
        color: var(--terra-orange);
        border-color: var(--terra-orange);
    }

    .payments-page .btn-outline-terra:hover {
        color: #fff;
        background: var(--terra-orange);
    }

    .payments-page .table {
        margin-bottom: 0;
    }

    .payments-page .table > :not(caption) > * > * {
        padding: .9rem 1rem;
        vertical-align: middle;
    }

    .payments-page .table thead th {
        background: #f8f9fc;
        color: #60697c;
        font-size: .73rem;
        font-weight: 750;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
        border-bottom: 1px solid var(--terra-border);
    }

    .payments-page .table tbody td {
        font-size: .85rem;
        border-color: #edf0f5;
    }

    .payments-page .table tbody tr:hover {
        background: #fcfcfd;
    }

    .payments-page .payment-number {
        color: var(--terra-navy);
        font-weight: 700;
        text-decoration: none;
    }

    .payments-page .payment-number:hover {
        color: var(--terra-orange);
    }

    .payments-page .tenant-name {
        color: #202638;
        font-weight: 650;
    }

    .payments-page .table-footer {
        padding: 1rem 1.25rem;
        border-top: 1px solid var(--terra-border);
    }

    .payments-page .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--terra-muted);
    }

    .payments-page .empty-state h5 {
        color: var(--terra-navy);
        font-weight: 700;
    }

    .payments-page .dropdown-menu {
        border: 1px solid var(--terra-border);
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(25, 38, 93, .1);
    }

    .payments-page .dropdown-item {
        font-size: .86rem;
        padding: .55rem .9rem;
    }

    .payments-page .dropdown-item i {
        width: 20px;
    }

    .payments-page .pagination {
        margin-bottom: 0;
    }

    .payments-page .pagination .page-link {
        color: var(--terra-navy);
        border-color: var(--terra-border);
    }

    .payments-page .pagination .active .page-link {
        color: #fff;
        background: var(--terra-orange);
        border-color: var(--terra-orange);
    }
</style>

<div class="container-fluid py-4 payments-page">

    {{-- Page heading --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="page-title h3 mb-2">Rent Payments</h1>
            <p class="page-subtitle mb-0">
                Record payments, verify received funds, and track allocations against rental invoices.
            </p>
        </div>

        <a
            href="{{ route('property-manager.payments.create') }}"
            class="btn btn-terra px-3 py-2"
        >
            <i class="bi bi-plus-lg me-2"></i>
            Record payment
        </a>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            {{ session('success') }}
            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>
        </div>
    @endif

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="summary-card">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="summary-label">Total payments</div>
                        <div class="summary-value">
                            {{ number_format((int) ($summary['all'] ?? 0)) }}
                        </div>
                        <div class="muted-note mt-2">All recorded payments</div>
                    </div>
                    <span class="summary-icon" style="background:#edf1ff;color:#19265d;">
                        <i class="bi bi-receipt"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="summary-card">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="summary-label">Pending verification</div>
                        <div class="summary-value">
                            {{ number_format((int) ($summary['pending'] ?? 0)) }}
                        </div>
                        <div class="muted-note mt-2">Awaiting confirmation</div>
                    </div>
                    <span class="summary-icon" style="background:#fff4d9;color:#986600;">
                        <i class="bi bi-clock-history"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="summary-card">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="summary-label">Confirmed payments</div>
                        <div class="summary-value">
                            {{ number_format((int) ($summary['confirmed'] ?? 0)) }}
                        </div>
                        <div class="muted-note mt-2">Verified receipts</div>
                    </div>
                    <span class="summary-icon" style="background:#e7f7ed;color:#187644;">
                        <i class="bi bi-check-circle"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="summary-card">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="summary-label">Confirmed value</div>
                        <div class="summary-value" style="font-size:1.3rem;">
                            {{ number_format((float) ($summary['confirmed_amount'] ?? 0), 2) }}
                        </div>
                        <div class="muted-note mt-2">
                            Combined amount, potentially across currencies
                        </div>
                    </div>
                    <span class="summary-icon" style="background:#fff0e8;color:#D05208;">
                        <i class="bi bi-wallet2"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Search and filters --}}
    <div class="panel mb-4">
        <div class="panel-header">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-funnel" style="color:var(--terra-orange);"></i>
                <h2 class="panel-title mb-0">Search and filters</h2>
            </div>
        </div>

        <div class="p-3 p-lg-4">
            <form method="GET" action="{{ route('property-manager.payments.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-4">
                        <label for="search" class="filter-label">
                            Search payments
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input
                                type="search"
                                id="search"
                                name="search"
                                class="form-control border-start-0 ps-0"
                                value="{{ $filters['search'] }}"
                                placeholder="Payment number, reference, tenant..."
                            >
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-2">
                        <label for="status" class="filter-label">Payment status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All statuses</option>
                            <option value="pending" @selected($filters['status'] === 'pending')>
                                Pending
                            </option>
                            <option value="confirmed" @selected($filters['status'] === 'confirmed')>
                                Confirmed
                            </option>
                            <option value="failed" @selected($filters['status'] === 'failed')>
                                Failed
                            </option>
                            <option value="refunded" @selected($filters['status'] === 'refunded')>
                                Refunded
                            </option>
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-2">
                        <label for="payment_method" class="filter-label">Payment method</label>
                        <select name="payment_method" id="payment_method" class="form-select">
                            <option value="">All methods</option>
                            @foreach ($paymentMethods as $method => $label)
                                <option
                                    value="{{ $method }}"
                                    @selected($filters['payment_method'] === $method)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="date_from" class="filter-label">From date</label>
                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            class="form-control"
                            value="{{ $filters['date_from'] }}"
                        >
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="date_to" class="filter-label">To date</label>
                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            class="form-control"
                            value="{{ $filters['date_to'] }}"
                        >
                    </div>

                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-terra">
                            <i class="bi bi-search me-1"></i>
                            Apply filters
                        </button>

                        <a
                            href="{{ route('property-manager.payments.index') }}"
                            class="btn btn-outline-secondary"
                        >
                            <i class="bi bi-arrow-counterclockwise me-1"></i>
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Payments table --}}
    <div class="panel">
        <div class="panel-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="panel-title">Payment records</h2>
                <p class="muted-note mb-0">
                    View and manage payments for properties assigned to you.
                </p>
            </div>

            <span class="badge rounded-pill text-bg-light border text-dark px-3 py-2">
                {{ number_format($payments->total()) }} record(s)
            </span>
        </div>

        @if ($payments->count())
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Payment</th>
                            <th>Tenant</th>
                            <th>Property / unit</th>
                            <th>Method</th>
                            <th>Payment date</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Allocated</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($payments as $payment)
                            @php
                                $status = $payment->status ?? 'pending';
                                $statusColor = $statusStyles[$status] ?? 'secondary';
                                $statusLabel = $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status));

                                $tenant = $payment->tenant;
                                $unit = $payment->unit;
                                $property = $unit?->building?->property;

                                $paymentCurrency = strtoupper($payment->currency ?? 'RWF');

                                $confirmedAllocated = (float) (
                                    $payment->confirmed_allocated_amount ?? 0
                                );
                            @endphp

                            <tr>
                                <td>
                                    <a
                                        href="{{ route('property-manager.payments.show', $payment->id) }}"
                                        class="payment-number"
                                    >
                                        {{ $payment->payment_number ?? 'PAY-' . $payment->id }}
                                    </a>

                                    <div class="muted-note mt-1">
                                        {{ $payment->transaction_reference ?: 'No transaction reference' }}
                                    </div>
                                </td>

                                <td>
                                    <div class="tenant-name">
                                        {{ $tenant?->full_name ?? 'Unknown tenant' }}
                                    </div>
                                    <div class="muted-note mt-1">
                                        {{ $tenant?->tenant_number ?? $tenant?->phone ?? '—' }}
                                    </div>
                                </td>

                                <td>
                                    <div class="fw-semibold">
                                        {{ $property?->name ?? 'Property unavailable' }}
                                    </div>
                                    <div class="muted-note mt-1">
                                        {{ $unit?->building?->name ? $unit->building->name . ' / ' : '' }}
                                        Unit {{ $unit?->unit_number ?? '—' }}
                                    </div>
                                </td>

                                <td>
                                    {{ $paymentMethods[$payment->payment_method ?? ''] ?? ucfirst(str_replace('_', ' ', $payment->payment_method ?? 'Unknown')) }}
                                </td>

                                <td class="text-nowrap">
                                    {{ $payment->payment_date?->format('d M Y') ?? '—' }}
                                </td>

                                <td class="text-end text-nowrap">
                                    <div class="fw-semibold">
                                        {{ $paymentCurrency }}
                                        {{ number_format((float) $payment->amount, 2) }}
                                    </div>
                                </td>

                                <td class="text-end text-nowrap">
                                    <div class="fw-semibold">
                                        {{ $paymentCurrency }}
                                        {{ number_format($confirmedAllocated, 2) }}
                                    </div>
                                    @if ($status === 'confirmed')
                                        <div class="muted-note mt-1">
                                            Remaining:
                                            {{ $paymentCurrency }}
                                            {{ number_format(max(0, (float) $payment->amount - $confirmedAllocated), 2) }}
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    <span class="badge rounded-pill text-bg-{{ $statusColor }} px-3 py-2">
                                        {{ $statusLabel }}
                                    </span>
                                </td>

                                <td class="text-end">
                                    <div class="dropdown">
                                        <button
                                            class="btn btn-sm btn-light border"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false"
                                            aria-label="Payment actions"
                                        >
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>

                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a
                                                    class="dropdown-item"
                                                    href="{{ route('property-manager.payments.show', $payment->id) }}"
                                                >
                                                    <i class="bi bi-eye"></i>
                                                    View payment
                                                </a>
                                            </li>

                                            @if ($status === 'pending')
                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-success"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#confirmPaymentModal"
                                                        data-payment-id="{{ $payment->id }}"
                                                        data-payment-number="{{ $payment->payment_number }}"
                                                        data-payment-amount="{{ $paymentCurrency }} {{ number_format((float) $payment->amount, 2) }}"
                                                    >
                                                        <i class="bi bi-check-circle"></i>
                                                        Confirm payment
                                                    </button>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="muted-note">
                        Showing
                        <strong>{{ $payments->firstItem() ?? 0 }}</strong>
                        to
                        <strong>{{ $payments->lastItem() ?? 0 }}</strong>
                        of
                        <strong>{{ $payments->total() }}</strong>
                        payments
                    </div>

                    <div>
                        {{ $payments->links() }}
                    </div>
                </div>
            </div>
        @else
            <div class="empty-state">
                <i class="bi bi-receipt-cutoff fs-1 d-block mb-3"></i>
                <h5>No payments found</h5>

                @if (array_filter($filters))
                    <p class="mb-3">
                        No payment records match your current search or filters.
                    </p>

                    <a
                        href="{{ route('property-manager.payments.index') }}"
                        class="btn btn-outline-terra"
                    >
                        Clear filters
                    </a>
                @else
                    <p class="mb-3">
                        Payments recorded for your assigned properties will appear here.
                    </p>

                    <a
                        href="{{ route('property-manager.payments.create') }}"
                        class="btn btn-terra"
                    >
                        <i class="bi bi-plus-lg me-1"></i>
                        Record your first payment
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>

{{-- Confirm payment modal --}}
<div
    class="modal fade"
    id="confirmPaymentModal"
    tabindex="-1"
    aria-labelledby="confirmPaymentModalLabel"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="confirmPaymentModalLabel">
                    Confirm payment
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>
            </div>

            <div class="modal-body">
                <p class="text-muted">
                    Confirm that you have verified receipt of this payment. Once confirmed,
                    it can be allocated to outstanding invoices.
                </p>

                <div class="p-3 rounded-3 border bg-light">
                    <div class="small text-muted mb-1">Payment reference</div>
                    <div class="fw-semibold mb-3" id="confirmPaymentNumber">—</div>

                    <div class="small text-muted mb-1">Payment amount</div>
                    <div class="fw-bold fs-5" id="confirmPaymentAmount">—</div>
                </div>

                <div class="alert alert-warning mt-3 mb-0 small">
                    Only confirm after the funds have actually been received and verified.
                </div>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-light border"
                    data-bs-dismiss="modal"
                >
                    Cancel
                </button>

                <form
                    method="POST"
                    id="confirmPaymentForm"
                    action=""
                >
                    @csrf

                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>
                        Confirm funds received
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('confirmPaymentModal');

    if (!modal) {
        return;
    }

    modal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;

        if (!trigger) {
            return;
        }

        const paymentId = trigger.getAttribute('data-payment-id');
        const paymentNumber = trigger.getAttribute('data-payment-number');
        const paymentAmount = trigger.getAttribute('data-payment-amount');

        document.getElementById('confirmPaymentNumber').textContent =
            paymentNumber || '—';

        document.getElementById('confirmPaymentAmount').textContent =
            paymentAmount || '—';

        document.getElementById('confirmPaymentForm').action =
            '{{ url('/property-manager/payments') }}/' +
            encodeURIComponent(paymentId) +
            '/confirm';
    });
});
</script>
@endsection
