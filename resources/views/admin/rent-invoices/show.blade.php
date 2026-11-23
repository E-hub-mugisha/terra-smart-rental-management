
@extends('layouts.app')

@section('title', 'Invoice Details')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.rent-invoices.index') }}"
               class="text-decoration-none">
                &larr; Back to invoices
            </a>
            <h3 class="mt-2 mb-1">{{ $rentInvoice->invoice_number }}</h3>
            <p class="text-muted mb-0">Invoice details and payment allocation history</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" onclick="window.print()"
                    class="btn btn-outline-secondary">
                <i class="bi bi-printer me-1"></i> Print
            </button>
            <a href="{{ route('admin.payments.index') }}"
               class="btn btn-primary">
                <i class="bi bi-wallet2 me-1"></i> Rent Payments
            </a>
            @if((float) $rentInvoice->amount_paid <= 0 && ! $rentInvoice->allocations()->exists())
                <form method="POST" action="{{ route('admin.rent-invoices.destroy', $rentInvoice) }}" class="m-0"
                      onsubmit="return confirm('Delete this invoice? This cannot be undone from the admin panel.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash3 me-1"></i> Delete</button>
                </form>
            @endif
            <x-purge-button type="invoice" :id="$rentInvoice->id" what="this invoice and the payments applied to it" />
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $statusClass = $isOverdue
            ? 'danger'
            : match($rentInvoice->status) {
                'paid' => 'success',
                'partially_paid' => 'info',
                'unpaid' => 'warning',
                default => 'secondary',
            };
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Invoice Total</div>
                    <div class="fs-4 fw-bold">
                        {{ $rentInvoice->currency }}
                        {{ number_format((float)$rentInvoice->total_amount, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Confirmed Payments Allocated</div>
                    <div class="fs-4 fw-bold text-success">
                        {{ $rentInvoice->currency }}
                        {{ number_format($confirmedPaid, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Outstanding Balance</div>
                    <div class="fs-4 fw-bold {{ $balance > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $rentInvoice->currency }}
                        {{ number_format($balance, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Invoice Status</div>
                    <div class="mt-2">
                        <span class="badge text-bg-{{ $statusClass }} fs-6">
                            {{ $isOverdue ? 'Overdue' : ucfirst(str_replace('_', ' ', $rentInvoice->status)) }}
                        </span>
                    </div>
                    <div class="small text-muted mt-2">
                        Due {{ $rentInvoice->due_date?->format('d M Y') ?? '—' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($isOverdue)
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-1"></i>
            This invoice is overdue and has an outstanding balance of
            <strong>{{ $rentInvoice->currency }} {{ number_format($balance, 2) }}</strong>.
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Invoice Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Invoice Number</dt>
                        <dd class="col-sm-8">{{ $rentInvoice->invoice_number }}</dd>

                        <dt class="col-sm-4">Invoice Type</dt>
                        <dd class="col-sm-8">
                            {{ ucfirst(str_replace('_', ' ', $rentInvoice->invoice_type)) }}
                        </dd>

                        <dt class="col-sm-4">Issue Date</dt>
                        <dd class="col-sm-8">
                            {{ $rentInvoice->issue_date?->format('d M Y') ?? '—' }}
                        </dd>

                        <dt class="col-sm-4">Due Date</dt>
                        <dd class="col-sm-8">
                            {{ $rentInvoice->due_date?->format('d M Y') ?? '—' }}
                        </dd>

                        <dt class="col-sm-4">Billing Period</dt>
                        <dd class="col-sm-8">
                            {{ $rentInvoice->billing_period_start?->format('d M Y') ?? '—' }}
                            –
                            {{ $rentInvoice->billing_period_end?->format('d M Y') ?? '—' }}
                        </dd>

                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8">{{ $rentInvoice->description ?: '—' }}</dd>

                        <dt class="col-sm-4">Notes</dt>
                        <dd class="col-sm-8">{{ $rentInvoice->notes ?: '—' }}</dd>
                    </dl>

                    <hr>

                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <th>Subtotal</th>
                                    <td class="text-end">
                                        {{ $rentInvoice->currency }}
                                        {{ number_format((float)$rentInvoice->subtotal, 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tax</th>
                                    <td class="text-end">
                                        {{ $rentInvoice->currency }}
                                        {{ number_format((float)$rentInvoice->tax_amount, 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Total</th>
                                    <td class="text-end fw-bold">
                                        {{ $rentInvoice->currency }}
                                        {{ number_format((float)$rentInvoice->total_amount, 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Paid from confirmed allocations</th>
                                    <td class="text-end text-success">
                                        {{ $rentInvoice->currency }}
                                        {{ number_format($confirmedPaid, 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Balance</th>
                                    <td class="text-end fw-bold {{ $balance > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ $rentInvoice->currency }}
                                        {{ number_format($balance, 2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Payment Allocation History</h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Payment</th>
                                <th>Date Allocated</th>
                                <th>Amount</th>
                                <th>Payment Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($rentInvoice->allocations as $allocation)
                            @php
                                $payment = $allocation->payment;
                                $confirmed = $payment && $payment->status === 'confirmed';
                            @endphp
                            <tr>
                                <td>
                                    @if($payment)
                                        <a href="{{ route('admin.payments.show', $payment) }}"
                                           class="fw-semibold text-decoration-none">
                                            {{ $payment->payment_number }}
                                        </a>
                                        <div class="small text-muted">
                                            {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                                        </div>
                                        @if($payment->transaction_reference)
                                            <div class="small text-muted">
                                                Ref: {{ $payment->transaction_reference }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted">Payment unavailable</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $allocation->allocated_at?->format('d M Y') ?? '—' }}
                                </td>
                                <td class="text-nowrap {{ $confirmed ? 'text-success' : 'text-muted' }}">
                                    {{ $rentInvoice->currency }}
                                    {{ number_format((float)$allocation->amount, 2) }}
                                </td>
                                <td>
                                    <span class="badge text-bg-{{ $confirmed ? 'success' : 'warning' }}">
                                        {{ $payment ? ucfirst($payment->status) : 'Unavailable' }}
                                    </span>
                                    @if(!$confirmed)
                                        <div class="small text-muted">Not counted as paid</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No payment allocations have been recorded for this invoice.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Tenant Details</h5>
                </div>
                <div class="card-body">
                    <h6>{{ $rentInvoice->tenant?->full_name ?? 'Tenant unavailable' }}</h6>
                    <div class="text-muted small mb-3">
                        {{ $rentInvoice->tenant?->tenant_number }}
                    </div>

                    <dl class="row mb-0">
                        <dt class="col-4">Email</dt>
                        <dd class="col-8">{{ $rentInvoice->tenant?->email ?? '—' }}</dd>

                        <dt class="col-4">Phone</dt>
                        <dd class="col-8">{{ $rentInvoice->tenant?->phone ?? '—' }}</dd>

                        <dt class="col-4">Lease</dt>
                        <dd class="col-8">
                            {{ $rentInvoice->lease?->lease_number ?? '—' }}
                        </dd>

                        <dt class="col-4">Unit</dt>
                        <dd class="col-8">
                            {{ $rentInvoice->unit?->unit_number ?? '—' }}
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Next Actions</h5>
                </div>
                <div class="card-body">
                    @if($balance > 0)
                        <p class="text-muted small">
                            Apply a verified tenant payment from the Rent Payments module.
                            Only confirmed payments allocated to this invoice count toward its balance.
                        </p>
                        <a href="{{ route('admin.payments.index', [
                            'search' => $rentInvoice->tenant?->full_name
                        ]) }}" class="btn btn-primary w-100">
                            <i class="bi bi-wallet2 me-1"></i> Find Tenant Payments
                        </a>
                    @else
                        <div class="alert alert-success mb-0">
                            <i class="bi bi-check-circle me-1"></i>
                            This invoice is fully paid according to confirmed allocations.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    nav, .sidebar, .btn, a[href*="admin.payments"], a[href*="admin.rent-invoices"],
    .no-print {
        display: none !important;
    }

    body {
        background: #fff !important;
    }

    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
        break-inside: avoid;
    }

    .container-fluid {
        width: 100% !important;
        padding: 0 !important;
    }
}
</style>
@endsection
