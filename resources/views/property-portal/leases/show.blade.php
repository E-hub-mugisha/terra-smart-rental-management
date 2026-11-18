@extends('layouts.app')

@section('title', 'Lease Details')

@section('content')
@php
$unit = $lease->unit;
$property = $unit?->building?->property;
$tenant = $lease->tenant;

$isDraft = $lease->status === 'draft';
$awaitingActivation = $lease->status === 'pending_activation';
$isActive = $lease->status === 'active';
$tenantAccepted = (bool) $lease->tenant_accepted_at;
$depositPaid = (float) $completedDepositPayments >= (float) $lease->deposit_amount;
@endphp

<div class="container-fluid py-4 pm-lease-detail">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <a href="{{ route('property-manager.leases.index') }}"
                class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i> Back to leases
            </a>
            <h3 class="fw-bold mt-3 mb-1">{{ $lease->lease_number }}</h3>
            <p class="text-muted mb-0">
                {{ $tenant?->full_name ?? 'Tenant unavailable' }}
                · {{ $property?->name ?? 'Property unavailable' }}
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <span class="lease-status status-{{ $lease->status }} align-self-center">
                {{ ucwords(str_replace('_', ' ', $lease->status)) }}
            </span>

            @if($isDraft && !$tenantAccepted)
            <a href="{{ route('property-manager.leases.edit', $lease) }}"
                class="btn btn-brand">
                <i class="bi bi-pencil me-1"></i> Edit draft
            </a>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger">
        <strong>Unable to complete this action.</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted mb-2">Monthly rent</div>
                    <div class="fs-4 fw-bold text-brand">
                        {{ $lease->currency }} {{ number_format((float) $lease->monthly_rent, 0) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted mb-2">Security deposit</div>
                    <div class="fs-4 fw-bold">
                        {{ $lease->currency }} {{ number_format((float) $lease->deposit_amount, 0) }}
                    </div>
                    <div class="small text-muted mt-1">
                        Paid: {{ $lease->currency }} {{ number_format((float) $completedDepositPayments, 0) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted mb-2">Start date</div>
                    <div class="fs-5 fw-bold">
                        {{ $lease->start_date?->format('d M Y') ?? '—' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted mb-2">End date</div>
                    <div class="fs-5 fw-bold">
                        {{ $lease->end_date?->format('d M Y') ?? '—' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Lease information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="detail-label">Tenant</div>
                            <div class="detail-value">{{ $tenant?->full_name ?? '—' }}</div>
                            <div class="small text-muted">{{ $tenant?->tenant_number ?? '' }}</div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Tenant email</div>
                            <div class="detail-value">{{ $tenant?->email ?? '—' }}</div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Tenant phone</div>
                            <div class="detail-value">{{ $tenant?->phone ?? '—' }}</div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Property</div>
                            <div class="detail-value">{{ $property?->name ?? '—' }}</div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Building / Unit</div>
                            <div class="detail-value">
                                {{ $unit?->building?->name ?? '—' }} /
                                {{ $unit?->unit_number ?? '—' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Floor</div>
                            <div class="detail-value">{{ $unit?->floor?->name ?? '—' }}</div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Payment frequency</div>
                            <div class="detail-value">
                                {{ ucwords(str_replace('_', ' ', $lease->payment_frequency ?? 'monthly')) }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Rent due day</div>
                            <div class="detail-value">
                                {{ $lease->rent_due_day ? 'Day '.$lease->rent_due_day.' of each month' : '—' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Tenant acceptance</div>
                            <div class="detail-value">
                                @if($lease->tenant_accepted_at)
                                <span class="text-success">Accepted</span>
                                <div class="small text-muted">
                                    {{ $lease->tenant_accepted_at->format('d M Y') }}
                                </div>
                                @else
                                <span class="text-warning">Awaiting acceptance</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Move-in confirmation</div>
                            <div class="detail-value">
                                @if($lease->move_in_confirmed_at)
                                {{ $lease->move_in_confirmed_at->format('d M Y') }}
                                @else
                                Not confirmed
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Activation date</div>
                            <div class="detail-value">
                                {{ $lease->activated_at?->format('d M Y') ?? 'Not activated' }}
                            </div>
                        </div>

                        @if($lease->termination_date)
                        <div class="col-md-6">
                            <div class="detail-label">Termination date</div>
                            <div class="detail-value text-danger">
                                {{ $lease->termination_date->format('d M Y') }}
                            </div>
                        </div>
                        @endif
                    </div>

                    @if($lease->terms)
                    <hr>
                    <h6 class="fw-bold">Lease terms and conditions</h6>
                    <div class="text-secondary">{!! nl2br(e($lease->terms)) !!}</div>
                    @endif

                    @if($lease->termination_reason)
                    <hr>
                    <h6 class="fw-bold text-danger">Termination reason</h6>
                    <p class="mb-0">{!! nl2br(e($lease->termination_reason)) !!}</p>
                    @endif
                </div>
            </div>


            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h6 class="fw-bold mb-1">Invoices & Payment Status</h6>
                        <div class="small text-muted">
                            Track required payments and outstanding balances.
                        </div>
                    </div>

                    @if($awaitingActivation && $tenantAccepted)
                    <form
                        method="POST"
                        action="{{ route('property-manager.leases.generate-required-invoices', $lease) }}"
                        onsubmit="return confirm('Generate the required security deposit and initial rent invoices? Existing invoices will not be duplicated.');">
                        @csrf

                        <button type="submit" class="btn btn-brand">
                            <i class="bi bi-receipt me-1"></i>
                            Generate Required Invoices
                        </button>
                    </form>
                    @endif
                </div>

                @php
                $invoiceCollection = $lease->invoices;

                $invoiceTotal = 0;
                $invoicePaidTotal = 0;
                $invoiceBalanceTotal = 0;

                foreach ($invoiceCollection as $invoice) {
                $paid = (float) $invoice->allocations
                ->filter(fn ($allocation) =>
                $allocation->payment
                && $allocation->payment->status === 'confirmed'
                )
                ->sum('amount');

                $total = (float) $invoice->total_amount;
                $balance = max(0, $total - $paid);

                $invoiceTotal += $total;
                $invoicePaidTotal += min($total, $paid);
                $invoiceBalanceTotal += $balance;
                }
                @endphp

                <div class="card-body border-bottom">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="small text-muted mb-1">Total invoiced</div>
                            <div class="fw-bold fs-5">
                                {{ $lease->currency }}
                                {{ number_format($invoiceTotal, 2) }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted mb-1">Confirmed payments</div>
                            <div class="fw-bold fs-5 text-success">
                                {{ $lease->currency }}
                                {{ number_format($invoicePaidTotal, 2) }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted mb-1">Outstanding balance</div>
                            <div class="fw-bold fs-5 {{ $invoiceBalanceTotal > 0 ? 'text-danger' : 'text-success' }}">
                                {{ $lease->currency }}
                                {{ number_format($invoiceBalanceTotal, 2) }}
                            </div>
                        </div>
                    </div>

                    @if(!$tenantAccepted && in_array($lease->status, ['draft', 'pending_tenant_acceptance'], true))
                    <div class="alert alert-info mt-3 mb-0">
                        The tenant must accept the lease before the required invoices can be generated.
                    </div>
                    @elseif($invoiceCollection->isEmpty())
                    <div class="alert alert-light border mt-3 mb-0">
                        No invoices have been generated for this lease yet.
                    </div>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Invoice</th>
                                <th>Type</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($invoiceCollection as $invoice)
                            @php
                            $confirmedPaid = (float) $invoice->allocations
                            ->filter(fn ($allocation) =>
                            $allocation->payment
                            && $allocation->payment->status === 'confirmed'
                            )
                            ->sum('amount');

                            $total = (float) $invoice->total_amount;
                            $balance = max(0, $total - $confirmedPaid);

                            $paymentStatus = $balance <= 0
                                ? 'paid'
                                : ($confirmedPaid> 0 ? 'partially_paid' : 'unpaid');

                                $paymentBadge = match ($paymentStatus) {
                                'paid' => 'success',
                                'partially_paid' => 'warning',
                                default => 'secondary',
                                };
                                @endphp

                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold">
                                            {{ $invoice->invoice_number }}
                                        </div>
                                        <div class="small text-muted">
                                            Issued {{ $invoice->issue_date?->format('d M Y') ?? '—' }}
                                        </div>
                                        <div class="small text-muted">
                                            Due {{ $invoice->due_date?->format('d M Y') ?? '—' }}
                                        </div>
                                    </td>

                                    <td>
                                        {{ ucwords(str_replace('_', ' ', $invoice->invoice_type)) }}
                                    </td>

                                    <td class="text-nowrap">
                                        {{ $invoice->currency ?? $lease->currency }}
                                        {{ number_format($total, 2) }}
                                    </td>

                                    <td class="text-success text-nowrap">
                                        {{ $invoice->currency ?? $lease->currency }}
                                        {{ number_format(min($total, $confirmedPaid), 2) }}
                                    </td>

                                    <td class="{{ $balance > 0 ? 'text-danger' : 'text-success' }} text-nowrap">
                                        {{ $invoice->currency ?? $lease->currency }}
                                        {{ number_format($balance, 2) }}
                                    </td>

                                    <td>
                                        <span class="badge text-bg-{{ $paymentBadge }}">
                                            {{ ucwords(str_replace('_', ' ', $paymentStatus)) }}
                                        </span>
                                    </td>
                                </tr>

                                @if($invoice->allocations->isNotEmpty())
                                <tr class="table-light">
                                    <td colspan="6" class="px-3 py-3">
                                        <div class="small fw-semibold mb-2">
                                            Payment Allocation History
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Payment reference</th>
                                                        <th>Date</th>
                                                        <th>Method</th>
                                                        <th>Payment status</th>
                                                        <th class="text-end">Allocated amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($invoice->allocations as $allocation)
                                                    <tr>
                                                        <td>
                                                            {{ $allocation->payment?->transaction_reference
                                                            ?: $allocation->payment?->payment_number
                                                            ?: '—' }}
                                                        </td>
                                                        <td>
                                                            {{ $allocation->allocated_at?->format('d M Y')
                                                            ?? '—' }}
                                                        </td>
                                                        <td>
                                                            {{ ucwords(str_replace(
                                                            '_',
                                                            ' ',
                                                            $allocation->payment?->payment_method ?? 'unknown'
                                                        )) }}
                                                        </td>
                                                        <td>
                                                            @php
                                                            $allocationStatus = $allocation->payment?->status ?? 'unknown';
                                                            $allocationBadge = match ($allocationStatus) {
                                                            'confirmed' => 'success',
                                                            'completed' => 'success',
                                                            'pending' => 'warning',
                                                            'failed', 'rejected' => 'danger',
                                                            default => 'secondary',
                                                            };
                                                            @endphp

                                                            <span class="badge text-bg-{{ $allocationBadge }}">
                                                                {{ ucfirst($allocationStatus) }}
                                                            </span>
                                                        </td>
                                                        <td class="text-end text-nowrap">
                                                            {{ $invoice->currency ?? $lease->currency }}
                                                            {{ number_format((float) $allocation->amount, 2) }}
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                                @endif
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No invoices linked to this lease yet.
                                    </td>
                                </tr>
                                @endforelse
                        </tbody>
                    </table>
                </div>
            </div>


            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Payment history</h6>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Receipt</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lease->payments as $payment)
                            <tr>
                                <td>{{ $payment->receipt_number ?? '—' }}</td>
                                <td>{{ $payment->payment_date?->format('d M Y') ?? $payment->payment_date ?? '—' }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method ?? 'other')) }}</td>
                                <td>
                                    {{ $payment->currency ?? $lease->currency }}
                                    {{ number_format((float) $payment->amount, 0) }}
                                </td>
                                <td>{{ ucfirst($payment->status ?? 'unknown') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No payments recorded for this lease.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            @include('leases._workflow-panel', ['lease' => $lease, 'method' => 'PATCH', 'routes' => [
                'send' => 'property-manager.leases.send-for-signature', 'recall' => 'property-manager.leases.recall',
                'invoices' => 'property-manager.leases.generate-required-invoices', 'moveIn' => 'property-manager.leases.confirm-move-in',
                'activate' => 'property-manager.leases.activate', 'edit' => 'property-manager.leases.edit',
            ]])

            @include('leases._tenancy-panel', ['lease' => $lease])
            @include('inspections._lease-panel', ['lease' => $lease])
            @if($isActive)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold text-danger mb-0">Terminate lease</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">
                        Record the termination date and reason. Existing invoices and payment
                        history will be preserved.
                    </p>

                    <form method="POST"
                        action="{{ route('property-manager.leases.terminate', $lease) }}"
                        onsubmit="return confirm('Terminate this lease?');">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label for="termination_date" class="form-label">Termination date</label>
                            <input
                                type="date"
                                name="termination_date"
                                id="termination_date"
                                required
                                max="{{ now()->toDateString() }}"
                                value="{{ old('termination_date', now()->toDateString()) }}"
                                class="form-control @error('termination_date') is-invalid @enderror">
                            @error('termination_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="termination_reason" class="form-label">Reason</label>
                            <textarea
                                name="termination_reason"
                                id="termination_reason"
                                rows="4"
                                required
                                maxlength="5000"
                                class="form-control @error('termination_reason') is-invalid @enderror"
                                placeholder="Explain why the lease is being terminated">{{ old('termination_reason') }}</textarea>
                            @error('termination_reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-outline-danger w-100">
                            Terminate lease
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    .pm-lease-detail .card {
        border-radius: 14px;
    }

    .pm-lease-detail .text-brand {
        color: #D05208;
    }

    .pm-lease-detail .btn-brand {
        background: #D05208;
        border-color: #D05208;
        color: #fff;
    }

    .pm-lease-detail .btn-brand:hover {
        background: #b94707;
        border-color: #b94707;
        color: #fff;
    }

    .pm-lease-detail .detail-label {
        color: #727b8d;
        font-size: .8rem;
        margin-bottom: .3rem;
    }

    .pm-lease-detail .detail-value {
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .pm-lease-detail .lease-status {
        display: inline-block;
        padding: .4rem .7rem;
        border-radius: 24px;
        font-size: .76rem;
        font-weight: 700;
    }

    .pm-lease-detail .status-pending_tenant_acceptance { background: #fff3dc; color: #8f5a08; }
    .pm-lease-detail .status-pending_activation { background: #e7f4fd; color: #116a9c; }
    .pm-lease-detail .status-draft {
        background: #f0f1f3;
        color: #525866;
    }

    .pm-lease-detail .status-active {
        background: #e6f6ed;
        color: #18794e;
    }

    .pm-lease-detail .status-expired {
        background: #eaf3ff;
        color: #245eaa;
    }

    .pm-lease-detail .status-terminated,
    .pm-lease-detail .status-cancelled {
        background: #ffebeb;
        color: #b42318;
    }

    .pm-lease-detail .check-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: .7rem 0;
        font-size: .875rem;
    }
</style>
@endsection