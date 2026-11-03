@extends('layouts.app')

@section('title', $property->name . ' | Property Details')

@section('content')
@php
/*
|--------------------------------------------------------------------------
| Display helpers
|--------------------------------------------------------------------------
*/
$statusConfig = [
'active' => ['icon' => 'bi-check-circle-fill'],
'inactive' => ['icon' => 'bi-pause-circle-fill'],
'under_maintenance' => ['icon' => 'bi-tools'],
];

$pillMap = [
'active' => 'success',
'inactive' => 'muted',
'under_maintenance' => 'warning',
'available' => 'success',
'occupied' => 'navy',
'reserved' => 'info',
'maintenance' => 'warning',
];

$pill = fn ($value) => $pillMap[$value] ?? 'muted';
$label = fn ($value) => ucfirst(str_replace('_', ' ', (string) $value));
$money = fn ($unit, $amount) => $unit->currency . ' '
. number_format((float) $amount, $unit->currency === 'RWF' ? 0 : 2);

$statusIcon = $statusConfig[$property->status]['icon'] ?? 'bi-circle';

$location = collect([
$property->address,
$property->village,
$property->cell,
$property->sector,
$property->district,
$property->province,
$property->country,
])->filter()->implode(', ');

/*
|--------------------------------------------------------------------------
| Structure data
|--------------------------------------------------------------------------
*/
$buildings = $property->buildings;
$activeBuildings = $buildings->where('status', 'active');
$hasActiveBuildings = $activeBuildings->isNotEmpty();

$buildingCount = $buildings->count();
$floorCount = $buildings->sum(fn ($b) => $b->floors->count());
$unitCount = $buildings->sum(fn ($b) => $b->units->count());

$availableUnits = $buildings->sum(fn ($b) => $b->units->where('status', 'available')->count());
$occupiedUnits = $buildings->sum(fn ($b) => $b->units->where('status', 'occupied')->count());
$inactiveUnits = $buildings->sum(fn ($b) => $b->units->where('status', 'inactive')->count());

$rentableUnits = max($unitCount - $inactiveUnits, 0);
$occupancy = $rentableUnits > 0 ? (int) round($occupiedUnits / $rentableUnits * 100) : 0;

$coverImage = $property->images->firstWhere('is_primary', true) ?? $property->images->first();

/*
|--------------------------------------------------------------------------
| Image manager targets (one modal per building / floor / unit)
|--------------------------------------------------------------------------
*/
$imageRoutes = [
'building' => [
'primary' => 'admin.properties.buildings.images.primary',
'delete' => 'admin.properties.buildings.images.destroy',
'param' => 'building',
],
'floor' => [
'primary' => 'admin.properties.floors.images.primary',
'delete' => 'admin.properties.floors.images.destroy',
'param' => 'floor',
],
'unit' => [
'primary' => 'admin.properties.units.images.primary',
'delete' => 'admin.properties.units.images.destroy',
'param' => 'unit',
],
];

$imageTargets = collect();

foreach ($buildings as $b) {
$imageTargets->push(['type' => 'building', 'parent' => $b, 'title' => $b->name]);

foreach ($b->floors as $f) {
$imageTargets->push(['type' => 'floor', 'parent' => $f, 'title' => $f->name . ' · ' . $b->name]);
}

foreach ($b->units as $u) {
$imageTargets->push(['type' => 'unit', 'parent' => $u, 'title' => 'Unit ' . $u->unit_number . ' · ' . $b->name]);
}
}

/*
|--------------------------------------------------------------------------
| Form modals: null = "create", model = "edit"
|--------------------------------------------------------------------------
*/
$buildingForms = collect([null])->concat($buildings);
$floorForms = collect([null])->concat($buildings->flatMap->floors);
$unitForms = collect([null])->concat($buildings->flatMap->units);

$unitTypes = [
'apartment' => 'Apartment',
'studio' => 'Studio',
'house' => 'House',
'office' => 'Office',
'shop' => 'Shop',
'warehouse' => 'Warehouse',
'other' => 'Other',
];

$unitStatuses = [
'available' => 'Available',
'occupied' => 'Occupied',
'reserved' => 'Reserved',
'maintenance' => 'Under maintenance',
'inactive' => 'Inactive',
];
@endphp

<div class="terra-property-page">

    {{-- ================================================================
         PAGE HEADING
         ================================================================ --}}
    <div class="terra-page-heading d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div class="min-w-0">
            <div class="terra-breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <i class="bi bi-chevron-right" style="font-size:9px;"></i>
                <a href="{{ route('admin.properties.index') }}">Properties &amp; Units</a>
                <i class="bi bi-chevron-right" style="font-size:9px;"></i>
                <span class="text-truncate">{{ $property->name }}</span>
            </div>

            <h1 class="terra-page-title">Property details</h1>
            <p class="terra-page-subtitle">
                Review this property, manage its buildings, floors and rental units, and see who looks after it.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.properties.index') }}" class="btn-terra-outline">
                <i class="bi bi-arrow-left"></i> All properties
            </a>

            <a href="{{ route('admin.properties.edit', $property) }}" class="btn btn-terra">
                <i class="bi bi-pencil-square"></i> Edit property
            </a>

            @if($property->status !== 'inactive')
            <form method="POST"
                action="{{ route('admin.properties.destroy', $property) }}"
                onsubmit="return confirm('Deactivate this property?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger pp-danger-lg">
                    <i class="bi bi-pause-circle me-1"></i> Deactivate
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- ================================================================
         HERO
         ================================================================ --}}
    <section class="pp-hero mb-4">
        <div class="row g-4 align-items-center position-relative">
            <div class="col-lg-8">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="pp-code">
                        <i class="bi bi-upc-scan"></i>
                        {{ $property->property_code ?: 'Property record' }}
                    </span>

                    <span class="pp-pill is-{{ $pill($property->status) }}">
                        <i class="bi {{ $statusIcon }}"></i>
                        {{ $label($property->status) }}
                    </span>
                </div>

                <h2 class="pp-hero-title">{{ $property->name }}</h2>

                <p class="pp-hero-location">
                    <i class="bi bi-geo-alt me-1"></i>
                    {{ $location ?: 'Property location not specified' }}
                </p>

                <div class="d-flex flex-wrap gap-2">
                    <span class="pp-hero-chip">
                        <i class="bi bi-building"></i>
                        {{ $label($property->property_type) }}
                    </span>

                    @if($property->year_built)
                    <span class="pp-hero-chip">
                        <i class="bi bi-calendar3"></i>
                        Built {{ $property->year_built }}
                    </span>
                    @endif
                </div>
            </div>

            <div class="col-lg-4">
                @if($coverImage)
                <img src="{{ asset('storage/' . $coverImage->image_path) }}"
                    alt="{{ $coverImage->caption ?? $property->name }}"
                    class="pp-hero-cover">
                @else
                <div class="pp-hero-cover pp-hero-cover-empty">
                    <i class="bi bi-image"></i>
                    <span>No cover image yet</span>
                </div>
                @endif
            </div>
        </div>

        <div class="pp-hero-meta position-relative">
            <div>
                <div class="pp-hero-meta-label">Property owner</div>
                <div class="pp-hero-meta-value">
                    <i class="bi bi-person-circle me-1"></i>
                    {{ $property->owner?->name ?? 'Owner unavailable' }}
                </div>
                @if($property->owner?->email)
                <div class="pp-hero-meta-sub">{{ $property->owner->email }}</div>
                @endif
            </div>

            <div>
                <div class="pp-hero-meta-label">Managers</div>
                <div class="pp-hero-meta-value">
                    <i class="bi bi-people me-1"></i>
                    {{ $property->activeManagers->count() }} assigned
                </div>
            </div>

            <div>
                <div class="pp-hero-meta-label">Last updated</div>
                <div class="pp-hero-meta-value">
                    <i class="bi bi-arrow-repeat me-1"></i>
                    {{ $property->updated_at?->format('d M Y') ?? 'Unavailable' }}
                </div>
            </div>
        </div>
    </section>

    {{-- ================================================================
         KEY NUMBERS
         ================================================================ --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="terra-stat-card">
                <div class="terra-stat-top">
                    <span class="terra-stat-label">Buildings</span>
                    <span class="terra-stat-icon orange"><i class="bi bi-buildings"></i></span>
                </div>
                <div class="terra-stat-value">{{ $buildingCount }}</div>
                <div class="terra-stat-foot">{{ $activeBuildings->count() }} active</div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="terra-stat-card">
                <div class="terra-stat-top">
                    <span class="terra-stat-label">Floors</span>
                    <span class="terra-stat-icon navy"><i class="bi bi-layers"></i></span>
                </div>
                <div class="terra-stat-value">{{ $floorCount }}</div>
                <div class="terra-stat-foot">Across all buildings</div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="terra-stat-card">
                <div class="terra-stat-top">
                    <span class="terra-stat-label">Rental units</span>
                    <span class="terra-stat-icon blue"><i class="bi bi-door-open"></i></span>
                </div>
                <div class="terra-stat-value">{{ $unitCount }}</div>
                <div class="terra-stat-foot">{{ $availableUnits }} available now</div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="terra-stat-card">
                <div class="terra-stat-top">
                    <span class="terra-stat-label">Occupancy</span>
                    <span class="terra-stat-icon green"><i class="bi bi-key"></i></span>
                </div>
                <div class="terra-stat-value">{{ $occupancy }}%</div>
                <div class="progress pp-progress mt-2" role="progressbar"
                    aria-label="Occupancy" aria-valuenow="{{ $occupancy }}"
                    aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar" style="width: {{ $occupancy }}%"></div>
                </div>
                <div class="terra-stat-foot">{{ $occupiedUnits }} of {{ $rentableUnits }} rentable units occupied</div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- ============================================================
             MAIN COLUMN
             ============================================================ --}}
        <div class="col-xl-8">

            {{-- Overview --}}
            <section class="terra-card mb-4" id="property-overview">
                <div class="terra-card-header">
                    <div>
                        <h3 class="terra-card-title">Property overview</h3>
                        <div class="terra-card-subtitle">Essential information about this property</div>
                    </div>

                    <a href="{{ route('admin.properties.edit', $property) }}" class="pp-btn">
                        <i class="bi bi-pencil"></i> Edit details
                    </a>
                </div>

                <div class="terra-card-body">
                    <div class="mb-4">
                        <div class="pp-label">Description</div>
                        <p class="mb-0" style="line-height:1.75;">
                            {{ $property->description ?: 'No description has been provided for this property.' }}
                        </p>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <div class="pp-detail">
                                <div class="pp-label"><i class="bi bi-geo-alt me-1"></i> Full address</div>
                                <div class="pp-value">{{ $location ?: 'Location not specified' }}</div>
                            </div>
                        </div>

                        @foreach([
                        'province' => 'Province',
                        'district' => 'District',
                        'sector' => 'Sector',
                        'cell' => 'Cell',
                        'village' => 'Village',
                        ] as $field => $fieldLabel)
                        <div class="col-6 col-md-4">
                            <div class="pp-detail">
                                <div class="pp-label">{{ $fieldLabel }}</div>
                                <div class="pp-value">{{ $property->$field ?: 'Not specified' }}</div>
                            </div>
                        </div>
                        @endforeach

                        <div class="col-6 col-md-4">
                            <div class="pp-detail">
                                <div class="pp-label">Country</div>
                                <div class="pp-value">{{ $property->country ?: 'Not specified' }}</div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="pp-detail">
                                <div class="pp-label"><i class="bi bi-pin-map me-1"></i> Latitude</div>
                                <div class="pp-value">{{ $property->latitude ?? 'Not specified' }}</div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="pp-detail">
                                <div class="pp-label"><i class="bi bi-pin-map-fill me-1"></i> Longitude</div>
                                <div class="pp-value">{{ $property->longitude ?? 'Not specified' }}</div>
                            </div>
                        </div>
                    </div>

                    @if($property->latitude !== null && $property->longitude !== null)
                    <div class="mt-3">
                        <a href="https://www.google.com/maps?q={{ urlencode($property->latitude . ',' . $property->longitude) }}"
                            target="_blank" rel="noopener noreferrer" class="pp-btn">
                            <i class="bi bi-map"></i> View on Google Maps
                        </a>
                    </div>
                    @endif
                </div>
            </section>

            {{-- Property gallery --}}
            <section class="terra-card mb-4" id="property-gallery">
                <div class="terra-card-header">
                    <div>
                        <h3 class="terra-card-title">Property gallery</h3>
                        <div class="terra-card-subtitle">Photos of the property as a whole</div>
                    </div>

                    <span class="pp-pill is-navy">{{ $property->images->count() }} {{ \Illuminate\Support\Str::plural('image', $property->images->count()) }}</span>
                </div>

                <div class="terra-card-body">
                    @if($property->images->isNotEmpty())
                    <div class="row g-3">
                        @foreach($property->images as $image)
                        <div class="col-6 col-md-4">
                            <button type="button"
                                class="pp-gallery-tile"
                                data-bs-toggle="modal"
                                data-bs-target="#propertyImageModal"
                                data-image-src="{{ asset('storage/' . $image->image_path) }}"
                                data-image-caption="{{ $image->caption ?: $property->name }}"
                                aria-label="View image larger">
                                <img src="{{ asset('storage/' . $image->image_path) }}"
                                    alt="{{ $image->caption ?? $property->name }}"
                                    loading="lazy">

                                @if($image->is_primary)
                                <span class="pp-pill is-orange pp-gallery-flag">
                                    <i class="bi bi-star-fill"></i> Primary
                                </span>
                                @endif
                            </button>

                            @if($image->caption)
                            <div class="small pp-muted text-truncate mt-2">{{ $image->caption }}</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="terra-empty-state pp-empty">
                        <div class="terra-empty-icon"><i class="bi bi-images"></i></div>
                        <h6>No property images yet</h6>
                        <p class="mb-3">Photos make the property record easier to recognise and present.</p>
                        <a href="{{ route('admin.properties.edit', $property) }}" class="btn btn-terra">
                            <i class="bi bi-upload"></i> Add images
                        </a>
                    </div>
                    @endif
                </div>
            </section>

            {{-- Buildings, floors and units --}}
            <section class="terra-card" id="property-structure">
                <div class="terra-card-header flex-wrap">
                    <div>
                        <h3 class="terra-card-title">Buildings, floors &amp; units</h3>
                        <div class="terra-card-subtitle">
                            Manage the physical structure, images and rental availability.
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="pp-btn"
                            data-bs-toggle="modal" data-bs-target="#createBuildingModal">
                            <i class="bi bi-plus-lg"></i> Building
                        </button>

                        <button type="button" class="pp-btn"
                            data-bs-toggle="modal" data-bs-target="#createFloorModal"
                            @disabled(!$hasActiveBuildings)>
                            <i class="bi bi-plus-lg"></i> Floor
                        </button>

                        <button type="button" class="pp-btn is-primary"
                            data-bs-toggle="modal" data-bs-target="#createUnitModal"
                            @disabled(!$hasActiveBuildings)>
                            <i class="bi bi-plus-lg"></i> Rental unit
                        </button>
                    </div>
                </div>

                <div class="terra-card-body">
                    @if($buildings->isNotEmpty())
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div class="small pp-muted">
                            {{ $buildingCount }} {{ \Illuminate\Support\Str::plural('building', $buildingCount) }}
                            · {{ $floorCount }} {{ \Illuminate\Support\Str::plural('floor', $floorCount) }}
                            · {{ $unitCount }} {{ \Illuminate\Support\Str::plural('unit', $unitCount) }}
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="pp-btn" data-collapse-all="show">
                                <i class="bi bi-arrows-expand"></i> Expand all
                            </button>
                            <button type="button" class="pp-btn" data-collapse-all="hide">
                                <i class="bi bi-arrows-collapse"></i> Collapse all
                            </button>
                        </div>
                    </div>
                    @endif

                    @forelse($buildings as $building)
                    @php
                    $buildingImage = $building->images->firstWhere('is_primary', true)
                    ?? $building->images->first();

                    $buildingIsActive = $building->status === 'active';

                    $groups = $building->floors->map(fn ($f) => [
                    'floor' => $f,
                    'units' => $f->units,
                    ]);

                    $looseUnits = $building->units->whereNull('floor_id');

                    if ($looseUnits->isNotEmpty()) {
                    $groups->push(['floor' => null, 'units' => $looseUnits]);
                    }
                    @endphp

                    <div class="pp-building" id="building-{{ $building->id }}">

                        {{-- Building header --}}
                        <div class="pp-building-head">
                            <button type="button"
                                class="pp-toggle"
                                data-bs-toggle="collapse"
                                data-bs-target="#buildingBody{{ $building->id }}"
                                aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                aria-controls="buildingBody{{ $building->id }}"
                                title="Show or hide this building">
                                <i class="bi bi-chevron-down"></i>
                            </button>

                            @if($buildingImage)
                            <img src="{{ asset('storage/' . $buildingImage->image_path) }}"
                                alt="{{ $building->name }}" class="pp-thumb is-lg">
                            @else
                            <div class="pp-thumb is-lg"><i class="bi bi-buildings"></i></div>
                            @endif

                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <h4 class="pp-building-name">{{ $building->name }}</h4>
                                    <span class="pp-pill is-{{ $buildingIsActive ? 'success' : 'muted' }}">
                                        {{ ucfirst($building->status) }}
                                    </span>
                                </div>

                                <div class="small pp-muted mt-1">
                                    Code {{ $building->building_code }}
                                    · {{ $building->number_of_floors }} planned floors
                                    · {{ $building->floors->count() }} registered
                                    · {{ $building->units->count() }} units
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="pp-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#images-building-{{ $building->id }}"
                                    title="Manage building images">
                                    <i class="bi bi-images"></i> {{ $building->images->count() }}
                                </button>

                                <button type="button" class="pp-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editBuilding{{ $building->id }}"
                                    title="Edit building">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>

                                @if($buildingIsActive)
                                <form method="POST"
                                    action="{{ route('admin.properties.buildings.destroy', [$property, $building]) }}"
                                    onsubmit="return confirm('Deactivate this building?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="pp-btn is-danger" title="Deactivate building">
                                        <i class="bi bi-pause-circle"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>

                        {{-- Building body --}}
                        <div class="collapse pp-building-body {{ $loop->first ? 'show' : '' }}"
                            id="buildingBody{{ $building->id }}">
                            <div class="pp-building-inner">

                                @if($buildingIsActive)
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <button type="button" class="pp-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#createFloorModal"
                                        data-prefill-building="{{ $building->id }}">
                                        <i class="bi bi-plus-lg"></i> Add floor
                                    </button>

                                    <button type="button" class="pp-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#createUnitModal"
                                        data-prefill-building="{{ $building->id }}">
                                        <i class="bi bi-plus-lg"></i> Add unit
                                    </button>
                                </div>
                                @endif

                                @forelse($groups as $group)
                                @php
                                $floor = $group['floor'];
                                $units = $group['units'];
                                $floorIsActive = $floor ? $floor->status === 'active' : true;
                                @endphp

                                <div class="pp-floor">
                                    <div class="pp-floor-head">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="pp-floor-icon"><i class="bi {{ $floor ? 'bi-layers' : 'bi-door-open' }}"></i></span>

                                            @if($floor)
                                            <strong>{{ $floor->name }}</strong>
                                            <span class="pp-muted">Floor {{ $floor->floor_number }}</span>
                                            <span class="pp-pill is-{{ $floorIsActive ? 'success' : 'muted' }}">
                                                {{ ucfirst($floor->status) }}
                                            </span>
                                            @else
                                            <strong>Units without a floor</strong>
                                            @endif

                                            <span class="small pp-muted">
                                                {{ $units->count() }} {{ \Illuminate\Support\Str::plural('unit', $units->count()) }}
                                            </span>
                                        </div>

                                        <div class="d-flex flex-wrap gap-2">
                                            @if($buildingIsActive && $floorIsActive)
                                            <button type="button" class="pp-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#createUnitModal"
                                                data-prefill-building="{{ $building->id }}"
                                                data-prefill-floor="{{ $floor?->id }}">
                                                <i class="bi bi-plus-lg"></i> Unit
                                            </button>
                                            @endif

                                            @if($floor)
                                            <button type="button" class="pp-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#images-floor-{{ $floor->id }}"
                                                title="Manage floor images">
                                                <i class="bi bi-images"></i> {{ $floor->images->count() }}
                                            </button>

                                            <button type="button" class="pp-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editFloor{{ $floor->id }}"
                                                title="Edit floor">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            @if($floorIsActive)
                                            <form method="POST"
                                                action="{{ route('admin.properties.floors.destroy', [$property, $floor]) }}"
                                                onsubmit="return confirm('Deactivate this floor?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="pp-btn is-danger" title="Deactivate floor">
                                                    <i class="bi bi-pause-circle"></i>
                                                </button>
                                            </form>
                                            @endif
                                            @endif
                                        </div>
                                    </div>

                                    @if($units->isNotEmpty())
                                    <div class="terra-table-wrap">
                                        <table class="table terra-table pp-units-table">
                                            <thead>
                                                <tr>
                                                    <th>Unit</th>
                                                    <th>Type</th>
                                                    <th>Layout</th>
                                                    <th>Area</th>
                                                    <th>Rent</th>
                                                    <th>Status</th>
                                                    <th class="text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($units as $unit)
                                                @php
                                                $unitImage = $unit->images->firstWhere('is_primary', true)
                                                ?? $unit->images->first();
                                                @endphp

                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            @if($unitImage)
                                                            <img src="{{ asset('storage/' . $unitImage->image_path) }}"
                                                                alt="{{ $unit->unit_number }}" class="pp-thumb">
                                                            @else
                                                            <div class="pp-thumb"><i class="bi bi-door-closed"></i></div>
                                                            @endif

                                                            <strong>Unit {{ $unit->unit_number }}</strong>
                                                        </div>
                                                    </td>

                                                    <td>{{ $label($unit->unit_type) }}</td>

                                                    <td>{{ $unit->bedrooms }} bd · {{ $unit->bathrooms }} ba</td>

                                                    <td>
                                                        {{ $unit->area_sqm ? number_format($unit->area_sqm, 2) . ' m²' : '—' }}
                                                    </td>

                                                    <td>
                                                        <div class="fw-bold" style="color:var(--terra-navy);">
                                                            {{ $money($unit, $unit->rent_amount) }}
                                                            <span class="pp-muted fw-normal">/ month</span>
                                                        </div>
                                                        <div class="small pp-muted">
                                                            Deposit {{ $money($unit, $unit->deposit_amount) }}
                                                        </div>
                                                    </td>

                                                    <td>
                                                        <span class="pp-pill is-{{ $pill($unit->status) }}">
                                                            {{ $label($unit->status) }}
                                                        </span>
                                                    </td>

                                                    <td>
                                                        <div class="d-flex justify-content-end gap-2">
                                                            <button type="button" class="pp-btn"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#images-unit-{{ $unit->id }}"
                                                                title="Manage unit images">
                                                                <i class="bi bi-images"></i> {{ $unit->images->count() }}
                                                            </button>

                                                            <button type="button" class="pp-btn"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#editUnit{{ $unit->id }}"
                                                                title="Edit unit">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>

                                                            @if($unit->status !== 'inactive')
                                                            <form method="POST"
                                                                action="{{ route('admin.properties.units.destroy', [$property, $unit]) }}"
                                                                onsubmit="return confirm('Deactivate this unit? Units with lease history cannot be deactivated using this action.')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="pp-btn is-danger" title="Deactivate unit">
                                                                    <i class="bi bi-pause-circle"></i>
                                                                </button>
                                                            </form>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @else
                                    <div class="pp-floor-empty">No units on this floor yet.</div>
                                    @endif
                                </div>
                                @empty
                                <div class="pp-floor-empty is-boxed">
                                    This building has no floors or units yet.
                                    @if($buildingIsActive)
                                    Use <strong>Add floor</strong> or <strong>Add unit</strong> above to start.
                                    @endif
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="terra-empty-state pp-empty">
                        <div class="terra-empty-icon"><i class="bi bi-buildings"></i></div>
                        <h6>No buildings yet</h6>
                        <p class="mb-3">Add a building to start organising floors and rental units.</p>
                        <button type="button" class="btn btn-terra"
                            data-bs-toggle="modal" data-bs-target="#createBuildingModal">
                            <i class="bi bi-plus-lg"></i> Add first building
                        </button>
                    </div>
                    @endforelse
                </div>
            </section>
        </div>

        {{-- ============================================================
             SIDEBAR COLUMN
             ============================================================ --}}
        <div class="col-xl-4">

            {{-- Assigned managers --}}
            <section class="terra-card mb-4">
                <div class="terra-card-header">
                    <div>
                        <h3 class="terra-card-title">Assigned managers</h3>
                        <div class="terra-card-subtitle">People responsible for this property</div>
                    </div>

                    <span class="pp-pill is-navy">{{ $property->activeManagers->count() }}</span>
                </div>

                <div class="terra-card-body">
                    @forelse($property->activeManagers as $manager)
                    @php
                    $managerName = $manager->user?->name ?? 'Unnamed manager';
                    $managerInitials = collect(explode(' ', trim($managerName)))
                    ->filter()->take(2)
                    ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                    ->implode('');
                    @endphp

                    <div class="pp-manager">
                        <div class="pp-avatar">{{ $managerInitials ?: 'M' }}</div>

                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-bold" style="color:var(--terra-navy);">{{ $managerName }}</div>

                            @if($manager->user?->email)
                            <div class="small pp-muted text-break">{{ $manager->user->email }}</div>
                            @endif

                            <div class="small pp-muted">{{ $manager->job_title ?: 'Property manager' }}</div>
                        </div>

                        <i class="bi bi-check-circle-fill text-success" title="Active manager"></i>
                    </div>
                    @empty
                    <div class="terra-empty-state py-3">
                        <div class="terra-empty-icon"><i class="bi bi-people"></i></div>
                        <h6>No managers assigned</h6>
                        <p class="mb-0">Assign a manager to help oversee this property.</p>
                    </div>
                    @endforelse

                    <a href="{{ route('admin.properties.edit', $property) }}"
                        class="btn-terra-outline w-100 mt-3">
                        <i class="bi bi-person-gear"></i> Manage assignments
                    </a>
                </div>
            </section>

            {{-- Record information --}}
            <section class="terra-card mb-4">
                <div class="terra-card-header">
                    <div>
                        <h3 class="terra-card-title">Record information</h3>
                        <div class="terra-card-subtitle">Registration details</div>
                    </div>
                </div>

                <div class="terra-card-body">
                    <div class="pp-record">
                        <span class="pp-record-icon"><i class="bi bi-upc"></i></span>
                        <div>
                            <div class="pp-label">Property code</div>
                            <div class="pp-value">{{ $property->property_code ?: 'Not assigned' }}</div>
                        </div>
                    </div>

                    <div class="pp-record">
                        <span class="pp-record-icon"><i class="bi bi-calendar-plus"></i></span>
                        <div>
                            <div class="pp-label">Date created</div>
                            <div class="pp-value">{{ $property->created_at?->format('d M Y, H:i') ?? 'Unavailable' }}</div>
                        </div>
                    </div>

                    <div class="pp-record">
                        <span class="pp-record-icon"><i class="bi bi-arrow-repeat"></i></span>
                        <div>
                            <div class="pp-label">Last updated</div>
                            <div class="pp-value">{{ $property->updated_at?->format('d M Y, H:i') ?? 'Unavailable' }}</div>
                        </div>
                    </div>

                    <div class="pp-record">
                        <span class="pp-record-icon"><i class="bi bi-shield-check"></i></span>
                        <div>
                            <div class="pp-label">Current status</div>
                            <div class="pp-value">
                                <span class="pp-pill is-{{ $pill($property->status) }}">
                                    {{ $label($property->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Quick actions --}}
            <section class="terra-card">
                <div class="terra-card-header">
                    <div>
                        <h3 class="terra-card-title">Quick actions</h3>
                        <div class="terra-card-subtitle">Shortcuts for this property</div>
                    </div>
                </div>

                <div class="terra-card-body d-grid gap-2">
                    <button type="button" class="btn btn-terra"
                        data-bs-toggle="modal" data-bs-target="#createUnitModal"
                        @disabled(!$hasActiveBuildings)>
                        <i class="bi bi-door-open"></i> Add rental unit
                    </button>

                    <button type="button" class="btn-terra-outline"
                        data-bs-toggle="modal" data-bs-target="#createBuildingModal">
                        <i class="bi bi-buildings"></i> Add building
                    </button>

                    <a href="{{ route('admin.properties.edit', $property) }}" class="btn-terra-outline">
                        <i class="bi bi-pencil-square"></i> Update property details
                    </a>

                    <a href="#property-structure" class="btn-terra-outline">
                        <i class="bi bi-grid-1x2"></i> Jump to structure
                    </a>
                </div>
            </section>
        </div>
    </div>

    {{-- ================================================================
         MODALS
         ================================================================ --}}

    {{-- Property image preview --}}
    <div class="modal fade" id="propertyImageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="propertyImageCaption">Property image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-2">
                    <img src="" alt="" id="propertyImagePreview" class="img-fluid rounded" style="max-height:75vh;">
                </div>
            </div>
        </div>
    </div>

    {{-- Building create / edit --}}
    @foreach($buildingForms as $building)
    @php
    $modalId = $building ? 'editBuilding' . $building->id : 'createBuildingModal';
    $v = fn ($field, $default = null) => old('_modal') === $modalId ? old($field, $default) : $default;
    @endphp

    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form method="POST"
                action="{{ $building
                          ? route('admin.properties.buildings.update', [$property, $building])
                          : route('admin.properties.buildings.store', $property) }}"
                enctype="multipart/form-data"
                class="modal-content">
                @csrf
                @if($building) @method('PUT') @endif
                <input type="hidden" name="_modal" value="{{ $modalId }}">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">{{ $building ? 'Edit ' . $building->name : 'Add building' }}</h5>
                        <div class="small pp-muted">
                            {{ $building ? 'Update the building details and add images.' : 'Register a new building on this property.' }}
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @if(old('_modal') === $modalId && $errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Building code *</label>
                            <input name="building_code" class="form-control" required maxlength="50"
                                value="{{ $v('building_code', $building?->building_code) }}">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Building name *</label>
                            <input name="name" class="form-control" required
                                value="{{ $v('name', $building?->name) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Number of floors *</label>
                            <input type="number" name="number_of_floors" class="form-control"
                                min="1" max="200" required
                                value="{{ $v('number_of_floors', $building?->number_of_floors ?? 1) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                @foreach(['active' => 'Active', 'inactive' => 'Inactive'] as $value => $optLabel)
                                <option value="{{ $value }}"
                                    @selected($v('status', $building?->status ?? 'active') === $value)>
                                    {{ $optLabel }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ $v('description', $building?->description) }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Building images</label>
                            <input type="file" name="images[]" class="form-control"
                                accept=".jpg,.jpeg,.png,.webp" multiple>
                            <div class="form-text">
                                JPG, PNG or WebP. Maximum 5 MB per image.
                                @if($building) Existing images are managed from the images button on the building. @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">{{ $building ? 'Save changes' : 'Create building' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach

    {{-- Floor create / edit --}}
    @foreach($floorForms as $floor)
    @php
    $modalId = $floor ? 'editFloor' . $floor->id : 'createFloorModal';
    $v = fn ($field, $default = null) => old('_modal') === $modalId ? old($field, $default) : $default;
    @endphp

    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form method="POST"
                action="{{ $floor
                          ? route('admin.properties.floors.update', [$property, $floor])
                          : route('admin.properties.floors.store', $property) }}"
                enctype="multipart/form-data"
                class="modal-content">
                @csrf
                @if($floor) @method('PUT') @endif
                <input type="hidden" name="_modal" value="{{ $modalId }}">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">{{ $floor ? 'Edit ' . $floor->name : 'Add floor' }}</h5>
                        <div class="small pp-muted">
                            {{ $floor ? 'Update the floor details and add images.' : 'Add a floor to one of your active buildings.' }}
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @if(old('_modal') === $modalId && $errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Building *</label>
                            <select name="building_id" class="form-select" required>
                                <option value="">Select building</option>
                                @foreach($activeBuildings as $optBuilding)
                                <option value="{{ $optBuilding->id }}"
                                    @selected((string) $v('building_id', $floor?->building_id) === (string) $optBuilding->id)>
                                    {{ $optBuilding->name }} ({{ $optBuilding->building_code }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Floor name *</label>
                            <input name="name" class="form-control" required
                                value="{{ $v('name', $floor?->name) }}"
                                placeholder="e.g. Ground Floor">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Floor number *</label>
                            <input type="number" name="floor_number" class="form-control"
                                min="-5" max="300" required
                                value="{{ $v('floor_number', $floor?->floor_number ?? 0) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                @foreach(['active' => 'Active', 'inactive' => 'Inactive'] as $value => $optLabel)
                                <option value="{{ $value }}"
                                    @selected($v('status', $floor?->status ?? 'active') === $value)>
                                    {{ $optLabel }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ $v('description', $floor?->description) }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Floor images</label>
                            <input type="file" name="images[]" class="form-control"
                                accept=".jpg,.jpeg,.png,.webp" multiple>
                            <div class="form-text">
                                JPG, PNG or WebP. Maximum 5 MB per image.
                                @if($floor) Existing images are managed from the images button on the floor. @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">{{ $floor ? 'Save changes' : 'Create floor' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach

    {{-- Unit create / edit --}}
    @foreach($unitForms as $unit)
    @php
    $modalId = $unit ? 'editUnit' . $unit->id : 'createUnitModal';
    $v = fn ($field, $default = null) => old('_modal') === $modalId ? old($field, $default) : $default;
    @endphp

    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form method="POST"
                action="{{ $unit
                          ? route('admin.properties.units.update', [$property, $unit])
                          : route('admin.properties.units.store', $property) }}"
                enctype="multipart/form-data"
                class="modal-content">
                @csrf
                @if($unit) @method('PUT') @endif
                <input type="hidden" name="_modal" value="{{ $modalId }}">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">{{ $unit ? 'Edit unit ' . $unit->unit_number : 'Add rental unit' }}</h5>
                        <div class="small pp-muted">
                            {{ $unit ? 'Update pricing, availability and details.' : 'Register a unit that can be rented out.' }}
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @if(old('_modal') === $modalId && $errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Building *</label>
                            <select name="building_id" class="form-select unit-building-select" required>
                                <option value="">Select building</option>
                                @foreach($activeBuildings as $optBuilding)
                                <option value="{{ $optBuilding->id }}"
                                    @selected((string) $v('building_id', $unit?->building_id) === (string) $optBuilding->id)>
                                    {{ $optBuilding->name }} ({{ $optBuilding->building_code }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Floor</label>
                            <select name="floor_id" class="form-select unit-floor-select">
                                <option value="">No specific floor</option>
                                @foreach($activeBuildings as $optBuilding)
                                @foreach($optBuilding->floors as $optFloor)
                                <option value="{{ $optFloor->id }}"
                                    data-building-id="{{ $optBuilding->id }}"
                                    @selected((string) $v('floor_id', $unit?->floor_id) === (string) $optFloor->id)>
                                    {{ $optFloor->name }} — {{ $optBuilding->name }}
                                </option>
                                @endforeach
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Unit number *</label>
                            <input name="unit_number" class="form-control" required maxlength="50"
                                value="{{ $v('unit_number', $unit?->unit_number) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Unit type *</label>
                            <select name="unit_type" class="form-select" required>
                                @foreach($unitTypes as $value => $optLabel)
                                <option value="{{ $value }}"
                                    @selected($v('unit_type', $unit?->unit_type ?? 'apartment') === $value)>
                                    {{ $optLabel }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Availability *</label>
                            <select name="status" class="form-select" required>
                                @foreach($unitStatuses as $value => $optLabel)
                                <option value="{{ $value }}"
                                    @selected($v('status', $unit?->status ?? 'available') === $value)>
                                    {{ $optLabel }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Bedrooms *</label>
                            <input type="number" name="bedrooms" class="form-control" min="0" max="50" required
                                value="{{ $v('bedrooms', $unit?->bedrooms ?? 0) }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Bathrooms *</label>
                            <input type="number" name="bathrooms" class="form-control" min="0" max="50" required
                                value="{{ $v('bathrooms', $unit?->bathrooms ?? 0) }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Area (m²)</label>
                            <input type="number" name="area_sqm" class="form-control" min="0" step="0.01"
                                value="{{ $v('area_sqm', $unit?->area_sqm) }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Currency *</label>
                            <select name="currency" class="form-select" required>
                                @foreach(['RWF', 'USD', 'EUR'] as $currency)
                                <option value="{{ $currency }}"
                                    @selected($v('currency', $unit?->currency ?? 'RWF') === $currency)>
                                    {{ $currency }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Monthly rent *</label>
                            <input type="number" name="rent_amount" class="form-control" min="0" step="0.01" required
                                value="{{ $v('rent_amount', $unit?->rent_amount ?? 0) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Security deposit *</label>
                            <input type="number" name="deposit_amount" class="form-control" min="0" step="0.01" required
                                value="{{ $v('deposit_amount', $unit?->deposit_amount ?? 0) }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ $v('description', $unit?->description) }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Unit images</label>
                            <input type="file" name="images[]" class="form-control"
                                accept=".jpg,.jpeg,.png,.webp" multiple>
                            <div class="form-text">
                                JPG, PNG or WebP. Maximum 5 MB per image.
                                @if($unit) Existing images are managed from the images button on the unit. @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">{{ $unit ? 'Save changes' : 'Create unit' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach

    {{-- Image managers (buildings, floors and units) --}}
    @foreach($imageTargets as $target)
    @php
    $config = $imageRoutes[$target['type']];
    $parent = $target['parent'];
    @endphp

    <div class="modal fade" id="images-{{ $target['type'] }}-{{ $parent->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">{{ $label($target['type']) }} images</h5>
                        <div class="small pp-muted">{{ $target['title'] }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        @forelse($parent->images as $image)
                        <div class="col-6 col-md-4">
                            <div class="pp-image-card">
                                <img src="{{ asset('storage/' . $image->image_path) }}"
                                    alt="{{ $image->caption ?? $target['title'] }}"
                                    loading="lazy">

                                <div class="p-2">
                                    @if($image->is_primary)
                                    <span class="pp-pill is-orange mb-2">
                                        <i class="bi bi-star-fill"></i> Primary image
                                    </span>
                                    @endif

                                    @if($image->caption)
                                    <div class="small mb-2">{{ $image->caption }}</div>
                                    @endif

                                    <div class="d-flex flex-wrap gap-2">
                                        @if(!$image->is_primary)
                                        <form method="POST"
                                            action="{{ route($config['primary'], [
                                                              'property'        => $property->id,
                                                              $config['param']  => $parent->id,
                                                              'image'           => $image->id,
                                                          ]) }}">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="pp-btn">Set primary</button>
                                        </form>
                                        @endif

                                        <form method="POST"
                                            action="{{ route($config['delete'], [
                                                          'property'        => $property->id,
                                                          $config['param']  => $parent->id,
                                                          'image'           => $image->id,
                                                      ]) }}"
                                            onsubmit="return confirm('Delete this image permanently?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="pp-btn is-danger">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12">
                            <div class="terra-empty-state pp-empty">
                                <div class="terra-empty-icon"><i class="bi bi-images"></i></div>
                                <h6>No images uploaded yet</h6>
                                <p class="mb-0">Upload photos from the edit form for this {{ $target['type'] }}.</p>
                            </div>
                        </div>
                        @endforelse
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>


@push('styles')
<style>
/* =========================================================
   TERRA SMART RENTAL MANAGEMENT — PROPERTY DETAILS
   ========================================================= */

.terra-property-page {
    --pd-orange: var(--terra-orange, #D05208);
    --pd-navy: var(--terra-navy, #19265d);
    --pd-muted: var(--terra-muted, #697386);
    --pd-border: var(--terra-border, #e8eaf0);
    --pd-shadow: 0 8px 28px rgba(25, 38, 93, .055);
    --pd-radius: 17px;
    color: #27334a;
    padding-bottom: 32px;
    font-size: .91rem;
}

.terra-property-page * {
    box-sizing: border-box;
}

.terra-property-page .terra-page-heading {
    margin-bottom: 24px;
    padding: 8px 0 18px;
    border-bottom: 1px solid var(--pd-border);
}

.terra-property-page .terra-breadcrumb {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 9px;
    margin-bottom: 12px;
    color: var(--pd-muted);
    font-size: .78rem;
}

.terra-property-page .terra-breadcrumb a {
    color: var(--pd-muted);
    text-decoration: none;
}

.terra-property-page .terra-breadcrumb a:hover {
    color: var(--pd-orange);
}

.terra-property-page .terra-page-title {
    margin: 0 0 7px;
    color: var(--pd-navy);
    font-size: clamp(1.45rem, 2vw, 1.9rem);
    font-weight: 800;
    letter-spacing: -.045em;
}

.terra-property-page .terra-page-subtitle {
    max-width: 720px;
    margin: 0;
    color: var(--pd-muted);
    line-height: 1.65;
}

.terra-property-page .btn-terra,
.terra-property-page .btn-terra-outline,
.terra-property-page .pp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 38px;
    padding: 9px 13px;
    border-radius: 10px;
    font-size: .82rem;
    font-weight: 700;
    line-height: 1.2;
    text-decoration: none;
    transition: background .18s ease, border-color .18s ease,
                color .18s ease, transform .18s ease;
}

.terra-property-page .btn-terra {
    color: #fff;
    background: var(--pd-orange);
    border: 1px solid var(--pd-orange);
}

.terra-property-page .btn-terra:hover {
    color: #fff;
    background: #b94706;
    border-color: #b94706;
    transform: translateY(-1px);
}

.terra-property-page .btn-terra-outline,
.terra-property-page .pp-btn {
    color: var(--pd-navy);
    background: #fff;
    border: 1px solid var(--pd-border);
}

.terra-property-page .btn-terra-outline:hover,
.terra-property-page .pp-btn:hover {
    color: var(--pd-orange);
    background: #fff8f3;
    border-color: #efc5ab;
}

.terra-property-page .pp-btn.is-primary {
    color: #fff;
    background: var(--pd-orange);
    border-color: var(--pd-orange);
}

.terra-property-page .pp-btn.is-danger {
    color: #b42318;
    background: #fff7f6;
    border-color: #f3d0cc;
}

.terra-property-page .pp-danger-lg {
    min-height: 40px;
    border-radius: 10px;
    font-weight: 700;
}

/* Property hero */

.terra-property-page .pp-hero {
    position: relative;
    overflow: hidden;
    padding: clamp(20px, 3vw, 32px);
    color: #fff;
    background:
        radial-gradient(ellipse at 90% 0%, rgba(255,255,255,.12), transparent 38%),
        linear-gradient(125deg, #19265d 0%, #253d79 62%, #354c83 100%);
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 22px;
    box-shadow: 0 14px 36px rgba(25, 38, 93, .16);
}

.terra-property-page .pp-hero::before {
    position: absolute;
    right: -65px;
    bottom: -130px;
    width: 300px;
    height: 300px;
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 50%;
    content: "";
    pointer-events: none;
}

.terra-property-page .pp-code {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 10px;
    color: #fff;
    background: rgba(255,255,255,.11);
    border: 1px solid rgba(255,255,255,.17);
    border-radius: 8px;
    font-size: .76rem;
    font-weight: 700;
}

.terra-property-page .pp-hero-title {
    margin: 0 0 10px;
    color: #fff;
    font-size: clamp(1.7rem, 3vw, 2.45rem);
    font-weight: 800;
    letter-spacing: -.045em;
    overflow-wrap: anywhere;
}

.terra-property-page .pp-hero-location {
    display: flex;
    align-items: flex-start;
    gap: 3px;
    margin-bottom: 18px;
    color: rgba(255,255,255,.78);
    line-height: 1.65;
    overflow-wrap: anywhere;
}

.terra-property-page .pp-hero-chip {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 11px;
    color: rgba(255,255,255,.93);
    background: rgba(255,255,255,.09);
    border: 1px solid rgba(255,255,255,.14);
    border-radius: 9px;
    font-size: .79rem;
    font-weight: 600;
}

.terra-property-page .pp-hero-cover {
    display: block;
    width: 100%;
    height: 220px;
    object-fit: cover;
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,.16);
}

.terra-property-page .pp-hero-cover-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    color: rgba(255,255,255,.65);
    font-size: .85rem;
}

.terra-property-page .pp-hero-cover-empty i {
    font-size: 2.6rem;
}

.terra-property-page .pp-hero-meta {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr;
    gap: 20px;
    margin-top: 25px;
    padding-top: 22px;
    border-top: 1px solid rgba(255,255,255,.17);
}

.terra-property-page .pp-hero-meta-label {
    margin-bottom: 7px;
    color: rgba(255,255,255,.64);
    font-size: .73rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .055em;
}

.terra-property-page .pp-hero-meta-value {
    color: #fff;
    font-weight: 750;
    overflow-wrap: anywhere;
}

.terra-property-page .pp-hero-meta-sub {
    margin-top: 4px;
    color: rgba(255,255,255,.66);
    font-size: .78rem;
    overflow-wrap: anywhere;
}

/* Status badges */

.terra-property-page .pp-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    width: fit-content;
    max-width: 100%;
    padding: 5px 9px;
    border: 1px solid transparent;
    border-radius: 7px;
    font-size: .72rem;
    font-weight: 750;
    line-height: 1.3;
    white-space: normal;
}

.terra-property-page .pp-pill.is-success {
    color: #147847;
    background: #eaf8f0;
    border-color: #d0efdd;
}

.terra-property-page .pp-pill.is-muted {
    color: #647084;
    background: #f0f2f6;
    border-color: #e3e7ee;
}

.terra-property-page .pp-pill.is-warning {
    color: #98600b;
    background: #fff5df;
    border-color: #f6e5b8;
}

.terra-property-page .pp-pill.is-orange {
    color: #a64108;
    background: #fff0e5;
    border-color: #f6d5bf;
}

.terra-property-page .pp-pill.is-navy {
    color: var(--pd-navy);
    background: #edf0fb;
    border-color: #dce3f8;
}

.terra-property-page .pp-pill.is-info {
    color: #116a9c;
    background: #e8f5ff;
    border-color: #cce9fa;
}

/* Statistics */

.terra-property-page .terra-stat-card {
    height: 100%;
    min-height: 145px;
    padding: 19px;
    background: #fff;
    border: 1px solid var(--pd-border);
    border-radius: var(--pd-radius);
    box-shadow: var(--pd-shadow);
    transition: transform .18s ease, box-shadow .18s ease;
}

.terra-property-page .terra-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(25,38,93,.09);
}

.terra-property-page .terra-stat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 15px;
}

.terra-property-page .terra-stat-label {
    color: var(--pd-muted);
    font-size: .8rem;
    font-weight: 700;
}

.terra-property-page .terra-stat-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 39px;
    width: 39px;
    height: 39px;
    border-radius: 12px;
    font-size: 1.05rem;
}

.terra-property-page .terra-stat-icon.orange {
    color: var(--pd-orange);
    background: #fff0e6;
}

.terra-property-page .terra-stat-icon.navy {
    color: var(--pd-navy);
    background: #edf0fb;
}

.terra-property-page .terra-stat-icon.blue {
    color: #1674a9;
    background: #e8f5ff;
}

.terra-property-page .terra-stat-icon.green {
    color: #17804e;
    background: #e8f7ee;
}

.terra-property-page .terra-stat-value {
    color: var(--pd-navy);
    font-size: clamp(1.65rem, 2.5vw, 2.1rem);
    font-weight: 850;
    letter-spacing: -.045em;
    line-height: 1.15;
}

.terra-property-page .terra-stat-foot {
    margin-top: 8px;
    color: var(--pd-muted);
    font-size: .76rem;
    line-height: 1.5;
}

.terra-property-page .pp-progress {
    height: 6px;
    overflow: hidden;
    background: #edf0f5;
    border-radius: 99px;
}

.terra-property-page .pp-progress .progress-bar {
    background: linear-gradient(90deg, var(--pd-orange), #f29b54);
    border-radius: 99px;
}

/* Content cards */

.terra-property-page .terra-card {
    min-width: 0;
    overflow: hidden;
    background: #fff;
    border: 1px solid var(--pd-border);
    border-radius: var(--pd-radius);
    box-shadow: var(--pd-shadow);
}

.terra-property-page .terra-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 19px 21px;
    background: #fff;
    border-bottom: 1px solid var(--pd-border);
}

.terra-property-page .terra-card-title {
    margin: 0;
    color: var(--pd-navy);
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: -.02em;
}

.terra-property-page .terra-card-subtitle {
    margin-top: 5px;
    color: var(--pd-muted);
    font-size: .79rem;
    line-height: 1.5;
}

.terra-property-page .terra-card-body {
    padding: 21px;
}

.terra-property-page .pp-label {
    margin-bottom: 6px;
    color: var(--pd-muted);
    font-size: .74rem;
    font-weight: 750;
    letter-spacing: .015em;
}

.terra-property-page .pp-value {
    color: #28354d;
    font-size: .87rem;
    font-weight: 650;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

.terra-property-page .pp-detail {
    height: 100%;
    padding: 13px 14px;
    background: #fafbfc;
    border: 1px solid #edf0f4;
    border-radius: 11px;
}

.terra-property-page .pp-muted {
    color: var(--pd-muted) !important;
}

/* Gallery */

.terra-property-page .pp-gallery-tile {
    position: relative;
    display: block;
    width: 100%;
    padding: 0;
    overflow: hidden;
    background: #f3f5f8;
    border: 1px solid var(--pd-border);
    border-radius: 12px;
    cursor: zoom-in;
}

.terra-property-page .pp-gallery-tile img {
    display: block;
    width: 100%;
    height: 180px;
    object-fit: cover;
    transition: transform .25s ease;
}

.terra-property-page .pp-gallery-tile:hover img {
    transform: scale(1.04);
}

.terra-property-page .pp-gallery-flag {
    position: absolute;
    top: 10px;
    left: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,.1);
}

/* Building hierarchy */

.terra-property-page .pp-building {
    overflow: hidden;
    margin-top: 15px;
    background: #fff;
    border: 1px solid var(--pd-border);
    border-radius: 14px;
}

.terra-property-page .pp-building:first-of-type {
    margin-top: 0;
}

.terra-property-page .pp-building-head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    background: #fbfcfe;
}

.terra-property-page .pp-building-name {
    margin: 0;
    color: var(--pd-navy);
    font-size: .94rem;
    font-weight: 800;
    overflow-wrap: anywhere;
}

.terra-property-page .pp-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 30px;
    width: 30px;
    height: 30px;
    color: var(--pd-navy);
    background: #fff;
    border: 1px solid var(--pd-border);
    border-radius: 8px;
}

.terra-property-page .pp-toggle i {
    transition: transform .2s ease;
}

.terra-property-page .pp-toggle[aria-expanded="false"] i {
    transform: rotate(-90deg);
}

.terra-property-page .pp-thumb {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 40px;
    width: 40px;
    height: 40px;
    object-fit: cover;
    color: #8a96aa;
    background: #eef1f6;
    border: 1px solid var(--pd-border);
    border-radius: 9px;
}

.terra-property-page .pp-thumb.is-lg {
    flex-basis: 58px;
    width: 58px;
    height: 58px;
    border-radius: 11px;
    font-size: 1.2rem;
}

.terra-property-page .pp-building-body {
    border-top: 1px solid var(--pd-border);
}

.terra-property-page .pp-building-inner {
    padding: 17px;
}

.terra-property-page .pp-floor {
    margin-top: 13px;
    overflow: hidden;
    border: 1px solid var(--pd-border);
    border-radius: 12px;
}

.terra-property-page .pp-floor:first-child {
    margin-top: 0;
}

.terra-property-page .pp-floor-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 13px 15px;
    background: #f8f9fc;
    border-bottom: 1px solid var(--pd-border);
}

.terra-property-page .pp-floor-head strong {
    color: var(--pd-navy);
    font-size: .84rem;
}

.terra-property-page .pp-floor-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 31px;
    height: 31px;
    color: var(--pd-navy);
    background: #e9edf9;
    border-radius: 9px;
}

.terra-property-page .pp-floor-empty {
    padding: 18px;
    color: var(--pd-muted);
    font-size: .82rem;
    text-align: center;
}

.terra-property-page .pp-floor-empty.is-boxed {
    margin-top: 10px;
    background: #fafbfc;
    border: 1px dashed #d9dee8;
    border-radius: 10px;
}

/* Rental units — compact, readable table */

.terra-property-page .terra-table-wrap {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.terra-property-page .pp-units-table {
    min-width: 760px;
    margin: 0;
    vertical-align: middle;
    font-size: .79rem;
}

.terra-property-page .pp-units-table thead th {
    padding: 11px 13px;
    color: #65718a;
    background: #fff;
    border-bottom: 1px solid var(--pd-border);
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .035em;
    text-transform: uppercase;
    white-space: nowrap;
}

.terra-property-page .pp-units-table tbody td {
    padding: 12px 13px;
    color: #4a566c;
    border-bottom: 1px solid #edf0f4;
}

.terra-property-page .pp-units-table tbody tr:last-child td {
    border-bottom: 0;
}

.terra-property-page .pp-units-table tbody tr:hover td {
    background: #fcfcfe;
}

.terra-property-page .pp-units-table td strong {
    color: var(--pd-navy);
    white-space: nowrap;
}

.terra-property-page .pp-units-table .pp-btn {
    min-width: 34px;
    min-height: 32px;
    padding: 6px 9px;
    font-size: .75rem;
}

/* Assigned managers and record information */

.terra-property-page .pp-manager {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 0;
    border-bottom: 1px solid #edf0f4;
}

.terra-property-page .pp-manager:first-child {
    padding-top: 0;
}

.terra-property-page .pp-manager:last-of-type {
    border-bottom: 0;
}

.terra-property-page .pp-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 43px;
    width: 43px;
    height: 43px;
    color: var(--pd-navy);
    background: #edf0fb;
    border: 1px solid #dfe5f7;
    border-radius: 13px;
    font-size: .82rem;
    font-weight: 850;
}

.terra-property-page .pp-record {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 13px 0;
    border-bottom: 1px solid #edf0f4;
}

.terra-property-page .pp-record:first-child {
    padding-top: 0;
}

.terra-property-page .pp-record:last-child {
    padding-bottom: 0;
    border-bottom: 0;
}

.terra-property-page .pp-record-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 35px;
    width: 35px;
    height: 35px;
    color: var(--pd-navy);
    background: #f0f2fa;
    border-radius: 10px;
    font-size: .95rem;
}

.terra-property-page .pp-record .pp-value {
    font-size: .82rem;
}

/* Empty states */

.terra-property-page .terra-empty-state {
    padding: 30px 18px;
    color: var(--pd-muted);
    text-align: center;
}

.terra-property-page .terra-empty-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 54px;
    height: 54px;
    margin: 0 auto 14px;
    color: var(--pd-navy);
    background: #eef1fa;
    border-radius: 16px;
    font-size: 1.4rem;
}

.terra-property-page .terra-empty-state h6 {
    margin-bottom: 7px;
    color: var(--pd-navy);
    font-weight: 800;
}

.terra-property-page .terra-empty-state p {
    font-size: .82rem;
    line-height: 1.6;
}

.terra-property-page .pp-empty {
    background: #fbfcfe;
    border: 1px dashed #d9dee8;
    border-radius: 12px;
}

/* Image manager cards */

.terra-property-page .pp-image-card {
    height: 100%;
    overflow: hidden;
    background: #fff;
    border: 1px solid var(--pd-border);
    border-radius: 12px;
}

.terra-property-page .pp-image-card > img {
    display: block;
    width: 100%;
    height: 155px;
    object-fit: cover;
    background: #f1f3f7;
}

.terra-property-page .pp-image-card .pp-btn {
    min-height: 32px;
    padding: 6px 9px;
    font-size: .74rem;
}

/* Modals and form fields */

.terra-property-page .modal-content {
    overflow: hidden;
    border: 0;
    border-radius: 17px;
    box-shadow: 0 24px 70px rgba(20,30,60,.2);
}

.terra-property-page .modal-header {
    padding: 19px 22px;
    background: #fbfcfe;
    border-bottom: 1px solid var(--pd-border);
}

.terra-property-page .modal-title {
    color: var(--pd-navy);
    font-size: 1.03rem;
    font-weight: 800;
}

.terra-property-page .modal-body {
    padding: 22px;
}

.terra-property-page .modal-footer {
    padding: 15px 22px;
    background: #fbfcfe;
    border-top: 1px solid var(--pd-border);
}

.terra-property-page .form-label {
    margin-bottom: 7px;
    color: #34415a;
    font-size: .8rem;
    font-weight: 750;
}

.terra-property-page .form-control,
.terra-property-page .form-select {
    min-height: 43px;
    color: #27334a;
    border-color: #dfe4ed;
    border-radius: 9px;
    font-size: .86rem;
    box-shadow: none;
}

.terra-property-page textarea.form-control {
    min-height: 90px;
}

.terra-property-page .form-control:focus,
.terra-property-page .form-select:focus {
    border-color: var(--pd-orange);
    box-shadow: 0 0 0 .2rem rgba(208,82,8,.10);
}

.terra-property-page .form-text {
    color: var(--pd-muted);
    font-size: .76rem;
    line-height: 1.5;
}

.terra-property-page .modal-footer .btn {
    min-height: 39px;
    padding: 8px 14px;
    border-radius: 9px;
    font-size: .82rem;
    font-weight: 700;
}

.terra-property-page .modal-footer .btn-primary {
    background: var(--pd-orange);
    border-color: var(--pd-orange);
}

.terra-property-page .modal-footer .btn-primary:hover {
    background: #b94706;
    border-color: #b94706;
}

.terra-property-page .alert {
    border-radius: 10px;
    font-size: .83rem;
}

/* Sidebar quick actions */

.terra-property-page .terra-card-body.d-grid > .btn-terra,
.terra-property-page .terra-card-body.d-grid > .btn-terra-outline {
    width: 100%;
    justify-content: flex-start;
    min-height: 43px;
    padding: 11px 13px;
}

/* Responsive adjustments */

@media (max-width: 991.98px) {
    .terra-property-page .pp-hero-cover {
        height: 240px;
    }

    .terra-property-page .pp-hero-meta {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .terra-property-page .pp-hero-meta > div:first-child {
        grid-column: 1 / -1;
    }

    .terra-property-page .terra-card-header,
    .terra-property-page .terra-card-body {
        padding: 17px;
    }
}

@media (max-width: 575.98px) {
    .terra-property-page .terra-page-heading > .d-flex {
        width: 100%;
    }

    .terra-property-page .terra-page-heading > .d-flex > a,
    .terra-property-page .terra-page-heading > .d-flex > form,
    .terra-property-page .terra-page-heading > .d-flex > form button {
        flex: 1 1 auto;
    }

    .terra-property-page .pp-hero {
        padding: 19px;
        border-radius: 16px;
    }

    .terra-property-page .pp-hero-cover {
        height: 190px;
    }

    .terra-property-page .pp-hero-meta {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .terra-property-page .pp-hero-meta > div:first-child {
        grid-column: auto;
    }

    .terra-property-page .terra-stat-card {
        min-height: 135px;
        padding: 14px;
        border-radius: 13px;
    }

    .terra-property-page .terra-stat-icon {
        flex-basis: 33px;
        width: 33px;
        height: 33px;
    }

    .terra-property-page .terra-stat-value {
        font-size: 1.65rem;
    }

    .terra-property-page .terra-card-header {
        align-items: flex-start;
        flex-wrap: wrap;
        padding: 16px;
    }

    .terra-property-page .terra-card-body {
        padding: 16px;
    }

    .terra-property-page .pp-building-head {
        align-items: flex-start;
        flex-wrap: wrap;
        padding: 12px;
    }

    .terra-property-page .pp-building-head > .flex-grow-1 {
        flex: 1 1 calc(100% - 100px);
    }

    .terra-property-page .pp-building-head > .d-flex:last-child {
        width: 100%;
        padding-left: 42px;
    }

    .terra-property-page .pp-building-inner {
        padding: 10px;
    }

    .terra-property-page .pp-floor-head {
        align-items: flex-start;
        padding: 11px;
    }

    .terra-property-page .pp-floor-head > .d-flex:last-child {
        width: 100%;
    }

    .terra-property-page .pp-gallery-tile img {
        height: 135px;
    }

    .terra-property-page .modal-body,
    .terra-property-page .modal-header,
    .terra-property-page .modal-footer {
        padding: 16px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .terra-property-page *,
    .terra-property-page *::before,
    .terra-property-page *::after {
        transition-duration: .01ms !important;
        animation-duration: .01ms !important;
        scroll-behavior: auto !important;
    }
}
</style>
@endpush


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const page = document.querySelector('.terra-property-page');
        if (!page) return;

        /* ---- Unit forms: only show floors that belong to the chosen building ---- */
        page.querySelectorAll('.unit-building-select').forEach(function(buildingSelect) {
            const modal = buildingSelect.closest('.modal');
            const floorSelect = modal ? modal.querySelector('.unit-floor-select') : null;
            if (!floorSelect) return;

            function filterFloors() {
                Array.from(floorSelect.options).forEach(function(option) {
                    if (!option.value) return;

                    const matches = option.dataset.buildingId === buildingSelect.value;
                    option.hidden = !matches;
                    option.disabled = !matches;

                    if (!matches && option.selected) {
                        floorSelect.value = '';
                    }
                });
            }

            buildingSelect.addEventListener('change', filterFloors);
            filterFloors();
        });

        /* ---- Create modals: preselect building / floor from the button that opened them ---- */
        ['createFloorModal', 'createUnitModal'].forEach(function(id) {
            const modal = document.getElementById(id);
            if (!modal) return;

            modal.addEventListener('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                if (!trigger) return; // reopened after a validation error: keep the old input

                const form = modal.querySelector('form');
                if (form) form.reset();

                const buildingSelect = modal.querySelector('select[name="building_id"]');
                const floorSelect = modal.querySelector('select[name="floor_id"]');

                if (buildingSelect) {
                    if (trigger.dataset.prefillBuilding) {
                        buildingSelect.value = trigger.dataset.prefillBuilding;
                    }
                    buildingSelect.dispatchEvent(new Event('change'));
                }

                if (floorSelect && trigger.dataset.prefillFloor) {
                    floorSelect.value = trigger.dataset.prefillFloor;
                }
            });
        });

        /* ---- Expand / collapse all buildings ---- */
        page.querySelectorAll('[data-collapse-all]').forEach(function(button) {
            button.addEventListener('click', function() {
                const show = button.dataset.collapseAll === 'show';

                page.querySelectorAll('.pp-building-body').forEach(function(panel) {
                    const instance = bootstrap.Collapse.getOrCreateInstance(panel, {
                        toggle: false
                    });
                    show ? instance.show() : instance.hide();
                });
            });
        });

        /* ---- Property image preview ---- */
        const preview = document.getElementById('propertyImageModal');
        if (preview) {
            preview.addEventListener('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                if (!trigger) return;

                const image = document.getElementById('propertyImagePreview');
                image.src = trigger.dataset.imageSrc || '';
                image.alt = trigger.dataset.imageCaption || '';

                document.getElementById('propertyImageCaption').textContent =
                    trigger.dataset.imageCaption || 'Property image';
            });
        }

        /* ---- Reopen the modal that was submitted when validation failed ---- */
        @if(old('_modal'))
        const failedModal = document.getElementById(@json(old('_modal')));
        if (failedModal) {
            bootstrap.Modal.getOrCreateInstance(failedModal).show();
        }
        @endif
    });
</script>

@endsection