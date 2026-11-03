@extends('layouts.app')

@section('title', 'Tenant Applications')

@section('content')
<div class="container-fluid py-4">

    <div class="mb-4">
        <h3 class="mb-1">Tenant Applications</h3>
        <p class="text-muted mb-0">
            Review rental applications and manage approval decisions.
        </p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="row g-3 mb-4">
        @foreach([
            'all' => ['All Applications', 'primary'],
            'pending' => ['Pending Review', 'warning'],
            'approved' => ['Approved', 'success'],
            'rejected' => ['Rejected', 'danger'],
        ] as $key => $item)
            <div class="col-6 col-xl-3">
                <a href="{{ route('admin.tenant-applications.index', $key === 'all' ? [] : ['status' => $key]) }}"
                   class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted small">{{ $item[0] }}</div>
                            <div class="fs-3 fw-semibold text-dark">
                                {{ $counts[$key] }}
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            <form method="GET"
                  action="{{ route('admin.tenant-applications.index') }}"
                  class="row g-2 mb-4">

                <div class="col-lg-6">
                    <input type="search"
                           name="search"
                           class="form-control"
                           value="{{ request('search') }}"
                           placeholder="Search application number, tenant or unit">
                </div>

                <div class="col-lg-3">
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach(['pending', 'approved', 'rejected', 'withdrawn'] as $status)
                            <option value="{{ $status }}"
                                @selected(request('status') === $status)>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-3 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>

                    <a href="{{ route('admin.tenant-applications.index') }}"
                       class="btn btn-light border">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Application</th>
                            <th>Applicant</th>
                            <th>Unit</th>
                            <th>Offered Rent</th>
                            <th>Move-in Date</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($applications as $application)
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
                                    {{ $application->tenant?->full_name ?? 'Tenant unavailable' }}
                                </div>
                                <small class="text-muted">
                                    {{ $application->tenant?->email ?? 'No email' }}
                                </small>
                            </td>

                            <td>
                                {{ $application->unit?->unit_number ?? 'Unit unavailable' }}
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
                                    $badge = match ($application->status) {
                                        'pending' => 'bg-warning-subtle text-warning-emphasis',
                                        'approved' => 'bg-success-subtle text-success',
                                        'rejected' => 'bg-danger-subtle text-danger',
                                        default => 'bg-secondary-subtle text-secondary',
                                    };
                                @endphp

                                <span class="badge {{ $badge }}">
                                    {{ ucfirst($application->status) }}
                                </span>
                            </td>

                            <td class="text-end">
                                <a href="{{ route('admin.tenant-applications.show', $application) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    Review <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-file-earmark-text fs-2 text-muted"></i>
                                <p class="mt-2 mb-0">No tenant applications found.</p>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{ $applications->links() }}
        </div>
    </div>
</div>
@endsection