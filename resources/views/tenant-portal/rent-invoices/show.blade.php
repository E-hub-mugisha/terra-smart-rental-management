
@extends('layouts.app')

@section('title', 'Invoice Details')
@section('page_title', 'Invoice Details')
@section('page_subtitle', 'Review your invoice charges, due date and payment history.')

@section('content')
@php
    $money = fn ($amount) =>
        ($rentInvoice->currency ?: 'RWF') . ' ' . number_format((float) $amount, 2);

    if ((float) $rentInvoice->balance_due <= 0) {
        $statusLabel = 'Paid';
        $statusColor = 'success';
    } elseif ($rentInvoice->is_overdue) {
        $statusLabel = 'Overdue';
        $statusColor = 'danger';
    } elseif ((float) $rentInvoice->amount_paid > 0) {
        $statusLabel = 'Partially Paid';
        $statusColor = 'warning';
    } else {
        $statusLabel = 'Unpaid';
        $statusColor = 'secondary';
    }
@endphp

<style>
    .invoice-detail-card {
        height: 100%;
        padding: 22px;
        border: 1px solid var(--terra-border);
        border-radius: 14px;
        background: #fff;
        box-shadow: var(--terra-shadow);
    }

    .invoice-detail-label {
        margin-bottom: 7px;
        color: var(--terra-muted);
        font-size: 11px;
        font-weight: 700;
    }

    .invoice-detail-value {
        color: var(--terra-navy);
        font-size: 14px;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .invoice-total-panel {
        padding: 24px;
        border-radius: 14px;
        background: var(--terra-navy);
        color: #fff;
    }

    .invoice-total-panel .label {
        color: rgba(255,255,255,.72);
        font-size: 12px;
    }

    .invoice-total-panel .amount {
        margin: 8px 0 0;
        font-size: clamp(25px, 4vw, 34px);
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .invoice-total-panel .balance {
        color: #FFD3B9;
        font-size: 13px;
        font-weight: 700;
    }

    .invoice-detail-table th {
        color: var(--terra-muted);
        background: #F8F9FC;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .6px;
    }

    .invoice-detail-table th,
    .invoice-detail-table td {
        padding: 13px 16px;
        vertical-align: middle;
    }
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <a href="{{ route('tenant-portal.rent-invoices.index') }}"
       class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to invoices
    </a>

    <span class="badge rounded-pill text-bg-{{ $statusColor }} px-3 py-2">
        <i class="bi bi-circle-fill me-1" style="font-size:7px;"></i>
        {{ $statusLabel }}
    </span>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="invoice-detail-card">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="invoice-detail-value">{{ $rentInvoice->invoice_number }}</span>
                    </div>
                    <h2 class="h5 fw-bold mb-1">Rental Invoice</h2>
                    <p class="text-muted small mb-0">
                        {{ \Illuminate\Support\Str::headline($rentInvoice->invoice_type ?? 'rent') }}
                    </p>
                </div>

                <div class="text-xl-end">
                    <div class="invoice-detail-label">Issue date</div>
                    <div class="invoice-detail-value">
                        {{ $rentInvoice->issue_date?->format('d M Y') ?? '—' }}
                    </div>
                </div>
            </div>

            <hr class="my-4" style="border-color:var(--terra-border);">

            <div class="row g-4 mb-4">
                <div class="col-6 col-md-4">
                    <div class="invoice-detail-label">Billing period starts</div>
                    <div class="invoice-detail-value">
                        {{ $rentInvoice->billing_period_start?->format('d M Y') ?? '—' }}
                    </div>
                </div>

                <div class="col-6 col-md-4">
                    <div class="invoice-detail-label">Billing period ends</div>
                    <div class="invoice-detail-value">
                        {{ $rentInvoice->billing_period_end?->format('d M Y') ?? '—' }}
                    </div>
                </div>

                <div class="col-6 col-md-4">
                    <div class="invoice-detail-label">Payment due date</div>
                    <div class="invoice-detail-value {{ $rentInvoice->is_overdue ? 'text-danger' : '' }}">
                        {{ $rentInvoice->due_date?->format('d M Y') ?? '—' }}
                    </div>
                </div>

                <div class="col-6 col-md-4">
                    <div class="invoice-detail-label">Unit</div>
                    <div class="invoice-detail-value">
                        {{ $rentInvoice->unit?->unit_number ?? '—' }}
                    </div>
                </div>

                <div class="col-6 col-md-4">
                    <div class="invoice-detail-label">Lease reference</div>
                    <div class="invoice-detail-value">
                        {{ $rentInvoice->lease?->lease_number ?? '—' }}
                    </div>
                </div>

                <div class="col-6 col-md-4">
                    <div class="invoice-detail-label">Invoice currency</div>
                    <div class="invoice-detail-value">
                        {{ $rentInvoice->currency ?: 'RWF' }}
                    </div>
                </div>
            </div>

            @if ($rentInvoice->description)
                <div class="p-3 rounded-3 mb-3" style="background:#F8F9FC;">
                    <div class="invoice-detail-label">Description</div>
                    <div>{{ $rentInvoice->description }}</div>
                </div>
            @endif

            @if ($rentInvoice->notes)
                <div class="p-3 rounded-3" style="background:#F8F9FC;">
                    <div class="invoice-detail-label">Additional notes</div>
                    <div class="text-muted">{{ $rentInvoice->notes }}</div>
                </div>
            @endif
        </div>

        <div class="invoice-detail-card mt-4">
            <h3 class="h6 fw-bold mb-3">Invoice breakdown</h3>

            <div class="table-responsive">
                <table class="table invoice-detail-table mb-0">
                    <tbody>
                        <tr>
                            <td>
                                <div class="fw-semibold">
                                    {{ \Illuminate\Support\Str::headline($rentInvoice->invoice_type ?? 'rent') }}
                                </div>
                                <div class="text-muted small">
                                    {{ $rentInvoice->description ?: 'Charges for the billing period shown above.' }}
                                </div>
                            </td>
                            <td class="text-end fw-semibold">
                                {{ $money($rentInvoice->subtotal) }}
                            </td>
                        </tr>
                        <tr>
                            <td>Tax</td>
                            <td class="text-end">{{ $money($rentInvoice->tax_amount) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td>Total invoice amount</td>
                            <td class="text-end">{{ $money($rentInvoice->total_amount) }}</td>
                        </tr>
                        <tr>
                            <td>Amount paid</td>
                            <td class="text-end text-success">
                                − {{ $money($rentInvoice->amount_paid) }}
                            </td>
                        </tr>
                        <tr class="fw-bold">
                            <td>Outstanding balance</td>
                            <td class="text-end {{ (float) $rentInvoice->balance_due > 0 ? 'text-danger' : 'text-success' }}">
                                {{ $money($rentInvoice->balance_due) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="invoice-total-panel">
            <div class="label">Total invoice amount</div>
            <div class="amount">{{ $money($rentInvoice->total_amount) }}</div>

            <hr class="my-4" style="border-color:rgba(255,255,255,.18);">

            <div class="d-flex justify-content-between gap-3 mb-3">
                <span class="label">Amount paid</span>
                <strong>{{ $money($rentInvoice->amount_paid) }}</strong>
            </div>

            <div class="d-flex justify-content-between align-items-center gap-3">
                <span class="label">Remaining balance</span>
                <span class="balance">{{ $money($rentInvoice->balance_due) }}</span>
            </div>

            @if ((float) $rentInvoice->balance_due > 0)
                <div class="mt-4 small" style="color:rgba(255,255,255,.78);">
                    @if ($rentInvoice->is_overdue)
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        This invoice is overdue. Please contact your property manager about payment.
                    @else
                        <i class="bi bi-calendar-check me-1"></i>
                        Please settle the remaining balance by
                        {{ $rentInvoice->due_date?->format('d M Y') ?? 'the due date' }}.
                    @endif
                </div>
            @else
                <div class="mt-4 small">
                    <i class="bi bi-check-circle me-1"></i>
                    This invoice has been fully paid.
                </div>
            @endif
        </div>

        @if (\Illuminate\Support\Facades\Route::has('tenant-portal.payments.index'))
            <a href="{{ route('tenant-portal.payments.index') }}"
               class="btn btn-outline-secondary w-100 mt-3 py-2">
                <i class="bi bi-clock-history me-2"></i> View my payment history
            </a>
        @endif

        <div class="invoice-detail-card mt-3">
            <h3 class="h6 fw-bold mb-3">Payment allocation history</h3>

            @forelse ($rentInvoice->allocations as $allocation)
                @php
                    $allocatedAmount = $allocation->allocated_amount
                        ?? $allocation->amount_allocated
                        ?? $allocation->amount
                        ?? 0;
                @endphp

                <div class="d-flex justify-content-between gap-3 py-3 border-bottom">
                    <div>
                        <div class="fw-semibold small">Payment allocation</div>
                        <div class="text-muted" style="font-size:11px;">
                            {{ $allocation->created_at?->format('d M Y') ?? 'Date unavailable' }}
                        </div>
                        @if ($allocation->reference ?? false)
                            <div class="text-muted" style="font-size:11px;">
                                Reference: {{ $allocation->reference }}
                            </div>
                        @endif
                    </div>
                    <div class="text-end fw-bold text-success small">
                        {{ $money($allocatedAmount) }}
                    </div>
                </div>
            @empty
                <div class="text-center py-4">
                    <i class="bi bi-clock-history fs-3 text-muted"></i>
                    <p class="small text-muted mt-2 mb-0">
                        No payment allocations are recorded for this invoice yet.
                    </p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
