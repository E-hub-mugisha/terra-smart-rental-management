@extends('layouts.app')

@section('title', 'Create Tenant Application')

@section('content')
<div class="container-fluid py-4">

    {{-- Page heading --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('admin.tenant-applications.index') }}"
                    class="text-decoration-none text-muted">
                    Applications
                </a>
                <span class="text-muted">/</span>
                <span class="text-muted">Create</span>
            </div>

            <h3 class="fw-bold mb-1">Create Tenant Application</h3>
            <p class="text-muted mb-0">
                Register a rental application on behalf of an existing tenant.
            </p>
        </div>

        <a href="{{ route('admin.tenant-applications.index') }}"
            class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>
            Back to Applications
        </a>
    </div>

    {{-- Validation errors --}}
    @if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm mb-4">
        <div class="fw-semibold mb-2">
            Please correct the following errors:
        </div>

        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.tenant-applications.store') }}"
        method="POST">
        @csrf

        <div class="row g-4">

            {{-- Main form --}}
            <div class="col-lg-8">

                {{-- Tenant selection --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="application-section-icon">
                                <i class="bi bi-person"></i>
                            </div>

                            <div>
                                <h6 class="fw-bold mb-0">Tenant Information</h6>
                                <small class="text-muted">
                                    Select the tenant applying for the unit.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="tenant_id" class="form-label fw-semibold">
                                Select Tenant <span class="text-danger">*</span>
                            </label>

                            <select name="tenant_id"
                                id="tenant_id"
                                class="form-select @error('tenant_id') is-invalid @enderror"
                                required>
                                <option value="">Choose a tenant</option>

                                @foreach ($tenants as $tenant)
                                <option value="{{ $tenant->id }}"
                                    data-name="{{ $tenant->full_name }}"
                                    data-phone="{{ $tenant->phone }}"
                                    data-email="{{ $tenant->email }}"
                                    @selected(old('tenant_id')==$tenant->id)>
                                    {{ $tenant->full_name }}
                                    @if ($tenant->tenant_number)
                                    — {{ $tenant->tenant_number }}
                                    @endif
                                </option>
                                @endforeach
                            </select>

                            @error('tenant_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            @if ($tenants->isEmpty())
                            <div class="form-text text-danger">
                                No tenants are registered yet.
                                Register a tenant before creating an application.
                            </div>
                            @endif
                        </div>

                        <div class="tenant-information-panel d-none" id="tenantInformation">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Tenant</small>
                                    <span class="fw-semibold" id="tenantName">—</span>
                                </div>

                                <div class="col-md-4">
                                    <small class="text-muted d-block">Phone</small>
                                    <span class="fw-semibold" id="tenantPhone">—</span>
                                </div>

                                <div class="col-md-4">
                                    <small class="text-muted d-block">Email</small>
                                    <span class="fw-semibold" id="tenantEmail">—</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Unit selection --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="application-section-icon">
                                <i class="bi bi-house"></i>
                            </div>

                            <div>
                                <h6 class="fw-bold mb-0">Rental Unit</h6>
                                <small class="text-muted">
                                    Select an available unit for the application.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="unit_id" class="form-label fw-semibold">
                                Select Unit <span class="text-danger">*</span>
                            </label>

                            <select name="unit_id"
                                id="unit_id"
                                class="form-select @error('unit_id') is-invalid @enderror"
                                required>
                                <option value="">Choose an available unit</option>

                                @foreach ($units as $unit)
                                <option value="{{ $unit->id }}"
                                    data-rent="{{ $unit->rent_amount }}"
                                    data-currency="{{ $unit->currency ?: 'RWF' }}"
                                    data-building="{{ $unit->building?->name ?? 'Building' }}"
                                    data-floor="{{ $unit->floor?->name ?? 'N/A' }}"
                                    @selected(old('unit_id')==$unit->id)>
                                    {{ $unit->unit_number }}
                                    — {{ $unit->building?->name ?? 'Building' }}
                                    — {{ number_format((float) $unit->rent_amount) }}
                                    {{ $unit->currency ?: 'RWF' }}
                                </option>
                                @endforeach
                            </select>

                            @error('unit_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            @if ($units->isEmpty())
                            <div class="form-text text-danger">
                                There are currently no available units.
                            </div>
                            @endif
                        </div>

                        <div class="unit-information-panel d-none" id="unitInformation">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Unit</small>
                                    <span class="fw-semibold" id="unitNumber">—</span>
                                </div>

                                <div class="col-md-4">
                                    <small class="text-muted d-block">Building</small>
                                    <span class="fw-semibold" id="unitBuilding">—</span>
                                </div>

                                <div class="col-md-4">
                                    <small class="text-muted d-block">Floor</small>
                                    <span class="fw-semibold" id="unitFloor">—</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Application details --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="application-section-icon">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>

                            <div>
                                <h6 class="fw-bold mb-0">Application Details</h6>
                                <small class="text-muted">
                                    Enter the tenant's rental requirements.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label for="application_date" class="form-label fw-semibold">
                                    Application Date <span class="text-danger">*</span>
                                </label>

                                <input type="date"
                                    name="application_date"
                                    id="application_date"
                                    value="{{ old('application_date', now()->toDateString()) }}"
                                    class="form-control @error('application_date') is-invalid @enderror"
                                    required>

                                @error('application_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="desired_move_in_date" class="form-label fw-semibold">
                                    Desired Move-in Date <span class="text-danger">*</span>
                                </label>

                                <input type="date"
                                    name="desired_move_in_date"
                                    id="desired_move_in_date"
                                    value="{{ old('desired_move_in_date') }}"
                                    class="form-control @error('desired_move_in_date') is-invalid @enderror"
                                    required>

                                @error('desired_move_in_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="number_of_occupants" class="form-label fw-semibold">
                                    Number of Occupants <span class="text-danger">*</span>
                                </label>

                                <input type="number"
                                    name="number_of_occupants"
                                    id="number_of_occupants"
                                    min="1"
                                    max="50"
                                    value="{{ old('number_of_occupants', 1) }}"
                                    class="form-control @error('number_of_occupants') is-invalid @enderror"
                                    required>

                                @error('number_of_occupants')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="employment_status" class="form-label fw-semibold">
                                    Employment Status
                                </label>

                                <select name="employment_status"
                                    id="employment_status"
                                    class="form-select @error('employment_status') is-invalid @enderror">
                                    <option value="">Select employment status</option>
                                    <option value="employed" @selected(old('employment_status')=='employed' )>
                                        Employed
                                    </option>
                                    <option value="self-employed" @selected(old('employment_status')=='self-employed' )>
                                        Self-employed
                                    </option>
                                    <option value="unemployed" @selected(old('employment_status')=='unemployed' )>
                                        Unemployed
                                    </option>
                                    <option value="student" @selected(old('employment_status')=='student' )>
                                        Student
                                    </option>
                                    <option value="other" @selected(old('employment_status')=='other' )>
                                        Other
                                    </option>
                                </select>

                                @error('employment_status')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-8">
                                <label for="offered_rent" class="form-label fw-semibold">
                                    Offered Monthly Rent <span class="text-danger">*</span>
                                </label>

                                <input type="number"
                                    name="offered_rent"
                                    id="offered_rent"
                                    min="0.01"
                                    step="0.01"
                                    value="{{ old('offered_rent') }}"
                                    class="form-control @error('offered_rent') is-invalid @enderror"
                                    placeholder="Enter offered rent"
                                    required>

                                @error('offered_rent')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="currency" class="form-label fw-semibold">
                                    Currency <span class="text-danger">*</span>
                                </label>

                                <select name="currency"
                                    id="currency"
                                    class="form-select @error('currency') is-invalid @enderror"
                                    required>
                                    <option value="RWF" @selected(old('currency', 'RWF' )=='RWF' )>
                                        RWF
                                    </option>
                                    <option value="USD" @selected(old('currency')=='USD' )>
                                        USD
                                    </option>
                                </select>

                                @error('currency')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3">Application Summary</h6>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Application number</span>
                            <span class="fw-semibold">Auto-generated</span>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Application status</span>
                            <span class="badge bg-warning-subtle text-warning-emphasis">
                                Pending
                            </span>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Created by</span>
                            <span class="fw-semibold">
                                {{ auth()->user()->name }}
                            </span>
                        </div>

                        <hr>

                        <div class="small text-muted">
                            The application will be saved as pending and will
                            appear in the normal application review process.
                            No lease will be created at this stage.
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3">Before submitting</h6>

                        <ul class="small text-muted ps-3 mb-0">
                            <li class="mb-2">Confirm the correct tenant is selected.</li>
                            <li class="mb-2">Confirm the unit is available.</li>
                            <li class="mb-2">Verify the offered rent and currency.</li>
                            <li>Confirm the tenant's desired move-in date.</li>
                        </ul>
                    </div>
                </div>

            </div>

        </div>

        {{-- Actions --}}
        <div class="d-flex justify-content-end gap-2 mt-2 mb-4">
            <a href="{{ route('admin.tenant-applications.index') }}"
                class="btn btn-light border px-4">
                Cancel
            </a>

            <button type="submit"
                class="btn btn-primary px-4"
                {{ $tenants->isEmpty() || $units->isEmpty() ? 'disabled' : '' }}>
                <i class="bi bi-check-circle me-1"></i>
                Create Application
            </button>
        </div>

    </form>
</div>
@endsection

@push('styles')
<style>
    .application-section-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(208, 82, 8, .10);
        color: #D05208;
        font-size: 18px;
    }

    .card {
        border-radius: 12px;
    }

    .card-header {
        border-radius: 12px 12px 0 0 !important;
    }

    .form-label {
        font-size: .875rem;
    }

    .form-control,
    .form-select {
        min-height: 44px;
        border-color: #dee2e6;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #D05208;
        box-shadow: 0 0 0 .2rem rgba(208, 82, 8, .12);
    }

    .btn-primary {
        background-color: #D05208;
        border-color: #D05208;
    }

    .btn-primary:hover {
        background-color: #b94708;
        border-color: #b94708;
    }

    .tenant-information-panel,
    .unit-information-panel {
        background: #f8f9fa;
        border: 1px solid #edf0f2;
        border-radius: 10px;
        padding: 15px;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const tenantSelect = document.getElementById('tenant_id');
    const tenantInformation = document.getElementById('tenantInformation');

    const unitSelect = document.getElementById('unit_id');
    const unitInformation = document.getElementById('unitInformation');

    const offeredRent = document.getElementById('offered_rent');
    const currency = document.getElementById('currency');

    // Display selected tenant information
    function updateTenantInformation() {
        const option = tenantSelect.selectedOptions[0];

        if (!option || !option.value) {
            tenantInformation.classList.add('d-none');
            return;
        }

        document.getElementById('tenantName').textContent =
            option.dataset.name || '—';

        document.getElementById('tenantPhone').textContent =
            option.dataset.phone || '—';

        document.getElementById('tenantEmail').textContent =
            option.dataset.email || '—';

        tenantInformation.classList.remove('d-none');
    }

    // Display selected unit information
    function updateUnitInformation(updateRent = true) {
        const option = unitSelect.selectedOptions[0];

        if (!option || !option.value) {
            unitInformation.classList.add('d-none');
            return;
        }

        document.getElementById('unitNumber').textContent =
            option.textContent.split('—')[0].trim();

        document.getElementById('unitBuilding').textContent =
            option.dataset.building || '—';

        document.getElementById('unitFloor').textContent =
            option.dataset.floor || '—';

        // Populate rent and currency when the administrator
        // selects a different unit.
        if (updateRent) {
            offeredRent.value = option.dataset.rent || '';

            currency.value =
                option.dataset.currency === 'USD' ? 'USD' : 'RWF';
        }

        unitInformation.classList.remove('d-none');
    }

    // Listen for changes
    tenantSelect.addEventListener('change', updateTenantInformation);

    unitSelect.addEventListener('change', function () {
        updateUnitInformation(true);
    });

    // Initialize selected values
    updateTenantInformation();
    updateUnitInformation(false);

});
</script>
@endpush