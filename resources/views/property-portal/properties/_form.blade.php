@php
    $property = $property ?? new \App\Models\Property();

    $isEditing = $property->exists;

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
@endphp

@if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm mb-4">
        <div class="fw-bold mb-2">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Please review the form
        </div>

        <ul class="small mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">

    {{-- Main form --}}
    <div class="col-xl-8">

        {{-- Basic information --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">

            <div class="card-header bg-white p-4">
                <h5 class="fw-bold mb-1" style="color:#19265d;">
                    <i class="bi bi-building me-2" style="color:#D05208;"></i>
                    Property information
                </h5>
                <p class="text-muted small mb-0">
                    Enter the main details of your property.
                </p>
            </div>

            <div class="card-body p-4">
                <div class="row g-3">

                    <div class="col-12">
                        <label class="form-label fw-semibold" for="name">
                            Property name <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $property->name) }}"
                            placeholder="e.g. Kigali Heights Apartments"
                            required
                        >

                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="property_type">
                            Property type <span class="text-danger">*</span>
                        </label>

                        <select
                            name="property_type"
                            id="property_type"
                            class="form-select @error('property_type') is-invalid @enderror"
                            required
                        >
                            <option value="">Select property type</option>

                            @foreach($propertyTypes as $value => $label)
                                <option value="{{ $value }}"
                                    @selected(old('property_type', $property->property_type) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        @error('property_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="year_built">
                            Year built
                        </label>

                        <input
                            type="number"
                            name="year_built"
                            id="year_built"
                            class="form-control @error('year_built') is-invalid @enderror"
                            value="{{ old('year_built', $property->year_built) }}"
                            min="1800"
                            max="{{ date('Y') + 5 }}"
                            placeholder="e.g. 2022"
                        >

                        @error('year_built')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="status">
                            Property status <span class="text-danger">*</span>
                        </label>

                        <select
                            name="status"
                            id="status"
                            class="form-select @error('status') is-invalid @enderror"
                            required
                        >
                            @foreach($propertyStatuses as $value => $label)
                                <option value="{{ $value }}"
                                    @selected(old('status', $property->status ?? 'active') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold" for="description">
                            Property description
                        </label>

                        <textarea
                            name="description"
                            id="description"
                            rows="4"
                            class="form-control @error('description') is-invalid @enderror"
                            placeholder="Describe the property, facilities and main features..."
                        >{{ old('description', $property->description) }}</textarea>

                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- Location --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">

            <div class="card-header bg-white p-4">
                <h5 class="fw-bold mb-1" style="color:#19265d;">
                    <i class="bi bi-geo-alt me-2" style="color:#D05208;"></i>
                    Property location
                </h5>
                <p class="text-muted small mb-0">
                    Provide the property's physical location.
                </p>
            </div>

            <div class="card-body p-4">
                <div class="row g-3">

                    @foreach([
                        'country' => 'Country',
                        'province' => 'Province',
                        'district' => 'District',
                        'sector' => 'Sector',
                        'cell' => 'Cell',
                        'village' => 'Village',
                    ] as $field => $label)

                        <div class="col-md-6">
                            <label for="{{ $field }}" class="form-label fw-semibold">
                                {{ $label }}
                                @if(in_array($field, ['country', 'district']))
                                    <span class="text-danger">*</span>
                                @endif
                            </label>

                            <input
                                type="text"
                                name="{{ $field }}"
                                id="{{ $field }}"
                                class="form-control @error($field) is-invalid @enderror"
                                value="{{ old($field, $property->{$field} ?? ($field === 'country' ? 'Rwanda' : '')) }}"
                                {{ in_array($field, ['country', 'district']) ? 'required' : '' }}
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
                            class="form-control @error('address') is-invalid @enderror"
                            value="{{ old('address', $property->address) }}"
                            placeholder="Street, road or nearby landmark"
                        >

                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <div class="rounded-3 p-3" style="background:#f7f8fb;">
                            <h6 class="fw-semibold" style="color:#19265d;">
                                <i class="bi bi-pin-map me-2"></i>
                                Map coordinates
                            </h6>

                            <p class="small text-muted">
                                Optional geographic coordinates.
                            </p>

                            <div class="row g-3">

                                <div class="col-md-6">
                                    <label for="latitude" class="form-label">
                                        Latitude
                                    </label>

                                    <input
                                        type="number"
                                        step="any"
                                        min="-90"
                                        max="90"
                                        name="latitude"
                                        id="latitude"
                                        class="form-control @error('latitude') is-invalid @enderror"
                                        value="{{ old('latitude', $property->latitude) }}"
                                    >

                                    @error('latitude')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="longitude" class="form-label">
                                        Longitude
                                    </label>

                                    <input
                                        type="number"
                                        step="any"
                                        min="-180"
                                        max="180"
                                        name="longitude"
                                        id="longitude"
                                        class="form-control @error('longitude') is-invalid @enderror"
                                        value="{{ old('longitude', $property->longitude) }}"
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

        {{-- Images --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">

            <div class="card-header bg-white p-4">
                <h5 class="fw-bold mb-1" style="color:#19265d;">
                    <i class="bi bi-images me-2" style="color:#D05208;"></i>
                    Property images
                </h5>
                <p class="text-muted small mb-0">
                    Upload photos of your property.
                </p>
            </div>

            <div class="card-body p-4">

                <label for="images" class="form-label fw-semibold">
                    Upload photos
                </label>

                <input
                    type="file"
                    name="images[]"
                    id="images"
                    class="form-control @error('images') is-invalid @enderror"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                >

                @error('images')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror

                @error('images.*')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror

                <p class="small text-muted mt-2 mb-0">
                    JPG, PNG or WebP. Maximum 5 MB per image.
                </p>

                @if($isEditing && $property->images && $property->images->isNotEmpty())
                    <hr class="my-4">

                    <h6 class="fw-semibold mb-3">Existing images</h6>

                    <div class="row g-3">
                        @foreach($property->images as $image)
                            <div class="col-6 col-md-4">
                                <img
                                    src="{{ asset('storage/' . $image->image_path) }}"
                                    alt="{{ $property->name }}"
                                    class="w-100 rounded-3"
                                    style="height:140px;object-fit:cover;"
                                >
                            </div>
                        @endforeach
                    </div>
                @endif

            </div>
        </div>

    </div>

    {{-- Sidebar --}}
    <div class="col-xl-4">

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white p-3">
                <h6 class="fw-bold mb-0" style="color:#19265d;">
                    <i class="bi bi-clipboard-check me-2"
                       style="color:#D05208;"></i>
                    Before you save
                </h6>
            </div>

            <div class="card-body p-3">

                @foreach([
                    ['bi-building', 'Property details', 'Provide the correct property name and type.'],
                    ['bi-person-check', 'Ownership', 'This property will belong to your owner profile.'],
                    ['bi-geo-alt', 'Location', 'Enter accurate property location details.'],
                    ['bi-images', 'Property photos', 'Upload clear images of the property.'],
                ] as [$icon, $title, $description])

                    <div class="d-flex gap-3 mb-3">
                        <i class="bi {{ $icon }} fs-5"
                           style="color:#D05208;"></i>

                        <div>
                            <div class="fw-semibold small">{{ $title }}</div>
                            <div class="small text-muted">{{ $description }}</div>
                        </div>
                    </div>

                @endforeach

            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <i class="bi bi-shield-check fs-3"
                       style="color:#198754;"></i>

                    <h6 class="fw-bold mb-0" style="color:#19265d;">
                        Ownership protection
                    </h6>
                </div>

                <p class="small text-muted mb-0">
                    Your property will automatically be associated with
                    your authenticated owner account. Ownership is
                    validated by the application.
                </p>
            </div>
        </div>

        <div class="d-grid gap-2">

            <button type="submit"
                    class="btn text-white py-3 fw-semibold"
                    style="background:#D05208;border-radius:10px;">

                <i class="bi bi-check2-circle me-2"></i>
                {{ $isEditing ? 'Save property changes' : 'Create property' }}

            </button>

            <a href="{{ route('property-manager.properties.index') }}"
               class="btn btn-outline-secondary py-2"
               style="border-radius:10px;">
                Cancel
            </a>

        </div>

    </div>

</div>
