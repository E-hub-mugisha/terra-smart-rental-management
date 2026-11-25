@extends('layouts.app')

@section('title', 'Manager Profile')

@section('page_heading', 'Manager Profile')

@section('page_subtitle', 'Manager information, employment details and property assignments.')

@push('styles')
<style>
    .manager-show {
        --mg-orange: var(--terra-orange, #D05208);
        --mg-orange-dark: #AD4307;
        --mg-orange-soft: #FFF0E7;
        --mg-navy: var(--terra-navy, #19265D);
        --mg-border: var(--terra-border, #E7EAF1);
        --mg-muted: var(--terra-muted, #778197);
        color: #263047;
    }

    /* Heading */
    .manager-show .page-intro { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 18px; margin-bottom: 22px; }
    .manager-show .breadcrumb-line { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; color: var(--mg-muted); font-size: 11px; }
    .manager-show .breadcrumb-line a { color: var(--mg-muted); text-decoration: none; }
    .manager-show .breadcrumb-line a:hover { color: var(--mg-orange); }
    .manager-show .breadcrumb-line i { font-size: 10px; color: #AAB1BF; }
    .manager-show .page-title { margin: 0; color: var(--mg-navy); font-size: clamp(22px, 2vw, 28px); font-weight: 800; letter-spacing: -.7px; }
    .manager-show .page-description { margin: 7px 0 0; color: var(--mg-muted); font-size: 12px; line-height: 1.7; }

    .manager-show .mg-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 40px;
        padding: 9px 14px; border: 1px solid transparent; border-radius: 10px;
        font-size: 12px; font-weight: 700; text-decoration: none; transition: .18s ease; white-space: nowrap;
    }
    .manager-show .mg-btn-primary { background: var(--mg-orange); border-color: var(--mg-orange); color: #fff; }
    .manager-show .mg-btn-primary:hover { background: var(--mg-orange-dark); border-color: var(--mg-orange-dark); color: #fff; transform: translateY(-1px); }
    .manager-show .mg-btn-outline { background: #fff; border-color: var(--mg-border); color: var(--mg-navy); }
    .manager-show .mg-btn-outline:hover, .manager-show .mg-btn-outline[aria-expanded="true"] { border-color: #EFC4A8; background: #FFF8F3; color: var(--mg-orange); }

    /* Dropdown menu (small font) */
    .manager-show .action-menu, .manager-show .row-menu {
        min-width: 210px; padding: 6px; border: 1px solid var(--mg-border);
        border-radius: 12px; box-shadow: 0 10px 30px rgba(25, 38, 93, .12); font-size: 11px;
    }
    .manager-show .action-menu .dropdown-item, .manager-show .row-menu .dropdown-item {
        display: flex; align-items: center; gap: 9px; width: 100%; padding: 7px 10px; border: 0;
        border-radius: 8px; background: transparent; color: #35425B; font-size: 11px; font-weight: 600; text-align: left;
    }
    .manager-show .action-menu .dropdown-item i, .manager-show .row-menu .dropdown-item i { font-size: 13px; color: #7B879C; }
    .manager-show .action-menu .dropdown-item:hover, .manager-show .row-menu .dropdown-item:hover { background: #F3F5FA; color: var(--mg-navy); }
    .manager-show .dropdown-item.item-danger, .manager-show .dropdown-item.item-danger i { color: #B42318; }
    .manager-show .dropdown-item.item-danger:hover { background: #FFF0EF; color: #B42318; }
    .manager-show .dropdown-divider { margin: 5px 4px; border-color: #EDF0F5; }

    /* Hero */
    .manager-show .mg-hero {
        position: relative; overflow: hidden; margin-bottom: 20px; padding: 26px; border-radius: 17px;
        background: linear-gradient(115deg, #19265D 0%, #253B7B 67%, #354C91 100%);
        color: #fff; box-shadow: 0 10px 28px rgba(25, 38, 93, .12);
    }
    .manager-show .mg-hero::after {
        position: absolute; top: -85px; right: -40px; width: 245px; height: 245px;
        border: 38px solid rgba(255, 255, 255, .045); border-radius: 50%; content: ""; pointer-events: none;
    }
    .manager-show .mg-hero-inner { position: relative; z-index: 1; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 22px; }
    .manager-show .mg-avatar {
        display: flex; align-items: center; justify-content: center; width: 72px; height: 72px; flex: 0 0 72px;
        border: 2px solid rgba(255, 255, 255, .3); border-radius: 20px; background: rgba(255, 255, 255, .12);
        color: #fff; font-size: 24px; font-weight: 800; letter-spacing: 1px;
    }
    .manager-show .mg-hero-name { margin: 0; color: #fff; font-size: 21px; font-weight: 800; overflow-wrap: anywhere; }
    .manager-show .mg-hero-meta { margin-top: 7px; color: rgba(255, 255, 255, .78); font-size: 12px; }
    .manager-show .mg-hero-meta strong { color: #fff; }
    .manager-show .mg-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
    .manager-show .mg-chip {
        display: inline-flex; align-items: center; gap: 7px; padding: 6px 10px; border: 1px solid rgba(255, 255, 255, .16);
        border-radius: 9px; background: rgba(255, 255, 255, .09); color: #fff; font-size: 11px; font-weight: 600; max-width: 100%;
    }
    .manager-show .mg-chip button { border: 0; background: transparent; color: rgba(255, 255, 255, .75); padding: 0 0 0 4px; font-size: 12px; }
    .manager-show .mg-chip button:hover { color: #fff; }
    .manager-show .mg-hero-label { margin-bottom: 7px; color: rgba(255, 255, 255, .7); font-size: 11px; }
    .manager-show .mg-hero-divider { width: 1px; min-height: 45px; background: rgba(255, 255, 255, .17); }

    /* Badges */
    .manager-show .mg-badge {
        display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border: 1px solid transparent;
        border-radius: 8px; font-size: 11px; font-weight: 800; line-height: 1.3; white-space: nowrap;
    }
    .manager-show .mg-badge::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .manager-show .mg-badge.no-dot::before { display: none; }
    .manager-show .mg-badge.active, .manager-show .mg-badge.verified { border-color: #C8F0DC; background: #E8FAF0; color: #18764B; }
    .manager-show .mg-badge.inactive, .manager-show .mg-badge.ended, .manager-show .mg-badge.unknown { border-color: #E4E7EC; background: #F2F4F7; color: #667085; }
    .manager-show .mg-badge.pending { border-color: #F6E1A7; background: #FFF7DF; color: #936500; }
    .manager-show .mg-badge.neutral { border-color: rgba(255, 255, 255, .18); background: rgba(255, 255, 255, .11); color: #fff; }

    /* KPI cards */
    .manager-show .mg-kpi {
        display: flex; align-items: center; gap: 13px; height: 100%; min-height: 96px; padding: 16px;
        border: 1px solid var(--mg-border); border-radius: 14px; background: #fff; box-shadow: 0 5px 20px rgba(25, 38, 93, .035);
    }
    .manager-show .mg-kpi-icon { display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; flex: 0 0 44px; border-radius: 12px; font-size: 18px; }
    .manager-show .k-orange { background: #FFF0E7; color: #D05208; }
    .manager-show .k-navy   { background: #EDF0FB; color: #19265D; }
    .manager-show .k-green  { background: #E8F7EF; color: #16845B; }
    .manager-show .k-purple { background: #F3ECFF; color: #7652B5; }
    .manager-show .mg-kpi-label { margin-bottom: 5px; color: var(--mg-muted); font-size: 11.5px; font-weight: 700; }
    .manager-show .mg-kpi-value { color: var(--mg-navy); font-size: 20px; font-weight: 800; letter-spacing: -.5px; line-height: 1.15; overflow-wrap: anywhere; }
    .manager-show .mg-kpi-note { margin-top: 4px; color: var(--mg-muted); font-size: 11px; }

    /* Cards */
    .manager-show .mg-card { margin-bottom: 20px; overflow: hidden; border: 1px solid var(--mg-border); border-radius: 15px; background: #fff; box-shadow: 0 5px 24px rgba(25, 38, 93, .04); }
    .manager-show .mg-card-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--mg-border); }
    .manager-show .mg-card-heading { display: flex; align-items: center; gap: 11px; min-width: 0; }
    .manager-show .mg-card-icon { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; flex: 0 0 36px; border-radius: 10px; background: var(--mg-orange-soft); color: var(--mg-orange); font-size: 16px; }
    .manager-show .mg-card-title { margin: 0; color: var(--mg-navy); font-size: 14px; font-weight: 800; }
    .manager-show .mg-card-subtitle { margin-top: 3px; color: var(--mg-muted); font-size: 11px; line-height: 1.5; }
    .manager-show .mg-card-body { padding: 20px; }

    /* Tabs */
    .manager-show .mg-tabs { display: flex; gap: 6px; padding: 6px; margin-bottom: 18px; overflow-x: auto; border: 1px solid var(--mg-border); border-radius: 13px; background: #fff; box-shadow: 0 5px 20px rgba(25, 38, 93, .03); }
    .manager-show .mg-tabs .nav-link {
        display: inline-flex; align-items: center; gap: 8px; padding: 9px 14px; border: 0; border-radius: 9px;
        background: transparent; color: #5B6880; font-size: 12px; font-weight: 700; white-space: nowrap;
    }
    .manager-show .mg-tabs .nav-link:hover { background: #F5F7FB; color: var(--mg-navy); }
    .manager-show .mg-tabs .nav-link.active { background: var(--mg-navy); color: #fff; }
    .manager-show .mg-tabs .tab-count { padding: 0 7px; border-radius: 10px; background: #EEF1F7; color: #5B6880; font-size: 11px; font-weight: 800; }
    .manager-show .mg-tabs .nav-link.active .tab-count { background: rgba(255, 255, 255, .2); color: #fff; }

    /* Detail tiles */
    .manager-show .mg-detail { height: 100%; min-height: 78px; padding: 13px 14px; border: 1px solid #EDF0F5; border-radius: 10px; background: #FCFCFE; }
    .manager-show .mg-detail-label { display: flex; align-items: center; gap: 7px; margin-bottom: 7px; color: var(--mg-muted); font-size: 11px; font-weight: 700; }
    .manager-show .mg-detail-label i { color: var(--mg-orange); font-size: 12px; }
    .manager-show .mg-detail-value { color: #263247; font-size: 12px; font-weight: 700; line-height: 1.65; overflow-wrap: anywhere; }
    .manager-show .mg-detail-value a { color: var(--mg-orange); text-decoration: none; }
    .manager-show .mg-detail-value a:hover { text-decoration: underline; }
    .manager-show .mg-empty { color: #98A2B3; font-weight: 500; font-style: italic; }

    /* Assignments table */
    .manager-show .mg-pills { display: flex; flex-wrap: wrap; gap: 8px; }
    .manager-show .mg-pill {
        display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid #E1E5ED; border-radius: 20px;
        background: #fff; color: #4B556B; font-size: 11px; font-weight: 700;
    }
    .manager-show .mg-pill:hover { background: #F5F7FB; color: var(--mg-navy); }
    .manager-show .mg-pill.active { background: var(--mg-navy); border-color: var(--mg-navy); color: #fff; }
    .manager-show .mg-pill .pill-count { padding: 0 7px; border-radius: 10px; background: #EEF1F7; color: #5B6880; font-size: 11px; font-weight: 800; }
    .manager-show .mg-pill.active .pill-count { background: rgba(255, 255, 255, .2); color: #fff; }

    .manager-show .mg-table-wrap { width: 100%; overflow-x: auto; }
    .manager-show .mg-table { width: 100%; min-width: 820px; margin: 0; border-collapse: separate; border-spacing: 0; }
    .manager-show .mg-table thead th { padding: 12px 16px; border-bottom: 1px solid var(--mg-border); background: #F8F9FC; color: #7B8498; font-size: 11px; font-weight: 800; letter-spacing: .55px; text-transform: uppercase; white-space: nowrap; }
    .manager-show .mg-table tbody td { padding: 14px 16px; border-bottom: 1px solid #EEF0F5; color: #4D586D; font-size: 11px; vertical-align: middle; }
    .manager-show .mg-table tbody tr:last-child td { border-bottom: 0; }
    .manager-show .mg-table tbody tr:hover { background: #FCFCFE; }

    .manager-show .prop-cell { display: flex; align-items: center; gap: 11px; min-width: 210px; }
    .manager-show .prop-thumb { width: 44px; height: 44px; flex: 0 0 44px; display: flex; align-items: center; justify-content: center; border-radius: 12px; background: #EDF0FB; color: var(--mg-navy); font-size: 18px; }
    .manager-show .prop-name { color: var(--mg-navy); font-size: 12px; font-weight: 800; text-decoration: none; }
    .manager-show a.prop-name:hover { color: var(--mg-orange); text-decoration: underline; }
    .manager-show .prop-meta { margin-top: 3px; color: var(--mg-muted); font-size: 11px; }
    .manager-show .prop-code { display: inline-block; margin-top: 4px; padding: 2px 7px; border: 1px solid #E8EBF3; border-radius: 5px; background: #F8F9FC; color: var(--mg-navy); font-size: 11px; font-weight: 800; }

    .manager-show .occ-wrap { min-width: 120px; }
    .manager-show .occ-text { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 11px; font-weight: 700; color: var(--mg-navy); }
    .manager-show .occ-bar { height: 6px; overflow: hidden; border-radius: 6px; background: #EDF0F5; }
    .manager-show .occ-bar span { display: block; height: 100%; border-radius: 6px; background: linear-gradient(90deg, #16845B, #3FB583); }

    .manager-show .row-btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #E3E7EF; border-radius: 8px; background: #fff; color: var(--mg-navy); font-size: 14px; }
    .manager-show .row-btn:hover, .manager-show .row-btn[aria-expanded="true"] { border-color: #F1C4AA; background: #FFF5EF; color: var(--mg-orange); }

    .manager-show .mg-empty-state { padding: 46px 20px; text-align: center; }
    .manager-show .mg-empty-icon { display: flex; align-items: center; justify-content: center; width: 60px; height: 60px; margin: 0 auto 15px; border-radius: 18px; background: var(--mg-orange-soft); color: var(--mg-orange); font-size: 24px; }
    .manager-show .mg-empty-state h3 { color: var(--mg-navy); font-size: 15px; font-weight: 800; }
    .manager-show .mg-empty-state p { max-width: 360px; margin: 7px auto 0; color: var(--mg-muted); font-size: 12px; line-height: 1.7; }
    .manager-show .mg-table-foot { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px; padding: 13px 20px; border-top: 1px solid var(--mg-border); background: #FCFCFE; color: var(--mg-muted); font-size: 11px; }

    /* Sidebar rows */
    .manager-show .side-row { display: flex; align-items: flex-start; gap: 12px; padding: 13px 0; border-bottom: 1px solid #EEF0F5; }
    .manager-show .side-row:first-child { padding-top: 0; }
    .manager-show .side-row:last-child { padding-bottom: 0; border-bottom: 0; }
    .manager-show .side-icon { display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; flex: 0 0 34px; border-radius: 10px; background: #F1F3FB; color: var(--mg-navy); font-size: 14px; }
    .manager-show .side-label { margin-bottom: 4px; color: var(--mg-muted); font-size: 11px; font-weight: 700; }
    .manager-show .side-value { color: #263247; font-size: 12px; font-weight: 700; line-height: 1.6; overflow-wrap: anywhere; }
    .manager-show .side-value a { color: var(--mg-orange); text-decoration: none; }

    .manager-show .mg-note { padding: 15px; border: 1px solid #F1E5D8; border-radius: 10px; background: #FFFAF5; color: #5F554D; font-size: 12px; line-height: 1.8; white-space: pre-line; overflow-wrap: anywhere; }
    .manager-show .mg-note.is-empty { color: #9A8D81; font-style: italic; }

    .manager-show .act-btn {
        display: flex; align-items: center; gap: 11px; width: 100%; padding: 11px 13px; border: 1px solid var(--mg-border);
        border-radius: 10px; background: #fff; color: var(--mg-navy); font-size: 12px; font-weight: 700; text-align: left; text-decoration: none; transition: .18s ease;
    }
    .manager-show .act-btn + .act-btn { margin-top: 8px; }
    .manager-show .act-btn i:first-child { color: var(--mg-orange); font-size: 15px; }
    .manager-show .act-btn:hover { border-color: #EFC5AC; background: #FFFAF6; color: var(--mg-orange); }
    .manager-show .act-btn.danger, .manager-show .act-btn.danger i:first-child { color: #B42318; }
    .manager-show .act-btn.danger:hover { border-color: #F1C6C2; background: #FFF5F4; }

    .manager-show .mg-record-footer { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px; padding: 13px 18px; border-top: 1px solid var(--mg-border); background: #FCFCFE; color: var(--mg-muted); font-size: 11px; }

    /* Modals */
    .manager-show-modal .modal-content { border: 0; border-radius: 17px; overflow: hidden; }
    .manager-show-modal .modal-header { padding: 18px 22px; border-bottom: 1px solid var(--terra-border, #E7EAF1); }
    .manager-show-modal .modal-title { color: var(--terra-navy, #19265D); font-size: 15px; font-weight: 800; }
    .manager-show-modal .modal-body { padding: 22px; font-size: 13px; color: #35425B; }
    .manager-show-modal .modal-footer { padding: 14px 22px; border-top: 1px solid var(--terra-border, #E7EAF1); }
    .manager-show-modal .m-icon { display: flex; align-items: center; justify-content: center; width: 58px; height: 58px; margin: 0 auto 14px; border-radius: 50%; font-size: 24px; }
    .manager-show-modal .m-icon.orange { background: #FFF0E7; color: #D05208; }
    .manager-show-modal .m-icon.red { background: #FFF0F0; color: #B42318; }
    .manager-show-modal .m-btn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 15px; border-radius: 9px; border: 1px solid transparent; font-size: 12px; font-weight: 700; text-decoration: none; }
    .manager-show-modal .m-btn-primary { background: #D05208; color: #fff; border-color: #D05208; }
    .manager-show-modal .m-btn-primary:hover { background: #AD4307; color: #fff; }
    .manager-show-modal .m-btn-danger { background: #B42318; color: #fff; border-color: #B42318; }
    .manager-show-modal .m-btn-danger:hover { background: #912018; color: #fff; }
    .manager-show-modal .m-btn-outline { background: #fff; color: #19265D; border-color: #DDE2EC; }
    .manager-show-modal .m-btn-outline:hover { background: #F5F7FB; }
    .manager-show-modal .contact-row { display: flex; align-items: center; gap: 12px; padding: 13px; border: 1px solid #E7EAF1; border-radius: 11px; }
    .manager-show-modal .contact-row + .contact-row { margin-top: 10px; }
    .manager-show-modal .contact-row .c-icon { display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 10px; background: #FFF0E7; color: #D05208; font-size: 16px; flex-shrink: 0; }
    .manager-show-modal .contact-row .c-label { color: #778197; font-size: 11px; font-weight: 700; }
    .manager-show-modal .contact-row .c-value { color: #19265D; font-size: 12.5px; font-weight: 700; overflow-wrap: anywhere; }
    .manager-show-modal .c-actions { margin-left: auto; display: flex; gap: 6px; }
    .manager-show-modal .c-actions a, .manager-show-modal .c-actions button { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #E3E7EF; border-radius: 8px; background: #fff; color: #19265D; font-size: 13px; text-decoration: none; }
    .manager-show-modal .c-actions a:hover, .manager-show-modal .c-actions button:hover { border-color: #F1C4AA; background: #FFF5EF; color: #D05208; }

    @media (max-width: 767.98px) {
        .manager-show .mg-hero { padding: 20px; }
        .manager-show .mg-avatar { width: 58px; height: 58px; flex-basis: 58px; border-radius: 16px; font-size: 20px; }
        .manager-show .mg-hero-name { font-size: 18px; }
        .manager-show .mg-card-header, .manager-show .mg-card-body { padding: 16px; }
        .manager-show .page-intro > div:last-child { width: 100%; }
    }

    @media print {
        .manager-show .page-intro > div:last-child, .manager-show .breadcrumb-line, .manager-show .mg-tabs, .manager-show .act-card, .manager-show .mg-pills, .manager-show .row-btn { display: none !important; }
        .manager-show .tab-pane { display: block !important; opacity: 1 !important; }
        .manager-show .mg-hero, .manager-show .mg-card { break-inside: avoid; box-shadow: none; }
    }
</style>
@endpush

@section('content')
@php
    $DB = \Illuminate\Support\Facades\DB::class;

    $managerUser   = $manager->user;
    $managerName   = $managerUser?->name ?? 'User account unavailable';
    $managerEmail  = $managerUser?->email;
    $managerStatus = strtolower($manager->status ?? 'inactive');
    $statusClass   = in_array($managerStatus, ['active', 'inactive'], true) ? $managerStatus : 'unknown';

    $initials = collect(preg_split('/\s+/', trim($managerName)))->filter()->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');

    $employmentType = $manager->employment_type
        ? ucwords(str_replace('_', ' ', $manager->employment_type))
        : 'Not specified';

    $safeDate = function ($date) {
        if (!$date) { return null; }
        try { return \Illuminate\Support\Carbon::parse($date); } catch (\Throwable $e) { return null; }
    };
    $formatDate = fn ($date, $format = 'd M Y') => $safeDate($date)?->format($format);

    $startDate = $safeDate($manager->start_date);
    $tenure = null;
    if ($startDate) {
        $months = (int) $startDate->diffInMonths(now());
        $tenure = $months >= 12
            ? floor($months / 12) . ' yr' . (floor($months / 12) > 1 ? 's' : '') . ($months % 12 ? ' ' . ($months % 12) . ' mo' : '')
            : max($months, 0) . ' mo';
    }

    /* ---------- Assignments (single set of queries) ---------- */
    $assignments = $DB::table('property_manager_assignments as a')
        ->join('properties as p', 'p.id', '=', 'a.property_id')
        ->leftJoin('property_owners as o', 'o.id', '=', 'p.property_owner_id')
        ->leftJoin('users as ab', 'ab.id', '=', 'a.assigned_by')
        ->where('a.property_manager_id', $manager->id)
        ->whereNull('p.deleted_at')
        ->select(
            'a.id as assignment_id', 'a.status as assignment_status', 'a.start_date', 'a.end_date', 'a.notes as assignment_notes',
            'p.id', 'p.name', 'p.property_code', 'p.property_type', 'p.district', 'p.sector', 'p.address',
            'o.id as owner_id', 'o.name as owner_name', 'ab.name as assigned_by_name'
        )
        ->orderByRaw("CASE WHEN a.status = 'active' THEN 0 ELSE 1 END")
        ->orderByDesc('a.start_date')
        ->get();

    $propIds = $assignments->pluck('id')->unique()->all();

    $unitStats = $DB::table('units')
        ->join('buildings', 'buildings.id', '=', 'units.building_id')
        ->whereIn('buildings.property_id', $propIds)
        ->whereNull('units.deleted_at')
        ->whereNull('buildings.deleted_at')
        ->groupBy('buildings.property_id')
        ->selectRaw("buildings.property_id as property_id, COUNT(units.id) as total, SUM(CASE WHEN units.status = 'occupied' THEN 1 ELSE 0 END) as occupied, SUM(CASE WHEN units.status = 'occupied' THEN units.rent_amount ELSE 0 END) as rent_roll")
        ->get()->keyBy('property_id');

    $activeAssignments = $assignments->filter(fn ($a) => strtolower($a->assignment_status) === 'active');
    $activeIds   = $activeAssignments->pluck('id')->unique();
    $managedUnits    = (int) $activeIds->sum(fn ($id) => $unitStats[$id]->total ?? 0);
    $managedOccupied = (int) $activeIds->sum(fn ($id) => $unitStats[$id]->occupied ?? 0);
    $managedRent     = (float) $activeIds->sum(fn ($id) => $unitStats[$id]->rent_roll ?? 0);
    $occupancyRate   = $managedUnits > 0 ? round($managedOccupied / $managedUnits * 100) : 0;
    $endedAssignments = $assignments->count() - $activeAssignments->count();

    $money = fn ($v) => number_format((float) $v, 0) . ' RWF';

    $canShowProperty = \Illuminate\Support\Facades\Route::has('admin.properties.show');
    $canEditProperty = \Illuminate\Support\Facades\Route::has('admin.properties.edit');
@endphp

<div class="manager-show">

    {{-- Page heading --}}
    <div class="page-intro">
        <div>
            <div class="breadcrumb-line">
                <a href="{{ \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/') }}">Dashboard</a>
                <i class="bi bi-chevron-right"></i>
                <a href="{{ route('admin.managers.index') }}">Property Managers</a>
                <i class="bi bi-chevron-right"></i>
                <span>Manager profile</span>
            </div>

            <h1 class="page-title">Manager profile</h1>
            <p class="page-description">View account information, employment details and assigned properties.</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.managers.index') }}" class="mg-btn mg-btn-outline"><i class="bi bi-arrow-left"></i> Back</a>

            <a href="{{ route('admin.managers.edit', $manager) }}" class="mg-btn mg-btn-primary">
                <i class="bi bi-pencil-square"></i> Edit manager
            </a>

            <div class="dropdown">
                <button type="button" class="mg-btn mg-btn-outline" data-bs-toggle="dropdown"
                        data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                    <i class="bi bi-three-dots"></i> Actions
                </button>

                <ul class="dropdown-menu dropdown-menu-end action-menu">
                    <li>
                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#contactManagerModal">
                            <i class="bi bi-chat-dots"></i> Contact manager
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#resendCredentialsModal">
                            <i class="bi bi-envelope-arrow-up"></i> Resend credentials
                        </button>
                    </li>
                    <li>
                        <x-hr-add-button :user="$manager->user" class="dropdown-item"
                            :property-id="\App\Models\PropertyManagerAssignment::where('property_manager_id', $manager->id)->where('status', 'active')->whereNull('end_date')->value('property_id')" />
                    </li>
                    <li>
                        <button type="button" class="dropdown-item" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print profile
                        </button>
                    </li>
                    @if($managerStatus === 'active')
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <button type="button" class="dropdown-item item-danger" data-bs-toggle="modal" data-bs-target="#deactivateManagerModal">
                                <i class="bi bi-person-dash"></i> Deactivate manager
                            </button>
                        </li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <x-purge-button type="manager" :id="$manager->id" what="this manager and their login"
                                        class="dropdown-item item-danger" />
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Hero --}}
    <section class="mg-hero">
        <div class="mg-hero-inner">
            <div class="d-flex align-items-center gap-3">
                <div class="mg-avatar">{{ $initials ?: 'PM' }}</div>

                <div class="min-w-0">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h2 class="mg-hero-name">{{ $managerName }}</h2>
                        <span class="mg-badge neutral no-dot"><i class="bi bi-briefcase"></i> {{ $manager->job_title ?: 'Property Manager' }}</span>
                    </div>

                    <div class="mg-hero-meta">
                        Manager reference: <strong>{{ $manager->manager_number ?: 'Not assigned' }}</strong>
                        &middot; {{ $employmentType }}
                    </div>

                    <div class="mg-chips">
                        @if($managerEmail)
                            <span class="mg-chip">
                                <i class="bi bi-envelope"></i> {{ $managerEmail }}
                                <button type="button" data-copy="{{ $managerEmail }}" title="Copy email" aria-label="Copy email"><i class="bi bi-clipboard"></i></button>
                            </span>
                        @endif
                        @if(filled($manager->phone))
                            <span class="mg-chip">
                                <i class="bi bi-telephone"></i> {{ $manager->phone }}
                                <button type="button" data-copy="{{ $manager->phone }}" title="Copy phone" aria-label="Copy phone"><i class="bi bi-clipboard"></i></button>
                            </span>
                        @endif
                        @if(filled($manager->district))
                            <span class="mg-chip"><i class="bi bi-geo-alt"></i> {{ collect([$manager->district, $manager->province])->filter()->implode(', ') }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-3">
                <div>
                    <div class="mg-hero-label">Account status</div>
                    <span class="mg-badge {{ $statusClass }}">{{ ucfirst($managerStatus) }}</span>
                </div>
                <div class="mg-hero-divider d-none d-md-block"></div>
                <div>
                    <div class="mg-hero-label">With Terra</div>
                    <span class="mg-badge neutral no-dot"><i class="bi bi-calendar-check"></i> {{ $tenure ?: 'Start date not set' }}</span>
                </div>
            </div>
        </div>
    </section>

    {{-- KPI cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="mg-kpi">
                <div class="mg-kpi-icon k-orange"><i class="bi bi-buildings"></i></div>
                <div>
                    <div class="mg-kpi-label">Active properties</div>
                    <div class="mg-kpi-value">{{ number_format($activeAssignments->count()) }}</div>
                    <div class="mg-kpi-note">{{ $assignments->count() }} total assignments</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="mg-kpi">
                <div class="mg-kpi-icon k-navy"><i class="bi bi-door-open"></i></div>
                <div>
                    <div class="mg-kpi-label">Units managed</div>
                    <div class="mg-kpi-value">{{ $managedOccupied }}/{{ $managedUnits }}</div>
                    <div class="mg-kpi-note">{{ $occupancyRate }}% occupied</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="mg-kpi">
                <div class="mg-kpi-icon k-green"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="mg-kpi-label">Monthly rent roll</div>
                    <div class="mg-kpi-value" style="font-size:16px;">{{ $money($managedRent) }}</div>
                    <div class="mg-kpi-note">From occupied units</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="mg-kpi">
                <div class="mg-kpi-icon k-purple"><i class="bi bi-calendar-check"></i></div>
                <div>
                    <div class="mg-kpi-label">Employment start</div>
                    <div class="mg-kpi-value" style="font-size:16px;">{{ $formatDate($manager->start_date) ?: 'Not set' }}</div>
                    <div class="mg-kpi-note">{{ $tenure ? $tenure . ' in role' : 'Tenure unavailable' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- ============ MAIN COLUMN ============ --}}
        <div class="col-12 col-xl-8">

            <ul class="nav mg-tabs" id="managerTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#pane-overview" type="button" role="tab">
                        <i class="bi bi-person-vcard"></i> Overview
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pane-properties" type="button" role="tab">
                        <i class="bi bi-buildings"></i> Assigned properties <span class="tab-count">{{ $assignments->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pane-notes" type="button" role="tab">
                        <i class="bi bi-journal-text"></i> Notes &amp; audit
                    </button>
                </li>
            </ul>

            <div class="tab-content">

                {{-- ---------- Overview ---------- --}}
                <div class="tab-pane fade show active" id="pane-overview" role="tabpanel" tabindex="0">

                    <section class="mg-card">
                        <div class="mg-card-header">
                            <div class="mg-card-heading">
                                <div class="mg-card-icon"><i class="bi bi-briefcase"></i></div>
                                <div>
                                    <h2 class="mg-card-title">Employment information</h2>
                                    <div class="mg-card-subtitle">Position, employment arrangement and current status</div>
                                </div>
                            </div>
                            <span class="mg-badge {{ $statusClass }}">{{ ucfirst($managerStatus) }}</span>
                        </div>

                        <div class="mg-card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="mg-detail">
                                        <div class="mg-detail-label"><i class="bi bi-person-badge"></i> Job title</div>
                                        <div class="mg-detail-value {{ filled($manager->job_title) ? '' : 'mg-empty' }}">{{ $manager->job_title ?: 'Not specified' }}</div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="mg-detail">
                                        <div class="mg-detail-label"><i class="bi bi-diagram-3"></i> Employment type</div>
                                        <div class="mg-detail-value">{{ $employmentType }}</div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="mg-detail">
                                        <div class="mg-detail-label"><i class="bi bi-calendar-check"></i> Employment start date</div>
                                        <div class="mg-detail-value {{ $startDate ? '' : 'mg-empty' }}">{{ $formatDate($manager->start_date) ?: 'Not provided' }}</div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="mg-detail">
                                        <div class="mg-detail-label"><i class="bi bi-upc-scan"></i> Manager reference</div>
                                        <div class="mg-detail-value">{{ $manager->manager_number ?: 'Not assigned' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="mg-card">
                        <div class="mg-card-header">
                            <div class="mg-card-heading">
                                <div class="mg-card-icon"><i class="bi bi-telephone"></i></div>
                                <div>
                                    <h2 class="mg-card-title">Contact &amp; location</h2>
                                    <div class="mg-card-subtitle">Communication channels and registered address</div>
                                </div>
                            </div>
                            <button type="button" class="mg-btn mg-btn-outline" style="min-height:34px;padding:6px 12px;" data-bs-toggle="modal" data-bs-target="#contactManagerModal">
                                <i class="bi bi-chat-dots"></i> Contact
                            </button>
                        </div>

                        <div class="mg-card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="mg-detail">
                                        <div class="mg-detail-label"><i class="bi bi-envelope"></i> Email address</div>
                                        <div class="mg-detail-value">
                                            @if($managerEmail) <a href="mailto:{{ $managerEmail }}">{{ $managerEmail }}</a>
                                            @else <span class="mg-empty">Not available</span> @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="mg-detail">
                                        <div class="mg-detail-label"><i class="bi bi-telephone"></i> Phone number</div>
                                        <div class="mg-detail-value">
                                            @if(filled($manager->phone)) <a href="tel:{{ $manager->phone }}">{{ $manager->phone }}</a>
                                            @else <span class="mg-empty">Not provided</span> @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="mg-detail">
                                        <div class="mg-detail-label"><i class="bi bi-map"></i> Province / district</div>
                                        <div class="mg-detail-value {{ filled($manager->district) || filled($manager->province) ? '' : 'mg-empty' }}">{{ collect([$manager->district, $manager->province])->filter()->implode(', ') ?: 'Not provided' }}</div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="mg-detail">
                                        <div class="mg-detail-label"><i class="bi bi-signpost-2"></i> Physical address</div>
                                        <div class="mg-detail-value {{ filled($manager->address) ? '' : 'mg-empty' }}">{{ $manager->address ?: 'Not provided' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- ---------- Assigned properties ---------- --}}
                <div class="tab-pane fade" id="pane-properties" role="tabpanel" tabindex="0">
                    <section class="mg-card">
                        <div class="mg-card-header">
                            <div class="mg-card-heading">
                                <div class="mg-card-icon"><i class="bi bi-buildings"></i></div>
                                <div>
                                    <h2 class="mg-card-title">Assigned properties</h2>
                                    <div class="mg-card-subtitle">{{ $activeAssignments->count() }} active and {{ $endedAssignments }} ended {{ \Illuminate\Support\Str::plural('assignment', $assignments->count()) }}</div>
                                </div>
                            </div>

                            @if($assignments->isNotEmpty())
                                <div class="mg-pills" id="assignmentPills">
                                    <button type="button" class="mg-pill active" data-filter="all">All <span class="pill-count">{{ $assignments->count() }}</span></button>
                                    <button type="button" class="mg-pill" data-filter="active">Active <span class="pill-count">{{ $activeAssignments->count() }}</span></button>
                                    <button type="button" class="mg-pill" data-filter="ended">Ended <span class="pill-count">{{ $endedAssignments }}</span></button>
                                </div>
                            @endif
                        </div>

                        @if($assignments->isEmpty())
                            <div class="mg-empty-state">
                                <div class="mg-empty-icon"><i class="bi bi-buildings"></i></div>
                                <h3>No properties assigned</h3>
                                <p>This manager does not have any property assignments yet. Properties are assigned from the property page.</p>
                            </div>
                        @else
                            <div class="mg-table-wrap">
                                <table class="mg-table" id="assignmentTable">
                                    <thead>
                                        <tr>
                                            <th>Property</th>
                                            <th>Owner</th>
                                            <th>Units &amp; occupancy</th>
                                            <th>Assignment period</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach($assignments as $item)
                                            @php
                                                $aStatus = strtolower($item->assignment_status ?? 'unknown');
                                                $aGroup  = $aStatus === 'active' ? 'active' : 'ended';
                                                $aClass  = in_array($aStatus, ['active', 'inactive', 'ended'], true) ? $aStatus : 'unknown';

                                                $stat   = $unitStats[$item->id] ?? null;
                                                $pUnits = (int) ($stat->total ?? 0);
                                                $pOcc   = (int) ($stat->occupied ?? 0);
                                                $pRate  = $pUnits > 0 ? round($pOcc / $pUnits * 100) : 0;

                                                $pType = ucwords(str_replace('_', ' ', $item->property_type ?? 'property'));
                                                $pLocation = collect([$item->sector, $item->district])->filter()->implode(', ');
                                            @endphp

                                            <tr data-group="{{ $aGroup }}">
                                                <td>
                                                    <div class="prop-cell">
                                                        <div class="prop-thumb"><i class="bi bi-building"></i></div>
                                                        <div>
                                                            @if($canShowProperty)
                                                                <a href="{{ route('admin.properties.show', $item->id) }}" class="prop-name">{{ $item->name }}</a>
                                                            @else
                                                                <span class="prop-name">{{ $item->name }}</span>
                                                            @endif
                                                            <div class="prop-meta">{{ $pType }}@if($pLocation) &middot; {{ $pLocation }}@endif</div>
                                                            <span class="prop-code">{{ $item->property_code }}</span>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td>
                                                    @if($item->owner_name)
                                                        @if(\Illuminate\Support\Facades\Route::has('admin.owners.show') && $item->owner_id)
                                                            <a href="{{ route('admin.owners.show', $item->owner_id) }}" class="prop-name">{{ $item->owner_name }}</a>
                                                        @else
                                                            <span style="font-weight:700;color:#35425B;">{{ $item->owner_name }}</span>
                                                        @endif
                                                    @else
                                                        <span class="mg-empty">Not recorded</span>
                                                    @endif
                                                </td>

                                                <td>
                                                    <div class="occ-wrap">
                                                        <div class="occ-text">
                                                            <span>{{ $pOcc }}/{{ $pUnits }} {{ $pUnits === 1 ? 'unit' : 'units' }}</span>
                                                            <span>{{ $pRate }}%</span>
                                                        </div>
                                                        <div class="occ-bar"><span style="width: {{ $pRate }}%"></span></div>
                                                    </div>
                                                </td>

                                                <td>
                                                    <div style="font-weight:700;color:#35425B;">{{ $formatDate($item->start_date) ?: 'Not set' }}</div>
                                                    <div class="prop-meta">to {{ $formatDate($item->end_date) ?: 'present' }}</div>
                                                    @if($item->assigned_by_name)
                                                        <div class="prop-meta">By {{ $item->assigned_by_name }}</div>
                                                    @endif
                                                </td>

                                                <td><span class="mg-badge {{ $aClass }}">{{ ucfirst(str_replace('_', ' ', $aStatus)) }}</span></td>

                                                <td class="text-end">
                                                    <div class="dropdown">
                                                        <button type="button" class="row-btn" data-bs-toggle="dropdown"
                                                                data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false"
                                                                aria-label="Actions for {{ $item->name }}">
                                                            <i class="bi bi-three-dots-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end row-menu">
                                                            @if($canShowProperty)
                                                                <li><a class="dropdown-item" href="{{ route('admin.properties.show', $item->id) }}"><i class="bi bi-eye"></i> View property</a></li>
                                                            @endif
                                                            @if($canEditProperty)
                                                                <li><a class="dropdown-item" href="{{ route('admin.properties.edit', $item->id) }}"><i class="bi bi-pencil-square"></i> Edit property</a></li>
                                                            @endif
                                                            @if($item->assignment_notes)
                                                                <li><span class="dropdown-item-text small text-muted" style="max-width:240px;white-space:normal;"><i class="bi bi-chat-left-text me-1"></i>{{ \Illuminate\Support\Str::limit($item->assignment_notes, 120) }}</span></li>
                                                            @endif
                                                            @if(!$canShowProperty && !$canEditProperty && !$item->assignment_notes)
                                                                <li><span class="dropdown-item-text text-muted">No actions available</span></li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach

                                        <tr id="assignmentNoMatch" style="display:none;">
                                            <td colspan="6" class="text-center text-muted py-4">No assignments in this group.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mg-table-foot">
                                <span>{{ $assignments->count() }} {{ \Illuminate\Support\Str::plural('assignment', $assignments->count()) }} &middot; {{ $managedUnits }} {{ \Illuminate\Support\Str::plural('unit', $managedUnits) }} under active management</span>
                                <span>Rent roll: <strong>{{ $money($managedRent) }}</strong> / month</span>
                            </div>
                        @endif
                    </section>
                </div>

                {{-- ---------- Notes & audit ---------- --}}
                <div class="tab-pane fade" id="pane-notes" role="tabpanel" tabindex="0">
                    <section class="mg-card">
                        <div class="mg-card-header">
                            <div class="mg-card-heading">
                                <div class="mg-card-icon"><i class="bi bi-journal-text"></i></div>
                                <div>
                                    <h2 class="mg-card-title">Internal notes</h2>
                                    <div class="mg-card-subtitle">Administrative remarks recorded for this manager</div>
                                </div>
                            </div>
                        </div>
                        <div class="mg-card-body">
                            <div class="mg-note {{ filled($manager->notes) ? '' : 'is-empty' }}">{{ filled($manager->notes) ? $manager->notes : 'No internal notes have been recorded for this manager.' }}</div>
                        </div>
                    </section>

                    <section class="mg-card">
                        <div class="mg-card-header">
                            <div class="mg-card-heading">
                                <div class="mg-card-icon"><i class="bi bi-clock-history"></i></div>
                                <div>
                                    <h2 class="mg-card-title">Record information</h2>
                                    <div class="mg-card-subtitle">Profile creation and last update</div>
                                </div>
                            </div>
                        </div>

                        <div class="mg-card-body">
                            <div class="side-row">
                                <div class="side-icon"><i class="bi bi-person-plus"></i></div>
                                <div>
                                    <div class="side-label">Created by</div>
                                    <div class="side-value">{{ $manager->creator?->name ?? 'System / unavailable' }}</div>
                                    @if($manager->creator?->email) <div class="mg-card-subtitle">{{ $manager->creator->email }}</div> @endif
                                </div>
                            </div>
                            <div class="side-row">
                                <div class="side-icon"><i class="bi bi-calendar-plus"></i></div>
                                <div>
                                    <div class="side-label">Profile created</div>
                                    <div class="side-value">{{ $formatDate($manager->created_at, 'd M Y, H:i') ?: 'Not available' }}</div>
                                </div>
                            </div>
                            <div class="side-row">
                                <div class="side-icon"><i class="bi bi-pencil"></i></div>
                                <div>
                                    <div class="side-label">Last updated</div>
                                    <div class="side-value">{{ $formatDate($manager->updated_at, 'd M Y, H:i') ?: 'Not available' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mg-record-footer">
                            <span>Record ID: #{{ $manager->id }}</span>
                            <span>Terra Smart Rental Management</span>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        {{-- ============ SIDEBAR ============ --}}
        <div class="col-12 col-xl-4">

            {{-- Login account --}}
            <section class="mg-card">
                <div class="mg-card-header">
                    <div class="mg-card-heading">
                        <div class="mg-card-icon"><i class="bi bi-person-lock"></i></div>
                        <div>
                            <h2 class="mg-card-title">Login account</h2>
                            <div class="mg-card-subtitle">Terra platform access information</div>
                        </div>
                    </div>
                </div>

                <div class="mg-card-body">
                    @if($managerUser)
                        <div class="side-row">
                            <div class="side-icon"><i class="bi bi-person"></i></div>
                            <div>
                                <div class="side-label">Account name</div>
                                <div class="side-value">{{ $managerName }} <span class="text-muted">(#{{ $managerUser->id }})</span></div>
                            </div>
                        </div>
                        <div class="side-row">
                            <div class="side-icon"><i class="bi bi-envelope"></i></div>
                            <div>
                                <div class="side-label">Login email</div>
                                <div class="side-value">@if($managerEmail)<a href="mailto:{{ $managerEmail }}">{{ $managerEmail }}</a>@else Not available @endif</div>
                            </div>
                        </div>
                        <div class="side-row">
                            <div class="side-icon"><i class="bi bi-person-badge"></i></div>
                            <div>
                                <div class="side-label">Assigned role</div>
                                <div class="side-value">{{ $managerUser->role ? ucwords(str_replace('_', ' ', $managerUser->role)) : 'Not specified' }}</div>
                            </div>
                        </div>
                        <div class="side-row">
                            <div class="side-icon"><i class="bi bi-patch-check"></i></div>
                            <div>
                                <div class="side-label">Email verification</div>
                                <div class="side-value">
                                    @if($managerUser->email_verified_at)
                                        <span class="mg-badge verified">Verified</span>
                                        <div class="mg-card-subtitle mt-2">{{ $formatDate($managerUser->email_verified_at, 'd M Y, H:i') }}</div>
                                    @else
                                        <span class="mg-badge pending">Not verified</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-2">
                            <div class="mg-kpi-icon k-navy mx-auto mb-3"><i class="bi bi-person-x"></i></div>
                            <div class="mg-card-title mb-2">User account unavailable</div>
                            <p class="mg-card-subtitle mb-0">The login account linked to this manager could not be found.</p>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Quick actions (open modals) --}}
            <section class="mg-card act-card">
                <div class="mg-card-header">
                    <div class="mg-card-heading">
                        <div class="mg-card-icon"><i class="bi bi-lightning-charge"></i></div>
                        <div>
                            <h2 class="mg-card-title">Quick actions</h2>
                            <div class="mg-card-subtitle">Manage this manager record</div>
                        </div>
                    </div>
                </div>

                <div class="mg-card-body">
                    <a href="{{ route('admin.managers.edit', $manager) }}" class="act-btn">
                        <i class="bi bi-pencil-square"></i><span>Edit manager information</span><i class="bi bi-chevron-right ms-auto"></i>
                    </a>

                    <button type="button" class="act-btn" data-bs-toggle="modal" data-bs-target="#contactManagerModal">
                        <i class="bi bi-chat-dots"></i><span>Contact manager</span><i class="bi bi-chevron-right ms-auto"></i>
                    </button>

                    <button type="button" class="act-btn" data-bs-toggle="modal" data-bs-target="#resendCredentialsModal">
                        <i class="bi bi-envelope-arrow-up"></i><span>Resend login credentials</span><i class="bi bi-chevron-right ms-auto"></i>
                    </button>

                    <a href="{{ route('admin.managers.index') }}" class="act-btn">
                        <i class="bi bi-people"></i><span>View all property managers</span><i class="bi bi-chevron-right ms-auto"></i>
                    </a>

                    @if($managerStatus === 'active')
                        <button type="button" class="act-btn danger" data-bs-toggle="modal" data-bs-target="#deactivateManagerModal">
                            <i class="bi bi-person-dash"></i><span>Deactivate manager</span><i class="bi bi-chevron-right ms-auto"></i>
                        </button>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>

{{-- ================= MODALS ================= --}}

{{-- Contact manager --}}
<div class="modal fade manager-show-modal" id="contactManagerModal" tabindex="-1" aria-labelledby="contactManagerLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contactManagerLabel">Contact {{ $managerName }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                @if($managerEmail || filled($manager->phone))
                    @if($managerEmail)
                        <div class="contact-row">
                            <div class="c-icon"><i class="bi bi-envelope"></i></div>
                            <div>
                                <div class="c-label">Email</div>
                                <div class="c-value">{{ $managerEmail }}</div>
                            </div>
                            <div class="c-actions">
                                <button type="button" data-copy="{{ $managerEmail }}" title="Copy" aria-label="Copy email"><i class="bi bi-clipboard"></i></button>
                                <a href="mailto:{{ $managerEmail }}" title="Send email" aria-label="Send email"><i class="bi bi-send"></i></a>
                            </div>
                        </div>
                    @endif

                    @if(filled($manager->phone))
                        <div class="contact-row">
                            <div class="c-icon"><i class="bi bi-telephone"></i></div>
                            <div>
                                <div class="c-label">Phone</div>
                                <div class="c-value">{{ $manager->phone }}</div>
                            </div>
                            <div class="c-actions">
                                <button type="button" data-copy="{{ $manager->phone }}" title="Copy" aria-label="Copy phone"><i class="bi bi-clipboard"></i></button>
                                <a href="tel:{{ $manager->phone }}" title="Call" aria-label="Call"><i class="bi bi-telephone-outbound"></i></a>
                                <a href="https://wa.me/{{ preg_replace('/\D/', '', $manager->phone) }}" target="_blank" rel="noopener" title="WhatsApp" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-3">
                        <div class="m-icon orange"><i class="bi bi-person-x"></i></div>
                        <p class="mb-0 text-muted">No email address or phone number is recorded for this manager. Edit the manager to add contact details.</p>
                    </div>
                @endif
            </div>

            <div class="modal-footer">
                <button type="button" class="m-btn m-btn-outline" data-bs-dismiss="modal">Close</button>
                <a href="{{ route('admin.managers.edit', $manager) }}" class="m-btn m-btn-primary"><i class="bi bi-pencil-square"></i> Edit contact details</a>
            </div>
        </div>
    </div>
</div>

{{-- Resend credentials --}}
<div class="modal fade manager-show-modal" id="resendCredentialsModal" tabindex="-1" aria-labelledby="resendCredentialsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form method="POST" action="{{ route('admin.managers.resend-credentials', $manager) }}" class="modal-content">
            @csrf

            <div class="modal-body text-center py-4">
                <div class="m-icon orange"><i class="bi bi-envelope-arrow-up"></i></div>
                <h5 class="fw-bold mb-2" id="resendCredentialsLabel" style="color:#19265D;">Resend credentials?</h5>
                <p class="mb-1 text-secondary">A new password will be generated and emailed to</p>
                <p class="fw-bold mb-0" style="overflow-wrap:anywhere;">{{ $managerEmail ?: 'this manager' }}</p>
                <p class="small text-muted mt-2 mb-0">Their current password will stop working.</p>
            </div>

            <div class="modal-footer justify-content-center">
                <button type="button" class="m-btn m-btn-outline" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="m-btn m-btn-primary"><i class="bi bi-send"></i> Send credentials</button>
            </div>
        </form>
    </div>
</div>

@if($managerStatus === 'active')
    {{-- Deactivate manager --}}
    <div class="modal fade manager-show-modal" id="deactivateManagerModal" tabindex="-1" aria-labelledby="deactivateManagerLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <form method="POST" action="{{ route('admin.managers.destroy', $manager) }}" class="modal-content">
                @csrf
                @method('DELETE')

                <div class="modal-body text-center py-4">
                    <div class="m-icon red"><i class="bi bi-person-dash"></i></div>
                    <h5 class="fw-bold mb-2" id="deactivateManagerLabel" style="color:#19265D;">Deactivate manager?</h5>
                    <p class="mb-1 text-secondary">You are about to deactivate <strong>{{ $managerName }}</strong>.</p>
                    @if($activeAssignments->count() > 0)
                        <p class="small text-danger mb-0">This manager is actively assigned to {{ $activeAssignments->count() }} {{ \Illuminate\Support\Str::plural('property', $activeAssignments->count()) }}. Reassign them before continuing.</p>
                    @else
                        <p class="small text-muted mb-0">The manager will no longer be able to use the platform.</p>
                    @endif
                </div>

                <div class="modal-footer justify-content-center">
                    <button type="button" class="m-btn m-btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="m-btn m-btn-danger"><i class="bi bi-person-dash"></i> Deactivate</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    /* Copy to clipboard */
    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const text = btn.dataset.copy;
            const done = function () {
                const icon = btn.querySelector('i');
                if (!icon) return;
                const old = icon.className;
                icon.className = 'bi bi-check2';
                setTimeout(function () { icon.className = old; }, 1400);
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(done);
            } else {
                const ta = document.createElement('textarea');
                ta.value = text; document.body.appendChild(ta); ta.select();
                try { document.execCommand('copy'); done(); } catch (e) {}
                document.body.removeChild(ta);
            }
        });
    });

    /* Assignment filter pills */
    const pills = document.querySelectorAll('#assignmentPills .mg-pill');
    const rows = document.querySelectorAll('#assignmentTable tbody tr[data-group]');
    const none = document.getElementById('assignmentNoMatch');
    pills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            pills.forEach(function (p) { p.classList.remove('active'); });
            pill.classList.add('active');
            const f = pill.dataset.filter;
            let visible = 0;
            rows.forEach(function (row) {
                const show = f === 'all' || row.dataset.group === f;
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            if (none) none.style.display = visible ? 'none' : '';
        });
    });

    /* Remember the active tab through the URL hash */
    const hash = window.location.hash;
    if (hash) {
        const trigger = document.querySelector('#managerTabs [data-bs-target="' + hash + '"]');
        if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
    }
    document.querySelectorAll('#managerTabs [data-bs-toggle="pill"]').forEach(function (tab) {
        tab.addEventListener('shown.bs.tab', function (e) {
            history.replaceState(null, '', e.target.dataset.bsTarget);
        });
    });
});
</script>
@endpush