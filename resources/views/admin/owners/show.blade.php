@extends('layouts.app')

@section('title', 'Property Owner Profile')

@section('page_heading', 'Property Owner Profile')

@section('page_subtitle', 'Registration details, properties, account access and audit records.')

@push('styles')
<style>
    .owner-show {
        --ow-orange: var(--terra-orange, #D05208);
        --ow-orange-dark: #AD4307;
        --ow-orange-soft: #FFF0E7;
        --ow-navy: var(--terra-navy, #19265D);
        --ow-border: var(--terra-border, #E7EAF1);
        --ow-muted: var(--terra-muted, #778197);
        color: #263047;
    }

    /* Heading */
    .owner-show .page-intro { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 18px; margin-bottom: 22px; }
    .owner-show .breadcrumb-line { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; color: var(--ow-muted); font-size: 11px; }
    .owner-show .breadcrumb-line a { color: var(--ow-muted); text-decoration: none; }
    .owner-show .breadcrumb-line a:hover { color: var(--ow-orange); }
    .owner-show .breadcrumb-line i { font-size: 10px; color: #AAB1BF; }
    .owner-show .page-title { margin: 0; color: var(--ow-navy); font-size: clamp(22px, 2vw, 28px); font-weight: 800; letter-spacing: -.7px; }
    .owner-show .page-description { margin: 7px 0 0; color: var(--ow-muted); font-size: 12px; line-height: 1.7; }

    .owner-show .ow-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 40px;
        padding: 9px 14px; border: 1px solid transparent; border-radius: 10px;
        font-size: 12px; font-weight: 700; text-decoration: none; transition: .18s ease; white-space: nowrap;
    }
    .owner-show .ow-btn-primary { background: var(--ow-orange); border-color: var(--ow-orange); color: #fff; }
    .owner-show .ow-btn-primary:hover { background: var(--ow-orange-dark); border-color: var(--ow-orange-dark); color: #fff; transform: translateY(-1px); }
    .owner-show .ow-btn-outline { background: #fff; border-color: var(--ow-border); color: var(--ow-navy); }
    .owner-show .ow-btn-outline:hover, .owner-show .ow-btn-outline[aria-expanded="true"] { border-color: #EFC4A8; background: #FFF8F3; color: var(--ow-orange); }

    /* Dropdown menu (small font) */
    .owner-show .action-menu, .owner-show .row-menu {
        min-width: 210px; padding: 6px; border: 1px solid var(--ow-border);
        border-radius: 12px; box-shadow: 0 10px 30px rgba(25, 38, 93, .12); font-size: 11px;
    }
    .owner-show .action-menu .dropdown-item, .owner-show .row-menu .dropdown-item {
        display: flex; align-items: center; gap: 9px; width: 100%; padding: 7px 10px; border: 0;
        border-radius: 8px; background: transparent; color: #35425B; font-size: 11px; font-weight: 600; text-align: left;
    }
    .owner-show .action-menu .dropdown-item i, .owner-show .row-menu .dropdown-item i { font-size: 13px; color: #7B879C; }
    .owner-show .action-menu .dropdown-item:hover, .owner-show .row-menu .dropdown-item:hover { background: #F3F5FA; color: var(--ow-navy); }
    .owner-show .dropdown-item.item-danger, .owner-show .dropdown-item.item-danger i { color: #B42318; }
    .owner-show .dropdown-item.item-danger:hover { background: #FFF0EF; color: #B42318; }
    .owner-show .dropdown-divider { margin: 5px 4px; border-color: #EDF0F5; }

    /* Hero */
    .owner-show .ow-hero {
        position: relative; overflow: hidden; margin-bottom: 20px; padding: 26px; border-radius: 17px;
        background: linear-gradient(115deg, #19265D 0%, #253B7B 67%, #354C91 100%);
        color: #fff; box-shadow: 0 10px 28px rgba(25, 38, 93, .12);
    }
    .owner-show .ow-hero::after {
        position: absolute; top: -85px; right: -40px; width: 245px; height: 245px;
        border: 38px solid rgba(255, 255, 255, .045); border-radius: 50%; content: ""; pointer-events: none;
    }
    .owner-show .ow-hero-inner { position: relative; z-index: 1; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 22px; }
    .owner-show .ow-avatar {
        display: flex; align-items: center; justify-content: center; width: 72px; height: 72px; flex: 0 0 72px;
        border: 2px solid rgba(255, 255, 255, .3); border-radius: 20px; background: rgba(255, 255, 255, .12);
        color: #fff; font-size: 24px; font-weight: 800; letter-spacing: 1px;
    }
    .owner-show .ow-hero-name { margin: 0; color: #fff; font-size: 21px; font-weight: 800; overflow-wrap: anywhere; }
    .owner-show .ow-hero-meta { margin-top: 7px; color: rgba(255, 255, 255, .78); font-size: 12px; }
    .owner-show .ow-hero-meta strong { color: #fff; }
    .owner-show .ow-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
    .owner-show .ow-chip {
        display: inline-flex; align-items: center; gap: 7px; padding: 6px 10px; border: 1px solid rgba(255, 255, 255, .16);
        border-radius: 9px; background: rgba(255, 255, 255, .09); color: #fff; font-size: 11px; font-weight: 600;
        text-decoration: none; max-width: 100%;
    }
    .owner-show .ow-chip:hover { background: rgba(255, 255, 255, .17); color: #fff; }
    .owner-show .ow-chip button { border: 0; background: transparent; color: rgba(255, 255, 255, .75); padding: 0 0 0 4px; font-size: 12px; }
    .owner-show .ow-chip button:hover { color: #fff; }
    .owner-show .ow-hero-divider { width: 1px; min-height: 45px; background: rgba(255, 255, 255, .17); }
    .owner-show .ow-hero-label { margin-bottom: 7px; color: rgba(255, 255, 255, .7); font-size: 11px; }

    /* Badges */
    .owner-show .ow-badge {
        display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border: 1px solid transparent;
        border-radius: 8px; font-size: 11px; font-weight: 800; line-height: 1.3; white-space: nowrap;
    }
    .owner-show .ow-badge::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .owner-show .ow-badge.no-dot::before { display: none; }
    .owner-show .ow-badge.verified, .owner-show .ow-badge.active { border-color: #C8F0DC; background: #E8FAF0; color: #18764B; }
    .owner-show .ow-badge.pending { border-color: #F6E1A7; background: #FFF7DF; color: #936500; }
    .owner-show .ow-badge.inactive, .owner-show .ow-badge.unknown { border-color: #E4E7EC; background: #F2F4F7; color: #667085; }
    .owner-show .ow-badge.rejected, .owner-show .ow-badge.suspended { border-color: #F3CDCD; background: #FFF0F0; color: #B42318; }
    .owner-show .ow-badge.neutral { border-color: rgba(255, 255, 255, .18); background: rgba(255, 255, 255, .11); color: #fff; }

    /* KPI cards */
    .owner-show .ow-kpi {
        display: flex; align-items: center; gap: 13px; height: 100%; min-height: 96px; padding: 16px;
        border: 1px solid var(--ow-border); border-radius: 14px; background: #fff;
        box-shadow: 0 5px 20px rgba(25, 38, 93, .035);
    }
    .owner-show .ow-kpi-icon { display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; flex: 0 0 44px; border-radius: 12px; font-size: 18px; }
    .owner-show .k-orange { background: #FFF0E7; color: #D05208; }
    .owner-show .k-navy   { background: #EDF0FB; color: #19265D; }
    .owner-show .k-green  { background: #E8F7EF; color: #16845B; }
    .owner-show .k-purple { background: #F3ECFF; color: #7652B5; }
    .owner-show .ow-kpi-label { margin-bottom: 5px; color: var(--ow-muted); font-size: 11.5px; font-weight: 700; }
    .owner-show .ow-kpi-value { color: var(--ow-navy); font-size: 20px; font-weight: 800; letter-spacing: -.5px; line-height: 1.15; overflow-wrap: anywhere; }
    .owner-show .ow-kpi-note { margin-top: 4px; color: var(--ow-muted); font-size: 11px; }

    /* Cards */
    .owner-show .ow-card { margin-bottom: 20px; overflow: hidden; border: 1px solid var(--ow-border); border-radius: 15px; background: #fff; box-shadow: 0 5px 24px rgba(25, 38, 93, .04); }
    .owner-show .ow-card-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--ow-border); }
    .owner-show .ow-card-heading { display: flex; align-items: center; gap: 11px; min-width: 0; }
    .owner-show .ow-card-icon { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; flex: 0 0 36px; border-radius: 10px; background: var(--ow-orange-soft); color: var(--ow-orange); font-size: 16px; }
    .owner-show .ow-card-title { margin: 0; color: var(--ow-navy); font-size: 14px; font-weight: 800; }
    .owner-show .ow-card-subtitle { margin-top: 3px; color: var(--ow-muted); font-size: 11px; line-height: 1.5; }
    .owner-show .ow-card-body { padding: 20px; }

    /* Tabs */
    .owner-show .ow-tabs { display: flex; gap: 6px; padding: 6px; margin-bottom: 18px; overflow-x: auto; border: 1px solid var(--ow-border); border-radius: 13px; background: #fff; box-shadow: 0 5px 20px rgba(25, 38, 93, .03); }
    .owner-show .ow-tabs .nav-link {
        display: inline-flex; align-items: center; gap: 8px; padding: 9px 14px; border: 0; border-radius: 9px;
        background: transparent; color: #5B6880; font-size: 12px; font-weight: 700; white-space: nowrap;
    }
    .owner-show .ow-tabs .nav-link:hover { background: #F5F7FB; color: var(--ow-navy); }
    .owner-show .ow-tabs .nav-link.active { background: var(--ow-navy); color: #fff; }
    .owner-show .ow-tabs .tab-count { padding: 0 7px; border-radius: 10px; background: #EEF1F7; color: #5B6880; font-size: 11px; font-weight: 800; }
    .owner-show .ow-tabs .nav-link.active .tab-count { background: rgba(255, 255, 255, .2); color: #fff; }

    /* Detail tiles */
    .owner-show .ow-detail { height: 100%; min-height: 78px; padding: 13px 14px; border: 1px solid #EDF0F5; border-radius: 10px; background: #FCFCFE; }
    .owner-show .ow-detail-label { display: flex; align-items: center; gap: 7px; margin-bottom: 7px; color: var(--ow-muted); font-size: 11px; font-weight: 700; }
    .owner-show .ow-detail-label i { color: var(--ow-orange); font-size: 12px; }
    .owner-show .ow-detail-value { color: #263247; font-size: 12px; font-weight: 700; line-height: 1.65; overflow-wrap: anywhere; }
    .owner-show .ow-detail-value a { color: var(--ow-orange); text-decoration: none; }
    .owner-show .ow-detail-value a:hover { text-decoration: underline; }
    .owner-show .ow-empty { color: #98A2B3; font-weight: 500; font-style: italic; }

    /* Properties table */
    .owner-show .ow-search { position: relative; min-width: 230px; }
    .owner-show .ow-search i { position: absolute; top: 50%; left: 12px; transform: translateY(-50%); color: #8A94A7; font-size: 13px; }
    .owner-show .ow-search input { width: 100%; height: 38px; padding: 0 12px 0 34px; border: 1px solid #E1E5ED; border-radius: 9px; font-size: 12px; outline: 0; }
    .owner-show .ow-search input:focus { border-color: #E8A47E; box-shadow: 0 0 0 3px rgba(208, 82, 8, .08); }

    .owner-show .ow-table-wrap { width: 100%; overflow-x: auto; }
    .owner-show .ow-table { width: 100%; min-width: 760px; margin: 0; border-collapse: separate; border-spacing: 0; }
    .owner-show .ow-table thead th { padding: 12px 16px; border-bottom: 1px solid var(--ow-border); background: #F8F9FC; color: #7B8498; font-size: 11px; font-weight: 800; letter-spacing: .55px; text-transform: uppercase; white-space: nowrap; }
    .owner-show .ow-table tbody td { padding: 14px 16px; border-bottom: 1px solid #EEF0F5; color: #4D586D; font-size: 11px; vertical-align: middle; }
    .owner-show .ow-table tbody tr:last-child td { border-bottom: 0; }
    .owner-show .ow-table tbody tr:hover { background: #FCFCFE; }

    .owner-show .prop-cell { display: flex; align-items: center; gap: 11px; min-width: 210px; }
    .owner-show .prop-thumb { position: relative; width: 46px; height: 46px; flex: 0 0 46px; overflow: hidden; display: flex; align-items: center; justify-content: center; border-radius: 12px; background: #EDF0FB; color: var(--ow-navy); font-size: 18px; }
    .owner-show .prop-thumb img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .owner-show .prop-name { color: var(--ow-navy); font-size: 12px; font-weight: 800; text-decoration: none; }
    .owner-show a.prop-name:hover { color: var(--ow-orange); text-decoration: underline; }
    .owner-show .prop-meta { margin-top: 3px; color: var(--ow-muted); font-size: 11px; }
    .owner-show .prop-code { display: inline-block; margin-top: 4px; padding: 2px 7px; border: 1px solid #E8EBF3; border-radius: 5px; background: #F8F9FC; color: var(--ow-navy); font-size: 11px; font-weight: 800; }

    .owner-show .occ-wrap { min-width: 120px; }
    .owner-show .occ-text { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 11px; font-weight: 700; color: var(--ow-navy); }
    .owner-show .occ-bar { height: 6px; overflow: hidden; border-radius: 6px; background: #EDF0F5; }
    .owner-show .occ-bar span { display: block; height: 100%; border-radius: 6px; background: linear-gradient(90deg, #16845B, #3FB583); }

    .owner-show .row-btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #E3E7EF; border-radius: 8px; background: #fff; color: var(--ow-navy); font-size: 14px; }
    .owner-show .row-btn:hover, .owner-show .row-btn[aria-expanded="true"] { border-color: #F1C4AA; background: #FFF5EF; color: var(--ow-orange); }

    .owner-show .ow-empty-state { padding: 46px 20px; text-align: center; }
    .owner-show .ow-empty-icon { display: flex; align-items: center; justify-content: center; width: 60px; height: 60px; margin: 0 auto 15px; border-radius: 18px; background: var(--ow-orange-soft); color: var(--ow-orange); font-size: 24px; }
    .owner-show .ow-empty-state h3 { color: var(--ow-navy); font-size: 15px; font-weight: 800; }
    .owner-show .ow-empty-state p { max-width: 360px; margin: 7px auto 16px; color: var(--ow-muted); font-size: 12px; line-height: 1.7; }

    .owner-show .ow-table-foot { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px; padding: 13px 20px; border-top: 1px solid var(--ow-border); background: #FCFCFE; color: var(--ow-muted); font-size: 11px; }

    /* Sidebar rows */
    .owner-show .side-row { display: flex; align-items: flex-start; gap: 12px; padding: 13px 0; border-bottom: 1px solid #EEF0F5; }
    .owner-show .side-row:first-child { padding-top: 0; }
    .owner-show .side-row:last-child { padding-bottom: 0; border-bottom: 0; }
    .owner-show .side-icon { display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; flex: 0 0 34px; border-radius: 10px; background: #F1F3FB; color: var(--ow-navy); font-size: 14px; }
    .owner-show .side-label { margin-bottom: 4px; color: var(--ow-muted); font-size: 11px; font-weight: 700; }
    .owner-show .side-value { color: #263247; font-size: 12px; font-weight: 700; line-height: 1.6; overflow-wrap: anywhere; }
    .owner-show .side-value a { color: var(--ow-orange); text-decoration: none; }

    .owner-show .ow-note { padding: 15px; border: 1px solid #F1E5D8; border-radius: 10px; background: #FFFAF5; color: #5F554D; font-size: 12px; line-height: 1.8; white-space: pre-line; overflow-wrap: anywhere; }
    .owner-show .ow-note.is-empty { color: #9A8D81; font-style: italic; }

    .owner-show .act-btn {
        display: flex; align-items: center; gap: 11px; width: 100%; padding: 11px 13px; border: 1px solid var(--ow-border);
        border-radius: 10px; background: #fff; color: var(--ow-navy); font-size: 12px; font-weight: 700; text-align: left; text-decoration: none; transition: .18s ease;
    }
    .owner-show .act-btn + .act-btn { margin-top: 8px; }
    .owner-show .act-btn i:first-child { color: var(--ow-orange); font-size: 15px; }
    .owner-show .act-btn:hover { border-color: #EFC5AC; background: #FFFAF6; color: var(--ow-orange); }
    .owner-show .act-btn.danger, .owner-show .act-btn.danger i:first-child { color: #B42318; }
    .owner-show .act-btn.danger:hover { border-color: #F1C6C2; background: #FFF5F4; }

    .owner-show .ow-record-footer { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px; padding: 13px 18px; border-top: 1px solid var(--ow-border); background: #FCFCFE; color: var(--ow-muted); font-size: 11px; }

    /* Modals */
    .owner-show-modal .modal-content { border: 0; border-radius: 17px; overflow: hidden; }
    .owner-show-modal .modal-header { padding: 18px 22px; border-bottom: 1px solid var(--terra-border, #E7EAF1); }
    .owner-show-modal .modal-title { color: var(--terra-navy, #19265D); font-size: 15px; font-weight: 800; }
    .owner-show-modal .modal-body { padding: 22px; font-size: 13px; color: #35425B; }
    .owner-show-modal .modal-footer { padding: 14px 22px; border-top: 1px solid var(--terra-border, #E7EAF1); }
    .owner-show-modal .m-icon { display: flex; align-items: center; justify-content: center; width: 58px; height: 58px; margin: 0 auto 14px; border-radius: 50%; font-size: 24px; }
    .owner-show-modal .m-icon.orange { background: #FFF0E7; color: #D05208; }
    .owner-show-modal .m-icon.red { background: #FFF0F0; color: #B42318; }
    .owner-show-modal .m-btn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 15px; border-radius: 9px; border: 1px solid transparent; font-size: 12px; font-weight: 700; text-decoration: none; }
    .owner-show-modal .m-btn-primary { background: #D05208; color: #fff; border-color: #D05208; }
    .owner-show-modal .m-btn-primary:hover { background: #AD4307; color: #fff; }
    .owner-show-modal .m-btn-danger { background: #B42318; color: #fff; border-color: #B42318; }
    .owner-show-modal .m-btn-danger:hover { background: #912018; color: #fff; }
    .owner-show-modal .m-btn-outline { background: #fff; color: #19265D; border-color: #DDE2EC; }
    .owner-show-modal .m-btn-outline:hover { background: #F5F7FB; }
    .owner-show-modal .contact-row { display: flex; align-items: center; gap: 12px; padding: 13px; border: 1px solid #E7EAF1; border-radius: 11px; }
    .owner-show-modal .contact-row + .contact-row { margin-top: 10px; }
    .owner-show-modal .contact-row .c-icon { display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 10px; background: #FFF0E7; color: #D05208; font-size: 16px; flex-shrink: 0; }
    .owner-show-modal .contact-row .c-label { color: #778197; font-size: 11px; font-weight: 700; }
    .owner-show-modal .contact-row .c-value { color: #19265D; font-size: 12.5px; font-weight: 700; overflow-wrap: anywhere; }
    .owner-show-modal .c-actions { margin-left: auto; display: flex; gap: 6px; }
    .owner-show-modal .c-actions a, .owner-show-modal .c-actions button { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #E3E7EF; border-radius: 8px; background: #fff; color: #19265D; font-size: 13px; text-decoration: none; }
    .owner-show-modal .c-actions a:hover, .owner-show-modal .c-actions button:hover { border-color: #F1C4AA; background: #FFF5EF; color: #D05208; }

    @media (max-width: 767.98px) {
        .owner-show .ow-hero { padding: 20px; }
        .owner-show .ow-avatar { width: 58px; height: 58px; flex-basis: 58px; border-radius: 16px; font-size: 20px; }
        .owner-show .ow-hero-name { font-size: 18px; }
        .owner-show .ow-card-header, .owner-show .ow-card-body { padding: 16px; }
        .owner-show .page-intro > div:last-child { width: 100%; }
    }

    @media print {
        .owner-show .page-intro > div:last-child, .owner-show .breadcrumb-line, .owner-show .ow-tabs, .owner-show .act-card, .owner-show .ow-search, .owner-show .row-btn { display: none !important; }
        .owner-show .tab-pane { display: block !important; opacity: 1 !important; }
        .owner-show .ow-hero, .owner-show .ow-card { break-inside: avoid; box-shadow: none; }
    }
</style>
@endpush

@section('content')
@php
    $DB = \Illuminate\Support\Facades\DB::class;

    $ownerName = $owner->name ?: 'Unnamed owner';
    $ownerInitials = collect(preg_split('/\s+/', trim($ownerName)))->filter()->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');

    $verification = strtolower((string) ($owner->verification_status ?: 'pending'));
    $status       = strtolower((string) ($owner->status ?: 'inactive'));
    $verificationClass = in_array($verification, ['pending', 'verified', 'rejected'], true) ? $verification : 'unknown';
    $statusClass       = in_array($status, ['active', 'inactive', 'suspended'], true) ? $status : 'unknown';
    $ownerType = $owner->owner_type ? ucwords(str_replace(['_', '-'], ' ', $owner->owner_type)) : 'Individual';
    $isCompany = ($owner->owner_type ?? '') === 'company';

    $account = $owner->user;
    $creator = $owner->creator;

    $formatDate = fn ($date, $format = 'd M Y') => $date ? \Illuminate\Support\Carbon::parse($date)->format($format) : null;
    $money = fn ($v) => number_format((float) $v, 0) . ' RWF';

    /* ---------- Properties owned (single set of queries) ---------- */
    $properties = $DB::table('properties')
        ->where('property_owner_id', $owner->id)
        ->whereNull('deleted_at')
        ->orderBy('name')
        ->get();
    $propIds = $properties->pluck('id')->all();

    $unitStats = $DB::table('units')
        ->join('buildings', 'buildings.id', '=', 'units.building_id')
        ->whereIn('buildings.property_id', $propIds)
        ->whereNull('units.deleted_at')
        ->whereNull('buildings.deleted_at')
        ->groupBy('buildings.property_id')
        ->selectRaw("buildings.property_id as property_id, COUNT(units.id) as total, SUM(CASE WHEN units.status = 'occupied' THEN 1 ELSE 0 END) as occupied, SUM(CASE WHEN units.status = 'occupied' THEN units.rent_amount ELSE 0 END) as rent_roll")
        ->get()->keyBy('property_id');

    $buildingCounts = $DB::table('buildings')
        ->whereIn('property_id', $propIds)->whereNull('deleted_at')
        ->selectRaw('property_id, COUNT(*) as total')->groupBy('property_id')
        ->pluck('total', 'property_id');

    $managersByProperty = $DB::table('property_manager_assignments as a')
        ->join('property_managers as m', 'm.id', '=', 'a.property_manager_id')
        ->join('users as u', 'u.id', '=', 'm.user_id')
        ->whereIn('a.property_id', $propIds)
        ->where('a.status', 'active')
        ->select('a.property_id', 'm.id as manager_id', 'u.name')
        ->get()->groupBy('property_id');

    $primaryImages = $DB::table('property_images')
        ->whereIn('property_id', $propIds)->where('is_primary', 1)
        ->pluck('image_path', 'property_id');

    $totalExpenses = $DB::table('property_expenses')
        ->whereIn('property_id', $propIds)->whereNull('deleted_at')->where('currency', 'RWF')
        ->sum('amount');

    $totalUnits    = (int) $unitStats->sum('total');
    $occupiedUnits = (int) $unitStats->sum('occupied');
    $rentRoll      = (float) $unitStats->sum('rent_roll');
    $occupancyRate = $totalUnits > 0 ? round($occupiedUnits / $totalUnits * 100) : 0;

    $canShowProperty = \Illuminate\Support\Facades\Route::has('admin.properties.show');
    $canEditProperty = \Illuminate\Support\Facades\Route::has('admin.properties.edit');
    $canCreateProperty = \Illuminate\Support\Facades\Route::has('admin.properties.create');
@endphp

<div class="owner-show">

    {{-- Page heading --}}
    <div class="page-intro">
        <div>
            <div class="breadcrumb-line">
                <a href="{{ \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/') }}">Dashboard</a>
                <i class="bi bi-chevron-right"></i>
                <a href="{{ route('admin.owners.index') }}">Property Owners</a>
                <i class="bi bi-chevron-right"></i>
                <span>Owner profile</span>
            </div>

            <h1 class="page-title">Property owner profile</h1>
            <p class="page-description">
                View registration details, owned properties, account access and administrative records.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.owners.index') }}" class="ow-btn ow-btn-outline">
                <i class="bi bi-arrow-left"></i> Back
            </a>

            <a href="{{ route('admin.owners.edit', $owner) }}" class="ow-btn ow-btn-primary">
                <i class="bi bi-pencil-square"></i> Edit owner
            </a>

            <div class="dropdown">
                <button type="button" class="ow-btn ow-btn-outline" data-bs-toggle="dropdown"
                        data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                    <i class="bi bi-three-dots"></i> Actions
                </button>

                <ul class="dropdown-menu dropdown-menu-end action-menu">
                    <li>
                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#contactOwnerModal">
                            <i class="bi bi-chat-dots"></i> Contact owner
                        </button>
                    </li>
                    @if($owner->user_id)
                        <li>
                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#resendCredentialsModal">
                                <i class="bi bi-envelope-arrow-up"></i> Resend credentials
                            </button>
                        </li>
                    @endif
                    <li>
                        <button type="button" class="dropdown-item" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print profile
                        </button>
                    </li>
                    @if($status === 'active')
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <button type="button" class="dropdown-item item-danger" data-bs-toggle="modal" data-bs-target="#deactivateOwnerModal">
                                <i class="bi bi-person-dash"></i> Deactivate owner
                            </button>
                        </li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <x-purge-button type="owner" :id="$owner->id" what="this owner, their properties"
                                        class="dropdown-item item-danger" />
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Hero --}}
    <section class="ow-hero">
        <div class="ow-hero-inner">
            <div class="d-flex align-items-center gap-3">
                <div class="ow-avatar">
                    @if($isCompany) <i class="bi bi-building"></i> @else {{ $ownerInitials ?: 'O' }} @endif
                </div>

                <div class="min-w-0">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h2 class="ow-hero-name">{{ $ownerName }}</h2>
                        <span class="ow-badge neutral no-dot"><i class="bi bi-person-badge"></i> {{ $ownerType }}</span>
                    </div>

                    <div class="ow-hero-meta">
                        Owner reference: <strong>{{ $owner->owner_number ?: 'Not assigned' }}</strong>
                        @if(filled($owner->company_name)) &middot; {{ $owner->company_name }} @endif
                    </div>

                    <div class="ow-chips">
                        @if(filled($owner->email))
                            <span class="ow-chip">
                                <i class="bi bi-envelope"></i> {{ $owner->email }}
                                <button type="button" data-copy="{{ $owner->email }}" title="Copy email" aria-label="Copy email"><i class="bi bi-clipboard"></i></button>
                            </span>
                        @endif
                        @if(filled($owner->phone))
                            <span class="ow-chip">
                                <i class="bi bi-telephone"></i> {{ $owner->phone }}
                                <button type="button" data-copy="{{ $owner->phone }}" title="Copy phone" aria-label="Copy phone"><i class="bi bi-clipboard"></i></button>
                            </span>
                        @endif
                        @if(filled($owner->district))
                            <span class="ow-chip"><i class="bi bi-geo-alt"></i> {{ collect([$owner->district, $owner->province])->filter()->implode(', ') }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-3">
                <div>
                    <div class="ow-hero-label">Verification</div>
                    <span class="ow-badge {{ $verificationClass }}">{{ ucfirst($verification) }}</span>
                </div>
                <div class="ow-hero-divider d-none d-md-block"></div>
                <div>
                    <div class="ow-hero-label">Account status</div>
                    <span class="ow-badge {{ $statusClass }}">{{ ucfirst($status) }}</span>
                </div>
            </div>
        </div>
    </section>

    {{-- KPI cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="ow-kpi">
                <div class="ow-kpi-icon k-orange"><i class="bi bi-buildings"></i></div>
                <div>
                    <div class="ow-kpi-label">Properties</div>
                    <div class="ow-kpi-value">{{ number_format($properties->count()) }}</div>
                    <div class="ow-kpi-note">Registered under this owner</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="ow-kpi">
                <div class="ow-kpi-icon k-navy"><i class="bi bi-door-open"></i></div>
                <div>
                    <div class="ow-kpi-label">Units &amp; occupancy</div>
                    <div class="ow-kpi-value">{{ $occupiedUnits }}/{{ $totalUnits }}</div>
                    <div class="ow-kpi-note">{{ $occupancyRate }}% occupied</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="ow-kpi">
                <div class="ow-kpi-icon k-green"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="ow-kpi-label">Monthly rent roll</div>
                    <div class="ow-kpi-value" style="font-size:16px;">{{ $money($rentRoll) }}</div>
                    <div class="ow-kpi-note">From occupied units</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="ow-kpi">
                <div class="ow-kpi-icon k-purple"><i class="bi bi-wallet2"></i></div>
                <div>
                    <div class="ow-kpi-label">Recorded expenses</div>
                    <div class="ow-kpi-value" style="font-size:16px;">{{ $money($totalExpenses) }}</div>
                    <div class="ow-kpi-note">All properties combined</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- ============ MAIN COLUMN ============ --}}
        <div class="col-12 col-xl-8">

            <ul class="nav ow-tabs" id="ownerTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-overview" data-bs-toggle="pill" data-bs-target="#pane-overview" type="button" role="tab">
                        <i class="bi bi-person-vcard"></i> Overview
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-properties" data-bs-toggle="pill" data-bs-target="#pane-properties" type="button" role="tab">
                        <i class="bi bi-buildings"></i> Properties <span class="tab-count">{{ $properties->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-notes" data-bs-toggle="pill" data-bs-target="#pane-notes" type="button" role="tab">
                        <i class="bi bi-journal-text"></i> Notes &amp; audit
                    </button>
                </li>
            </ul>

            <div class="tab-content">

                {{-- ---------- Overview ---------- --}}
                <div class="tab-pane fade show active" id="pane-overview" role="tabpanel" tabindex="0">

                    <section class="ow-card">
                        <div class="ow-card-header">
                            <div class="ow-card-heading">
                                <div class="ow-card-icon"><i class="bi bi-person-vcard"></i></div>
                                <div>
                                    <h2 class="ow-card-title">Personal &amp; business information</h2>
                                    <div class="ow-card-subtitle">Owner identity and registration details</div>
                                </div>
                            </div>
                            <span class="ow-badge unknown no-dot">{{ $ownerType }}</span>
                        </div>

                        <div class="ow-card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="ow-detail">
                                        <div class="ow-detail-label"><i class="bi bi-person"></i> Full name / contact person</div>
                                        <div class="ow-detail-value">{{ $owner->name ?: 'Not provided' }}</div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="ow-detail">
                                        <div class="ow-detail-label"><i class="bi bi-building"></i> Company / organization</div>
                                        <div class="ow-detail-value {{ filled($owner->company_name) ? '' : 'ow-empty' }}">{{ $owner->company_name ?: 'Not provided' }}</div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="ow-detail">
                                        <div class="ow-detail-label"><i class="bi bi-card-text"></i> National ID / reference</div>
                                        <div class="ow-detail-value {{ filled($owner->national_id) ? '' : 'ow-empty' }}">{{ $owner->national_id ?: 'Not provided' }}</div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="ow-detail">
                                        <div class="ow-detail-label"><i class="bi bi-hash"></i> Internal owner ID</div>
                                        <div class="ow-detail-value">#{{ $owner->id }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="ow-card">
                        <div class="ow-card-header">
                            <div class="ow-card-heading">
                                <div class="ow-card-icon"><i class="bi bi-telephone"></i></div>
                                <div>
                                    <h2 class="ow-card-title">Contact information</h2>
                                    <div class="ow-card-subtitle">Communication channels for the property owner</div>
                                </div>
                            </div>
                            <button type="button" class="ow-btn ow-btn-outline" style="min-height:34px;padding:6px 12px;" data-bs-toggle="modal" data-bs-target="#contactOwnerModal">
                                <i class="bi bi-chat-dots"></i> Contact
                            </button>
                        </div>

                        <div class="ow-card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="ow-detail">
                                        <div class="ow-detail-label"><i class="bi bi-envelope"></i> Email address</div>
                                        <div class="ow-detail-value">
                                            @if(filled($owner->email)) <a href="mailto:{{ $owner->email }}">{{ $owner->email }}</a>
                                            @else <span class="ow-empty">Not provided</span> @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="ow-detail">
                                        <div class="ow-detail-label"><i class="bi bi-telephone"></i> Phone number</div>
                                        <div class="ow-detail-value">
                                            @if(filled($owner->phone)) <a href="tel:{{ $owner->phone }}">{{ $owner->phone }}</a>
                                            @else <span class="ow-empty">Not provided</span> @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="ow-card">
                        <div class="ow-card-header">
                            <div class="ow-card-heading">
                                <div class="ow-card-icon"><i class="bi bi-geo-alt"></i></div>
                                <div>
                                    <h2 class="ow-card-title">Address &amp; location</h2>
                                    <div class="ow-card-subtitle">Registered address and geographical information</div>
                                </div>
                            </div>
                        </div>

                        <div class="ow-card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="ow-detail">
                                        <div class="ow-detail-label"><i class="bi bi-globe"></i> Country</div>
                                        <div class="ow-detail-value {{ filled($owner->country) ? '' : 'ow-empty' }}">{{ $owner->country ?: 'Not provided' }}</div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="ow-detail">
                                        <div class="ow-detail-label"><i class="bi bi-map"></i> Province / region</div>
                                        <div class="ow-detail-value {{ filled($owner->province) ? '' : 'ow-empty' }}">{{ $owner->province ?: 'Not provided' }}</div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="ow-detail">
                                        <div class="ow-detail-label"><i class="bi bi-geo"></i> District</div>
                                        <div class="ow-detail-value {{ filled($owner->district) ? '' : 'ow-empty' }}">{{ $owner->district ?: 'Not provided' }}</div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="ow-detail">
                                        <div class="ow-detail-label"><i class="bi bi-signpost-2"></i> Physical address</div>
                                        <div class="ow-detail-value {{ filled($owner->address) ? '' : 'ow-empty' }}">{{ $owner->address ?: 'Not provided' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- ---------- Properties ---------- --}}
                <div class="tab-pane fade" id="pane-properties" role="tabpanel" tabindex="0">
                    <section class="ow-card">
                        <div class="ow-card-header">
                            <div class="ow-card-heading">
                                <div class="ow-card-icon"><i class="bi bi-buildings"></i></div>
                                <div>
                                    <h2 class="ow-card-title">Properties owned</h2>
                                    <div class="ow-card-subtitle">{{ $properties->count() }} {{ \Illuminate\Support\Str::plural('property', $properties->count()) }} registered under {{ $ownerName }}</div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                @if($properties->count() > 4)
                                    <div class="ow-search">
                                        <i class="bi bi-search"></i>
                                        <input type="search" id="propertyFilter" placeholder="Filter properties..." aria-label="Filter properties">
                                    </div>
                                @endif

                                @if($canCreateProperty)
                                    <a href="{{ route('admin.properties.create') }}" class="ow-btn ow-btn-primary" style="min-height:38px;padding:7px 13px;">
                                        <i class="bi bi-plus-lg"></i> Add property
                                    </a>
                                @endif
                            </div>
                        </div>

                        @if($properties->isEmpty())
                            <div class="ow-empty-state">
                                <div class="ow-empty-icon"><i class="bi bi-buildings"></i></div>
                                <h3>No properties yet</h3>
                                <p>This owner has no registered properties. Add a property to start managing buildings, units and tenants.</p>
                                @if($canCreateProperty)
                                    <a href="{{ route('admin.properties.create') }}" class="ow-btn ow-btn-primary"><i class="bi bi-plus-lg"></i> Add first property</a>
                                @endif
                            </div>
                        @else
                            <div class="ow-table-wrap">
                                <table class="ow-table" id="propertyTable">
                                    <thead>
                                        <tr>
                                            <th>Property</th>
                                            <th>Location</th>
                                            <th>Units &amp; occupancy</th>
                                            <th>Manager</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach($properties as $property)
                                            @php
                                                $stat = $unitStats[$property->id] ?? null;
                                                $pUnits = (int) ($stat->total ?? 0);
                                                $pOcc   = (int) ($stat->occupied ?? 0);
                                                $pRate  = $pUnits > 0 ? round($pOcc / $pUnits * 100) : 0;
                                                $pBuild = (int) ($buildingCounts[$property->id] ?? 0);
                                                $pManagers = $managersByProperty[$property->id] ?? collect();
                                                $pStatus = strtolower($property->status ?? 'inactive');
                                                $pStatusClass = in_array($pStatus, ['active', 'inactive', 'suspended'], true) ? $pStatus : 'unknown';
                                                $pType = ucwords(str_replace('_', ' ', $property->property_type ?? 'property'));
                                                $pLocation = collect([$property->sector, $property->district])->filter()->implode(', ');
                                                $pImage = $primaryImages[$property->id] ?? null;
                                            @endphp

                                            <tr data-search="{{ strtolower($property->name . ' ' . $property->property_code . ' ' . $pLocation . ' ' . $pType) }}">
                                                <td>
                                                    <div class="prop-cell">
                                                        <div class="prop-thumb">
                                                            <i class="bi bi-building"></i>
                                                            @if($pImage)
                                                                <img src="{{ asset($pImage) }}" alt="" loading="lazy" onerror="this.remove()">
                                                            @endif
                                                        </div>
                                                        <div>
                                                            @if($canShowProperty)
                                                                <a href="{{ route('admin.properties.show', $property->id) }}" class="prop-name">{{ $property->name }}</a>
                                                            @else
                                                                <span class="prop-name">{{ $property->name }}</span>
                                                            @endif
                                                            <div class="prop-meta">{{ $pType }}@if($property->year_built) &middot; Built {{ $property->year_built }}@endif</div>
                                                            <span class="prop-code">{{ $property->property_code }}</span>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td>
                                                    <div style="font-weight:700;color:#35425B;">{{ $pLocation ?: 'Not specified' }}</div>
                                                    <div class="prop-meta">{{ $property->province ?: '' }}</div>
                                                </td>

                                                <td>
                                                    <div class="occ-wrap">
                                                        <div class="occ-text">
                                                            <span>{{ $pOcc }}/{{ $pUnits }} {{ $pUnits === 1 ? 'unit' : 'units' }}</span>
                                                            <span>{{ $pRate }}%</span>
                                                        </div>
                                                        <div class="occ-bar"><span style="width: {{ $pRate }}%"></span></div>
                                                        <div class="prop-meta">{{ $pBuild }} {{ $pBuild === 1 ? 'building' : 'buildings' }}</div>
                                                    </div>
                                                </td>

                                                <td>
                                                    @forelse($pManagers as $manager)
                                                        <div style="font-weight:700;color:#35425B;">{{ $manager->name }}</div>
                                                    @empty
                                                        <span class="ow-empty">Unassigned</span>
                                                    @endforelse
                                                </td>

                                                <td><span class="ow-badge {{ $pStatusClass }}">{{ ucfirst($pStatus) }}</span></td>

                                                <td class="text-end">
                                                    <div class="dropdown">
                                                        <button type="button" class="row-btn" data-bs-toggle="dropdown"
                                                                data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false"
                                                                aria-label="Actions for {{ $property->name }}">
                                                            <i class="bi bi-three-dots-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end row-menu">
                                                            @if($canShowProperty)
                                                                <li><a class="dropdown-item" href="{{ route('admin.properties.show', $property->id) }}"><i class="bi bi-eye"></i> View property</a></li>
                                                            @endif
                                                            @if($canEditProperty)
                                                                <li><a class="dropdown-item" href="{{ route('admin.properties.edit', $property->id) }}"><i class="bi bi-pencil-square"></i> Edit property</a></li>
                                                            @endif
                                                            @if(!$canShowProperty && !$canEditProperty)
                                                                <li><span class="dropdown-item-text text-muted">No actions available</span></li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach

                                        <tr id="propertyNoMatch" style="display:none;">
                                            <td colspan="6" class="text-center text-muted py-4">No properties match your filter.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="ow-table-foot">
                                <span>{{ $properties->count() }} {{ \Illuminate\Support\Str::plural('property', $properties->count()) }} &middot; {{ $totalUnits }} {{ \Illuminate\Support\Str::plural('unit', $totalUnits) }}</span>
                                <span>Rent roll: <strong>{{ $money($rentRoll) }}</strong> / month</span>
                            </div>
                        @endif
                    </section>
                </div>

                {{-- ---------- Notes & audit ---------- --}}
                <div class="tab-pane fade" id="pane-notes" role="tabpanel" tabindex="0">
                    <section class="ow-card">
                        <div class="ow-card-header">
                            <div class="ow-card-heading">
                                <div class="ow-card-icon"><i class="bi bi-journal-text"></i></div>
                                <div>
                                    <h2 class="ow-card-title">Internal notes</h2>
                                    <div class="ow-card-subtitle">Administrative remarks recorded for this owner</div>
                                </div>
                            </div>
                        </div>
                        <div class="ow-card-body">
                            <div class="ow-note {{ filled($owner->notes) ? '' : 'is-empty' }}">{{ filled($owner->notes) ? $owner->notes : 'No internal notes have been recorded for this owner.' }}</div>
                        </div>
                    </section>

                    <section class="ow-card">
                        <div class="ow-card-header">
                            <div class="ow-card-heading">
                                <div class="ow-card-icon"><i class="bi bi-clock-history"></i></div>
                                <div>
                                    <h2 class="ow-card-title">Registration audit</h2>
                                    <div class="ow-card-subtitle">Record ownership and timestamps</div>
                                </div>
                            </div>
                        </div>

                        <div class="ow-card-body">
                            <div class="side-row">
                                <div class="side-icon"><i class="bi bi-person-plus"></i></div>
                                <div>
                                    <div class="side-label">Registered by</div>
                                    <div class="side-value">{{ $creator?->name ?: 'System / not recorded' }}</div>
                                    @if($creator?->email) <div class="ow-card-subtitle">{{ $creator->email }}</div> @endif
                                </div>
                            </div>
                            <div class="side-row">
                                <div class="side-icon"><i class="bi bi-calendar-plus"></i></div>
                                <div>
                                    <div class="side-label">Created at</div>
                                    <div class="side-value">{{ $formatDate($owner->created_at, 'd M Y, H:i') ?: 'Not available' }}</div>
                                </div>
                            </div>
                            <div class="side-row">
                                <div class="side-icon"><i class="bi bi-pencil"></i></div>
                                <div>
                                    <div class="side-label">Last updated</div>
                                    <div class="side-value">{{ $formatDate($owner->updated_at, 'd M Y, H:i') ?: 'Not available' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="ow-record-footer">
                            <span>Record ID: #{{ $owner->id }}</span>
                            <span>Terra Smart Rental Management</span>
                        </div>
                    </section>
                </div>
            </div>
            {{-- Documents & contracts --}}
            @include('documents._profile-panel', [
                'owner' => $owner,
                'ownerKey' => 'owner',
                'documents' => $owner->documents()->with('uploader:id,name')->latest()->get(),
                'canUpload' => true,
                'heading' => 'Owner documents & contracts',
            ])
        </div>

        {{-- ============ SIDEBAR ============ --}}
        <div class="col-12 col-xl-4">

            {{-- Linked account --}}
            <section class="ow-card">
                <div class="ow-card-header">
                    <div class="ow-card-heading">
                        <div class="ow-card-icon"><i class="bi bi-person-lock"></i></div>
                        <div>
                            <h2 class="ow-card-title">Linked login account</h2>
                            <div class="ow-card-subtitle">Terra platform access information</div>
                        </div>
                    </div>
                </div>

                <div class="ow-card-body">
                    @if($account)
                        <div class="side-row">
                            <div class="side-icon"><i class="bi bi-person"></i></div>
                            <div>
                                <div class="side-label">Account name</div>
                                <div class="side-value">{{ $account->name ?: 'Unnamed user' }} <span class="text-muted">(#{{ $account->id }})</span></div>
                            </div>
                        </div>
                        <div class="side-row">
                            <div class="side-icon"><i class="bi bi-envelope"></i></div>
                            <div>
                                <div class="side-label">Login email</div>
                                <div class="side-value">
                                    @if(filled($account->email)) <a href="mailto:{{ $account->email }}">{{ $account->email }}</a> @else Not provided @endif
                                </div>
                            </div>
                        </div>
                        <div class="side-row">
                            <div class="side-icon"><i class="bi bi-person-badge"></i></div>
                            <div>
                                <div class="side-label">Assigned role</div>
                                <div class="side-value">{{ $account->role ? ucwords(str_replace('_', ' ', $account->role)) : 'Not specified' }}</div>
                            </div>
                        </div>
                        <div class="side-row">
                            <div class="side-icon"><i class="bi bi-patch-check"></i></div>
                            <div>
                                <div class="side-label">Email verification</div>
                                <div class="side-value">
                                    @if($account->email_verified_at)
                                        <span class="ow-badge verified">Verified</span>
                                        <div class="ow-card-subtitle mt-2">{{ $formatDate($account->email_verified_at, 'd M Y, H:i') }}</div>
                                    @else
                                        <span class="ow-badge pending">Not verified</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-2">
                            <div class="ow-kpi-icon k-navy mx-auto mb-3"><i class="bi bi-person-x"></i></div>
                            <div class="ow-card-title mb-2">No linked login account</div>
                            <p class="ow-card-subtitle mb-0">This owner is not linked to a user account, so they cannot sign in to the platform.</p>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Status --}}
            <section class="ow-card">
                <div class="ow-card-header">
                    <div class="ow-card-heading">
                        <div class="ow-card-icon"><i class="bi bi-shield-check"></i></div>
                        <div>
                            <h2 class="ow-card-title">Verification &amp; status</h2>
                            <div class="ow-card-subtitle">Current administrative standing</div>
                        </div>
                    </div>
                </div>

                <div class="ow-card-body">
                    <div class="side-row">
                        <div class="side-icon"><i class="bi bi-shield-check"></i></div>
                        <div>
                            <div class="side-label">Verification status</div>
                            <div class="side-value"><span class="ow-badge {{ $verificationClass }}">{{ ucfirst($verification) }}</span></div>
                        </div>
                    </div>
                    <div class="side-row">
                        <div class="side-icon"><i class="bi bi-toggle-on"></i></div>
                        <div>
                            <div class="side-label">Account status</div>
                            <div class="side-value"><span class="ow-badge {{ $statusClass }}">{{ ucfirst($status) }}</span></div>
                        </div>
                    </div>
                    <div class="side-row">
                        <div class="side-icon"><i class="bi bi-calendar-event"></i></div>
                        <div>
                            <div class="side-label">Date registered</div>
                            <div class="side-value">{{ $formatDate($owner->created_at, 'd M Y, H:i') ?: 'Not available' }}</div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Quick actions (open modals) --}}
            <section class="ow-card act-card">
                <div class="ow-card-header">
                    <div class="ow-card-heading">
                        <div class="ow-card-icon"><i class="bi bi-lightning-charge"></i></div>
                        <div>
                            <h2 class="ow-card-title">Quick actions</h2>
                            <div class="ow-card-subtitle">Manage this owner record</div>
                        </div>
                    </div>
                </div>

                <div class="ow-card-body">
                    <a href="{{ route('admin.owners.edit', $owner) }}" class="act-btn">
                        <i class="bi bi-pencil-square"></i><span>Edit owner information</span><i class="bi bi-chevron-right ms-auto"></i>
                    </a>

                    <button type="button" class="act-btn" data-bs-toggle="modal" data-bs-target="#contactOwnerModal">
                        <i class="bi bi-chat-dots"></i><span>Contact owner</span><i class="bi bi-chevron-right ms-auto"></i>
                    </button>

                    @if($owner->user_id)
                        <button type="button" class="act-btn" data-bs-toggle="modal" data-bs-target="#resendCredentialsModal">
                            <i class="bi bi-envelope-arrow-up"></i><span>Resend login credentials</span><i class="bi bi-chevron-right ms-auto"></i>
                        </button>
                    @endif

                    <a href="{{ route('admin.owners.index') }}" class="act-btn">
                        <i class="bi bi-people"></i><span>View all property owners</span><i class="bi bi-chevron-right ms-auto"></i>
                    </a>

                    @if($status === 'active')
                        <button type="button" class="act-btn danger" data-bs-toggle="modal" data-bs-target="#deactivateOwnerModal">
                            <i class="bi bi-person-dash"></i><span>Deactivate owner</span><i class="bi bi-chevron-right ms-auto"></i>
                        </button>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>

{{-- ================= MODALS ================= --}}

{{-- Contact owner --}}
<div class="modal fade owner-show-modal" id="contactOwnerModal" tabindex="-1" aria-labelledby="contactOwnerLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contactOwnerLabel">Contact {{ $ownerName }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                @if(filled($owner->email) || filled($owner->phone))
                    @if(filled($owner->email))
                        <div class="contact-row">
                            <div class="c-icon"><i class="bi bi-envelope"></i></div>
                            <div>
                                <div class="c-label">Email</div>
                                <div class="c-value">{{ $owner->email }}</div>
                            </div>
                            <div class="c-actions">
                                <button type="button" data-copy="{{ $owner->email }}" title="Copy" aria-label="Copy email"><i class="bi bi-clipboard"></i></button>
                                <a href="mailto:{{ $owner->email }}" title="Send email" aria-label="Send email"><i class="bi bi-send"></i></a>
                            </div>
                        </div>
                    @endif

                    @if(filled($owner->phone))
                        <div class="contact-row">
                            <div class="c-icon"><i class="bi bi-telephone"></i></div>
                            <div>
                                <div class="c-label">Phone</div>
                                <div class="c-value">{{ $owner->phone }}</div>
                            </div>
                            <div class="c-actions">
                                <button type="button" data-copy="{{ $owner->phone }}" title="Copy" aria-label="Copy phone"><i class="bi bi-clipboard"></i></button>
                                <a href="tel:{{ $owner->phone }}" title="Call" aria-label="Call"><i class="bi bi-telephone-outbound"></i></a>
                                <a href="https://wa.me/{{ preg_replace('/\D/', '', $owner->phone) }}" target="_blank" rel="noopener" title="WhatsApp" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-3">
                        <div class="m-icon orange"><i class="bi bi-person-x"></i></div>
                        <p class="mb-0 text-muted">No email address or phone number is recorded for this owner. Edit the owner to add contact details.</p>
                    </div>
                @endif
            </div>

            <div class="modal-footer">
                <button type="button" class="m-btn m-btn-outline" data-bs-dismiss="modal">Close</button>
                <a href="{{ route('admin.owners.edit', $owner) }}" class="m-btn m-btn-primary"><i class="bi bi-pencil-square"></i> Edit contact details</a>
            </div>
        </div>
    </div>
</div>

@if($owner->user_id)
    {{-- Resend credentials --}}
    <div class="modal fade owner-show-modal" id="resendCredentialsModal" tabindex="-1" aria-labelledby="resendCredentialsLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <form method="POST" action="{{ route('admin.owners.resend-credentials', $owner) }}" class="modal-content">
                @csrf

                <div class="modal-body text-center py-4">
                    <div class="m-icon orange"><i class="bi bi-envelope-arrow-up"></i></div>
                    <h5 class="fw-bold mb-2" id="resendCredentialsLabel" style="color:#19265D;">Resend credentials?</h5>
                    <p class="mb-1 text-secondary">A new password will be generated and emailed to</p>
                    <p class="fw-bold mb-0" style="overflow-wrap:anywhere;">{{ $owner->email ?: $account?->email }}</p>
                    <p class="small text-muted mt-2 mb-0">Their current password will stop working.</p>
                </div>

                <div class="modal-footer justify-content-center">
                    <button type="button" class="m-btn m-btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="m-btn m-btn-primary"><i class="bi bi-send"></i> Send credentials</button>
                </div>
            </form>
        </div>
    </div>
@endif

@if($status === 'active')
    {{-- Deactivate owner --}}
    <div class="modal fade owner-show-modal" id="deactivateOwnerModal" tabindex="-1" aria-labelledby="deactivateOwnerLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <form method="POST" action="{{ route('admin.owners.destroy', $owner) }}" class="modal-content">
                @csrf
                @method('DELETE')

                <div class="modal-body text-center py-4">
                    <div class="m-icon red"><i class="bi bi-person-dash"></i></div>
                    <h5 class="fw-bold mb-2" id="deactivateOwnerLabel" style="color:#19265D;">Deactivate owner?</h5>
                    <p class="mb-1 text-secondary">You are about to deactivate <strong>{{ $ownerName }}</strong>.</p>
                    @if($properties->count() > 0)
                        <p class="small text-danger mb-0">This owner has {{ $properties->count() }} {{ \Illuminate\Support\Str::plural('property', $properties->count()) }} on record. Review them before continuing.</p>
                    @else
                        <p class="small text-muted mb-0">The owner will no longer be able to use the platform.</p>
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

    /* Property filter */
    const filter = document.getElementById('propertyFilter');
    if (filter) {
        const rows = document.querySelectorAll('#propertyTable tbody tr[data-search]');
        const none = document.getElementById('propertyNoMatch');
        filter.addEventListener('input', function () {
            const q = filter.value.trim().toLowerCase();
            let visible = 0;
            rows.forEach(function (row) {
                const show = !q || row.dataset.search.includes(q);
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            if (none) none.style.display = visible ? 'none' : '';
        });
    }

    /* Remember the active tab through the URL hash */
    const hash = window.location.hash;
    if (hash) {
        const trigger = document.querySelector('#ownerTabs [data-bs-target="' + hash + '"]');
        if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
    }
    document.querySelectorAll('#ownerTabs [data-bs-toggle="pill"]').forEach(function (tab) {
        tab.addEventListener('shown.bs.tab', function (e) {
            history.replaceState(null, '', e.target.dataset.bsTarget);
        });
    });
});
</script>
@endpush