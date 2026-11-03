@extends('layouts.app')

@section('title', 'Manage Lease')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.leases.index') }}"
               class="text-decoration-none small">
                &larr; Back to Lease Management
            </a>

            <h2 class="fw-bold mt-2 mb-1">
                {{ $lease->lease_number }}
            </h2>

            <p class="text-muted mb-0">
                {{ $lease->tenant?->full_name ?? 'Tenant unavailable' }}
                &middot;
                Unit {{ $lease->unit?->unit_number ?? 'N/A' }}
            </p>
        </div>

        <span class="badge fs-6 text-bg-{{
            match ($lease->status) {
                'active' => 'success',
                'pending_activation' => 'info',
                'pending_tenant_acceptance' => 'warning',
                'rejected', 'cancelled' => 'danger',
                'terminated' => 'dark',
                default => 'secondary',
            }
        }}">
            {{ ucwords(str_replace('_', ' ', $lease->status)) }}
        </span>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Action could not be completed.</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Lease summary --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Monthly Rent</div>
                    <div class="fs-4 fw-bold">
                        {{ $lease->currency }}
                        {{ number_format((float) $lease->monthly_rent, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Required Deposit</div>
                    <div class="fs-4 fw-bold">
                        {{ $lease->currency }}
                        {{ number_format((float) $lease->deposit_amount, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Invoiced</div>
                    <div class="fs-4 fw-bold">
                        {{ $lease->currency }}
                        {{ number_format($invoiceSummary['total'], 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Outstanding Balance</div>
                    <div class="fs-4 fw-bold {{ $invoiceSummary['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $lease->currency }}
                        {{ number_format($invoiceSummary['balance'], 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Lease details --}}
        <div class="col-xl-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Lease Information</h5>
                </div>

                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="text-muted small">Tenant</div>
                            <div class="fw-semibold">
                                {{ $lease->tenant?->full_name ?? 'N/A' }}
                            </div>
                            <div class="small text-muted">
                                {{ $lease->tenant?->email }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small">Property / Building</div>
                            <div class="fw-semibold">
                                {{ $lease->unit?->building?->name ?? 'N/A' }}
                            </div>
                            <div class="small text-muted">
                                Unit {{ $lease->unit?->unit_number ?? 'N/A' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small">Start Date</div>
                            <div class="fw-semibold">
                                {{ $lease->start_date?->format('d M Y') }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small">End Date</div>
                            <div class="fw-semibold">
                                {{ $lease->end_date?->format('d M Y') }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small">Tenant Acceptance</div>
                            @if ($lease->tenant_accepted_at)
                                <span class="badge text-bg-success">Accepted</span>
                                <div class="small text-muted mt-1">
                                    {{ $lease->tenant_accepted_at->format('d M Y, H:i') }}
                                </div>
                            @else
                                <span class="badge text-bg-warning">Awaiting acceptance</span>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small">Move-in Confirmation</div>
                            @if ($lease->move_in_confirmed_at)
                                <span class="badge text-bg-success">Confirmed</span>
                                <div class="small text-muted mt-1">
                                    {{ $lease->move_in_confirmed_at->format('d M Y, H:i') }}
                                </div>
                            @else
                                <span class="badge text-bg-secondary">Not confirmed</span>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small">Lease Activated</div>
                            @if ($lease->activated_at)
                                <span class="badge text-bg-success">Active</span>
                                <div class="small text-muted mt-1">
                                    {{ $lease->activated_at->format('d M Y, H:i') }}
                                </div>
                            @else
                                <span class="text-muted">Not activated</span>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small">Payment Frequency</div>
                            <div class="fw-semibold">
                                {{ ucfirst($lease->payment_frequency) }}
                            </div>
                        </div>
                    </div>

                    @if ($lease->terms)
                        <hr>
                        <h6 class="fw-bold">Lease Terms</h6>
                        <div style="white-space: pre-line;">{{ $lease->terms }}</div>
                    @endif
                </div>
            </div>

            {{-- Invoices --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="fw-bold mb-0">Invoices & Payment Status</h5>

                    @if ($lease->status === 'pending_activation' && $lease->tenant_accepted_at)
                        <form method="POST"
                              action="{{ route('admin.leases.generate-required-invoices', $lease) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                Generate Required Invoices
                            </button>
                        </form>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Type</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($invoices as $invoice)
                                @php
                                    $confirmedPaid = (float) $invoice->allocations
                                        ->filter(fn ($allocation) =>
                                            $allocation->payment
                                            && $allocation->payment->status === 'confirmed'
                                        )
                                        ->sum('amount');

                                    $balance = max(
                                        0,
                                        (float) $invoice->total_amount - $confirmedPaid
                                    );
                                @endphp

                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $invoice->invoice_number }}
                                        </div>
                                        <small class="text-muted">
                                            Due {{ $invoice->due_date?->format('d M Y') ?? 'N/A' }}
                                        </small>
                                    </td>

                                    <td>{{ ucwords(str_replace('_', ' ', $invoice->invoice_type)) }}</td>

                                    <td>
                                        {{ $invoice->currency }}
                                        {{ number_format((float) $invoice->total_amount, 2) }}
                                    </td>

                                    <td class="text-success">
                                        {{ $invoice->currency }}
                                        {{ number_format($confirmedPaid, 2) }}
                                    </td>

                                    <td class="{{ $balance > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ $invoice->currency }}
                                        {{ number_format($balance, 2) }}
                                    </td>

                                    <td>
                                        <span class="badge text-bg-{{ $balance <= 0 ? 'success' : ($confirmedPaid > 0 ? 'warning' : 'secondary') }}">
                                            {{ $balance <= 0 ? 'Paid' : ($confirmedPaid > 0 ? 'Partially Paid' : 'Unpaid') }}
                                        </span>
                                    </td>
                                </tr>

                                @if ($invoice->allocations->isNotEmpty())
                                    <tr class="table-light">
                                        <td colspan="6">
                                            <div class="small fw-semibold mb-2">
                                                Payment Allocation History
                                            </div>

                                            <div class="table-responsive">
                                                <table class="table table-sm mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Payment Reference</th>
                                                            <th>Date</th>
                                                            <th>Method</th>
                                                            <th>Payment Status</th>
                                                            <th>Allocated Amount</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($invoice->allocations as $allocation)
                                                            <tr>
                                                                <td>
                                                                    {{ $allocation->payment?->transaction_reference
                                                                        ?: $allocation->payment?->payment_number
                                                                        ?: 'N/A' }}
                                                                </td>
                                                                <td>
                                                                    {{ $allocation->allocated_at?->format('d M Y') ?? 'N/A' }}
                                                                </td>
                                                                <td>
                                                                    {{ ucwords(str_replace('_', ' ', $allocation->payment?->payment_method ?? 'N/A')) }}
                                                                </td>
                                                                <td>
                                                                    {{ ucfirst($allocation->payment?->status ?? 'Unknown') }}
                                                                </td>
                                                                <td>
                                                                    {{ $invoice->currency }}
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
                                        No invoices have been generated for this lease.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        {{-- Admin actions --}}
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Lease Activation</h5>
                </div>

                <div class="card-body">

                    @if ($lease->status === 'draft')
                        <div class="alert alert-secondary">
                            This lease is still a draft. Submit it for tenant acceptance
                            through your existing application or lease workflow.
                        </div>

                    @elseif ($lease->status === 'pending_tenant_acceptance')
                        <div class="alert alert-warning">
                            The tenant has not accepted this lease yet.
                        </div>

                    @elseif ($lease->status === 'pending_activation')
                        <div class="mb-3">
                            <div class="fw-semibold mb-1">Step 1: Tenant acceptance</div>
                            <span class="badge text-bg-{{ $lease->tenant_accepted_at ? 'success' : 'warning' }}">
                                {{ $lease->tenant_accepted_at ? 'Completed' : 'Pending' }}
                            </span>
                        </div>

                        <div class="mb-3">
                            <div class="fw-semibold mb-1">Step 2: Required invoices</div>
                            @php
                                $requiredInvoices = $invoices->whereIn('invoice_type', ['deposit', 'rent']);

                                $hasRequiredRent = $requiredInvoices->contains(
                                    fn ($invoice) => $invoice->invoice_type === 'rent'
                                );

                                $hasRequiredDeposit = (float) $lease->deposit_amount <= 0
                                    || $requiredInvoices->contains(
                                        fn ($invoice) => $invoice->invoice_type === 'deposit'
                                    );

                                $allRequiredPaid = $hasRequiredRent
                                    && $hasRequiredDeposit
                                    && $requiredInvoices->every(function ($invoice) {
                                        $paid = (float) $invoice->allocations
                                            ->filter(fn ($allocation) =>
                                                $allocation->payment
                                                && $allocation->payment->status === 'confirmed'
                                            )
                                            ->sum('amount');

                                        return max(
                                            0,
                                            (float) $invoice->total_amount - $paid
                                        ) <= 0;
                                    });
                            @endphp

                            <span class="badge text-bg-{{ $allRequiredPaid ? 'success' : 'warning' }}">
                                {{ $allRequiredPaid ? 'Completed' : 'Pending Payment' }}
                            </span>

                            @if (!$allRequiredPaid)
                                <div class="small text-muted mt-1">
                                    Generate the invoices and record/confirm payments using your existing finance workflow.
                                </div>
                            @endif
                        </div>

                        <div class="mb-4">
                            <div class="fw-semibold mb-1">Step 3: Move-in confirmation</div>
                            <span class="badge text-bg-{{ $lease->move_in_confirmed_at ? 'success' : 'warning' }}">
                                {{ $lease->move_in_confirmed_at ? 'Confirmed' : 'Pending' }}
                            </span>
                        </div>

                        @if (!$lease->move_in_confirmed_at)
                            <form method="POST"
                                  action="{{ route('admin.leases.confirm-move-in', $lease) }}"
                                  onsubmit="return confirm('Confirm that the tenant is ready to move in?');"
                                  class="mb-3">
                                @csrf
                                <button type="submit" class="btn btn-outline-success w-100">
                                    Confirm Move-in
                                </button>
                            </form>
                        @endif

                        <form method="POST"
                              action="{{ route('admin.leases.activate', $lease) }}"
                              onsubmit="return confirm('Activate this lease? The system will verify all required conditions.');">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-circle me-1"></i>
                                Activate Lease
                            </button>
                        </form>

                        <p class="small text-muted mt-3 mb-0">
                            The server rechecks payment allocations, move-in confirmation,
                            lease acceptance, start date and conflicting active leases
                            when you activate the lease.
                        </p>

                    @elseif ($lease->status === 'active')
                        <div class="alert alert-success mb-0">
                            <h6 class="fw-bold">Lease Active</h6>
                            <p class="mb-1">
                                This lease has been activated.
                            </p>
                            @if ($lease->activated_at)
                                <small>
                                    Activated {{ $lease->activated_at->format('d M Y, H:i') }}
                                </small>
                            @endif
                        </div>

                    @else
                        <div class="alert alert-secondary mb-0">
                            No activation actions are available for this lease status.
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
@endsection