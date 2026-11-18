@php
    $editing = isset($manager) && $manager->exists;
    $provinces = config('rwanda.provinces', []);

    $currentCountry = old('country', $manager->country ?: 'Rwanda');
    $currentProvince = old('province', $manager->province);
    $currentDistrict = old('district', $manager->district);
    $currentEmployment = old('employment_type', $manager->employment_type ?: 'full_time');
@endphp

@push('styles')
<style>
    .manager-form {
        --mf-orange: #D05208;
        --mf-navy: #19265d;
        --mf-muted: #687386;
        --mf-border: #e5e9f0;
        --mf-soft: #f7f8fb;
    }

    .manager-form .mf-card {
        background: #fff;
        border: 1px solid var(--mf-border);
        border-radius: 18px;
        overflow: hidden;
        margin-bottom: 22px;
        box-shadow: 0 5px 22px rgba(25, 38, 93, .035);
    }

    .manager-form .mf-card-header {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 22px 26px;
        border-bottom: 1px solid var(--mf-border);
        background: linear-gradient(110deg, #fff 60%, #fafbfe);
    }

    .manager-form .mf-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 46px;
        width: 46px;
        height: 46px;
        border-radius: 14px;
        color: var(--mf-orange);
        background: #fff1e8;
        font-size: 21px;
    }

    .manager-form .mf-icon.navy {
        color: var(--mf-navy);
        background: #edf0fa;
    }

    .manager-form .mf-card-title {
        color: var(--mf-navy);
        font-size: 16px;
        font-weight: 750;
        margin: 0 0 4px;
    }

    .manager-form .mf-card-description {
        color: var(--mf-muted);
        font-size: 13px;
        margin: 0;
        line-height: 1.55;
    }

    .manager-form .mf-card-body {
        padding: 26px;
    }

    .manager-form .form-label {
        color: #30394d;
        font-size: 13px;
        font-weight: 650;
        margin-bottom: 8px;
    }

    .manager-form .form-control,
    .manager-form .form-select {
        min-height: 46px;
        border: 1px solid #dfe4ed;
        border-radius: 10px;
        padding: 10px 13px;
        color: #202b40;
        font-size: 14px;
        background-color: #fff;
        box-shadow: none;
        transition: border-color .2s, box-shadow .2s;
    }

    .manager-form textarea.form-control {
        min-height: 120px;
        resize: vertical;
    }

    .manager-form .form-control:focus,
    .manager-form .form-select:focus {
        border-color: var(--mf-orange);
        box-shadow: 0 0 0 3px rgba(208, 82, 8, .10);
    }

    .manager-form .form-control::placeholder {
        color: #a0a8b7;
    }

    .manager-form .form-text {
        color: var(--mf-muted);
        font-size: 12px;
        line-height: 1.5;
        margin-top: 7px;
    }

    .manager-form .mf-required {
        color: #dc3545;
    }

    .manager-form .mf-error-summary {
        border: 1px solid #f3c7ca;
        background: #fff7f7;
        border-radius: 14px;
        padding: 18px 22px;
        color: #842029;
        margin-bottom: 22px;
    }

    .manager-form .mf-error-summary ul {
        margin-bottom: 0;
        padding-left: 22px;
    }

    .manager-form .invalid-feedback {
        font-size: 12px;
    }

    .manager-form .mf-password-note {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        padding: 12px 14px;
        margin-top: 18px;
        background: #f6f8fc;
        border: 1px solid #e8ecf4;
        border-radius: 10px;
        color: #566177;
        font-size: 12px;
        line-height: 1.6;
    }

    .manager-form .mf-footer {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        padding: 20px 0 8px;
    }

    .manager-form .mf-security-note {
        display: flex;
        align-items: center;
        gap: 9px;
        color: var(--mf-muted);
        font-size: 12px;
    }

    .manager-form .mf-security-note i {
        color: #198754;
        font-size: 17px;
    }

    .manager-form .mf-btn {
        min-height: 44px;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        border-radius: 10px;
        padding: 10px 20px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        transition: transform .15s, background .15s;
    }

    .manager-form .mf-btn-primary {
        background: var(--mf-orange);
        border: 1px solid var(--mf-orange);
        color: #fff;
    }

    .manager-form .mf-btn-primary:hover {
        background: #b94707;
        border-color: #b94707;
        color: #fff;
        transform: translateY(-1px);
    }

    .manager-form .mf-btn-secondary {
        color: #475166;
        background: #fff;
        border: 1px solid #dfe4ed;
    }

    .manager-form .mf-btn-secondary:hover {
        background: #f7f8fb;
        color: var(--mf-navy);
    }

    .manager-form .mf-status-hint {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin-top: 9px;
        color: var(--mf-muted);
        font-size: 12px;
        line-height: 1.5;
    }


    .manager-form .mf-callout {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-top: 22px;
        padding: 14px 16px;
        border: 1px solid #cfe9dd;
        border-radius: 12px;
        background: #f3fbf7;
        color: #1d5c43;
        font-size: 12.5px;
        line-height: 1.7;
    }

    .manager-form .mf-callout > i {
        margin-top: 2px;
        color: #16845b;
        font-size: 18px;
    }

    .manager-form .mf-callout.is-warning {
        border-color: #f3dfc8;
        background: #fffaf4;
        color: #875126;
    }

    .manager-form .mf-callout.is-warning > i { color: var(--mf-orange); }

    .manager-form .mf-callout .js-email-preview {
        font-weight: 700;
        word-break: break-all;
    }

    .manager-form .form-select:disabled {
        background-color: #f8f9fc;
        color: #a1a9b6;
        cursor: not-allowed;
    }

    .manager-form .mf-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-left: 6px;
        padding: 1px 8px;
        border-radius: 20px;
        background: #fff1e8;
        color: var(--mf-orange);
        font-size: 11px;
        font-weight: 700;
    }

    .manager-form .mf-segment {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .manager-form .mf-segment label { margin: 0; position: relative; }

    .manager-form .mf-segment input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .manager-form .mf-segment span {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 46px;
        padding: 8px 10px;
        border: 1px solid #dfe4ed;
        border-radius: 10px;
        background: #fff;
        color: #475166;
        font-size: 13px;
        font-weight: 650;
        text-align: center;
        cursor: pointer;
        transition: all .2s;
    }

    .manager-form .mf-segment label:hover span { border-color: #f0c9ae; }

    .manager-form .mf-segment input:checked + span {
        border-color: var(--mf-orange);
        background: #fffaf6;
        color: var(--mf-orange);
        box-shadow: 0 0 0 3px rgba(208, 82, 8, .10);
    }

    .manager-form .mf-segment input:focus-visible + span {
        outline: 2px solid var(--mf-orange);
        outline-offset: 2px;
    }

    .manager-form .mf-btn:disabled {
        opacity: .7;
        cursor: wait;
        transform: none;
    }

    @media (max-width: 575.98px) {
        .manager-form .mf-segment { grid-template-columns: minmax(0, 1fr); }
    }

    @media (max-width: 767.98px) {
        .manager-form .mf-card-header {
            padding: 18px;
        }

        .manager-form .mf-card-body {
            padding: 20px 18px;
        }

        .manager-form .mf-footer {
            align-items: stretch;
            flex-direction: column;
        }

        .manager-form .mf-footer-actions {
            display: flex;
            width: 100%;
        }

        .manager-form .mf-footer-actions .mf-btn {
            flex: 1;
            padding-left: 12px;
            padding-right: 12px;
        }
    }
</style>
@endpush

<div class="manager-form">

    @if(session('error'))
        <div class="mf-error-summary" role="alert">
            <div class="d-flex align-items-center gap-2 fw-bold">
                <i class="bi bi-exclamation-triangle-fill"></i>
                {{ session('error') }}
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="mf-error-summary" role="alert">
            <div class="d-flex align-items-center gap-2 fw-bold mb-2">
                <i class="bi bi-exclamation-triangle-fill"></i>
                Please review the form
            </div>
            <p class="small mb-2">
                Some information needs your attention before you can save.
            </p>
            <ul class="small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Account information --}}
    <section class="mf-card">
        <div class="mf-card-header">
            <div class="mf-icon">
                <i class="bi bi-person-vcard"></i>
            </div>
            <div>
                <h2 class="mf-card-title">Account information</h2>
                <p class="mf-card-description">
                    The manager's personal details. Sign-in credentials are generated and emailed automatically.
                </p>
            </div>
        </div>

        <div class="mf-card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="name" class="form-label">
                        Full name <span class="mf-required">*</span>
                    </label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $manager->user?->name) }}"
                        maxlength="255"
                        autocomplete="name"
                        placeholder="Enter the manager's full name"
                        required
                    >
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">
                        Email address <span class="mf-required">*</span>
                    </label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', $manager->user?->email) }}"
                        maxlength="255"
                        autocomplete="email"
                        placeholder="manager@example.com"
                        required
                    >
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone number</label>
                    <input
                        id="phone"
                        name="phone"
                        data-intl-phone
                        type="tel"
                        inputmode="tel"
                        class="form-control @error('phone') is-invalid @enderror"
                        value="{{ old('phone', $manager->phone) }}"
                        maxlength="30"
                        autocomplete="tel"
                        placeholder="+250 7XX XXX XXX"
                    >
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            @if($editing)
                <div class="mf-callout">
                    <i class="bi bi-person-check"></i>
                    <div>
                        <strong>Linked login account.</strong><br>
                        Changing the name or email here also updates the manager's login.
                        To issue a new password, use "Resend credentials" on the managers list.
                    </div>
                </div>
            @else
                <div class="mf-callout">
                    <i class="bi bi-envelope-check"></i>
                    <div>
                        <strong>No password to set.</strong><br>
                        A secure password is generated and emailed to
                        <span class="js-email-preview">the address above</span>
                        as soon as you save. The login is linked to this manager profile.
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- Location --}}
    <section class="mf-card">
        <div class="mf-card-header">
            <div class="mf-icon navy">
                <i class="bi bi-geo-alt"></i>
            </div>
            <div>
                <h2 class="mf-card-title">Location</h2>
                <p class="mf-card-description">
                    For Rwanda, pick a province and its districts load automatically.
                    Choosing a district first fills in its province.
                </p>
            </div>
        </div>

        <div class="mf-card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="country" class="form-label">
                        Country <span class="mf-required">*</span>
                    </label>
                    <input
                        id="country"
                        name="country"
                        type="text"
                        list="country-list"
                        class="form-control @error('country') is-invalid @enderror"
                        value="{{ $currentCountry }}"
                        maxlength="100"
                        autocomplete="country-name"
                        required
                    >
                    <datalist id="country-list">
                        @foreach(['Rwanda', 'Uganda', 'Kenya', 'Tanzania', 'Burundi', 'Democratic Republic of the Congo', 'United States', 'United Kingdom', 'Belgium', 'France', 'Canada'] as $c)
                            <option value="{{ $c }}"></option>
                        @endforeach
                    </datalist>
                    @error('country')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="province-select" class="form-label">
                        Province / City
                        <span class="mf-badge js-rw-badge d-none">
                            <i class="bi bi-geo-alt-fill"></i> Rwanda
                        </span>
                    </label>

                    <select id="province-select" name="province"
                            class="form-select @error('province') is-invalid @enderror">
                        <option value="">Select province</option>
                        @foreach($provinces as $provinceName => $districtList)
                            <option value="{{ $provinceName }}" @selected($currentProvince === $provinceName)>
                                {{ $provinceName }}
                            </option>
                        @endforeach
                    </select>

                    <input id="province-text" name="province" type="text" disabled
                           class="form-control d-none @error('province') is-invalid @enderror"
                           value="{{ $currentProvince }}" maxlength="100"
                           placeholder="State / region / city">

                    @error('province')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="district-select" class="form-label">District</label>

                    <select id="district-select" name="district"
                            class="form-select @error('district') is-invalid @enderror">
                        <option value="">Select district</option>
                    </select>

                    <input id="district-text" name="district" type="text" disabled
                           class="form-control d-none @error('district') is-invalid @enderror"
                           value="{{ $currentDistrict }}" maxlength="100"
                           placeholder="District / county">

                    @error('district')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="address" class="form-label">Physical address</label>
                    <input
                        id="address"
                        name="address"
                        type="text"
                        class="form-control @error('address') is-invalid @enderror"
                        value="{{ old('address', $manager->address) }}"
                        maxlength="255"
                        autocomplete="street-address"
                        placeholder="Sector, cell, street or other details"
                    >
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </section>

    {{-- Employment details --}}
    <section class="mf-card">
        <div class="mf-card-header">
            <div class="mf-icon navy">
                <i class="bi bi-briefcase"></i>
            </div>
            <div>
                <h2 class="mf-card-title">Employment details</h2>
                <p class="mf-card-description">
                    Define the manager's role within your rental management team.
                </p>
            </div>
        </div>

        <div class="mf-card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="job_title" class="form-label">Job title</label>
                    <input
                        id="job_title"
                        name="job_title"
                        type="text"
                        class="form-control @error('job_title') is-invalid @enderror"
                        value="{{ old('job_title', $manager->job_title) }}"
                        maxlength="255"
                        placeholder="e.g. Senior Property Manager"
                    >
                    @error('job_title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="start_date" class="form-label">Employment start date</label>
                    <input
                        id="start_date"
                        name="start_date"
                        type="date"
                        class="form-control @error('start_date') is-invalid @enderror"
                        value="{{ old('start_date', $manager->start_date?->format('Y-m-d')) }}"
                    >
                    @error('start_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <span class="form-label d-block">
                        Employment type <span class="mf-required">*</span>
                    </span>
                    <div class="mf-segment" role="radiogroup" aria-label="Employment type">
                        @foreach([
                            'full_time' => 'Full-time',
                            'part_time' => 'Part-time',
                            'contract' => 'Contract',
                        ] as $value => $label)
                            <label>
                                <input type="radio" name="employment_type" value="{{ $value }}"
                                       @checked($currentEmployment === $value)>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('employment_type')
                        <div class="text-danger mt-2" style="font-size:12px;">{{ $message }}</div>
                    @enderror
                </div>

                @unless($registrationOnly ?? false)
                <div class="col-md-6">
                    <label for="status" class="form-label">
                        Account status <span class="mf-required">*</span>
                    </label>
                    <select
                        id="status"
                        name="status"
                        class="form-select @error('status') is-invalid @enderror"
                        required
                    >
                        <option value="active" @selected(old('status', $manager->status ?: 'active') === 'active')>
                            Active
                        </option>
                        <option value="inactive" @selected(old('status', $manager->status) === 'inactive')>
                            Inactive
                        </option>
                    </select>
                    <div class="mf-status-hint">
                        <i class="bi bi-info-circle"></i>
                        <span>
                            Set inactive when the manager should no longer be considered active.
                            Enforce account access restrictions in your authorization logic.
                        </span>
                    </div>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @endunless
            </div>
        </div>
    </section>

    {{-- Notes --}}
    <section class="mf-card">
        <div class="mf-card-header">
            <div class="mf-icon navy">
                <i class="bi bi-journal-text"></i>
            </div>
            <div>
                <h2 class="mf-card-title">Additional notes</h2>
                <p class="mf-card-description">
                    Record relevant internal information for administrators.
                </p>
            </div>
        </div>

        <div class="mf-card-body">
            <label for="notes" class="form-label">Internal notes</label>
            <textarea
                id="notes"
                name="notes"
                rows="4"
                class="form-control @error('notes') is-invalid @enderror"
                placeholder="Add employment details or other administrative notes..."
            >{{ old('notes', $manager->notes) }}</textarea>
            @error('notes')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </section>

    {{-- Form actions --}}
    <div class="mf-footer">
        <div class="mf-security-note">
            <i class="bi bi-shield-check"></i>
            <span>
                {{ $editing ? 'Review all details before saving your changes.' : 'Saving creates the account and emails the credentials right away.' }}
            </span>
        </div>

        <div class="mf-footer-actions d-flex flex-wrap gap-2">
            <a href="{{ $cancelUrl ?? route('admin.managers.index') }}" class="mf-btn mf-btn-secondary">
                <i class="bi bi-x-lg"></i>
                Cancel
            </a>

            <button type="submit" class="mf-btn mf-btn-primary" id="manager-submit">
                <i class="bi bi-check2-circle"></i>
                <span class="js-submit-label">{{ $editing ? 'Save changes' : 'Create manager account' }}</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var provinces = @json($provinces);
        var initialDistrict = @json($currentDistrict);
        var editing = @json($editing);

        var $ = function (id) { return document.getElementById(id); };
        var submitBtn = $('manager-submit');
        var form = submitBtn.closest('form');
        var country = $('country');
        var provSel = $('province-select'), provTxt = $('province-text');
        var distSel = $('district-select'), distTxt = $('district-text');
        var badge = document.querySelector('.js-rw-badge');

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
                buildDistricts(provSel.value, distSel.value || initialDistrict);
            }
        }

        provSel.addEventListener('change', function () {
            buildDistricts(provSel.value, '');
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

        // Live preview of where the credentials will be sent.
        var emailInput = $('email');
        var preview = document.querySelector('.js-email-preview');

        if (preview) {
            emailInput.addEventListener('input', function () {
                preview.textContent = emailInput.value.trim() || 'the address above';
            });
            if (emailInput.value.trim()) { preview.textContent = emailInput.value.trim(); }
        }

        // Double-submit guard: saving sends an email.
        form.addEventListener('submit', function () {
            if (!form.checkValidity()) { return; }
            submitBtn.disabled = true;
            submitBtn.querySelector('.js-submit-label').textContent =
                editing ? 'Saving…' : 'Creating & emailing…';
        });

        syncCountry();
    })();
</script>
@endpush