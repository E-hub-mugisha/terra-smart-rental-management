@extends('layouts.app')

@section('title', 'Review Tenant Application')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <a href="{{ route('admin.tenant-applications.index') }}"
               class="text-decoration-none small">
                <i class="bi bi-arrow-left"></i> Back to applications
            </a>

            <h3 class="mt-2 mb-1">
                {{ $tenantApplication->application_number }}
            </h3>

            <p class="text-muted mb-0">Review application and lease terms.</p>
        </div>

        <span class="badge fs-6
            {{ $tenantApplication->status === 'pending' ? 'bg-warning text-dark' : ($tenantApplication->status === 'approved' ? 'bg-success' : 'bg-secondary') }}">
            {{ ucfirst($tenantApplication->status) }}
        </span>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please review the following:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Applicant Information</h5>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted">Full Name</small>
                            <div class="fw-semibold">
                                {{ $tenantApplication->tenant?->full_name ?? 'N/A' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted">Email</small>
                            <div>{{ $tenantApplication->tenant?->email ?? 'N/A' }}</div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted">Phone</small>
                            <div>{{ $tenantApplication->tenant?->phone ?? 'N/A' }}</div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted">Employment Status</small>
                            <div>{{ $tenantApplication->employment_status ?: 'Not specified' }}</div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted">Number of Occupants</small>
                            <div>{{ $tenantApplication->occupants ?? 'Not specified' }}</div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted">Requested Move-in</small>
                            <div>
                                {{ $tenantApplication->desired_move_in_date?->format('d M Y') ?? 'Not specified' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted">Application Date</small>
                            <div>
                                {{ $tenantApplication->application_date?->format('d M Y') ?? $tenantApplication->created_at?->format('d M Y') }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted">Offered Rent</small>
                            <div class="fw-semibold">
                                {{ $tenantApplication->currency ?? 'RWF' }}
                                {{ number_format((float) $tenantApplication->offered_rent, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Requested Unit</h5>
                </div>

                <div class="card-body">
                    @if($tenantApplication->unit)
                        <div class="row g-3">
                            <div class="col-md-6">
                                <small class="text-muted">Unit Number</small>
                                <div class="fw-semibold">
                                    {{ $tenantApplication->unit->unit_number }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">Unit Type</small>
                                <div>
                                    {{ $tenantApplication->unit->unit_type ?? 'Not specified' }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">Listed Rent</small>
                                <div>
                                    {{ $tenantApplication->unit->currency ?? 'RWF' }}
                                    {{ number_format((float) $tenantApplication->unit->rent_amount, 2) }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">Unit Status</small>
                                <div>{{ ucfirst($tenantApplication->unit->status ?? 'unknown') }}</div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            The requested unit could not be found. Approval is unavailable.
                        </div>
                    @endif
                </div>
            </div>

            @if($tenantApplication->decision_notes)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Review Notes</h5>
                    </div>
                    <div class="card-body">
                        {{ $tenantApplication->decision_notes }}
                        @if($tenantApplication->reviewer)
                            <div class="small text-muted mt-2">
                                Reviewed by {{ $tenantApplication->reviewer->name }}
                                @if($tenantApplication->reviewed_at)
                                    on {{ $tenantApplication->reviewed_at->format('d M Y H:i') }}
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            @if($tenantApplication->lease)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-success text-white py-3">
                        <h5 class="mb-0">Lease Created</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted">Lease Number</small>
                            <div class="fw-semibold">
                                {{ $tenantApplication->lease->lease_number }}
                            </div>
                        </div>

                        <div class="mb-2">
                            <small class="text-muted">Start Date</small>
                            <div>
                                {{ $tenantApplication->lease->start_date?->format('d M Y') }}
                            </div>
                        </div>

                        <div class="mb-2">
                            <small class="text-muted">Monthly Rent</small>
                            <div>
                                {{ $tenantApplication->lease->currency }}
                                {{ number_format((float) $tenantApplication->lease->monthly_rent, 2) }}
                            </div>
                        </div>

                        <span class="badge bg-success">
                            {{ ucfirst($tenantApplication->lease->status) }}
                        </span>
                    </div>
                </div>

            @elseif($tenantApplication->status === 'pending' && $tenantApplication->unit)

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Approve & Create Lease</h5>
                    </div>

                    <div class="card-body">
                        <p class="small text-muted">
                            Approval will create a lease and record your decision.
                            Confirm the agreed terms before submitting.
                        </p>

                        <form method="POST"
                              action="{{ route('admin.tenant-applications.approve', $tenantApplication) }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Lease Start Date *</label>
                                <input type="date" name="start_date"
                                       class="form-control"
                                       value="{{ old('start_date', $tenantApplication->desired_move_in_date?->format('Y-m-d') ?? now()->toDateString()) }}"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Lease End Date</label>
                                <input type="date" name="end_date"
                                       class="form-control"
                                       value="{{ old('end_date') }}">
                                <div class="form-text">Leave blank for an open-ended lease.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Agreed Monthly Rent *</label>
                                <input type="number" name="monthly_rent"
                                       class="form-control" min="0.01" step="0.01"
                                       value="{{ old('monthly_rent', $tenantApplication->offered_rent ?? $tenantApplication->unit->rent_amount) }}"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Security Deposit *</label>
                                <input type="number" name="deposit_amount"
                                       class="form-control" min="0" step="0.01"
                                       value="{{ old('deposit_amount', $tenantApplication->unit->deposit_amount ?? 0) }}"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Rent Due Day *</label>
                                <select name="rent_due_day" class="form-select" required>
                                    @for($day = 1; $day <= 28; $day++)
                                        <option value="{{ $day }}"
                                            @selected((int) old('rent_due_day', 1) === $day)>
                                            {{ $day }}{{ in_array($day % 100, [11, 12, 13]) ? 'th' : match ($day % 10) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' } }} of each month
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Payment Frequency *</label>
                                <select name="payment_frequency" class="form-select" required>
                                    @foreach(['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'annually' => 'Annually'] as $value => $label)
                                        <option value="{{ $value }}"
                                            @selected(old('payment_frequency', 'monthly') === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Decision Notes</label>
                                <textarea name="decision_notes" class="form-control" rows="2">{{ old('decision_notes') }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Lease Terms</label>
                                <textarea name="terms" class="form-control" rows="3">{{ old('terms') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-success w-100"
                                    onclick="return confirm('Approve this application and create its lease?')">
                                <i class="bi bi-check-circle me-1"></i>
                                Approve & Create Lease
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6>Reject Application</h6>
                        <form method="POST"
                              action="{{ route('admin.tenant-applications.reject', $tenantApplication) }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Reason for Rejection *</label>
                                <textarea name="decision_notes" class="form-control"
                                          rows="3" required>{{ old('decision_notes') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-outline-danger w-100"
                                    onclick="return confirm('Reject this application?')">
                                Reject Application
                            </button>
                        </form>
                    </div>
                </div>

            @else
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6>Application Decision</h6>
                        <p class="text-muted mb-0">
                            This application is {{ $tenantApplication->status }}.
                            No further decision can be made from this screen.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection