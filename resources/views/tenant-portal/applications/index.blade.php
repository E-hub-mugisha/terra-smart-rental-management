@extends('layouts.app')

@section('title', 'My Rental Applications')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h3 class="mb-1">My Rental Applications</h3>
            <p class="text-muted mb-0">
                Track your application progress and decisions.
            </p>
        </div>

        <a href="{{ route('rentals.index') }}" class="btn btn-primary">
            <i class="bi bi-search me-1"></i> Browse Rentals
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(!$tenant)
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-person-circle fs-1 text-muted"></i>
                <h5 class="mt-3">Tenant profile not yet created</h5>
                <p class="text-muted">
                    Browse a rental and submit an application to create your tenant profile.
                </p>
                <a href="{{ route('rentals.index') }}" class="btn btn-primary">
                    Browse Available Units
                </a>
            </div>
        </div>
    @elseif($applications->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-file-earmark-text fs-1 text-muted"></i>
                <h5 class="mt-3">No applications yet</h5>
                <p class="text-muted">
                    Your rental applications and their statuses will appear here.
                </p>
                <a href="{{ route('rentals.index') }}" class="btn btn-primary">
                    Find a Rental
                </a>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Application</th>
                            <th>Rental Unit</th>
                            <th>Offered Rent</th>
                            <th>Move-in Date</th>
                            <th>Application Status</th>
                            <th>Lease Status</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                    @foreach($applications as $application)
                        <tr>
                            <td>
                                <div class="fw-semibold">
                                    {{ $application->application_number }}
                                </div>
                                <small class="text-muted">
                                    {{ $application->application_date?->format('d M Y') ?? $application->created_at?->format('d M Y') }}
                                </small>
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $application->unit?->building?->name ?? 'Rental Property' }}
                                </div>
                                <small class="text-muted">
                                    Unit {{ $application->unit?->unit_number ?? 'Unavailable' }}
                                </small>
                            </td>

                            <td>
                                {{ $application->currency ?? 'RWF' }}
                                {{ number_format((float) $application->offered_rent, 0) }}
                            </td>

                            <td>
                                {{ $application->desired_move_in_date?->format('d M Y') ?? 'Not specified' }}
                            </td>

                            <td>
                                @php
                                    $statusClass = match ($application->status) {
                                        'pending' => 'bg-warning-subtle text-warning-emphasis',
                                        'approved' => 'bg-success-subtle text-success',
                                        'rejected' => 'bg-danger-subtle text-danger',
                                        default => 'bg-secondary-subtle text-secondary',
                                    };
                                @endphp

                                <span class="badge {{ $statusClass }}">
                                    {{ ucfirst($application->status) }}
                                </span>
                            </td>

                            <td>
                                @if($application->lease)
                                    <span class="badge bg-success-subtle text-success">
                                        {{ ucfirst($application->lease->status) }}
                                    </span>
                                @else
                                    <span class="text-muted">Not created</span>
                                @endif
                            </td>

                            <td class="text-end">
                                <a href="{{ route('tenant-portal.applications.show', $application) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-body">
                {{ $applications->links() }}
            </div>
        </div>
    @endif
</div>
@endsection