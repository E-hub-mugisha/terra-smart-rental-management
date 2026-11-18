@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="container-fluid py-4 profile-page">

    <div class="mb-4">
        <span class="text-muted small">Tenant portal / Account</span>
        <h1 class="h3 fw-bold mt-2 mb-1">My Profile</h1>
        <p class="text-muted mb-0">
            Manage your personal information and account security.
        </p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-2">Please correct the following errors:</div>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $initials = collect(explode(' ', trim($tenant->full_name ?? 'Tenant')))
            ->filter()
            ->take(2)
            ->map(fn($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
    @endphp

    <div class="row g-4">

        {{-- Profile summary --}}
        <div class="col-lg-4 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body text-center p-4">
                    <div class="profile-avatar mx-auto mb-3">
                        {{ $initials ?: 'T' }}
                    </div>

                    <h2 class="h5 fw-bold mb-1">
                        {{ $tenant->full_name }}
                    </h2>

                    <p class="text-muted small mb-3">
                        {{ $tenant->email }}
                    </p>

                    <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2">
                        {{ ucfirst($tenant->status ?? 'Active') }} tenant
                    </span>

                    <hr class="my-4">

                    <div class="profile-summary-item">
                        <span>Tenant number</span>
                        <strong>{{ $tenant->tenant_number ?? 'Not assigned' }}</strong>
                    </div>

                    <div class="profile-summary-item">
                        <span>Phone number</span>
                        <strong>{{ $tenant->phone ?: 'Not provided' }}</strong>
                    </div>

                    <div class="profile-summary-item">
                        <span>Account email</span>
                        <strong class="text-break">{{ $tenant->email }}</strong>
                    </div>

                    <div class="profile-summary-item">
                        <span>Member since</span>
                        <strong>{{ $tenant->created_at?->format('M Y') ?? '—' }}</strong>
                    </div>
                </div>
            </div>

            <div class="profile-help rounded-4 p-4">
                <div class="help-icon mb-3">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h3 class="h6 fw-bold">Keep your details up to date</h3>
                <p class="text-muted small mb-0">
                    Accurate contact information helps your property manager
                    reach you about your lease, rent and maintenance requests.
                </p>
            </div>
        </div>

        {{-- Edit forms --}}
        <div class="col-lg-8 col-xl-9">

            <form method="POST" action="{{ route('tenant-portal.profile.update') }}">
                @csrf
                @method('PUT')

                {{-- Personal information --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 px-4 pt-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="section-icon">
                                <i class="bi bi-person"></i>
                            </div>
                            <div>
                                <h2 class="h5 fw-bold mb-1">Personal information</h2>
                                <p class="text-muted small mb-0">
                                    Your basic contact details.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label for="full_name" class="form-label">Full name <span class="text-danger">*</span></label>
                                <input type="text" id="full_name" name="full_name"
                                       class="form-control @error('full_name') is-invalid @enderror"
                                       value="{{ old('full_name', $tenant->full_name) }}"
                                       maxlength="150" required>
                                @error('full_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Email address <span class="text-danger">*</span></label>
                                <input type="email" id="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $tenant->email) }}"
                                       maxlength="255" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone number <span class="text-danger">*</span></label>
                                <input type="tel" id="phone" name="phone" data-intl-phone
                                       class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $tenant->phone) }}"
                                       maxlength="30" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="alternative_phone" class="form-label">Alternative phone</label>
                                <input type="tel" id="alternative_phone" name="alternative_phone"
                                       class="form-control @error('alternative_phone') is-invalid @enderror"
                                       value="{{ old('alternative_phone', $tenant->alternative_phone) }}"
                                       maxlength="30">
                                @error('alternative_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="address" class="form-label">Residential address</label>
                                <textarea id="address" name="address" rows="3"
                                          class="form-control @error('address') is-invalid @enderror"
                                          maxlength="2000"
                                          placeholder="Enter your current residential address">{{ old('address', $tenant->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Emergency contact --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 px-4 pt-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="section-icon">
                                <i class="bi bi-telephone"></i>
                            </div>
                            <div>
                                <h2 class="h5 fw-bold mb-1">Emergency contact</h2>
                                <p class="text-muted small mb-0">
                                    Someone your property manager can contact in an emergency.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="emergency_contact_name" class="form-label">Contact name</label>
                                <input type="text" id="emergency_contact_name"
                                       name="emergency_contact_name"
                                       class="form-control @error('emergency_contact_name') is-invalid @enderror"
                                       value="{{ old('emergency_contact_name', $tenant->emergency_contact_name) }}"
                                       maxlength="150">
                                @error('emergency_contact_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="emergency_contact_phone" class="form-label">Contact phone</label>
                                <input type="tel" id="emergency_contact_phone"
                                       name="emergency_contact_phone"
                                       class="form-control @error('emergency_contact_phone') is-invalid @enderror"
                                       value="{{ old('emergency_contact_phone', $tenant->emergency_contact_phone) }}"
                                       maxlength="30">
                                @error('emergency_contact_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="emergency_contact_relationship" class="form-label">Relationship</label>
                                <input type="text" id="emergency_contact_relationship"
                                       name="emergency_contact_relationship"
                                       class="form-control @error('emergency_contact_relationship') is-invalid @enderror"
                                       value="{{ old('emergency_contact_relationship', $tenant->emergency_contact_relationship) }}"
                                       maxlength="100"
                                       placeholder="e.g. Parent, spouse, sibling">
                                @error('emergency_contact_relationship')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Password --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 px-4 pt-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="section-icon">
                                <i class="bi bi-lock"></i>
                            </div>
                            <div>
                                <h2 class="h5 fw-bold mb-1">Change password</h2>
                                <p class="text-muted small mb-0">
                                    Leave these fields empty to keep your current password.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="current_password" class="form-label">Current password</label>
                                <input type="password" id="current_password"
                                       name="current_password"
                                       autocomplete="current-password"
                                       class="form-control @error('current_password') is-invalid @enderror">
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="password" class="form-label">New password</label>
                                <input type="password" id="password" name="password"
                                       autocomplete="new-password"
                                       class="form-control @error('password') is-invalid @enderror">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirm new password</label>
                                <input type="password" id="password_confirmation"
                                       name="password_confirmation"
                                       autocomplete="new-password"
                                       class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="d-flex flex-wrap justify-content-end gap-2 mb-4">
                    <a href="{{ route('tenant-portal.rentals.index') }}"
                       class="btn btn-light border px-4">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check2-circle me-2"></i>Save changes
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<style>
    .profile-page {
        color: #202938;
        font-family: var(--terra-font);
    }

    .profile-page .card {
        background: #fff;
    }

    .profile-page .card-header {
        border-radius: 16px 16px 0 0;
    }

    .profile-page .form-label {
        font-size: .88rem;
        font-weight: 600;
        color: #344054;
    }

    .profile-page .form-control {
        min-height: 44px;
        border-color: #e1e6ed;
        border-radius: 10px;
        font-size: .9rem;
    }

    .profile-page textarea.form-control {
        min-height: 100px;
    }

    .profile-page .form-control:focus {
        border-color: #D05208;
        box-shadow: 0 0 0 .2rem rgba(208, 82, 8, .12);
    }

    .profile-page .btn-primary {
        background: #D05208;
        border-color: #D05208;
        border-radius: 10px;
        min-height: 43px;
    }

    .profile-page .btn-primary:hover {
        background: #b84808;
        border-color: #b84808;
    }

    .profile-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 88px;
        height: 88px;
        border-radius: 50%;
        background: #fff0e6;
        border: 4px solid #fff;
        outline: 1px solid #f3d4c1;
        color: #D05208;
        font-size: 1.8rem;
        font-weight: 750;
    }

    .profile-summary-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
        text-align: left;
        padding: 13px 0;
        border-bottom: 1px solid #edf0f3;
    }

    .profile-summary-item:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .profile-summary-item span {
        color: #737d8b;
        font-size: .78rem;
    }

    .profile-summary-item strong {
        font-size: .88rem;
        overflow-wrap: anywhere;
    }

    .section-icon,
    .help-icon {
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border-radius: 12px;
        background: #fff0e6;
        color: #D05208;
        font-size: 1.2rem;
    }

    .profile-help {
        background: #fff8f3;
        border: 1px solid #fbe5d7;
    }

    @media (max-width: 575.98px) {
        .profile-page .card-body {
            padding: 1.15rem !important;
        }

        .profile-page .card-header {
            padding: 1.15rem 1.15rem 0 !important;
        }
    }
</style>
@endsection
