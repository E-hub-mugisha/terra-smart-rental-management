@extends('layouts.app')

@section('title', 'Tenants')

@section('content')
<div class="container-fluid py-4 pm-tenants">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Tenants</h3>
            <p class="text-muted mb-0">Manage tenant profiles and their linked accounts.</p>
        </div>

        <a href="{{ route('property-manager.tenants.create') }}" class="btn btn-brand">
            <i class="bi bi-person-plus me-1"></i> Add tenant
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('generated_password'))
        <div class="alert alert-warning">
            <h6 class="fw-bold">Temporary tenant login credentials</h6>
            <p class="mb-1">Email: <strong>{{ session('generated_email') }}</strong></p>
            <p class="mb-2">
                Temporary password:
                <code id="tenantGeneratedPassword">{{ session('generated_password') }}</code>
            </p>
            <button type="button" class="btn btn-sm btn-outline-dark"
                onclick="navigator.clipboard.writeText(document.getElementById('tenantGeneratedPassword').textContent)">
                Copy password
            </button>
            <p class="small mb-0 mt-2">
                Share these credentials securely. This password will not be displayed again.
            </p>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            Please review the validation errors in the form.
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('property-manager.tenants.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-7">
                        <label class="form-label">Search tenants</label>
                        <input type="text" name="search" class="form-control"
                            placeholder="Name, tenant number, email or phone"
                            value="{{ request('search') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All statuses</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-brand flex-grow-1" type="submit">Filter</button>
                        <a href="{{ route('property-manager.tenants.index') }}"
                            class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Tenant directory</h6>
            <span class="text-muted small">{{ $tenants->total() }} records</span>
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tenant</th>
                        <th>Contact</th>
                        <th>Tenant number</th>
                        <th>Account</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $tenant)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $tenant->full_name }}</div>
                                <div class="small text-muted">{{ $tenant->email }}</div>
                            </td>
                            <td>
                                <div>{{ $tenant->phone }}</div>
                                <div class="small text-muted">
                                    {{ $tenant->alternative_phone ?: 'No alternative phone' }}
                                </div>
                            </td>
                            <td>
                                <span class="fw-semibold">{{ $tenant->tenant_number }}</span>
                            </td>
                            <td>
                                @if($tenant->user)
                                    <span class="badge text-bg-success">Linked</span>
                                @else
                                    <span class="badge text-bg-secondary">No account</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $tenant->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ ucfirst($tenant->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('property-manager.tenants.show', $tenant) }}"
                                        class="btn btn-sm btn-light" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('property-manager.tenants.edit', $tenant) }}"
                                        class="btn btn-sm btn-light" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST"
                                        action="{{ route('property-manager.tenants.destroy', $tenant) }}"
                                        onsubmit="return confirm('Archive this tenant? Their rental and payment history will be preserved.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Archive tenant">
                                            <i class="bi bi-archive"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-people fs-2 text-muted"></i>
                                <h6 class="mt-2">No tenants found</h6>
                                <p class="text-muted small mb-3">
                                    Add a tenant or adjust your search filters.
                                </p>
                                <a href="{{ route('property-manager.tenants.create') }}"
                                    class="btn btn-brand btn-sm">Add tenant</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tenants->hasPages())
            <div class="card-footer bg-white">
                {{ $tenants->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    .pm-tenants { --terra-orange: #D05208; --terra-navy: #19265d; }
    .pm-tenants .btn-brand {
        background: var(--terra-orange);
        border-color: var(--terra-orange);
        color: #fff;
    }
    .pm-tenants .btn-brand:hover {
        background: #b94707;
        border-color: #b94707;
        color: #fff;
    }
    .pm-tenants .card { border-radius: 14px; }
    .pm-tenants .table th {
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .035em;
        color: #687083;
        white-space: nowrap;
    }
    .pm-tenants .table td { padding: .9rem .8rem; }
</style>
@endsection