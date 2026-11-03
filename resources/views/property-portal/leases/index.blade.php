
@extends('layouts.app')

@section('title', 'Lease Management')

@section('content')
<div class="container-fluid py-4 pm-leases">
    <div class="mb-4">
        <h3 class="fw-bold mb-1">Lease management</h3>
        <p class="text-muted mb-0">
            Manage lease agreements, tenant acceptance, rent and move-in status.
        </p>
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

    <div class="row g-3 mb-4">
        @php
            $stats = [
                ['All leases', $counts['all'], 'bi-files', 'navy'],
                ['Draft', $counts['draft'], 'bi-file-earmark', 'orange'],
                ['Active', $counts['active'], 'bi-check-circle', 'green'],
                ['Expired', $counts['expired'], 'bi-calendar-x', 'blue'],
                ['Terminated', $counts['terminated'], 'bi-x-circle', 'red'],
                ['Cancelled', $counts['cancelled'], 'bi-slash-circle', 'gray'],
            ];
        @endphp

        @foreach($stats as [$label, $value, $icon, $color])
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center gap-2">
                        <div>
                            <div class="small text-muted mb-1">{{ $label }}</div>
                            <div class="fs-3 fw-bold">{{ $value }}</div>
                        </div>
                        <div class="lease-stat-icon stat-{{ $color }}">
                            <i class="bi {{ $icon }}"></i>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('property-manager.leases.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-7">
                        <label for="search" class="form-label">Search leases</label>
                        <input
                            type="search"
                            id="search"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Lease number, tenant, email, phone or unit"
                        >
                    </div>

                    <div class="col-lg-3">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">All statuses</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>
                                    {{ ucwords(str_replace('_', ' ', $status)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 d-flex gap-2">
                        <button type="submit" class="btn btn-brand flex-grow-1">
                            Filter
                        </button>
                        <a href="{{ route('property-manager.leases.index') }}"
                           class="btn btn-outline-secondary">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="fw-bold mb-0">Lease register</h6>
            <span class="small text-muted">{{ $leases->total() }} records</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Lease</th>
                        <th>Tenant</th>
                        <th>Property / Unit</th>
                        <th>Lease period</th>
                        <th>Monthly rent</th>
                        <th>Acceptance</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leases as $lease)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $lease->lease_number }}</div>
                                <div class="small text-muted">
                                    Created {{ $lease->created_at?->format('d M Y') ?? '—' }}
                                </div>
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $lease->tenant?->full_name ?? 'Tenant unavailable' }}
                                </div>
                                <div class="small text-muted">
                                    {{ $lease->tenant?->email ?? '—' }}
                                </div>
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $lease->unit?->unit_number ?? '—' }}
                                </div>
                                <div class="small text-muted">
                                    {{ $lease->unit?->building?->property?->name ?? 'Property unavailable' }}
                                </div>
                            </td>

                            <td class="text-nowrap small">
                                {{ $lease->start_date?->format('d M Y') ?? '—' }}
                                <div class="text-muted">
                                    to {{ $lease->end_date?->format('d M Y') ?? '—' }}
                                </div>
                            </td>

                            <td class="text-nowrap fw-semibold">
                                {{ $lease->currency }}
                                {{ number_format((float) $lease->monthly_rent, 0) }}
                            </td>

                            <td>
                                @if($lease->tenant_accepted_at)
                                    <span class="acceptance-badge accepted">Accepted</span>
                                    <div class="small text-muted mt-1">
                                        {{ $lease->tenant_accepted_at->format('d M Y') }}
                                    </div>
                                @else
                                    <span class="acceptance-badge waiting">Awaiting tenant</span>
                                @endif
                            </td>

                            <td>
                                <span class="lease-status status-{{ $lease->status }}">
                                    {{ ucwords(str_replace('_', ' ', $lease->status)) }}
                                </span>
                            </td>

                            <td class="text-end">
                                <a href="{{ route('property-manager.leases.show', $lease) }}"
                                   class="btn btn-sm btn-brand">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-files fs-1 text-muted"></i>
                                <h6 class="fw-bold mt-3">No leases found</h6>
                                <p class="text-muted small mb-0">
                                    Leases created from approved rental applications will appear here.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($leases->hasPages())
            <div class="card-footer bg-white">
                {{ $leases->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    .pm-leases { --terra-orange: #D05208; }
    .pm-leases .card { border-radius: 14px; }
    .pm-leases .btn-brand {
        background: var(--terra-orange);
        border-color: var(--terra-orange);
        color: #fff;
    }
    .pm-leases .btn-brand:hover {
        background: #b94707;
        border-color: #b94707;
        color: #fff;
    }
    .pm-leases .lease-stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .pm-leases .stat-navy { background: #edf0ff; color: #19265d; }
    .pm-leases .stat-orange { background: #fff0e8; color: #D05208; }
    .pm-leases .stat-blue { background: #eaf3ff; color: #245eaa; }
    .pm-leases .stat-green { background: #e6f6ed; color: #18794e; }
    .pm-leases .stat-gray { background: #f0f1f3; color: #525866; }
    .pm-leases .stat-red { background: #ffebeb; color: #b42318; }
    .pm-leases .lease-status,
    .pm-leases .acceptance-badge {
        display: inline-block;
        padding: .35rem .6rem;
        border-radius: 20px;
        font-size: .75rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .pm-leases .status-draft { background: #f0f1f3; color: #525866; }
    .pm-leases .status-active { background: #e6f6ed; color: #18794e; }
    .pm-leases .status-expired { background: #eaf3ff; color: #245eaa; }
    .pm-leases .status-terminated,
    .pm-leases .status-cancelled { background: #ffebeb; color: #b42318; }
    .pm-leases .accepted { background: #e6f6ed; color: #18794e; }
    .pm-leases .waiting { background: #fff2d6; color: #805b00; }
    .pm-leases .table th {
        font-size: .76rem;
        text-transform: uppercase;
        letter-spacing: .035em;
        color: #697386;
        white-space: nowrap;
    }
    .pm-leases .table td { padding: .9rem .8rem; }
</style>
@endsection
