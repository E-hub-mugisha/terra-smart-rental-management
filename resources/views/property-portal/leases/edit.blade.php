
@extends('layouts.app')

@section('title', 'Edit Draft Lease')

@section('content')
<div class="container-fluid py-4 pm-lease-edit">
    <div class="mb-4">
        <a href="{{ route('property-manager.leases.show', $lease) }}"
           class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left me-1"></i> Back to lease
        </a>

        <h3 class="fw-bold mt-3 mb-1">Edit draft lease</h3>
        <p class="text-muted mb-0">
            {{ $lease->lease_number }} · {{ $lease->tenant?->full_name ?? 'Tenant unavailable' }}
        </p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct these errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('property-manager.leases.update', $lease) }}">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0">Lease terms</h6>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tenant</label>
                                <input
                                    class="form-control"
                                    value="{{ $lease->tenant?->full_name ?? '—' }}"
                                    disabled
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Rental unit</label>
                                <input
                                    class="form-control"
                                    value="{{ $lease->unit?->building?->property?->name ?? '—' }} / {{ $lease->unit?->unit_number ?? '—' }}"
                                    disabled
                                >
                            </div>

                            <div class="col-md-6">
                                <label for="start_date" class="form-label">
                                    Lease start date <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="date"
                                    id="start_date"
                                    name="start_date"
                                    required
                                    value="{{ old('start_date', $lease->start_date?->format('Y-m-d')) }}"
                                    class="form-control @error('start_date') is-invalid @enderror"
                                >
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="end_date" class="form-label">
                                    Lease end date <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="date"
                                    id="end_date"
                                    name="end_date"
                                    required
                                    value="{{ old('end_date', $lease->end_date?->format('Y-m-d')) }}"
                                    class="form-control @error('end_date') is-invalid @enderror"
                                >
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="monthly_rent" class="form-label">
                                    Monthly rent <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="monthly_rent"
                                    name="monthly_rent"
                                    required
                                    min="0"
                                    step="0.01"
                                    value="{{ old('monthly_rent', $lease->monthly_rent) }}"
                                    class="form-control @error('monthly_rent') is-invalid @enderror"
                                >
                                @error('monthly_rent')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="deposit_amount" class="form-label">
                                    Security deposit <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="deposit_amount"
                                    name="deposit_amount"
                                    required
                                    min="0"
                                    step="0.01"
                                    value="{{ old('deposit_amount', $lease->deposit_amount) }}"
                                    class="form-control @error('deposit_amount') is-invalid @enderror"
                                >
                                @error('deposit_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="currency" class="form-label">Currency</label>
                                <select
                                    id="currency"
                                    name="currency"
                                    required
                                    class="form-select @error('currency') is-invalid @enderror"
                                >
                                    @foreach(['RWF', 'USD', 'EUR'] as $currency)
                                        <option
                                            value="{{ $currency }}"
                                            @selected(old('currency', $lease->currency ?? 'RWF') === $currency)
                                        >
                                            {{ $currency }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('currency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="rent_due_day" class="form-label">Rent due day</label>
                                <select
                                    id="rent_due_day"
                                    name="rent_due_day"
                                    required
                                    class="form-select @error('rent_due_day') is-invalid @enderror"
                                >
                                    @for($day = 1; $day <= 28; $day++)
                                        <option
                                            value="{{ $day }}"
                                            @selected((int) old('rent_due_day', $lease->rent_due_day ?? 1) === $day)
                                        >
                                            Day {{ $day }} of each month
                                        </option>
                                    @endfor
                                </select>
                                @error('rent_due_day')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="payment_frequency" class="form-label">
                                    Payment frequency
                                </label>
                                <select
                                    id="payment_frequency"
                                    name="payment_frequency"
                                    required
                                    class="form-select @error('payment_frequency') is-invalid @enderror"
                                >
                                    @foreach([
                                        'monthly' => 'Monthly',
                                        'quarterly' => 'Quarterly',
                                        'yearly' => 'Yearly',
                                    ] as $value => $label)
                                        <option
                                            value="{{ $value }}"
                                            @selected(old('payment_frequency', $lease->payment_frequency ?? 'monthly') === $value)
                                        >
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('payment_frequency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="terms" class="form-label">
                                    Lease terms and conditions
                                </label>
                                <textarea
                                    id="terms"
                                    name="terms"
                                    rows="7"
                                    maxlength="10000"
                                    class="form-control @error('terms') is-invalid @enderror"
                                    placeholder="Enter the agreed rental terms and conditions"
                                >{{ old('terms', $lease->terms) }}</textarea>
                                @error('terms')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-brand">
                        <i class="bi bi-check2-circle me-1"></i> Save draft
                    </button>

                    <a href="{{ route('property-manager.leases.show', $lease) }}"
                       class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold">Before tenant acceptance</h6>
                        <ul class="small text-muted ps-3 mb-0">
                            <li class="mb-2">Confirm the tenant and rental unit.</li>
                            <li class="mb-2">Check the lease start and end dates.</li>
                            <li class="mb-2">Confirm the rent, deposit and currency.</li>
                            <li class="mb-2">Confirm the rent due day and payment frequency.</li>
                            <li>Review all lease terms and conditions.</li>
                        </ul>

                        <hr>

                        <div class="small text-muted">
                            The lease remains a draft until it is accepted by the tenant
                            and activated by the property manager after the required checks.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .pm-lease-edit .card { border-radius: 14px; }
    .pm-lease-edit .btn-brand {
        background: #D05208;
        border-color: #D05208;
        color: #fff;
    }
    .pm-lease-edit .btn-brand:hover {
        background: #b94707;
        border-color: #b94707;
        color: #fff;
    }
</style>
@endsection
