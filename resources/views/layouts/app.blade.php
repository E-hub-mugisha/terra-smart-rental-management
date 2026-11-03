<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Dashboard') | Terra Smart Rental Management</title>

    <meta name="description" content="@yield('meta_description', 'Manage rental properties, tenants, leases, payments and maintenance with Terra Smart Rental Management.')">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

        <style>

        :root {

            --terra-orange: #D05208;

            --terra-orange-dark: #AC4205;

            --terra-orange-light: #FFF0E6;

            --terra-navy: #19265d;

            --terra-navy-dark: #111b45;

            --terra-bg: #F5F7FB;

            --terra-surface: #FFFFFF;

            --terra-text: #20283A;

            --terra-muted: #737D91;

            --terra-border: #E5E9F1;

            --terra-sidebar-width: 272px;

            --terra-radius: 14px;

            --terra-shadow: 0 8px 28px rgba(25, 38, 93, .06);

        }

        * {

            box-sizing: border-box;

        }

        body {

            margin: 0;

            font-family: 'DM Sans', sans-serif;

            color: var(--terra-text);

            background: var(--terra-bg);

            font-size: 14px;

        }

        a {

            text-decoration: none;

        }

        .terra-app {

            min-height: 100vh;

        }

        .terra-sidebar {

            position: fixed;

            z-index: 1040;

            top: 0;

            bottom: 0;

            left: 0;

            width: var(--terra-sidebar-width);

            display: flex;

            flex-direction: column;

            background: var(--terra-surface);

            border-right: 1px solid var(--terra-border);

            transition: transform .25s ease;

        }

        .terra-brand {

            min-height: 78px;

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 18px 22px;

            border-bottom: 1px solid var(--terra-border);

            color: var(--terra-navy);

        }

        .terra-brand-mark {

            width: 42px;

            height: 42px;

            display: inline-flex;

            flex-shrink: 0;

            align-items: center;

            justify-content: center;

            border-radius: 12px;

            background: var(--terra-orange);

            color: #fff;

            font-size: 22px;

        }

        .terra-brand-name {

            font-size: 17px;

            line-height: 1.15;

            font-weight: 800;

            letter-spacing: -.4px;

        }

        .terra-brand-subtitle {

            margin-top: 4px;

            color: var(--terra-muted);

            font-size: 10px;

            font-weight: 600;

            letter-spacing: .7px;

            text-transform: uppercase;

        }

        .terra-sidebar-content {

            flex: 1;

            overflow-y: auto;

            padding: 16px 13px 24px;

        }

        .terra-nav-group {

            margin-bottom: 23px;

        }

        .terra-nav-heading {

            padding: 0 12px;

            margin: 0 0 8px;

            color: #98A1B2;

            font-size: 10px;

            font-weight: 800;

            letter-spacing: 1.1px;

            text-transform: uppercase;

        }

        .terra-nav {

            display: flex;

            flex-direction: column;

            gap: 4px;

        }

        .terra-nav-link {

            position: relative;

            display: flex;

            align-items: center;

            gap: 12px;

            min-height: 43px;

            padding: 10px 12px;

            border-radius: 10px;

            color: #59647A;

            font-size: 13px;

            font-weight: 600;

            transition: background .18s ease, color .18s ease;

        }

        .terra-nav-link i {

            width: 19px;

            flex-shrink: 0;

            font-size: 17px;

            text-align: center;

        }

        .terra-nav-link:hover {

            background: #F6F7FA;

            color: var(--terra-navy);

        }

        .terra-nav-link.active {

            background: var(--terra-orange-light);

            color: var(--terra-orange);

            font-weight: 700;

        }

        .terra-nav-link.active::before {

            content: "";

            position: absolute;

            left: -13px;

            top: 9px;

            bottom: 9px;

            width: 3px;

            border-radius: 0 4px 4px 0;

            background: var(--terra-orange);

        }

        .terra-sidebar-footer {

            padding: 15px 18px;

            border-top: 1px solid var(--terra-border);

            color: var(--terra-muted);

            font-size: 11px;

        }

        .terra-main {

            min-height: 100vh;

            margin-left: var(--terra-sidebar-width);

        }

        .terra-topbar {

            position: sticky;

            z-index: 1020;

            top: 0;

            min-height: 76px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 18px;

            padding: 12px 28px;

            background: rgba(255, 255, 255, .96);

            border-bottom: 1px solid var(--terra-border);

            backdrop-filter: blur(12px);

        }

        .terra-topbar-left,

        .terra-topbar-right {

            display: flex;

            align-items: center;

            gap: 14px;

        }

        .terra-page-context {

            min-width: 0;

        }

        .terra-page-context h1 {

            margin: 0;

            color: var(--terra-navy);

            font-size: 18px;

            font-weight: 800;

        }

        .terra-page-context p {

            margin: 4px 0 0;

            color: var(--terra-muted);

            font-size: 12px;

        }

        .terra-icon-button {

            width: 40px;

            height: 40px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border: 1px solid var(--terra-border);

            border-radius: 11px;

            background: #fff;

            color: var(--terra-navy);

            font-size: 18px;

        }

        .terra-icon-button:hover {

            border-color: #F0C3A7;

            color: var(--terra-orange);

        }

        .terra-primary-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            min-height: 40px;

            padding: 9px 14px;

            border: 0;

            border-radius: 10px;

            background: var(--terra-orange);

            color: #fff;

            font-size: 12px;

            font-weight: 700;

        }

        .terra-primary-button:hover {

            background: var(--terra-orange-dark);

            color: #fff;

        }

        .terra-profile-button {

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 3px;

            border: 0;

            background: transparent;

            text-align: left;

        }

        .terra-avatar {

            width: 39px;

            height: 39px;

            display: inline-flex;

            flex-shrink: 0;

            align-items: center;

            justify-content: center;

            border-radius: 12px;

            background: var(--terra-navy);

            color: #fff;

            font-size: 12px;

            font-weight: 800;

        }

        .terra-profile-name {

            max-width: 150px;

            overflow: hidden;

            color: var(--terra-navy);

            font-size: 12px;

            font-weight: 800;

            text-overflow: ellipsis;

            white-space: nowrap;

        }

        .terra-profile-role {

            margin-top: 3px;

            color: var(--terra-muted);

            font-size: 11px;

        }

        .terra-content {

            padding: 28px;

        }

        .terra-content .card {

            border: 1px solid var(--terra-border);

            border-radius: var(--terra-radius);

            box-shadow: var(--terra-shadow);

        }

        .terra-content .btn-primary {

            border-color: var(--terra-orange);

            background: var(--terra-orange);

        }

        .terra-content .btn-primary:hover {

            border-color: var(--terra-orange-dark);

            background: var(--terra-orange-dark);

        }

        .terra-content .form-control,

        .terra-content .form-select {

            min-height: 42px;

            border-color: var(--terra-border);

            border-radius: 9px;

            font-size: 13px;

        }

        .terra-content .form-control:focus,

        .terra-content .form-select:focus {

            border-color: #E8A47D;

            box-shadow: 0 0 0 .2rem rgba(208, 82, 8, .10);

        }

        .terra-footer {

            padding: 18px 28px 24px;

            color: var(--terra-muted);

            font-size: 11px;

        }

        .terra-sidebar-backdrop {

            display: none;

        }

        .terra-empty-nav {

            padding: 10px 12px;

            color: var(--terra-muted);

            font-size: 12px;

        }

        @media (max-width: 991.98px) {

            .terra-sidebar {

                transform: translateX(-100%);

            }

            body.terra-sidebar-open .terra-sidebar {

                transform: translateX(0);

            }

            .terra-sidebar-backdrop {

                position: fixed;

                z-index: 1030;

                inset: 0;

                background: rgba(12, 20, 47, .45);

            }

            body.terra-sidebar-open .terra-sidebar-backdrop {

                display: block;

            }

            .terra-main {

                margin-left: 0;

            }

            .terra-topbar {

                padding: 12px 18px;

            }

            .terra-content {

                padding: 20px 16px;

            }

            .terra-footer {

                padding: 16px;

            }

        }

        @media (max-width: 575.98px) {

            .terra-topbar {

                min-height: 66px;

                gap: 8px;

                padding: 10px 12px;

            }

            .terra-page-context h1 {

                font-size: 15px;

            }

            .terra-page-context p,

            .terra-profile-copy,

            .terra-quick-action-label {

                display: none;

            }

            .terra-topbar-left,

            .terra-topbar-right {

                gap: 8px;

            }

            .terra-primary-button {

                width: 40px;

                min-width: 40px;

                padding: 8px;

            }

            .terra-profile-button .bi-chevron-down {

                display: none;

            }

            .terra-content {

                padding: 16px 12px;

            }

        }

    </style>

        @stack('styles')

</head>

<body>

        @php

        $currentUser = auth()->user();

        $userName = $currentUser?->name ?? 'Guest';

        $userEmail = $currentUser?->email ?? '';

        $rawRole = strtolower(trim((string) ($currentUser?->role ?? 'user')));

        $roleAliases = [

        'super_admin' => 'admin',

        'terra_admin' => 'admin',

        'owner' => 'property_owner',

        'landlord' => 'property_owner',

        'manager' => 'property_manager',

        'agent' => 'real_estate_agent',

        'finance' => 'accountant',

        'finance_officer' => 'accountant',

        'technician' => 'service_provider',

        ];

        $role = $roleAliases[$rawRole] ?? $rawRole;

        $roleLabels = [

        'admin' => 'Administrator',

        'property_owner' => 'Property Owner',

        'property_manager' => 'Property Manager',

        'tenant' => 'Tenant',

        'real_estate_agent' => 'Real Estate Agent',

        'service_provider' => 'Service Provider',

        'accountant' => 'Accountant',

        'user' => 'User',

        ];

        $roleLabel = $roleLabels[$role] ?? ucwords(str_replace('_', ' ', $role));

        /*

        * Returns the first registered route name from a list.

        * Fully qualified facade call avoids Blade import/compile issues.

        */

        $resolveTerraRoute = function (array $candidates) {

        foreach ($candidates as $candidate) {

        if (\Illuminate\Support\Facades\Route::has($candidate)) {

        return $candidate;

        }

        }

        return null;

        };

        $isTerraRouteActive = function (array $patterns) {

        foreach ($patterns as $pattern) {

        if (request()->routeIs($pattern)) {

        return true;

        }

        }

        return false;

        };

        $initials = collect(preg_split('/\s+/', trim($userName)))

        ->filter()

        ->take(2)

        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))

        ->implode('');

        $initials = $initials ?: 'G';

        /*

        * Dashboard fallback candidates by normalized role.

        */

        $dashboardCandidates = match ($role) {

        'admin' => [

        'admin.dashboard',

        'dashboard',

        ],

        'property_owner' => [

        'property-manager.dashboard',

    'property-management.dashboard',

        'property-owner.dashboard',

        'dashboard',

        ],

        'property_manager' => [

        'property-manager.dashboard',

        'manager.dashboard',

        'dashboard',

        ],

        'tenant' => [

        'tenant-portal.dashboard',

        'dashboard',

        ],

        'accountant' => [

        'accountant.dashboard',

        'finance.dashboard',

        'dashboard',

        ],

        'real_estate_agent' => [

        'real-estate-agent.dashboard',

        'agent.dashboard',

        'dashboard',

        ],

        'service_provider' => [

        'service-provider.dashboard',

        'dashboard',

        ],

        default => [

        'dashboard',

        'rentals.index',

        ],

        };

        $dashboardRoute = $resolveTerraRoute($dashboardCandidates);

        /*

        * Every item includes route candidates and patterns used for active highlighting.

        * Links are displayed only when at least one candidate route exists.

        */

        $navigation = [];

        if ($role === 'admin') {

        $navigation = [

        [

        'heading' => 'Overview',

        'items' => [

        ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'routes' => ['admin.dashboard', 'dashboard'], 'active' => ['admin.dashboard']],

        ],

        ],

        [

        'heading' => 'People & Access',

        'items' => [

        ['label' => 'All Users', 'icon' => 'bi-people', 'routes' => ['admin.users.index'], 'active' => ['admin.users.*']],

        ['label' => 'Property Owners', 'icon' => 'bi-person-badge', 'routes' => ['admin.owners.index', 'admin.property-owners.index'], 'active' => ['admin.owners.*', 'admin.property-owners.*']],

        ['label' => 'Property Managers', 'icon' => 'bi-person-gear', 'routes' => ['admin.managers.index', 'admin.property-managers.index'], 'active' => ['admin.managers.*', 'admin.property-managers.*']],

        ['label' => 'Tenants', 'icon' => 'bi-person-vcard', 'routes' => ['admin.tenants.index'], 'active' => ['admin.tenants.*']],

        ],

        ],

        [

        'heading' => 'Property Operations',

        'items' => [

        ['label' => 'Managed Properties', 'icon' => 'bi-buildings', 'routes' => ['admin.managed-properties.index', 'admin.properties.index'], 'active' => ['admin.managed-properties.*', 'admin.properties.*']],

        ['label' => 'Buildings & Floors', 'icon' => 'bi-building', 'routes' => ['admin.buildings.index', 'admin.floors.index'], 'active' => ['admin.buildings.*', 'admin.floors.*']],

        ['label' => 'Units', 'icon' => 'bi-door-open', 'routes' => ['admin.units.index'], 'active' => ['admin.units.*']],

        ['label' => 'Rental Listings', 'icon' => 'bi-house', 'routes' => ['rentals.index', 'admin.rentals.index', 'admin.rental-listings.index'], 'active' => ['rentals.*', 'admin.rentals.*', 'admin.rental-listings.*']],

        ['label' => 'Applications', 'icon' => 'bi-file-earmark-person', 'routes' => ['admin.tenant-applications.index', 'admin.applications.index'], 'active' => ['admin.tenant-applications.*', 'admin.applications.*']],

        ['label' => 'Leases', 'icon' => 'bi-file-earmark-text', 'routes' => ['admin.leases.index'], 'active' => ['admin.leases.*']],

        ],

        ],

        [

        'heading' => 'Finance',

        'items' => [

        ['label' => 'Rent Payments', 'icon' => 'bi-cash-stack', 'routes' => ['admin.payments.index', 'admin.rent-payments.index'], 'active' => ['admin.payments.*', 'admin.rent-payments.*']],

        ['label' => 'Rent Invoices', 'icon' => 'bi-receipt', 'routes' => ['admin.rent-invoices.index', 'admin.invoices.index'], 'active' => ['admin.rent-invoices.*', 'admin.invoices.*']],

        ['label' => 'Property Expenses', 'icon' => 'bi-wallet2', 'routes' => ['admin.property-expenses.index', 'admin.expenses.index'], 'active' => ['admin.property-expenses.*', 'admin.expenses.*']],

        ['label' => 'Financial Reports', 'icon' => 'bi-graph-up-arrow', 'routes' => ['admin.financial-reports.index', 'admin.reports.index'], 'active' => ['admin.financial-reports.*', 'admin.reports.*']],

        ],

        ],

        [

        'heading' => 'Service & Support',

        'items' => [

        ['label' => 'Maintenance', 'icon' => 'bi-tools', 'routes' => ['admin.maintenance-requests.index', 'admin.maintenance.index'], 'active' => ['admin.maintenance-requests.*', 'admin.maintenance.*']],

        ['label' => 'Support Tickets', 'icon' => 'bi-headset', 'routes' => ['admin.support.index', 'support.index'], 'active' => ['admin.support.*', 'support.*']],

        ],

        ],

        [

        'heading' => 'Account',

        'items' => [

        ['label' => 'My Profile', 'icon' => 'bi-person-circle', 'routes' => ['profile.edit'], 'active' => ['profile.*']],

        ],

        ],

        ];

        } elseif ($role === 'property_owner') {

    $navigation = [

    ['heading' => 'Workspace', 'items' => [

    ['label' => 'Owner Dashboard', 'icon' => 'bi-grid-1x2', 'routes' => ['property-manager.dashboard', 'property-management.dashboard', 'property-owner.dashboard', 'dashboard'], 'active' => ['property-manager.dashboard', 'property-management.dashboard', 'property-owner.dashboard']],

    ]],

    ['heading' => 'My Properties', 'items' => [

    ['label' => 'My Properties', 'icon' => 'bi-buildings', 'routes' => ['property-manager.properties.index', 'property-management.properties.index', 'property-owner.properties.index'], 'active' => ['property-manager.properties.*', 'property-management.properties.*', 'property-owner.properties.*']],

    ]],

    ['heading' => 'Tenancy', 'items' => [

    ['label' => 'Tenants', 'icon' => 'bi-people', 'routes' => ['property-manager.tenants.index', 'property-management.tenants.index', 'property-owner.tenants.index'], 'active' => ['property-manager.tenants.*', 'property-management.tenants.*', 'property-owner.tenants.*']],

    ['label' => 'Applications', 'icon' => 'bi-file-earmark-person', 'routes' => ['property-manager.applications.index', 'property-management.applications.index', 'property-owner.applications.index'], 'active' => ['property-manager.applications.*', 'property-management.applications.*', 'property-owner.applications.*']],

    ['label' => 'Leases', 'icon' => 'bi-file-earmark-text', 'routes' => ['property-manager.leases.index', 'property-management.leases.index', 'property-owner.leases.index'], 'active' => ['property-manager.leases.*', 'property-management.leases.*', 'property-owner.leases.*']],

    ]],

    ['heading' => 'Finance', 'items' => [

    ['label' => 'Rent Invoices', 'icon' => 'bi-receipt', 'routes' => ['property-manager.invoices.index', 'property-management.invoices.index', 'property-owner.invoices.index'], 'active' => ['property-manager.invoices.*', 'property-management.invoices.*', 'property-owner.invoices.*']],

    ['label' => 'Rent Payments', 'icon' => 'bi-cash-stack', 'routes' => ['property-manager.payments.index', 'property-management.payments.index', 'property-owner.payments.index'], 'active' => ['property-manager.payments.*', 'property-management.payments.*', 'property-owner.payments.*']],

    ['label' => 'Property Expenses', 'icon' => 'bi-wallet2', 'routes' => ['property-manager.expenses.index', 'property-management.expenses.index', 'property-owner.expenses.index'], 'active' => ['property-manager.expenses.*', 'property-management.expenses.*', 'property-owner.expenses.*']],

    ['label' => 'Financial Reports', 'icon' => 'bi-graph-up-arrow', 'routes' => ['property-manager.reports.index', 'property-management.reports.index', 'property-owner.reports.index'], 'active' => ['property-manager.reports.*', 'property-management.reports.*', 'property-owner.reports.*']],

    ]],

    ['heading' => 'Property Services', 'items' => [

    ['label' => 'Maintenance Requests', 'icon' => 'bi-tools', 'routes' => ['property-manager.maintenance-requests.index', 'property-management.maintenance-requests.index', 'property-owner.maintenance.index'], 'active' => ['property-manager.maintenance-requests.*', 'property-management.maintenance-requests.*', 'property-owner.maintenance.*']],

    ]],

    ['heading' => 'Account', 'items' => [

    ['label' => 'My Profile', 'icon' => 'bi-person-circle', 'routes' => ['profile.edit'], 'active' => ['profile.*']],

    ]],

    ];

    } elseif ($role === 'property_manager') {

        $navigation = [

        [

        'heading' => 'Operations',

        'items' => [

        ['label' => 'Operations Dashboard', 'icon' => 'bi-grid-1x2', 'routes' => ['property-manager.dashboard', 'manager.dashboard', 'dashboard'], 'active' => ['property-manager.dashboard', 'manager.dashboard']],

        ['label' => 'Assigned Properties', 'icon' => 'bi-buildings', 'routes' => ['property-manager.properties.index', 'manager.properties.index', 'property-manager.managed-properties.index'], 'active' => ['property-manager.properties.*', 'property-manager.managed-properties.*', 'manager.properties.*']],

        ['label' => 'Buildings & Floors', 'icon' => 'bi-building', 'routes' => ['property-manager.buildings.index', 'manager.buildings.index', 'property-manager.floors.index'], 'active' => ['property-manager.buildings.*', 'property-manager.floors.*', 'manager.buildings.*']],

        ['label' => 'Units', 'icon' => 'bi-door-open', 'routes' => ['property-manager.units.index', 'manager.units.index'], 'active' => ['property-manager.units.*', 'manager.units.*']],

        ],

        ],

        [

        'heading' => 'Tenancy',

        'items' => [

        ['label' => 'Tenants', 'icon' => 'bi-people', 'routes' => ['property-manager.tenants.index', 'manager.tenants.index'], 'active' => ['property-manager.tenants.*', 'manager.tenants.*']],

        ['label' => 'Applications', 'icon' => 'bi-file-earmark-person', 'routes' => ['property-manager.tenant-applications.index', 'property-manager.applications.index', 'manager.applications.index'], 'active' => ['property-manager.tenant-applications.*', 'property-manager.applications.*', 'manager.applications.*']],

        ['label' => 'Leases', 'icon' => 'bi-file-earmark-text', 'routes' => ['property-manager.leases.index', 'manager.leases.index'], 'active' => ['property-manager.leases.*', 'manager.leases.*']],

        ],

        ],

        [

        'heading' => 'Rent & Reporting',

        'items' => [

        ['label' => 'Rent Invoices', 'icon' => 'bi-receipt', 'routes' => ['property-manager.invoices.index', 'manager.invoices.index'], 'active' => ['property-manager.invoices.*', 'manager.invoices.*']],

        ['label' => 'Rent Payments', 'icon' => 'bi-cash-stack', 'routes' => ['property-manager.payments.index', 'manager.payments.index'], 'active' => ['property-manager.payments.*', 'manager.payments.*']],

        ['label' => 'Property Expenses', 'icon' => 'bi-wallet2', 'routes' => ['property-manager.expenses.index', 'manager.expenses.index'], 'active' => ['property-manager.expenses.*', 'manager.expenses.*']],

        ['label' => 'Operational Reports', 'icon' => 'bi-graph-up-arrow', 'routes' => ['property-manager.reports.index', 'manager.reports.index'], 'active' => ['property-manager.reports.*', 'manager.reports.*']],

        ],

        ],

        [

        'heading' => 'Service & Support',

        'items' => [

        ['label' => 'Maintenance', 'icon' => 'bi-tools', 'routes' => ['property-manager.maintenance-requests.index', 'property-manager.maintenance.index', 'manager.maintenance-requests.index', 'manager.maintenance.index'], 'active' => ['property-manager.maintenance-requests.*', 'property-manager.maintenance.*', 'manager.maintenance-requests.*', 'manager.maintenance.*']],

        ['label' => 'My Profile', 'icon' => 'bi-person-circle', 'routes' => ['profile.edit'], 'active' => ['profile.*']],

        ],

        ],

        ];

        } elseif ($role === 'tenant') {

        $navigation = [

        [

        'heading' => 'My Home',

        'items' => [

        ['label' => 'Tenant Dashboard', 'icon' => 'bi-grid-1x2', 'routes' => ['tenant-portal.dashboard', 'dashboard'], 'active' => ['tenant-portal.dashboard']],

        ['label' => 'My Rental', 'icon' => 'bi-house', 'routes' => ['tenant-portal.rentals.index', 'my rental'], 'active' => ['tenant-portal.rentals.index']],

        ['label' => 'Find Rentals', 'icon' => 'bi-house', 'routes' => ['rentals.index'], 'active' => ['rentals.*']],

        ['label' => 'My Applications', 'icon' => 'bi-file-earmark-person', 'routes' => ['tenant-portal.applications.index'], 'active' => ['tenant-portal.applications.*']],

        ['label' => 'My Lease', 'icon' => 'bi-file-earmark-text', 'routes' => ['tenant-portal.leases.index', 'tenant-portal.lease'], 'active' => ['tenant-portal.leases.*', 'tenant-portal.lease']],

        ],

        ],

        [

        'heading' => 'Rent & Payments',

        'items' => [

        ['label' => 'Rent Invoices', 'icon' => 'bi-receipt', 'routes' => ['tenant-portal.rent-invoices.index', 'tenant-portal.invoices.index'], 'active' => ['tenant-portal.rent-invoices.*', 'tenant-portal.invoices.*']],

        ['label' => 'Payments', 'icon' => 'bi-cash-stack', 'routes' => ['tenant-portal.payments.index'], 'active' => ['tenant-portal.payments.*']],

        ],

        ],

        [

        'heading' => 'Help & Account',

        'items' => [

        ['label' => 'Maintenance Requests', 'icon' => 'bi-tools', 'routes' => ['tenant-portal.maintenance-requests.index', 'tenant-portal.maintenance.index'], 'active' => ['tenant-portal.maintenance-requests.*', 'tenant-portal.maintenance.*']],

        ['label' => 'Profile', 'icon' => 'bi-person-circle', 'routes' => ['tenant-portal.profile.index', 'profile.index'], 'active' => ['tenant-portal.profile.index', 'profile.*']],

        ['label' => 'Support', 'icon' => 'bi-headset', 'routes' => ['support.index'], 'active' => ['support.*']],

        ],

        ],

        ];

        } elseif ($role === 'accountant') {

        $navigation = [

        [

        'heading' => 'Finance Workspace',

        'items' => [

        ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'routes' => ['accountant.dashboard', 'finance.dashboard', 'dashboard'], 'active' => ['accountant.dashboard', 'finance.dashboard']],

        ['label' => 'Rent Invoices', 'icon' => 'bi-receipt', 'routes' => ['accountant.rent-invoices.index', 'finance.rent-invoices.index', 'admin.rent-invoices.index'], 'active' => ['accountant.rent-invoices.*', 'finance.rent-invoices.*']],

        ['label' => 'Rent Payments', 'icon' => 'bi-cash-stack', 'routes' => ['accountant.payments.index', 'finance.payments.index', 'admin.payments.index'], 'active' => ['accountant.payments.*', 'finance.payments.*']],

        ['label' => 'Property Expenses', 'icon' => 'bi-wallet2', 'routes' => ['accountant.expenses.index', 'finance.expenses.index', 'admin.property-expenses.index'], 'active' => ['accountant.expenses.*', 'finance.expenses.*']],

        ['label' => 'Financial Reports', 'icon' => 'bi-graph-up-arrow', 'routes' => ['accountant.reports.index', 'finance.reports.index', 'admin.financial-reports.index'], 'active' => ['accountant.reports.*', 'finance.reports.*']],

        ['label' => 'My Profile', 'icon' => 'bi-person-circle', 'routes' => ['profile.edit'], 'active' => ['profile.*']],

        ],

        ],

        ];

        } elseif ($role === 'real_estate_agent') {

        $navigation = [

        [

        'heading' => 'Workspace',

        'items' => [

        ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'routes' => ['real-estate-agent.dashboard', 'agent.dashboard', 'dashboard'], 'active' => ['real-estate-agent.dashboard', 'agent.dashboard']],

        ['label' => 'Rental Listings', 'icon' => 'bi-house', 'routes' => ['real-estate-agent.properties.index', 'agent.properties.index', 'rentals.index'], 'active' => ['real-estate-agent.properties.*', 'agent.properties.*', 'rentals.*']],

        ['label' => 'Applications', 'icon' => 'bi-file-earmark-person', 'routes' => ['real-estate-agent.applications.index', 'agent.applications.index'], 'active' => ['real-estate-agent.applications.*', 'agent.applications.*']],

        ['label' => 'My Profile', 'icon' => 'bi-person-circle', 'routes' => ['profile.edit'], 'active' => ['profile.*']],

        ],

        ],

        ];

        } elseif ($role === 'service_provider') {

        $navigation = [

        [

        'heading' => 'Service Workspace',

        'items' => [

        ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'routes' => ['service-provider.dashboard', 'dashboard'], 'active' => ['service-provider.dashboard']],

        ['label' => 'Maintenance Jobs', 'icon' => 'bi-tools', 'routes' => ['service-provider.maintenance.index', 'service-provider.maintenance-requests.index'], 'active' => ['service-provider.maintenance.*', 'service-provider.maintenance-requests.*']],

        ['label' => 'My Profile', 'icon' => 'bi-person-circle', 'routes' => ['profile.edit'], 'active' => ['profile.*']],

        ],

        ],

        ];

        } else {

        $navigation = [

        [

        'heading' => 'Explore Terra',

        'items' => [

        ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'routes' => ['dashboard'], 'active' => ['dashboard']],

        ['label' => 'Find Rentals', 'icon' => 'bi-house', 'routes' => ['rentals.index'], 'active' => ['rentals.*']],

        ['label' => 'My Profile', 'icon' => 'bi-person-circle', 'routes' => ['profile.edit'], 'active' => ['profile.*']],

        ],

        ],

        ];

        }

        /*

        * Resolve route names and remove links with no registered route.

        */

        foreach ($navigation as $groupIndex => $group) {

        $resolvedItems = [];

        foreach ($group['items'] as $item) {

        $resolvedRoute = $resolveTerraRoute($item['routes'] ?? []);

        if (!$resolvedRoute) {

        continue;

        }

        $item['resolved_route'] = $resolvedRoute;

        $item['is_active'] = $isTerraRouteActive($item['active'] ?? []);

        $resolvedItems[] = $item;

        }

        $navigation[$groupIndex]['items'] = $resolvedItems;

        }

        $navigation = array_values(array_filter($navigation, fn ($group) => !empty($group['items'])));

        /*

        * Role-aware Quick Actions.

        */

        $quickActionCandidates = match ($role) {

        'admin' => [

        ['label' => 'Add User', 'icon' => 'bi-person-plus', 'routes' => ['admin.users.create']],

        ['label' => 'Add Property', 'icon' => 'bi-building-add', 'routes' => ['admin.properties.create', 'admin.managed-properties.create']],

        ['label' => 'View Applications', 'icon' => 'bi-file-earmark-person', 'routes' => ['admin.tenant-applications.index', 'admin.applications.index']],

        ['label' => 'View Payments', 'icon' => 'bi-cash-stack', 'routes' => ['admin.payments.index', 'admin.rent-payments.index']],

        ],

        'property_owner' => [

    ['label' => 'My Properties', 'icon' => 'bi-buildings', 'routes' => ['property-manager.properties.index', 'property-management.properties.index', 'property-owner.properties.index']],

    ['label' => 'Review Applications', 'icon' => 'bi-file-earmark-person', 'routes' => ['property-manager.applications.index', 'property-management.applications.index', 'property-owner.applications.index']],

    ['label' => 'Record Payment', 'icon' => 'bi-cash-stack', 'routes' => ['property-manager.payments.create', 'property-management.payments.create', 'property-owner.payments.create']],

    ],

    'property_manager' => [

        ['label' => 'View Applications', 'icon' => 'bi-file-earmark-person', 'routes' => ['property-manager.tenant-applications.index', 'manager.applications.index']],

        ['label' => 'View Maintenance', 'icon' => 'bi-tools', 'routes' => ['property-manager.maintenance-requests.index', 'manager.maintenance.index']],

        ['label' => 'Record Payment', 'icon' => 'bi-cash-stack', 'routes' => ['property-manager.payments.create', 'manager.payments.create']],

        ],

        'tenant' => [

        ['label' => 'Find a Rental', 'icon' => 'bi-house', 'routes' => ['rentals.index']],

        ['label' => 'My Invoices', 'icon' => 'bi-receipt', 'routes' => ['tenant-portal.rent-invoices.index', 'tenant-portal.invoices.index']],

        ['label' => 'Request Maintenance', 'icon' => 'bi-tools', 'routes' => ['tenant-portal.maintenance-requests.create', 'tenant-portal.maintenance.create']],

        ],

        'accountant' => [

        ['label' => 'View Invoices', 'icon' => 'bi-receipt', 'routes' => ['accountant.rent-invoices.index', 'finance.rent-invoices.index', 'admin.rent-invoices.index']],

        ['label' => 'View Payments', 'icon' => 'bi-cash-stack', 'routes' => ['accountant.payments.index', 'finance.payments.index', 'admin.payments.index']],

        ['label' => 'Financial Reports', 'icon' => 'bi-graph-up-arrow', 'routes' => ['accountant.reports.index', 'finance.reports.index', 'admin.financial-reports.index']],

        ],

        default => [

        ['label' => 'Find a Rental', 'icon' => 'bi-house', 'routes' => ['rentals.index']],

        ['label' => 'My Profile', 'icon' => 'bi-person-circle', 'routes' => ['profile.edit']],

        ],

        };

        $quickActions = [];

        foreach ($quickActionCandidates as $action) {

        $resolvedRoute = $resolveTerraRoute($action['routes'] ?? []);

        if ($resolvedRoute) {

        $action['resolved_route'] = $resolvedRoute;

        $quickActions[] = $action;

        }

        }

        $pageTitle = trim($__env->yieldContent('page_title')) ?: trim($__env->yieldContent('title')) ?: 'Dashboard';

        $pageSubtitle = trim($__env->yieldContent('page_subtitle')) ?: 'Manage your rental operations in one place.';

        @endphp

        <div class="terra-sidebar-backdrop" id="terraSidebarBackdrop"></div>

        <div class="terra-app">

                <aside class="terra-sidebar" id="terraSidebar">

                        <a href="{{ $dashboardRoute ? route($dashboardRoute) : url('/') }}" class="terra-brand">

                                <span class="terra-brand-mark">

                                        <i class="bi bi-buildings"></i>

                                    </span>

                                <span>

                                        <span class="terra-brand-name d-block">Terra</span>

                                        <span class="terra-brand-subtitle d-block">Smart Rental Management</span>

                                    </span>

                            </a>

                        <div class="terra-sidebar-content">

                                @forelse ($navigation as $group)

                                <section class="terra-nav-group">

                                        <h2 class="terra-nav-heading">{{ $group['heading'] }}</h2>

                                        <nav class="terra-nav" aria-label="{{ $group['heading'] }}">

                                                @foreach ($group['items'] as $item)

                                                <a

                                                        href="{{ route($item['resolved_route']) }}"

                                                        class="terra-nav-link {{ $item['is_active'] ? 'active' : '' }}"

                                                        @if ($item['is_active']) aria-current="page" @endif>

                                                        <i class="bi {{ $item['icon'] }}"></i>

                                                        <span>{{ $item['label'] }}</span>

                                                    </a>

                                                @endforeach

                                            </nav>

                                    </section>

                                @empty

                                <p class="terra-empty-nav">No navigation items are configured for this account.</p>

                                @endforelse

                            </div>

                        <div class="terra-sidebar-footer">

                                <div class="fw-bold text-dark mb-1">Terra Smart Rental Management</div>

                                <div>&copy; {{ date('Y') }} Terra. All rights reserved.</div>

                            </div>

                    </aside>

                <main class="terra-main">

                        <header class="terra-topbar">

                                <div class="terra-topbar-left">

                                        <button

                                                type="button"

                                                class="terra-icon-button d-lg-none"

                                                id="terraSidebarToggle"

                                                aria-label="Open navigation"

                                                aria-controls="terraSidebar"

                                                aria-expanded="false">

                                                <i class="bi bi-list"></i>

                                            </button>

                                        <div class="terra-page-context">

                                                <h1>@yield('page_title', trim($__env->yieldContent('title')) ?: 'Dashboard')</h1>

                                                <p>@yield('page_subtitle', 'Welcome back. Here is your workspace overview.')</p>

                                            </div>

                                    </div>

                                <div class="terra-topbar-right">

                                        @if (!empty($quickActions))

                                        <div class="dropdown">

                                                <button

                                                        type="button"

                                                        class="terra-primary-button dropdown-toggle"

                                                        data-bs-toggle="dropdown"

                                                        aria-expanded="false">

                                                        <i class="bi bi-plus-lg"></i>

                                                        <span class="terra-quick-action-label">Quick Actions</span>

                                                    </button>

                                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow p-2" style="min-width: 220px; border-radius: 12px;">

                                                        @foreach ($quickActions as $action)

                                                        <li>

                                                                <a class="dropdown-item rounded-2 py-2" href="{{ route($action['resolved_route']) }}">

                                                                        <i class="bi {{ $action['icon'] }} me-2 text-secondary"></i>

                                                                        {{ $action['label'] }}

                                                                    </a>

                                                            </li>

                                                        @endforeach

                                                    </ul>

                                            </div>

                                        @endif

                                        <div class="dropdown">

                                                <button

                                                        type="button"

                                                        class="terra-profile-button"

                                                        data-bs-toggle="dropdown"

                                                        aria-expanded="false">

                                                        <span class="terra-avatar">{{ $initials }}</span>

                                                        <span class="terra-profile-copy">

                                                                <span class="terra-profile-name d-block">{{ $userName }}</span>

                                                                <span class="terra-profile-role d-block">{{ $roleLabel }}</span>

                                                            </span>

                                                        <i class="bi bi-chevron-down small text-secondary"></i>

                                                    </button>

                                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow p-2 mt-2" style="min-width: 230px; border-radius: 12px;">

                                                        <li class="px-3 py-2">

                                                                <div class="fw-bold">{{ $userName }}</div>

                                                                @if ($userEmail)

                                                                <div class="small text-muted text-break">{{ $userEmail }}</div>

                                                                @endif

                                                                <div class="small text-muted mt-1">{{ $roleLabel }}</div>

                                                            </li>

                                                        <li>

                                <hr class="dropdown-divider">

                            </li>

                                                        @if (\Illuminate\Support\Facades\Route::has('profile.edit'))

                                                        <li>

                                                                <a class="dropdown-item rounded-2 py-2" href="{{ route('profile.edit') }}">

                                                                        <i class="bi bi-person-circle me-2"></i> My Profile

                                                                    </a>

                                                            </li>

                                                        @elseif (\Illuminate\Support\Facades\Route::has('tenant-portal.profile') && $role === 'tenant')

                                                        <li>

                                                                <a class="dropdown-item rounded-2 py-2" href="{{ route('tenant-portal.profile') }}">

                                                                        <i class="bi bi-person-circle me-2"></i> My Profile

                                                                    </a>

                                                            </li>

                                                        @endif

                                                        @if (\Illuminate\Support\Facades\Route::has('support.index'))

                                                        <li>

                                                                <a class="dropdown-item rounded-2 py-2" href="{{ route('support.index') }}">

                                                                        <i class="bi bi-headset me-2"></i> Help & Support

                                                                    </a>

                                                            </li>

                                                        @endif

                                                        @if (\Illuminate\Support\Facades\Route::has('logout'))

                                                        <li>

                                <hr class="dropdown-divider">

                            </li>

                                                        <li>

                                                                <form method="POST" action="{{ route('logout') }}">

                                                                        @csrf

                                                                        <button type="submit" class="dropdown-item rounded-2 py-2 text-danger">

                                                                                <i class="bi bi-box-arrow-right me-2"></i> Sign Out

                                                                            </button>

                                                                    </form>

                                                            </li>

                                                        @endif

                                                    </ul>

                                            </div>

                                    </div>

                            </header>

                        <div class="terra-content">

                                @if (session('success'))

                                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">

                                        <i class="bi bi-check-circle me-2"></i>

                                        {{ session('success') }}

                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

                                    </div>

                                @endif

                                @if (session('error'))

                                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">

                                        <i class="bi bi-exclamation-circle me-2"></i>

                                        {{ session('error') }}

                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

                                    </div>

                                @endif

                                @if (session('warning'))

                                <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm" role="alert">

                                        <i class="bi bi-exclamation-triangle me-2"></i>

                                        {{ session('warning') }}

                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

                                    </div>

                                @endif

                                @if ($errors->any())

                                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">

                                        <div class="fw-bold mb-1">

                                                <i class="bi bi-exclamation-circle me-2"></i>

                                                Please correct the following errors:

                                            </div>

                                        <ul class="mb-0 ps-4">

                                                @foreach ($errors->all() as $error)

                                                <li>{{ $error }}</li>

                                                @endforeach

                                            </ul>

                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

                                    </div>

                                @endif

                                @yield('content')

                            </div>

                        <footer class="terra-footer d-flex flex-wrap justify-content-between gap-2">

                                <span>&copy; {{ date('Y') }} Terra Smart Rental Management</span>

                                <span>Smarter property management. Better rental experiences.</span>

                            </footer>

                    </main>

            </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>

        (() => {

            const toggle = document.getElementById('terraSidebarToggle');

            const backdrop = document.getElementById('terraSidebarBackdrop');

            const closeSidebar = () => {

                document.body.classList.remove('terra-sidebar-open');

                if (toggle) toggle.setAttribute('aria-expanded', 'false');

            };

            if (toggle) {

                toggle.addEventListener('click', () => {

                    const isOpen = document.body.classList.toggle('terra-sidebar-open');

                    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

                });

            }

            if (backdrop) {

                backdrop.addEventListener('click', closeSidebar);

            }

            document.querySelectorAll('.terra-nav-link').forEach((link) => {

                link.addEventListener('click', () => {

                    if (window.innerWidth < 992) closeSidebar();

                });

            });

            window.addEventListener('resize', () => {

                if (window.innerWidth >= 992) closeSidebar();

            });

        })();

    </script>

        @stack('scripts')

</body>

</html>