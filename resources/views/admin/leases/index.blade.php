@extends('layouts.app')

@section('title', 'Lease Management')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1">Lease Management</h2>
            <p class="text-muted mb-0">
                Manage lease agreements, rental invoices, payments and move-ins.
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Summary --}}
    <div class="row g-3 mb-4">
        @foreach ([
            ['label' => 'All Leases', 'value' => $counts['all'], 'status' => ''],
            ['label' => 'Drafts', 'value' => $counts['draft'], 'status' => 'draft'],
            ['label' => 'Awaiting Acceptance', 'value' => $counts['awaiting_acceptance'], 'status' => 'pending_tenant_acceptance'],
            ['label' => 'Pending Activation', 'value' => $counts['pending_activation'], 'status' => 'pending_activation'],
            ['label' => 'Active', 'value' => $counts['active'], 'status' => 'active'],
        ] as $item)
            <div class="col-6 col-md-4 col-xl">
                <a href="{{ route('admin.leases.index', $item['status'] ? ['status' => $item['status']] : []) }}"
                   class="text-decoration-none text-reset">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted small">{{ $item['label'] }}</div>
                            <div class="fs-3 fw-bold">{{ $item['value'] }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.leases.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Search</label>
                        <input
                            type="text"
                            name="search"
                            id="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Lease number, tenant, email or unit"
                        >
                    </div>

                    <div class="col-md-4">
                        <label for="status" class="form-label">Lease Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All statuses</option>
                            @foreach ([
                                'draft' => 'Draft',
                                'pending_tenant_acceptance' => 'Awaiting Acceptance',
                                'pending_activation' => 'Pending Activation',
                                'active' => 'Active',
                                'terminated' => 'Terminated',
                                'rejected' => 'Rejected',
                                'cancelled' => 'Cancelled',
                                'expired' => 'Expired',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" type="submit">
                            Filter
                        </button>
                        <a href="{{ route('admin.leases.index') }}"
                           class="btn btn-outline-secondary">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Lease table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0">Leases</h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Lease</th>
                        <th>Tenant</th>
                        <th>Unit</th>
                        <th>Monthly Rent</th>
                        <th>Period</th>
                        <th>Invoices</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($leases as $lease)
                        @php
                            $badge = match ($lease->status) {
                                'active' => 'success',
                                'pending_activation' => 'info',
                                'pending_tenant_acceptance' => 'warning',
                                'rejected', 'cancelled' => 'danger',
                                'terminated' => 'dark',
                                default => 'secondary',
                            };
                        @endphp

                        <tr>
                            <td class="fw-semibold">
                                {{ $lease->lease_number }}
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $lease->tenant?->full_name ?? 'N/A' }}
                                </div>
                                <small class="text-muted">
                                    {{ $lease->tenant?->email }}
                                </small>
                            </td>

                            <td>
                                {{ $lease->unit?->building?->name ?? 'Property' }}
                                <div class="small text-muted">
                                    Unit {{ $lease->unit?->unit_number ?? 'N/A' }}
                                </div>
                            </td>

                            <td>
                                {{ $lease->currency }}
                                {{ number_format((float) $lease->monthly_rent, 2) }}
                            </td>

                            <td>
                                {{ $lease->start_date?->format('d M Y') }}
                                <div class="small text-muted">
                                    to {{ $lease->end_date?->format('d M Y') }}
                                </div>
                            </td>

                            <td>{{ $lease->invoices_count }}</td>

                            <td>
                                <span class="badge text-bg-{{ $badge }}">
                                    {{ ucwords(str_replace('_', ' ', $lease->status)) }}
                                </span>
                            </td>

                            <td class="text-end">
                                <a href="{{ route('admin.leases.show', $lease) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    Manage Lease
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                No leases found matching your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($leases->hasPages())
            <div class="card-footer bg-white">
                {{ $leases->links() }}
            </div>
        @endif
    </div>
</div>
@endsection