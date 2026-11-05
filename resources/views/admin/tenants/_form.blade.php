{{--
    Shared tenant form (create + edit).
    Expects an optional $tenant model. When $tenant exists the form is in edit mode.
    Note: there is no password field. Credentials are generated and emailed automatically.
--}}
@php
    $tenant = $tenant ?? null;
    $isEdit = $tenant && $tenant->exists;

    $val = fn (string $field, $default = null) => old($field, data_get($tenant, $field, $default));

    $provinces = [
        'Kigali City' => ['Gasabo', 'Kicukiro', 'Nyarugenge'],
        'Northern Province' => ['Burera', 'Gakenke', 'Gicumbi', 'Musanze', 'Rulindo'],
        'Southern Province' => ['Gisagara', 'Huye', 'Kamonyi', 'Muhanga', 'Nyamagabe', 'Nyanza', 'Nyaruguru', 'Ruhango'],
        'Eastern Province' => ['Bugesera', 'Gatsibo', 'Kayonza', 'Kirehe', 'Ngoma', 'Nyagatare', 'Rwamagana'],
        'Western Province' => ['Karongi', 'Ngororero', 'Nyabihu', 'Nyamasheke', 'Rubavu', 'Rusizi', 'Rutsiro'],
    ];

    $currentProvince = $val('province');
    $currentDistrict = $val('district');
    $currentStatus   = $val('status', 'active');

    $cancelUrl = $isEdit && \Illuminate\Support\Facades\Route::has('admin.tenants.show')
        ? route('admin.tenants.show', $tenant)
        : route('admin.tenants.index');
@endphp

@push('styles')
<style>
    .tenant-form-page {
        --tf-orange: var(--terra-orange, #D05208);
        --tf-orange-dark: #AD4307;
        --tf-orange-soft: #FFF0E7;
        --tf-navy: var(--terra-navy, #19265D);
        --tf-border: var(--terra-border, #E7EAF1);
        --tf-muted: var(--terra-muted, #778197);
        color: #263047;
        padding-bottom: 90px;
    }

    .tenant-form-page .tf-intro { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 18px; margin-bottom: 22px; }
    .tenant-form-page .tf-breadcrumb { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; color: var(--tf-muted); font-size: 11px; }
    .tenant-form-page .tf-breadcrumb a { color: var(--tf-muted); text-decoration: none; }
    .tenant-form-page .tf-breadcrumb a:hover { color: var(--tf-orange); }
    .tenant-form-page .tf-breadcrumb i { font-size: 9px; color: #AAB1BF; }
    .tenant-form-page .tf-title { margin: 0; color: var(--tf-navy); font-size: clamp(22px, 2vw, 28px); font-weight: 800; letter-spacing: -.7px; }
    .tenant-form-page .tf-description { max-width: 680px; margin: 7px 0 0; color: var(--tf-muted); font-size: 12px; line-height: 1.7; }

    .tenant-form-page .tf-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 40px;
        padding: 9px 15px; border: 1px solid transparent; border-radius: 10px;
        font-size: 12px; font-weight: 700; text-decoration: none; transition: .18s ease; white-space: nowrap;
    }
    .tenant-form-page .tf-btn-primary { background: var(--tf-orange); border-color: var(--tf-orange); color: #fff; }
    .tenant-form-page .tf-btn-primary:hover { background: var(--tf-orange-dark); border-color: var(--tf-orange-dark); color: #fff; transform: translateY(-1px); }
    .tenant-form-page .tf-btn-outline { background: #fff; border-color: var(--tf-border); color: var(--tf-navy); }
    .tenant-form-page .tf-btn-outline:hover { border-color: #EFC4A8; background: #FFF8F3; color: var(--tf-orange); }

    /* Cards */
    .tenant-form-page .tf-card { margin-bottom: 20px; border: 1px solid var(--tf-border); border-radius: 15px; background: #fff; box-shadow: 0 5px 24px rgba(25, 38, 93, .04); }
    .tenant-form-page .tf-card-header { display: flex; align-items: center; gap: 11px; padding: 16px 20px; border-bottom: 1px solid var(--tf-border); }
    .tenant-form-page .tf-card-icon { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; flex: 0 0 36px; border-radius: 10px; background: var(--tf-orange-soft); color: var(--tf-orange); font-size: 16px; }
    .tenant-form-page .tf-card-title { margin: 0; color: var(--tf-navy); font-size: 14px; font-weight: 800; }
    .tenant-form-page .tf-card-subtitle { margin-top: 3px; color: var(--tf-muted); font-size: 11px; line-height: 1.5; }
    .tenant-form-page .tf-card-body { padding: 20px; }

    /* Fields */
    .tenant-form-page .tf-label { display: flex; align-items: center; justify-content: space-between; margin-bottom: 7px; color: #4B556B; font-size: 11px; font-weight: 700; }
    .tenant-form-page .tf-label .req { color: #B42318; margin-left: 2px; }
    .tenant-form-page .tf-label .opt { color: #98A2B3; font-size: 10px; font-weight: 600; }
    .tenant-form-page .tf-input-wrap { position: relative; }
    .tenant-form-page .tf-input-wrap > i { position: absolute; top: 50%; left: 13px; transform: translateY(-50%); color: #8A94A7; font-size: 14px; pointer-events: none; }
    .tenant-form-page .tf-input-wrap .form-control, .tenant-form-page .tf-input-wrap .form-select { padding-left: 38px; }
    .tenant-form-page .form-control, .tenant-form-page .form-select {
        min-height: 43px; border-color: #E1E5ED; border-radius: 10px; color: #30394D; font-size: 12.5px; box-shadow: none;
    }
    .tenant-form-page textarea.form-control { min-height: 96px; padding-top: 11px; line-height: 1.6; }
    .tenant-form-page .form-control:focus, .tenant-form-page .form-select:focus { border-color: #E8A47E; box-shadow: 0 0 0 3px rgba(208, 82, 8, .09); }
    .tenant-form-page .form-control.is-invalid, .tenant-form-page .form-select.is-invalid { border-color: #E5A29C; background-image: none; }
    .tenant-form-page .form-control.is-invalid:focus { box-shadow: 0 0 0 3px rgba(180, 35, 24, .1); }
    .tenant-form-page .tf-error { margin-top: 6px; color: #B42318; font-size: 11px; font-weight: 600; }
    .tenant-form-page .tf-hint { margin-top: 6px; color: var(--tf-muted); font-size: 10.5px; }
    .tenant-form-page .tf-counter { float: right; color: #98A2B3; font-size: 10px; }

    /* Sidebar */
    .tenant-form-page .tf-info-box { display: flex; gap: 12px; padding: 14px; border: 1px solid #F6D8C5; border-radius: 12px; background: #FFF8F3; }
    .tenant-form-page .tf-info-box .ib-icon { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; flex: 0 0 36px; border-radius: 10px; background: #fff; color: var(--tf-orange); font-size: 16px; }
    .tenant-form-page .tf-info-box .ib-title { color: var(--tf-navy); font-size: 12px; font-weight: 800; }
    .tenant-form-page .tf-info-box .ib-text { margin-top: 4px; color: #6B5B4F; font-size: 11px; line-height: 1.65; }

    .tenant-form-page .tf-steps { margin: 16px 0 0; padding: 0; list-style: none; }
    .tenant-form-page .tf-steps li { position: relative; display: flex; gap: 12px; padding-bottom: 16px; }
    .tenant-form-page .tf-steps li:last-child { padding-bottom: 0; }
    .tenant-form-page .tf-steps li::before { content: ""; position: absolute; top: 28px; bottom: 0; left: 13px; width: 1px; background: var(--tf-border); }
    .tenant-form-page .tf-steps li:last-child::before { display: none; }
    .tenant-form-page .tf-step-num { display: flex; align-items: center; justify-content: center; width: 27px; height: 27px; flex: 0 0 27px; border-radius: 50%; background: #EDF0FB; color: var(--tf-navy); font-size: 11px; font-weight: 800; }
    .tenant-form-page .tf-step-title { color: var(--tf-navy); font-size: 12px; font-weight: 700; }
    .tenant-form-page .tf-step-text { margin-top: 2px; color: var(--tf-muted); font-size: 11px; line-height: 1.55; }

    .tenant-form-page .tf-status-options { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .tenant-form-page .tf-status-option input { position: absolute; opacity: 0; pointer-events: none; }
    .tenant-form-page .tf-status-option label {
        display: flex; align-items: center; gap: 9px; width: 100%; padding: 11px 12px; border: 1px solid #E1E5ED;
        border-radius: 10px; background: #fff; color: #4B556B; font-size: 12px; font-weight: 700; cursor: pointer; transition: .15s ease;
    }
    .tenant-form-page .tf-status-option label i { font-size: 15px; color: #98A2B3; }
    .tenant-form-page .tf-status-option input:checked + label { border-color: var(--tf-orange); background: #FFF8F3; color: var(--tf-orange-dark); box-shadow: 0 0 0 3px rgba(208, 82, 8, .08); }
    .tenant-form-page .tf-status-option input:checked + label i { color: var(--tf-orange); }
    .tenant-form-page .tf-status-option input:focus-visible + label { outline: 2px solid var(--tf-orange); outline-offset: 2px; }

    .tenant-form-page .tf-account-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 11px 0; border-bottom: 1px solid #EEF0F5; font-size: 11.5px; }
    .tenant-form-page .tf-account-row:last-child { border-bottom: 0; padding-bottom: 0; }
    .tenant-form-page .tf-account-row span:first-child { color: var(--tf-muted); font-weight: 600; }
    .tenant-form-page .tf-account-row span:last-child { color: var(--tf-navy); font-weight: 700; text-align: right; overflow-wrap: anywhere; }

    /* Sticky action bar */
    .tenant-form-page .tf-actionbar {
        position: sticky; bottom: 0; z-index: 20; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
        margin-top: 8px; padding: 14px 20px; border: 1px solid var(--tf-border); border-radius: 14px;
        background: rgba(255, 255, 255, .96); backdrop-filter: blur(8px); box-shadow: 0 -6px 24px rgba(25, 38, 93, .07);
    }
    .tenant-form-page .tf-actionbar .tf-note { color: var(--tf-muted); font-size: 11px; }

    @media (max-width: 767.98px) {
        .tenant-form-page .tf-intro > .tf-btn { width: 100%; }
        .tenant-form-page .tf-card-header, .tenant-form-page .tf-card-body { padding: 16px; }
        .tenant-form-page .tf-actionbar { padding: 12px 14px; }
        .tenant-form-page .tf-actionbar .tf-btn { flex: 1; }
        .tenant-form-page .tf-actionbar .tf-note { display: none; }
    }
</style>
@endpush

<div class="row g-4">

    {{-- ============ MAIN FORM ============ --}}
    <div class="col-12 col-xl-8">

        {{-- Personal details --}}
        <section class="tf-card">
            <div class="tf-card-header">
                <div class="tf-card-icon"><i class="bi bi-person-vcard"></i></div>
                <div>
                    <h2 class="tf-card-title">Personal details</h2>
                    <div class="tf-card-subtitle">The tenant's name and primary login email</div>
                </div>
            </div>

            <div class="tf-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="full_name" class="tf-label"><span>Full name<span class="req">*</span></span></label>
                        <div class="tf-input-wrap">
                            <i class="bi bi-person"></i>
                            <input type="text" id="full_name" name="full_name" maxlength="150" required autofocus
                                   class="form-control @error('full_name') is-invalid @enderror"
                                   value="{{ $val('full_name') }}" placeholder="e.g. Uwase Claudine">
                        </div>
                        @error('full_name') <div class="tf-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label for="email" class="tf-label"><span>Email address<span class="req">*</span></span></label>
                        <div class="tf-input-wrap">
                            <i class="bi bi-envelope"></i>
                            <input type="email" id="email" name="email" maxlength="255" autocomplete="off"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ $val('email') }}" placeholder="tenant@example.com">
                        </div>
                        @error('email')
                            <div class="tf-error">{{ $message }}</div>
                        @else
                            <div class="tf-hint">
                                @if($isEdit) Changing the email changes where login information is sent.
                                @else The login credentials are sent to this address. @endif
                            </div>
                        @enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- Contact --}}
        <section class="tf-card">
            <div class="tf-card-header">
                <div class="tf-card-icon"><i class="bi bi-telephone"></i></div>
                <div>
                    <h2 class="tf-card-title">Contact numbers</h2>
                    <div class="tf-card-subtitle">How the property team can reach the tenant</div>
                </div>
            </div>

            <div class="tf-card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="phone" class="tf-label"><span>Phone number<span class="req">*</span></span></label>
                        <div class="tf-input-wrap">
                            <i class="bi bi-telephone"></i>
                            <input type="tel" id="phone" name="phone" maxlength="30" inputmode="tel" required
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ $val('phone') }}" placeholder="+250 78X XXX XXX">
                        </div>
                        @error('phone') <div class="tf-error">{{ $message }}</div> @else <div class="tf-hint">Use the +250 format or a local number starting with 07.</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="alternative_phone" class="tf-label"><span>Alternative phone</span><span class="opt">Optional</span></label>
                        <div class="tf-input-wrap">
                            <i class="bi bi-telephone-plus"></i>
                            <input type="tel" id="alternative_phone" name="alternative_phone" maxlength="30" inputmode="tel"
                                   class="form-control @error('alternative_phone') is-invalid @enderror"
                                   value="{{ $val('alternative_phone') }}" placeholder="+250 7XX XXX XXX">
                        </div>
                        @error('alternative_phone') <div class="tf-error">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- Location --}}
        <section class="tf-card">
            <div class="tf-card-header">
                <div class="tf-card-icon"><i class="bi bi-geo-alt"></i></div>
                <div>
                    <h2 class="tf-card-title">Home address</h2>
                    <div class="tf-card-subtitle">Where the tenant lives or comes from</div>
                </div>
            </div>

            <div class="tf-card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label for="country" class="tf-label"><span>Country</span></label>
                        <div class="tf-input-wrap">
                            <i class="bi bi-globe"></i>
                            <input type="text" id="country" name="country" maxlength="100"
                                   class="form-control @error('country') is-invalid @enderror"
                                   value="{{ $val('country', 'Rwanda') }}">
                        </div>
                        @error('country') <div class="tf-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="province" class="tf-label"><span>Province</span></label>
                        <div class="tf-input-wrap">
                            <i class="bi bi-map"></i>
                            <select id="province" name="province" class="form-select @error('province') is-invalid @enderror">
                                <option value="">Select province</option>
                                @foreach($provinces as $provinceName => $districts)
                                    <option value="{{ $provinceName }}" @selected($currentProvince === $provinceName)>{{ $provinceName }}</option>
                                @endforeach
                                @if($currentProvince && !array_key_exists($currentProvince, $provinces))
                                    <option value="{{ $currentProvince }}" selected>{{ $currentProvince }}</option>
                                @endif
                            </select>
                        </div>
                        @error('province') <div class="tf-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="district" class="tf-label"><span>District</span></label>
                        <div class="tf-input-wrap">
                            <i class="bi bi-geo"></i>
                            <select id="district" name="district" class="form-select @error('district') is-invalid @enderror" data-current="{{ $currentDistrict }}">
                                <option value="">Select district</option>
                            </select>
                        </div>
                        @error('district') <div class="tf-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label for="address" class="tf-label"><span>Street address / details</span><span class="opt">Optional</span></label>
                        <div class="tf-input-wrap">
                            <i class="bi bi-signpost-2"></i>
                            <input type="text" id="address" name="address"
                                   class="form-control @error('address') is-invalid @enderror"
                                   value="{{ $val('address') }}" placeholder="e.g. KK 15 Rd, Gikondo, Kicukiro">
                        </div>
                        @error('address') <div class="tf-error">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- Emergency contact --}}
        <section class="tf-card">
            <div class="tf-card-header">
                <div class="tf-card-icon"><i class="bi bi-heart-pulse"></i></div>
                <div>
                    <h2 class="tf-card-title">Emergency contact</h2>
                    <div class="tf-card-subtitle">Someone to call if the tenant cannot be reached</div>
                </div>
            </div>

            <div class="tf-card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="emergency_contact_name" class="tf-label"><span>Contact name</span><span class="opt">Optional</span></label>
                        <div class="tf-input-wrap">
                            <i class="bi bi-person-heart"></i>
                            <input type="text" id="emergency_contact_name" name="emergency_contact_name" maxlength="150"
                                   class="form-control @error('emergency_contact_name') is-invalid @enderror"
                                   value="{{ $val('emergency_contact_name') }}" placeholder="Full name">
                        </div>
                        @error('emergency_contact_name') <div class="tf-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="emergency_contact_phone" class="tf-label"><span>Contact phone</span><span class="opt">Optional</span></label>
                        <div class="tf-input-wrap">
                            <i class="bi bi-telephone"></i>
                            <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" maxlength="30" inputmode="tel"
                                   class="form-control @error('emergency_contact_phone') is-invalid @enderror"
                                   value="{{ $val('emergency_contact_phone') }}" placeholder="+250 7XX XXX XXX">
                        </div>
                        @error('emergency_contact_phone') <div class="tf-error">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- Notes --}}
        <section class="tf-card">
            <div class="tf-card-header">
                <div class="tf-card-icon"><i class="bi bi-journal-text"></i></div>
                <div>
                    <h2 class="tf-card-title">Internal notes</h2>
                    <div class="tf-card-subtitle">Visible to administrators only</div>
                </div>
            </div>

            <div class="tf-card-body">
                <label for="notes" class="tf-label"><span>Notes</span><span class="opt" id="notesCounter">0 / 1000</span></label>
                <textarea id="notes" name="notes" rows="4" maxlength="1000"
                          class="form-control @error('notes') is-invalid @enderror"
                          placeholder="Anything the team should know about this tenant...">{{ $val('notes') }}</textarea>
                @error('notes') <div class="tf-error">{{ $message }}</div> @enderror
            </div>
        </section>
    </div>

    {{-- ============ SIDEBAR ============ --}}
    <div class="col-12 col-xl-4">

        {{-- Login account --}}
        <section class="tf-card">
            <div class="tf-card-header">
                <div class="tf-card-icon"><i class="bi bi-shield-lock"></i></div>
                <div>
                    <h2 class="tf-card-title">Login account</h2>
                    <div class="tf-card-subtitle">Handled automatically by Terra</div>
                </div>
            </div>

            <div class="tf-card-body">
                @if($isEdit)
                    <div class="tf-account-row">
                        <span>Account</span>
                        <span>{{ $tenant->user_id ? 'Linked (#' . $tenant->user_id . ')' : 'No login account' }}</span>
                    </div>
                    <div class="tf-account-row">
                        <span>Tenant number</span>
                        <span>{{ $tenant->tenant_number ?: 'Not assigned' }}</span>
                    </div>

                    <div class="tf-info-box mt-3">
                        <div class="ib-icon"><i class="bi bi-key"></i></div>
                        <div>
                            <div class="ib-title">Forgot their password?</div>
                            <div class="ib-text">Passwords are never edited here. Use <strong>Resend credentials</strong> on the tenant profile to email a new one.</div>
                        </div>
                    </div>
                @else
                    <div class="tf-info-box">
                        <div class="ib-icon"><i class="bi bi-envelope-check"></i></div>
                        <div>
                            <div class="ib-title">No password needed</div>
                            <div class="ib-text">A secure password is generated and emailed to the tenant when you save. They can change it after their first sign in.</div>
                        </div>
                    </div>

                    <ol class="tf-steps">
                        <li>
                            <span class="tf-step-num">1</span>
                            <div><div class="tf-step-title">Save the tenant</div><div class="tf-step-text">A tenant number and profile are created.</div></div>
                        </li>
                        <li>
                            <span class="tf-step-num">2</span>
                            <div><div class="tf-step-title">Account is created</div><div class="tf-step-text">A tenant login is linked to the profile.</div></div>
                        </li>
                        <li>
                            <span class="tf-step-num">3</span>
                            <div><div class="tf-step-title">Credentials emailed</div><div class="tf-step-text">The tenant receives their sign-in details.</div></div>
                        </li>
                    </ol>
                @endif
            </div>
        </section>

        {{-- Status --}}
        <section class="tf-card">
            <div class="tf-card-header">
                <div class="tf-card-icon"><i class="bi bi-toggle-on"></i></div>
                <div>
                    <h2 class="tf-card-title">Tenant status</h2>
                    <div class="tf-card-subtitle">Controls whether the tenant is active</div>
                </div>
            </div>

            <div class="tf-card-body">
                <div class="tf-status-options" role="radiogroup" aria-label="Tenant status">
                    <div class="tf-status-option">
                        <input type="radio" name="status" id="status_active" value="active" @checked($currentStatus === 'active')>
                        <label for="status_active"><i class="bi bi-check-circle-fill"></i> Active</label>
                    </div>
                    <div class="tf-status-option">
                        <input type="radio" name="status" id="status_inactive" value="inactive" @checked($currentStatus === 'inactive')>
                        <label for="status_inactive"><i class="bi bi-pause-circle-fill"></i> Inactive</label>
                    </div>
                </div>
                @error('status') <div class="tf-error">{{ $message }}</div> @enderror
            </div>
        </section>
    </div>
</div>

{{-- Sticky actions --}}
<div class="tf-actionbar">
    <span class="tf-note">
        <i class="bi bi-info-circle me-1"></i>
        Fields marked <span style="color:#B42318;">*</span> are required.
    </span>

    <div class="d-flex gap-2 ms-auto">
        <a href="{{ $cancelUrl }}" class="tf-btn tf-btn-outline">Cancel</a>
        <button type="submit" class="tf-btn tf-btn-primary">
            <i class="bi {{ $isEdit ? 'bi-save' : 'bi-person-plus' }}"></i>
            {{ $isEdit ? 'Save changes' : 'Create tenant & send credentials' }}
        </button>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    /* Province -> district */
    const map = @json($provinces);
    const province = document.getElementById('province');
    const district = document.getElementById('district');

    const fillDistricts = function () {
        const current = district.dataset.current || '';
        const list = map[province.value] || [];
        district.innerHTML = '<option value="">Select district</option>';
        list.forEach(function (name) {
            const opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            if (name === current) opt.selected = true;
            district.appendChild(opt);
        });
        // Keep a saved district that is not in the list (older data).
        if (current && list.indexOf(current) === -1) {
            const opt = document.createElement('option');
            opt.value = current;
            opt.textContent = current;
            opt.selected = true;
            district.appendChild(opt);
        }
    };

    province.addEventListener('change', function () {
        district.dataset.current = '';
        fillDistricts();
    });
    fillDistricts();

    /* Notes counter */
    const notes = document.getElementById('notes');
    const counter = document.getElementById('notesCounter');
    const updateCounter = function () { counter.textContent = notes.value.length + ' / 1000'; };
    notes.addEventListener('input', updateCounter);
    updateCounter();

    /* Light phone clean-up: keep digits, +, spaces */
    document.querySelectorAll('input[type="tel"]').forEach(function (input) {
        input.addEventListener('input', function () {
            input.value = input.value.replace(/[^\d+\s-]/g, '');
        });
    });
});
</script>
@endpush