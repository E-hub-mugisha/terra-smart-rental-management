@php
    $editing = isset($owner) && $owner->exists;
    $hasAccount = $editing && $owner->user_id;
    $provinces = config('rwanda.provinces', []);

    $currentType = old('owner_type', $owner->owner_type ?: 'individual');
    $currentCountry = old('country', $owner->country ?: 'Rwanda');
    $currentProvince = old('province', $owner->province);
    $currentDistrict = old('district', $owner->district);
@endphp

@once
    @push('styles')
        <style>
            .owner-form-section + .owner-form-section {
                margin-top: 30px;
                padding-top: 26px;
                border-top: 1px solid var(--terra-border, #e7eaf0);
            }

            .owner-form-section-title {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 5px;
                color: var(--terra-navy, #19265d);
                font-size: 14px;
                font-weight: 800;
            }

            .owner-form-section-title i {
                color: var(--terra-orange, #D05208);
                font-size: 18px;
            }

            .owner-form-section-description {
                margin-bottom: 20px;
                color: var(--terra-muted, #748094);
                font-size: 12px;
                line-height: 1.7;
            }

            .owner-form-field { min-width: 0; }

            .owner-form-field .form-label {
                display: block;
                margin-bottom: 8px;
                color: var(--terra-navy, #19265d);
                font-size: 12px;
                font-weight: 700;
            }

            .owner-form-field .required { color: #d92d20; }

            .owner-form-field .form-control,
            .owner-form-field .form-select {
                min-height: 44px;
                border: 1px solid var(--terra-border, #e7eaf0);
                border-radius: 9px;
                background-color: #fff;
                color: var(--terra-navy, #19265d);
                font-size: 12px;
                box-shadow: none;
                transition: border-color .2s ease, box-shadow .2s ease;
            }

            .owner-form-field textarea.form-control {
                min-height: 115px;
                resize: vertical;
            }

            .owner-form-field .form-control::placeholder { color: #a1a9b6; }

            .owner-form-field .form-control:focus,
            .owner-form-field .form-select:focus {
                border-color: var(--terra-orange, #D05208);
                box-shadow: 0 0 0 3px rgba(208, 82, 8, .09);
            }

            .owner-form-field .form-select:disabled {
                background-color: #f8f9fc;
                color: #a1a9b6;
                cursor: not-allowed;
            }

            .owner-form-field .form-control.is-invalid,
            .owner-form-field .form-select.is-invalid {
                border-color: #d92d20;
            }

            .owner-form-field .invalid-feedback { font-size: 11px; }

            .owner-form-help {
                margin-top: 6px;
                color: var(--terra-muted, #748094);
                font-size: 11px;
                line-height: 1.6;
            }

            /* Owner type segmented cards */
            .owner-type-switch {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .owner-type-option { position: relative; margin: 0; }

            .owner-type-option input {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }

            .owner-type-card {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 13px 15px;
                border: 1px solid var(--terra-border, #e7eaf0);
                border-radius: 11px;
                background: #fff;
                cursor: pointer;
                transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
            }

            .owner-type-card:hover { border-color: #f0c9ae; }

            .owner-type-card i {
                display: grid;
                place-items: center;
                width: 36px;
                height: 36px;
                flex-shrink: 0;
                border-radius: 10px;
                background: #f4f6fa;
                color: var(--terra-muted, #748094);
                font-size: 17px;
                transition: all .2s ease;
            }

            .owner-type-card strong {
                display: block;
                color: var(--terra-navy, #19265d);
                font-size: 12px;
            }

            .owner-type-card small {
                color: var(--terra-muted, #748094);
                font-size: 11px;
            }

            .owner-type-option input:checked + .owner-type-card {
                border-color: var(--terra-orange, #D05208);
                background: #fffaf6;
                box-shadow: 0 0 0 3px rgba(208, 82, 8, .09);
            }

            .owner-type-option input:checked + .owner-type-card i {
                background: #fff0e5;
                color: var(--terra-orange, #D05208);
            }

            .owner-type-option input:focus-visible + .owner-type-card {
                outline: 2px solid var(--terra-orange, #D05208);
                outline-offset: 2px;
            }

            /* Account callout */
            .owner-account-callout {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                margin-top: 20px;
                padding: 14px 16px;
                border: 1px solid #cfe9dd;
                border-radius: 11px;
                background: #f3fbf7;
                color: #1d5c43;
                font-size: 12px;
                line-height: 1.7;
            }

            .owner-account-callout > i {
                margin-top: 2px;
                color: #16845b;
                font-size: 17px;
            }

            .owner-account-callout strong { color: #14543c; }

            .owner-account-callout .js-email-preview {
                font-weight: 700;
                word-break: break-all;
            }

            .owner-location-badge {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                margin-left: 6px;
                padding: 1px 8px;
                border-radius: 20px;
                background: #fff0e5;
                color: var(--terra-orange, #D05208);
                font-size: 11px;
                font-weight: 700;
            }

            .owner-form-actions {
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 15px;
                margin-top: 30px;
                padding-top: 22px;
                border-top: 1px solid var(--terra-border, #e7eaf0);
            }

            .owner-form-action-note {
                display: flex;
                align-items: flex-start;
                gap: 8px;
                color: var(--terra-muted, #748094);
                font-size: 11px;
                line-height: 1.6;
            }

            .owner-form-action-note i {
                margin-top: 2px;
                color: var(--terra-orange, #D05208);
            }

            .owner-form-actions .btn-terra,
            .owner-form-actions .btn-terra-outline {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                min-height: 42px;
                padding: 10px 17px;
                border-radius: 9px;
                font-size: 12px;
                font-weight: 700;
                text-decoration: none;
                transition: all .2s ease;
            }

            .owner-form-actions .btn-terra {
                border: 1px solid var(--terra-orange, #D05208);
                background: var(--terra-orange, #D05208);
                color: #fff;
            }

            .owner-form-actions .btn-terra:hover {
                border-color: #b84607;
                background: #b84607;
                color: #fff;
                transform: translateY(-1px);
            }

            .owner-form-actions .btn-terra:disabled {
                opacity: .7;
                cursor: wait;
                transform: none;
            }

            .owner-form-actions .btn-terra-outline {
                border: 1px solid var(--terra-border, #e7eaf0);
                background: #fff;
                color: var(--terra-navy, #19265d);
            }

            .owner-form-actions .btn-terra-outline:hover {
                border-color: var(--terra-navy, #19265d);
                background: #f8f9fc;
            }

            @media (max-width: 575.98px) {
                .owner-type-switch { grid-template-columns: minmax(0, 1fr); }

                .owner-form-actions { align-items: stretch; }
                .owner-form-actions > .d-flex { width: 100%; }

                .owner-form-actions .btn-terra,
                .owner-form-actions .btn-terra-outline { flex: 1; }
            }
        </style>
    @endpush
@endonce

{{-- Owner identity --}}
<section class="owner-form-section">
    <div class="owner-form-section-title">
        <i class="bi bi-person-vcard"></i>
        Owner identity
    </div>

    <p class="owner-form-section-description">
        Identify the owner as an individual or company and provide the relevant
        identification details.
    </p>

    <div class="row g-4">
        <div class="col-12 owner-form-field">
            <span class="form-label">Owner type <span class="required">*</span></span>

            <div class="owner-type-switch" role="radiogroup" aria-label="Owner type">
                <label class="owner-type-option">
                    <input type="radio" name="owner_type" value="individual"
                           @checked($currentType === 'individual')>
                    <span class="owner-type-card">
                        <i class="bi bi-person"></i>
                        <span>
                            <strong>Individual</strong>
                            <small>A person owning property</small>
                        </span>
                    </span>
                </label>

                <label class="owner-type-option">
                    <input type="radio" name="owner_type" value="company"
                           @checked($currentType === 'company')>
                    <span class="owner-type-card">
                        <i class="bi bi-building"></i>
                        <span>
                            <strong>Company</strong>
                            <small>A registered business or organization</small>
                        </span>
                    </span>
                </label>
            </div>

            @error('owner_type')
                <div class="text-danger mt-2" style="font-size:11px;">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-6 owner-form-field">
            <label for="name" class="form-label" id="name-label">
                <span class="js-name-label">Full name</span> <span class="required">*</span>
            </label>

            <input
                type="text"
                name="name"
                id="name"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $owner->name) }}"
                maxlength="255"
                autocomplete="name"
                placeholder="Enter full name"
                required>

            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-6 owner-form-field" id="company-name-field">
            <label for="company_name" class="form-label">
                Company name <span class="required">*</span>
            </label>

            <input
                type="text"
                name="company_name"
                id="company_name"
                class="form-control @error('company_name') is-invalid @enderror"
                value="{{ old('company_name', $owner->company_name) }}"
                maxlength="255"
                placeholder="Registered company name">

            @error('company_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-6 owner-form-field">
            <label for="national_id" class="form-label">
                <span class="js-id-label">National ID</span>
            </label>

            <input
                type="text"
                name="national_id"
                id="national_id"
                class="form-control @error('national_id') is-invalid @enderror"
                value="{{ old('national_id', $owner->national_id) }}"
                maxlength="100"
                placeholder="Identification number">

            <div class="owner-form-help js-id-help">
                Enter the 16-digit national ID number.
            </div>

            @error('national_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</section>

{{-- Contact information --}}
<section class="owner-form-section">
    <div class="owner-form-section-title">
        <i class="bi bi-telephone"></i>
        Contact information
    </div>

    <p class="owner-form-section-description">
        Provide the owner's primary email address and telephone number.
    </p>

    <div class="row g-4">
        <div class="col-12 col-md-6 owner-form-field">
            <label for="email" class="form-label">
                Email address
                @if (! $editing || $hasAccount)
                    <span class="required">*</span>
                @endif
            </label>

            <input
                type="email"
                name="email"
                id="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email', $owner->email) }}"
                maxlength="255"
                autocomplete="email"
                placeholder="owner@example.com"
                @required(! $editing || $hasAccount)>

            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-6 owner-form-field">
            <label for="phone" class="form-label">Phone number</label>

            <input
                type="tel"
                name="phone"
                id="phone"
                class="form-control @error('phone') is-invalid @enderror"
                value="{{ old('phone', $owner->phone) }}"
                maxlength="30"
                autocomplete="tel"
                inputmode="tel"
                placeholder="+250 7XX XXX XXX">

            @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    @if (! $editing)
        <div class="owner-account-callout">
            <i class="bi bi-envelope-check"></i>
            <div>
                <strong>A login account is created automatically.</strong><br>
                A secure password is generated and emailed to
                <span class="js-email-preview">the address above</span>
                as soon as you save. The account is linked to this owner record.
            </div>
        </div>
    @elseif ($hasAccount)
        <div class="owner-account-callout">
            <i class="bi bi-person-check"></i>
            <div>
                <strong>Linked login account.</strong><br>
                Changing the name or email here also updates the owner's login.
            </div>
        </div>
    @else
        <div class="owner-account-callout" style="border-color:#f3dfc8;background:#fffaf4;color:#875126;">
            <i class="bi bi-info-circle" style="color:#D05208;"></i>
            <div>
                <strong>No login account yet.</strong><br>
                Add an email address and save to create one and send the credentials.
            </div>
        </div>
    @endif
</section>

{{-- Address --}}
<section class="owner-form-section">
    <div class="owner-form-section-title">
        <i class="bi bi-geo-alt"></i>
        Address information
    </div>

    <p class="owner-form-section-description">
        For Rwanda, pick the province and the districts load automatically.
        Choosing a district first fills in its province for you.
    </p>

    <div class="row g-4">
        <div class="col-12 col-md-6 owner-form-field">
            <label for="country" class="form-label">
                Country <span class="required">*</span>
            </label>

            <input
                type="text"
                name="country"
                id="country"
                list="country-list"
                class="form-control @error('country') is-invalid @enderror"
                value="{{ $currentCountry }}"
                maxlength="100"
                autocomplete="country-name"
                required>

            <datalist id="country-list">
                @foreach (['Rwanda', 'Uganda', 'Kenya', 'Tanzania', 'Burundi', 'Democratic Republic of the Congo', 'United States', 'United Kingdom', 'Belgium', 'France', 'Canada'] as $c)
                    <option value="{{ $c }}"></option>
                @endforeach
            </datalist>

            @error('country')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-6 owner-form-field">
            <label for="province" class="form-label">
                Province / City
                <span class="owner-location-badge js-rw-badge d-none">
                    <i class="bi bi-geo-alt-fill"></i> Rwanda
                </span>
            </label>

            <select id="province-select" name="province"
                    class="form-select @error('province') is-invalid @enderror">
                <option value="">Select province</option>
                @foreach ($provinces as $provinceName => $districtList)
                    <option value="{{ $provinceName }}" @selected($currentProvince === $provinceName)>
                        {{ $provinceName }}
                    </option>
                @endforeach
            </select>

            <input type="text" id="province-text" name="province"
                   class="form-control d-none @error('province') is-invalid @enderror"
                   value="{{ $currentProvince }}" maxlength="100"
                   placeholder="State / region / city" disabled>

            @error('province')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-6 owner-form-field">
            <label for="district" class="form-label">District</label>

            <select id="district-select" name="district"
                    class="form-select @error('district') is-invalid @enderror">
                <option value="">Select district</option>
            </select>

            <input type="text" id="district-text" name="district"
                   class="form-control d-none @error('district') is-invalid @enderror"
                   value="{{ $currentDistrict }}" maxlength="100"
                   placeholder="District / county" disabled>

            @error('district')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-6 owner-form-field">
            <label for="address" class="form-label">Physical address</label>

            <input
                type="text"
                name="address"
                id="address"
                class="form-control @error('address') is-invalid @enderror"
                value="{{ old('address', $owner->address) }}"
                maxlength="255"
                autocomplete="street-address"
                placeholder="Sector, cell, street or other address details">

            @error('address')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</section>

{{-- Verification and account status --}}
<section class="owner-form-section">
    <div class="owner-form-section-title">
        <i class="bi bi-shield-check"></i>
        Verification and account status
    </div>

    <p class="owner-form-section-description">
        Choose the appropriate verification and account status based on your
        organization's review process.
    </p>

    <div class="row g-4">
        <div class="col-12 col-md-6 owner-form-field">
            <label for="verification_status" class="form-label">
                Verification status <span class="required">*</span>
            </label>

            <select
                name="verification_status"
                id="verification_status"
                class="form-select @error('verification_status') is-invalid @enderror"
                required>
                @foreach([
                    'pending' => 'Pending review',
                    'verified' => 'Verified',
                    'rejected' => 'Rejected',
                ] as $value => $label)
                    <option value="{{ $value }}"
                        @selected(old('verification_status', $owner->verification_status ?: 'pending') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            @error('verification_status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-6 owner-form-field">
            <label for="status" class="form-label">
                Account status <span class="required">*</span>
            </label>

            <select
                name="status"
                id="status"
                class="form-select @error('status') is-invalid @enderror"
                required>
                <option value="active"
                    @selected(old('status', $owner->status ?: 'active') === 'active')>
                    Active
                </option>
                <option value="inactive"
                    @selected(old('status', $owner->status) === 'inactive')>
                    Inactive
                </option>
            </select>

            @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</section>

{{-- Internal notes --}}
<section class="owner-form-section">
    <div class="owner-form-section-title">
        <i class="bi bi-journal-text"></i>
        Internal notes
    </div>

    <p class="owner-form-section-description">
        Add any relevant administrative notes for future reference.
    </p>

    <div class="owner-form-field">
        <label for="notes" class="form-label">Notes</label>

        <textarea
            name="notes"
            id="notes"
            rows="4"
            class="form-control @error('notes') is-invalid @enderror"
            placeholder="Enter any relevant notes about this owner...">{{ old('notes', $owner->notes) }}</textarea>

        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</section>

{{-- Actions --}}
<div class="owner-form-actions">
    <div class="owner-form-action-note">
        <i class="bi bi-info-circle"></i>
        <span>
            Review the information before
            {{ $editing ? 'updating this record.' : 'creating the owner record and sending the credentials.' }}
        </span>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.owners.index') }}" class="btn-terra-outline">
            Cancel
        </a>

        <button type="submit" class="btn-terra" id="owner-submit">
            <i class="bi {{ $editing ? 'bi-check2-circle' : 'bi-person-plus' }}"></i>
            <span class="js-submit-label">{{ $editing ? 'Save changes' : 'Create owner' }}</span>
        </button>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                var provinces = @json($provinces);
                var initial = {
                    province: @json($currentProvince),
                    district: @json($currentDistrict)
                };
                var editing = @json($editing);

                var $ = function (id) { return document.getElementById(id); };
                var form = $('owner-submit').closest('form');
                var country = $('country');
                var provSel = $('province-select'), provTxt = $('province-text');
                var distSel = $('district-select'), distTxt = $('district-text');
                var badge = document.querySelector('.js-rw-badge');

                // ---------- Owner type ----------
                var typeInputs = document.querySelectorAll('input[name="owner_type"]');
                var companyField = $('company-name-field');
                var companyInput = $('company_name');
                var nameLabel = document.querySelector('.js-name-label');
                var nameInput = $('name');
                var idLabel = document.querySelector('.js-id-label');
                var idHelp = document.querySelector('.js-id-help');
                var idInput = $('national_id');

                function syncType() {
                    var company = document.querySelector('input[name="owner_type"]:checked').value === 'company';

                    companyField.classList.toggle('d-none', !company);
                    companyInput.disabled = !company;
                    companyInput.required = company;

                    nameLabel.textContent = company ? 'Contact person' : 'Full name';
                    nameInput.placeholder = company ? 'Name of the contact person' : 'Enter full name';
                    idLabel.textContent = company ? 'Company registration / TIN' : 'National ID';
                    idInput.placeholder = company ? 'RDB registration or TIN number' : 'Identification number';
                    idHelp.textContent = company
                        ? 'RDB registration number or tax identification number.'
                        : 'Enter the 16-digit national ID number.';
                }

                typeInputs.forEach(function (i) { i.addEventListener('change', syncType); });

                // ---------- Location ----------
                function isRwanda() {
                    return country.value.trim().toLowerCase() === 'rwanda';
                }

                function buildDistricts(province, selected) {
                    distSel.innerHTML = '';
                    distSel.appendChild(new Option(province ? 'Select district' : 'Select district (any province)', ''));

                    if (province && provinces[province]) {
                        provinces[province].forEach(function (d) {
                            distSel.appendChild(new Option(d, d, false, d === selected));
                        });
                    } else {
                        // No province yet: list every district grouped by province.
                        Object.keys(provinces).forEach(function (p) {
                            var group = document.createElement('optgroup');
                            group.label = p;
                            provinces[p].forEach(function (d) {
                                group.appendChild(new Option(d, d, false, d === selected));
                            });
                            distSel.appendChild(group);
                        });
                    }
                }

                function syncCountry() {
                    var rw = isRwanda();

                    provSel.classList.toggle('d-none', !rw);
                    provSel.disabled = !rw;
                    provTxt.classList.toggle('d-none', rw);
                    provTxt.disabled = rw;

                    distSel.classList.toggle('d-none', !rw);
                    distSel.disabled = !rw;
                    distTxt.classList.toggle('d-none', rw);
                    distTxt.disabled = rw;

                    badge.classList.toggle('d-none', !rw);

                    if (rw) {
                        buildDistricts(provSel.value, distSel.value || initial.district);
                    }
                }

                provSel.addEventListener('change', function () {
                    buildDistricts(provSel.value, '');
                    // If the province has a single obvious next step, keep focus flowing.
                    if (provSel.value) { distSel.focus(); }
                });

                distSel.addEventListener('change', function () {
                    if (!distSel.value) { return; }

                    var chosen = distSel.value;

                    // Auto-fill the parent province of the selected district.
                    Object.keys(provinces).forEach(function (p) {
                        if (provinces[p].indexOf(chosen) !== -1) { provSel.value = p; }
                    });

                    buildDistricts(provSel.value, chosen);
                });

                country.addEventListener('input', syncCountry);
                country.addEventListener('change', function () {
                    // Reset stale values when switching between Rwanda and elsewhere.
                    provSel.value = ''; provTxt.value = ''; distTxt.value = '';
                    buildDistricts('', '');
                    syncCountry();
                });

                // ---------- Email preview ----------
                var emailInput = $('email');
                var preview = document.querySelector('.js-email-preview');

                if (preview) {
                    emailInput.addEventListener('input', function () {
                        preview.textContent = emailInput.value.trim() || 'the address above';
                    });
                    if (emailInput.value.trim()) { preview.textContent = emailInput.value.trim(); }
                }

                // ---------- Double-submit guard (the form sends an email) ----------
                var submitBtn = $('owner-submit');

                form.addEventListener('submit', function () {
                    if (!form.checkValidity()) { return; }
                    submitBtn.disabled = true;
                    submitBtn.querySelector('.js-submit-label').textContent =
                        editing ? 'Saving…' : 'Creating & emailing…';
                });

                // ---------- Init ----------
                if (initial.province && provinces[initial.province]) {
                    provSel.value = initial.province;
                }
                syncType();
                syncCountry();
            })();
        </script>
    @endpush
@endonce