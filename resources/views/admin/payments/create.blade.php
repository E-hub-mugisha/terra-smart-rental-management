
@extends('layouts.app')

@section('title', 'Record Rent Payment')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <a href="{{ route('admin.payments.index') }}" class="text-decoration-none">
            &larr; Back to payments
        </a>
        <h3 class="mt-3 mb-1">Record Rent Payment</h3>
        <p class="text-muted mb-0">
            New payments are saved as pending until receipt is verified.
        </p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.payments.store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tenant *</label>
                        <select name="tenant_id" class="form-select" required>
                            <option value="">Select tenant</option>
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}"
                                    @selected(old('tenant_id') == $tenant->id)>
                                    {{ $tenant->full_name }}
                                    ({{ $tenant->tenant_number }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Lease *</label>
                        <select name="lease_id" class="form-select" required>
                            <option value="">Select lease</option>
                            @foreach($leases as $lease)
                                <option value="{{ $lease->id }}"
                                    @selected(old('lease_id') == $lease->id)>
                                    {{ $lease->lease_number }}
                                    — {{ $lease->tenant?->full_name }}
                                    — Unit {{ $lease->unit?->unit_number }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            The selected tenant must belong to the selected lease.
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Amount *</label>
                        <input type="number" name="amount" class="form-control"
                               min="0.01" step="0.01" required
                               value="{{ old('amount') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Currency *</label>
                        <select name="currency" class="form-select" required>
                            @foreach(['RWF', 'USD'] as $currency)
                                <option value="{{ $currency }}"
                                    @selected(old('currency', 'RWF') === $currency)>
                                    {{ $currency }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Payment Date *</label>
                        <input type="date" name="payment_date" class="form-control"
                               required value="{{ old('payment_date', now()->toDateString()) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Payment Method *</label>
                        <select name="payment_method" class="form-select" required>
                            @foreach([
                                'cash' => 'Cash',
                                'bank_transfer' => 'Bank transfer',
                                'mobile_money' => 'Mobile money',
                                'card' => 'Card',
                                'payment_gateway' => 'Payment gateway',
                                'other' => 'Other',
                            ] as $value => $label)
                                <option value="{{ $value }}"
                                    @selected(old('payment_method', 'cash') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Provider</label>
                        <input type="text" name="provider" class="form-control"
                               value="{{ old('provider') }}"
                               placeholder="e.g. MTN MoMo, Airtel Money, bank">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Transaction Reference</label>
                        <input type="text" name="transaction_reference"
                               class="form-control"
                               value="{{ old('transaction_reference') }}"
                               placeholder="Bank reference or mobile money transaction ID">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="3" class="form-control"
                                  placeholder="Optional payment details">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.payments.index') }}"
                       class="btn btn-light">Cancel</a>
                    <button class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Record as Pending
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
