@extends('layouts.app')

@section('title', 'Invoice Details')

@section('content')
@php
    $isOverdue = !in_array($invoice->status, ['paid', 'cancelled', 'draft'])
        && $invoice->balance_due > 0
        && $invoice->due_date
        && \Illuminate\Support\Carbon::parse($invoice->due_date)->isPast()
        && !\Illuminate\Support\Carbon::parse($invoice->due_date)->isToday();

    $displayStatus = $isOverdue ? 'overdue' : $invoice->status;
@endphp

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <a href="{{ route('property-manager.invoices.index') }}"
               class="small text-decoration-none text-muted">
                <i class="bi bi-arrow-left me-1"></i> Back to invoices
            </a>
            <h1 class="h3 fw-bold mt-2 mb-1">{{ $invoice->invoice_number }}</h1>
            <p class="text-muted mb-0">Invoice details, outstanding balance and payment allocations.</p>
        </div>

        @if((float) $invoice->balance_due > 0 && !in_array($invoice->status, ['cancelled', 'draft']))
            <a href="{{ route('property-manager.payments.create') }}"
               class="btn btn-terra">
                <i class="bi bi-plus-lg me-1"></i> Record Payment
            </a>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
                        <div>
                            <div class="text-muted small mb-1">Invoice type</div>
                            <div class="fw-semibold">{{ str($invoice->invoice_type)->replace('_', ' ')->title() }}</div>
                        </div>
                        <div>
                            <div class="text-muted small mb-1">Status</div>
                            <span class="status-pill status-{{ $displayStatus }}">
                                {{ str($displayStatus)->replace('_', ' ')->title() }}
                            </span>
                        </div>
                        <div>
                            <div class="text-muted small mb-1">Issue date</div>
                            <div class="fw-semibold">
                                {{ $invoice->issue_date ? \Illuminate\Support\Carbon::parse($invoice->issue_date)->format('d M Y') : '—' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-muted small mb-1">Due date</div>
                            <div class="fw-semibold">
                                {{ $invoice->due_date ? \Illuminate\Support\Carbon::parse($invoice->due_date)->format('d M Y') : '—' }}
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h2 class="h6 fw-bold mb-3">Invoice breakdown</h2>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <tbody>
                                <tr>
                                    <td>Invoice total</td>
                                    <td class="text-end fw-semibold">
                                        {{ $invoice->currency }} {{ number_format((float) $invoice->total_amount, 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>Amount paid</td>
                                    <td class="text-end">
                                        {{ $invoice->currency }} {{ number_format((float) $invoice->amount_paid, 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Outstanding balance</td>
                                    <td class="text-end fw-bold text-danger">
                                        {{ $invoice->currency }} {{ number_format((float) $invoice->balance_due, 2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @if($invoice->description)
                        <h2 class="h6 fw-bold mt-4">Description</h2>
                        <p class="text-muted mb-0">{{ $invoice->description }}</p>
                    @endif

                    @if($invoice->notes)
                        <h2 class="h6 fw-bold mt-4">Notes</h2>
                        <p class="text-muted mb-0">{{ $invoice->notes }}</p>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h2 class="h6 fw-bold mb-0">Payment allocation history</h2>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th class="text-end">Allocated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->allocations as $allocation)
                                @php $payment = $allocation->payment; @endphp
                                @if($payment)
                                    <tr>
                                        <td>
                                            <a href="{{ route('property-manager.payments.show', $payment->id) }}"
                                               class="text-decoration-none fw-semibold">
                                                {{ $payment->payment_number }}
                                            </a>
                                            @if($payment->transaction_reference)
                                                <div class="small text-muted">{{ $payment->transaction_reference }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $payment->payment_date ? \Illuminate\Support\Carbon::parse($payment->payment_date)->format('d M Y') : '—' }}</td>
                                        <td>{{ str($payment->payment_method)->replace('_', ' ')->title() }}</td>
                                        <td>{{ str($payment->status)->title() }}</td>
                                        <td class="text-end text-nowrap">
                                            {{ $invoice->currency }} {{ number_format((float) $allocation->amount, 2) }}
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No payments have been allocated to this invoice.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-3">Tenant</h2>
                    <div class="fw-semibold">{{ $invoice->tenant?->full_name ?? 'Tenant unavailable' }}</div>
                    <div class="text-muted small">{{ $invoice->tenant?->email }}</div>
                    <div class="text-muted small">{{ $invoice->tenant?->phone }}</div>

                    <hr>

                    <h2 class="h6 fw-bold mb-3">Rental details</h2>
                    <div class="small text-muted">Property</div>
                    <div class="fw-semibold mb-3">
                        {{ $invoice->unit?->building?->property?->name ?? '—' }}
                    </div>

                    <div class="small text-muted">Building / Unit</div>
                    <div class="fw-semibold mb-3">
                        {{ $invoice->unit?->building?->name ?? '—' }}
                        / {{ $invoice->unit?->unit_number ?? '—' }}
                    </div>

                    <div class="small text-muted">Lease</div>
                    <div class="fw-semibold">
                        {{ $invoice->lease?->lease_number ?? '—' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .btn-terra { background:#D05208; border-color:#D05208; color:#fff; }
    .btn-terra:hover { background:#ad4306; border-color:#ad4306; color:#fff; }
    .status-pill { display:inline-block; border-radius:30px; padding:5px 10px; font-size:.75rem; font-weight:600; background:#eef0f3; color:#4b5563; }
    .status-paid { background:#e3f6eb; color:#17663b; }
    .status-issued { background:#e9efff; color:#2448a5; }
    .status-partially_paid { background:#fff1d6; color:#885600; }
    .status-overdue { background:#fde8e7; color:#a12520; }
</style>
@endsection