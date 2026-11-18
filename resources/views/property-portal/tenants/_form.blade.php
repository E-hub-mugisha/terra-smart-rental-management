@php
    $editing = isset($tenant);
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0">Personal information</h6>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full name <span class="text-danger">*</span></label>
                        <input name="full_name" required maxlength="150"
                            class="form-control @error('full_name') is-invalid @enderror"
                            value="{{ old('full_name', $tenant->full_name ?? '') }}">
                        @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email address <span class="text-danger">*</span></label>
                        <input type="email" name="email" required maxlength="255"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $tenant->email ?? '') }}">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Phone number <span class="text-danger">*</span></label>
                        <input name="phone" type="tel" data-intl-phone required maxlength="30"
                            class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone', $tenant->phone ?? '') }}">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Alternative phone</label>
                        <input name="alternative_phone" type="tel" data-intl-phone maxlength="30"
                            class="form-control @error('alternative_phone') is-invalid @enderror"
                            value="{{ old('alternative_phone', $tenant->alternative_phone ?? '') }}">
                        @error('alternative_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" rows="3"
                            class="form-control @error('address') is-invalid @enderror">{{ old('address', $tenant->address ?? '') }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0">Emergency contact</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Contact name</label>
                        <input name="emergency_contact_name" maxlength="150"
                            class="form-control @error('emergency_contact_name') is-invalid @enderror"
                            value="{{ old('emergency_contact_name', $tenant->emergency_contact_name ?? '') }}">
                        @error('emergency_contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact phone</label>
                        <input name="emergency_contact_phone" type="tel" data-intl-phone maxlength="30"
                            class="form-control @error('emergency_contact_phone') is-invalid @enderror"
                            value="{{ old('emergency_contact_phone', $tenant->emergency_contact_phone ?? '') }}">
                        @error('emergency_contact_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0">Additional notes</h6>
            </div>
            <div class="card-body">
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="4"
                    class="form-control @error('notes') is-invalid @enderror"
                    placeholder="Optional internal notes about this tenant">{{ old('notes', $tenant->notes ?? '') }}</textarea>
                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0">Account settings</h6>
            </div>
            <div class="card-body">
                <label class="form-label">Tenant status <span class="text-danger">*</span></label>
                <select name="status" required
                    class="form-select @error('status') is-invalid @enderror">
                    <option value="active" @selected(old('status', $tenant->status ?? 'active') === 'active')>
                        Active
                    </option>
                    <option value="inactive" @selected(old('status', $tenant->status ?? '') === 'inactive')>
                        Inactive
                    </option>
                </select>
                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror

                @if(!$editing)
                    <div class="alert alert-info small mt-3 mb-0">
                        A tenant login account will be created using this email address.
                        A temporary password will be generated after saving.
                    </div>
                @else
                    <div class="alert alert-light border small mt-3 mb-0">
                        Updating the email also updates the linked user account, if one exists.
                        This form does not reset the tenant's password.
                    </div>
                @endif
            </div>
        </div>

        <div class="d-grid gap-2 mt-4">
            <button type="submit" class="btn btn-brand">
                <i class="bi bi-check2-circle me-1"></i>
                {{ $editing ? 'Save changes' : 'Create tenant' }}
            </button>
            <a href="{{ $editing ? route('property-manager.tenants.show', $tenant) : route('property-manager.tenants.index') }}"
                class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</div>

<style>
    .btn-brand {
        background: #D05208;
        border-color: #D05208;
        color: white;
    }
    .btn-brand:hover {
        background: #b94707;
        border-color: #b94707;
        color: white;
    }
</style>