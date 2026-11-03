@extends('layouts.app')

@section('title', 'Payment Details')

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
        'pending'   => 'Pending verification',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'failed'    => 'Failed',
        'refunded'  => 'Refunded',
    ];

    $paymentStatus = $payment->status ?? 'pending';
    $statusColor = $statusStyles[$paymentStatus] ?? 'secondary';
    $statusLabel = $statusLabels[$paymentStatus] ?? ucfirst(str_replace('_', ' ', $paymentStatus));

    $currency = strtoupper($payment->currency ?? 'RWF');
    $paymentAmount = (float) $payment->amount;
    $allocatedAmount = (float) ($allocatedAmount ?? 0);
    $availableAmount = (float) ($availableAmount ?? 0);

    $formatMoney = function ($amount) use ($currency) {
        return $currency . ' ' . number_format((float) $amount, 2);
    };

    $paymentMethodLabels = [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank transfer',
        'mobile_money' => 'Mobile money',
        'card' => 'Card',
        'payment_gateway' => 'Payment gateway',
        'cheque' => 'Cheque',
        'other' => 'Other',
    ];

    $paymentMethod = $paymentMethodLabels[$payment->payment_method ?? ''] 
        ?? ucfirst(str_replace('_', ' ', $payment->payment_method ?? 'Unknown'));

    $tenant = $payment->tenant;
    $lease = $payment->lease;
    $unit = $payment->unit;
    $property = $unit?->building?->property;

    $outstandingInvoices = $invoices ?? collect();
@endphp

<style>
    .payment-page {
        --terra-orange: #D05208;
        --terra-navy: #19265d;
        --terra-border: #e8ebf0;
        --terra-muted: #6b7280;
        color: #202638;
    }

    .payment-page .page-title {
        color: var(--terra-navy);
        font-weight: 750;
        letter-spacing: -0.035em;
    }

    .payment-page .page-subtitle {
        color: var(--terra-muted);
        font-size: .94rem;
    }

    .payment-page .panel {
        background: #fff;
        border: 1px solid var(--terra-border);
        border-radius: 14px;
        box-shadow: 0 3px 14px rgba(25, 38, 93, .035);
    }

    .payment-page .panel-header {
        padding: 1.1rem 1.25rem;
        border-bottom: 1px solid var(--terra-border);
    }

    .payment-page .panel-body {
        padding: 1.25rem;
    }

    .payment-page .section-title {
        color: var(--terra-navy);
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: .2rem;
    }

    .payment-page .detail-label {
        display: block;
        color: var(--terra-muted);
        font-size: .78rem;
        font-weight: 600;
        margin-bottom: .35rem;
    }

    .payment-page .detail-value {
        color: #202638;
        font-size: .94rem;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .payment-page .summary-card {
        height: 100%;
        padding: 1.1rem;
        border: 1px solid var(--terra-border);
        border-radius: 12px;
        background: #fff;
    }

    .payment-page .summary-card .summary-label {
        color: var(--terra-muted);
        font-size: .8rem;
        font-weight: 600;
        margin-bottom: .55rem;
    }

    .payment-page .summary-card .summary-value {
        color: var(--terra-navy);
        font-size: clamp(1.05rem, 2vw, 1.4rem);
        font-weight: 750;
        letter-spacing: -.025em;
        overflow-wrap: anywhere;
    }

    .payment-page .btn-terra {
        color: #fff;
        background: var(--terra-orange);
        border-color: var(--terra-orange);
    }

    .payment-page .btn-terra:hover,
    .payment-page .btn-terra:focus {
        color: #fff;
        background: #b94807;
        border-color: #b94807;
    }

    .payment-page .btn-outline-terra {
        color: var(--terra-orange);
        border-color: var(--terra-orange);
    }

    .payment-page .btn-outline-terra:hover {
        color: #fff;
        background: var(--terra-orange);
    }

    .payment-page .table > :not(caption) > * > * {
        padding: .85rem .9rem;
        vertical-align: middle;
    }

    .payment-page .table thead th {
        background: #f8f9fc;
        color: #60697c;
        font-size: .75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .035em;
        white-space: nowrap;
        border-bottom: 1px solid var(--terra-border);
    }

    .payment-page .table tbody td {
        font-size: .87rem;
        border-color: #edf0f5;
    }

    .payment-page .form-label {
        color: #374151;
        font-size: .84rem;
        font-weight: 650;
    }

    .payment-page .form-control,
    .payment-page .form-select {
        min-height: 43px;
        border-color: #dce1e9;
        border-radius: 8px;
    }

    .payment-page .form-control:focus,
    .payment-page .form-select:focus {
        border-color: var(--terra-orange);
        box-shadow: 0 0 0 .2rem rgba(208, 82, 8, .1);
    }

    .payment-page .muted-note {
        color: var(--terra-muted);
        font-size: .82rem;
    }

    .payment-page .info-block {
        padding: .95rem 1rem;
        border-radius: 10px;
        background: #f8f9fc;
        border: 1px solid #edf0f5;
    }

    .payment-page .empty-state {
        padding: 2rem 1rem;
        color: var(--terra-muted);
        text-align: center;
    }

    .payment-page .empty-state h6 {
        color: var(--terra-navy);
        font-weight: 700;
    }
</style>

<div class="container-fluid py-4 payment-page">

    {{-- Page header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <a
                href="{{ route('property-manager.payments.index') }}"
                class="text-decoration-none small d-inline-flex align-items-center gap-2 mb-3"
                style="color: var(--terra-orange);"
            >
                <i class="bi bi-arrow-left"></i>
                Back to payments
            </a>

            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <h1 class="page-title h3 mb-0">Payment Details</h1>

                <span class="badge rounded-pill text-bg-{{ $statusColor }} px-3 py-2">
                    {{ $statusLabel }}
                </span>
            </div>

            <p class="page-subtitle mb-0">
                Review the payment, verify receipt of funds, and allocate confirmed funds to invoices.
            </p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <a
                href="{{ route('property-manager.payments.index') }}"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-list-ul me-1"></i>
                All payments
            </a>

            @if ($paymentStatus === 'pending')
                <form
                    method="POST"
                    action="{{ route('property-manager.payments.confirm', $payment->id) }}"
                    onsubmit="return confirm('Have you verified that the funds were received? Confirm this payment only after verification.');"
                >
                    @csrf

                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>
                        Confirm payment
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="fw-semibold mb-1">
                Please correct the following:
            </div>

            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>
        </div>
    @endif

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

    {{-- Payment summary --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="summary-card">
                <div class="summary-label">Payment amount</div>
                <div class="summary-value">
                    {{ $formatMoney($paymentAmount) }}
                </div>
                <div class="muted-note mt-2">Original recorded amount</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="summary-card">
                <div class="summary-label">Allocated amount</div>
                <div class="summary-value text-success">
                    {{ $formatMoney($allocatedAmount) }}
                </div>
                <div class="muted-note mt-2">Allocated to invoices from this payment</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="summary-card">
                <div class="summary-label">Available to allocate</div>
                <div class="summary-value">
                    {{ $formatMoney($availableAmount) }}
                </div>
                <div class="muted-note mt-2">Remaining confirmed funds</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="summary-card">
                <div class="summary-label">Payment reference</div>
                <div class="summary-value">
                    {{ $payment->payment_number ?? '—' }}
                </div>
                <div class="muted-note mt-2">
                    {{ $payment->payment_date?->format('d M Y') ?? 'Date not available' }}
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Main column --}}
        <div class="col-12 col-xl-8">

            {{-- Payment information --}}
            <div class="panel mb-4">
                <div class="panel-header d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <h2 class="section-title mb-1">Payment information</h2>
                        <p class="muted-note mb-0">
                            Recorded transaction details and verification status.
                        </p>
                    </div>

                    <i class="bi bi-receipt fs-4" style="color: var(--terra-orange);"></i>
                </div>

                <div class="panel-body">
                    <div class="row g-4">
                        <div class="col-12 col-sm-6">
                            <span class="detail-label">Payment number</span>
                            <div class="detail-value">
                                {{ $payment->payment_number ?? '—' }}
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="detail-label">Status</span>
                            <div class="detail-value">
                                <span class="badge text-bg-{{ $statusColor }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="detail-label">Payment method</span>
                            <div class="detail-value">{{ $paymentMethod }}</div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="detail-label">Payment date</span>
                            <div class="detail-value">
                                {{ $payment->payment_date?->format('d M Y') ?? '—' }}
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="detail-label">Transaction reference</span>
                            <div class="detail-value">
                                {{ $payment->transaction_reference ?: 'Not provided' }}
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="detail-label">Payment provider</span>
                            <div class="detail-value">
                                {{ $payment->provider ?: 'Not specified' }}
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="detail-label">Currency</span>
                            <div class="detail-value">{{ $currency }}</div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="detail-label">Recorded by</span>
                            <div class="detail-value">
                                {{ $payment->recorder?->name ?? '—' }}
                            </div>
                        </div>

                        <div class="col-12">
                            <span class="detail-label">Notes</span>
                            <div class="detail-value fw-normal">
                                {{ $payment->notes ?: 'No notes were provided.' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tenant and lease --}}
            <div class="panel mb-4">
                <div class="panel-header">
                    <h2 class="section-title">Tenant and rental details</h2>
                    <p class="muted-note mb-0">
                        The tenant, lease, and unit associated with this payment.
                    </p>
                </div>

                <div class="panel-body">
                    <div class="row g-4">
                        <div class="col-12 col-sm-6">
                            <span class="detail-label">Tenant</span>
                            <div class="detail-value">
                                {{ $tenant?->full_name ?? '—' }}
                            </div>
                            @if ($tenant?->tenant_number)
                                <div class="muted-note mt-1">
                                    {{ $tenant->tenant_number }}
                                </div>
                            @endif
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="detail-label">Tenant email</span>
                            <div class="detail-value">
                                {{ $tenant?->email ?? '—' }}
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="detail-label">Tenant phone</span>
                            <div class="detail-value">
                                {{ $tenant?->phone ?? '—' }}
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="detail-label">Lease number</span>
                            <div class="detail-value">
                                {{ $lease?->lease_number ?? '—' }}
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="detail-label">Property</span>
                            <div class="detail-value">
                                {{ $property?->name ?? '—' }}
                            </div>
                            @if ($property?->property_code)
                                <div class="muted-note mt-1">
                                    {{ $property->property_code }}
                                </div>
                            @endif
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="detail-label">Building</span>
                            <div class="detail-value">
                                {{ $unit?->building?->name ?? '—' }}
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="detail-label">Unit</span>
                            <div class="detail-value">
                                {{ $unit?->unit_number ?? '—' }}
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="detail-label">Lease period</span>
                            <div class="detail-value">
                                @if ($lease?->start_date)
                                    {{ $lease->start_date->format('d M Y') }}
                                    —
                                    {{ $lease->end_date?->format('d M Y') ?? 'Open-ended' }}
                                @else
                                    —
                                @endif
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="detail-label">Monthly rent</span>
                            <div class="detail-value">
                                {{ $lease ? $formatMoney($lease->monthly_rent) : '—' }}
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="detail-label">Lease status</span>
                            <div class="detail-value">
                                @if ($lease)
                                    <span class="badge text-bg-light border text-dark">
                                        {{ ucfirst(str_replace('_', ' ', $lease->status)) }}
                                    </span>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Allocation history --}}
            <div class="panel mb-4">
                <div class="panel-header d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <h2 class="section-title">Allocation history</h2>
                        <p class="muted-note mb-0">
                            Invoices paid using this payment.
                        </p>
                    </div>

                    <span class="badge rounded-pill text-bg-light border text-dark">
                        {{ $payment->allocations->count() }} allocation(s)
                    </span>
                </div>

                @if ($payment->allocations->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Allocated date</th>
                                    <th class="text-end">Amount</th>
                                    <th>Invoice status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payment->allocations as $allocation)
                                    @php
                                        $invoice = $allocation->invoice;
                                        $invoiceStatus = $invoice?->status ?? 'unknown';

                                        $invoiceStatusColor = match ($invoiceStatus) {
                                            'paid' => 'success',
                                            'partially_paid' => 'warning',
                                            'overdue' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp

                                    <tr>
                                        <td>
                                            <div class="fw-semibold">
                                                {{ $invoice?->invoice_number ?? 'Invoice unavailable' }}
                                            </div>
                                            @if ($invoice)
                                                <div class="muted-note">
                                                    {{ ucfirst(str_replace('_', ' ', $invoice->invoice_type ?? 'invoice')) }}
                                                </div>
                                            @endif
                                        </td>

                                        <td>
                                            {{ $allocation->allocated_at
                                                ? \Illuminate\Support\Carbon::parse($allocation->allocated_at)->format('d M Y')
                                                : '—' }}
                                        </td>

                                        <td class="text-end fw-semibold">
                                            {{ $formatMoney($allocation->amount) }}
                                        </td>

                                        <td>
                                            <span class="badge text-bg-{{ $invoiceStatusColor }}">
                                                {{ ucfirst(str_replace('_', ' ', $invoiceStatus)) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bi bi-journal-text fs-2 d-block mb-2"></i>
                        <h6>No allocations yet</h6>
                        <p class="mb-0 small">
                            @if ($paymentStatus === 'confirmed')
                                Allocate this payment to an outstanding invoice when ready.
                            @else
                                Confirm this payment after verifying receipt of funds before allocating it.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-12 col-xl-4">

            {{-- Verification guidance --}}
            <div class="panel mb-4">
                <div class="panel-header">
                    <h2 class="section-title">Payment workflow</h2>
                    <p class="muted-note mb-0">Current processing stage</p>
                </div>

                <div class="panel-body">
                    <div class="d-flex gap-3 mb-4">
                        <div>
                            <i class="bi bi-1-circle-fill fs-4"
                               style="color: {{ in_array($paymentStatus, ['confirmed', 'completed']) ? '#198754' : '#D05208' }};"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">Payment recorded</div>
                            <div class="muted-note">
                                Payment details have been saved in the system.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mb-4">
                        <div>
                            <i class="bi bi-2-circle-fill fs-4"
                               style="color: {{ in_array($paymentStatus, ['confirmed', 'completed']) ? '#198754' : '#9ca3af' }};"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">Verify and confirm</div>
                            <div class="muted-note">
                                Verify receipt of funds before confirming the payment.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <div>
                            <i class="bi bi-3-circle-fill fs-4"
                               style="color: {{ $allocatedAmount > 0 ? '#198754' : '#9ca3af' }};"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">Allocate to invoices</div>
                            <div class="muted-note">
                                Allocate confirmed funds against eligible outstanding invoices.
                            </div>
                        </div>
                    </div>

                    @if ($paymentStatus === 'pending')
                        <div class="alert alert-warning mt-4 mb-0 small">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Do not confirm until the money has been received and verified.
                        </div>
                    @elseif ($paymentStatus === 'confirmed' && $availableAmount > 0)
                        <div class="alert alert-info mt-4 mb-0 small">
                            This payment has {{ $formatMoney($availableAmount) }} available for allocation.
                        </div>
                    @elseif ($paymentStatus === 'confirmed' && $availableAmount <= 0)
                        <div class="alert alert-success mt-4 mb-0 small">
                            No funds remain available for allocation.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Allocate payment --}}
            @if ($paymentStatus === 'confirmed' && $availableAmount > 0)
                <div class="panel mb-4">
                    <div class="panel-header">
                        <h2 class="section-title">Allocate payment</h2>
                        <p class="muted-note mb-0">
                            Apply confirmed funds to an outstanding invoice.
                        </p>
                    </div>

                    <div class="panel-body">
                        @if ($outstandingInvoices->isNotEmpty())
                            <form
                                method="POST"
                                action="{{ route('property-manager.payments.allocate', $payment->id) }}"
                            >
                                @csrf

                                <div class="mb-3">
                                    <label for="rent_invoice_id" class="form-label">
                                        Outstanding invoice
                                    </label>

                                    <select
                                        id="rent_invoice_id"
                                        name="rent_invoice_id"
                                        class="form-select @error('rent_invoice_id') is-invalid @enderror"
                                        required
                                    >
                                        <option value="">Select an invoice</option>

                                        @foreach ($outstandingInvoices as $invoice)
                                            @php
                                                $invoiceBalance = (float) $invoice->balance_due;
                                            @endphp

                                            <option
                                                value="{{ $invoice->id }}"
                                                data-balance="{{ number_format($invoiceBalance, 2, '.', '') }}"
                                                @selected((string) old('rent_invoice_id') === (string) $invoice->id)
                                            >
                                                {{ $invoice->invoice_number }}
                                                — {{ $formatMoney($invoiceBalance) }} due
                                                — {{ ucfirst(str_replace('_', ' ', $invoice->invoice_type ?? 'invoice')) }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('rent_invoice_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="amount" class="form-label">
                                        Allocation amount ({{ $currency }})
                                    </label>

                                    <input
                                        type="number"
                                        name="amount"
                                        id="amount"
                                        class="form-control @error('amount') is-invalid @enderror"
                                        min="0.01"
                                        max="{{ number_format($availableAmount, 2, '.', '') }}"
                                        step="0.01"
                                        value="{{ old('amount') }}"
                                        placeholder="Enter amount"
                                        required
                                    >

                                    <div class="form-text">
                                        Maximum available: {{ $formatMoney($availableAmount) }}
                                    </div>

                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="allocated_at" class="form-label">
                                        Allocation date
                                    </label>

                                    <input
                                        type="date"
                                        name="allocated_at"
                                        id="allocated_at"
                                        class="form-control @error('allocated_at') is-invalid @enderror"
                                        value="{{ old('allocated_at', now()->toDateString()) }}"
                                        max="{{ now()->toDateString() }}"
                                        required
                                    >

                                    @error('allocated_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="allocation_notes" class="form-label">
                                        Notes <span class="text-muted fw-normal">(optional)</span>
                                    </label>

                                    <textarea
                                        name="notes"
                                        id="allocation_notes"
                                        rows="3"
                                        class="form-control @error('notes') is-invalid @enderror"
                                        placeholder="Add allocation details"
                                    >{{ old('notes') }}</textarea>

                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="info-block mb-3">
                                    <div class="d-flex justify-content-between gap-2 mb-2">
                                        <span class="muted-note">Available payment</span>
                                        <strong>{{ $formatMoney($availableAmount) }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between gap-2">
                                        <span class="muted-note">Selected invoice balance</span>
                                        <strong id="selected-invoice-balance">Select an invoice</strong>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-terra w-100">
                                    <i class="bi bi-check2-square me-1"></i>
                                    Allocate payment
                                </button>
                            </form>
                        @else
                            <div class="empty-state py-3">
                                <i class="bi bi-check-circle fs-2 d-block mb-2"></i>
                                <h6>No eligible invoices</h6>
                                <p class="small mb-0">
                                    There are no outstanding invoices matching this payment's tenant, lease, unit, and currency.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Allocation totals --}}
            <div class="panel">
                <div class="panel-header">
                    <h2 class="section-title">Allocation summary</h2>
                </div>

                <div class="panel-body">
                    <div class="d-flex justify-content-between gap-3 mb-3">
                        <span class="muted-note">Payment amount</span>
                        <strong>{{ $formatMoney($paymentAmount) }}</strong>
                    </div>

                    <div class="d-flex justify-content-between gap-3 mb-3">
                        <span class="muted-note">Allocated</span>
                        <strong class="text-success">
                            {{ $formatMoney($allocatedAmount) }}
                        </strong>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between gap-3">
                        <span class="fw-semibold">Available balance</span>
                        <strong style="color: var(--terra-navy);">
                            {{ $formatMoney($availableAmount) }}
                        </strong>
                    </div>

                    @if ($paymentStatus !== 'confirmed')
                        <p class="muted-note mt-3 mb-0">
                            The payment must be confirmed before its funds can be allocated.
                        </p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const invoiceSelect = document.getElementById('rent_invoice_id');
    const amountInput = document.getElementById('amount');
    const invoiceBalanceLabel = document.getElementById('selected-invoice-balance');

    if (!invoiceSelect || !amountInput || !invoiceBalanceLabel) {
        return;
    }

    const availableAmount = Number(amountInput.max || 0);

    function updateInvoiceBalance() {
        const selectedOption = invoiceSelect.options[invoiceSelect.selectedIndex];

        if (!selectedOption || !selectedOption.value) {
            invoiceBalanceLabel.textContent = 'Select an invoice';
            amountInput.removeAttribute('max');
            return;
        }

        const invoiceBalance = Number(selectedOption.dataset.balance || 0);
        const maxAllocation = Math.min(invoiceBalance, availableAmount);

        invoiceBalanceLabel.textContent = invoiceSelect.value
            ? invoiceBalance.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
            : 'Select an invoice';

        amountInput.max = maxAllocation.toFixed(2);
    }

    invoiceSelect.addEventListener('change', updateInvoiceBalance);

    updateInvoiceBalance();
});
</script>
@endsection
