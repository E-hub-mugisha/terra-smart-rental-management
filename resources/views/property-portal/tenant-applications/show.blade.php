@extends('layouts.app')

@section('title', 'Rental Application Details')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <a href="{{ route('property-manager.applications.index') }}"
               class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i> Back to applications
            </a>

            <h1 class="h3 fw-bold mt-2 mb-1">Rental Application</h1>

            <p class="text-muted mb-0">
                {{ $application->application_number }}
            </p>
        </div>

        <span class="application-status status-{{ $application->status }}">
            {{ str($application->status)->replace('_', ' ')->title() }}
        </span>
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
            <div class="fw-semibold mb-2">Please correct the following:</div>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-8">

            {{-- Applicant details --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 p-4 pb-2">
                    <h2 class="h5 fw-bold mb-0">Applicant information</h2>
                </div>

                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="detail-label">Full name</div>
                            <div class="detail-value">
                                {{ $application->tenant?->full_name ?? '—' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Email</div>
                            <div class="detail-value">
                                {{ $application->tenant?->email ?? '—' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value">
                                {{ $application->tenant?->phone ?? '—' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Employment status</div>
                            <div class="detail-value">
                                {{ $application->employment_status
                                    ? str($application->employment_status)->replace('_', ' ')->title()
                                    : '—' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Number of occupants</div>
                            <div class="detail-value">
                                {{ $application->number_of_occupants ?? '—' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Application date</div>
                            <div class="detail-value">
                                {{ $application->application_date?->format('d M Y') ?? '—' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Desired move-in date</div>
                            <div class="detail-value">
                                {{ $application->desired_move_in_date?->format('d M Y') ?? '—' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Applicant's offered rent</div>
                            <div class="detail-value">
                                {{ $application->currency }}
                                {{ number_format((float) $application->offered_rent, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Unit details --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 p-4 pb-2">
                    <h2 class="h5 fw-bold mb-0">Requested rental unit</h2>
                </div>

                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="detail-label">Property</div>
                            <div class="detail-value">
                                {{ $application->unit?->building?->property?->name ?? '—' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Building</div>
                            <div class="detail-value">
                                {{ $application->unit?->building?->name ?? '—' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Unit number</div>
                            <div class="detail-value">
                                {{ $application->unit?->unit_number ?? '—' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Current unit status</div>
                            <div class="detail-value">
                                {{ $application->unit?->status
                                    ? str($application->unit->status)->replace('_', ' ')->title()
                                    : '—' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Monthly rent</div>
                            <div class="detail-value">
                                {{ $application->unit?->currency ?? $application->currency }}
                                {{ number_format((float) ($application->unit?->rent_amount ?? 0), 2) }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Address</div>
                            <div class="detail-value">
                                {{ $application->unit?->building?->property?->address ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Decision notes --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 p-4 pb-2">
                    <h2 class="h5 fw-bold mb-0">Review decision</h2>
                </div>

                <div class="card-body p-4">
                    <div class="detail-label">Decision notes</div>

                    @if($application->decision_notes)
                        <div class="decision-notes">
                            {{ $application->decision_notes }}
                        </div>
                    @else
                        <p class="text-muted mb-0">No decision notes have been recorded.</p>
                    @endif

                    <hr>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-label">Reviewed by</div>
                            <div class="detail-value">
                                {{ $application->reviewer?->name ?? 'Not reviewed' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-label">Reviewed at</div>
                            <div class="detail-value">
                                {{ $application->reviewed_at?->format('d M Y, H:i') ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Created lease --}}
            @if($application->lease)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 p-4 pb-2">
                        <h2 class="h5 fw-bold mb-0">Associated lease</h2>
                    </div>

                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="detail-label">Lease number</div>
                                <div class="detail-value">
                                    {{ $application->lease->lease_number }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="detail-label">Lease status</div>
                                <div class="detail-value">
                                    {{ str($application->lease->status)->replace('_', ' ')->title() }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="detail-label">Start date</div>
                                <div class="detail-value">
                                    {{ $application->lease->start_date?->format('d M Y') ?? '—' }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="detail-label">End date</div>
                                <div class="detail-value">
                                    {{ $application->lease->end_date?->format('d M Y') ?? '—' }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="detail-label">Tenant acceptance</div>
                                <div class="detail-value">
                                    {{ $application->lease->tenant_accepted_at?->format('d M Y') ?? 'Pending' }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="detail-label">Move-in confirmation</div>
                                <div class="detail-value">
                                    {{ $application->lease->move_in_confirmed_at?->format('d M Y') ?? 'Pending' }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="detail-label">Activation date</div>
                                <div class="detail-value">
                                    {{ $application->lease->activated_at?->format('d M Y') ?? 'Not activated' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-xl-4">

            {{-- Start review --}}
            @if($application->status === 'pending')
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-bold">Start review</h2>
                        <p class="text-muted small">
                            Mark this application as under review before making a decision.
                        </p>

                        <form method="POST"
                              action="{{ route('property-manager.applications.review', $application) }}">
                            @csrf
                            @method('PATCH')

                            <button type="submit" class="btn btn-outline-primary w-100">
                                Move to review
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Approve and create draft lease --}}
            @if(in_array($application->status, ['pending', 'under_review'], true))
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 p-4 pb-0">
                        <h2 class="h5 fw-bold mb-1">Approve application</h2>
                        <p class="small text-muted mb-0">
                            Approval creates a draft lease for tenant acceptance.
                        </p>
                    </div>

                    <div class="card-body p-4">
                        <form method="POST"
                              action="{{ route('property-manager.applications.approve', $application) }}">
                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label class="form-label">Lease start date</label>
                                <input type="date"
                                       name="start_date"
                                       class="form-control"
                                       value="{{ old('start_date', $application->desired_move_in_date?->format('Y-m-d') ?? now()->toDateString()) }}"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Lease end date</label>
                                <input type="date"
                                       name="end_date"
                                       class="form-control"
                                       value="{{ old('end_date', now()->addYear()->toDateString()) }}"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Monthly rent</label>
                                <input type="number"
                                       name="monthly_rent"
                                       class="form-control"
                                       min="0"
                                       step="0.01"
                                       value="{{ old('monthly_rent', $application->offered_rent ?? $application->unit?->rent_amount) }}"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Security deposit</label>
                                <input type="number"
                                       name="deposit_amount"
                                       class="form-control"
                                       min="0"
                                       step="0.01"
                                       value="{{ old('deposit_amount', $application->unit?->deposit_amount ?? 0) }}"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Currency</label>
                                <select name="currency" class="form-select" required>
                                    @foreach(['RWF', 'USD', 'EUR'] as $currency)
                                        <option value="{{ $currency }}"
                                            @selected(old('currency', $application->currency ?: 'RWF') === $currency)>
                                            {{ $currency }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Rent due day</label>
                                <select name="rent_due_day" class="form-select" required>
                                    @for($day = 1; $day <= 28; $day++)
                                        <option value="{{ $day }}"
                                            @selected(old('rent_due_day', 1) == $day)>
                                            {{ $day }}{{ in_array($day % 100, [11, 12, 13]) ? 'th' : match($day % 10) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' } }} of each month
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Payment frequency</label>
                                <select name="payment_frequency" class="form-select" required>
                                    @foreach(['monthly', 'quarterly', 'yearly'] as $frequency)
                                        <option value="{{ $frequency }}"
                                            @selected(old('payment_frequency', 'monthly') === $frequency)>
                                            {{ str($frequency)->title() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Decision notes</label>
                                <textarea name="decision_notes"
                                          class="form-control"
                                          rows="3"
                                          maxlength="5000"
                                          placeholder="Optional approval notes">{{ old('decision_notes') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-terra w-100">
                                Approve and create draft lease
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Reject application --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-1">Reject application</h2>
                        <p class="small text-muted">
                            Record the reason in the application's decision notes.
                        </p>

                        <form method="POST"
                              action="{{ route('property-manager.applications.reject', $application) }}"
                              onsubmit="return confirm('Are you sure you want to reject this application?')">
                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label class="form-label">Reason for rejection</label>
                                <textarea name="decision_notes"
                                          class="form-control"
                                          rows="4"
                                          maxlength="5000"
                                          required
                                          placeholder="Explain why this application is being rejected">{{ old('decision_notes') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-outline-danger w-100">
                                Reject application
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .application-status {
        display: inline-flex;
        align-items: center;
        padding: 7px 13px;
        border-radius: 30px;
        font-size: .8rem;
        font-weight: 700;
        background: #eef0f3;
        color: #4b5563;
    }

    .status-pending {
        background: #fff1d6;
        color: #885600;
    }

    .status-under_review {
        background: #e5efff;
        color: #1c4e91;
    }

    .status-approved {
        background: #e3f6eb;
        color: #17663b;
    }

    .status-rejected {
        background: #fde8e7;
        color: #a12520;
    }

    .detail-label {
        color: #6b7280;
        font-size: .82rem;
        margin-bottom: 5px;
    }

    .detail-value {
        color: #19265d;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .decision-notes {
        white-space: pre-line;
        overflow-wrap: anywhere;
    }

    .btn-terra {
        background: #D05208;
        border-color: #D05208;
        color: #fff;
    }

    .btn-terra:hover {
        background: #ad4306;
        border-color: #ad4306;
        color: #fff;
    }
</style>
@endsection