
@extends('layouts.app')

@section('title', 'Rent Payments')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="mb-1">Rent Payments</h3>
            <p class="text-muted mb-0">Record payments, verify receipts, and allocate funds to invoices.</p>
        </div>
        <a href="{{ route('admin.payments.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Record Payment
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please check the following:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-4">
        @foreach([
            'all' => ['All Payments', 'primary'],
            'pending' => ['Pending', 'warning'],
            'confirmed' => ['Confirmed', 'success'],
            'failed' => ['Failed', 'danger'],
        ] as $key => $item)
            <div class="col-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">{{ $item[0] }}</div>
                        <div class="fs-3 fw-bold text-{{ $item[1] }}">
                            {{ number_format($counts[$key] ?? 0) }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.payments.index') }}"
                  class="row g-2 mb-4">
                <div class="col-md-6">
                    <input type="search" name="search" class="form-control"
                           value="{{ request('search') }}"
                           placeholder="Payment number, reference, tenant...">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach(['pending', 'confirmed', 'failed'] as $status)
                            <option value="{{ $status }}"
                                @selected(request('status') === $status)>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-outline-primary">Filter</button>
                    <a href="{{ route('admin.payments.index') }}"
                       class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Payment</th>
                            <th>Tenant</th>
                            <th>Lease / Unit</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>
                                <strong>{{ $payment->payment_number }}</strong>
                                <div class="small text-muted">
                                    {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                                </div>
                                @if($payment->transaction_reference)
                                    <div class="small text-muted">
                                        Ref: {{ $payment->transaction_reference }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                {{ $payment->tenant?->full_name ?? '—' }}
                                <div class="small text-muted">
                                    {{ $payment->tenant?->tenant_number }}
                                </div>
                            </td>
                            <td>
                                {{ $payment->lease?->lease_number ?? '—' }}
                                <div class="small text-muted">
                                    {{ $payment->unit?->unit_number ?? '—' }}
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <strong>{{ $payment->currency }}
                                    {{ number_format((float)$payment->amount, 2) }}</strong>
                            </td>
                            <td>{{ $payment->payment_date?->format('d M Y') ?? '—' }}</td>
                            <td>
                                @php
                                    $badge = match($payment->status) {
                                        'confirmed' => 'success',
                                        'pending' => 'warning',
                                        'failed' => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp
                                <span class="badge text-bg-{{ $badge }}">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.payments.show', $payment) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                No rent payments found.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{ $payments->links() }}
        </div>
    </div>
</div>
@endsection
