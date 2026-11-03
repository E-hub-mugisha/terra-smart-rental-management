
@php
    $property = $property ?? null;

    $selectedManagers = array_map(
        'intval',
        (array) old('manager_ids', $assignedManagerIds ?? [])
    );

    $propertyTypes = [
        'residential' => 'Residential',
        'commercial' => 'Commercial',
        'mixed_use' => 'Mixed use',
        'industrial' => 'Industrial',
        'other' => 'Other',
    ];

    $propertyStatuses = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'under_maintenance' => 'Under maintenance',
    ];

    $inputClass = 'form-control';
    $selectClass = 'form-select';
@endphp

@if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm mb-4"
         role="alert" style="border-radius:12px;">
        <div class="fw-bold mb-2">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Please review the form
        </div>
        <p class="small mb-2">Some information needs to be corrected before saving.</p>
        <ul class="small mb-0 ps-4">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">

    {{-- Main form column --}}
    <div class="col-xl-8">

        {{-- Basic information --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
            <div class="card-header bg-white border-bottom p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:46px;height:46px;background:#fff0e8;color:#D05208;">
                        <i class="bi bi-building fs-4"></i>
                    </div>
                    <div>
                        <h2 class="h5 fw-bold mb-1" style="color:#19265d;">
                            Property information
                        </h2>
                        <p class="small text-secondary mb-0">
                            Provide the main details of the property.
                        </p>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row g-3">

                    <div class="col-12">
                        <label for="name" class="form-label fw-semibold">
                            Property name <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="{{ $inputClass }} @error('name') is-invalid @enderror"
                            value="{{ old('name', $property?->name) }}"
                            placeholder="e.g. Kigali Heights Apartments"
                            maxlength="255"
                            required
                        >
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="property_type" class="form-label fw-semibold">
                            Property type <span class="text-danger">*</span>
                        </label>
                        <select
                            name="property_type"
                            id="property_type"
                            class="{{ $selectClass }} @error('property_type') is-invalid @enderror"
                            required
                        >
                            <option value="">Select property type</option>
                            @foreach($propertyTypes as $value => $label)
                                <option value="{{ $value }}"
                                    @selected(old('property_type', $property?->property_type) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('property_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="property_owner_id" class="form-label fw-semibold">
                            Property owner <span class="text-danger">*</span>
                        </label>
                        <select
                            name="property_owner_id"
                            id="property_owner_id"
                            class="{{ $selectClass }} @error('property_owner_id') is-invalid @enderror"
                            required
                        >
                            <option value="">Select an owner</option>
                            @foreach($owners as $owner)
                                <option value="{{ $owner->id }}"
                                    @selected((string) old('property_owner_id', $property?->property_owner_id) === (string) $owner->id)>
                                    {{ $owner->name }}
                                    @if($owner->email)
                                        — {{ $owner->email }}
                                    @endif
                                </option>
                            @endforeach
                        </select>

                        @error('property_owner_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        @if($owners->isEmpty())
                            <div class="small text-danger mt-2">
                                <i class="bi bi-exclamation-circle me-1"></i>
                                No eligible owners found. Register an owner before creating a property.
                            </div>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label for="year_built" class="form-label fw-semibold">Year built</label>
                        <input
                            type="number"
                            name="year_built"
                            id="year_built"
                            class="{{ $inputClass }} @error('year_built') is-invalid @enderror"
                            min="1800"
                            max="{{ date('Y') + 5 }}"
                            value="{{ old('year_built', $property?->year_built) }}"
                            placeholder="e.g. 2022"
                        >
                        @error('year_built')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label fw-semibold">
                            Property status <span class="text-danger">*</span>
                        </label>
                        <select
                            name="status"
                            id="status"
                            class="{{ $selectClass }} @error('status') is-invalid @enderror"
                            required
                        >
                            @foreach($propertyStatuses as $value => $label)
                                <option value="{{ $value }}"
                                    @selected(old('status', $property?->status ?? 'active') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label fw-semibold">
                            Property description
                        </label>
                        <textarea
                            name="description"
                            id="description"
                            rows="4"
                            class="{{ $inputClass }} @error('description') is-invalid @enderror"
                            placeholder="Describe the property, facilities and main features..."
                        >{{ old('description', $property?->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- Location --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
            <div class="card-header bg-white border-bottom p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:46px;height:46px;background:#edf0fa;color:#19265d;">
                        <i class="bi bi-geo-alt fs-4"></i>
                    </div>
                    <div>
                        <h2 class="h5 fw-bold mb-1" style="color:#19265d;">
                            Property location
                        </h2>
                        <p class="small text-secondary mb-0">
                            Add the property's address and geographic coordinates.
                        </p>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="country" class="form-label fw-semibold">
                            Country <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            name="country"
                            id="country"
                            class="{{ $inputClass }} @error('country') is-invalid @enderror"
                            value="{{ old('country', $property?->country ?? 'Rwanda') }}"
                            required
                        >
                        @error('country')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @foreach([
                        'province' => 'Province',
                        'district' => 'District',
                        'sector' => 'Sector',
                        'cell' => 'Cell',
                        'village' => 'Village',
                    ] as $field => $label)
                        <div class="col-md-6">
                            <label for="{{ $field }}" class="form-label fw-semibold">
                                {{ $label }}
                            </label>
                            <input
                                type="text"
                                name="{{ $field }}"
                                id="{{ $field }}"
                                class="{{ $inputClass }} @error($field) is-invalid @enderror"
                                value="{{ old($field, $property?->$field) }}"
                                placeholder="Enter {{ strtolower($label) }}"
                            >
                            @error($field)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach

                    <div class="col-12">
                        <label for="address" class="form-label fw-semibold">
                            Street address / directions
                        </label>
                        <input
                            type="text"
                            name="address"
                            id="address"
                            class="{{ $inputClass }} @error('address') is-invalid @enderror"
                            value="{{ old('address', $property?->address) }}"
                            placeholder="Street, road, nearby landmark or directions"
                        >
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <div class="rounded-3 p-3 mt-1" style="background:#f7f8fb;">
                            <div class="fw-semibold small mb-1" style="color:#19265d;">
                                <i class="bi bi-pin-map me-2"></i>Map coordinates
                            </div>
                            <p class="small text-secondary mb-3">
                                Optional. Enter the geographic coordinates if known.
                            </p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="latitude" class="form-label small fw-semibold">
                                        Latitude
                                    </label>
                                    <input
                                        type="number"
                                        name="latitude"
                                        id="latitude"
                                        step="any"
                                        min="-90"
                                        max="90"
                                        class="{{ $inputClass }} @error('latitude') is-invalid @enderror"
                                        value="{{ old('latitude', $property?->latitude) }}"
                                        placeholder="-2.0000"
                                    >
                                    @error('latitude')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="longitude" class="form-label small fw-semibold">
                                        Longitude
                                    </label>
                                    <input
                                        type="number"
                                        name="longitude"
                                        id="longitude"
                                        step="any"
                                        min="-180"
                                        max="180"
                                        class="{{ $inputClass }} @error('longitude') is-invalid @enderror"
                                        value="{{ old('longitude', $property?->longitude) }}"
                                        placeholder="30.0000"
                                    >
                                    @error('longitude')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Property managers --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
            <div class="card-header bg-white border-bottom p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:46px;height:46px;background:#e8f7ee;color:#198754;">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                    <div>
                        <h2 class="h5 fw-bold mb-1" style="color:#19265d;">
                            Assign property managers
                        </h2>
                        <p class="small text-secondary mb-0">
                            Select the managers responsible for this property.
                        </p>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <p class="small text-secondary mb-0">
                        Only active manager profiles supplied by the controller are listed.
                    </p>

                    @if($managers->isNotEmpty())
                        <span class="badge rounded-pill px-3 py-2"
                              style="background:#edf0fa;color:#19265d;">
                            {{ $managers->count() }} available
                        </span>
                    @endif
                </div>

                @error('manager_ids')
                    <div class="text-danger small mb-2">{{ $message }}</div>
                @enderror
                @error('manager_ids.*')
                    <div class="text-danger small mb-2">{{ $message }}</div>
                @enderror

                @forelse($managers as $manager)
                    <label for="manager_{{ $manager->id }}"
                           class="d-flex align-items-center gap-3 border rounded-3 p-3 mb-2"
                           style="cursor:pointer;">

                        <input
                            class="form-check-input mt-0 flex-shrink-0"
                            type="checkbox"
                            name="manager_ids[]"
                            value="{{ $manager->id }}"
                            id="manager_{{ $manager->id }}"
                            @checked(in_array((int) $manager->id, $selectedManagers, true))
                        >

                        <span class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                              style="width:42px;height:42px;background:#edf0fa;color:#19265d;">
                            <i class="bi bi-person-badge fs-5"></i>
                        </span>

                        <span class="flex-grow-1">
                            <span class="d-block fw-semibold" style="color:#19265d;">
                                {{ $manager->user?->name ?? 'Unnamed manager' }}
                            </span>
                            <span class="d-block small text-secondary">
                                {{ $manager->user?->email ?? 'No email available' }}
                                @if($manager->job_title)
                                    <span class="mx-1">·</span>{{ $manager->job_title }}
                                @endif
                            </span>
                        </span>

                        <span class="small text-secondary">
                            <i class="bi bi-check2-square"></i>
                        </span>
                    </label>
                @empty
                    <div class="text-center rounded-3 p-4" style="background:#f7f8fb;">
                        <i class="bi bi-person-exclamation fs-2 d-block mb-2"
                           style="color:#D05208;"></i>
                        <div class="fw-semibold" style="color:#19265d;">
                            No active managers available
                        </div>
                        <p class="small text-secondary mb-0">
                            You can assign managers after eligible manager profiles have been created.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Property images --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
            <div class="card-header bg-white border-bottom p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:46px;height:46px;background:#fff0e8;color:#D05208;">
                        <i class="bi bi-images fs-4"></i>
                    </div>
                    <div>
                        <h2 class="h5 fw-bold mb-1" style="color:#19265d;">
                            Property images
                        </h2>
                        <p class="small text-secondary mb-0">
                            Upload photos to help identify and present the property.
                        </p>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <label for="images" class="form-label fw-semibold">
                    Upload property photos
                </label>

                <input
                    type="file"
                    name="images[]"
                    id="images"
                    class="form-control @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    multiple
                >

                @error('images')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
                @error('images.*')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror

                <div class="small text-secondary mt-2">
                    <i class="bi bi-info-circle me-1"></i>
                    JPG, PNG or WebP. The file size and number of images must match your server-side validation rules.
                </div>

                @if($property && $property->images && $property->images->isNotEmpty())
                    <hr class="my-4">

                    <div class="fw-semibold mb-3" style="color:#19265d;">
                        Existing property images
                    </div>

                    <div class="row g-3">
                        @foreach($property->images as $image)
                            <div class="col-6 col-md-4 col-lg-3">
                                <div class="border rounded-3 overflow-hidden h-100">
                                    <img
                                        src="{{ asset('storage/' . $image->image_path) }}"
                                        alt="{{ $image->caption ?? $property->name }}"
                                        class="w-100"
                                        style="height:135px;object-fit:cover;"
                                    >
                                    <div class="p-2">
                                        @if($image->is_primary)
                                            <span class="badge rounded-pill"
                                                  style="background:#fff0e8;color:#D05208;">
                                                <i class="bi bi-star-fill me-1"></i> Primary image
                                            </span>
                                        @else
                                            <span class="small text-secondary">Property photo</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <p class="small text-secondary mt-3 mb-0">
                        Existing images are displayed for reference. Uploading new files will only append images if your controller is implemented that way.
                    </p>
                @endif
            </div>
        </div>

    </div>

    {{-- Sidebar --}}
    <div class="col-xl-4">

        <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
            <div class="card-header bg-white border-bottom p-3">
                <h2 class="h6 fw-bold mb-0" style="color:#19265d;">
                    <i class="bi bi-clipboard-check me-2" style="color:#D05208;"></i>
                    Before you save
                </h2>
            </div>

            <div class="card-body p-3">
                <div class="d-flex gap-3 mb-3">
                    <i class="bi bi-check-circle fs-5" style="color:#198754;"></i>
                    <div>
                        <div class="fw-semibold small">Property details</div>
                        <div class="small text-secondary">Check the name, type and status.</div>
                    </div>
                </div>

                <div class="d-flex gap-3 mb-3">
                    <i class="bi bi-check-circle fs-5" style="color:#198754;"></i>
                    <div>
                        <div class="fw-semibold small">Ownership</div>
                        <div class="small text-secondary">Choose the correct property owner.</div>
                    </div>
                </div>

                <div class="d-flex gap-3 mb-3">
                    <i class="bi bi-check-circle fs-5" style="color:#198754;"></i>
                    <div>
                        <div class="fw-semibold small">Location</div>
                        <div class="small text-secondary">Enter accurate location information.</div>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <i class="bi bi-check-circle fs-5" style="color:#198754;"></i>
                    <div>
                        <div class="fw-semibold small">Management team</div>
                        <div class="small text-secondary">Assign the appropriate managers.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3"
                         style="width:44px;height:44px;background:#edf0fa;color:#19265d;">
                        <i class="bi bi-shield-lock fs-4"></i>
                    </div>
                    <h2 class="h6 fw-bold mb-0" style="color:#19265d;">
                        Property records
                    </h2>
                </div>
                <p class="small text-secondary mb-0">
                    Keep ownership, location and management assignments accurate so your team can maintain reliable property records.
                </p>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn text-white py-3 fw-semibold"
                    style="background:#D05208;border-radius:10px;">
                <i class="bi bi-check2-circle me-2"></i>
                {{ $property ? 'Save property changes' : 'Create property' }}
            </button>

            <a href="{{ $property
                ? route('admin.properties.show', $property)
                : route('admin.properties.index') }}"
               class="btn btn-outline-secondary py-2"
               style="border-radius:10px;">
                Cancel
            </a>
        </div>

        <p class="small text-secondary text-center mt-3">
            <i class="bi bi-lock me-1"></i>
            Required fields are marked with an asterisk.
        </p>
    </div>
</div>
