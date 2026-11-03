
@extends('layouts.app')

@section('title', 'Payment Details')
@section('page_title', 'Payment Details')
@section('page_subtitle', 'Review your transaction, confirmation status and invoice allocations.')

@section('content')
@php
    $money = fn ($amount, $currency = null) =>
        ($currency ?: $rentPayment->currency ?: 'RWF')
        . ' '
        . number_format((float) $amount, 2);

    $statusDetails = match (strtolower((string) $rentPayment->status)) {
        'confirmed' => ['Confirmed', 'success', 'bi-check-circle-fill'],
        'pending' => ['Pending', 'warning', 'bi-hourglass-split'],
        'failed' => ['Failed', 'danger', 'bi-x-circle-fill'],
        'rejected' => ['Rejected', 'danger', 'bi-exclamation-circle-fill'],
        'cancelled' => ['Cancelled', 'secondary', 'bi-slash-circle'],
        default => [
            ucfirst((string) ($rentPayment->status ?: 'Unknown')),
            'secondary',
            'bi-info-circle'
        ],
    };

    [$statusLabel, $statusColor, $statusIcon] = $statusDetails;
@endphp

<style>
    .payment-detail-card {
        height: 100%;
        padding: 22px;
        border: 1px solid var(--terra-border);
        border-radius: 14px;
        background: #fff;
        box-shadow: var(--terra-shadow);
    }

    .payment-detail-label {
        margin-bottom: 7px;
        color: var(--terra-muted);
        font-size: 11px;
        font-weight: 700;
    }

    .payment-detail-value {
        color: var(--terra-navy);
        font-size: 14px;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .payment-highlight {
        padding: 26px;
        border-radius: 14px;
        background: var(--terra-navy);
        color: white;
    }

    .payment-highlight .label {
        color: rgba(255,255,255,.72);
        font-size: 12px;
    }

    .payment-highlight .amount {
        margin: 8px 0 16px;
        font-size: clamp(26px, 4vw, 36px);
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .payment-highlight .reference {
        color: rgba(255,255,255,.8);
        font-size: 12px;
        overflow-wrap: anywhere;
    }

    .allocation-item {
        padding: 14px 0;
        border-bottom: 1px solid var(--terra-border);
    }

    .allocation-item:last-child {
        border-bottom: 0;
    }
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <a href="{{ route('tenant-portal.payments.index') }}"
       class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to payments
    </a>

    <span class="badge rounded-pill text-bg-{{ $statusColor }} px-3 py-2">
        <i class="bi {{ $statusIcon }} me-1"></i>
        {{ $statusLabel }}
    </span>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="payment-detail-card">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <div class="payment-detail-label">Payment number</div>
                    <div class="payment-detail-value">
                        {{ $rentPayment->payment_number }}
                    </div>
                    <h2 class="h5 fw-bold mt-3 mb-1">Payment information</h2>
                    <p class="text-muted small mb-0">
                        Details recorded for this rental payment.
                    </p>
                </div>

                <div class="text-end">
                    <div class="payment-detail-label">Payment date</div>
                    <div class="payment-detail-value">
                        {{ $rentPayment->payment_date?->format('d M Y') ?? '—' }}
                    </div>
                </div>
            </div>

            <hr style="border-color:var(--terra-border);">

            <div class="row g-4 mt-1">
                <div class="col-6 col-md-4">
                    <div class="payment-detail-label">Payment method</div>
                    <div class="payment-detail-value">
                        {{ \Illuminate\Support\Str::headline($rentPayment->payment_method ?? 'Other') }}
                    </div>
                </div>

                <div class="col-6 col-md-4">
                    <div class="payment-detail-label">Provider</div>
                    <div class="payment-detail-value">
                        {{ $rentPayment->provider ?: '—' }}
                    </div>
                </div>

                <div class="col-6 col-md-4">
                    <div class="payment-detail-label">Currency</div>
                    <div class="payment-detail-value">
                        {{ $rentPayment->currency ?: 'RWF' }}
                    </div>
                </div>

                <div class="col-6 col-md-4">
                    <div class="payment-detail-label">Unit</div>
                    <div class="payment-detail-value">
                        {{ $rentPayment->unit?->unit_number ?? '—' }}
                    </div>
                </div>

                <div class="col-6 col-md-4">
                    <div class="payment-detail-label">Lease reference</div>
                    <div class="payment-detail-value">
                        {{ $rentPayment->lease?->lease_number ?? '—' }}
                    </div>
                </div>

                <div class="col-6 col-md-4">
                    <div class="payment-detail-label">Transaction reference</div>
                    <div class="payment-detail-value">
                        {{ $rentPayment->transaction_reference ?: '—' }}
                    </div>
                </div>
            </div>

            @if ($rentPayment->notes)
                <div class="p-3 rounded-3 mt-4" style="background:#F8F9FC;">
                    <div class="payment-detail-label">Payment notes</div>
                    <p class="mb-0">{{ $rentPayment->notes }}</p>
                </div>
            @endif

            <div class="mt-4 p-3 rounded-3"
                 style="background:#F8F9FC;">
                <div class="d-flex gap-2 align-items-start">
                    <i class="bi bi-info-circle text-secondary fs-5"></i>
                    <div>
                        <div class="fw-bold small">Payment confirmation</div>
                        <p class="text-muted small mb-0 mt-1">
                            @if ($rentPayment->status === 'confirmed')
                                This payment is confirmed in the rental management system.
                            @elseif ($rentPayment->status === 'pending')
                                This payment is awaiting confirmation. Contact your property manager if you need assistance.
                            @else
                                The current status is {{ strtolower($statusLabel) }}.
                                Contact your property manager for clarification if necessary.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="payment-detail-card mt-4">
            <h3 class="h6 fw-bold mb-1">Invoice allocation history</h3>
            <p class="text-muted small mb-3">
                See how this payment has been applied to your invoices.
            </p>

            @forelse ($rentPayment->allocations as $allocation)
                <div class="allocation-item d-flex justify-content-between align-items-start gap-3">
                    <div>
                        @if ($allocation->invoice)
                            <a href="{{ route('tenant-portal.rent-invoices.show', $allocation->invoice->id) }}"
                               class="fw-bold text-decoration-none"
                               style="color:var(--terra-navy);">
                                {{ $allocation->invoice->invoice_number }}
                            </a>

                            <div class="text-muted small mt-1">
                                Due:
                                {{ $allocation->invoice->due_date?->format('d M Y') ?? '—' }}
                            </div>
                        @else
                            <div class="fw-semibold small">Invoice allocation</div>
                            <div class="text-muted small">Invoice details unavailable</div>
                        @endif

                        @if ($allocation->allocated_at)
                            <div class="text-muted mt-1" style="font-size:11px;">
                                Allocated {{ $allocation->allocated_at->format('d M Y, H:i') }}
                            </div>
                        @endif

                        @if ($allocation->notes)
                            <div class="text-muted small mt-1">
                                {{ $allocation->notes }}
                            </div>
                        @endif
                    </div>

                    <div class="text-end">
                        <div class="fw-bold text-success text-nowrap">
                            {{ $money($allocation->amount) }}
                        </div>
                        @if ($allocation->invoice)
                            <div class="text-muted mt-1" style="font-size:11px;">
                                Invoice balance:
                                {{ $money($allocation->invoice->balance_due, $allocation->invoice->currency) }}
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-4">
                    <i class="bi bi-receipt fs-3 text-muted"></i>
                    <p class="text-muted small mt-2 mb-0">
                        No invoice allocations have been recorded for this payment.
                    </p>
                </div>
            @endforelse

            @if ($rentPayment->status === 'confirmed')
                <div class="d-flex justify-content-between align-items-center gap-3 mt-3 pt-3 border-top">
                    <div>
                        <div class="fw-bold small">Total allocated</div>
                        <div class="text-muted small">Applied to invoices</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold">
                            {{ $money($rentPayment->allocated_amount) }}
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center gap-3 mt-3">
                    <div>
                        <div class="fw-bold small">Unallocated balance</div>
                        <div class="text-muted small">Confirmed funds not yet allocated</div>
                    </div>
                    <div class="fw-bold text-nowrap">
                        {{ $money($rentPayment->unallocated_amount) }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="payment-highlight">
            <div class="label">Payment amount</div>
            <div class="amount">
                {{ $money($rentPayment->amount) }}
            </div>

            <span class="badge text-bg-{{ $statusColor }}">
                <i class="bi {{ $statusIcon }} me-1"></i>
                {{ $statusLabel }}
            </span>

            <hr class="my-4" style="border-color:rgba(255,255,255,.2);">

            <div class="label mb-2">Transaction reference</div>
            <div class="reference">
                {{ $rentPayment->transaction_reference ?: 'No reference recorded' }}
            </div>
        </div>

        @if (\Illuminate\Support\Facades\Route::has('tenant-portal.rent-invoices.index'))
            <a href="{{ route('tenant-portal.rent-invoices.index') }}"
               class="btn btn-outline-secondary w-100 mt-3 py-2">
                <i class="bi bi-receipt me-2"></i> View my invoices
            </a>
        @endif

        <div class="payment-detail-card mt-3">
            <h3 class="h6 fw-bold mb-3">Payment summary</h3>

            <div class="d-flex justify-content-between gap-3 mb-3">
                <span class="text-muted small">Amount received</span>
                <strong class="small">{{ $money($rentPayment->amount) }}</strong>
            </div>

            <div class="d-flex justify-content-between gap-3 mb-3">
                <span class="text-muted small">Allocated</span>
                <strong class="small">
                    {{ $money($rentPayment->allocated_amount) }}
                </strong>
            </div>

            <div class="d-flex justify-content-between gap-3 pt-3 border-top">
                <span class="text-muted small">Unallocated</span>
                <strong class="small">
                    {{ $money($rentPayment->unallocated_amount) }}
                </strong>
            </div>
        </div>
    </div>
</div>
@endsection
