
@extends('layouts.app')

@section('title', 'Payment Details')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.payments.index') }}" class="text-decoration-none">
                &larr; Back to payments
            </a>
            <h3 class="mt-2 mb-1">{{ $payment->payment_number }}</h3>
            <p class="text-muted mb-0">Payment verification and invoice allocation</p>
        </div>

        @if($payment->status === 'confirmed')
            <a href="{{ route('receipts.show', $payment) }}" target="_blank" class="btn btn-outline-secondary ms-2"><i class="bi bi-file-earmark-check me-1"></i> Receipt</a>
        @endif
        <a href="{{ route('admin.payments.index') }}"
           class="btn btn-outline-secondary">All Payments</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>The action could not be completed:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $statusClass = match($payment->status) {
            'confirmed' => 'success',
            'pending' => 'warning',
            'failed' => 'danger',
            default => 'secondary',
        };
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Payment Amount</div>
                    <div class="fs-4 fw-bold">
                        {{ $payment->currency }}
                        {{ number_format((float) $payment->amount, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Allocated Amount</div>
                    <div class="fs-4 fw-bold text-primary">
                        {{ $payment->currency }}
                        {{ number_format($allocatedAmount, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Available to Allocate</div>
                    <div class="fs-4 fw-bold text-success">
                        {{ $payment->currency }}
                        {{ number_format($availableAmount, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Payment Status</div>
                    <div class="mt-2">
                        <span class="badge text-bg-{{ $statusClass }} fs-6">
                            {{ ucfirst($payment->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Payment Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Tenant</dt>
                        <dd class="col-7">
                            {{ $payment->tenant?->full_name ?? '—' }}
                        </dd>

                        <dt class="col-5">Lease</dt>
                        <dd class="col-7">
                            {{ $payment->lease?->lease_number ?? '—' }}
                        </dd>

                        <dt class="col-5">Unit</dt>
                        <dd class="col-7">
                            {{ $payment->unit?->unit_number ?? '—' }}
                        </dd>

                        <dt class="col-5">Payment Date</dt>
                        <dd class="col-7">
                            {{ $payment->payment_date?->format('d M Y') ?? '—' }}
                        </dd>

                        <dt class="col-5">Method</dt>
                        <dd class="col-7">
                            {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                        </dd>

                        <dt class="col-5">Provider</dt>
                        <dd class="col-7">{{ $payment->provider ?: '—' }}</dd>

                        <dt class="col-5">Reference</dt>
                        <dd class="col-7">
                            {{ $payment->transaction_reference ?: '—' }}
                        </dd>

                        <dt class="col-5">Recorded By</dt>
                        <dd class="col-7">
                            {{ $payment->recorder?->name ?? '—' }}
                        </dd>

                        <dt class="col-5">Notes</dt>
                        <dd class="col-7">{{ $payment->notes ?: '—' }}</dd>
                    </dl>

                    @if($payment->status === 'pending')
                        <hr>
                        <div class="alert alert-warning small">
                            Confirm only after verifying that the funds were received.
                            Confirmation enables invoice allocation.
                        </div>

                        <form method="POST"
                              action="{{ route('admin.payments.confirm', $payment) }}"
                              onsubmit="return confirm('Have you verified receipt of these funds? Confirm this payment?')">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-circle me-1"></i>
                                Confirm Receipt of Payment
                            </button>
                        </form>
                    @elseif($payment->status === 'confirmed')
                        <div class="alert alert-success small mb-0">
                            This payment is confirmed and can be allocated to eligible invoices.
                        </div>
                    @else
                        <div class="alert alert-danger small mb-0">
                            This payment cannot be allocated while its status is
                            {{ $payment->status }}.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Allocate Payment to Invoice</h5>
                </div>
                <div class="card-body">
                    @if($payment->status !== 'confirmed')
                        <div class="alert alert-info mb-0">
                            Confirm this payment first to enable allocation.
                        </div>
                    @elseif($availableAmount <= 0)
                        <div class="alert alert-success mb-0">
                            No funds remain available for allocation.
                        </div>
                    @else
                        <form method="POST"
                              action="{{ route('admin.payments.allocate', $payment) }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Invoice *</label>
                                <select name="rent_invoice_id" class="form-select" required>
                                    <option value="">Select invoice</option>
                                    @foreach($invoices as $invoice)
                                        @if((float) $invoice->balance_due > 0)
                                            <option value="{{ $invoice->id }}"
                                                data-balance="{{ (float) $invoice->balance_due }}"
                                                @selected(old('rent_invoice_id') == $invoice->id)>
                                                {{ $invoice->invoice_number }}
                                                — {{ ucfirst(str_replace('_', ' ', $invoice->invoice_type)) }}
                                                — Balance:
                                                {{ $invoice->currency }}
                                                {{ number_format((float) $invoice->balance_due, 2) }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Allocation Amount *</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $payment->currency }}</span>
                                    <input type="number" name="amount" id="allocationAmount"
                                           class="form-control" min="0.01" step="0.01"
                                           max="{{ number_format($availableAmount, 2, '.', '') }}"
                                           value="{{ old('amount') }}" required>
                                </div>
                                <div class="form-text">
                                    Available payment:
                                    {{ $payment->currency }}
                                    {{ number_format($availableAmount, 2) }}.
                                    The amount cannot exceed the invoice balance.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Allocation Date *</label>
                                <input type="date" name="allocated_at" class="form-control"
                                       value="{{ old('allocated_at', now()->toDateString()) }}"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="2" class="form-control"
                                          placeholder="Optional allocation notes">{{ old('notes') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-diagram-3 me-1"></i>
                                Allocate Payment
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Allocation History</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Payment Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($payment->allocations as $allocation)
                            <tr>
                                <td>
                                    {{ $allocation->invoice?->invoice_number ?? 'Invoice unavailable' }}
                                    <div class="small text-muted">
                                        {{ $allocation->invoice?->invoice_type }}
                                    </div>
                                </td>
                                <td>
                                    {{ $allocation->allocated_at?->format('d M Y') ?? '—' }}
                                </td>
                                <td class="text-nowrap">
                                    {{ $payment->currency }}
                                    {{ number_format((float) $allocation->amount, 2) }}
                                </td>
                                <td>
                                    <span class="badge text-bg-{{ $payment->status === 'confirmed' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No allocations have been made for this payment.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const invoiceSelect = document.getElementById('rent_invoice_id');
    const amountInput = document.getElementById('allocationAmount');

    if (!invoiceSelect || !amountInput) return;

    invoiceSelect.addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        const balance = Number(option?.dataset?.balance || 0);
        const available = Number(amountInput.max || 0);

        if (balance > 0) {
            amountInput.max = Math.min(balance, available).toFixed(2);
        }
    });
});
</script>
@endsection
