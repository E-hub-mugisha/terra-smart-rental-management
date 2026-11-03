@php
    $isEdit = isset($tenant) && $tenant->exists;
    $provinces = config('rwanda.provinces', []);

    $currentCountry = old('country', $tenant->country ?? 'Rwanda') ?: 'Rwanda';
    $currentProvince = old('province', $tenant->province ?? null);
    $currentDistrict = old('district', $tenant->district ?? null);
@endphp

@push('styles')
<style>
    .tenant-form {
        --tf-orange: var(--terra-orange, #D05208);
        --tf-navy: var(--terra-navy, #19265d);
        --tf-muted: var(--terra-muted, #687386);
        --tf-border: var(--terra-border, #e5e9f0);
    }

    .tenant-form .tf-card {
        background: #fff;
        border: 1px solid var(--tf-border);
        border-radius: 18px;
        overflow: hidden;
        margin-bottom: 22px;
        box-shadow: 0 5px 22px rgba(25, 38, 93, .035);
    }

    .tenant-form .tf-card-header {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 20px 26px;
        border-bottom: 1px solid var(--tf-border);
        background: linear-gradient(110deg, #fff 60%, #fafbfe);
    }

    .tenant-form .tf-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 44px;
        width: 44px;
        height: 44px;
        border-radius: 13px;
        color: var(--tf-orange);
        background: #fff1e8;
        font-size: 20px;
    }

    .tenant-form .tf-icon.navy { color: var(--tf-navy); background: #edf0fa; }

    .tenant-form .tf-title {
        margin: 0 0 3px;
        color: var(--tf-navy);
        font-size: 16px;
        font-weight: 750;
    }

    .tenant-form .tf-desc {
        margin: 0;
        color: var(--tf-muted);
        font-size: 13px;
        line-height: 1.55;
    }

    .tenant-form .tf-body { padding: 26px; }

    .tenant-form .form-label {
        margin-bottom: 8px;
        color: #30394d;
        font-size: 13px;
        font-weight: 650;
    }

    .tenant-form .form-control,
    .tenant-form .form-select {
        min-height: 46px;
        padding: 10px 13px;
        border: 1px solid #dfe4ed;
        border-radius: 10px;
        color: #202b40;
        font-size: 14px;
        box-shadow: none;
        transition: border-color .2s, box-shadow .2s;
    }

    .tenant-form textarea.form-control { min-height: 96px; resize: vertical; }

    .tenant-form .form-control:focus,
    .tenant-form .form-select:focus {
        border-color: var(--tf-orange);
        box-shadow: 0 0 0 3px rgba(208, 82, 8, .10);
    }

    .tenant-form .form-select:disabled {
        background-color: #f8f9fc;
        color: #a1a9b6;
        cursor: not-allowed;
    }

    .tenant-form .form-control::placeholder { color: #a0a8b7; }
    .tenant-form .tf-required { color: #dc3545; }

    .tenant-form .tf-callout {
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

    .tenant-form .tf-callout > i { margin-top: 2px; color: #16845b; font-size: 18px; }
    .tenant-form .tf-callout .js-email-preview { font-weight: 700; word-break: break-all; }

    .tenant-form .tf-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-left: 6px;
        padding: 1px 8px;
        border-radius: 20px;
        background: #fff1e8;
        color: var(--tf-orange);
        font-size: 10px;
        font-weight: 700;
    }

    .tenant-form .tf-segment {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .tenant-form .tf-segment label { position: relative; margin: 0; }

    .tenant-form .tf-segment input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .tenant-form .tf-segment span {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 46px;
        padding: 8px 10px;
        border: 1px solid #dfe4ed;
        border-radius: 10px;
        background: #fff;
        color: #475166;
        font-size: 13px;
        font-weight: 650;
        cursor: pointer;
        transition: all .2s;
    }

    .tenant-form .tf-segment label:hover span { border-color: #f0c9ae; }

    .tenant-form .tf-segment input:checked + span {
        border-color: var(--tf-orange);
        background: #fffaf6;
        color: var(--tf-orange);
        box-shadow: 0 0 0 3px rgba(208, 82, 8, .10);
    }

    .tenant-form .tf-segment input:focus-visible + span {
        outline: 2px solid var(--tf-orange);
        outline-offset: 2px;
    }

    .tenant-form .tf-error {
        margin-bottom: 22px;
        padding: 18px 22px;
        border: 1px solid #f3c7ca;
        border-radius: 14px;
        background: #fff7f7;
        color: #842029;
    }

    .tenant-form .tf-error ul { margin: 8px 0 0; padding-left: 22px; }

    .tenant-form .tf-footer {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        padding: 12px 0 8px;
    }

    .tenant-form .tf-note {
        display: flex;
        align-items: center;
        gap: 9px;
        color: var(--tf-muted);
        font-size: 12px;
    }

    .tenant-form .tf-note i { color: #198754; font-size: 17px; }

    .tenant-form .tf-btn {
        min-height: 44px;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        transition: transform .15s, background .15s;
    }

    .tenant-form .tf-btn-primary {
        background: var(--tf-orange);
        border: 1px solid var(--tf-orange);
        color: #fff;
    }

    .tenant-form .tf-btn-primary:hover {
        background: #b94707;
        border-color: #b94707;
        color: #fff;
        transform: translateY(-1px);
    }

    .tenant-form .tf-btn-primary:disabled { opacity: .7; cursor: wait; transform: none; }

    .tenant-form .tf-btn-secondary {
        background: #fff;
        border: 1px solid #dfe4ed;
        color: #475166;
    }

    .tenant-form .tf-btn-secondary:hover { background: #f7f8fb; color: var(--tf-navy); }

    @media (max-width: 767.98px) {
        .tenant-form .tf-card-header { padding: 18px; }
        .tenant-form .tf-body { padding: 20px 18px; }
        .tenant-form .tf-footer { flex-direction: column; align-items: stretch; }
        .tenant-form .tf-actions { display: flex; width: 100%; }
        .tenant-form .tf-actions .tf-btn { flex: 1; }
    }
</style>
@endpush

<div class="tenant-form">

    @if (session('error'))
        <div class="tf-error" role="alert">
            <div class="d-flex align-items-center gap-2 fw-bold">
                <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="tf-error" role="alert">
            <div class="d-flex align-items-center gap-2 fw-bold">
                <i class="bi bi-exclamation-triangle-fill"></i> Please correct the following errors
            </div>
            <ul class="small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Tenant information --}}
    <section class="tf-card">
        <div class="tf-card-header">
            <div class="tf-icon"><i class="bi bi-person-vcard"></i></div>
            <div>
                <h2 class="tf-title">Tenant information</h2>
                <p class="tf-desc">Personal and contact details. Login credentials are generated and emailed automatically.</p>
            </div>
        </div>

        <div class="tf-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="full_name" class="form-label">
                        Full name <span class="tf-required">*</span>
                    </label>
                    <input type="text" id="full_name" name="full_name"
                           class="form-control @error('full_name') is-invalid @enderror"
                           value="{{ old('full_name', $tenant->full_name ?? '') }}"
                           maxlength="150" autocomplete="name"
                           placeholder="Enter the tenant's full name" required>
                    @error('full_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">
                        Email address <span class="tf-required">*</span>
                    </label>
                    <input type="email" id="email" name="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $tenant->email ?? '') }}"
                           maxlength="255" autocomplete="email"
                           placeholder="tenant@example.com" required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="phone" class="form-label">
                        Phone number <span class="tf-required">*</span>
                    </label>
                    <input type="tel" id="phone" name="phone" inputmode="tel"
                           class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone', $tenant->phone ?? '') }}"
                           maxlength="30" autocomplete="tel"
                           placeholder="+250 7XX XXX XXX" required>
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="alternative_phone" class="form-label">Alternative phone</label>
                    <input type="tel" id="alternative_phone" name="alternative_phone" inputmode="tel"
                           class="form-control @error('alternative_phone') is-invalid @enderror"
                           value="{{ old('alternative_phone', $tenant->alternative_phone ?? '') }}"
                           maxlength="30" placeholder="Optional">
                    @error('alternative_phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            @if ($isEdit)
                <div class="tf-callout">
                    <i class="bi bi-person-check"></i>
                    <div>
                        <strong>Linked login account.</strong><br>
                        Changing the name or email here also updates the tenant's login.
                        To issue a new password, use "Resend credentials".
                    </div>
                </div>
            @else
                <div class="tf-callout">
                    <i class="bi bi-envelope-check"></i>
                    <div>
                        <strong>A login account is created automatically.</strong><br>
                        A secure temporary password is emailed to
                        <span class="js-email-preview">the address above</span>
                        as soon as you save, and the login is linked to this tenant.
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- Location --}}
    <section class="tf-card">
        <div class="tf-card-header">
            <div class="tf-icon navy"><i class="bi bi-geo-alt"></i></div>
            <div>
                <h2 class="tf-title">Location</h2>
                <p class="tf-desc">
                    For Rwanda, pick a province and its districts load automatically.
                    Choosing a district first fills in its province.
                </p>
            </div>
        </div>

        <div class="tf-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="country" class="form-label">
                        Country <span class="tf-required">*</span>
                    </label>
                    <input type="text" id="country" name="country" list="country-list"
                           class="form-control @error('country') is-invalid @enderror"
                           value="{{ $currentCountry }}" maxlength="100"
                           autocomplete="country-name" required>
                    <datalist id="country-list">
                        @foreach (['Rwanda', 'Uganda', 'Kenya', 'Tanzania', 'Burundi', 'Democratic Republic of the Congo', 'United States', 'United Kingdom', 'Belgium', 'France', 'Canada'] as $c)
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
                        <span class="tf-badge js-rw-badge d-none">
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

                    <input type="text" id="province-text" name="province" disabled
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

                    <input type="text" id="district-text" name="district" disabled
                           class="form-control d-none @error('district') is-invalid @enderror"
                           value="{{ $currentDistrict }}" maxlength="100"
                           placeholder="District / county">

                    @error('district')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="address" class="form-label">Address details</label>
                    <textarea id="address" name="address" rows="2"
                              class="form-control @error('address') is-invalid @enderror"
                              autocomplete="street-address"
                              placeholder="Sector, cell, street or other details">{{ old('address', $tenant->address ?? '') }}</textarea>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </section>

    {{-- Emergency contact --}}
    <section class="tf-card">
        <div class="tf-card-header">
            <div class="tf-icon navy"><i class="bi bi-telephone-plus"></i></div>
            <div>
                <h2 class="tf-title">Emergency contact</h2>
                <p class="tf-desc">Someone we can reach if the tenant is unavailable.</p>
            </div>
        </div>

        <div class="tf-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="emergency_contact_name" class="form-label">Contact name</label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name"
                           class="form-control @error('emergency_contact_name') is-invalid @enderror"
                           value="{{ old('emergency_contact_name', $tenant->emergency_contact_name ?? '') }}"
                           maxlength="150" placeholder="Full name">
                    @error('emergency_contact_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="emergency_contact_phone" class="form-label">Contact phone</label>
                    <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" inputmode="tel"
                           class="form-control @error('emergency_contact_phone') is-invalid @enderror"
                           value="{{ old('emergency_contact_phone', $tenant->emergency_contact_phone ?? '') }}"
                           maxlength="30" placeholder="+250 7XX XXX XXX">
                    @error('emergency_contact_phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </section>

    {{-- Status and notes --}}
    <section class="tf-card">
        <div class="tf-card-header">
            <div class="tf-icon navy"><i class="bi bi-journal-text"></i></div>
            <div>
                <h2 class="tf-title">Status and notes</h2>
                <p class="tf-desc">Account status and internal information for administrators.</p>
            </div>
        </div>

        <div class="tf-body">
            <div class="row g-4">
                <div class="col-12">
                    <span class="form-label d-block">
                        Status <span class="tf-required">*</span>
                    </span>
                    <div class="tf-segment" role="radiogroup" aria-label="Tenant status">
                        @php $currentStatus = old('status', $tenant->status ?? 'active'); @endphp
                        <label>
                            <input type="radio" name="status" value="active" @checked($currentStatus === 'active')>
                            <span><i class="bi bi-check-circle"></i> Active</span>
                        </label>
                        <label>
                            <input type="radio" name="status" value="inactive" @checked($currentStatus === 'inactive')>
                            <span><i class="bi bi-slash-circle"></i> Inactive</span>
                        </label>
                    </div>
                    @error('status')
                        <div class="text-danger mt-2" style="font-size:12px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="notes" class="form-label">Internal notes</label>
                    <textarea id="notes" name="notes" rows="3"
                              class="form-control @error('notes') is-invalid @enderror"
                              placeholder="Add any administrative notes about this tenant...">{{ old('notes', $tenant->notes ?? '') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </section>

    {{-- Actions --}}
    <div class="tf-footer">
        <div class="tf-note">
            <i class="bi bi-shield-check"></i>
            <span>
                {{ $isEdit ? 'Review all details before saving your changes.' : 'Saving creates the account and emails the credentials right away.' }}
            </span>
        </div>

        <div class="tf-actions d-flex flex-wrap gap-2">
            <a href="{{ route('admin.tenants.index') }}" class="tf-btn tf-btn-secondary">
                <i class="bi bi-x-lg"></i> Cancel
            </a>

            <button type="submit" class="tf-btn tf-btn-primary" id="tenant-submit">
                <i class="bi bi-check-circle"></i>
                <span class="js-submit-label">{{ $isEdit ? 'Save Changes' : 'Create Tenant & Account' }}</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var provinces = @json($provinces);
        var initialDistrict = @json($currentDistrict);
        var isEdit = @json($isEdit);

        var $ = function (id) { return document.getElementById(id); };
        var submitBtn = $('tenant-submit');
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
                isEdit ? 'Saving…' : 'Creating & emailing…';
        });

        syncCountry();
    })();
</script>
@endpush