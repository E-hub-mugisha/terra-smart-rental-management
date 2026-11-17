@extends('layouts.app')

@section('title', $property->name . ' | Property Structure')
@section('page_heading', 'Property Details')
@section('page_subtitle', 'Manage buildings, floors and rental units in your assigned property.')

@section('content')
@php

$isPropertyOwner = auth()->user()?->propertyOwner
&& (int) $property->property_owner_id === (int) auth()->user()->propertyOwner->id;

$pillMap = [
'active' => 'success',
'available' => 'success',
'approved' => 'success',
'occupied' => 'warning',
'reserved' => 'info',
'inactive' => 'secondary',
'maintenance' => 'danger',
'under_maintenance' => 'danger',
];

$pill = fn ($value) => $pillMap[$value] ?? 'secondary';
$label = fn ($value) => ucwords(str_replace('_', ' ', (string) $value));

$money = fn ($currency, $amount) =>
($currency ?: 'RWF') . ' ' .
number_format((float) $amount, $currency === 'RWF' ? 0 : 2);

$coverImage = $property->images->firstWhere('is_primary', true)
?? $property->images->first();

$unitTypes = [
'apartment' => 'Apartment',
'studio' => 'Studio',
'house' => 'House',
'office' => 'Office',
'shop' => 'Shop',
'warehouse' => 'Warehouse',
'other' => 'Other',
];

$unitStatuses = \App\Models\Unit::STATUSES;

$buildingForms = collect([null])->concat($buildings);
$floorForms = collect([null])->concat($buildings->flatMap->floors);
$unitForms = collect([null])->concat($buildings->flatMap->units);
@endphp

<div class="pm-property-page">

    {{-- Page heading --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <a href="{{ route('property-manager.properties.index') }}"
                class="pm-back-link">
                <i class="bi bi-arrow-left me-1"></i> Assigned properties
            </a>

            <h2 class="pm-title mt-3 mb-1">{{ $property->name }}</h2>

            <div class="pm-muted">
                {{ $property->property_code ?: 'Property code not assigned' }}
                @if($property->district)
                · {{ $property->district }}
                @endif
            </div>
        </div>

        <div class="d-flex align-items-center flex-wrap gap-2">
            <span class="pm-pill bg-{{ $pill($property->status) }}">
                {{ $label($property->status ?: 'unspecified') }}
            </span>

            @if($isPropertyOwner)
            <button type="button"
                class="btn pm-btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#assignManagerModal">
                <i class="bi bi-person-plus me-1"></i>
                Assign Manager
            </button>
            @endif
        </div>
    </div>

    {{-- Property showcase (same layout as the public property page) --}}
    <section class="pm-card mb-4" style="padding:22px;">
        <x-property-showcase :property="$property" mode="admin"
            :public-url="route('listings.show', $property)"
            unit-modal-prefix="editUnit" />
    </section>

    {{-- Assigned property managers --}}
    @if($isPropertyOwner)
    <section class="pm-card mb-4">
        <div class="pm-card-header">
            <div>
                <h3>Property Managers</h3>
                <p>Manage the people responsible for this property's operations.</p>
            </div>

            <button type="button"
                class="btn pm-btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#assignManagerModal">
                <i class="bi bi-person-plus me-1"></i>
                Assign Manager
            </button>
        </div>

        <div class="pm-card-body">
            @if($managerAssignments->isEmpty())
            <div class="pm-empty pm-empty-small">
                <div class="pm-empty-icon">
                    <i class="bi bi-people"></i>
                </div>
                <h4>No managers assigned</h4>
                <p>Assign a property manager to help manage this property.</p>
            </div>
            @else
            <div class="table-responsive">
                <table class="table pm-unit-table">
                    <thead>
                        <tr>
                            <th>Manager</th>
                            <th>Email</th>
                            <th>Start date</th>
                            <th>End date</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($managerAssignments as $assignment)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="pm-manager-avatar">
                                        <i class="bi bi-person"></i>
                                    </span>

                                    <div>
                                        <strong>
                                            {{ $assignment->manager?->user?->name
                                                        ?? 'Manager #' . $assignment->property_manager_id }}
                                        </strong>

                                        <div class="pm-muted small">
                                            Manager ID: {{ $assignment->property_manager_id }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                {{ $assignment->manager?->user?->email ?? '—' }}
                            </td>

                            <td>
                                {{ $assignment->start_date?->format('d M Y') ?? '—' }}
                            </td>

                            <td>
                                {{ $assignment->end_date?->format('d M Y') ?? 'Ongoing' }}
                            </td>

                            <td>
                                <span class="pm-pill bg-{{ $assignment->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ $label($assignment->status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </section>
    @endif

    {{-- Structure summary --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="pm-stat">
                <div>
                    <div class="pm-stat-label">Available units</div>
                    <div class="pm-stat-value">{{ number_format($availableUnits) }}</div>
                </div>
                <span class="pm-stat-icon text-success bg-success-subtle">
                    <i class="bi bi-check-circle"></i>
                </span>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="pm-stat">
                <div>
                    <div class="pm-stat-label">Occupied units</div>
                    <div class="pm-stat-value">{{ number_format($occupiedUnits) }}</div>
                </div>
                <span class="pm-stat-icon text-primary bg-primary-subtle">
                    <i class="bi bi-person-check"></i>
                </span>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="pm-stat">
                <div>
                    <div class="pm-stat-label">Inactive units</div>
                    <div class="pm-stat-value">{{ number_format($inactiveUnits) }}</div>
                </div>
                <span class="pm-stat-icon text-secondary bg-secondary-subtle">
                    <i class="bi bi-pause-circle"></i>
                </span>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="pm-stat">
                <div>
                    <div class="pm-stat-label">Rentable units</div>
                    <div class="pm-stat-value">{{ number_format($rentableUnits) }}</div>
                </div>
                <span class="pm-stat-icon text-warning bg-warning-subtle">
                    <i class="bi bi-door-open"></i>
                </span>
            </div>
        </div>
    </div>

    {{-- Building / floor / unit management --}}
    <section class="pm-card">
        <div class="pm-card-header">
            <div>
                <h3>Buildings, floors and units</h3>
                <p>Manage the property structure from one workspace.</p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="button"
                    class="btn pm-btn-outline"
                    data-collapse-all="show">
                    <i class="bi bi-arrows-expand me-1"></i> Expand all
                </button>

                <button type="button"
                    class="btn pm-btn-outline"
                    data-collapse-all="hide">
                    <i class="bi bi-arrows-collapse me-1"></i> Collapse all
                </button>

                <button type="button"
                    class="btn pm-btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#createBuildingModal">
                    <i class="bi bi-plus-lg me-1"></i> Add building
                </button>
            </div>
        </div>

        <div class="pm-card-body">
            @if($buildings->isEmpty())
            <div class="pm-empty">
                <div class="pm-empty-icon"><i class="bi bi-buildings"></i></div>
                <h4>No buildings yet</h4>
                <p>Add a building to start organising floors and rental units.</p>
                <button type="button"
                    class="btn pm-btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#createBuildingModal">
                    <i class="bi bi-plus-lg me-1"></i> Add first building
                </button>
            </div>
            @else
            @foreach($buildings as $building)
            @php
            $buildingImage = $building->images->firstWhere('is_primary', true)
            ?? $building->images->first();

            $buildingUnits = $building->units;
            $buildingFloorCount = $building->floors->count();

            $groups = $building->floors->map(fn ($floor) => [
            'floor' => $floor,
            'units' => $floor->units,
            ]);

            $looseUnits = $buildingUnits->whereNull('floor_id');

            if ($looseUnits->isNotEmpty()) {
            $groups->push([
            'floor' => null,
            'units' => $looseUnits,
            ]);
            }
            @endphp

            <article class="pm-building mb-3" id="building-{{ $building->id }}">
                <div class="pm-building-header">
                    <button type="button"
                        class="pm-toggle"
                        data-bs-toggle="collapse"
                        data-bs-target="#buildingBody{{ $building->id }}"
                        aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                        <i class="bi bi-chevron-down"></i>
                    </button>

                    @if($buildingImage)
                    <img src="{{ asset('storage/' . $buildingImage->image_path) }}"
                        alt="{{ $building->name }}"
                        class="pm-thumb pm-thumb-lg">
                    @else
                    <div class="pm-thumb pm-thumb-lg">
                        <i class="bi bi-buildings"></i>
                    </div>
                    @endif

                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <h4 class="pm-building-name">{{ $building->name }}</h4>
                            <span class="pm-pill bg-{{ $pill($building->status) }}">
                                {{ $label($building->status) }}
                            </span>
                        </div>
                        <div class="pm-muted small mt-1">
                            Code {{ $building->building_code }}
                            · {{ $building->number_of_floors }} planned floors
                            · {{ $buildingFloorCount }} registered
                            · {{ $buildingUnits->count() }} units
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="button"
                            class="btn pm-btn-outline btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#editBuilding{{ $building->id }}">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </button>

                        <button type="button"
                            class="btn pm-btn-outline btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#createFloorModal"
                            data-prefill-building="{{ $building->id }}">
                            <i class="bi bi-plus-lg me-1"></i> Floor
                        </button>

                        <button type="button"
                            class="btn pm-btn-outline btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#createUnitModal"
                            data-prefill-building="{{ $building->id }}"
                            @disabled($building->status !== 'active')>
                            <i class="bi bi-plus-lg me-1"></i> Unit
                        </button>
                    </div>
                </div>

                <div id="buildingBody{{ $building->id }}"
                    class="collapse {{ $loop->first ? 'show' : '' }} pm-building-body">
                    <div class="pm-building-inner">
                        @if($groups->isEmpty())
                        <div class="pm-empty pm-empty-small">
                            No floors or units registered for this building yet.
                        </div>
                        @else
                        @foreach($groups as $group)
                        @php
                        $floor = $group['floor'];
                        $units = $group['units'];

                        $floorImage = $floor
                        ? ($floor->images->firstWhere('is_primary', true)
                        ?? $floor->images->first())
                        : null;
                        @endphp

                        <div class="pm-floor">
                            <div class="pm-floor-header">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @if($floorImage)
                                    <img src="{{ asset('storage/' . $floorImage->image_path) }}"
                                        alt="{{ $floor->name }}"
                                        class="pm-thumb">
                                    @else
                                    <span class="pm-floor-icon">
                                        <i class="bi {{ $floor ? 'bi-layers' : 'bi-door-open' }}"></i>
                                    </span>
                                    @endif

                                    <div>
                                        <strong>
                                            {{ $floor?->name ?? 'Units without a floor' }}
                                        </strong>

                                        @if($floor)
                                        <div class="pm-muted small">
                                            Floor number: {{ $floor->floor_number }}
                                        </div>
                                        @endif
                                    </div>

                                    @if($floor)
                                    <span class="pm-pill bg-{{ $pill($floor->status) }}">
                                        {{ $label($floor->status) }}
                                    </span>
                                    @endif

                                    <span class="pm-muted small">
                                        {{ $units->count() }}
                                        {{ \Illuminate\Support\Str::plural('unit', $units->count()) }}
                                    </span>
                                </div>

                                <div class="d-flex flex-wrap gap-2">
                                    @if($floor)
                                    <button type="button"
                                        class="btn pm-btn-outline btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editFloor{{ $floor->id }}">
                                        <i class="bi bi-pencil me-1"></i> Edit floor
                                    </button>
                                    @endif

                                    <button type="button"
                                        class="btn pm-btn-outline btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#createUnitModal"
                                        data-prefill-building="{{ $building->id }}"
                                        @if($floor)
                                        data-prefill-floor="{{ $floor->id }}"
                                        @endif
                                        @disabled($building->status !== 'active' || ($floor && $floor->status !== 'active'))>
                                        <i class="bi bi-plus-lg me-1"></i> Add unit
                                    </button>
                                </div>
                            </div>

                            @if($units->isEmpty())
                            <div class="pm-empty pm-empty-small">
                                No units have been added to this section.
                            </div>
                            @else
                            <div class="table-responsive">
                                <table class="table pm-unit-table">
                                    <thead>
                                        <tr>
                                            <th>Unit</th>
                                            <th>Type</th>
                                            <th>Layout</th>
                                            <th>Monthly rent</th>
                                            <th>Deposit</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
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
                                                        alt="Unit {{ $unit->unit_number }}"
                                                        class="pm-thumb">
                                                    @else
                                                    <span class="pm-thumb">
                                                        <i class="bi bi-door-closed"></i>
                                                    </span>
                                                    @endif
                                                    <div>
                                                        <strong>{{ $unit->unit_number }}</strong>
                                                        <div class="pm-muted small">
                                                            @if($unit->area_sqm)
                                                            {{ number_format((float) $unit->area_sqm, 1) }} m²
                                                            @else
                                                            Area not set
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $label($unit->unit_type) }}</td>
                                            <td>
                                                {{ $unit->bedrooms }} bed
                                                · {{ $unit->bathrooms }} bath
                                            </td>
                                            <td class="fw-bold">
                                                {{ $money($unit->currency, $unit->rent_amount) }}
                                            </td>
                                            <td>
                                                {{ $money($unit->currency, $unit->deposit_amount) }}
                                            </td>
                                            <td>
                                                <span class="pm-pill bg-{{ $pill($unit->status) }}">
                                                    {{ $label($unit->status) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <button type="button"
                                                    class="btn pm-btn-outline btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editUnit{{ $unit->id }}">
                                                    <i class="bi bi-pencil"></i>
                                                    <span class="d-none d-md-inline">Edit</span>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>
                        @endforeach
                        @endif
                    </div>
                </div>
            </article>
            @endforeach
            @endif
        </div>
    </section>
</div>

{{-- =========================================================
     BUILDING MODALS
========================================================= --}}
@foreach($buildingForms as $building)
@php
$modalId = $building ? 'editBuilding' . $building->id : 'createBuildingModal';
$v = fn ($field, $default = null) =>
old('_modal') === $modalId ? old($field, $default) : $default;
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST"
            action="{{ $building
                    ? route('property-manager.properties.buildings.update', [$property, $building])
                    : route('property-manager.properties.buildings.store', $property) }}"
            enctype="multipart/form-data"
            class="modal-content">
            @csrf
            @if($building) @method('PUT') @endif
            <input type="hidden" name="_modal" value="{{ $modalId }}">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title">
                        {{ $building ? 'Edit building' : 'Add building' }}
                    </h5>
                    <div class="pm-muted small">Building details and images</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                @if(old('_modal') === $modalId && $errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                    @endforeach
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
                            @foreach(['active' => 'Active', 'inactive' => 'Inactive'] as $value => $text)
                            <option value="{{ $value }}"
                                @selected($v('status', $building?->status ?? 'active') === $value)>
                                {{ $text }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $v('description', $building?->description) }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Add images</label>
                        <input type="file" name="images[]" class="form-control"
                            accept=".jpg,.jpeg,.png,.webp" multiple>
                        <div class="form-text">JPG, PNG or WebP. Maximum 5 MB per image.</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn pm-btn-primary">
                    {{ $building ? 'Save changes' : 'Create building' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endforeach

{{-- =========================================================
     FLOOR MODALS
========================================================= --}}
@foreach($floorForms as $floor)
@php
$modalId = $floor ? 'editFloor' . $floor->id : 'createFloorModal';
$v = fn ($field, $default = null) =>
old('_modal') === $modalId ? old($field, $default) : $default;
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST"
            action="{{ $floor
                    ? route('property-manager.properties.floors.update', [$property, $floor])
                    : route('property-manager.properties.floors.store', $property) }}"
            enctype="multipart/form-data"
            class="modal-content">
            @csrf
            @if($floor) @method('PUT') @endif
            <input type="hidden" name="_modal" value="{{ $modalId }}">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title">{{ $floor ? 'Edit floor' : 'Add floor' }}</h5>
                    <div class="pm-muted small">Floor details and images</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                @if(old('_modal') === $modalId && $errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                    @endforeach
                </div>
                @endif

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Building *</label>
                        <select name="building_id" class="form-select" required>
                            <option value="">Select building</option>
                            @foreach($activeBuildings as $optionBuilding)
                            <option value="{{ $optionBuilding->id }}"
                                @selected((string) $v('building_id', $floor?->building_id) === (string) $optionBuilding->id)>
                                {{ $optionBuilding->name }} ({{ $optionBuilding->building_code }})
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
                            @foreach(['active' => 'Active', 'inactive' => 'Inactive'] as $value => $text)
                            <option value="{{ $value }}"
                                @selected($v('status', $floor?->status ?? 'active') === $value)>
                                {{ $text }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $v('description', $floor?->description) }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Add images</label>
                        <input type="file" name="images[]" class="form-control"
                            accept=".jpg,.jpeg,.png,.webp" multiple>
                        <div class="form-text">JPG, PNG or WebP. Maximum 5 MB per image.</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn pm-btn-primary">
                    {{ $floor ? 'Save changes' : 'Create floor' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endforeach

{{-- =========================================================
     UNIT MODALS
========================================================= --}}
@foreach($unitForms as $unit)
@php
$modalId = $unit ? 'editUnit' . $unit->id : 'createUnitModal';
$v = fn ($field, $default = null) =>
old('_modal') === $modalId ? old($field, $default) : $default;
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="POST"
            action="{{ $unit
                    ? route('property-manager.properties.units.update', [$property, $unit])
                    : route('property-manager.properties.units.store', $property) }}"
            enctype="multipart/form-data"
            class="modal-content">
            @csrf
            @if($unit) @method('PUT') @endif
            <input type="hidden" name="_modal" value="{{ $modalId }}">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title">
                        {{ $unit ? 'Edit unit ' . $unit->unit_number : 'Add rental unit' }}
                    </h5>
                    <div class="pm-muted small">Unit details, rent and images</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                @if(old('_modal') === $modalId && $errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                    @endforeach
                </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Building *</label>
                        <select name="building_id"
                            class="form-select unit-building-select"
                            required>
                            <option value="">Select building</option>
                            @foreach($activeBuildings as $optionBuilding)
                            <option value="{{ $optionBuilding->id }}"
                                @selected((string) $v('building_id', $unit?->building_id) === (string) $optionBuilding->id)>
                                {{ $optionBuilding->name }} ({{ $optionBuilding->building_code }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Floor</label>
                        <select name="floor_id" class="form-select unit-floor-select">
                            <option value="">No specific floor</option>
                            @foreach($activeBuildings as $optionBuilding)
                            @foreach($optionBuilding->floors->where('status', 'active') as $optionFloor)
                            <option value="{{ $optionFloor->id }}"
                                data-building-id="{{ $optionBuilding->id }}"
                                @selected((string) $v('floor_id', $unit?->floor_id) === (string) $optionFloor->id)>
                                {{ $optionFloor->name }} — {{ $optionBuilding->name }}
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
                            @foreach($unitTypes as $value => $text)
                            <option value="{{ $value }}"
                                @selected($v('unit_type', $unit?->unit_type ?? 'apartment') === $value)>
                                {{ $text }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Availability *</label>
                        <select name="status" class="form-select" required>
                            @foreach($unitStatuses as $value => $text)
                            <option value="{{ $value }}"
                                @selected($v('status', $unit?->status ?? 'available') === $value)>
                                {{ $text }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Bedrooms *</label>
                        <input type="number" name="bedrooms" class="form-control"
                            min="0" max="50" required
                            value="{{ $v('bedrooms', $unit?->bedrooms ?? 0) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Bathrooms *</label>
                        <input type="number" name="bathrooms" class="form-control"
                            min="0" max="50" required
                            value="{{ $v('bathrooms', $unit?->bathrooms ?? 0) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Area (m²)</label>
                        <input type="number" name="area_sqm" class="form-control"
                            min="0" step="0.01"
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
                        <input type="number" name="rent_amount" class="form-control"
                            min="0" step="0.01" required
                            value="{{ $v('rent_amount', $unit?->rent_amount ?? 0) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Security deposit *</label>
                        <input type="number" name="deposit_amount" class="form-control"
                            min="0" step="0.01" required
                            value="{{ $v('deposit_amount', $unit?->deposit_amount ?? 0) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $v('description', $unit?->description) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Key features <small class="text-muted fw-normal">(one per line — shown on the property page)</small></label>
                        <textarea name="features" class="form-control" rows="3" placeholder="Spacious living room&#10;Modern kitchen&#10;Balcony with great view">{{ $v('features', implode(PHP_EOL, $unit?->features ?? [])) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Electricity meter no.</label>
                        <input name="electricity_meter" class="form-control" maxlength="60" value="{{ $v('electricity_meter', $unit?->electricity_meter) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Water meter no.</label>
                        <input name="water_meter" class="form-control" maxlength="60" value="{{ $v('water_meter', $unit?->water_meter) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Inventory <small class="text-muted fw-normal">(furniture, appliances and fittings handed over)</small></label>
                        <textarea name="inventory" class="form-control" rows="2">{{ $v('inventory', $unit?->inventory) }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Add images</label>
                        <input type="file" name="images[]" class="form-control"
                            accept=".jpg,.jpeg,.png,.webp" multiple>
                        <div class="form-text">JPG, PNG or WebP. Maximum 5 MB per image.</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn pm-btn-primary">
                    {{ $unit ? 'Save changes' : 'Create unit' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endforeach

{{-- Validation reopens the submitted modal --}}

{{-- =========================================================
     ASSIGN PROPERTY MANAGER MODAL
========================================================= --}}
@if($isPropertyOwner)
<div class="modal fade" id="assignManagerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST"
            action="{{ route('property-manager.properties.managers.assign', $property) }}"
            class="modal-content">

            @csrf

            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Assign Property Manager</h5>
                    <div class="pm-muted small">
                        Select a manager to oversee {{ $property->name }}.
                    </div>
                </div>

                <button type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                @if($errors->has('property_manager_id') || $errors->has('start_date') || $errors->has('notes'))
                <div class="alert alert-danger">
                    @foreach(['property_manager_id', 'start_date', 'notes'] as $field)
                    @foreach($errors->get($field) as $error)
                    <div>{{ $error }}</div>
                    @endforeach
                    @endforeach
                </div>
                @endif

                @if($availableManagers->isEmpty())
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    There are currently no active property managers available.
                    Activate or register a property manager before assigning one.
                </div>
                @else
                <div class="mb-3">
                    <label class="form-label">
                        Select property manager <span class="text-danger">*</span>
                    </label>

                    <select name="property_manager_id"
                        class="form-select @error('property_manager_id') is-invalid @enderror"
                        required>

                        <option value="">Choose a manager</option>

                        @foreach($availableManagers as $manager)
                        <option value="{{ $manager->id }}"
                            @selected((string) old('property_manager_id')===(string) $manager->id)>

                            {{ $manager->user?->name ?? 'Manager #' . $manager->id }}

                            @if($manager->user?->email)
                            — {{ $manager->user->email }}
                            @endif
                        </option>
                        @endforeach
                    </select>

                    @error('property_manager_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Assignment start date <span class="text-danger">*</span>
                    </label>

                    <input type="date"
                        name="start_date"
                        class="form-control @error('start_date') is-invalid @enderror"
                        value="{{ old('start_date', now()->format('Y-m-d')) }}"
                        required>

                    @error('start_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Assignment notes</label>

                    <textarea name="notes"
                        class="form-control @error('notes') is-invalid @enderror"
                        rows="4"
                        maxlength="2000"
                        placeholder="Optional responsibilities or instructions for this manager...">{{ old('notes') }}</textarea>

                    @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="pm-assignment-notice">
                    <i class="bi bi-shield-check"></i>
                    <div>
                        <strong>Manager access</strong>
                        <p>
                            The selected manager will be able to access this property
                            through their property management account.
                        </p>
                    </div>
                </div>
                @endif
            </div>

            <div class="modal-footer">
                <button type="button"
                    class="btn btn-light border"
                    data-bs-dismiss="modal">
                    Cancel
                </button>

                @if($availableManagers->isNotEmpty())
                <button type="submit" class="btn pm-btn-primary">
                    <i class="bi bi-person-check me-1"></i>
                    Assign Manager
                </button>
                @endif
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('styles')
<style>
    .pm-manager-avatar {
        width: 38px;
        height: 38px;
        display: inline-grid;
        place-items: center;
        flex-shrink: 0;
        border-radius: 10px;
        background: #fff0e8;
        color: #D05208;
        font-size: 17px;
    }

    .pm-assignment-notice {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px;
        border: 1px solid #dce9f8;
        border-radius: 10px;
        background: #f5f9ff;
        color: #19265d;
    }

    .pm-assignment-notice>i {
        font-size: 20px;
        color: #2472b8;
    }

    .pm-assignment-notice strong {
        display: block;
        font-size: 12px;
    }

    .pm-assignment-notice p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 11px;
    }

    .pm-property-page {
        --pm-orange: #D05208;
        --pm-navy: #19265d;
        --pm-border: #e7eaf1;
        --pm-muted: #7c8496;
    }

    .pm-back-link {
        color: var(--pm-orange);
        text-decoration: none;
        font-size: 12px;
        font-weight: 700;
    }

    .pm-title {
        color: var(--pm-navy);
        font-size: 25px;
        font-weight: 800;
    }

    .pm-muted {
        color: var(--pm-muted);
    }

    .pm-hero,
    .pm-card,
    .pm-stat {
        background: #fff;
        border: 1px solid var(--pm-border);
        border-radius: 15px;
        box-shadow: 0 5px 24px rgba(25, 38, 93, .04);
        overflow: hidden;
    }

    .pm-cover-image,
    .pm-cover-placeholder {
        width: 100%;
        height: 100%;
        min-height: 260px;
        max-height: 320px;
        object-fit: cover;
    }

    .pm-cover-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        color: #9aa3b5;
        background: #eef1f7;
        font-size: 13px;
    }

    .pm-cover-placeholder i {
        font-size: 45px;
    }

    .pm-hero-content {
        padding: 25px;
    }

    .pm-hero-content h3 {
        color: var(--pm-navy);
        font-size: 23px;
        font-weight: 800;
    }

    .pm-eyebrow {
        color: var(--pm-orange);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 1px;
    }

    .pm-hero-metric {
        padding: 12px;
        border: 1px solid var(--pm-border);
        border-radius: 10px;
    }

    .pm-hero-metric span {
        display: block;
        color: var(--pm-muted);
        font-size: 11px;
    }

    .pm-hero-metric strong {
        display: block;
        margin-top: 5px;
        color: var(--pm-navy);
        font-size: 21px;
        font-weight: 800;
    }

    .pm-stat {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        padding: 18px;
        height: 100%;
    }

    .pm-stat-label {
        color: var(--pm-muted);
        font-size: 11px;
    }

    .pm-stat-value {
        color: var(--pm-navy);
        font-size: 25px;
        font-weight: 800;
        margin-top: 8px;
    }

    .pm-stat-icon {
        display: grid;
        place-items: center;
        width: 40px;
        height: 40px;
        border-radius: 11px;
        font-size: 18px;
    }

    .pm-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        padding: 20px;
        border-bottom: 1px solid var(--pm-border);
    }

    .pm-card-header h3 {
        color: var(--pm-navy);
        font-size: 16px;
        font-weight: 800;
        margin: 0;
    }

    .pm-card-header p {
        color: var(--pm-muted);
        font-size: 12px;
        margin: 5px 0 0;
    }

    .pm-card-body {
        padding: 20px;
    }

    .pm-btn-primary {
        background: var(--pm-orange);
        color: #fff;
        border: 1px solid var(--pm-orange);
        border-radius: 9px;
        font-weight: 700;
        font-size: 12px;
        padding: 9px 13px;
    }

    .pm-btn-primary:hover {
        background: #b94707;
        border-color: #b94707;
        color: #fff;
    }

    .pm-btn-outline {
        border: 1px solid var(--pm-border);
        color: #4c566c;
        background: #fff;
        border-radius: 9px;
        font-size: 12px;
        font-weight: 700;
        padding: 8px 11px;
    }

    .pm-btn-outline:hover {
        color: var(--pm-orange);
        border-color: var(--pm-orange);
        background: #fff8f4;
    }

    .pm-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 20px;
        padding: 5px 9px;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .pm-pill.bg-success {
        background: #e5f6ed !important;
        color: #18794e;
    }

    .pm-pill.bg-warning {
        background: #fff3d7 !important;
        color: #946200;
    }

    .pm-pill.bg-danger {
        background: #ffebe8 !important;
        color: #b93824;
    }

    .pm-pill.bg-info {
        background: #e6f4fb !important;
        color: #176b8a;
    }

    .pm-pill.bg-secondary {
        background: #eef0f5 !important;
        color: #626b7d;
    }

    .pm-building {
        overflow: hidden;
        border: 1px solid var(--pm-border);
        border-radius: 13px;
    }

    .pm-building-header {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        padding: 15px;
        background: #fff;
    }

    .pm-building-name {
        color: var(--pm-navy);
        font-size: 14px;
        font-weight: 800;
        margin: 0;
    }

    .pm-toggle {
        width: 31px;
        height: 31px;
        flex-shrink: 0;
        border: 1px solid var(--pm-border);
        border-radius: 8px;
        background: #fff;
        color: var(--pm-navy);
    }

    .pm-toggle[aria-expanded="true"] i {
        transform: rotate(180deg);
    }

    .pm-toggle i {
        display: inline-block;
        transition: transform .2s;
    }

    .pm-thumb {
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        object-fit: cover;
        border-radius: 9px;
        background: #eef1f7;
        color: #8a96aa;
        border: 1px solid var(--pm-border);
    }

    .pm-thumb-lg {
        width: 54px;
        height: 54px;
        flex-basis: 54px;
        border-radius: 11px;
        font-size: 20px;
    }

    .pm-building-body {
        border-top: 1px solid var(--pm-border);
    }

    .pm-building-inner {
        padding: 15px;
        background: #fcfcfe;
    }

    .pm-floor {
        margin-top: 12px;
        border: 1px solid var(--pm-border);
        border-radius: 11px;
        overflow: hidden;
        background: #fff;
    }

    .pm-floor:first-child {
        margin-top: 0;
    }

    .pm-floor-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        padding: 12px;
        background: #f8f9fc;
        border-bottom: 1px solid var(--pm-border);
    }

    .pm-floor-header strong {
        color: var(--pm-navy);
        font-size: 12px;
    }

    .pm-floor-icon {
        width: 34px;
        height: 34px;
        display: inline-grid;
        place-items: center;
        border-radius: 9px;
        color: var(--pm-navy);
        background: #e9edf9;
    }

    .pm-unit-table {
        min-width: 760px;
        margin: 0;
        vertical-align: middle;
        font-size: 12px;
    }

    .pm-unit-table thead th {
        padding: 11px 13px;
        color: #65718a;
        background: #fff;
        border-bottom: 1px solid var(--pm-border);
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .pm-unit-table tbody td {
        padding: 11px 13px;
        color: #4a566c;
        border-bottom: 1px solid #edf0f4;
        vertical-align: middle;
    }

    .pm-unit-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .pm-unit-table td strong {
        color: var(--pm-navy);
    }

    .pm-empty {
        padding: 35px 15px;
        text-align: center;
    }

    .pm-empty-small {
        padding: 20px 12px;
        color: var(--pm-muted);
        font-size: 12px;
    }

    .pm-empty-icon {
        width: 54px;
        height: 54px;
        display: grid;
        place-items: center;
        margin: 0 auto 13px;
        border-radius: 15px;
        color: var(--pm-orange);
        background: #fff0e8;
        font-size: 25px;
    }

    .pm-empty h4 {
        color: var(--pm-navy);
        font-size: 15px;
        font-weight: 800;
    }

    .pm-empty p {
        color: var(--pm-muted);
        font-size: 12px;
    }

    .pm-property-page .modal-content {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
    }

    .pm-property-page .modal-header,
    .pm-property-page .modal-footer {
        padding: 18px 22px;
        border-color: var(--pm-border);
    }

    .pm-property-page .modal-body {
        padding: 22px;
    }

    .pm-property-page .modal-title {
        color: var(--pm-navy);
        font-size: 16px;
        font-weight: 800;
    }

    .pm-property-page .form-label {
        color: #3c465b;
        font-size: 12px;
        font-weight: 700;
    }

    .pm-property-page .form-control,
    .pm-property-page .form-select {
        min-height: 42px;
        border-color: #dfe3ec;
        border-radius: 8px;
        font-size: 13px;
    }

    .pm-property-page .form-control:focus,
    .pm-property-page .form-select:focus {
        border-color: var(--pm-orange);
        box-shadow: 0 0 0 .2rem rgba(208, 82, 8, .1);
    }

    @media (max-width: 767.98px) {
        .pm-hero-content {
            padding: 18px;
        }

        .pm-cover-image,
        .pm-cover-placeholder {
            min-height: 190px;
            height: 190px;
        }

        .pm-card-header,
        .pm-card-body {
            padding: 15px;
        }

        .pm-building-header {
            align-items: flex-start;
        }

        .pm-building-header>.d-flex:last-child {
            width: 100%;
            padding-left: 42px;
        }

        .pm-floor-header>.d-flex:last-child {
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const page = document.querySelector('.pm-property-page');
        if (!page) return;

        // Filter available floors based on the selected building.
        page.querySelectorAll('.unit-building-select').forEach(function(buildingSelect) {
            const modal = buildingSelect.closest('.modal');
            const floorSelect = modal?.querySelector('.unit-floor-select');
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

        // Preselect building and floor when opening a create modal from a building/floor.
        ['createFloorModal', 'createUnitModal'].forEach(function(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;

            modal.addEventListener('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                if (!trigger) return;

                const form = modal.querySelector('form');
                if (form) form.reset();

                const buildingSelect = modal.querySelector('select[name="building_id"]');
                const floorSelect = modal.querySelector('select[name="floor_id"]');

                if (buildingSelect) {
                    buildingSelect.value = trigger.dataset.prefillBuilding || '';
                    buildingSelect.dispatchEvent(new Event('change'));
                }

                if (floorSelect) {
                    floorSelect.value = trigger.dataset.prefillFloor || '';
                }
            });
        });

        // Expand or collapse all buildings.
        page.querySelectorAll('[data-collapse-all]').forEach(function(button) {
            button.addEventListener('click', function() {
                const expand = button.dataset.collapseAll === 'show';

                page.querySelectorAll('.pm-building-body').forEach(function(panel) {
                    const collapse = bootstrap.Collapse.getOrCreateInstance(panel, {
                        toggle: false
                    });

                    expand ? collapse.show() : collapse.hide();
                });
            });
        });

        // Reopen the form modal when validation fails.
        @if(old('_modal'))
        const failedModal = document.getElementById(@json(old('_modal')));
        if (failedModal) {
            bootstrap.Modal.getOrCreateInstance(failedModal).show();
        }
        @endif
    });
</script>
@endpush