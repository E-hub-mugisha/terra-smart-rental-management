@extends('layouts.app')

@section('title', 'Manager Details')
@section('page_heading', 'Manager Details')
@section('page_subtitle', 'View manager information, employment details and property assignments.')

@section('content')
@php
$managerUser = $manager->user;
$managerName = $managerUser?->name ?? 'User account unavailable';
$managerStatus = strtolower($manager->status ?? 'inactive');

$initials = collect(
preg_split('/\s+/', trim($managerUser?->name ?? 'Property Manager'))
)->filter()->take(2)->map(
fn ($part) => strtoupper(substr($part, 0, 1))
)->implode('');

$assignedProperties = $manager->properties;
$activeAssignments = $assignedProperties->filter(
fn ($property) => strtolower($property->pivot->status ?? '') === 'active'
)->count();

$employmentType = $manager->employment_type
? ucwords(str_replace('_', ' ', $manager->employment_type))
: 'Not specified';

$formatDate = function ($date) {
if (!$date) {
return 'Not provided';
}

try {
return \Illuminate\Support\Carbon::parse($date)->format('d M Y');
} catch (\Throwable $e) {
return 'Not provided';
}
};
@endphp

<style>
    .manager-detail {
        --md-orange: #D05208;
        --md-navy: #19265d;
        --md-muted: #727d90;
        --md-border: #e6eaf1;
        --md-bg: #f7f8fc;
        color: #263148;
    }

    .manager-detail .md-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--md-muted);
        text-decoration: none;
        font-size: 13px;
        font-weight: 650;
        margin-bottom: 20px;
    }

    .manager-detail .md-back:hover {
        color: var(--md-orange);
    }

    .manager-detail .md-heading {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 18px;
        margin-bottom: 25px;
    }

    .manager-detail .md-eyebrow {
        color: var(--md-orange);
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: .12em;
        font-weight: 800;
        margin-bottom: 8px;
    }

    .manager-detail .md-page-title {
        color: var(--md-navy);
        font-size: clamp(24px, 3vw, 31px);
        letter-spacing: -.7px;
        font-weight: 800;
        margin-bottom: 7px;
    }

    .manager-detail .md-subtitle {
        font-size: 13px;
        color: var(--md-muted);
        margin: 0;
        line-height: 1.7;
    }

    .manager-detail .md-btn {
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 10px;
        padding: 10px 16px;
        font-size: 12px;
        font-weight: 750;
        text-decoration: none;
        transition: .2s ease;
        white-space: nowrap;
    }

    .manager-detail .md-btn-primary {
        background: var(--md-orange);
        border: 1px solid var(--md-orange);
        color: #fff;
    }

    .manager-detail .md-btn-primary:hover {
        background: #b94707;
        border-color: #b94707;
        color: #fff;
        transform: translateY(-1px);
    }

    .manager-detail .md-btn-secondary {
        background: #fff;
        border: 1px solid var(--md-border);
        color: #4c566a;
    }

    .manager-detail .md-btn-secondary:hover {
        background: var(--md-bg);
        color: var(--md-navy);
    }

    .manager-detail .md-hero {
        position: relative;
        overflow: hidden;
        border: 1px solid var(--md-border);
        border-radius: 19px;
        background: #fff;
        margin-bottom: 22px;
        box-shadow: 0 6px 25px rgba(25, 38, 93, .045);
    }

    .manager-detail .md-hero-band {
        height: 100px;
        background: linear-gradient(115deg, #19265d 0%, #303e7b 68%, #46528a 100%);
        position: relative;
    }

    .manager-detail .md-hero-band::after {
        content: "";
        position: absolute;
        width: 240px;
        height: 240px;
        border: 35px solid rgba(255, 255, 255, .055);
        border-radius: 50%;
        right: 7%;
        top: -150px;
    }

    .manager-detail .md-hero-body {
        padding: 0 26px 25px;
    }

    .manager-detail .md-profile-row {
        display: flex;
        align-items: flex-end;
        gap: 19px;
        margin-top: -43px;
        position: relative;
        z-index: 1;
    }

    .manager-detail .md-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 92px;
        width: 92px;
        height: 92px;
        border-radius: 20px;
        background: #fff;
        border: 4px solid #fff;
        box-shadow: 0 4px 15px rgba(25, 38, 93, .12);
        color: var(--md-navy);
        font-size: 29px;
        font-weight: 800;
    }

    .manager-detail .md-profile-info {
        padding-bottom: 5px;
        min-width: 0;
        flex: 1;
    }

    .manager-detail .md-profile-name {
        color: var(--md-navy);
        font-size: 21px;
        font-weight: 800;
        overflow-wrap: anywhere;
        margin: 0 0 5px;
    }

    .manager-detail .md-profile-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        font-size: 12px;
        color: var(--md-muted);
    }

    .manager-detail .md-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        border-radius: 30px;
        padding: 7px 11px;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .manager-detail .md-status::before {
        content: "";
        display: inline-block;
        width: 6px;
        height: 6px;
        background: currentColor;
        border-radius: 50%;
    }

    .manager-detail .md-status-active {
        background: #e8f7ee;
        color: #167544;
    }

    .manager-detail .md-status-inactive {
        background: #f0f1f4;
        color: #667085;
    }

    .manager-detail .md-status-other {
        background: #fff3df;
        color: #98600b;
    }

    .manager-detail .md-hero-status {
        padding-bottom: 7px;
    }

    .manager-detail .md-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 24px;
    }

    .manager-detail .md-stat {
        display: flex;
        align-items: center;
        gap: 13px;
        min-width: 0;
        padding: 19px;
        background: #fff;
        border: 1px solid var(--md-border);
        border-radius: 15px;
        box-shadow: 0 4px 16px rgba(25, 38, 93, .025);
    }

    .manager-detail .md-stat-icon {
        width: 43px;
        height: 43px;
        flex: 0 0 43px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 13px;
        background: #fff0e7;
        color: var(--md-orange);
        font-size: 19px;
    }

    .manager-detail .md-stat-icon.navy {
        background: #edf0fa;
        color: var(--md-navy);
    }

    .manager-detail .md-stat-icon.green {
        background: #e9f7ef;
        color: #16804b;
    }

    .manager-detail .md-stat-label {
        font-size: 11px;
        color: var(--md-muted);
        margin-bottom: 5px;
        line-height: 1.4;
    }

    .manager-detail .md-stat-value {
        font-size: 22px;
        line-height: 1.15;
        color: var(--md-navy);
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .manager-detail .md-card {
        background: #fff;
        border: 1px solid var(--md-border);
        border-radius: 16px;
        margin-bottom: 22px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(25, 38, 93, .03);
    }

    .manager-detail .md-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        padding: 20px 23px;
        border-bottom: 1px solid var(--md-border);
    }

    .manager-detail .md-card-heading {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .manager-detail .md-section-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 40px;
        width: 40px;
        height: 40px;
        background: #edf0fa;
        color: var(--md-navy);
        border-radius: 12px;
        font-size: 18px;
    }

    .manager-detail .md-section-icon.orange {
        background: #fff0e7;
        color: var(--md-orange);
    }

    .manager-detail .md-section-title {
        color: var(--md-navy);
        font-size: 14px;
        font-weight: 800;
        margin: 0 0 4px;
    }

    .manager-detail .md-section-description {
        font-size: 11px;
        line-height: 1.5;
        color: var(--md-muted);
        margin: 0;
    }

    .manager-detail .md-card-body {
        padding: 23px;
    }

    .manager-detail .md-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 23px 26px;
    }

    .manager-detail .md-info-label {
        font-size: 11px;
        color: var(--md-muted);
        margin-bottom: 7px;
    }

    .manager-detail .md-info-value {
        font-size: 13px;
        font-weight: 700;
        color: #29344a;
        overflow-wrap: anywhere;
        line-height: 1.6;
    }

    .manager-detail .md-info-value a {
        color: var(--md-navy);
        text-decoration: none;
    }

    .manager-detail .md-info-value a:hover {
        color: var(--md-orange);
    }

    .manager-detail .md-divider {
        height: 1px;
        background: var(--md-border);
        margin: 22px 0;
    }

    .manager-detail .md-property {
        padding: 17px 0;
        border-bottom: 1px solid #edf0f5;
    }

    .manager-detail .md-property:first-child {
        padding-top: 0;
    }

    .manager-detail .md-property:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .manager-detail .md-property-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
    }

    .manager-detail .md-property-main {
        display: flex;
        gap: 12px;
        min-width: 0;
    }

    .manager-detail .md-property-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 43px;
        height: 43px;
        flex: 0 0 43px;
        border-radius: 12px;
        background: #f0f2f9;
        color: var(--md-navy);
        font-size: 19px;
    }

    .manager-detail .md-property-name {
        color: var(--md-navy);
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 5px;
        overflow-wrap: anywhere;
    }

    .manager-detail .md-property-address {
        color: var(--md-muted);
        font-size: 11px;
        line-height: 1.6;
        overflow-wrap: anywhere;
    }

    .manager-detail .md-assignment-meta {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 13px;
        padding: 14px;
        margin-top: 14px;
        border-radius: 11px;
        background: #f8f9fc;
    }

    .manager-detail .md-assignment-label {
        color: var(--md-muted);
        font-size: 10px;
        margin-bottom: 5px;
    }

    .manager-detail .md-assignment-value {
        color: #303b50;
        font-size: 11px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .manager-detail .md-note {
        background: #fafbfe;
        border: 1px solid var(--md-border);
        border-radius: 12px;
        padding: 18px;
        font-size: 13px;
        line-height: 1.8;
        color: #445067;
        overflow-wrap: anywhere;
    }

    .manager-detail .md-empty {
        text-align: center;
        padding: 35px 20px;
    }

    .manager-detail .md-empty-icon {
        width: 54px;
        height: 54px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 14px;
        border-radius: 16px;
        background: #f0f2f9;
        color: var(--md-navy);
        font-size: 23px;
    }

    .manager-detail .md-empty-title {
        color: var(--md-navy);
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 6px;
    }

    .manager-detail .md-empty-text {
        color: var(--md-muted);
        font-size: 12px;
        line-height: 1.7;
        margin: 0;
    }

    .manager-detail .md-reference {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 11px;
        border: 1px solid var(--md-border);
        border-radius: 9px;
        background: #fafbfe;
        color: var(--md-navy);
        font-size: 11px;
        font-weight: 750;
        overflow-wrap: anywhere;
    }

    @media (max-width: 991.98px) {
        .manager-detail .md-stats {
            gap: 10px;
        }

        .manager-detail .md-stat {
            padding: 15px;
        }

        .manager-detail .md-stat-icon {
            width: 36px;
            height: 36px;
            flex-basis: 36px;
            font-size: 16px;
        }

        .manager-detail .md-stat-value {
            font-size: 19px;
        }
    }

    @media (max-width: 767.98px) {
        .manager-detail .md-heading {
            flex-direction: column;
        }

        .manager-detail .md-heading-actions {
            display: flex;
            width: 100%;
        }

        .manager-detail .md-heading-actions .md-btn {
            flex: 1;
        }

        .manager-detail .md-hero-body {
            padding: 0 18px 20px;
        }

        .manager-detail .md-profile-row {
            flex-wrap: wrap;
            align-items: flex-start;
            gap: 12px;
        }

        .manager-detail .md-avatar {
            width: 75px;
            height: 75px;
            flex-basis: 75px;
            font-size: 24px;
            border-radius: 16px;
        }

        .manager-detail .md-profile-info {
            padding-top: 8px;
        }

        .manager-detail .md-profile-name {
            font-size: 17px;
        }

        .manager-detail .md-hero-status {
            width: 100%;
            padding: 0;
        }

        .manager-detail .md-stats {
            grid-template-columns: 1fr;
        }

        .manager-detail .md-stat {
            padding: 14px 17px;
        }

        .manager-detail .md-card-header,
        .manager-detail .md-card-body {
            padding: 18px;
        }

        .manager-detail .md-assignment-meta {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 420px) {
        .manager-detail .md-info-grid {
            grid-template-columns: 1fr;
            gap: 18px;
        }

        .manager-detail .md-property-top {
            flex-direction: column;
        }

        .manager-detail .md-assignment-meta {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .manager-detail .md-card-header {
            align-items: flex-start;
        }
    }
</style>

<div class="manager-detail">

    <a href="{{ route('admin.managers.index') }}" class="md-back">
        <i class="bi bi-arrow-left"></i>
        Back to property managers
    </a>

    <form method="POST" action="{{ route('admin.managers.resend-credentials', $manager) }}"
        onsubmit="return confirm('Generate a new password and email it to this manager?')">
        @csrf
        <button class="btn btn-sm btn-outline-secondary">Resend credentials</button>
    </form>

    {{-- Page heading --}}
    <div class="md-heading">
        <div>
            <div class="md-eyebrow">Team administration</div>
            <h1 class="md-page-title">Manager profile</h1>
            <p class="md-subtitle">
                View account information, employment history and assigned properties.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2 md-heading-actions">
            <a href="{{ route('admin.managers.edit', $manager) }}"
                class="md-btn md-btn-primary">
                <i class="bi bi-pencil-square"></i>
                Edit manager
            </a>

            <a href="{{ route('admin.managers.index') }}"
                class="md-btn md-btn-secondary">
                <i class="bi bi-arrow-left"></i>
                All managers
            </a>
        </div>
    </div>

    {{-- Profile banner --}}
    <section class="md-hero">
        <div class="md-hero-band"></div>

        <div class="md-hero-body">
            <div class="md-profile-row">
                <div class="md-avatar">
                    {{ $initials ?: 'PM' }}
                </div>

                <div class="md-profile-info">
                    <h2 class="md-profile-name">{{ $managerName }}</h2>

                    <div class="md-profile-meta">
                        <span>
                            <i class="bi bi-briefcase me-1"></i>
                            {{ $manager->job_title ?: 'Property Manager' }}
                        </span>

                        <span class="d-none d-sm-inline">·</span>

                        <span>
                            <i class="bi bi-hash me-1"></i>
                            {{ $manager->manager_number ?: 'Number not assigned' }}
                        </span>
                    </div>
                </div>

                <div class="md-hero-status">
                    @if($managerStatus === 'active')
                    <span class="md-status md-status-active">Active account</span>
                    @elseif($managerStatus === 'inactive')
                    <span class="md-status md-status-inactive">Inactive account</span>
                    @else
                    <span class="md-status md-status-other">
                        {{ ucfirst($manager->status ?? 'Unknown') }} account
                    </span>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Summary statistics --}}
    <div class="md-stats">
        <div class="md-stat">
            <div class="md-stat-icon navy">
                <i class="bi bi-buildings"></i>
            </div>
            <div>
                <div class="md-stat-label">Assigned properties</div>
                <div class="md-stat-value">{{ number_format($assignedProperties->count()) }}</div>
            </div>
        </div>

        <div class="md-stat">
            <div class="md-stat-icon green">
                <i class="bi bi-check-circle"></i>
            </div>
            <div>
                <div class="md-stat-label">Active assignments</div>
                <div class="md-stat-value">{{ number_format($activeAssignments) }}</div>
            </div>
        </div>

        <div class="md-stat">
            <div class="md-stat-icon">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div>
                <div class="md-stat-label">Employment start</div>
                <div class="md-stat-value" style="font-size:16px;">
                    {{ $manager->start_date?->format('d M Y') ?? 'Not set' }}
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Left column --}}
        <div class="col-lg-4">

            {{-- Contact and account details --}}
            <section class="md-card">
                <div class="md-card-header">
                    <div class="md-card-heading">
                        <div class="md-section-icon orange">
                            <i class="bi bi-person-vcard"></i>
                        </div>
                        <div>
                            <h2 class="md-section-title">Contact information</h2>
                            <p class="md-section-description">Account and communication details</p>
                        </div>
                    </div>
                </div>

                <div class="md-card-body">
                    <div class="mb-4">
                        <div class="md-info-label">Full name</div>
                        <div class="md-info-value">{{ $managerName }}</div>
                    </div>

                    <div class="mb-4">
                        <div class="md-info-label">Email address</div>
                        <div class="md-info-value">
                            @if($managerUser?->email)
                            <a href="mailto:{{ $managerUser->email }}">
                                <i class="bi bi-envelope me-1"></i>
                                {{ $managerUser->email }}
                            </a>
                            @else
                            Not available
                            @endif
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="md-info-label">Phone number</div>
                        <div class="md-info-value">
                            @if($manager->phone)
                            <a href="tel:{{ $manager->phone }}">
                                <i class="bi bi-telephone me-1"></i>
                                {{ $manager->phone }}
                            </a>
                            @else
                            Not provided
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="md-info-label">Manager reference</div>
                        <span class="md-reference">
                            <i class="bi bi-upc-scan"></i>
                            {{ $manager->manager_number ?: 'Not assigned' }}
                        </span>
                    </div>
                </div>
            </section>

            {{-- Record audit information --}}
            <section class="md-card">
                <div class="md-card-header">
                    <div class="md-card-heading">
                        <div class="md-section-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <h2 class="md-section-title">Record information</h2>
                            <p class="md-section-description">Profile creation and last update</p>
                        </div>
                    </div>
                </div>

                <div class="md-card-body">
                    <div class="mb-4">
                        <div class="md-info-label">Created by</div>
                        <div class="md-info-value">
                            {{ $manager->creator?->name ?? 'System / unavailable' }}
                        </div>
                        @if($manager->creator?->email)
                        <div class="small text-secondary mt-1 text-break">
                            {{ $manager->creator->email }}
                        </div>
                        @endif
                    </div>

                    <div class="mb-4">
                        <div class="md-info-label">Profile created</div>
                        <div class="md-info-value">
                            {{ $formatDate($manager->created_at) }}
                        </div>
                    </div>

                    <div>
                        <div class="md-info-label">Last updated</div>
                        <div class="md-info-value">
                            {{ $formatDate($manager->updated_at) }}
                        </div>
                    </div>
                </div>
            </section>

        </div>

        {{-- Right column --}}
        <div class="col-lg-8">

            {{-- Employment information --}}
            <section class="md-card">
                <div class="md-card-header">
                    <div class="md-card-heading">
                        <div class="md-section-icon">
                            <i class="bi bi-briefcase"></i>
                        </div>
                        <div>
                            <h2 class="md-section-title">Employment information</h2>
                            <p class="md-section-description">
                                Position, employment arrangement and current status
                            </p>
                        </div>
                    </div>
                </div>

                <div class="md-card-body">
                    <div class="md-info-grid">
                        <div>
                            <div class="md-info-label">Job title</div>
                            <div class="md-info-value">
                                {{ $manager->job_title ?: 'Not specified' }}
                            </div>
                        </div>

                        <div>
                            <div class="md-info-label">Employment type</div>
                            <div class="md-info-value">{{ $employmentType }}</div>
                        </div>

                        <div>
                            <div class="md-info-label">Employment start date</div>
                            <div class="md-info-value">
                                {{ $manager->start_date?->format('d M Y') ?? 'Not provided' }}
                            </div>
                        </div>

                        <div>
                            <div class="md-info-label">Account status</div>
                            <div>
                                @if($managerStatus === 'active')
                                <span class="md-status md-status-active">Active</span>
                                @elseif($managerStatus === 'inactive')
                                <span class="md-status md-status-inactive">Inactive</span>
                                @else
                                <span class="md-status md-status-other">
                                    {{ ucfirst($manager->status ?? 'Unknown') }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Assigned properties --}}
            <section class="md-card">
                <div class="md-card-header">
                    <div class="md-card-heading">
                        <div class="md-section-icon orange">
                            <i class="bi bi-buildings"></i>
                        </div>
                        <div>
                            <h2 class="md-section-title">Assigned properties</h2>
                            <p class="md-section-description">
                                Properties associated with this manager's assignment records
                            </p>
                        </div>
                    </div>

                    <span class="md-reference">
                        {{ $assignedProperties->count() }} total
                    </span>
                </div>

                <div class="md-card-body">
                    @forelse($assignedProperties as $property)
                    @php
                    $assignmentStatus = strtolower($property->pivot->status ?? 'unknown');

                    $assignmentBadgeClass = match ($assignmentStatus) {
                    'active' => 'md-status-active',
                    'inactive', 'ended', 'completed' => 'md-status-inactive',
                    default => 'md-status-other',
                    };

                    $propertyName = $property->name
                    ?? $property->property_name
                    ?? ('Property #' . $property->getKey());

                    $propertyAddress = $property->address
                    ?? $property->location
                    ?? null;
                    @endphp

                    <article class="md-property">
                        <div class="md-property-top">
                            <div class="md-property-main">
                                <div class="md-property-icon">
                                    <i class="bi bi-building"></i>
                                </div>

                                <div>
                                    <div class="md-property-name">
                                        {{ $propertyName }}
                                    </div>

                                    <div class="md-property-address">
                                        @if($propertyAddress)
                                        <i class="bi bi-geo-alt me-1"></i>
                                        {{ $propertyAddress }}
                                        @else
                                        Property ID: {{ $property->getKey() }}
                                        @endif
                                    </div>

                                    @if(isset($property->property_number) && $property->property_number)
                                    <div class="md-property-address mt-1">
                                        Reference: {{ $property->property_number }}
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <span class="md-status {{ $assignmentBadgeClass }}">
                                {{ ucfirst(str_replace('_', ' ', $property->pivot->status ?? 'Unknown')) }}
                            </span>
                        </div>

                        <div class="md-assignment-meta">
                            <div>
                                <div class="md-assignment-label">Assignment start</div>
                                <div class="md-assignment-value">
                                    {{ $formatDate($property->pivot->start_date) }}
                                </div>
                            </div>

                            <div>
                                <div class="md-assignment-label">Assignment end</div>
                                <div class="md-assignment-value">
                                    {{ $formatDate($property->pivot->end_date) }}
                                </div>
                            </div>

                            <div>
                                <div class="md-assignment-label">Assigned by</div>
                                <div class="md-assignment-value">
                                    @if($property->owner->name)
                                    {{ $property->owner->name }}
                                    @else
                                    Not recorded
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($property->pivot->notes)
                        <div class="mt-3">
                            <div class="md-assignment-label">Assignment notes</div>
                            <div class="small text-secondary" style="line-height:1.7;white-space:pre-line;">{{ $property->pivot->notes }}</div>
                        </div>
                        @endif
                    </article>
                    @empty
                    <div class="md-empty">
                        <div class="md-empty-icon">
                            <i class="bi bi-buildings"></i>
                        </div>
                        <div class="md-empty-title">No properties assigned</div>
                        <p class="md-empty-text">
                            This manager does not currently have any properties
                            associated with their assignment records.
                        </p>
                    </div>
                    @endforelse
                </div>
            </section>

            {{-- Notes --}}
            <section class="md-card">
                <div class="md-card-header">
                    <div class="md-card-heading">
                        <div class="md-section-icon">
                            <i class="bi bi-journal-text"></i>
                        </div>
                        <div>
                            <h2 class="md-section-title">Internal notes</h2>
                            <p class="md-section-description">
                                Additional administrative information about the manager
                            </p>
                        </div>
                    </div>
                </div>

                <div class="md-card-body">
                    @if(filled($manager->notes))
                    <div class="md-note">{{ $manager->notes }}</div>
                    @else
                    <div class="md-empty" style="padding:18px 10px;">
                        <div class="md-empty-icon">
                            <i class="bi bi-chat-left-text"></i>
                        </div>
                        <div class="md-empty-title">No notes recorded</div>
                        <p class="md-empty-text">
                            Add internal notes when editing this manager's profile.
                        </p>
                    </div>
                    @endif
                </div>
            </section>

        </div>
    </div>

</div>
@endsection