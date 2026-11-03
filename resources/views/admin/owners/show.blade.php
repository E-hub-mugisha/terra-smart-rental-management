@extends('layouts.app')

@section('title', 'Property Owner Profile | Terra Smart Rental Management')

@push('styles')
<style>
    .terra-owner-page {
        --owner-orange: #D05208;
        --owner-orange-soft: #FFF2E9;
        --owner-navy: #19265D;
        --owner-muted: #748094;
        --owner-border: #E8ECF2;
        --owner-bg: #F7F8FC;
        color: #263247;
        padding-bottom: 30px;
    }

    .terra-owner-page * {
        box-sizing: border-box;
    }

    .terra-owner-page .owner-heading {
        margin-bottom: 24px;
    }

    .terra-owner-page .owner-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        margin-bottom: 15px;
        color: var(--owner-muted);
        font-size: 12px;
    }

    .terra-owner-page .owner-breadcrumb a {
        color: var(--owner-muted);
        text-decoration: none;
    }

    .terra-owner-page .owner-breadcrumb a:hover {
        color: var(--owner-orange);
    }

    .terra-owner-page .owner-title {
        margin: 0 0 7px;
        color: var(--owner-navy);
        font-size: clamp(23px, 3vw, 30px);
        font-weight: 800;
        letter-spacing: -.7px;
    }

    .terra-owner-page .owner-subtitle {
        margin: 0;
        color: var(--owner-muted);
        font-size: 13px;
        line-height: 1.7;
    }

    .terra-owner-page .owner-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 41px;
        padding: 10px 15px;
        border: 1px solid transparent;
        border-radius: 9px;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: .2s ease;
    }

    .terra-owner-page .owner-btn-primary {
        background: var(--owner-orange);
        color: #fff;
    }

    .terra-owner-page .owner-btn-primary:hover {
        background: #B94706;
        color: #fff;
        transform: translateY(-1px);
    }

    .terra-owner-page .owner-btn-outline {
        border-color: var(--owner-border);
        background: #fff;
        color: var(--owner-navy);
    }

    .terra-owner-page .owner-btn-outline:hover {
        border-color: var(--owner-orange);
        color: var(--owner-orange);
    }

    /* Profile banner */
    .terra-owner-page .owner-hero {
        position: relative;
        overflow: hidden;
        margin-bottom: 22px;
        padding: 27px;
        border-radius: 17px;
        background: linear-gradient(115deg, #19265D 0%, #253B7B 67%, #354C91 100%);
        color: #fff;
        box-shadow: 0 10px 28px rgba(25, 38, 93, .12);
    }

    .terra-owner-page .owner-hero::after {
        position: absolute;
        top: -85px;
        right: -40px;
        width: 245px;
        height: 245px;
        border: 38px solid rgba(255, 255, 255, .045);
        border-radius: 50%;
        content: "";
        pointer-events: none;
    }

    .terra-owner-page .owner-hero-content {
        position: relative;
        z-index: 1;
    }

    .terra-owner-page .owner-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 76px;
        height: 76px;
        flex: 0 0 76px;
        border: 2px solid rgba(255, 255, 255, .3);
        border-radius: 20px;
        background: rgba(255, 255, 255, .12);
        color: #fff;
        font-size: 25px;
        font-weight: 800;
        letter-spacing: 1px;
    }

    .terra-owner-page .owner-hero-name {
        margin: 0;
        color: #fff;
        font-size: 22px;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .terra-owner-page .owner-hero-meta {
        margin-top: 8px;
        color: rgba(255, 255, 255, .76);
        font-size: 12px;
    }

    .terra-owner-page .owner-hero-meta strong {
        color: #fff;
    }

    .terra-owner-page .owner-hero-company {
        margin-top: 9px;
        color: rgba(255, 255, 255, .88);
        font-size: 12px;
    }

    .terra-owner-page .owner-hero-divider {
        width: 1px;
        min-height: 45px;
        background: rgba(255, 255, 255, .17);
    }

    /* Status badges */
    .terra-owner-page .owner-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 10px;
        border: 1px solid transparent;
        border-radius: 8px;
        font-size: 10px;
        font-weight: 800;
        line-height: 1.3;
        white-space: normal;
    }

    .terra-owner-page .owner-badge.verified,
    .terra-owner-page .owner-badge.active {
        border-color: #C8F0DC;
        background: #E8FAF0;
        color: #18764B;
    }

    .terra-owner-page .owner-badge.pending,
    .terra-owner-page .owner-badge.inactive {
        border-color: #F6E1A7;
        background: #FFF7DF;
        color: #936500;
    }

    .terra-owner-page .owner-badge.rejected,
    .terra-owner-page .owner-badge.suspended {
        border-color: #F3CDCD;
        background: #FFF0F0;
        color: #B42318;
    }

    .terra-owner-page .owner-badge.unknown {
        border-color: #E4E7EC;
        background: #F2F4F7;
        color: #667085;
    }

    .terra-owner-page .owner-badge.neutral {
        border-color: rgba(255, 255, 255, .18);
        background: rgba(255, 255, 255, .11);
        color: #fff;
    }

    /* Summary metrics */
    .terra-owner-page .owner-metric {
        display: flex;
        align-items: center;
        gap: 13px;
        height: 100%;
        min-height: 94px;
        padding: 16px;
        border: 1px solid var(--owner-border);
        border-radius: 13px;
        background: #fff;
    }

    .terra-owner-page .owner-metric-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 43px;
        height: 43px;
        flex: 0 0 43px;
        border-radius: 12px;
        background: var(--owner-orange-soft);
        color: var(--owner-orange);
        font-size: 18px;
    }

    .terra-owner-page .owner-metric-icon.navy {
        background: #EDF0FC;
        color: var(--owner-navy);
    }

    .terra-owner-page .owner-metric-icon.green {
        background: #E9F8EF;
        color: #198754;
    }

    .terra-owner-page .owner-metric-icon.purple {
        background: #F3ECFF;
        color: #7652B5;
    }

    .terra-owner-page .owner-metric-label {
        margin-bottom: 5px;
        color: var(--owner-muted);
        font-size: 10px;
        font-weight: 700;
    }

    .terra-owner-page .owner-metric-value {
        color: var(--owner-navy);
        font-size: 14px;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    /* Content cards */
    .terra-owner-page .owner-card {
        margin-bottom: 20px;
        overflow: hidden;
        border: 1px solid var(--owner-border);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 3px 12px rgba(23, 37, 84, .025);
    }

    .terra-owner-page .owner-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 18px 20px;
        border-bottom: 1px solid var(--owner-border);
    }

    .terra-owner-page .owner-card-heading {
        display: flex;
        align-items: center;
        gap: 11px;
        min-width: 0;
    }

    .terra-owner-page .owner-card-heading-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 37px;
        height: 37px;
        flex: 0 0 37px;
        border-radius: 10px;
        background: var(--owner-orange-soft);
        color: var(--owner-orange);
        font-size: 16px;
    }

    .terra-owner-page .owner-card-title {
        margin: 0;
        color: var(--owner-navy);
        font-size: 14px;
        font-weight: 800;
    }

    .terra-owner-page .owner-card-subtitle {
        margin-top: 4px;
        color: var(--owner-muted);
        font-size: 11px;
        line-height: 1.5;
    }

    .terra-owner-page .owner-card-body {
        padding: 20px;
    }

    .terra-owner-page .owner-detail {
        height: 100%;
        min-height: 79px;
        padding: 13px 14px;
        border: 1px solid #EDF0F5;
        border-radius: 10px;
        background: #FCFCFE;
    }

    .terra-owner-page .owner-detail-label {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 8px;
        color: var(--owner-muted);
        font-size: 10px;
        font-weight: 700;
    }

    .terra-owner-page .owner-detail-label i {
        color: var(--owner-orange);
        font-size: 12px;
    }

    .terra-owner-page .owner-detail-value {
        color: #263247;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.65;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .terra-owner-page .owner-detail-value a {
        color: var(--owner-orange);
        text-decoration: none;
    }

    .terra-owner-page .owner-detail-value a:hover {
        text-decoration: underline;
    }

    .terra-owner-page .owner-empty {
        color: #98A2B3;
        font-weight: 500;
        font-style: italic;
    }

    /* Sidebar */
    .terra-owner-page .owner-side-row {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 0;
        border-bottom: 1px solid #EEF0F5;
    }

    .terra-owner-page .owner-side-row:first-child {
        padding-top: 0;
    }

    .terra-owner-page .owner-side-row:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .terra-owner-page .owner-side-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        border-radius: 10px;
        background: #F1F3FB;
        color: var(--owner-navy);
        font-size: 15px;
    }

    .terra-owner-page .owner-side-label {
        margin-bottom: 5px;
        color: var(--owner-muted);
        font-size: 10px;
        font-weight: 700;
    }

    .terra-owner-page .owner-side-value {
        color: #263247;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.6;
        overflow-wrap: anywhere;
    }

    .terra-owner-page .owner-side-value a {
        color: var(--owner-orange);
        text-decoration: none;
    }

    .terra-owner-page .owner-note {
        padding: 15px;
        border: 1px solid #F1E5D8;
        border-radius: 10px;
        background: #FFFAF5;
        color: #5F554D;
        font-size: 12px;
        line-height: 1.8;
        white-space: pre-line;
        overflow-wrap: anywhere;
    }

    .terra-owner-page .owner-note-empty {
        color: #9A8D81;
        font-style: italic;
    }

    .terra-owner-page .owner-action-link {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 12px 13px;
        border: 1px solid var(--owner-border);
        border-radius: 10px;
        color: var(--owner-navy);
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: .2s ease;
    }

    .terra-owner-page .owner-action-link+.owner-action-link {
        margin-top: 9px;
    }

    .terra-owner-page .owner-action-link i {
        color: var(--owner-orange);
        font-size: 15px;
    }

    .terra-owner-page .owner-action-link:hover {
        border-color: #EFC5AC;
        background: #FFFAF6;
        color: var(--owner-orange);
    }

    .terra-owner-page .owner-record-footer {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 18px;
        border-top: 1px solid var(--owner-border);
        background: #FCFCFE;
        color: var(--owner-muted);
        font-size: 10px;
    }

    @media (max-width: 767.98px) {
        .terra-owner-page .owner-hero {
            padding: 20px;
        }

        .terra-owner-page .owner-avatar {
            width: 60px;
            height: 60px;
            flex-basis: 60px;
            border-radius: 16px;
            font-size: 21px;
        }

        .terra-owner-page .owner-hero-name {
            font-size: 19px;
        }

        .terra-owner-page .owner-card-header,
        .terra-owner-page .owner-card-body {
            padding: 16px;
        }

        .terra-owner-page .owner-detail {
            min-height: 70px;
        }
    }

    @media print {

        .terra-owner-page .owner-heading-actions,
        .terra-owner-page .owner-action-card,
        .terra-owner-page .owner-breadcrumb {
            display: none !important;
        }

        .terra-owner-page .owner-hero,
        .terra-owner-page .owner-card {
            break-inside: avoid;
            box-shadow: none;
        }
    }
</style>
@endpush

@section('content')
@php
$ownerName = $owner->name ?: 'Unnamed owner';

$ownerInitials = collect(preg_split('/\s+/', trim($ownerName)))
->filter()
->take(2)
->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
->implode('');

$verification = strtolower((string) ($owner->verification_status ?: 'pending'));
$status = strtolower((string) ($owner->status ?: 'inactive'));

$verificationClass = in_array($verification, ['pending', 'verified', 'rejected'], true)
? $verification : 'unknown';

$statusClass = in_array($status, ['active', 'inactive', 'suspended'], true)
? $status : 'unknown';

$ownerType = $owner->owner_type
? ucwords(str_replace(['_', '-'], ' ', $owner->owner_type))
: 'Individual';

$account = $owner->user;
$creator = $owner->creator;

$formatDate = function ($date, $format = 'd M Y') {
return $date ? \Illuminate\Support\Carbon::parse($date)->format($format) : null;
};
@endphp

<div class="terra-owner-page">

    {{-- Page heading --}}
    <div class="owner-heading">
        <div class="owner-breadcrumb">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <i class="bi bi-chevron-right"></i>
            <a href="{{ route('admin.owners.index') }}">Property Owners</a>
            <i class="bi bi-chevron-right"></i>
            <span>Owner profile</span>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="owner-title">Property owner profile</h1>
                <p class="owner-subtitle">
                    View registration details, account information, verification, and administrative records.
                </p>
            </div>

            <div class="owner-heading-actions d-flex flex-wrap gap-2">
                <a href="{{ route('admin.owners.index') }}" class="owner-btn owner-btn-outline">
                    <i class="bi bi-arrow-left"></i>
                    Back to owners
                </a>

                <button type="button" onclick="window.print()" class="owner-btn owner-btn-outline">
                    <i class="bi bi-printer"></i>
                    Print profile
                </button>

                <a href="{{ route('admin.owners.edit', $owner) }}" class="owner-btn owner-btn-primary">
                    <i class="bi bi-pencil-square"></i>
                    Edit owner
                </a>

            </div>
        </div>
    </div>

    {{-- Owner profile banner --}}
    <section class="owner-hero">
        <div class="owner-hero-content">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="owner-avatar">
                        {{ $ownerInitials ?: 'O' }}
                    </div>

                    <div class="min-w-0">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <h2 class="owner-hero-name">{{ $ownerName }}</h2>
                            <span class="owner-badge neutral">
                                <i class="bi bi-person-badge"></i>
                                {{ $ownerType }}
                            </span>
                        </div>

                        <div class="owner-hero-meta">
                            Owner reference:
                            <strong>{{ $owner->owner_number ?: 'Not assigned' }}</strong>
                        </div>

                        @if(filled($owner->company_name))
                        <div class="owner-hero-company">
                            <i class="bi bi-building me-1"></i>
                            {{ $owner->company_name }}
                        </div>
                        @endif

                        @if(filled($owner->email))
                        <div class="owner-hero-meta">
                            <i class="bi bi-envelope me-1"></i>
                            {{ $owner->email }}
                        </div>
                        @endif
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div>
                        <div class="small mb-2" style="color:rgba(255,255,255,.7);">
                            Verification
                        </div>
                        <span class="owner-badge {{ $verificationClass }}">
                            <i class="bi bi-shield-check"></i>
                            {{ ucfirst($verification) }}
                        </span>
                    </div>

                    <div class="owner-hero-divider d-none d-md-block"></div>

                    <div>
                        <div class="small mb-2" style="color:rgba(255,255,255,.7);">
                            Account status
                        </div>
                        <span class="owner-badge {{ $statusClass }}">
                            <i class="bi bi-circle-fill" style="font-size:7px;"></i>
                            {{ ucfirst($status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="owner-metric">
                <div class="owner-metric-icon">
                    <i class="bi bi-person-vcard"></i>
                </div>
                <div>
                    <div class="owner-metric-label">Owner reference</div>
                    <div class="owner-metric-value">
                        {{ $owner->owner_number ?: 'Not assigned' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="owner-metric">
                <div class="owner-metric-icon navy">
                    <i class="bi bi-person-check"></i>
                </div>
                <div>
                    <div class="owner-metric-label">Owner classification</div>
                    <div class="owner-metric-value">{{ $ownerType }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="owner-metric">
                <div class="owner-metric-icon green">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <div class="owner-metric-label">Verification</div>
                    <div class="owner-metric-value">{{ ucfirst($verification) }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="owner-metric">
                <div class="owner-metric-icon purple">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                    <div class="owner-metric-label">Registered on</div>
                    <div class="owner-metric-value">
                        {{ $formatDate($owner->created_at) ?: 'Not available' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Main profile information --}}
        <div class="col-12 col-xl-8">

            {{-- Personal and business details --}}
            <section class="owner-card">
                <div class="owner-card-header">
                    <div class="owner-card-heading">
                        <div class="owner-card-heading-icon">
                            <i class="bi bi-person-vcard"></i>
                        </div>
                        <div>
                            <h2 class="owner-card-title">Personal & business information</h2>
                            <div class="owner-card-subtitle">
                                Owner identity and registration details
                            </div>
                        </div>
                    </div>

                    <span class="owner-badge unknown">
                        {{ $ownerType }}
                    </span>
                </div>

                <div class="owner-card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="owner-detail">
                                <div class="owner-detail-label">
                                    <i class="bi bi-person"></i> Full name / contact person
                                </div>
                                <div class="owner-detail-value">
                                    {{ $owner->name ?: 'Not provided' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="owner-detail">
                                <div class="owner-detail-label">
                                    <i class="bi bi-diagram-3"></i> Owner type
                                </div>
                                <div class="owner-detail-value">
                                    {{ $ownerType }}
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="owner-detail">
                                <div class="owner-detail-label">
                                    <i class="bi bi-building"></i> Company / organization
                                </div>
                                <div class="owner-detail-value {{ filled($owner->company_name) ? '' : 'owner-empty' }}">
                                    {{ $owner->company_name ?: 'Not provided' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="owner-detail">
                                <div class="owner-detail-label">
                                    <i class="bi bi-card-text"></i> National ID / reference
                                </div>
                                <div class="owner-detail-value {{ filled($owner->national_id) ? '' : 'owner-empty' }}">
                                    {{ $owner->national_id ?: 'Not provided' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="owner-detail">
                                <div class="owner-detail-label">
                                    <i class="bi bi-hash"></i> Internal owner ID
                                </div>
                                <div class="owner-detail-value">
                                    #{{ $owner->id }}
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="owner-detail">
                                <div class="owner-detail-label">
                                    <i class="bi bi-calendar3"></i> Last profile update
                                </div>
                                <div class="owner-detail-value">
                                    {{ $formatDate($owner->updated_at, 'd M Y, H:i') ?: 'Not available' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Contact details --}}
            <section class="owner-card">
                <div class="owner-card-header">
                    <div class="owner-card-heading">
                        <div class="owner-card-heading-icon">
                            <i class="bi bi-telephone"></i>
                        </div>
                        <div>
                            <h2 class="owner-card-title">Contact information</h2>
                            <div class="owner-card-subtitle">
                                Communication channels for the property owner
                            </div>
                        </div>
                    </div>
                </div>

                <div class="owner-card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="owner-detail">
                                <div class="owner-detail-label">
                                    <i class="bi bi-envelope"></i> Email address
                                </div>
                                <div class="owner-detail-value">
                                    @if(filled($owner->email))
                                    <a href="mailto:{{ $owner->email }}">
                                        {{ $owner->email }}
                                    </a>
                                    @else
                                    <span class="owner-empty">Not provided</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="owner-detail">
                                <div class="owner-detail-label">
                                    <i class="bi bi-telephone"></i> Phone number
                                </div>
                                <div class="owner-detail-value">
                                    @if(filled($owner->phone))
                                    <a href="tel:{{ $owner->phone }}">
                                        {{ $owner->phone }}
                                    </a>
                                    @else
                                    <span class="owner-empty">Not provided</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Location --}}
            <section class="owner-card">
                <div class="owner-card-header">
                    <div class="owner-card-heading">
                        <div class="owner-card-heading-icon">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <div>
                            <h2 class="owner-card-title">Address & location</h2>
                            <div class="owner-card-subtitle">
                                Registered address and geographical information
                            </div>
                        </div>
                    </div>
                </div>

                <div class="owner-card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="owner-detail">
                                <div class="owner-detail-label">
                                    <i class="bi bi-globe"></i> Country
                                </div>
                                <div class="owner-detail-value {{ filled($owner->country) ? '' : 'owner-empty' }}">
                                    {{ $owner->country ?: 'Not provided' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="owner-detail">
                                <div class="owner-detail-label">
                                    <i class="bi bi-map"></i> Province / region
                                </div>
                                <div class="owner-detail-value {{ filled($owner->province) ? '' : 'owner-empty' }}">
                                    {{ $owner->province ?: 'Not provided' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="owner-detail">
                                <div class="owner-detail-label">
                                    <i class="bi bi-geo"></i> District
                                </div>
                                <div class="owner-detail-value {{ filled($owner->district) ? '' : 'owner-empty' }}">
                                    {{ $owner->district ?: 'Not provided' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="owner-detail">
                                <div class="owner-detail-label">
                                    <i class="bi bi-signpost-2"></i> Physical address
                                </div>
                                <div class="owner-detail-value {{ filled($owner->address) ? '' : 'owner-empty' }}">
                                    {{ $owner->address ?: 'Not provided' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Internal notes --}}
            <section class="owner-card">
                <div class="owner-card-header">
                    <div class="owner-card-heading">
                        <div class="owner-card-heading-icon">
                            <i class="bi bi-journal-text"></i>
                        </div>
                        <div>
                            <h2 class="owner-card-title">Internal notes</h2>
                            <div class="owner-card-subtitle">
                                Administrative remarks recorded for this owner
                            </div>
                        </div>
                    </div>
                </div>

                <div class="owner-card-body">
                    <div class="owner-note {{ filled($owner->notes) ? '' : 'owner-note-empty' }}">
                        @if(filled($owner->notes))
                        {{ $owner->notes }}
                        @else
                        No internal notes have been recorded for this owner.
                        @endif
                    </div>
                </div>
            </section>
        </div>

        {{-- Right sidebar --}}
        <div class="col-12 col-xl-4">

            {{-- Linked account --}}
            <section class="owner-card">
                <div class="owner-card-header">
                    <div class="owner-card-heading">
                        <div class="owner-card-heading-icon">
                            <i class="bi bi-person-lock"></i>
                        </div>
                        <div>
                            <h2 class="owner-card-title">Linked login account</h2>
                            <div class="owner-card-subtitle">
                                Terra platform access information
                            </div>
                        </div>
                    </div>
                </div>

                <div class="owner-card-body">
                    @if($account)
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="owner-avatar"
                            style="width:48px;height:48px;flex-basis:48px;border-radius:13px;background:#EDF0FC;border:0;color:var(--owner-navy);font-size:17px;">
                            {{ collect(preg_split('/\s+/', trim($account->name ?? 'U')))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: 'U' }}
                        </div>
                        <div class="min-w-0">
                            <div class="owner-detail-value">
                                {{ $account->name ?: 'Unnamed user' }}
                            </div>
                            <div class="owner-card-subtitle">
                                Login account linked to this owner
                            </div>
                        </div>
                    </div>

                    <div class="owner-side-row">
                        <div class="owner-side-icon">
                            <i class="bi bi-hash"></i>
                        </div>
                        <div>
                            <div class="owner-side-label">User account ID</div>
                            <div class="owner-side-value">#{{ $account->id }}</div>
                        </div>
                    </div>

                    <div class="owner-side-row">
                        <div class="owner-side-icon">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <div>
                            <div class="owner-side-label">Login email</div>
                            <div class="owner-side-value">
                                @if(filled($account->email))
                                <a href="mailto:{{ $account->email }}">
                                    {{ $account->email }}
                                </a>
                                @else
                                Not provided
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="owner-side-row">
                        <div class="owner-side-icon">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <div>
                            <div class="owner-side-label">Assigned role</div>
                            <div class="owner-side-value">
                                {{ $account->role ? ucwords(str_replace('_', ' ', $account->role)) : 'Not specified' }}
                            </div>
                        </div>
                    </div>

                    <div class="owner-side-row">
                        <div class="owner-side-icon">
                            <i class="bi bi-patch-check"></i>
                        </div>
                        <div>
                            <div class="owner-side-label">Email verification</div>
                            <div class="owner-side-value">
                                @if($account->email_verified_at)
                                <span class="owner-badge verified">
                                    <i class="bi bi-check-circle"></i>
                                    Verified
                                </span>
                                <div class="owner-card-subtitle mt-2">
                                    {{ $formatDate($account->email_verified_at, 'd M Y, H:i') }}
                                </div>
                                @else
                                <span class="owner-badge pending">
                                    <i class="bi bi-clock"></i>
                                    Not verified
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="text-center py-3">
                        <div class="owner-metric-icon navy mx-auto mb-3">
                            <i class="bi bi-person-x"></i>
                        </div>
                        <div class="owner-card-title mb-2">No linked login account</div>
                        <p class="owner-card-subtitle mb-0">
                            This owner record is not currently linked to a user account.
                            Confirm the owner's registration before enabling platform access.
                        </p>
                    </div>
                    @endif
                </div>
            </section>

            {{-- Verification and account status --}}
            <section class="owner-card">
                <div class="owner-card-header">
                    <div class="owner-card-heading">
                        <div class="owner-card-heading-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <h2 class="owner-card-title">Verification & status</h2>
                            <div class="owner-card-subtitle">
                                Current administrative standing
                            </div>
                        </div>
                    </div>
                </div>

                <div class="owner-card-body">
                    <div class="owner-side-row">
                        <div class="owner-side-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <div class="owner-side-label">Verification status</div>
                            <div class="owner-side-value">
                                <span class="owner-badge {{ $verificationClass }}">
                                    {{ ucfirst($verification) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="owner-side-row">
                        <div class="owner-side-icon">
                            <i class="bi bi-toggle-on"></i>
                        </div>
                        <div>
                            <div class="owner-side-label">Account status</div>
                            <div class="owner-side-value">
                                <span class="owner-badge {{ $statusClass }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="owner-side-row">
                        <div class="owner-side-icon">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <div>
                            <div class="owner-side-label">Date registered</div>
                            <div class="owner-side-value">
                                {{ $formatDate($owner->created_at, 'd M Y, H:i') ?: 'Not available' }}
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Registration audit --}}
            <section class="owner-card">
                <div class="owner-card-header">
                    <div class="owner-card-heading">
                        <div class="owner-card-heading-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <h2 class="owner-card-title">Registration audit</h2>
                            <div class="owner-card-subtitle">
                                Record ownership and timestamps
                            </div>
                        </div>
                    </div>
                </div>

                <div class="owner-card-body">
                    <div class="owner-side-row">
                        <div class="owner-side-icon">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <div>
                            <div class="owner-side-label">Registered by</div>
                            <div class="owner-side-value">
                                {{ $creator?->name ?: 'System / not recorded' }}
                            </div>
                            @if($creator?->email)
                            <div class="owner-card-subtitle">{{ $creator->email }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="owner-side-row">
                        <div class="owner-side-icon">
                            <i class="bi bi-calendar-plus"></i>
                        </div>
                        <div>
                            <div class="owner-side-label">Created at</div>
                            <div class="owner-side-value">
                                {{ $formatDate($owner->created_at, 'd M Y, H:i') ?: 'Not available' }}
                            </div>
                        </div>
                    </div>

                    <div class="owner-side-row">
                        <div class="owner-side-icon">
                            <i class="bi bi-pencil"></i>
                        </div>
                        <div>
                            <div class="owner-side-label">Last updated</div>
                            <div class="owner-side-value">
                                {{ $formatDate($owner->updated_at, 'd M Y, H:i') ?: 'Not available' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="owner-record-footer">
                    <span>Record ID: #{{ $owner->id }}</span>
                    <span>Terra Smart Rental Management</span>
                </div>
            </section>

            {{-- Quick actions --}}
            <section class="owner-card owner-action-card">
                <div class="owner-card-header">
                    <div class="owner-card-heading">
                        <div class="owner-card-heading-icon">
                            <i class="bi bi-lightning-charge"></i>
                        </div>
                        <div>
                            <h2 class="owner-card-title">Quick actions</h2>
                            <div class="owner-card-subtitle">
                                Manage this owner record
                            </div>
                        </div>
                    </div>
                </div>

                <div class="owner-card-body">
                    <a href="{{ route('admin.owners.edit', $owner) }}"
                        class="owner-action-link">
                        <i class="bi bi-pencil-square"></i>
                        <span>Edit owner information</span>
                        <i class="bi bi-chevron-right ms-auto"></i>
                    </a>

                    @if ($owner->user_id)
                    <form method="POST" action="{{ route('admin.owners.resend-credentials', $owner) }}"
                        onsubmit="return confirm('Generate a new password and email it to this owner?')">
                        @csrf
                        <button class="btn btn-sm btn-outline-secondary">Resend credentials</button>
                    </form>
                    @endif

                    @if(filled($owner->email))
                    <a href="mailto:{{ $owner->email }}" class="owner-action-link">
                        <i class="bi bi-envelope"></i>
                        <span>Send email to owner</span>
                        <i class="bi bi-chevron-right ms-auto"></i>
                    </a>
                    @endif

                    @if(filled($owner->phone))
                    <a href="tel:{{ $owner->phone }}" class="owner-action-link">
                        <i class="bi bi-telephone"></i>
                        <span>Call owner</span>
                        <i class="bi bi-chevron-right ms-auto"></i>
                    </a>
                    @endif

                    <a href="{{ route('admin.owners.index') }}" class="owner-action-link">
                        <i class="bi bi-people"></i>
                        <span>View all property owners</span>
                        <i class="bi bi-chevron-right ms-auto"></i>
                    </a>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection