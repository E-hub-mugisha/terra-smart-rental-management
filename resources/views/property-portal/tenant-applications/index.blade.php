@extends('layouts.app')

@section('title', 'Rental Applications')

@section('content')
<div class="container-fluid py-4 pm-applications">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Rental applications</h3>
            <p class="text-muted mb-0">Review tenant applications for your assigned properties.</p>
        </div>
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
        <strong>Unable to process the application.</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="row g-3 mb-4">
        @php
        $statCards = [
        ['label' => 'All applications', 'value' => $counts['all'], 'icon' => 'bi-inbox', 'class' => 'stat-navy'],
        ['label' => 'Pending', 'value' => $counts['pending'], 'icon' => 'bi-hourglass-split', 'class' => 'stat-orange'],
        ['label' => 'Under review', 'value' => $counts['under_review'], 'icon' => 'bi-search', 'class' => 'stat-blue'],
        ['label' => 'Approved', 'value' => $counts['approved'], 'icon' => 'bi-check-circle', 'class' => 'stat-green'],
        ['label' => 'Rejected', 'value' => $counts['rejected'], 'icon' => 'bi-x-circle', 'class' => 'stat-red'],
        ];
        @endphp

        @foreach($statCards as $stat)
        <div class="col-6 col-md-4 col-xl">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <div class="small text-muted mb-1">{{ $stat['label'] }}</div>
                        <div class="fs-3 fw-bold">{{ $stat['value'] }}</div>
                    </div>
                    <div class="stat-icon {{ $stat['class'] }}">
                        <i class="bi {{ $stat['icon'] }}"></i>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('property-manager.applications.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-7">
                        <label class="form-label">Search applications</label>
                        <input type="search" name="search" class="form-control"
                            placeholder="Application number, tenant, email or unit"
                            value="{{ request('search') }}">
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">Application status</label>
                        <select name="status" class="form-select">
                            <option value="">All statuses</option>
                            @foreach(['pending', 'under_review', 'approved', 'rejected'] as $status)
                            <option value="{{ $status }}" @selected(request('status')===$status)>
                                {{ ucwords(str_replace('_', ' ', $status)) }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 d-flex gap-2">
                        <button class="btn btn-brand flex-grow-1">Filter</button>
                        <a href="{{ route('property-manager.applications.index') }}"
                            class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between gap-2">
            <h6 class="fw-bold mb-0">Applications received</h6>
            <span class="small text-muted">{{ $applications->total() }} results</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Application</th>
                        <th>Applicant</th>
                        <th>Requested unit</th>
                        <th>Property</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $application)
                    @php
                    $statusClasses = [
                    'pending' => 'status-pending',
                    'under_review' => 'status-review',
                    'approved' => 'status-approved',
                    'rejected' => 'status-rejected',
                    ];
                    $unit = $application->unit;
                    $building = $unit?->building;
                    $property = $building?->property;
                    @endphp

                    <tr>
                        <td>
                            <div class="fw-semibold">
                                {{ $application->application_number ?? 'APP-' . $application->id }}
                            </div>
                            <div class="small text-muted">#{{ $application->id }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $application->tenant?->full_name ?? 'Unknown tenant' }}</div>
                            <div class="small text-muted">{{ $application->tenant?->email ?? 'No email' }}</div>
                            <div class="small text-muted">{{ $application->tenant?->phone ?? '' }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $unit?->unit_number ?? '—' }}</div>
                            <div class="small text-muted">{{ $unit?->unit_type ?? 'Unit' }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $property?->name ?? '—' }}</div>
                            <div class="small text-muted">{{ $building?->name ?? 'Building not set' }}</div>
                        </td>
                        <td class="text-nowrap">
                            {{ $application->created_at?->format('d M Y') ?? '—' }}
                        </td>
                        <td>
                            <span class="status-pill {{ $statusClasses[$application->status] ?? 'status-review' }}">
                                {{ ucwords(str_replace('_', ' ', $application->status)) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('property-manager.applications.show', $application) }}"
                                class="btn btn-sm btn-brand">
                                Review <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <h6 class="fw-bold mt-3">No applications found</h6>
                            <p class="text-muted small mb-0">
                                Applications for your assigned properties will appear here.
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($applications->hasPages())
        <div class="card-footer bg-white">
            {{ $applications->links() }}
        </div>
        @endif
    </div>
</div>

<style>
    .pm-applications {
        --terra-orange: #D05208;
        --terra-navy: #19265d;
    }

    .pm-applications .card {
        border-radius: 14px;
    }

    .pm-applications .btn-brand {
        background: var(--terra-orange);
        border-color: var(--terra-orange);
        color: #fff;
    }

    .pm-applications .btn-brand:hover {
        background: #b94707;
        border-color: #b94707;
        color: #fff;
    }

    .pm-applications .stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
    }

    .pm-applications .stat-navy {
        background: #edf0ff;
        color: #19265d;
    }

    .pm-applications .stat-orange {
        background: #fff0e8;
        color: #D05208;
    }

    .pm-applications .stat-blue {
        background: #eaf3ff;
        color: #245eaa;
    }

    .pm-applications .stat-green {
        background: #e6f6ed;
        color: #18794e;
    }

    .pm-applications .stat-red {
        background: #ffebeb;
        color: #b42318;
    }

    .pm-applications .status-pill {
        display: inline-block;
        padding: .35rem .6rem;
        border-radius: 20px;
        font-size: .75rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .pm-applications .status-pending {
        background: #fff2d6;
        color: #805b00;
    }

    .pm-applications .status-review {
        background: #eaf3ff;
        color: #245eaa;
    }

    .pm-applications .status-approved {
        background: #e6f6ed;
        color: #18794e;
    }

    .pm-applications .status-rejected {
        background: #ffebeb;
        color: #b42318;
    }

    .pm-applications .table th {
        font-size: .76rem;
        text-transform: uppercase;
        letter-spacing: .035em;
        color: #697386;
        white-space: nowrap;
    }

    .pm-applications .table td {
        padding: .9rem .8rem;
    }
</style>
@endsection