@extends('layouts.app')

@section('title', 'Add Property Owner')
@section('page_heading', 'Register Property Owner')
@section('page_subtitle', 'Create and manage property ownership records.')

@push('styles')
<style>
    .owner-create-page {
        --owner-orange: var(--terra-orange, #D05208);
        --owner-navy: var(--terra-navy, #19265d);
        --owner-muted: var(--terra-muted, #748094);
        --owner-border: var(--terra-border, #e7eaf0);
        --owner-surface: #ffffff;
        color: var(--owner-navy);
    }

    .owner-create-hero {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        padding: 25px 28px;
        margin-bottom: 24px;
        border: 1px solid var(--owner-border);
        border-radius: 18px;
        background: linear-gradient(120deg, #fff 0%, #fffaf6 100%);
    }

    .owner-create-hero::after {
        content: "";
        position: absolute;
        width: 180px;
        height: 180px;
        right: -65px;
        top: -85px;
        border-radius: 50%;
        background: rgba(208, 82, 8, .055);
        pointer-events: none;
    }

    .owner-create-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        margin-bottom: 13px;
        color: var(--owner-muted);
        font-size: 12px;
    }

    .owner-create-breadcrumb a {
        color: var(--owner-muted);
        text-decoration: none;
    }

    .owner-create-breadcrumb a:hover {
        color: var(--owner-orange);
    }

    .owner-create-breadcrumb i {
        font-size: 11px;
    }

    .owner-create-title {
        margin: 0 0 8px;
        color: var(--owner-navy);
        font-size: clamp(23px, 3vw, 30px);
        font-weight: 800;
        letter-spacing: -.7px;
    }

    .owner-create-description {
        margin: 0;
        color: var(--owner-muted);
        font-size: 13px;
        line-height: 1.7;
        max-width: 650px;
    }

    .owner-create-hero-icon {
        z-index: 1;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        width: 68px;
        height: 68px;
        border-radius: 19px;
        color: var(--owner-orange);
        background: #fff0e5;
        font-size: 29px;
    }

    .owner-create-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 270px;
        align-items: start;
        gap: 22px;
    }

    .owner-create-card,
    .owner-create-aside-card {
        background: var(--owner-surface);
        border: 1px solid var(--owner-border);
        border-radius: 16px;
        box-shadow: 0 4px 18px rgba(25, 38, 93, .035);
    }

    .owner-create-card {
        overflow: hidden;
    }

    .owner-create-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        padding: 22px 25px;
        border-bottom: 1px solid var(--owner-border);
    }

    .owner-create-card-heading {
        display: flex;
        align-items: center;
        gap: 13px;
    }

    .owner-create-card-icon {
        display: grid;
        place-items: center;
        width: 44px;
        height: 44px;
        flex-shrink: 0;
        border-radius: 12px;
        color: var(--owner-orange);
        background: #fff0e5;
        font-size: 20px;
    }

    .owner-create-card-title {
        margin: 0 0 4px;
        color: var(--owner-navy);
        font-size: 16px;
        font-weight: 800;
    }

    .owner-create-card-subtitle {
        margin: 0;
        color: var(--owner-muted);
        font-size: 12px;
        line-height: 1.6;
    }

    .owner-required-note {
        color: var(--owner-muted);
        font-size: 11px;
        white-space: nowrap;
    }

    .owner-required-note span {
        color: #d92d20;
        font-weight: 800;
    }

    .owner-create-card-body {
        padding: 25px;
    }

    .owner-create-aside {
        display: grid;
        gap: 16px;
    }

    .owner-create-aside-card {
        padding: 20px;
    }

    .owner-aside-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0 0 15px;
        font-size: 13px;
        font-weight: 800;
    }

    .owner-aside-title i {
        color: var(--owner-orange);
        font-size: 17px;
    }

    .owner-aside-list {
        display: grid;
        gap: 15px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .owner-aside-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        color: var(--owner-muted);
        font-size: 11px;
        line-height: 1.7;
    }

    .owner-aside-item i {
        margin-top: 2px;
        color: #16845b;
        font-size: 14px;
    }

    .owner-aside-item strong {
        display: block;
        margin-bottom: 2px;
        color: var(--owner-navy);
        font-size: 12px;
        font-weight: 700;
    }

    .owner-aside-notice {
        padding: 14px;
        border: 1px solid #f3dfc8;
        border-radius: 11px;
        background: #fffaf4;
        color: #875126;
        font-size: 11px;
        line-height: 1.7;
    }

    .owner-aside-notice i {
        margin-right: 5px;
    }

    .owner-create-back {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: var(--owner-muted);
        text-decoration: none;
        font-size: 12px;
        font-weight: 700;
    }

    .owner-create-back:hover {
        color: var(--owner-orange);
    }

    @media (max-width: 991.98px) {
        .owner-create-layout {
            grid-template-columns: minmax(0, 1fr);
        }

        .owner-create-aside {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .owner-create-hero {
            padding: 20px;
        }

        .owner-create-hero-icon {
            display: none;
        }

        .owner-create-card-header,
        .owner-create-card-body {
            padding: 18px;
        }

        .owner-create-card-header {
            align-items: flex-start;
        }

        .owner-required-note {
            display: none;
        }

        .owner-create-aside {
            grid-template-columns: minmax(0, 1fr);
        }
    }
</style>
@endpush

@section('content')
<div class="owner-create-page">

    <div class="owner-create-hero">
        <div>
            <nav class="owner-create-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <i class="bi bi-chevron-right"></i>
                <a href="{{ route('admin.owners.index') }}">Property Owners</a>
                <i class="bi bi-chevron-right"></i>
                <span>Add owner</span>
            </nav>

            <h1 class="owner-create-title">Register a property owner</h1>

            <p class="owner-create-description">
                Create an owner profile and login account in one step. Credentials are
                emailed to the owner automatically.
            </p>
        </div>

        <div class="owner-create-hero-icon" aria-hidden="true">
            <i class="bi bi-person-vcard"></i>
        </div>
    </div>

    <div class="owner-create-layout">

        <div class="owner-create-card">
            <div class="owner-create-card-header">
                <div class="owner-create-card-heading">
                    <div class="owner-create-card-icon">
                        <i class="bi bi-person-plus"></i>
                    </div>

                    <div>
                        <h2 class="owner-create-card-title">Owner registration</h2>
                        <p class="owner-create-card-subtitle">
                            Enter the owner’s details in the sections below.
                        </p>
                    </div>
                </div>

                <div class="owner-required-note">
                    <span>*</span> Required fields
                </div>
            </div>

            <div class="owner-create-card-body">
                @if (session('error'))
                    <div class="alert alert-danger d-flex align-items-start gap-2 mb-4" role="alert">
                        <i class="bi bi-exclamation-circle mt-1"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger d-flex align-items-start gap-2 mb-4"
                         role="alert">
                        <i class="bi bi-exclamation-circle mt-1"></i>
                        <div>
                            <strong>Please check the form.</strong>
                            <div class="small mt-1">
                                Some details need your attention. Review the highlighted
                                fields and try again.
                            </div>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.owners.store') }}">
                    @csrf

                    @include('admin.owners._form')
                </form>
            </div>
        </div>

        <aside class="owner-create-aside">

            <div class="owner-create-aside-card">
                <h3 class="owner-aside-title">
                    <i class="bi bi-clipboard-check"></i>
                    Registration checklist
                </h3>

                <ul class="owner-aside-list">
                    <li class="owner-aside-item">
                        <i class="bi bi-check-circle"></i>
                        <div>
                            <strong>Identity details</strong>
                            Confirm the owner’s name and relevant identification.
                        </div>
                    </li>

                    <li class="owner-aside-item">
                        <i class="bi bi-check-circle"></i>
                        <div>
                            <strong>Contact information</strong>
                            Add an accurate email address. The login credentials are sent there.
                        </div>
                    </li>

                    <li class="owner-aside-item">
                        <i class="bi bi-check-circle"></i>
                        <div>
                            <strong>Location</strong>
                            Pick the province and district, then add address details.
                        </div>
                    </li>

                    <li class="owner-aside-item">
                        <i class="bi bi-check-circle"></i>
                        <div>
                            <strong>Verification</strong>
                            Set the appropriate verification and account status.
                        </div>
                    </li>
                </ul>
            </div>

            <div class="owner-create-aside-card">
                <h3 class="owner-aside-title">
                    <i class="bi bi-shield-lock"></i>
                    Before you save
                </h3>

                <div class="owner-aside-notice">
                    <i class="bi bi-info-circle"></i>
                    Saving creates a login account for the owner and emails the
                    credentials immediately, so double-check the email address.
                    Only mark an owner as verified when your required checks are complete.
                </div>
            </div>

            <a href="{{ route('admin.owners.index') }}" class="owner-create-back">
                <i class="bi bi-arrow-left"></i>
                Back to property owners
            </a>

        </aside>
    </div>
</div>
@endsection