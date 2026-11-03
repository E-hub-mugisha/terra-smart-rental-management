
@extends('layouts.app')

@section('title', 'Edit Property Owner')
@section('page_heading', 'Edit Property Owner')
@section('page_subtitle', 'Update owner information and verification details.')

@push('styles')
<style>
    .owner-edit-page {
        --owner-orange: var(--terra-orange, #D05208);
        --owner-navy: var(--terra-navy, #19265d);
        --owner-muted: var(--terra-muted, #748094);
        --owner-border: var(--terra-border, #e7eaf0);
        color: var(--owner-navy);
    }

    .owner-edit-hero {
        position: relative;
        overflow: hidden;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 22px;
        padding: 25px 28px;
        margin-bottom: 23px;
        border: 1px solid var(--owner-border);
        border-radius: 18px;
        background: linear-gradient(120deg, #fff 0%, #fffaf6 100%);
    }

    .owner-edit-hero::after {
        content: "";
        position: absolute;
        top: -85px;
        right: -55px;
        width: 190px;
        height: 190px;
        border-radius: 50%;
        background: rgba(208, 82, 8, .055);
        pointer-events: none;
    }

    .owner-edit-breadcrumb {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 9px;
        margin-bottom: 13px;
        color: var(--owner-muted);
        font-size: 12px;
    }

    .owner-edit-breadcrumb a {
        color: var(--owner-muted);
        text-decoration: none;
    }

    .owner-edit-breadcrumb a:hover {
        color: var(--owner-orange);
    }

    .owner-edit-breadcrumb i {
        font-size: 10px;
    }

    .owner-edit-title {
        margin: 0 0 8px;
        font-size: clamp(23px, 3vw, 30px);
        font-weight: 800;
        letter-spacing: -.7px;
    }

    .owner-edit-description {
        margin: 0;
        max-width: 650px;
        color: var(--owner-muted);
        font-size: 13px;
        line-height: 1.7;
    }

    .owner-edit-hero-actions {
        z-index: 1;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
    }

    .owner-edit-hero-actions .btn-terra-outline {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 42px;
        padding: 10px 15px;
        border: 1px solid var(--owner-border);
        border-radius: 9px;
        background: #fff;
        color: var(--owner-navy);
        text-decoration: none;
        font-size: 12px;
        font-weight: 700;
        transition: all .2s ease;
    }

    .owner-edit-hero-actions .btn-terra-outline:hover {
        border-color: var(--owner-orange);
        color: var(--owner-orange);
        background: #fffaf6;
    }

    .owner-edit-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 270px;
        align-items: start;
        gap: 22px;
    }

    .owner-edit-card,
    .owner-edit-aside-card {
        border: 1px solid var(--owner-border);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 4px 18px rgba(25, 38, 93, .035);
    }

    .owner-edit-card {
        overflow: hidden;
    }

    .owner-edit-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        padding: 22px 25px;
        border-bottom: 1px solid var(--owner-border);
    }

    .owner-edit-identity {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .owner-edit-avatar {
        display: grid;
        place-items: center;
        flex-shrink: 0;
        width: 52px;
        height: 52px;
        border: 1px solid #f2d9c7;
        border-radius: 14px;
        background: #fff0e5;
        color: var(--owner-orange);
        font-size: 21px;
        font-weight: 800;
    }

    .owner-edit-owner-name {
        overflow-wrap: anywhere;
        margin: 0 0 5px;
        font-size: 16px;
        font-weight: 800;
        color: var(--owner-navy);
    }

    .owner-edit-owner-number {
        color: var(--owner-muted);
        font-size: 11px;
    }

    .owner-edit-owner-number strong {
        color: var(--owner-navy);
        font-weight: 800;
    }

    .owner-edit-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 10px;
        border: 1px solid #f2d9c7;
        border-radius: 20px;
        background: #fff7f0;
        color: var(--owner-orange);
        font-size: 10px;
        font-weight: 800;
        white-space: nowrap;
    }

    .owner-edit-label i {
        font-size: 12px;
    }

    .owner-edit-card-body {
        padding: 25px;
    }

    .owner-edit-aside {
        display: grid;
        gap: 16px;
    }

    .owner-edit-aside-card {
        padding: 20px;
    }

    .owner-edit-aside-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0 0 16px;
        font-size: 13px;
        font-weight: 800;
    }

    .owner-edit-aside-title i {
        color: var(--owner-orange);
        font-size: 17px;
    }

    .owner-edit-summary {
        display: grid;
        gap: 14px;
    }

    .owner-edit-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f0f2f6;
    }

    .owner-edit-summary-row:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .owner-edit-summary-label {
        color: var(--owner-muted);
        font-size: 11px;
    }

    .owner-edit-summary-value {
        max-width: 58%;
        overflow-wrap: anywhere;
        text-align: right;
        color: var(--owner-navy);
        font-size: 11px;
        font-weight: 700;
    }

    .owner-edit-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 8px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 800;
        text-transform: capitalize;
    }

    .owner-edit-status.active,
    .owner-edit-status.verified {
        color: #157347;
        background: #e9f7ef;
    }

    .owner-edit-status.inactive,
    .owner-edit-status.rejected {
        color: #b42318;
        background: #fef0ef;
    }

    .owner-edit-status.pending,
    .owner-edit-status.unknown {
        color: #946200;
        background: #fff5d9;
    }

    .owner-edit-guidance {
        display: grid;
        gap: 14px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .owner-edit-guidance li {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        color: var(--owner-muted);
        font-size: 11px;
        line-height: 1.7;
    }

    .owner-edit-guidance li i {
        margin-top: 2px;
        color: #16845b;
        font-size: 14px;
    }

    .owner-edit-notice {
        padding: 13px;
        border: 1px solid #f3dfc8;
        border-radius: 10px;
        background: #fffaf4;
        color: #875126;
        font-size: 11px;
        line-height: 1.7;
    }

    .owner-edit-notice i {
        margin-right: 5px;
    }

    .owner-edit-back {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: var(--owner-muted);
        text-decoration: none;
        font-size: 12px;
        font-weight: 700;
    }

    .owner-edit-back:hover {
        color: var(--owner-orange);
    }

    @media (max-width: 991.98px) {
        .owner-edit-layout {
            grid-template-columns: minmax(0, 1fr);
        }

        .owner-edit-aside {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .owner-edit-hero {
            align-items: flex-start;
            padding: 20px;
        }

        .owner-edit-hero-actions {
            width: 100%;
        }

        .owner-edit-hero-actions .btn-terra-outline {
            width: 100%;
        }

        .owner-edit-card-header,
        .owner-edit-card-body {
            padding: 18px;
        }

        .owner-edit-aside {
            grid-template-columns: minmax(0, 1fr);
        }
    }
</style>
@endpush

@section('content')
<div class="owner-edit-page">

    {{-- Page header --}}
    <div class="owner-edit-hero">
        <div>
            <nav class="owner-edit-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <i class="bi bi-chevron-right"></i>
                <a href="{{ route('admin.owners.index') }}">Property Owners</a>
                <i class="bi bi-chevron-right"></i>
                <a href="{{ route('admin.owners.show', $owner) }}">Owner profile</a>
                <i class="bi bi-chevron-right"></i>
                <span>Edit</span>
            </nav>

            <h1 class="owner-edit-title">Edit property owner</h1>

            <p class="owner-edit-description">
                Update registration details, contact information, address,
                and verification status for this owner.
            </p>
        </div>

        <div class="owner-edit-hero-actions">
            <a href="{{ route('admin.owners.show', $owner) }}"
               class="btn-terra-outline">
                <i class="bi bi-person-lines-fill"></i>
                View profile
            </a>
        </div>
    </div>

    <div class="owner-edit-layout">

        {{-- Main form --}}
        <div class="owner-edit-card">
            <div class="owner-edit-card-header">
                <div class="owner-edit-identity">
                    <div class="owner-edit-avatar">
                        <i class="bi bi-person-gear"></i>
                    </div>

                    <div>
                        <h2 class="owner-edit-owner-name">
                            {{ $owner->name }}
                        </h2>

                        <div class="owner-edit-owner-number">
                            Owner number:
                            <strong>
                                {{ $owner->owner_number ?: 'Not assigned' }}
                            </strong>
                        </div>
                    </div>
                </div>

                <span class="owner-edit-label">
                    <i class="bi bi-pencil-square"></i>
                    Editing record
                </span>
            </div>

            <div class="owner-edit-card-body">
                @if ($errors->any())
                    <div class="alert alert-danger d-flex align-items-start gap-2 mb-4"
                         role="alert">
                        <i class="bi bi-exclamation-circle mt-1"></i>
                        <div>
                            <strong>Please review the form.</strong>
                            <div class="small mt-1">
                                Some fields contain errors. Correct the highlighted
                                details and save again.
                            </div>
                        </div>
                    </div>
                @endif

                <form method="POST"
                      action="{{ route('admin.owners.update', $owner) }}">
                    @csrf
                    @method('PUT')

                    @include('admin.owners._form')
                </form>
            </div>
        </div>

        {{-- Sidebar --}}
        <aside class="owner-edit-aside">

            <div class="owner-edit-aside-card">
                <h3 class="owner-edit-aside-title">
                    <i class="bi bi-card-checklist"></i>
                    Current record
                </h3>

                <div class="owner-edit-summary">
                    <div class="owner-edit-summary-row">
                        <span class="owner-edit-summary-label">Owner number</span>
                        <span class="owner-edit-summary-value">
                            {{ $owner->owner_number ?: 'Not assigned' }}
                        </span>
                    </div>

                    <div class="owner-edit-summary-row">
                        <span class="owner-edit-summary-label">Owner type</span>
                        <span class="owner-edit-summary-value">
                            {{ ucfirst($owner->owner_type ?: 'individual') }}
                        </span>
                    </div>

                    <div class="owner-edit-summary-row">
                        <span class="owner-edit-summary-label">Verification</span>
                        @php
                            $verificationStatus = strtolower(
                                $owner->verification_status ?: 'pending'
                            );
                            $verificationClass = in_array(
                                $verificationStatus,
                                ['verified', 'pending', 'rejected']
                            ) ? $verificationStatus : 'unknown';
                        @endphp
                        <span class="owner-edit-status {{ $verificationClass }}">
                            {{ str_replace('_', ' ', $verificationStatus) }}
                        </span>
                    </div>

                    <div class="owner-edit-summary-row">
                        <span class="owner-edit-summary-label">Account status</span>
                        @php
                            $accountStatus = strtolower($owner->status ?: 'inactive');
                            $accountClass = in_array(
                                $accountStatus,
                                ['active', 'inactive']
                            ) ? $accountStatus : 'unknown';
                        @endphp
                        <span class="owner-edit-status {{ $accountClass }}">
                            {{ str_replace('_', ' ', $accountStatus) }}
                        </span>
                    </div>

                    <div class="owner-edit-summary-row">
                        <span class="owner-edit-summary-label">Email address</span>
                        <span class="owner-edit-summary-value">
                            {{ $owner->email ?: 'Not provided' }}
                        </span>
                    </div>

                    <div class="owner-edit-summary-row">
                        <span class="owner-edit-summary-label">Phone number</span>
                        <span class="owner-edit-summary-value">
                            {{ $owner->phone ?: 'Not provided' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="owner-edit-aside-card">
                <h3 class="owner-edit-aside-title">
                    <i class="bi bi-shield-check"></i>
                    Editing checklist
                </h3>

                <ul class="owner-edit-guidance">
                    <li>
                        <i class="bi bi-check-circle"></i>
                        <span>Confirm the owner's name and identification details.</span>
                    </li>
                    <li>
                        <i class="bi bi-check-circle"></i>
                        <span>Check that contact and address information is up to date.</span>
                    </li>
                    <li>
                        <i class="bi bi-check-circle"></i>
                        <span>Review verification status before confirming changes.</span>
                    </li>
                </ul>
            </div>

            <div class="owner-edit-aside-card">
                <h3 class="owner-edit-aside-title">
                    <i class="bi bi-info-circle"></i>
                    Important
                </h3>

                <div class="owner-edit-notice">
                    <i class="bi bi-shield-lock"></i>
                    Only mark an owner as verified after the required identity
                    or company checks have been completed.
                </div>
            </div>

            <a href="{{ route('admin.owners.show', $owner) }}"
               class="owner-edit-back">
                <i class="bi bi-arrow-left"></i>
                Return to owner profile
            </a>

        </aside>
    </div>
</div>
@endsection
