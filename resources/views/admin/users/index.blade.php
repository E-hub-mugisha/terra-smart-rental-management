
@extends('layouts.app')

@section('title', 'User Management | Terra Smart Rental Management')

@section('content')
@php
    $roles = [
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'tenant' => 'Tenant',
    ];

    $roleColors = [
        'admin' => 'role-admin',
        'manager' => 'role-manager',
        'tenant' => 'role-tenant',
    ];

    $totalUsers = $users->total();
    $canManageUsers = auth()->user()?->isAdmin() ?? false;
@endphp

<style>
    .terra-users {
        --terra-orange: #D05208;
        --terra-navy: #19265d;
        --terra-border: #e7ebf2;
        --terra-muted: #718096;
        color: #273248;
        font-size: .9rem;
    }

    .terra-users .page-title {
        color: var(--terra-navy);
        font-weight: 800;
        letter-spacing: -.6px;
    }

    .terra-users .muted {
        color: var(--terra-muted);
    }

    .terra-users .btn-terra {
        background: var(--terra-orange);
        color: #fff;
        border-color: var(--terra-orange);
        font-weight: 650;
    }

    .terra-users .btn-terra:hover {
        background: #b74706;
        color: #fff;
        border-color: #b74706;
    }

    .terra-users .btn-terra-outline {
        color: var(--terra-navy);
        background: #fff;
        border: 1px solid #dbe1eb;
        font-weight: 600;
    }

    .terra-users .btn-terra-outline:hover {
        background: #f3f5fa;
    }

    .terra-users .surface {
        background: #fff;
        border: 1px solid var(--terra-border);
        border-radius: 16px;
        box-shadow: 0 5px 22px rgba(25, 38, 93, .04);
    }

    .terra-users .stat-card {
        display: flex;
        align-items: center;
        gap: 14px;
        height: 100%;
        padding: 18px;
        background: #fff;
        border: 1px solid var(--terra-border);
        border-radius: 14px;
    }

    .terra-users .stat-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        flex-shrink: 0;
        border-radius: 13px;
        font-size: 1.2rem;
    }

    .terra-users .stat-orange {
        background: #fff0e7;
        color: var(--terra-orange);
    }

    .terra-users .stat-navy {
        background: #edf0fb;
        color: var(--terra-navy);
    }

    .terra-users .stat-green {
        background: #e8f7ef;
        color: #18794e;
    }

    .terra-users .stat-number {
        color: var(--terra-navy);
        font-size: 1.45rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .terra-users .stat-label {
        color: var(--terra-muted);
        font-size: .78rem;
        margin-top: 4px;
    }

    .terra-users .filter-label {
        display: block;
        color: #536078;
        font-size: .76rem;
        font-weight: 700;
        margin-bottom: 7px;
    }

    .terra-users .form-control,
    .terra-users .form-select {
        border-color: #dfe5ee;
        border-radius: 9px;
        min-height: 42px;
        font-size: .88rem;
    }

    .terra-users .form-control:focus,
    .terra-users .form-select:focus {
        border-color: var(--terra-orange);
        box-shadow: 0 0 0 .2rem rgba(208, 82, 8, .1);
    }

    .terra-users .table {
        --bs-table-bg: transparent;
        margin: 0;
    }

    .terra-users .table thead th {
        padding: 14px 17px;
        background: #f8f9fc;
        color: #748096;
        border-bottom: 1px solid var(--terra-border);
        font-size: .71rem;
        text-transform: uppercase;
        letter-spacing: .45px;
        white-space: nowrap;
    }

    .terra-users .table tbody td {
        padding: 15px 17px;
        border-color: #edf0f5;
        vertical-align: middle;
    }

    .terra-users .table tbody tr:last-child td {
        border-bottom: 0;
    }

    .terra-users .user-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 43px;
        height: 43px;
        flex-shrink: 0;
        border-radius: 13px;
        background: #fff0e7;
        color: var(--terra-orange);
        font-weight: 800;
        font-size: .9rem;
    }

    .terra-users .user-name {
        color: var(--terra-navy);
        font-weight: 750;
        overflow-wrap: anywhere;
    }

    .terra-users .user-email {
        color: var(--terra-muted);
        font-size: .78rem;
        overflow-wrap: anywhere;
        margin-top: 3px;
    }

    .terra-users .role-badge {
        display: inline-flex;
        padding: 6px 10px;
        align-items: center;
        gap: 6px;
        border-radius: 20px;
        font-size: .73rem;
        font-weight: 750;
        white-space: nowrap;
    }

    .terra-users .role-admin {
        color: #a13d08;
        background: #fff0e7;
    }

    .terra-users .role-manager {
        color: #244f98;
        background: #eaf1ff;
    }

    .terra-users .role-tenant {
        color: #18794e;
        background: #e8f7ef;
    }

    .terra-users .verification {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: .78rem;
        font-weight: 650;
    }

    .terra-users .verified {
        color: #168052;
    }

    .terra-users .unverified {
        color: #9a6700;
    }

    .terra-users .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        padding: 0;
        border: 1px solid #e2e7ef;
        border-radius: 9px;
        background: #fff;
        color: #526078;
    }

    .terra-users .action-btn:hover {
        background: #f5f7fb;
        color: var(--terra-navy);
    }

    .terra-users .action-btn.action-danger:hover {
        background: #fff0f0;
        color: #b42318;
        border-color: #f4caca;
    }

    .terra-users .empty-state {
        text-align: center;
        padding: 54px 20px;
    }

    .terra-users .empty-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        border-radius: 18px;
        background: #f0f2f8;
        color: #8792a7;
        font-size: 1.6rem;
        margin-bottom: 14px;
    }

    .terra-users .modal-content {
        border: 0;
        border-radius: 17px;
        overflow: hidden;
    }

    .terra-users .modal-header {
        padding: 20px 22px;
        border-bottom: 1px solid var(--terra-border);
    }

    .terra-users .modal-title {
        color: var(--terra-navy);
        font-weight: 800;
    }

    .terra-users .modal-body {
        padding: 22px;
    }

    .terra-users .modal-footer {
        padding: 15px 22px;
        border-top: 1px solid var(--terra-border);
    }

    .terra-users .modal-heading-icon {
        display: inline-flex;
        width: 40px;
        height: 40px;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: #fff0e7;
        color: var(--terra-orange);
        font-size: 1.15rem;
    }

    .terra-users .current-account {
        background: #f2f4fa;
        color: #667085;
        font-size: .75rem;
        padding: 5px 8px;
        border-radius: 7px;
        white-space: nowrap;
    }

    @media (max-width: 767.98px) {
        .terra-users .stat-card {
            padding: 14px;
        }

        .terra-users .table thead th,
        .terra-users .table tbody td {
            padding: 12px;
        }

        .terra-users .modal-body {
            padding: 18px;
        }
    }
</style>

<div class="container-fluid py-4 terra-users">

    {{-- Heading --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="small muted mb-1">
                <i class="bi bi-house-door me-1"></i>
                Terra Smart Rental Management
                <span class="mx-1">/</span>
                Administration
            </div>

            <h2 class="page-title mb-1">User Management</h2>
            <p class="muted mb-0">
                Manage user accounts, application roles and account verification.
            </p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge rounded-pill bg-white text-dark border px-3 py-2">
                {{ number_format($totalUsers) }} users
            </span>

            @if($canManageUsers)
                <button type="button"
                        class="btn btn-terra"
                        data-bs-toggle="modal"
                        data-bs-target="#createUserModal">
                    <i class="bi bi-person-plus me-1"></i>
                    Create User
                </button>
            @endif
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm"
             role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"
             role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm" role="alert">
            <div class="fw-bold mb-1">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Please correct the following:
            </div>
            <ul class="mb-0 ps-4">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon stat-orange">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <div class="stat-number">{{ number_format($totalUsers) }}</div>
                    <div class="stat-label">Total users matching filters</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon stat-navy">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <div>
                    <div class="stat-number">
                        {{ number_format((clone $users)->getCollection()->where('role', 'admin')->count()) }}
                    </div>
                    <div class="stat-label">Administrators on this page</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon stat-green">
                    <i class="bi bi-patch-check"></i>
                </div>
                <div>
                    <div class="stat-number">
                        {{ number_format((clone $users)->getCollection()->filter(fn($user) => $user->email_verified_at)->count()) }}
                    </div>
                    <div class="stat-label">Verified on this page</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon stat-navy">
                    <i class="bi bi-person-check"></i>
                </div>
                <div>
                    <div class="stat-number">{{ $users->count() }}</div>
                    <div class="stat-label">Displayed on this page</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search and filters --}}
    <div class="surface p-3 p-lg-4 mb-4">
        <form method="GET" action="{{ route('admin.users.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-lg-6">
                    <label class="filter-label" for="userSearch">Search users</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"
                              style="border-color:#dfe5ee;">
                            <i class="bi bi-search muted"></i>
                        </span>
                        <input id="userSearch"
                               type="search"
                               name="search"
                               value="{{ $filters['search'] ?? '' }}"
                               class="form-control"
                               placeholder="Search by name or email">
                    </div>
                </div>

                <div class="col-lg-3">
                    <label class="filter-label" for="userRoleFilter">Application role</label>
                    <select id="userRoleFilter" name="role" class="form-select">
                        <option value="">All roles</option>
                        @foreach($roles as $value => $label)
                            <option value="{{ $value }}"
                                @selected(($filters['role'] ?? '') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-terra flex-fill">
                        <i class="bi bi-funnel me-1"></i> Apply
                    </button>

                    <a href="{{ route('admin.users.index') }}"
                       class="btn btn-terra-outline">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- User table --}}
    <div class="surface overflow-hidden">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 p-lg-4 border-bottom">
            <div>
                <h5 class="mb-1 fw-bold" style="color:#19265d;">Application Users</h5>
                <div class="small muted">
                    Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }}
                    of {{ number_format($totalUsers) }} users
                </div>
            </div>
            <span class="small muted">
                <i class="bi bi-arrow-down-up me-1"></i> Most recently registered first
            </span>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Verification</th>
                        <th>Registered</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($users as $user)
                        @php
                            $userInitials = collect(explode(' ', trim($user->name)))
                                ->filter()
                                ->take(2)
                                ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                ->implode('');

                            $userRole = $user->role ?: 'tenant';
                            $isCurrentUser = auth()->id() === $user->id;
                        @endphp

                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="user-avatar">
                                        {{ $userInitials ?: 'U' }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="user-name">{{ $user->name }}</div>
                                        <div class="user-email">{{ $user->email }}</div>
                                        @if($isCurrentUser)
                                            <span class="current-account d-inline-block mt-1">
                                                Your account
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="role-badge {{ $roleColors[$userRole] ?? 'role-tenant' }}">
                                    <i class="bi bi-person-badge"></i>
                                    {{ $roles[$userRole] ?? ucfirst(str_replace('_', ' ', $userRole)) }}
                                </span>
                            </td>

                            <td>
                                @if($user->email_verified_at)
                                    <span class="verification verified">
                                        <i class="bi bi-check-circle-fill"></i> Verified
                                    </span>
                                @else
                                    <span class="verification unverified">
                                        <i class="bi bi-clock-history"></i> Unverified
                                    </span>
                                @endif
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $user->created_at?->format('d M Y') ?? '—' }}
                                </div>
                                <div class="small muted">
                                    {{ $user->created_at?->format('H:i') ?? '' }}
                                </div>
                            </td>

                            <td class="text-end">
                                <div class="d-inline-flex align-items-center gap-1">
                                    @if($canManageUsers)
                                        <button type="button"
                                                class="action-btn"
                                                title="Edit user"
                                                aria-label="Edit {{ $user->name }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editUserModal"
                                                data-user-id="{{ $user->id }}"
                                                data-user-name="{{ $user->name }}"
                                                data-user-email="{{ $user->email }}"
                                                data-user-role="{{ $userRole }}"
                                                data-user-verified="{{ $user->email_verified_at ? '1' : '0' }}"
                                                data-update-url="{{ route('admin.users.update', $user) }}">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        @if(!$isCurrentUser)
                                            <button type="button"
                                                    class="action-btn"
                                                    title="Change role"
                                                    aria-label="Change role for {{ $user->name }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#changeRoleModal"
                                                    data-user-name="{{ $user->name }}"
                                                    data-user-role="{{ $userRole }}"
                                                    data-role-url="{{ route('admin.users.role.update', $user) }}">
                                                <i class="bi bi-shield-check"></i>
                                            </button>

                                            <button type="button"
                                                    class="action-btn action-danger"
                                                    title="Delete user"
                                                    aria-label="Delete {{ $user->name }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteUserModal"
                                                    data-user-name="{{ $user->name }}"
                                                    data-delete-url="{{ route('admin.users.destroy', $user) }}">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        @endif
                                    @else
                                        <span class="small muted">View only</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <h5 class="fw-bold" style="color:#19265d;">
                                        No users found
                                    </h5>
                                    <p class="muted mb-3">
                                        No accounts match the current search or role filters.
                                    </p>
                                    <a href="{{ route('admin.users.index') }}"
                                       class="btn btn-terra-outline btn-sm">
                                        Clear filters
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 p-lg-4 border-top">
                <div class="small muted">
                    Page {{ $users->currentPage() }} of {{ $users->lastPage() }}
                </div>
                {{ $users->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

{{-- CREATE USER MODAL --}}
@if($canManageUsers)
<div class="modal fade terra-users" id="createUserModal" tabindex="-1"
     aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admin.users.store') }}"
              class="modal-content" id="createUserForm">
            @csrf

            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-heading-icon">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <div>
                        <h5 class="modal-title" id="createUserModalLabel">Create User</h5>
                        <div class="small muted">Register a new platform account</div>
                    </div>
                </div>
                <button type="button" class="btn-close"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="filter-label" for="createName">Full name *</label>
                    <input id="createName" name="name" type="text"
                           class="form-control" maxlength="255"
                           value="{{ old('name') }}" required>
                    @error('name')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="filter-label" for="createEmail">Email address *</label>
                    <input id="createEmail" name="email" type="email"
                           class="form-control" maxlength="255"
                           value="{{ old('email') }}" required autocomplete="email">
                    @error('email')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="filter-label" for="createPassword">Password *</label>
                    <input id="createPassword" name="password" type="password"
                           class="form-control" minlength="8"
                           required autocomplete="new-password">
                    <div class="form-text">Use at least 8 characters.</div>
                    @error('password')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="filter-label" for="createPasswordConfirmation">
                        Confirm password *
                    </label>
                    <input id="createPasswordConfirmation"
                           name="password_confirmation" type="password"
                           class="form-control" minlength="8"
                           required autocomplete="new-password">
                </div>

                <div class="mb-3">
                    <label class="filter-label" for="createRole">Application role *</label>
                    <select id="createRole" name="role" class="form-select" required>
                        @foreach($roles as $value => $label)
                            <option value="{{ $value }}"
                                @selected(old('role', 'tenant') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           name="email_verified" value="1" id="createVerified"
                           @checked(old('email_verified'))>
                    <label class="form-check-label" for="createVerified">
                        Mark email as verified
                    </label>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-terra-outline"
                        data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-terra">
                    <i class="bi bi-check2-circle me-1"></i> Create User
                </button>
            </div>
        </form>
    </div>
</div>

{{-- EDIT USER MODAL --}}
<div class="modal fade terra-users" id="editUserModal" tabindex="-1"
     aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="#" class="modal-content" id="editUserForm">
            @csrf
            @method('PUT')

            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-heading-icon">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div>
                        <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                        <div class="small muted">Update account information</div>
                    </div>
                </div>
                <button type="button" class="btn-close"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="filter-label" for="editName">Full name *</label>
                    <input id="editName" name="name" type="text"
                           class="form-control" maxlength="255" required>
                    @error('name')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="filter-label" for="editEmail">Email address *</label>
                    <input id="editEmail" name="email" type="email"
                           class="form-control" maxlength="255" required>
                    @error('email')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="filter-label" for="editPassword">
                        New password <span class="muted">(optional)</span>
                    </label>
                    <input id="editPassword" name="password" type="password"
                           class="form-control" minlength="8"
                           autocomplete="new-password">
                    <div class="form-text">
                        Leave blank to keep the current password.
                    </div>
                    @error('password')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="filter-label" for="editRole">Application role *</label>
                    <select id="editRole" name="role" class="form-select" required>
                        @foreach($roles as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                        Your own role cannot be changed from this page.
                    </div>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           name="email_verified" value="1" id="editVerified">
                    <label class="form-check-label" for="editVerified">
                        Email address is verified
                    </label>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-terra-outline"
                        data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-terra">
                    <i class="bi bi-save me-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- CHANGE ROLE MODAL --}}
<div class="modal fade terra-users" id="changeRoleModal" tabindex="-1"
     aria-labelledby="changeRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form method="POST" action="#" class="modal-content" id="changeRoleForm">
            @csrf
            @method('PATCH')

            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="changeRoleModalLabel">Change User Role</h5>
                    <div class="small muted">Update application permissions</div>
                </div>
                <button type="button" class="btn-close"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-3">
                    Update the role for
                    <strong id="changeRoleUserName">this user</strong>.
                </p>

                <label class="filter-label" for="changeRoleSelect">New role</label>
                <select id="changeRoleSelect" name="role" class="form-select" required>
                    @foreach($roles as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <div class="alert alert-warning small mt-3 mb-0">
                    <i class="bi bi-shield-exclamation me-1"></i>
                    Role changes can affect the user's access to the platform.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-terra-outline"
                        data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-terra">Update Role</button>
            </div>
        </form>
    </div>
</div>

{{-- DELETE USER MODAL --}}
<div class="modal fade terra-users" id="deleteUserModal" tabindex="-1"
     aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form method="POST" action="#" class="modal-content" id="deleteUserForm">
            @csrf
            @method('DELETE')

            <div class="modal-body text-center py-4">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
                     style="width:60px;height:60px;background:#fff0f0;color:#b42318;font-size:1.5rem;">
                    <i class="bi bi-trash3"></i>
                </div>

                <h5 class="fw-bold mb-2" style="color:#19265d;">
                    Delete User?
                </h5>

                <p class="text-secondary mb-1">
                    You are about to delete
                    <strong id="deleteUserName">this user</strong>.
                </p>

                <p class="small text-danger mb-0">
                    This action cannot be undone. Ensure this account is not
                    required by linked tenant or property records.
                </p>
            </div>

            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-terra-outline"
                        data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash3 me-1"></i> Delete User
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editUserModal');

    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            const form = document.getElementById('editUserForm');
            form.action = button.dataset.updateUrl;

            document.getElementById('editName').value = button.dataset.userName || '';
            document.getElementById('editEmail').value = button.dataset.userEmail || '';
            document.getElementById('editRole').value = button.dataset.userRole || 'tenant';
            document.getElementById('editVerified').checked =
                button.dataset.userVerified === '1';
            document.getElementById('editPassword').value = '';
        });
    }

    const roleModal = document.getElementById('changeRoleModal');

    if (roleModal) {
        roleModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            document.getElementById('changeRoleForm').action = button.dataset.roleUrl;
            document.getElementById('changeRoleUserName').textContent =
                button.dataset.userName || 'this user';
            document.getElementById('changeRoleSelect').value =
                button.dataset.userRole || 'tenant';
        });
    }

    const deleteModal = document.getElementById('deleteUserModal');

    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            document.getElementById('deleteUserForm').action =
                button.dataset.deleteUrl;
            document.getElementById('deleteUserName').textContent =
                button.dataset.userName || 'this user';
        });
    }

    @if($errors->any() && old('_form') === 'create')
        const createModal = document.getElementById('createUserModal');
        if (createModal && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(createModal).show();
        }
    @endif
});
</script>
@endsection
