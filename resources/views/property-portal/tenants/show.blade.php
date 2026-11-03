@extends('layouts.app')

@section('title', $tenant->full_name)

@section('content')
<div class="container-fluid py-4 pm-tenant-show">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <a href="{{ route('property-manager.tenants.index') }}"
                class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i> Back to tenants
            </a>

            <div class="d-flex align-items-center gap-3 mt-3">
                <div class="tenant-avatar">
                    {{ strtoupper(substr($tenant->full_name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="fw-bold mb-1">{{ $tenant->full_name }}</h3>
                    <div class="text-muted small">
                        {{ $tenant->tenant_number }}
                        <span class="mx-1">·</span>
                        {{ $tenant->email }}
                    </div>
                    <span class="badge mt-2 {{ $tenant->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                        {{ ucfirst($tenant->status) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('property-manager.tenants.edit', $tenant) }}"
                class="btn btn-brand">
                <i class="bi bi-pencil me-1"></i> Edit tenant
            </a>

            <form method="POST"
                action="{{ route('property-manager.tenants.destroy', $tenant) }}"
                onsubmit="return confirm('Archive this tenant? Rental and payment history will be preserved.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-archive me-1"></i> Archive
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('generated_password'))
    <div class="alert alert-warning">
        <h6 class="fw-bold">Tenant account created</h6>
        <p class="mb-1">Login email: <strong>{{ session('generated_email') }}</strong></p>
        <p class="mb-2">
            Temporary password:
            <code id="tenantGeneratedPassword">{{ session('generated_password') }}</code>
        </p>
        <button type="button" class="btn btn-sm btn-outline-dark"
            onclick="navigator.clipboard.writeText(document.getElementById('tenantGeneratedPassword').textContent)">
            Copy password
        </button>
        <p class="small mt-2 mb-0">
            Share these credentials securely. The password is displayed only on this response.
        </p>
    </div>
    @endif

    @if(session('generated_password'))
    <div class="alert alert-warning border-0 shadow-sm">
        <h6 class="fw-bold mb-2">
            Temporary Tenant Login Credentials
        </h6>

        <p class="mb-1">
            <strong>Email:</strong>
            {{ session('generated_email') }}
        </p>

        <p class="mb-3">
            <strong>Password:</strong>
            <code>{{ session('generated_password') }}</code>
        </p>

        <div class="small text-muted">
            Share these credentials securely with the tenant.
            This password will only be available during this redirect.
        </div>
    </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted mb-2">Leases</div>
                    <div class="fs-3 fw-bold">{{ $tenant->leases->count() }}</div>
                    <div class="small text-muted">Rental agreements</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted mb-2">Applications</div>
                    <div class="fs-3 fw-bold">{{ $tenant->applications->count() }}</div>
                    <div class="small text-muted">Rental applications</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted mb-2">Invoices</div>
                    <div class="fs-3 fw-bold">{{ $tenant->invoices->count() }}</div>
                    <div class="small text-muted">Rent invoices</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted mb-2">Payments</div>
                    <div class="fs-3 fw-bold">{{ $tenant->payments->count() }}</div>
                    <div class="small text-muted">Recorded payments</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Contact information</h6>
                </div>
                <div class="card-body">
                    <div class="detail-row">
                        <span>Email</span>
                        <strong>{{ $tenant->email ?: 'Not provided' }}</strong>
                    </div>
                    <div class="detail-row">
                        <span>Phone</span>
                        <strong>{{ $tenant->phone ?: 'Not provided' }}</strong>
                    </div>
                    <div class="detail-row">
                        <span>Alternative phone</span>
                        <strong>{{ $tenant->alternative_phone ?: 'Not provided' }}</strong>
                    </div>
                    <div class="detail-row">
                        <span>Address</span>
                        <strong>{{ $tenant->address ?: 'Not provided' }}</strong>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Emergency contact</h6>
                </div>
                <div class="card-body">
                    <div class="detail-row">
                        <span>Name</span>
                        <strong>{{ $tenant->emergency_contact_name ?: 'Not provided' }}</strong>
                    </div>
                    <div class="detail-row">
                        <span>Phone</span>
                        <strong>{{ $tenant->emergency_contact_phone ?: 'Not provided' }}</strong>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Login account</h6>
                </div>
                <div class="card-body">
                    @if($tenant->user)
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">{{ $tenant->user->name }}</div>
                            <div class="small text-muted">{{ $tenant->user->email }}</div>
                        </div>
                        <span class="badge text-bg-success">Linked</span>
                    </div>
                    @else
                    <p class="text-muted mb-0">
                        This tenant does not currently have a linked login account.
                    </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Lease history</h6>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Unit</th>
                                <th>Property</th>
                                <th>Period</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tenant->leases as $lease)
                            <tr>
                                <td>
                                    {{ $lease->unit?->unit_number ?? '—' }}
                                </td>
                                <td>
                                    {{ $lease->unit?->building?->property?->name ?? '—' }}
                                </td>
                                <td class="small">
                                    {{ $lease->start_date ?? '—' }}
                                    <span class="text-muted">to</span>
                                    {{ $lease->end_date ?? '—' }}
                                </td>
                                <td>
                                    <span class="badge text-bg-light border">
                                        {{ ucfirst($lease->status ?? 'unknown') }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No leases found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Rental applications</h6>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Application</th>
                                <th>Unit</th>
                                <th>Property</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tenant->applications as $application)
                            <tr>
                                <td>{{ $application->application_number ?? '—' }}</td>
                                <td>{{ $application->unit?->unit_number ?? '—' }}</td>
                                <td>{{ $application->unit?->building?->property?->name ?? '—' }}</td>
                                <td>
                                    <span class="badge text-bg-light border">
                                        {{ ucfirst($application->status ?? 'unknown') }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No applications found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Internal notes</h6>
                </div>
                <div class="card-body">
                    {!! $tenant->notes
                    ? nl2br(e($tenant->notes))
                    : '<span class="text-muted">No notes have been added.</span>' !!}
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .pm-tenant-show .tenant-avatar {
        width: 58px;
        height: 58px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        background: #fff0e8;
        color: #D05208;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .pm-tenant-show .btn-brand {
        background: #D05208;
        border-color: #D05208;
        color: #fff;
    }

    .pm-tenant-show .btn-brand:hover {
        background: #b94707;
        border-color: #b94707;
        color: #fff;
    }

    .pm-tenant-show .card {
        border-radius: 14px;
    }

    .pm-tenant-show .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        padding: .8rem 0;
        border-bottom: 1px solid #edf0f4;
    }

    .pm-tenant-show .detail-row:last-child {
        border-bottom: 0;
    }

    .pm-tenant-show .detail-row>span {
        color: #737b8c;
        font-size: .875rem;
        flex-shrink: 0;
    }

    .pm-tenant-show .detail-row>strong {
        text-align: right;
        overflow-wrap: anywhere;
        font-size: .9rem;
    }
</style>
@endsection