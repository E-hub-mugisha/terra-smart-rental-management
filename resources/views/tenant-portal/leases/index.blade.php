@extends('layouts.app')

@section('title', 'My Leases')

@section('content')
<div class="container py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1">My Leases</h2>
            <p class="text-muted mb-0">
                Review your rental agreements and their current status.
            </p>
        </div>

        <a href="{{ route('tenant-portal.applications.index') }}"
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>
            My Applications
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($leases->count())
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Lease Number</th>
                            <th>Property / Unit</th>
                            <th>Monthly Rent</th>
                            <th>Lease Period</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($leases as $lease)
                            @php
                                $statusStyles = [
                                    'draft' => 'secondary',
                                    'pending_tenant_acceptance' => 'warning',
                                    'pending_activation' => 'info',
                                    'active' => 'success',
                                    'terminated' => 'dark',
                                    'rejected' => 'danger',
                                    'cancelled' => 'danger',
                                    'expired' => 'secondary',
                                ];

                                $statusLabels = [
                                    'draft' => 'Draft',
                                    'pending_tenant_acceptance' => 'Awaiting Acceptance',
                                    'pending_activation' => 'Awaiting Activation',
                                    'active' => 'Active',
                                    'terminated' => 'Terminated',
                                    'rejected' => 'Rejected',
                                    'cancelled' => 'Cancelled',
                                    'expired' => 'Expired',
                                ];

                                $status = $lease->status;
                            @endphp

                            <tr>
                                <td>
                                    <span class="fw-semibold">
                                        {{ $lease->lease_number }}
                                    </span>
                                </td>

                                <td>
                                    <div class="fw-semibold">
                                        {{ $lease->unit?->building?->name ?? 'Property' }}
                                    </div>
                                    <small class="text-muted">
                                        Unit {{ $lease->unit?->unit_number ?? 'N/A' }}
                                    </small>
                                </td>

                                <td>
                                    {{ $lease->currency }}
                                    {{ number_format((float) $lease->monthly_rent, 2) }}
                                </td>

                                <td>
                                    <div>
                                        {{ $lease->start_date?->format('d M Y') ?? 'N/A' }}
                                    </div>
                                    <small class="text-muted">
                                        to {{ $lease->end_date?->format('d M Y') ?? 'N/A' }}
                                    </small>
                                </td>

                                <td>
                                    <span class="badge text-bg-{{ $statusStyles[$status] ?? 'secondary' }}">
                                        {{ $statusLabels[$status] ?? ucwords(str_replace('_', ' ', $status)) }}
                                    </span>
                                </td>

                                <td class="text-end">
                                    <a href="{{ route('tenant-portal.leases.show', $lease) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        @if ($status === 'pending_tenant_acceptance')
                                            Review & Accept
                                        @else
                                            View Lease
                                        @endif
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $leases->links() }}
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="mb-3 text-muted">
                    <i class="bi bi-file-earmark-text display-4"></i>
                </div>

                <h5 class="fw-bold">No leases available</h5>

                <p class="text-muted mb-4">
                    When a lease is prepared for one of your rental applications,
                    it will appear here.
                </p>

                <a href="{{ route('tenant-portal.applications.index') }}"
                   class="btn btn-primary">
                    View My Applications
                </a>
            </div>
        </div>
    @endif

</div>
@endsection