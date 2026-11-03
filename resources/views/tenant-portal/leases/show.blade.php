@extends('layouts.app')

@section('title', 'Lease Details')

@section('content')
<div class="container py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <a href="{{ route('tenant-portal.leases.index') }}"
               class="text-decoration-none small">
                <i class="bi bi-arrow-left me-1"></i> Back to My Leases
            </a>

            <h2 class="fw-bold mt-2 mb-1">Lease Details</h2>

            <p class="text-muted mb-0">
                Lease reference: {{ $lease->lease_number }}
            </p>
        </div>

        <span class="badge fs-6 text-bg-{{
            [
                'draft' => 'secondary',
                'pending_tenant_acceptance' => 'warning',
                'pending_activation' => 'info',
                'active' => 'success',
                'terminated' => 'dark',
                'rejected' => 'danger',
                'cancelled' => 'danger',
                'expired' => 'secondary',
            ][$lease->status] ?? 'secondary'
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
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Please review the following:</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($lease->status === 'pending_tenant_acceptance')
        <div class="alert alert-warning">
            <h6 class="fw-bold mb-1">Draft lease awaiting your acceptance</h6>
            Please review the rental amount, dates, deposit, and terms below
            before accepting this agreement.
        </div>
    @elseif ($lease->status === 'pending_activation')
        <div class="alert alert-info">
            <h6 class="fw-bold mb-1">Lease accepted</h6>
            Your acceptance was recorded
            @if ($lease->tenant_accepted_at)
                on {{ $lease->tenant_accepted_at->format('d M Y, H:i') }}.
            @endif
            The property manager must complete the required payment and move-in
            checks before the lease becomes active.
        </div>
    @elseif ($lease->status === 'active')
        <div class="alert alert-success">
            <h6 class="fw-bold mb-1">Lease is active</h6>
            This rental agreement has been activated.
        </div>
    @elseif ($lease->status === 'draft')
        <div class="alert alert-secondary">
            This lease is still being prepared by the property manager.
            You cannot accept it until it is submitted for your review.
        </div>
    @endif

    <div class="row g-4">

        {{-- Lease summary --}}
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Rental Agreement</h5>
                </div>

                <div class="card-body">
                    <div class="row g-4">

                        <div class="col-md-6">
                            <small class="text-muted d-block">Lease Number</small>
                            <div class="fw-semibold">{{ $lease->lease_number }}</div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted d-block">Application Number</small>
                            <div class="fw-semibold">
                                {{ $lease->application?->application_number ?? 'N/A' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted d-block">Property / Building</small>
                            <div class="fw-semibold">
                                {{ $lease->unit?->building?->name ?? 'N/A' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted d-block">Unit</small>
                            <div class="fw-semibold">
                                {{ $lease->unit?->unit_number ?? 'N/A' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted d-block">Lease Start Date</small>
                            <div class="fw-semibold">
                                {{ $lease->start_date?->format('d M Y') ?? 'N/A' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted d-block">Lease End Date</small>
                            <div class="fw-semibold">
                                {{ $lease->end_date?->format('d M Y') ?? 'N/A' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted d-block">Monthly Rent</small>
                            <div class="fs-5 fw-bold text-primary">
                                {{ $lease->currency }}
                                {{ number_format((float) $lease->monthly_rent, 2) }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted d-block">Security Deposit</small>
                            <div class="fs-5 fw-bold">
                                {{ $lease->currency }}
                                {{ number_format((float) $lease->deposit_amount, 2) }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted d-block">Payment Frequency</small>
                            <div class="fw-semibold">
                                {{ ucwords(str_replace('_', ' ', $lease->payment_frequency)) }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted d-block">Rent Due Day</small>
                            <div class="fw-semibold">
                                Day {{ $lease->rent_due_day }} of the payment month
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Terms --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Terms and Conditions</h5>
                </div>

                <div class="card-body">
                    @if ($lease->terms)
                        <div class="text-break" style="white-space: pre-line;">{{ $lease->terms }}</div>
                    @else
                        <p class="text-muted mb-0">
                            No additional lease terms have been provided.
                            Contact the property manager if you need clarification
                            before accepting.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Acceptance form --}}
            @if ($lease->status === 'draft' && !$lease->tenant_accepted_at)
                <div class="card border-primary shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="fw-bold mb-0">Review and Accept Lease</h5>
                    </div>

                    <div class="card-body">
                        <p class="text-muted">
                            By accepting, you confirm that you have reviewed the
                            lease details and agree to the terms presented.
                        </p>

                        <form method="POST"
                              action="{{ route('tenant-portal.leases.accept', $lease) }}"
                              onsubmit="return confirm('Have you reviewed the lease and agreed to its terms?');">
                            @csrf

                            <div class="form-check mb-4">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="accept_terms"
                                    value="1"
                                    id="acceptLeaseTerms"
                                    required
                                >

                                <label class="form-check-label" for="acceptLeaseTerms">
                                    I have reviewed this lease agreement and agree
                                    to the terms and conditions stated above.
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>
                                Accept Draft Lease
                            </button>
                        </form>
                    </div>
                </div>
            @elseif ($lease->tenant_accepted_at)
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3">
                            <i class="bi bi-check-circle-fill text-success fs-3"></i>

                            <div>
                                <h6 class="fw-bold mb-1">Tenant acceptance recorded</h6>

                                <p class="text-muted mb-0">
                                    Accepted on
                                    {{ $lease->tenant_accepted_at->format('d M Y, H:i') }}.
                                    The lease must still satisfy the activation
                                    requirements before it becomes active.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>

        {{-- Status and progress --}}
        <div class="col-lg-4">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Lease Progress</h5>
                </div>

                <div class="card-body">
                    @php
                        $accepted = !is_null($lease->tenant_accepted_at);
                        $moveInConfirmed = !is_null($lease->move_in_confirmed_at);
                        $activated = !is_null($lease->activated_at)
                            || $lease->status === 'active';
                    @endphp

                    <div class="d-flex gap-3 mb-4">
                        <i class="bi bi-{{ $accepted ? 'check-circle-fill text-success' : 'circle text-muted' }} fs-5"></i>
                        <div>
                            <div class="fw-semibold">Tenant acceptance</div>
                            <small class="text-muted">
                                {{ $accepted ? 'Lease accepted' : 'Awaiting your acceptance' }}
                            </small>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mb-4">
                        <i class="bi bi-{{ $moveInConfirmed ? 'check-circle-fill text-success' : 'circle text-muted' }} fs-5"></i>
                        <div>
                            <div class="fw-semibold">Move-in confirmation</div>
                            <small class="text-muted">
                                {{ $moveInConfirmed ? 'Confirmed by management' : 'Awaiting management confirmation' }}
                            </small>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <i class="bi bi-{{ $activated ? 'check-circle-fill text-success' : 'circle text-muted' }} fs-5"></i>
                        <div>
                            <div class="fw-semibold">Lease activation</div>
                            <small class="text-muted">
                                {{ $activated ? 'Lease activated' : 'Payment and activation checks pending' }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            @if ($lease->application)
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold">Related Application</h6>

                        <p class="text-muted small mb-2">
                            Application:
                            {{ $lease->application->application_number }}
                        </p>

                        <a href="{{ route('tenant-portal.applications.show', $lease->application) }}"
                           class="btn btn-outline-secondary btn-sm w-100">
                            View Application
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
@endsection