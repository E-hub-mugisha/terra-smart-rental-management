
@extends('layouts.app')

@section('title', 'Application Details')

@section('content')
<div class="container py-4">

    <a href="{{ route('tenant-portal.applications.index') }}"
       class="text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i> My Applications
    </a>

    {{-- Page heading --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-3 mb-4">
        <div>
            <h3 class="mb-1">Application Details</h3>
            <p class="text-muted mb-0">
                {{ $tenantApplication->application_number }}
            </p>
        </div>

        <span class="badge fs-6
            @if($tenantApplication->status === 'pending')
                bg-warning text-dark
            @elseif($tenantApplication->status === 'approved')
                bg-success
            @elseif($tenantApplication->status === 'rejected')
                bg-danger
            @else
                bg-secondary
            @endif">
            {{ ucfirst($tenantApplication->status) }}
        </span>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close"
                    data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close"
                    data-bs-dismiss="alert" aria-label="Close"></button>
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

    {{-- Approved application with a draft lease --}}
    @if(
        $tenantApplication->status === 'approved'
        && $tenantApplication->lease
        && $tenantApplication->lease->status === 'draft'
    )
        <div class="alert alert-primary border-0 shadow-sm">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-file-earmark-check fs-2"></i>

                <div>
                    <h5 class="fw-bold mb-1">
                        Your rental application has been approved!
                    </h5>

                    <p class="mb-0">
                        The property manager has prepared a draft lease.
                        Please review the details and terms below. You must
                        accept the draft before the manager can proceed with
                        payment verification and move-in arrangements.
                    </p>
                </div>
            </div>
        </div>
    @elseif(
        $tenantApplication->lease
        && $tenantApplication->lease->status === 'pending_activation'
    )
        <div class="alert alert-info border-0">
            <h5 class="fw-bold">
                <i class="bi bi-hourglass-split me-2"></i>
                Lease accepted — awaiting activation
            </h5>
            <p class="mb-0">
                Your acceptance has been recorded. The property manager
                must confirm the required payments and move-in conditions
                before your tenancy becomes active.
            </p>
        </div>
    @elseif(
        $tenantApplication->lease
        && $tenantApplication->lease->status === 'active'
    )
        <div class="alert alert-success border-0">
            <h5 class="fw-bold">
                <i class="bi bi-check-circle me-2"></i>
                Your tenancy is active
            </h5>
            <p class="mb-0">
                Your lease is active. You can view the lease details
                and continue managing your rental through the tenant portal.
            </p>
        </div>
    @endif

    <div class="row g-4">

        {{-- Application information --}}
        <div class="col-lg-7">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Application Information</h5>
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <small class="text-muted">Application Number</small>
                            <div class="fw-semibold">
                                {{ $tenantApplication->application_number }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted">Submitted</small>
                            <div class="fw-semibold">
                                {{ $tenantApplication->application_date?->format('d M Y')
                                    ?? $tenantApplication->created_at?->format('d M Y')
                                    ?? '—' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted">Desired Move-in Date</small>
                            <div class="fw-semibold">
                                {{ $tenantApplication->desired_move_in_date?->format('d M Y')
                                    ?? 'Not specified' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted">Number of Occupants</small>
                            <div>
                                {{ $tenantApplication->occupants ?? 'Not specified' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted">Employment Status</small>
                            <div>
                                {{ $tenantApplication->employment_status ?: 'Not specified' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted">Offered Monthly Rent</small>
                            <div class="fw-semibold">
                                {{ $tenantApplication->currency ?? 'RWF' }}
                                {{ number_format((float) $tenantApplication->offered_rent, 0) }}
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Decision feedback --}}
            @if($tenantApplication->decision_notes)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Decision Feedback</h5>
                    </div>

                    <div class="card-body">
                        {{ $tenantApplication->decision_notes }}
                    </div>
                </div>
            @endif

            {{-- DRAFT LEASE REVIEW --}}
            @if($tenantApplication->lease)
                @php
                    $lease = $tenantApplication->lease;
                @endphp

                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0">
                                <i class="bi bi-file-earmark-text text-primary me-2"></i>
                                Draft Lease Agreement
                            </h5>

                            <span class="badge
                                @if($lease->status === 'active')
                                    bg-success
                                @elseif($lease->status === 'pending_tenant_acceptance')
                                    bg-warning text-dark
                                @elseif($lease->status === 'pending_activation')
                                    bg-info text-dark
                                @else
                                    bg-secondary
                                @endif">
                                {{ ucwords(str_replace('_', ' ', $lease->status)) }}
                            </span>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="alert alert-light border">
                            <div class="small text-muted">Lease Number</div>
                            <div class="fw-bold fs-5">
                                {{ $lease->lease_number }}
                            </div>
                        </div>

                        {{-- Lease terms and financial details --}}
                        <div class="row g-3 mb-4">

                            <div class="col-md-6">
                                <small class="text-muted">Lease Start Date</small>
                                <div class="fw-semibold">
                                    {{ $lease->start_date?->format('d M Y') ?? 'Not specified' }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">Lease End Date</small>
                                <div class="fw-semibold">
                                    {{ $lease->end_date?->format('d M Y') ?? 'Not specified' }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">Monthly Rent</small>
                                <div class="fw-bold fs-5 text-primary">
                                    {{ $lease->currency ?? 'RWF' }}
                                    {{ number_format((float) $lease->monthly_rent, 0) }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">Security Deposit</small>
                                <div class="fw-bold fs-5">
                                    {{ $lease->currency ?? 'RWF' }}
                                    {{ number_format((float) $lease->deposit_amount, 0) }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">Payment Frequency</small>
                                <div class="fw-semibold">
                                    {{ ucfirst(str_replace('_', ' ', $lease->payment_frequency ?? 'monthly')) }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">Rent Due Day</small>
                                <div class="fw-semibold">
                                    {{ $lease->rent_due_day
                                        ? 'Day ' . $lease->rent_due_day . ' of each month'
                                        : 'As agreed' }}
                                </div>
                            </div>

                        </div>

                        {{-- Lease terms --}}
                        <h6 class="fw-bold">Lease Terms and Conditions</h6>

                        <div class="border rounded-3 p-3 mb-4"
                             style="max-height:350px; overflow-y:auto;">
                            @if($lease->terms)
                                <div style="white-space:pre-line;">{{ $lease->terms }}</div>
                            @else
                                <p class="text-muted mb-0">
                                    No additional terms have been provided.
                                    Please contact the property manager if you
                                    need clarification before accepting.
                                </p>
                            @endif
                        </div>

                        {{-- Acceptance status --}}
                        @if($lease->tenant_accepted_at)
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                You accepted this lease on
                                <strong>
                                    {{ $lease->tenant_accepted_at->format('d M Y \a\t H:i') }}
                                </strong>.
                            </div>
                        @endif

                        {{-- Accept draft lease --}}
                        @if(
                            $lease->status === 'pending_tenant_acceptance'
                            && !$lease->tenant_accepted_at
                        )
                            <div class="bg-light rounded-3 p-3">
                                <h6 class="fw-bold mb-2">
                                    Accept your draft lease
                                </h6>

                                <p class="small text-muted">
                                    Confirm that you have reviewed the lease
                                    information, rent, deposit, dates, and terms.
                                    Acceptance does not activate the lease
                                    immediately. Required payments and move-in
                                    conditions must still be completed.
                                </p>

                                <form method="POST"
                                      action="{{ route('tenant-portal.leases.accept', $lease) }}"
                                      onsubmit="return confirm('Are you sure you have reviewed the lease and agree to its terms?');">
                                    @csrf

                                    <div class="form-check mb-3">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="accept_terms"
                                               value="1"
                                               id="acceptLeaseTerms"
                                               required>

                                        <label class="form-check-label"
                                               for="acceptLeaseTerms">
                                            I have reviewed the draft lease and
                                            agree to the terms and conditions.
                                        </label>
                                    </div>

                                    <button type="submit"
                                            class="btn btn-primary w-100">
                                        <i class="bi bi-check-circle me-2"></i>
                                        Accept Draft Lease
                                    </button>
                                </form>
                            </div>

                        @elseif($lease->status === 'pending_activation')
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-clock-history me-2"></i>
                                Your acceptance is complete. Await payment
                                verification and move-in confirmation.
                            </div>

                        @elseif($lease->status === 'active')
                            <div class="alert alert-success mb-0">
                                <i class="bi bi-house-check me-2"></i>
                                This lease is active.
                            </div>
                        @endif

                    </div>
                </div>
            @endif

        </div>

        {{-- Rental and lease summary --}}
        <div class="col-lg-5">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Requested Rental</h5>
                </div>

                <div class="card-body">

                    <div class="fw-semibold fs-5">
                        {{ $tenantApplication->unit?->building?->name
                            ?? 'Rental Property' }}
                    </div>

                    <p class="text-muted mb-3">
                        Unit {{ $tenantApplication->unit?->unit_number
                            ?? 'Unavailable' }}
                    </p>

                    @if($tenantApplication->unit)
                        <div class="mb-3">
                            <small class="text-muted">Listed Monthly Rent</small>
                            <div class="fw-semibold">
                                {{ $tenantApplication->unit->currency ?? 'RWF' }}
                                {{ number_format((float) $tenantApplication->unit->rent_amount, 0) }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted">Security Deposit</small>
                            <div class="fw-semibold">
                                {{ $tenantApplication->unit->currency ?? 'RWF' }}
                                {{ number_format((float) ($tenantApplication->unit->deposit_amount ?? 0), 0) }}
                            </div>
                        </div>
                    @endif

                    @if($tenantApplication->unit?->description)
                        <hr>
                        <p class="small text-muted mb-0">
                            {{ $tenantApplication->unit->description }}
                        </p>
                    @endif

                </div>
            </div>

            {{-- Application progress --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Application Progress</h5>
                </div>

                <div class="card-body">
                    <div class="d-flex gap-3 mb-3">
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <div>
                            <div class="fw-semibold">Application submitted</div>
                            <small class="text-muted">
                                Your application has been recorded.
                            </small>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mb-3">
                        @if($tenantApplication->status === 'approved')
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        @elseif($tenantApplication->status === 'rejected')
                            <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                        @else
                            <i class="bi bi-clock text-warning fs-5"></i>
                        @endif

                        <div>
                            <div class="fw-semibold">Manager review</div>
                            <small class="text-muted">
                                @if($tenantApplication->status === 'approved')
                                    Your application was approved.
                                @elseif($tenantApplication->status === 'rejected')
                                    Your application was not approved.
                                @else
                                    Waiting for the property manager's decision.
                                @endif
                            </small>
                        </div>
                    </div>

                    @if($tenantApplication->lease)
                        <div class="d-flex gap-3 mb-3">
                            @if($tenantApplication->lease->tenant_accepted_at)
                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            @else
                                <i class="bi bi-clock text-warning fs-5"></i>
                            @endif

                            <div>
                                <div class="fw-semibold">Lease acceptance</div>
                                <small class="text-muted">
                                    @if($tenantApplication->lease->tenant_accepted_at)
                                        Draft lease accepted.
                                    @else
                                        Review and accept the draft lease.
                                    @endif
                                </small>
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            @if($tenantApplication->lease->status === 'active')
                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            @else
                                <i class="bi bi-clock text-warning fs-5"></i>
                            @endif

                            <div>
                                <div class="fw-semibold">Tenancy activation</div>
                                <small class="text-muted">
                                    @if($tenantApplication->lease->status === 'active')
                                        Your tenancy is active.
                                    @else
                                        Pending required payments and move-in confirmation.
                                    @endif
                                </small>
                            </div>
                        </div>
                    @elseif($tenantApplication->status === 'pending')
                        <div class="small text-muted">
                            The draft lease will appear here after approval.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Lease shortcut --}}
            @if($tenantApplication->lease)
                <a href="{{ route('tenant-portal.leases.show', $tenantApplication->lease) }}"
                   class="btn btn-outline-primary w-100">
                    <i class="bi bi-file-earmark-text me-2"></i>
                    Open Full Lease Details
                </a>
            @endif

        </div>
    </div>
</div>
@endsection
