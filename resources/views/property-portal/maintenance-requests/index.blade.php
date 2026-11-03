@extends('layouts.app')

@section('title', 'Maintenance Requests')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Maintenance Management</h3>
            <p class="text-muted mb-0">
                Monitor, schedule and manage property maintenance requests.
            </p>
        </div>

        <a href="{{ route('property-manager.maintenance-requests.create') }}"
           class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Request
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted small mb-1">All Requests</p>
                            <h3 class="fw-bold mb-0">{{ number_format($summary['all']) }}</h3>
                        </div>
                        <span class="rounded-3 bg-primary-subtle text-primary p-3">
                            <i class="bi bi-tools fs-5"></i>
                        </span>
                    </div>
                    <small class="text-muted">Total maintenance records</small>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted small mb-1">Open Requests</p>
                            <h3 class="fw-bold mb-0">{{ number_format($summary['open']) }}</h3>
                        </div>
                        <span class="rounded-3 bg-warning-subtle text-warning p-3">
                            <i class="bi bi-clock-history fs-5"></i>
                        </span>
                    </div>
                    <small class="text-muted">Awaiting completion</small>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted small mb-1">Urgent Requests</p>
                            <h3 class="fw-bold mb-0">{{ number_format($summary['urgent']) }}</h3>
                        </div>
                        <span class="rounded-3 bg-danger-subtle text-danger p-3">
                            <i class="bi bi-exclamation-triangle fs-5"></i>
                        </span>
                    </div>
                    <small class="text-muted">Require urgent attention</small>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted small mb-1">Resolved</p>
                            <h3 class="fw-bold mb-0">{{ number_format($summary['resolved']) }}</h3>
                        </div>
                        <span class="rounded-3 bg-success-subtle text-success p-3">
                            <i class="bi bi-check-circle fs-5"></i>
                        </span>
                    </div>
                    <small class="text-muted">Resolved or closed requests</small>
                </div>
            </div>
        </div>

    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('property-manager.maintenance-requests.index') }}">
                <div class="row g-3 align-items-end">

                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold">Search</label>
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Request number, tenant, unit..."
                               value="{{ request('search') }}">
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All categories</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}"
                                    @selected(request('category') === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="">All priorities</option>
                            @foreach($priorities as $key => $label)
                                <option value="{{ $key }}"
                                    @selected(request('priority') === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All statuses</option>
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}"
                                    @selected(request('status') === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-search me-1"></i> Filter
                        </button>

                        <a href="{{ route('property-manager.maintenance-requests.index') }}"
                           class="btn btn-light border"
                           title="Clear filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold">Date From</label>
                        <input type="date"
                               name="date_from"
                               class="form-control"
                               value="{{ request('date_from') }}">
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold">Date To</label>
                        <input type="date"
                               name="date_to"
                               class="form-control"
                               value="{{ request('date_to') }}">
                    </div>

                </div>
            </form>
        </div>
    </div>

    {{-- Requests table --}}
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Maintenance Requests</h6>
                <span class="text-muted small">
                    {{ $requests->total() }} records
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Request</th>
                        <th>Tenant</th>
                        <th>Property / Unit</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($requests as $maintenance)
                        <tr>
                            <td class="ps-3">
                                <a href="{{ route('property-manager.maintenance-requests.show', $maintenance->id) }}"
                                   class="fw-semibold text-decoration-none">
                                    {{ $maintenance->request_number }}
                                </a>
                                <div class="small text-muted">
                                    {{ $maintenance->title }}
                                </div>
                            </td>

                            <td>
                                <div class="fw-medium">
                                    {{ $maintenance->tenant?->full_name ?? '—' }}
                                </div>
                                <small class="text-muted">
                                    {{ $maintenance->tenant?->phone ?? '' }}
                                </small>
                            </td>

                            <td>
                                <div class="fw-medium">
                                    {{ $maintenance->unit?->building?->property?->name ?? '—' }}
                                </div>
                                <small class="text-muted">
                                    Unit {{ $maintenance->unit?->unit_number ?? '—' }}
                                </small>
                            </td>

                            <td>
                                {{ $categories[$maintenance->category] ?? ucfirst($maintenance->category) }}
                            </td>

                            <td>
                                @php
                                    $priorityClass = [
                                        'low' => 'secondary',
                                        'medium' => 'info',
                                        'high' => 'warning',
                                        'urgent' => 'danger',
                                    ][$maintenance->priority] ?? 'secondary';
                                @endphp

                                <span class="badge bg-{{ $priorityClass }}-subtle text-{{ $priorityClass }}">
                                    {{ $priorities[$maintenance->priority] ?? ucfirst($maintenance->priority) }}
                                </span>
                            </td>

                            <td class="text-nowrap">
                                {{ $maintenance->created_at?->format('d M Y') }}
                            </td>

                            <td>
                                @php
                                    $statusClass = [
                                        'open' => 'primary',
                                        'in_progress' => 'info',
                                        'scheduled' => 'warning',
                                        'on_hold' => 'secondary',
                                        'resolved' => 'success',
                                        'closed' => 'dark',
                                        'rejected' => 'danger',
                                    ][$maintenance->status] ?? 'secondary';
                                @endphp

                                <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }}">
                                    {{ $statuses[$maintenance->status] ?? ucfirst(str_replace('_', ' ', $maintenance->status)) }}
                                </span>
                            </td>

                            <td class="text-end pe-3">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border"
                                            type="button"
                                            data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item"
                                               href="{{ route('property-manager.maintenance-requests.show', $maintenance->id) }}">
                                                <i class="bi bi-eye me-2"></i> View
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item"
                                               href="{{ route('property-manager.maintenance-requests.edit', $maintenance->id) }}">
                                                <i class="bi bi-pencil me-2"></i> Edit
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST"
                                                  action="{{ route('property-manager.maintenance-requests.destroy', $maintenance->id) }}"
                                                  onsubmit="return confirm('Delete this maintenance request?')">
                                                @csrf
                                                @method('DELETE')

                                                <button class="dropdown-item text-danger" type="submit">
                                                    <i class="bi bi-trash me-2"></i> Delete
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-tools fs-1 text-muted"></i>
                                <h6 class="fw-semibold mt-3">No maintenance requests found</h6>
                                <p class="text-muted small mb-3">
                                    Maintenance requests will appear here when recorded.
                                </p>
                                <a href="{{ route('property-manager.maintenance-requests.create') }}"
                                   class="btn btn-primary btn-sm">
                                    Create Request
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $requests->links() }}
            </div>
        @endif

    </div>
</div>
@endsection