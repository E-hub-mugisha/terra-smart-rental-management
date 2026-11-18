@extends('layouts.app')

@section('title', 'User Management | Terra Smart Rental Management')

@section('content')
@php
    $roles = [
        'admin'   => 'Administrator',
        'owner'   => 'Property Owner',
        'manager' => 'Property Manager',
        'tenant'  => 'Tenant',
        'accountant' => 'Accountant / Finance',
        'agent'   => 'Real Estate Agent',
        'technician' => 'Service Provider / Technician',
    ];

    $roleShort = [
        'admin'   => 'Administrator',
        'owner'   => 'Owner',
        'manager' => 'Manager',
        'tenant'  => 'Tenant',
        'accountant' => 'Accountant',
        'agent'   => 'Agent',
        'technician' => 'Technician',
        'field_staff' => 'Field Staff',
    ];

    $roleColors = [
        'admin'   => 'role-admin',
        'owner'   => 'role-owner',
        'manager' => 'role-manager',
        'tenant'  => 'role-tenant',
        'accountant' => 'role-manager',
        'agent'   => 'role-owner',
        'technician' => 'role-tenant',
        'field_staff' => 'role-manager',
    ];

    $roleIcons = [
        'admin'   => 'bi-shield-lock',
        'owner'   => 'bi-building',
        'manager' => 'bi-briefcase',
        'tenant'  => 'bi-person',
        'accountant' => 'bi-calculator',
        'agent'   => 'bi-person-badge',
        'technician' => 'bi-tools',
        'field_staff' => 'bi-person-walking',
    ];

    // Admin routes that show the profile of each role.
    $profileRoutes = [
        'owner'   => 'admin.owners.show',
        'manager' => 'admin.managers.show',
        'tenant'  => 'admin.tenants.show',
    ];

    $profileTables = [
        'owner'   => 'property_owners',
        'manager' => 'property_managers',
        'tenant'  => 'tenants',
    ];

    $totalUsers     = $users->total();
    $canManageUsers = auth()->user()?->isAdmin() ?? false;
    $activeRole     = $filters['role'] ?? '';
    $activeSearch   = $filters['search'] ?? '';

    // Overall counts per role (not only the current page).
    $roleCounts = \Illuminate\Support\Facades\DB::table('users')
        ->whereNull('deleted_at')
        ->selectRaw('role, COUNT(*) as total')
        ->groupBy('role')
        ->pluck('total', 'role');
    $allUsersCount = $roleCounts->sum();

    // Resolve profile ids (owner / manager / tenant record) for users on this page
    // with one query per role, so there is no N+1 problem.
    $pageUserIds = $users->getCollection()->pluck('id')->all();
    $profileIds  = [];
    foreach ($profileTables as $role => $table) {
        $profileIds[$role] = \Illuminate\Support\Facades\DB::table($table)
            ->whereIn('user_id', $pageUserIds)
            ->whereNull('deleted_at')
            ->pluck('id', 'user_id');
    }
@endphp

<style>
    .terra-users {
        --terra-orange: #D05208;
        --terra-orange-dark: #b74706;
        --terra-orange-soft: #fff0e7;
        --terra-navy: #19265d;
        --terra-border: #e7ebf2;
        --terra-muted: #718096;
        --terra-bg-soft: #f8f9fc;
        color: #273248;
        font-size: .9rem;
    }

    .terra-users .page-title { color: var(--terra-navy); font-weight: 800; letter-spacing: -.6px; }
    .terra-users .muted { color: var(--terra-muted); }

    .terra-users .btn-terra {
        background: var(--terra-orange); color: #fff;
        border-color: var(--terra-orange); font-weight: 650;
    }
    .terra-users .btn-terra:hover { background: var(--terra-orange-dark); color: #fff; border-color: var(--terra-orange-dark); }

    .terra-users .btn-terra-outline {
        color: var(--terra-navy); background: #fff;
        border: 1px solid #dbe1eb; font-weight: 600;
    }
    .terra-users .btn-terra-outline:hover { background: #f3f5fa; color: var(--terra-navy); }

    .terra-users .surface {
        background: #fff; border: 1px solid var(--terra-border);
        border-radius: 16px; box-shadow: 0 5px 22px rgba(25, 38, 93, .04);
    }

    /* Stat cards */
    .terra-users .stat-card {
        display: flex; align-items: center; gap: 14px; height: 100%;
        padding: 18px; background: #fff; border: 1px solid var(--terra-border);
        border-radius: 14px; text-decoration: none; color: inherit;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }
    .terra-users a.stat-card:hover {
        transform: translateY(-2px); border-color: #d6dcea;
        box-shadow: 0 8px 22px rgba(25, 38, 93, .08);
    }
    .terra-users .stat-icon {
        display: flex; align-items: center; justify-content: center;
        width: 44px; height: 44px; flex-shrink: 0; border-radius: 13px; font-size: 1.2rem;
    }
    .terra-users .stat-orange { background: var(--terra-orange-soft); color: var(--terra-orange); }
    .terra-users .stat-navy   { background: #edf0fb; color: var(--terra-navy); }
    .terra-users .stat-green  { background: #e8f7ef; color: #18794e; }
    .terra-users .stat-purple { background: #f1ecfd; color: #6b3fc4; }
    .terra-users .stat-number { color: var(--terra-navy); font-size: 1.45rem; font-weight: 800; line-height: 1.2; }
    .terra-users .stat-label  { color: var(--terra-muted); font-size: .78rem; margin-top: 4px; }

    /* Filters */
    .terra-users .filter-label { display: block; color: #536078; font-size: .76rem; font-weight: 700; margin-bottom: 7px; }
    .terra-users .form-control, .terra-users .form-select { border-color: #dfe5ee; border-radius: 9px; min-height: 42px; font-size: .88rem; }
    .terra-users .form-control:focus, .terra-users .form-select:focus {
        border-color: var(--terra-orange); box-shadow: 0 0 0 .2rem rgba(208, 82, 8, .1);
    }

    .terra-users .role-pills { display: flex; flex-wrap: wrap; gap: 8px; }
    .terra-users .role-pill {
        display: inline-flex; align-items: center; gap: 7px; padding: 7px 13px;
        border: 1px solid #dfe5ee; border-radius: 22px; background: #fff;
        color: #46536b; font-size: .8rem; font-weight: 650; text-decoration: none;
        transition: background .15s ease, border-color .15s ease;
    }
    .terra-users .role-pill:hover { background: #f5f7fb; color: var(--terra-navy); }
    .terra-users .role-pill .pill-count {
        background: #eef1f7; color: #5b6880; border-radius: 12px;
        padding: 1px 8px; font-size: .72rem; font-weight: 750;
    }
    .terra-users .role-pill.active { background: var(--terra-navy); border-color: var(--terra-navy); color: #fff; }
    .terra-users .role-pill.active .pill-count { background: rgba(255, 255, 255, .18); color: #fff; }

    /* Table */
    .terra-users .table { --bs-table-bg: transparent; margin: 0; }
    .terra-users .table thead th {
        padding: 14px 17px; background: var(--terra-bg-soft); color: #748096;
        border-bottom: 1px solid var(--terra-border); font-size: .71rem;
        text-transform: uppercase; letter-spacing: .45px; white-space: nowrap;
    }
    .terra-users .table tbody td { padding: 15px 17px; border-color: #edf0f5; vertical-align: middle; }
    .terra-users .table tbody tr { transition: background .12s ease; }
    .terra-users .table tbody tr:hover { background: #fbfcfe; }
    .terra-users .table tbody tr:last-child td { border-bottom: 0; }

    .terra-users .user-avatar {
        display: flex; align-items: center; justify-content: center;
        width: 43px; height: 43px; flex-shrink: 0; border-radius: 13px;
        font-weight: 800; font-size: .9rem;
    }
    .terra-users .avatar-admin   { background: var(--terra-orange-soft); color: var(--terra-orange); }
    .terra-users .avatar-owner   { background: #f1ecfd; color: #6b3fc4; }
    .terra-users .avatar-manager { background: #eaf1ff; color: #244f98; }
    .terra-users .avatar-tenant  { background: #e8f7ef; color: #18794e; }

    .terra-users .user-name { color: var(--terra-navy); font-weight: 750; overflow-wrap: anywhere; }
    .terra-users a.user-name { text-decoration: none; }
    .terra-users a.user-name:hover { color: var(--terra-orange); text-decoration: underline; }
    .terra-users .user-email { color: var(--terra-muted); font-size: .78rem; overflow-wrap: anywhere; margin-top: 3px; }

    .terra-users .role-badge {
        display: inline-flex; padding: 6px 10px; align-items: center; gap: 6px;
        border-radius: 20px; font-size: .73rem; font-weight: 750; white-space: nowrap;
    }
    .terra-users .role-admin   { color: #a13d08; background: var(--terra-orange-soft); }
    .terra-users .role-owner   { color: #6b3fc4; background: #f1ecfd; }
    .terra-users .role-manager { color: #244f98; background: #eaf1ff; }
    .terra-users .role-tenant  { color: #18794e; background: #e8f7ef; }

    .terra-users .verification { display: inline-flex; align-items: center; gap: 5px; font-size: .78rem; font-weight: 650; }
    .terra-users .verified { color: #168052; }
    .terra-users .unverified { color: #9a6700; }

    /* Actions dropdown (small font) */
    .terra-users .action-menu,
    .action-menu {
        min-width: 190px; padding: 6px; border: 1px solid var(--terra-border);
        border-radius: 12px; box-shadow: 0 10px 30px rgba(25, 38, 93, .12);
        font-size: .76rem;
    }
    .terra-users .action-menu .dropdown-item {
        display: flex; align-items: center; gap: 9px; padding: 7px 10px;
        border-radius: 8px; color: #35425b; font-size: .76rem; font-weight: 600;
    }
    .terra-users .action-menu .dropdown-item i { font-size: .85rem; color: #7b879c; }
    .terra-users .action-menu .dropdown-item:hover,
    .terra-users .action-menu .dropdown-item:focus { background: #f3f5fa; color: var(--terra-navy); }
    .terra-users .action-menu .dropdown-item:first-child:hover { color: var(--terra-orange-dark); }
    .terra-users .action-menu .item-danger,
    .terra-users .action-menu .item-danger i { color: #b42318; }
    .terra-users .action-menu .item-danger:hover { background: #fff0f0; color: #b42318; }
    .terra-users .action-menu .dropdown-divider { margin: 5px 4px; border-color: #edf0f5; }
    .terra-users .action-menu .menu-note {
        display: flex; align-items: center; gap: 8px; padding: 7px 10px;
        color: #98a2b3; font-size: .74rem;
    }

    .terra-users .action-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 34px; height: 34px; padding: 0; border: 1px solid #e2e7ef;
        border-radius: 9px; background: #fff; color: #526078;
    }
    .terra-users .action-btn:hover { background: #f5f7fb; color: var(--terra-navy); }
    .terra-users .action-btn.action-danger:hover { background: #fff0f0; color: #b42318; border-color: #f4caca; }

    .terra-users .current-account {
        background: #f2f4fa; color: #667085; font-size: .72rem;
        padding: 3px 8px; border-radius: 7px; white-space: nowrap;
    }

    /* Empty state */
    .terra-users .empty-state { text-align: center; padding: 54px 20px; }
    .terra-users .empty-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 60px; height: 60px; border-radius: 18px; background: #f0f2f8;
        color: #8792a7; font-size: 1.6rem; margin-bottom: 14px;
    }

    /* Modals */
    .terra-users .modal-content { border: 0; border-radius: 17px; overflow: hidden; }
    .terra-users .modal-header { padding: 20px 22px; border-bottom: 1px solid var(--terra-border); }
    .terra-users .modal-title { color: var(--terra-navy); font-weight: 800; }
    .terra-users .modal-body { padding: 22px; }
    .terra-users .modal-footer { padding: 15px 22px; border-top: 1px solid var(--terra-border); }
    .terra-users .modal-heading-icon {
        display: inline-flex; width: 40px; height: 40px; align-items: center;
        justify-content: center; border-radius: 12px; background: var(--terra-orange-soft);
        color: var(--terra-orange); font-size: 1.15rem;
    }
    .terra-users .pw-toggle { border: 1px solid #dfe5ee; background: #fff; color: #667085; }
    .terra-users .pw-toggle:hover { background: #f5f7fb; }

    @media (max-width: 767.98px) {
        .terra-users .stat-card { padding: 14px; }
        .terra-users .table thead th, .terra-users .table tbody td { padding: 12px; }
        .terra-users .modal-body { padding: 18px; }
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
                Manage accounts and roles, and open the full profile of owners, managers and tenants.
            </p>
        </div>

        @if($canManageUsers)
            <button type="button" class="btn btn-terra"
                    data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="bi bi-person-plus me-1"></i> Create User
            </button>
        @endif
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm" role="alert">
            <div class="fw-bold mb-1">
                <i class="bi bi-exclamation-triangle me-2"></i>Please correct the following:
            </div>
            <ul class="mb-0 ps-4">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Summary (platform-wide, click to filter) --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <a href="{{ route('admin.users.index') }}" class="stat-card">
                <div class="stat-icon stat-navy"><i class="bi bi-people"></i></div>
                <div>
                    <div class="stat-number">{{ number_format($allUsersCount) }}</div>
                    <div class="stat-label">Total users</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="{{ route('admin.users.index', ['role' => 'owner']) }}" class="stat-card">
                <div class="stat-icon stat-purple"><i class="bi bi-building"></i></div>
                <div>
                    <div class="stat-number">{{ number_format($roleCounts['owner'] ?? 0) }}</div>
                    <div class="stat-label">Property owners</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="{{ route('admin.users.index', ['role' => 'manager']) }}" class="stat-card">
                <div class="stat-icon stat-orange"><i class="bi bi-briefcase"></i></div>
                <div>
                    <div class="stat-number">{{ number_format($roleCounts['manager'] ?? 0) }}</div>
                    <div class="stat-label">Property managers</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="{{ route('admin.users.index', ['role' => 'tenant']) }}" class="stat-card">
                <div class="stat-icon stat-green"><i class="bi bi-person-check"></i></div>
                <div>
                    <div class="stat-number">{{ number_format($roleCounts['tenant'] ?? 0) }}</div>
                    <div class="stat-label">Tenants</div>
                </div>
            </a>
        </div>
    </div>

    {{-- Search and role filters --}}
    <div class="surface p-3 p-lg-4 mb-4">
        <form method="GET" action="{{ route('admin.users.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-lg-8">
                    <label class="filter-label" for="userSearch">Search users</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white" style="border-color:#dfe5ee;">
                            <i class="bi bi-search muted"></i>
                        </span>
                        <input id="userSearch" type="search" name="search"
                               value="{{ $activeSearch }}" class="form-control"
                               placeholder="Search by name or email">
                    </div>
                </div>

                <div class="col-lg-4 d-flex gap-2">
                    @if($activeRole)
                        <input type="hidden" name="role" value="{{ $activeRole }}">
                    @endif
                    <button type="submit" class="btn btn-terra flex-fill">
                        <i class="bi bi-funnel me-1"></i> Search
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-terra-outline">Reset</a>
                </div>
            </div>
        </form>

        <div class="role-pills mt-3">
            <a href="{{ route('admin.users.index', array_filter(['search' => $activeSearch])) }}"
               class="role-pill {{ $activeRole === '' ? 'active' : '' }}">
                All <span class="pill-count">{{ number_format($allUsersCount) }}</span>
            </a>
            @foreach($roles as $value => $label)
                <a href="{{ route('admin.users.index', array_filter(['search' => $activeSearch, 'role' => $value])) }}"
                   class="role-pill {{ $activeRole === $value ? 'active' : '' }}">
                    <i class="bi {{ $roleIcons[$value] }}"></i>
                    {{ $roleShort[$value] }}
                    <span class="pill-count">{{ number_format($roleCounts[$value] ?? 0) }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- User table --}}
    <div class="surface overflow-hidden">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 p-lg-4 border-bottom">
            <div>
                <h5 class="mb-1 fw-bold" style="color:#19265d;">Application Users</h5>
                <div class="small muted">
                    Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }}
                    of {{ number_format($totalUsers) }} users
                    @if($activeRole) &middot; {{ $roles[$activeRole] ?? ucfirst($activeRole) }} only @endif
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
                                ->filter()->take(2)
                                ->map(fn($part) => strtoupper(mb_substr($part, 0, 1)))
                                ->implode('');

                            $userRole      = $user->role ?: 'tenant';
                            $isCurrentUser = auth()->id() === $user->id;

                            $profileId  = isset($profileRoutes[$userRole])
                                ? ($profileIds[$userRole][$user->id] ?? null)
                                : null;
                            $profileUrl = $profileId ? route($profileRoutes[$userRole], $profileId) : null;
                        @endphp

                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="user-avatar avatar-{{ $userRole }}">{{ $userInitials ?: 'U' }}</div>
                                    <div class="min-w-0">
                                        @if($profileUrl)
                                            <a href="{{ $profileUrl }}" class="user-name d-inline-block">{{ $user->name }}</a>
                                        @else
                                            <div class="user-name">{{ $user->name }}</div>
                                        @endif
                                        <div class="user-email">{{ $user->email }}</div>
                                        @if($isCurrentUser)
                                            <span class="current-account d-inline-block mt-1">Your account</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="role-badge {{ $roleColors[$userRole] ?? 'role-tenant' }}">
                                    <i class="bi {{ $roleIcons[$userRole] ?? 'bi-person' }}"></i>
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
                                <div class="fw-semibold">{{ $user->created_at?->format('d M Y') ?? '—' }}</div>
                                <div class="small muted">{{ $user->created_at?->format('H:i') ?? '' }}</div>
                            </td>

                            <td class="text-end">
                                <div class="dropdown">
                                    <button type="button" class="action-btn"
                                            data-bs-toggle="dropdown"
                                            data-bs-popper-config='{"strategy":"fixed"}'
                                            aria-expanded="false"
                                            aria-label="Actions for {{ $user->name }}">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end action-menu">
                                        {{-- Profile --}}
                                        @if($profileUrl)
                                            <li>
                                                <a class="dropdown-item" href="{{ $profileUrl }}">
                                                    <i class="bi bi-person-lines-fill"></i>
                                                    View {{ strtolower($roleShort[$userRole]) }} profile
                                                </a>
                                            </li>
                                        @elseif($userRole === 'admin')
                                            <li>
                                                <span class="dropdown-item-text menu-note">
                                                    <i class="bi bi-dash-circle"></i> Staff account, no profile
                                                </span>
                                            </li>
                                        @else
                                            <li>
                                                <span class="dropdown-item-text menu-note">
                                                    <i class="bi bi-exclamation-circle"></i> No profile linked
                                                </span>
                                            </li>
                                        @endif

                                        @if($canManageUsers)
                                            <li><hr class="dropdown-divider"></li>

                                            <li>
                                                <button type="button" class="dropdown-item"
                                                        data-bs-toggle="modal" data-bs-target="#editUserModal"
                                                        data-user-id="{{ $user->id }}"
                                                        data-user-name="{{ $user->name }}"
                                                        data-user-email="{{ $user->email }}"
                                                        data-user-role="{{ $userRole }}"
                                                        data-user-verified="{{ $user->email_verified_at ? '1' : '0' }}"
                                                        data-update-url="{{ route('admin.users.update', $user) }}">
                                                    <i class="bi bi-pencil-square"></i> Edit user
                                                </button>
                                            </li>

                                            @if(!$isCurrentUser)
                                                <li>
                                                    <button type="button" class="dropdown-item"
                                                            data-bs-toggle="modal" data-bs-target="#changeRoleModal"
                                                            data-user-name="{{ $user->name }}"
                                                            data-user-role="{{ $userRole }}"
                                                            data-role-url="{{ route('admin.users.role.update', $user) }}">
                                                        <i class="bi bi-shield-check"></i> Change role
                                                    </button>
                                                </li>

                                                <li><hr class="dropdown-divider"></li>

                                                <li>
                                                    <button type="button" class="dropdown-item item-danger"
                                                            data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                                            data-user-name="{{ $user->name }}"
                                                            data-delete-url="{{ route('admin.users.destroy', $user) }}">
                                                        <i class="bi bi-trash3"></i> Delete user
                                                    </button>
                                                </li>
                                            @endif
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="bi bi-people"></i></div>
                                    <h5 class="fw-bold" style="color:#19265d;">No users found</h5>
                                    <p class="muted mb-3">No accounts match the current search or role filter.</p>
                                    <a href="{{ route('admin.users.index') }}" class="btn btn-terra-outline btn-sm">
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
                <div class="small muted">Page {{ $users->currentPage() }} of {{ $users->lastPage() }}</div>
                {{ $users->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

@if($canManageUsers)
{{-- CREATE USER MODAL --}}
<div class="modal fade terra-users" id="createUserModal" tabindex="-1"
     aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admin.users.store') }}" class="modal-content" id="createUserForm">
            @csrf
            <input type="hidden" name="_form" value="create">

            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-heading-icon"><i class="bi bi-person-plus"></i></div>
                    <div>
                        <h5 class="modal-title" id="createUserModalLabel">Create User</h5>
                        <div class="small muted">Register a new platform account</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="filter-label" for="createName">Full name *</label>
                    <input id="createName" name="name" type="text" class="form-control"
                           maxlength="255" value="{{ old('name') }}" required>
                    @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="filter-label" for="createEmail">Email address *</label>
                    <input id="createEmail" name="email" type="email" class="form-control"
                           maxlength="255" value="{{ old('email') }}" required autocomplete="email">
                    @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="filter-label" for="createPassword">Password *</label>
                    <div class="input-group">
                        <input id="createPassword" name="password" type="password" class="form-control"
                               minlength="8" required autocomplete="new-password">
                        <button type="button" class="btn pw-toggle" data-toggle-password="#createPassword"
                                aria-label="Show or hide password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">Use at least 8 characters.</div>
                    @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="filter-label" for="createPasswordConfirmation">Confirm password *</label>
                    <input id="createPasswordConfirmation" name="password_confirmation" type="password"
                           class="form-control" minlength="8" required autocomplete="new-password">
                </div>

                <div class="mb-3">
                    <label class="filter-label" for="createRole">Application role *</label>
                    <select id="createRole" name="role" class="form-select" required>
                        @foreach($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', 'tenant') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="email_verified"
                           value="1" id="createVerified" @checked(old('email_verified'))>
                    <label class="form-check-label" for="createVerified">Mark email as verified</label>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-terra-outline" data-bs-dismiss="modal">Cancel</button>
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
                    <div class="modal-heading-icon"><i class="bi bi-pencil-square"></i></div>
                    <div>
                        <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                        <div class="small muted">Update account information</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="filter-label" for="editName">Full name *</label>
                    <input id="editName" name="name" type="text" class="form-control" maxlength="255" required>
                </div>

                <div class="mb-3">
                    <label class="filter-label" for="editEmail">Email address *</label>
                    <input id="editEmail" name="email" type="email" class="form-control" maxlength="255" required>
                </div>

                <div class="mb-3">
                    <label class="filter-label" for="editPassword">
                        New password <span class="muted">(optional)</span>
                    </label>
                    <div class="input-group">
                        <input id="editPassword" name="password" type="password" class="form-control"
                               minlength="8" autocomplete="new-password">
                        <button type="button" class="btn pw-toggle" data-toggle-password="#editPassword"
                                aria-label="Show or hide password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">Leave blank to keep the current password.</div>
                </div>

                <div class="mb-3">
                    <label class="filter-label" for="editRole">Application role *</label>
                    <select id="editRole" name="role" class="form-select" required>
                        @foreach($roles as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Your own role cannot be changed from this page.</div>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="email_verified" value="1" id="editVerified">
                    <label class="form-check-label" for="editVerified">Email address is verified</label>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-terra-outline" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-terra"><i class="bi bi-save me-1"></i> Save Changes</button>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-3">Update the role for <strong id="changeRoleUserName">this user</strong>.</p>

                <label class="filter-label" for="changeRoleSelect">New role</label>
                <select id="changeRoleSelect" name="role" class="form-select" required>
                    @foreach($roles as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <div class="alert alert-warning small mt-3 mb-0">
                    <i class="bi bi-shield-exclamation me-1"></i>
                    Role changes affect the user's access. A linked owner, manager or tenant profile is not changed.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-terra-outline" data-bs-dismiss="modal">Cancel</button>
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

                <h5 class="fw-bold mb-2" id="deleteUserModalLabel" style="color:#19265d;">Delete User?</h5>
                <p class="text-secondary mb-1">
                    You are about to delete <strong id="deleteUserName">this user</strong>.
                </p>
                <p class="small text-danger mb-0">
                    This action cannot be undone. Make sure this account is not required by linked
                    owner, manager, tenant or property records.
                </p>
            </div>

            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-terra-outline" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-trash3 me-1"></i> Delete User</button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const fill = (modalId, handler) => {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        modal.addEventListener('show.bs.modal', function (event) {
            if (event.relatedTarget) handler(event.relatedTarget.dataset);
        });
    };

    fill('editUserModal', function (d) {
        document.getElementById('editUserForm').action = d.updateUrl;
        document.getElementById('editName').value = d.userName || '';
        document.getElementById('editEmail').value = d.userEmail || '';
        document.getElementById('editRole').value = d.userRole || 'tenant';
        document.getElementById('editVerified').checked = d.userVerified === '1';
        document.getElementById('editPassword').value = '';
    });

    fill('changeRoleModal', function (d) {
        document.getElementById('changeRoleForm').action = d.roleUrl;
        document.getElementById('changeRoleUserName').textContent = d.userName || 'this user';
        document.getElementById('changeRoleSelect').value = d.userRole || 'tenant';
    });

    fill('deleteUserModal', function (d) {
        document.getElementById('deleteUserForm').action = d.deleteUrl;
        document.getElementById('deleteUserName').textContent = d.userName || 'this user';
    });

    // Show / hide password
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.querySelector(btn.dataset.togglePassword);
            const icon = btn.querySelector('i');
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });

    @if($errors->any() && old('_form') === 'create')
        const createModal = document.getElementById('createUserModal');
        if (createModal && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(createModal).show();
        }
    @endif
});
</script>
@endsection