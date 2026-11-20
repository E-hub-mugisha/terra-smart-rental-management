<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') | Terra Smart Rental Management</title>
    <meta name="description" content="@yield('meta_description', 'Manage rental properties, tenants, leases, payments and maintenance with Terra Smart Rental Management.')">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.fonts')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --terra-orange: #D05208;
            --terra-orange-dark: #AC4205;
            --terra-orange-light: #FFF0E6;
            --terra-navy: #19265d;
            --terra-navy-dark: #111b45;
            --terra-navy-deep: #0d1538;
            --terra-bg: #F4F6FB;
            --terra-surface: #FFFFFF;
            --terra-text: #20283A;
            --terra-muted: #737D91;
            --terra-border: #E5E9F1;
            --terra-sidebar-width: 268px;
            --terra-sidebar-collapsed: 76px;
            --terra-topbar-height: 68px;
            --terra-radius: 14px;
            --terra-shadow: 0 8px 28px rgba(25, 38, 93, .06);
        }

        * { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            font-family: var(--terra-font);
            color: var(--terra-text);
            background: var(--terra-bg);
            font-size: 15px;
            -webkit-font-smoothing: antialiased;
        }

        a { text-decoration: none; }

        /* ---------- Accessibility ---------- */
        .terra-skip-link {
            position: fixed; z-index: 3000; top: -60px; left: 16px;
            padding: 10px 16px; border-radius: 0 0 10px 10px;
            background: var(--terra-orange); color: #fff; font-weight: 700; font-size: 12.5px;
            transition: top .2s ease;
        }
        .terra-skip-link:focus { top: 0; color: #fff; }

        :focus-visible { outline: 2px solid var(--terra-orange); outline-offset: 2px; }

        /* ---------- Navigation progress bar ---------- */
        .terra-progress {
            position: fixed; z-index: 3000; top: 0; left: 0; height: 3px; width: 0;
            background: linear-gradient(90deg, var(--terra-orange), #F28B4B);
            opacity: 0; pointer-events: none; transition: width .4s ease, opacity .25s ease;
        }
        .terra-progress.is-active { opacity: 1; width: 78%; }

        /* ---------- Offline banner ---------- */
        .terra-offline {
            display: none; position: fixed; z-index: 2500; left: 50%; bottom: 18px;
            transform: translateX(-50%); padding: 10px 16px; border-radius: 10px;
            background: #1F2937; color: #fff; font-size: 12.5px; font-weight: 600;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .25);
        }
        .terra-offline.is-visible { display: flex; align-items: center; gap: 8px; }

        .terra-app { min-height: 100vh; }

        /* ---------- Sidebar ---------- */
        .terra-sidebar {
            position: fixed; z-index: 1040; top: 0; bottom: 0; left: 0;
            width: var(--terra-sidebar-width);
            display: flex; flex-direction: column;
            background: linear-gradient(180deg, var(--terra-navy) 0%, var(--terra-navy-deep) 100%);
            color: #C9D0E6;
            transition: transform .25s ease, width .22s ease;
        }

        .terra-brand {
            min-height: var(--terra-topbar-height);
            display: flex; align-items: center; gap: 12px;
            padding: 14px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
            color: #fff;
        }
        .terra-brand:hover { color: #fff; }

        .terra-brand-mark {
            width: 40px; height: 40px; display: inline-flex; flex-shrink: 0;
            align-items: center; justify-content: center; border-radius: 12px;
            background: var(--terra-orange); color: #fff; font-size: 20px;
            box-shadow: 0 6px 16px rgba(208, 82, 8, .35);
        }
        .terra-brand-name { font-size: 17px; line-height: 1.15; font-weight: 800; letter-spacing: -.4px; }
        .terra-brand-subtitle {
            margin-top: 3px; color: #8E99BD; font-size: 11px; font-weight: 700;
            letter-spacing: .8px; text-transform: uppercase;
        }

        .terra-sidebar-content { flex: 1; overflow-y: auto; padding: 14px 12px 20px; scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.18) transparent; }
        .terra-sidebar-content::-webkit-scrollbar { width: 6px; }
        .terra-sidebar-content::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, .15); border-radius: 6px; }

        .terra-side-search {
            display: flex; align-items: center; gap: 9px; width: 100%;
            margin-bottom: 18px; padding: 9px 12px;
            border: 1px solid rgba(255, 255, 255, .10); border-radius: 10px;
            background: rgba(255, 255, 255, .05); color: #9AA5C7;
            font-size: 12.5px; text-align: left; transition: background .18s ease, border-color .18s ease;
        }
        .terra-side-search:hover { background: rgba(255, 255, 255, .09); border-color: rgba(255, 255, 255, .18); color: #fff; }
        .terra-side-search kbd {
            margin-left: auto; padding: 1px 6px; border-radius: 5px;
            background: rgba(255, 255, 255, .10); color: #B7C0DC; font-size: 11px; font-family: inherit;
        }

        .terra-nav-group { margin-bottom: 20px; }
        .terra-nav-heading {
            padding: 0 12px; margin: 0 0 8px; color: #7C88AE;
            font-size: 11px; font-weight: 800; letter-spacing: 1.1px; text-transform: uppercase;
            white-space: nowrap;
        }
        .terra-nav { display: flex; flex-direction: column; gap: 3px; }

        .terra-nav-link {
            position: relative; display: flex; align-items: center; gap: 12px;
            min-height: 41px; padding: 9px 12px; border-radius: 10px;
            color: #B9C2DD; font-size: 14px; font-weight: 600;
            transition: background .16s ease, color .16s ease;
        }
        .terra-nav-link i { width: 19px; flex-shrink: 0; font-size: 17px; text-align: center; }
        .terra-nav-link:hover { background: rgba(255, 255, 255, .07); color: #fff; }
        .terra-nav-link.active { background: var(--terra-orange); color: #fff; font-weight: 700; box-shadow: 0 6px 16px rgba(208, 82, 8, .28); }

        .terra-sidebar-user {
            display: flex; align-items: center; gap: 10px; padding: 14px 16px;
            border-top: 1px solid rgba(255, 255, 255, .08);
        }
        .terra-sidebar-user .terra-avatar { background: rgba(255, 255, 255, .12); }
        .terra-sidebar-user-name { color: #fff; font-size: 12.5px; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 130px; }
        .terra-sidebar-user-role { color: #8E99BD; font-size: 11.5px; margin-top: 2px; }
        .terra-sidebar-logout {
            margin-left: auto; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;
            border: 0; border-radius: 9px; background: rgba(255, 255, 255, .07); color: #C9D0E6; font-size: 15px;
        }
        .terra-sidebar-logout:hover { background: rgba(220, 53, 69, .85); color: #fff; }

        /* Collapsed (desktop) */
        @media (min-width: 992px) {
            body.terra-collapsed .terra-sidebar { width: var(--terra-sidebar-collapsed); }
            body.terra-collapsed .terra-main { margin-left: var(--terra-sidebar-collapsed); }
            body.terra-collapsed .terra-brand { justify-content: center; padding: 14px 0; }
            body.terra-collapsed .terra-brand > span:last-child,
            body.terra-collapsed .terra-nav-link span,
            body.terra-collapsed .terra-nav-heading,
            body.terra-collapsed .terra-side-search span,
            body.terra-collapsed .terra-side-search kbd,
            body.terra-collapsed .terra-sidebar-user > div:not(.terra-avatar),
            body.terra-collapsed .terra-sidebar-user form { display: none; }
            body.terra-collapsed .terra-nav-link { justify-content: center; padding: 10px 0; }
            body.terra-collapsed .terra-side-search { justify-content: center; padding: 10px 0; }
            body.terra-collapsed .terra-sidebar-user { justify-content: center; padding: 14px 0; }
            body.terra-collapsed .terra-nav-group { margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255, 255, 255, .06); }
        }

        /* ---------- Main + topbar ---------- */
        .terra-main { min-height: 100vh; margin-left: var(--terra-sidebar-width); transition: margin-left .22s ease; display: flex; flex-direction: column; }

        .terra-topbar {
            position: sticky; z-index: 1020; top: 0; min-height: var(--terra-topbar-height);
            display: flex; align-items: center; justify-content: space-between; gap: 18px;
            padding: 10px 28px; background: rgba(255, 255, 255, .92);
            border-bottom: 1px solid var(--terra-border); backdrop-filter: blur(12px);
        }
        .terra-topbar-left, .terra-topbar-right { display: flex; align-items: center; gap: 12px; }
        .terra-page-context { min-width: 0; }
        .terra-crumbs { display: flex; align-items: center; gap: 7px; color: var(--terra-muted); font-size: 12px; margin-bottom: 3px; }
        .terra-crumbs a { color: var(--terra-muted); }
        .terra-crumbs a:hover { color: var(--terra-orange); }
        .terra-crumbs i { font-size: 8px; color: #AAB1BF; }
        .terra-page-context h1 { margin: 0; color: var(--terra-navy); font-size: 17px; font-weight: 800; letter-spacing: -.3px; }

        .terra-icon-button {
            position: relative; width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center;
            border: 1px solid var(--terra-border); border-radius: 11px; background: #fff;
            color: var(--terra-navy); font-size: 17px; transition: all .16s ease;
        }
        .terra-icon-button:hover, .terra-icon-button[aria-expanded="true"] { border-color: #F0C3A7; color: var(--terra-orange); background: #FFF8F3; }
        .terra-badge-dot {
            position: absolute; top: -5px; right: -5px; min-width: 18px; height: 18px; padding: 0 5px;
            display: inline-flex; align-items: center; justify-content: center;
            border: 2px solid #fff; border-radius: 10px; background: var(--terra-orange);
            color: #fff; font-size: 11px; font-weight: 800;
        }

        .terra-top-search {
            display: flex; align-items: center; gap: 10px; min-width: 250px; height: 40px; padding: 0 12px;
            border: 1px solid var(--terra-border); border-radius: 11px; background: #F8F9FC;
            color: var(--terra-muted); font-size: 12.5px; transition: all .16s ease;
        }
        .terra-top-search:hover { border-color: #F0C3A7; background: #fff; color: var(--terra-navy); }
        .terra-top-search kbd {
            margin-left: auto; padding: 1px 6px; border-radius: 5px; background: #EEF1F7;
            color: #6B7589; font-size: 11px; font-family: inherit;
        }

        .terra-primary-button {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            min-height: 40px; padding: 9px 14px; border: 0; border-radius: 10px;
            background: var(--terra-orange); color: #fff; font-size: 12.5px; font-weight: 700;
        }
        .terra-primary-button:hover { background: var(--terra-orange-dark); color: #fff; }

        .terra-profile-button { display: flex; align-items: center; gap: 10px; padding: 3px; border: 0; background: transparent; text-align: left; }
        .terra-avatar {
            width: 38px; height: 38px; display: inline-flex; flex-shrink: 0; align-items: center; justify-content: center;
            border-radius: 12px; background: var(--terra-navy); color: #fff; font-size: 12.5px; font-weight: 800;
        }
        .terra-profile-name { max-width: 150px; overflow: hidden; color: var(--terra-navy); font-size: 12.5px; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
        .terra-profile-role { margin-top: 3px; color: var(--terra-muted); font-size: 12px; }

        .terra-menu { border: 1px solid var(--terra-border) !important; border-radius: 12px !important; box-shadow: 0 14px 36px rgba(25, 38, 93, .14) !important; padding: 6px !important; }
        .terra-menu .dropdown-item { border-radius: 8px; padding: 8px 12px; font-size: 13px; font-weight: 600; }
        .terra-menu .dropdown-item:hover { background: #F5F7FB; color: var(--terra-navy); }

        .terra-notify-list { width: 340px; max-width: 92vw; padding: 0 !important; overflow: hidden; }
        .terra-notify-head { display: flex; justify-content: space-between; align-items: center; padding: 13px 16px; border-bottom: 1px solid var(--terra-border); font-weight: 800; font-size: 14px; color: var(--terra-navy); }
        .terra-notify-item { display: flex; gap: 11px; padding: 12px 16px; border-bottom: 1px solid #EFF1F6; }
        .terra-notify-item:last-child { border-bottom: 0; }
        .terra-notify-icon { width: 34px; height: 34px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; border-radius: 10px; background: var(--terra-orange-light); color: var(--terra-orange); }
        .terra-notify-text { font-size: 12.5px; line-height: 1.5; color: #39455C; }
        .terra-notify-time { margin-top: 2px; color: var(--terra-muted); font-size: 11.5px; }
        .terra-notify-empty { padding: 28px 16px; text-align: center; color: var(--terra-muted); font-size: 12.5px; }

        /* ---------- Content ---------- */
        .terra-content { flex: 1; padding: 26px 28px; }
        .terra-content .card { border: 1px solid var(--terra-border); border-radius: var(--terra-radius); box-shadow: var(--terra-shadow); }
        .terra-content .btn-primary { border-color: var(--terra-orange); background: var(--terra-orange); }
        .terra-content .btn-primary:hover { border-color: var(--terra-orange-dark); background: var(--terra-orange-dark); }
        .terra-content .form-control, .terra-content .form-select { min-height: 42px; border-color: var(--terra-border); border-radius: 9px; font-size: 14px; }
        .terra-content .form-control:focus, .terra-content .form-select:focus { border-color: #E8A47D; box-shadow: 0 0 0 .2rem rgba(208, 82, 8, .10); }

        .terra-flash { margin-bottom: 18px; }
        .terra-flash .alert { border-radius: 12px; font-size: 14px; }

        .terra-footer { padding: 16px 28px 22px; color: var(--terra-muted); font-size: 12px; }

        .terra-to-top {
            position: fixed; z-index: 1010; right: 22px; bottom: 22px; width: 42px; height: 42px;
            display: none; align-items: center; justify-content: center; border: 0; border-radius: 12px;
            background: var(--terra-navy); color: #fff; font-size: 16px; box-shadow: 0 10px 24px rgba(25, 38, 93, .28);
        }
        .terra-to-top.is-visible { display: inline-flex; }
        .terra-to-top:hover { background: var(--terra-orange); }

        .terra-sidebar-backdrop { display: none; }
        .terra-empty-nav { padding: 10px 12px; color: #8E99BD; font-size: 12.5px; }

        /* ---------- Command palette ---------- */
        .terra-palette {
            position: fixed; z-index: 2600; inset: 0; display: none;
            align-items: flex-start; justify-content: center; padding: 11vh 16px 16px;
            background: rgba(12, 20, 47, .5); backdrop-filter: blur(3px);
        }
        .terra-palette.is-open { display: flex; }
        .terra-palette-box { width: 100%; max-width: 580px; overflow: hidden; border-radius: 16px; background: #fff; box-shadow: 0 30px 70px rgba(0, 0, 0, .35); }
        .terra-palette-input { display: flex; align-items: center; gap: 12px; padding: 0 18px; border-bottom: 1px solid var(--terra-border); }
        .terra-palette-input i { color: var(--terra-muted); font-size: 16px; }
        .terra-palette-input input { flex: 1; height: 56px; border: 0; outline: 0; font-size: 15px; background: transparent; }
        .terra-palette-list { max-height: 360px; overflow-y: auto; padding: 8px; }
        .terra-palette-group { padding: 10px 12px 6px; color: #98A1B2; font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; }
        .terra-palette-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 10px; color: #2B364D; font-size: 14px; font-weight: 600; }
        .terra-palette-item i { width: 20px; text-align: center; color: #7B879C; font-size: 16px; }
        .terra-palette-item.is-selected, .terra-palette-item:hover { background: var(--terra-orange-light); color: var(--terra-orange-dark); }
        .terra-palette-item.is-selected i { color: var(--terra-orange); }
        .terra-palette-empty { padding: 34px 16px; text-align: center; color: var(--terra-muted); font-size: 14px; }
        .terra-palette-foot { display: flex; gap: 16px; padding: 10px 18px; border-top: 1px solid var(--terra-border); background: #FAFBFD; color: var(--terra-muted); font-size: 12px; }
        .terra-palette-foot kbd { padding: 1px 6px; border-radius: 5px; background: #EEF1F7; color: #5B6880; font-size: 11px; font-family: inherit; }

        /* ---------- Responsive ---------- */
        @media (max-width: 1199.98px) { .terra-top-search { min-width: 0; } .terra-top-search span, .terra-top-search kbd { display: none; } .terra-top-search { width: 40px; justify-content: center; padding: 0; } }

        @media (max-width: 991.98px) {
            .terra-sidebar { transform: translateX(-100%); width: var(--terra-sidebar-width) !important; }
            body.terra-sidebar-open .terra-sidebar { transform: translateX(0); box-shadow: 0 0 60px rgba(0, 0, 0, .4); }
            .terra-sidebar-backdrop { position: fixed; z-index: 1030; inset: 0; background: rgba(12, 20, 47, .5); }
            body.terra-sidebar-open .terra-sidebar-backdrop { display: block; }
            .terra-main { margin-left: 0 !important; }
            .terra-topbar { padding: 10px 18px; }
            .terra-content { padding: 20px 16px; }
            .terra-footer { padding: 16px; }
        }

        @media (max-width: 575.98px) {
            .terra-topbar { min-height: 62px; gap: 8px; padding: 10px 12px; }
            .terra-page-context h1 { font-size: 15px; }
            .terra-crumbs, .terra-profile-copy, .terra-quick-action-label, .terra-profile-button .bi-chevron-down { display: none; }
            .terra-topbar-left, .terra-topbar-right { gap: 8px; }
            .terra-primary-button { width: 40px; min-width: 40px; padding: 8px; }
            .terra-content { padding: 16px 12px; }
        }

        /* ---------- Print (invoices, leases, reports) ---------- */
        @media print {
            .terra-sidebar, .terra-topbar, .terra-footer, .terra-to-top, .terra-sidebar-backdrop,
            .terra-offline, .terra-progress, .terra-palette, .terra-skip-link { display: none !important; }
            .terra-main { margin-left: 0 !important; }
            .terra-content { padding: 0 !important; }
            body { background: #fff; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; scroll-behavior: auto !important; }
        }
    </style>

    @stack('styles')
</head>

<body>

@php
    $currentUser = auth()->user();
    $userName    = $currentUser?->name ?? 'Guest';
    $userEmail   = $currentUser?->email ?? '';
    $rawRole     = strtolower(trim((string) ($currentUser?->role ?? 'user')));

    $roleAliases = [
        'super_admin'     => 'admin',
        'terra_admin'     => 'admin',
        'owner'           => 'property_owner',
        'landlord'        => 'property_owner',
        'manager'         => 'property_manager',
        'agent'           => 'real_estate_agent',
        'finance'         => 'accountant',
        'finance_officer' => 'accountant',
        'technician'      => 'service_provider',
    ];
    $role = $roleAliases[$rawRole] ?? $rawRole;

    $roleLabels = [
        'admin'             => 'Administrator',
        'property_owner'    => 'Property Owner',
        'property_manager'  => 'Property Manager',
        'tenant'            => 'Tenant',
        'real_estate_agent' => 'Real Estate Agent',
        'service_provider'  => 'Service Provider',
        'accountant'        => 'Accountant',
        'field_staff'       => 'Field Staff',
        'user'              => 'User',
    ];
    $roleLabel = $roleLabels[$role] ?? ucwords(str_replace('_', ' ', $role));

    // First registered route name from a list.
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
        ->filter()->take(2)
        ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
        ->implode('') ?: 'G';

    /* Builds a nav item whose routes exist under several route prefixes. */
    $multi = function (string $label, string $icon, array $prefixes, array $bases, string $action = 'index') {
        $routes = [];
        $active = [];
        foreach ($prefixes as $prefix) {
            foreach ($bases as $base) {
                $routes[] = "{$prefix}.{$base}.{$action}";
                $active[] = "{$prefix}.{$base}.*";
            }
        }
        return ['label' => $label, 'icon' => $icon, 'routes' => $routes, 'active' => $active];
    };

    $dashboardCandidates = match ($role) {
        'admin'            => ['admin.dashboard', 'dashboard'],
        'property_owner'   => ['property-manager.dashboard', 'property-management.dashboard', 'property-owner.dashboard', 'dashboard'],
        'property_manager' => ['property-manager.dashboard', 'manager.dashboard', 'dashboard'],
        'tenant'           => ['tenant-portal.dashboard', 'dashboard'],
        'accountant'       => ['accountant.dashboard', 'finance.dashboard', 'dashboard'],
        'real_estate_agent'=> ['real-estate-agent.dashboard', 'agent.dashboard', 'dashboard'],
        'service_provider' => ['service-provider.dashboard', 'dashboard'],
        'field_staff'      => ['field-staff.dashboard'],
        default            => ['dashboard', 'rentals.index'],
    };
    $dashboardRoute = $resolveTerraRoute($dashboardCandidates);

    $profileItem = ['label' => 'My Profile', 'icon' => 'bi-person-circle', 'routes' => ['profile.edit'], 'active' => ['profile.*']];

    /* ---------------- Navigation per role ---------------- */
    $navigation = [];

    if ($role === 'admin') {
        $navigation = [
            ['heading' => 'Overview', 'items' => [
                ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'routes' => ['admin.dashboard', 'dashboard'], 'active' => ['admin.dashboard']],
            ]],
            ['heading' => 'People & Access', 'items' => [
                ['label' => 'All Users', 'icon' => 'bi-people', 'routes' => ['admin.users.index'], 'active' => ['admin.users.*']],
                ['label' => 'Property Owners', 'icon' => 'bi-person-badge', 'routes' => ['admin.owners.index', 'admin.property-owners.index'], 'active' => ['admin.owners.*', 'admin.property-owners.*']],
                ['label' => 'Property Managers', 'icon' => 'bi-person-gear', 'routes' => ['admin.managers.index', 'admin.property-managers.index'], 'active' => ['admin.managers.*', 'admin.property-managers.*']],
                ['label' => 'Tenants', 'icon' => 'bi-person-vcard', 'routes' => ['admin.tenants.index'], 'active' => ['admin.tenants.*']],
                ['label' => 'Field Staff', 'icon' => 'bi-person-walking', 'routes' => ['admin.field-staff.index'], 'active' => ['admin.field-staff.*']],
                ['label' => 'Audit Log', 'icon' => 'bi-shield-check', 'routes' => ['admin.audit-logs.index'], 'active' => ['admin.audit-logs.*']],
            ]],
            ['heading' => 'Subscribers & Billing', 'items' => [
                ['label' => 'Subscribers', 'icon' => 'bi-person-check', 'routes' => ['admin.subscribers.index'], 'active' => ['admin.subscribers.*']],
                ['label' => 'Subscription Contracts', 'icon' => 'bi-file-earmark-check', 'routes' => ['admin.subscriptions.contracts.index'], 'active' => ['admin.subscriptions.contracts.*']],
                ['label' => 'Outstanding & Overdue', 'icon' => 'bi-hourglass-split', 'routes' => ['admin.subscriptions.invoices'], 'active' => ['admin.subscriptions.invoices*']],
                ['label' => 'Transactions', 'icon' => 'bi-arrow-left-right', 'routes' => ['admin.subscriptions.payments'], 'active' => ['admin.subscriptions.payments*']],
                ['label' => 'Plans', 'icon' => 'bi-stack', 'routes' => ['admin.subscriptions.plans'], 'active' => ['admin.subscriptions.plans*']],
            ]],
            ['heading' => 'Property Operations', 'items' => [
                ['label' => 'Managed Properties', 'icon' => 'bi-buildings', 'routes' => ['admin.managed-properties.index', 'admin.properties.index'], 'active' => ['admin.managed-properties.*', 'admin.properties.*']],
                ['label' => 'Buildings & Floors', 'icon' => 'bi-building', 'routes' => ['admin.buildings.index', 'admin.floors.index'], 'active' => ['admin.buildings.*', 'admin.floors.*']],
                ['label' => 'Units', 'icon' => 'bi-door-open', 'routes' => ['admin.units.index'], 'active' => ['admin.units.*']],
                ['label' => 'Rental Listings', 'icon' => 'bi-house', 'routes' => ['rentals.index', 'admin.rentals.index', 'admin.rental-listings.index'], 'active' => ['rentals.*', 'admin.rentals.*', 'admin.rental-listings.*']],
                ['label' => 'Applications', 'icon' => 'bi-file-earmark-person', 'routes' => ['admin.tenant-applications.index', 'admin.applications.index'], 'active' => ['admin.tenant-applications.*', 'admin.applications.*']],
                ['label' => 'Leases', 'icon' => 'bi-file-earmark-text', 'routes' => ['admin.leases.index'], 'active' => ['admin.leases.*']],
            ]],
            ['heading' => 'Finance', 'items' => [
                ['label' => 'Rent Payments', 'icon' => 'bi-cash-stack', 'routes' => ['admin.payments.index', 'admin.rent-payments.index'], 'active' => ['admin.payments.*', 'admin.rent-payments.*']],
                ['label' => 'Rent Invoices', 'icon' => 'bi-receipt', 'routes' => ['admin.rent-invoices.index', 'admin.invoices.index'], 'active' => ['admin.rent-invoices.*', 'admin.invoices.*']],
                ['label' => 'Property Expenses', 'icon' => 'bi-wallet2', 'routes' => ['admin.property-expenses.index', 'admin.expenses.index'], 'active' => ['admin.property-expenses.*', 'admin.expenses.*']],
                ['label' => 'Financial Reports', 'icon' => 'bi-graph-up-arrow', 'routes' => ['admin.financial-reports.index', 'admin.reports.index'], 'active' => ['admin.financial-reports.*', 'admin.reports.*']],
            ]],
            ['heading' => 'Service & Support', 'items' => [
                ['label' => 'Maintenance', 'icon' => 'bi-tools', 'routes' => ['admin.maintenance-requests.index', 'admin.maintenance.index'], 'active' => ['admin.maintenance-requests.*', 'admin.maintenance.*']],
                ['label' => 'Support Tickets', 'icon' => 'bi-headset', 'routes' => ['admin.support.index', 'support.index'], 'active' => ['admin.support.*', 'support.*']],
            ]],
            ['heading' => 'Account', 'items' => [$profileItem]],
        ];
    } elseif ($role === 'property_owner') {
        $op = ['property-manager', 'property-management', 'property-owner'];
        $navigation = [
            ['heading' => 'Workspace', 'items' => [
                ['label' => 'Owner Dashboard', 'icon' => 'bi-grid-1x2', 'routes' => ['property-manager.dashboard', 'property-management.dashboard', 'property-owner.dashboard', 'dashboard'], 'active' => ['property-manager.dashboard', 'property-management.dashboard', 'property-owner.dashboard']],
            ]],
            ['heading' => 'My Properties', 'items' => [
                $multi('My Properties', 'bi-buildings', $op, ['properties']),
            ]],
            ['heading' => 'Tenancy', 'items' => [
                $multi('Tenants', 'bi-people', $op, ['tenants']),
                $multi('Applications', 'bi-file-earmark-person', $op, ['applications']),
                $multi('Leases', 'bi-file-earmark-text', $op, ['leases']),
            ]],
            ['heading' => 'Finance', 'items' => [
                $multi('Rent Invoices', 'bi-receipt', $op, ['invoices']),
                $multi('Rent Payments', 'bi-cash-stack', $op, ['payments']),
                $multi('Property Expenses', 'bi-wallet2', $op, ['expenses']),
                $multi('Financial Reports', 'bi-graph-up-arrow', $op, ['reports']),
            ]],
            ['heading' => 'Property Services', 'items' => [
                $multi('Maintenance Requests', 'bi-tools', $op, ['maintenance-requests', 'maintenance']),
            ]],
            ['heading' => 'Account', 'items' => [
                ['label' => 'My Subscription', 'icon' => 'bi-patch-check', 'routes' => ['subscription.show'], 'active' => ['subscription.*']],
                $profileItem,
            ]],
        ];
    } elseif ($role === 'property_manager') {
        $mp = ['property-manager', 'manager'];
        $navigation = [
            ['heading' => 'Operations', 'items' => [
                ['label' => 'Operations Dashboard', 'icon' => 'bi-grid-1x2', 'routes' => ['property-manager.dashboard', 'manager.dashboard', 'dashboard'], 'active' => ['property-manager.dashboard', 'manager.dashboard']],
                $multi('Assigned Properties', 'bi-buildings', $mp, ['properties', 'managed-properties']),
                $multi('Buildings & Floors', 'bi-building', $mp, ['buildings', 'floors']),
                $multi('Units', 'bi-door-open', $mp, ['units']),
            ]],
            ['heading' => 'Tenancy', 'items' => [
                $multi('Tenants', 'bi-people', $mp, ['tenants']),
                $multi('Applications', 'bi-file-earmark-person', $mp, ['tenant-applications', 'applications']),
                $multi('Leases', 'bi-file-earmark-text', $mp, ['leases']),
            ]],
            ['heading' => 'Rent & Reporting', 'items' => [
                $multi('Rent Invoices', 'bi-receipt', $mp, ['invoices']),
                $multi('Rent Payments', 'bi-cash-stack', $mp, ['payments']),
                $multi('Property Expenses', 'bi-wallet2', $mp, ['expenses']),
                $multi('Operational Reports', 'bi-graph-up-arrow', $mp, ['reports']),
            ]],
            ['heading' => 'Service & Support', 'items' => [
                $multi('Maintenance', 'bi-tools', $mp, ['maintenance-requests', 'maintenance']),
                ['label' => 'My Subscription', 'icon' => 'bi-patch-check', 'routes' => ['subscription.show'], 'active' => ['subscription.*']],
                $profileItem,
            ]],
        ];
    } elseif ($role === 'tenant') {
        $navigation = [
            ['heading' => 'My Home', 'items' => [
                ['label' => 'Tenant Dashboard', 'icon' => 'bi-grid-1x2', 'routes' => ['tenant-portal.dashboard', 'dashboard'], 'active' => ['tenant-portal.dashboard']],
                ['label' => 'My Rental', 'icon' => 'bi-house-door', 'routes' => ['tenant-portal.rentals.index'], 'active' => ['tenant-portal.rentals.*']],
                ['label' => 'Find Rentals', 'icon' => 'bi-search-heart', 'routes' => ['rentals.index'], 'active' => ['rentals.*']],
                ['label' => 'My Applications', 'icon' => 'bi-file-earmark-person', 'routes' => ['tenant-portal.applications.index'], 'active' => ['tenant-portal.applications.*']],
                ['label' => 'My Lease', 'icon' => 'bi-file-earmark-text', 'routes' => ['tenant-portal.leases.index', 'tenant-portal.lease'], 'active' => ['tenant-portal.leases.*', 'tenant-portal.lease']],
            ]],
            ['heading' => 'Rent & Payments', 'items' => [
                ['label' => 'Rent Invoices', 'icon' => 'bi-receipt', 'routes' => ['tenant-portal.rent-invoices.index', 'tenant-portal.invoices.index'], 'active' => ['tenant-portal.rent-invoices.*', 'tenant-portal.invoices.*']],
                ['label' => 'Payments', 'icon' => 'bi-cash-stack', 'routes' => ['tenant-portal.payments.index'], 'active' => ['tenant-portal.payments.*']],
            ]],
            ['heading' => 'Help & Account', 'items' => [
                ['label' => 'Maintenance Requests', 'icon' => 'bi-tools', 'routes' => ['tenant-portal.maintenance-requests.index', 'tenant-portal.maintenance.index'], 'active' => ['tenant-portal.maintenance-requests.*', 'tenant-portal.maintenance.*']],
                ['label' => 'Profile', 'icon' => 'bi-person-circle', 'routes' => ['tenant-portal.profile.index', 'profile.index'], 'active' => ['tenant-portal.profile.*', 'profile.*']],
                ['label' => 'Support', 'icon' => 'bi-headset', 'routes' => ['support.index'], 'active' => ['support.*']],
            ]],
        ];
    } elseif ($role === 'accountant') {
        $navigation = [
            ['heading' => 'Finance Workspace', 'items' => [
                ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'routes' => ['accountant.dashboard', 'finance.dashboard', 'dashboard'], 'active' => ['accountant.dashboard', 'finance.dashboard']],
                ['label' => 'Rent Invoices', 'icon' => 'bi-receipt', 'routes' => ['accountant.rent-invoices.index', 'finance.rent-invoices.index', 'admin.rent-invoices.index'], 'active' => ['accountant.rent-invoices.*', 'finance.rent-invoices.*']],
                ['label' => 'Rent Payments', 'icon' => 'bi-cash-stack', 'routes' => ['accountant.payments.index', 'finance.payments.index', 'admin.payments.index'], 'active' => ['accountant.payments.*', 'finance.payments.*']],
                ['label' => 'Property Expenses', 'icon' => 'bi-wallet2', 'routes' => ['accountant.expenses.index', 'finance.expenses.index', 'admin.property-expenses.index'], 'active' => ['accountant.expenses.*', 'finance.expenses.*']],
                ['label' => 'Financial Reports', 'icon' => 'bi-graph-up-arrow', 'routes' => ['accountant.reports.index', 'finance.reports.index', 'admin.financial-reports.index'], 'active' => ['accountant.reports.*', 'finance.reports.*']],
                $profileItem,
            ]],
        ];
    } elseif ($role === 'real_estate_agent') {
        $navigation = [
            ['heading' => 'Workspace', 'items' => [
                ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'routes' => ['real-estate-agent.dashboard', 'agent.dashboard', 'dashboard'], 'active' => ['real-estate-agent.dashboard', 'agent.dashboard']],
                ['label' => 'Rental Listings', 'icon' => 'bi-house', 'routes' => ['real-estate-agent.properties.index', 'agent.properties.index', 'rentals.index'], 'active' => ['real-estate-agent.properties.*', 'agent.properties.*', 'rentals.*']],
                ['label' => 'Applications', 'icon' => 'bi-file-earmark-person', 'routes' => ['real-estate-agent.applications.index', 'agent.applications.index'], 'active' => ['real-estate-agent.applications.*', 'agent.applications.*']],
                $profileItem,
            ]],
        ];
    } elseif ($role === 'service_provider') {
        $navigation = [
            ['heading' => 'Service Workspace', 'items' => [
                ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'routes' => ['service-provider.dashboard', 'dashboard'], 'active' => ['service-provider.dashboard']],
                ['label' => 'Maintenance Jobs', 'icon' => 'bi-tools', 'routes' => ['service-provider.maintenance.index', 'service-provider.maintenance-requests.index'], 'active' => ['service-provider.maintenance.*', 'service-provider.maintenance-requests.*']],
                $profileItem,
            ]],
        ];
    } elseif ($role === 'field_staff') {
        $navigation = [
            ['heading' => 'Field Registration', 'items' => [
                ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'routes' => ['field-staff.dashboard'], 'active' => ['field-staff.dashboard']],
                ['label' => 'Register Owner', 'icon' => 'bi-person-badge', 'routes' => ['field-staff.owners.create'], 'active' => ['field-staff.owners.*']],
                ['label' => 'Register Manager', 'icon' => 'bi-person-gear', 'routes' => ['field-staff.managers.create'], 'active' => ['field-staff.managers.*']],
                ['label' => 'My Registrations', 'icon' => 'bi-list-check', 'routes' => ['field-staff.registrations'], 'active' => ['field-staff.registrations']],
            ]],
            ['heading' => 'Account', 'items' => [
                ['label' => 'Change Password', 'icon' => 'bi-key', 'routes' => ['field-staff.password'], 'active' => ['field-staff.password*']],
            ]],
        ];
    } else {
        $navigation = [
            ['heading' => 'Explore Terra', 'items' => [
                ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'routes' => ['dashboard'], 'active' => ['dashboard']],
                ['label' => 'Find Rentals', 'icon' => 'bi-house', 'routes' => ['rentals.index'], 'active' => ['rentals.*']],
                $profileItem,
            ]],
        ];
    }

    /* ---------------- Communication (brief §12–13), every role ---------------- */
    if ($currentUser) {
        $commsGroup = ['heading' => 'Communication & Care', 'items' => [
            ['label' => 'Messages', 'icon' => 'bi-chat-dots', 'routes' => ['messages.index'], 'active' => ['messages.*']],
            ['label' => 'Notifications', 'icon' => 'bi-bell', 'routes' => ['notifications.index'], 'active' => ['notifications.*']],
            ['label' => 'Inspections', 'icon' => 'bi-clipboard-check', 'routes' => ['inspections.index'], 'active' => ['inspections.*']],
            ['label' => 'Documents', 'icon' => 'bi-folder2-open', 'routes' => ['documents.index'], 'active' => ['documents.*']],
            ['label' => 'Security & Language', 'icon' => 'bi-shield-lock', 'routes' => ['account.security'], 'active' => ['account.security*']],
        ]];
        if ($role === 'field_staff') {
            // Field staff only talk to Terra; no inspections or documents.
            $commsGroup['items'] = array_values(array_filter($commsGroup['items'], fn ($i) => in_array($i['routes'][0], ['messages.index', 'notifications.index', 'account.security'], true)));
        }
        array_splice($navigation, max(count($navigation) - 1, 1), 0, [$commsGroup]);

        // Operations tools for admins, owners and managers.
        if (in_array($role, ['admin', 'property_owner', 'property_manager'], true)) {
            array_splice($navigation, max(count($navigation) - 1, 1), 0, [['heading' => 'Tools', 'items' => [
                ['label' => 'Reports', 'icon' => 'bi-bar-chart-line', 'routes' => ['reports.index'], 'active' => ['reports.*']],
                ['label' => 'Portfolio Map', 'icon' => 'bi-geo-alt', 'routes' => ['portfolio.map'], 'active' => ['portfolio.map']],
                ['label' => 'Finance', 'icon' => 'bi-calculator', 'routes' => ['finance.dashboard'], 'active' => ['finance.*']],
                ['label' => 'Service Providers', 'icon' => 'bi-person-gear', 'routes' => ['service-providers.index'], 'active' => ['service-providers.*']],
                ['label' => 'Lease Templates', 'icon' => 'bi-file-earmark-ruled', 'routes' => ['lease-templates.index'], 'active' => ['lease-templates.*', 'contracts.*']],
                ['label' => 'Agents & Accountants', 'icon' => 'bi-people', 'routes' => ['property-roles.index'], 'active' => ['property-roles.*']],
            ]]]);
        }
        if ($role === 'real_estate_agent') {
            $navigation[0]['items'][] = ['label' => 'Commissions', 'icon' => 'bi-cash-coin', 'routes' => ['agent.commissions.index'], 'active' => ['agent.commissions.*']];
        }
        if ($role === 'accountant') {
            $navigation[0]['items'][] = ['label' => 'Rent Ledgers & Reports', 'icon' => 'bi-bar-chart-line', 'routes' => ['reports.index'], 'active' => ['reports.*']];
        }
    }

    // Resolve route names and drop links with no registered route.
    foreach ($navigation as $groupIndex => $group) {
        $resolvedItems = [];
        foreach ($group['items'] as $item) {
            $resolvedRoute = $resolveTerraRoute($item['routes'] ?? []);
            if (!$resolvedRoute) {
                continue;
            }
            $item['resolved_route'] = $resolvedRoute;
            $item['label']          = __($item['label']);
            $item['is_active']      = $isTerraRouteActive($item['active'] ?? []);
            $resolvedItems[]        = $item;
        }
        $navigation[$groupIndex]['items'] = $resolvedItems;
        $navigation[$groupIndex]['heading'] = __($group['heading']);
    }
    $navigation = array_values(array_filter($navigation, fn ($group) => !empty($group['items'])));

    /* ---------------- Quick actions ---------------- */
    $quickActionCandidates = match ($role) {
        'admin' => [
            ['label' => 'Add User', 'icon' => 'bi-person-plus', 'routes' => ['admin.users.create']],
            ['label' => 'Register Owner', 'icon' => 'bi-person-badge', 'routes' => ['admin.owners.create']],
            ['label' => 'Add Manager', 'icon' => 'bi-person-gear', 'routes' => ['admin.managers.create']],
            ['label' => 'Add Tenant', 'icon' => 'bi-person-vcard', 'routes' => ['admin.tenants.create']],
            ['label' => 'New Subscription Contract', 'icon' => 'bi-file-earmark-plus', 'routes' => ['admin.subscriptions.contracts.create']],
            ['label' => 'Add Field Staff', 'icon' => 'bi-person-walking', 'routes' => ['admin.field-staff.create']],
            ['label' => 'Add Property', 'icon' => 'bi-building-add', 'routes' => ['admin.properties.create', 'admin.managed-properties.create']],
            ['label' => 'Record Payment', 'icon' => 'bi-cash-stack', 'routes' => ['admin.payments.create']],
            ['label' => 'Add Expense', 'icon' => 'bi-wallet2', 'routes' => ['admin.property-expenses.create']],
            ['label' => 'View Applications', 'icon' => 'bi-file-earmark-person', 'routes' => ['admin.tenant-applications.index', 'admin.applications.index']],
        ],
        'property_owner' => [
            ['label' => 'My Properties', 'icon' => 'bi-buildings', 'routes' => ['property-manager.properties.index', 'property-management.properties.index', 'property-owner.properties.index']],
            ['label' => 'Review Applications', 'icon' => 'bi-file-earmark-person', 'routes' => ['property-manager.applications.index', 'property-management.applications.index', 'property-owner.applications.index']],
            ['label' => 'Record Payment', 'icon' => 'bi-cash-stack', 'routes' => ['property-manager.payments.create', 'property-management.payments.create', 'property-owner.payments.create']],
            ['label' => 'Add Expense', 'icon' => 'bi-wallet2', 'routes' => ['property-manager.expenses.create']],
        ],
        'property_manager' => [
            ['label' => 'Add Tenant', 'icon' => 'bi-person-plus', 'routes' => ['property-manager.tenants.create']],
            ['label' => 'View Applications', 'icon' => 'bi-file-earmark-person', 'routes' => ['property-manager.tenant-applications.index', 'property-manager.applications.index', 'manager.applications.index']],
            ['label' => 'Log Maintenance', 'icon' => 'bi-tools', 'routes' => ['property-manager.maintenance-requests.create', 'property-manager.maintenance-requests.index', 'manager.maintenance.index']],
            ['label' => 'Record Payment', 'icon' => 'bi-cash-stack', 'routes' => ['property-manager.payments.create', 'manager.payments.create']],
            ['label' => 'Add Expense', 'icon' => 'bi-wallet2', 'routes' => ['property-manager.expenses.create']],
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
        'field_staff' => [
            ['label' => 'Register Owner', 'icon' => 'bi-person-badge', 'routes' => ['field-staff.owners.create']],
            ['label' => 'Register Manager', 'icon' => 'bi-person-gear', 'routes' => ['field-staff.managers.create']],
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

    /* ---------------- Page titles ---------------- */
    $pageTitle = trim($__env->yieldContent('page_title'))
        ?: trim($__env->yieldContent('page_heading'))
        ?: trim($__env->yieldContent('title'))
        ?: 'Dashboard';
    $pageSubtitle = trim($__env->yieldContent('page_subtitle'));

    /* ---------------- Command palette data ---------------- */
    $paletteItems = [];
    foreach ($navigation as $group) {
        foreach ($group['items'] as $item) {
            $paletteItems[] = ['label' => $item['label'], 'url' => route($item['resolved_route']), 'icon' => $item['icon'], 'group' => $group['heading']];
        }
    }
    foreach ($quickActions as $action) {
        $paletteItems[] = ['label' => $action['label'], 'url' => route($action['resolved_route']), 'icon' => $action['icon'], 'group' => 'Quick actions'];
    }

    $messageCount = 0;
    if ($currentUser && \Illuminate\Support\Facades\Route::has('messages.index')) {
        try { $messageCount = $currentUser->unreadConversationCount(); } catch (\Throwable $e) { $messageCount = 0; }
    }

    /* ---------------- Notifications (Laravel database notifications) ---------------- */
    $notifications = collect();
    $notificationCount = 0;
    $notificationsEnabled = false;
    if ($currentUser && method_exists($currentUser, 'unreadNotifications')) {
        try {
            $notificationCount    = $currentUser->unreadNotifications()->count();
            $notifications        = $currentUser->unreadNotifications()->latest()->limit(5)->get();
            $notificationsEnabled = true;
        } catch (\Throwable $e) {
            $notificationsEnabled = false; // notifications table not migrated
        }
    }

    $profileUrl = null;
    if (\Illuminate\Support\Facades\Route::has('profile.edit')) {
        $profileUrl = route('profile.edit');
    } elseif ($role === 'tenant') {
        foreach (['tenant-portal.profile.index', 'tenant-portal.profile'] as $candidate) {
            if (\Illuminate\Support\Facades\Route::has($candidate)) {
                $profileUrl = route($candidate);
                break;
            }
        }
    }
@endphp

<a href="#terra-main-content" class="terra-skip-link">Skip to main content</a>
<div class="terra-progress" id="terraProgress"></div>
<div class="terra-offline" id="terraOffline" role="status">
    <i class="bi bi-wifi-off"></i> You are offline. Changes will not be saved until you reconnect.
</div>

<div class="terra-sidebar-backdrop" id="terraSidebarBackdrop"></div>

<div class="terra-app">

    {{-- ================= SIDEBAR ================= --}}
    <aside class="terra-sidebar" id="terraSidebar" aria-label="Main navigation">
        <a href="{{ $dashboardRoute ? route($dashboardRoute) : url('/') }}" class="terra-brand">
            <span class="terra-brand-mark"><i class="bi bi-buildings"></i></span>
            <span>
                <span class="terra-brand-name d-block">Terra</span>
                <span class="terra-brand-subtitle d-block">Smart Rental Management</span>
            </span>
        </a>

        <div class="terra-sidebar-content">
            <button type="button" class="terra-side-search" data-open-palette title="Search pages (Ctrl+K)">
                <i class="bi bi-search"></i>
                <span>{{ __('Quick search') }}</span>
                <kbd>Ctrl K</kbd>
            </button>

            @forelse ($navigation as $group)
                <section class="terra-nav-group">
                    <h2 class="terra-nav-heading">{{ $group['heading'] }}</h2>

                    <nav class="terra-nav" aria-label="{{ $group['heading'] }}">
                        @foreach ($group['items'] as $item)
                            <a href="{{ route($item['resolved_route']) }}"
                               class="terra-nav-link {{ $item['is_active'] ? 'active' : '' }}"
                               title="{{ $item['label'] }}"
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

        <div class="terra-sidebar-user">
            <span class="terra-avatar">{{ $initials }}</span>
            <div class="min-w-0">
                <div class="terra-sidebar-user-name">{{ $userName }}</div>
                <div class="terra-sidebar-user-role">{{ $roleLabel }}</div>
            </div>

            @if (\Illuminate\Support\Facades\Route::has('logout'))
                <form method="POST" action="{{ route('logout') }}" class="ms-auto">
                    @csrf
                    <button type="submit" class="terra-sidebar-logout" title="{{ __('Sign out') }}" aria-label="{{ __('Sign out') }}">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </form>
            @endif
        </div>
    </aside>

    {{-- ================= MAIN ================= --}}
    <main class="terra-main">

        <header class="terra-topbar">
            <div class="terra-topbar-left">
                <button type="button" class="terra-icon-button d-lg-none" id="terraSidebarToggle"
                        aria-label="Open navigation" aria-controls="terraSidebar" aria-expanded="false">
                    <i class="bi bi-list"></i>
                </button>

                <button type="button" class="terra-icon-button d-none d-lg-inline-flex" id="terraCollapseToggle"
                        aria-label="Collapse sidebar" title="Collapse / expand sidebar">
                    <i class="bi bi-layout-sidebar-inset"></i>
                </button>

                <div class="terra-page-context">
                    <div class="terra-crumbs">
                        <a href="{{ $dashboardRoute ? route($dashboardRoute) : url('/') }}">Terra</a>
                        <i class="bi bi-chevron-right"></i>
                        <span>{{ $roleLabel }}</span>
                    </div>
                    <h1>{{ $pageTitle }}</h1>
                </div>
            </div>

            <div class="terra-topbar-right">
                <button type="button" class="terra-top-search" data-open-palette title="Search pages (Ctrl+K)">
                    <i class="bi bi-search"></i>
                    <span>Search pages and actions...</span>
                    <kbd>Ctrl K</kbd>
                </button>

                <div class="d-none d-md-flex">@include('partials.language-switcher', ['compact' => true])</div>

                @if (\Illuminate\Support\Facades\Route::has('messages.index') && $currentUser)
                    <a href="{{ route('messages.index') }}" class="terra-icon-button position-relative" aria-label="Messages{{ $messageCount ? ', ' . $messageCount . ' unread' : '' }}">
                        <i class="bi bi-chat-dots"></i>
                        @if ($messageCount > 0)
                            <span class="terra-badge-dot">{{ $messageCount > 9 ? '9+' : $messageCount }}</span>
                        @endif
                    </a>
                @endif

                @if ($notificationsEnabled)
                    <div class="dropdown">
                        <button type="button" class="terra-icon-button" data-bs-toggle="dropdown"
                                data-bs-popper-config='{"strategy":"fixed"}'
                                aria-expanded="false" aria-label="Notifications">
                            <i class="bi bi-bell"></i>
                            @if ($notificationCount > 0)
                                <span class="terra-badge-dot">{{ $notificationCount > 9 ? '9+' : $notificationCount }}</span>
                            @endif
                        </button>

                        <div class="dropdown-menu dropdown-menu-end terra-menu terra-notify-list">
                            <div class="terra-notify-head">
                                <span>{{ __('Notifications') }}</span>
                                @if ($notificationCount > 0)
                                    <span class="badge rounded-pill text-bg-light border">{{ $notificationCount }} new</span>
                                @endif
                            </div>

                            @forelse ($notifications as $notification)
                                @php
                                    $data = (array) ($notification->data ?? []);
                                    $text = $data['message'] ?? $data['title'] ?? $data['body'] ?? class_basename($notification->type);
                                    $link = \Illuminate\Support\Facades\Route::has('notifications.open')
                                        ? route('notifications.open', $notification->id)
                                        : ($data['url'] ?? $data['link'] ?? null);
                                @endphp
                                <a @if($link) href="{{ $link }}" @endif class="terra-notify-item text-decoration-none">
                                    <span class="terra-notify-icon"><i class="bi {{ $data['icon'] ?? 'bi-bell' }}"></i></span>
                                    <span>
                                        <span class="terra-notify-text d-block">{{ \Illuminate\Support\Str::limit($text, 110) }}</span>
                                        <span class="terra-notify-time d-block">{{ $notification->created_at?->diffForHumans() }}</span>
                                    </span>
                                </a>
                            @empty
                                <div class="terra-notify-empty">
                                    <i class="bi bi-check2-circle fs-4 d-block mb-2"></i>
                                    {{ __('You are all caught up.') }}
                                </div>
                            @endforelse

                            @if (\Illuminate\Support\Facades\Route::has('notifications.index'))
                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top small">
                                    <a href="{{ route('notifications.index') }}" class="fw-semibold text-decoration-none">{{ __('View all') }}</a>
                                    @if ($notificationCount > 0)
                                        <form method="POST" action="{{ route('notifications.read-all') }}" class="m-0">
                                            @csrf
                                            <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none">{{ __('Mark all read') }}</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if (!empty($quickActions))
                    <div class="dropdown">
                        <button type="button" class="terra-primary-button dropdown-toggle"
                                data-bs-toggle="dropdown"
                                data-bs-popper-config='{"strategy":"fixed"}'
                                aria-expanded="false">
                            <i class="bi bi-plus-lg"></i>
                            <span class="terra-quick-action-label">Quick Actions</span>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end terra-menu" style="min-width: 230px;">
                            @foreach ($quickActions as $action)
                                <li>
                                    <a class="dropdown-item" href="{{ route($action['resolved_route']) }}">
                                        <i class="bi {{ $action['icon'] }} me-2 text-secondary"></i>
                                        {{ __($action['label']) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="dropdown">
                    <button type="button" class="terra-profile-button" data-bs-toggle="dropdown"
                            data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                        <span class="terra-avatar">{{ $initials }}</span>
                        <span class="terra-profile-copy">
                            <span class="terra-profile-name d-block">{{ $userName }}</span>
                            <span class="terra-profile-role d-block">{{ $roleLabel }}</span>
                        </span>
                        <i class="bi bi-chevron-down small text-secondary"></i>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end terra-menu mt-2" style="min-width: 240px;">
                        <li class="px-3 py-2">
                            <div class="fw-bold">{{ $userName }}</div>
                            @if ($userEmail)
                                <div class="small text-muted text-break">{{ $userEmail }}</div>
                            @endif
                            <span class="badge rounded-pill mt-2" style="background:var(--terra-orange-light);color:var(--terra-orange-dark);">{{ $roleLabel }}</span>
                        </li>
                        <li><hr class="dropdown-divider"></li>

                        @if ($profileUrl)
                            <li>
                                <a class="dropdown-item" href="{{ $profileUrl }}">
                                    <i class="bi bi-person-circle me-2"></i> My Profile
                                </a>
                            </li>
                        @endif

                        <li>
                            <button type="button" class="dropdown-item" data-open-palette>
                                <i class="bi bi-command me-2"></i> {{ __('Quick search') }}
                            </button>
                        </li>

                        @if (\Illuminate\Support\Facades\Route::has('support.index'))
                            <li>
                                <a class="dropdown-item" href="{{ route('support.index') }}">
                                    <i class="bi bi-headset me-2"></i> Help &amp; Support
                                </a>
                            </li>
                        @endif

                        @if (\Illuminate\Support\Facades\Route::has('logout'))
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i> Sign Out
                                    </button>
                                </form>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </header>

        <div class="terra-content" id="terra-main-content" tabindex="-1">

            <div class="terra-flash">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert" data-autodismiss="6000">
                        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @include('partials.issued-credentials')

                @if (session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('status'))
                    <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm" role="alert" data-autodismiss="6000">
                        <i class="bi bi-info-circle me-2"></i>{{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle me-2"></i>Please correct the following errors:</div>
                        <ul class="mb-0 ps-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
            </div>

            @yield('content')
        </div>

        <footer class="terra-footer d-flex flex-wrap justify-content-between gap-2">
            <span>&copy; {{ date('Y') }} Terra Smart Rental Management</span>
            <span>Smarter property management. Better rental experiences.</span>
        </footer>
    </main>
</div>

<button type="button" class="terra-to-top" id="terraToTop" aria-label="Back to top"><i class="bi bi-arrow-up"></i></button>

{{-- ================= COMMAND PALETTE ================= --}}
<div class="terra-palette" id="terraPalette" role="dialog" aria-modal="true" aria-label="Quick search">
    <div class="terra-palette-box">
        <div class="terra-palette-input">
            <i class="bi bi-search"></i>
            <input type="text" id="terraPaletteInput" placeholder="Jump to a page or action..." autocomplete="off" aria-label="Search pages and actions">
        </div>
        <div class="terra-palette-list" id="terraPaletteList"></div>
        <div class="terra-palette-foot">
            <span><kbd>&uarr;</kbd> <kbd>&darr;</kbd> navigate</span>
            <span><kbd>Enter</kbd> open</span>
            <span><kbd>Esc</kbd> close</span>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
(() => {
    const body = document.body;
    const store = {
        get: (k) => { try { return localStorage.getItem(k); } catch (e) { return null; } },
        set: (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} },
    };

    /* ---------- Mobile sidebar ---------- */
    const toggle = document.getElementById('terraSidebarToggle');
    const backdrop = document.getElementById('terraSidebarBackdrop');
    const closeSidebar = () => {
        body.classList.remove('terra-sidebar-open');
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
    };
    if (toggle) toggle.addEventListener('click', () => {
        const open = body.classList.toggle('terra-sidebar-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    if (backdrop) backdrop.addEventListener('click', closeSidebar);
    document.querySelectorAll('.terra-nav-link').forEach((l) =>
        l.addEventListener('click', () => { if (window.innerWidth < 992) closeSidebar(); }));
    window.addEventListener('resize', () => { if (window.innerWidth >= 992) closeSidebar(); });

    /* ---------- Desktop collapse (remembered) ---------- */
    if (store.get('terra.sidebar') === 'collapsed') body.classList.add('terra-collapsed');
    const collapseBtn = document.getElementById('terraCollapseToggle');
    if (collapseBtn) collapseBtn.addEventListener('click', () => {
        const collapsed = body.classList.toggle('terra-collapsed');
        store.set('terra.sidebar', collapsed ? 'collapsed' : 'expanded');
    });

    /* ---------- Keep the active menu item visible ---------- */
    const activeLink = document.querySelector('.terra-nav-link.active');
    if (activeLink) activeLink.scrollIntoView({ block: 'center' });

    /* ---------- Tooltips ---------- */
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => new bootstrap.Tooltip(el));

    /* ---------- Auto-dismiss success messages ---------- */
    document.querySelectorAll('[data-autodismiss]').forEach((el) => {
        setTimeout(() => {
            if (!document.body.contains(el)) return;
            bootstrap.Alert.getOrCreateInstance(el).close();
        }, parseInt(el.dataset.autodismiss, 10) || 6000);
    });

    /* ---------- Back to top ---------- */
    const toTop = document.getElementById('terraToTop');
    window.addEventListener('scroll', () => {
        toTop.classList.toggle('is-visible', window.scrollY > 400);
    }, { passive: true });
    toTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

    /* ---------- Offline banner ---------- */
    const offline = document.getElementById('terraOffline');
    const syncOnline = () => offline.classList.toggle('is-visible', !navigator.onLine);
    window.addEventListener('online', syncOnline);
    window.addEventListener('offline', syncOnline);
    syncOnline();

    /* ---------- Navigation progress bar ---------- */
    const bar = document.getElementById('terraProgress');
    const startBar = () => bar.classList.add('is-active');
    const stopBar = () => bar.classList.remove('is-active');
    document.addEventListener('click', (e) => {
        const a = e.target.closest('a[href]');
        if (!a || e.defaultPrevented || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
        if (a.target === '_blank' || a.hasAttribute('download') || a.dataset.bsToggle) return;
        const href = a.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('tel:') || href.startsWith('mailto:')) return;
        if (a.origin !== window.location.origin) return;
        startBar();
    });
    window.addEventListener('pageshow', stopBar);

    /* ---------- Prevent double form submits ---------- */
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (e.defaultPrevented || form.dataset.noLock !== undefined) return;
        startBar();
        const btn = e.submitter || form.querySelector('[type="submit"]');
        if (btn && !btn.disabled) {
            // Delay so the submitter value is still sent with the request.
            setTimeout(() => {
                btn.disabled = true;
                btn.dataset.originalHtml = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Please wait...';
            }, 0);
        }
    });
    // Restore buttons if the user returns with the back button.
    window.addEventListener('pageshow', (e) => {
        if (!e.persisted) return;
        document.querySelectorAll('button[type="submit"][disabled]').forEach((b) => {
            b.disabled = false;
            if (b.dataset.originalHtml) b.innerHTML = b.dataset.originalHtml;
        });
    });

    /* ---------- Command palette ---------- */
    const items = @json($paletteItems);
    const palette = document.getElementById('terraPalette');
    const input = document.getElementById('terraPaletteInput');
    const list = document.getElementById('terraPaletteList');
    let results = [];
    let index = 0;

    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const render = () => {
        if (!results.length) {
            list.innerHTML = '<div class="terra-palette-empty"><i class="bi bi-search fs-4 d-block mb-2"></i>No matching pages or actions.</div>';
            return;
        }
        let html = '';
        let lastGroup = null;
        results.forEach((item, i) => {
            if (item.group !== lastGroup) {
                html += '<div class="terra-palette-group">' + esc(item.group) + '</div>';
                lastGroup = item.group;
            }
            html += '<a href="' + esc(item.url) + '" class="terra-palette-item' + (i === index ? ' is-selected' : '') + '" data-i="' + i + '">' +
                '<i class="bi ' + esc(item.icon) + '"></i><span>' + esc(item.label) + '</span></a>';
        });
        list.innerHTML = html;
        const sel = list.querySelector('.is-selected');
        if (sel) sel.scrollIntoView({ block: 'nearest' });
    };

    const filter = () => {
        const q = input.value.trim().toLowerCase();
        results = items.filter((it) => !q || it.label.toLowerCase().includes(q) || it.group.toLowerCase().includes(q));
        index = 0;
        render();
    };

    const openPalette = () => {
        closeSidebar();
        palette.classList.add('is-open');
        input.value = '';
        filter();
        setTimeout(() => input.focus(), 20);
    };
    const closePalette = () => palette.classList.remove('is-open');

    document.querySelectorAll('[data-open-palette]').forEach((b) => b.addEventListener('click', openPalette));
    palette.addEventListener('click', (e) => { if (e.target === palette) closePalette(); });
    input.addEventListener('input', filter);
    list.addEventListener('mousemove', (e) => {
        const a = e.target.closest('.terra-palette-item');
        if (a && parseInt(a.dataset.i, 10) !== index) { index = parseInt(a.dataset.i, 10); render(); }
    });
    list.addEventListener('click', () => startBar());

    document.addEventListener('keydown', (e) => {
        const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName) || document.activeElement.isContentEditable;

        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            palette.classList.contains('is-open') ? closePalette() : openPalette();
            return;
        }
        if (e.key === '/' && !typing && !palette.classList.contains('is-open')) {
            e.preventDefault();
            openPalette();
            return;
        }
        if (!palette.classList.contains('is-open')) return;

        if (e.key === 'Escape') { closePalette(); }
        else if (e.key === 'ArrowDown') { e.preventDefault(); index = Math.min(index + 1, results.length - 1); render(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); index = Math.max(index - 1, 0); render(); }
        else if (e.key === 'Enter' && results[index]) { e.preventDefault(); startBar(); window.location.href = results[index].url; }
    });
})();
</script>

@include('partials.intl-phone')
@stack('scripts')

</body>

</html>