
@extends('layouts.app')

@section('title', 'My Payments')
@section('page_title', 'My Payments')
@section('page_subtitle', 'Track your rent payments, transaction references and confirmations.')

@section('content')
@php
    $money = fn ($amount, $currency = 'RWF') =>
        ($currency ?: 'RWF') . ' ' . number_format((float) $amount, 2);

    $statusDetails = function ($status) {
        return match (strtolower((string) $status)) {
            'confirmed' => ['Confirmed', 'success'],
            'pending' => ['Pending', 'warning'],
            'failed' => ['Failed', 'danger'],
            'rejected' => ['Rejected', 'danger'],
            'cancelled' => ['Cancelled', 'secondary'],
            default => [ucfirst((string) ($status ?: 'Unknown')), 'secondary'],
        };
    };

    $methodLabels = [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'mobile_money' => 'Mobile Money',
        'card' => 'Card',
        'payment_gateway' => 'Payment Gateway',
        'other' => 'Other',
    ];
@endphp

<style>
    .payment-stat {
        height: 100%;
        padding: 20px;
        border: 1px solid var(--terra-border);
        border-radius: 14px;
        background: #fff;
        box-shadow: var(--terra-shadow);
    }

    .payment-stat-label {
        color: var(--terra-muted);
        font-size: 12px;
        font-weight: 600;
    }

    .payment-stat-value {
        margin-top: 10px;
        color: var(--terra-navy);
        font-size: clamp(20px, 2vw, 27px);
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .payment-stat-icon {
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

    .payment-panel {
        overflow: hidden;
        border: 1px solid var(--terra-border);
        border-radius: 14px;
        background: #fff;
        box-shadow: var(--terra-shadow);
    }

    .payment-table th {
        padding: 14px 16px;
        background: #F8F9FC;
        color: #778196;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .6px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .payment-table td {
        padding: 15px 16px;
        border-color: #EEF0F5;
        vertical-align: middle;
        font-size: 12px;
    }

    .payment-number {
        color: var(--terra-navy);
        font-weight: 800;
    }

    .payment-meta {
        margin-top: 4px;
        color: var(--terra-muted);
        font-size: 11px;
    }

    .payment-filter .form-control,
    .payment-filter .form-select {
        min-height: 42px;
        border-color: var(--terra-border);
        border-radius: 9px;
        font-size: 12px;
    }

    .payment-filter .btn {
        min-height: 42px;
        border-radius: 9px;
        font-size: 12px;
        font-weight: 700;
    }

    .payment-empty {
        padding: 60px 20px;
        text-align: center;
    }

    .payment-empty-icon {
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

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h2 class="h5 fw-bold mb-1">Payment overview</h2>
        <p class="text-muted small mb-0">
            Review recorded payments and check whether they have been confirmed.
        </p>
    </div>

    @if (\Illuminate\Support\Facades\Route::has('tenant-portal.rent-invoices.index'))
        <a href="{{ route('tenant-portal.rent-invoices.index') }}"
           class="btn btn-outline-secondary btn-sm px-3 py-2">
            <i class="bi bi-receipt me-1"></i> My Invoices
        </a>
    @endif
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="payment-stat">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <span class="payment-stat-label">Total Payments</span>
                <span class="payment-stat-icon"><i class="bi bi-wallet2"></i></span>
            </div>
            <div class="payment-stat-value">{{ number_format($summary['total']) }}</div>
            <div class="payment-meta">All recorded transactions</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="payment-stat">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <span class="payment-stat-label">Confirmed Amount</span>
                <span class="payment-stat-icon"><i class="bi bi-check-circle"></i></span>
            </div>
            <div class="payment-stat-value" style="color:#198754;">
                {{ number_format($summary['confirmed_amount'], 0) }}
            </div>
            <div class="payment-meta">Confirmed payment totals</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="payment-stat">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <span class="payment-stat-label">Confirmed Transactions</span>
                <span class="payment-stat-icon"><i class="bi bi-shield-check"></i></span>
            </div>
            <div class="payment-stat-value">{{ number_format($summary['confirmed_count']) }}</div>
            <div class="payment-meta">Successfully confirmed</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="payment-stat">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <span class="payment-stat-label">Pending</span>
                <span class="payment-stat-icon"><i class="bi bi-hourglass-split"></i></span>
            </div>
            <div class="payment-stat-value"
                 style="{{ $summary['pending_count'] > 0 ? 'color:#B7791F;' : '' }}">
                {{ number_format($summary['pending_count']) }}
            </div>
            <div class="payment-meta">Awaiting confirmation</div>
        </div>
    </div>
</div>

<div class="payment-panel">
    <div class="p-3 p-lg-4 border-bottom">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h3 class="h6 fw-bold mb-1">Payment history</h3>
                <p class="text-muted small mb-0">
                    View transaction details and invoice allocations.
                </p>
            </div>
            <span class="badge text-bg-light border">
                {{ $payments->total() }} records
            </span>
        </div>

        <form method="GET"
              action="{{ route('tenant-portal.payments.index') }}"
              class="row g-2 payment-filter">

            <div class="col-12 col-md-5">
                <input type="search"
                       name="search"
                       class="form-control"
                       value="{{ request('search') }}"
                       placeholder="Search payment number or reference">
            </div>

            <div class="col-6 col-md-3">
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach (['confirmed', 'pending', 'failed', 'rejected', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="method" class="form-select">
                    <option value="">All methods</option>
                    @foreach ($methodLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('method') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
            </div>

            @if (request()->filled('search') || request()->filled('status') || request()->filled('method'))
                <div class="col-12">
                    <a href="{{ route('tenant-portal.payments.index') }}"
                       class="small text-decoration-none">
                        <i class="bi bi-x-circle me-1"></i>Clear filters
                    </a>
                </div>
            @endif
        </form>
    </div>

    @if ($payments->count())
        <div class="table-responsive">
            <table class="table payment-table mb-0">
                <thead>
                    <tr>
                        <th>Payment</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Transaction Reference</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payments as $payment)
                        @php
                            [$statusLabel, $statusColor] = $statusDetails($payment->status);
                        @endphp

                        <tr>
                            <td>
                                <a href="{{ route('tenant-portal.payments.show', $payment->id) }}"
                                   class="payment-number">
                                    {{ $payment->payment_number }}
                                </a>
                                <div class="payment-meta">
                                    @if ($payment->unit)
                                        Unit {{ $payment->unit->unit_number }}
                                    @else
                                        Rental payment
                                    @endif
                                </div>
                            </td>

                            <td>
                                {{ $payment->payment_date?->format('d M Y') ?? '—' }}
                            </td>

                            <td>
                                {{ $methodLabels[$payment->payment_method] ?? \Illuminate\Support\Str::headline($payment->payment_method ?? 'Other') }}
                                @if ($payment->provider)
                                    <div class="payment-meta">{{ $payment->provider }}</div>
                                @endif
                            </td>

                            <td>
                                @if ($payment->transaction_reference)
                                    <span class="text-break">{{ $payment->transaction_reference }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td class="fw-bold text-nowrap">
                                {{ $money($payment->amount, $payment->currency) }}
                            </td>

                            <td>
                                <span class="badge rounded-pill text-bg-{{ $statusColor }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            <td class="text-end">
                                <a href="{{ route('tenant-portal.payments.show', $payment->id) }}"
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
                Showing {{ $payments->firstItem() }}–{{ $payments->lastItem() }}
                of {{ $payments->total() }} payments
            </span>
            {{ $payments->links() }}
        </div>
    @else
        <div class="payment-empty">
            <div class="payment-empty-icon mb-3">
                <i class="bi bi-wallet2"></i>
            </div>
            <h3 class="h6 fw-bold">No payments found</h3>
            <p class="text-muted small mb-3">
                Your recorded payments will appear here.
            </p>

            @if (request()->filled('search') || request()->filled('status') || request()->filled('method'))
                <a href="{{ route('tenant-portal.payments.index') }}"
                   class="btn btn-outline-secondary btn-sm">
                    Clear filters
                </a>
            @endif
        </div>
    @endif
</div>
@endsection
