
{{-- Success and error messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Please correct the following errors:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card border-0 shadow-sm mb-4">
    {{-- Section header --}}
    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="fw-bold mb-1">Buildings, Floors & Units</h5>
            <small class="text-muted">
                Manage the property's physical structure, images and rental availability.
            </small>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="button"
                    class="btn btn-outline-primary btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#createBuildingModal">
                + Building
            </button>

            <button type="button"
                    class="btn btn-outline-primary btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#createFloorModal"
                    @disabled($property->buildings->where('status', 'active')->isEmpty())>
                + Floor
            </button>

            <button type="button"
                    class="btn btn-primary btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#createUnitModal"
                    @disabled($property->buildings->where('status', 'active')->isEmpty())>
                + Rental Unit
            </button>
        </div>
    </div>

    <div class="card-body">
        {{-- Structure statistics --}}
        <div class="row g-3 mb-4">
            <div class="col-4">
                <div class="rounded border p-3 h-100">
                    <small class="text-muted d-block">Buildings</small>
                    <h4 class="fw-bold mb-0">
                        {{ $property->buildings->count() }}
                    </h4>
                </div>
            </div>

            <div class="col-4">
                <div class="rounded border p-3 h-100">
                    <small class="text-muted d-block">Floors</small>
                    <h4 class="fw-bold mb-0">
                        {{ $property->buildings->sum(fn ($building) => $building->floors->count()) }}
                    </h4>
                </div>
            </div>

            <div class="col-4">
                <div class="rounded border p-3 h-100">
                    <small class="text-muted d-block">Units</small>
                    <h4 class="fw-bold mb-0">
                        {{ $property->buildings->sum(fn ($building) => $building->units->count()) }}
                    </h4>
                </div>
            </div>
        </div>

        {{-- Buildings --}}
        @forelse($property->buildings as $building)
            <div class="border rounded mb-4 overflow-hidden">

                {{-- Building header --}}
                <div class="p-3 bg-light d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div class="d-flex gap-3">
                        @php
                            $primaryBuildingImage = $building->images
                                ->firstWhere('is_primary', true)
                                ?? $building->images->first();
                        @endphp

                        @if($primaryBuildingImage)
                            <img
                                src="{{ upload_url($primaryBuildingImage->image_path) }}"
                                alt="{{ $building->name }}"
                                class="rounded border"
                                style="width:90px;height:75px;object-fit:cover;">
                        @else
                            <div class="rounded border bg-white d-flex align-items-center justify-content-center"
                                 style="width:90px;height:75px;">
                                <span class="small text-muted">No image</span>
                            </div>
                        @endif

                        <div>
                            <h6 class="fw-bold mb-1">{{ $building->name }}</h6>

                            <small class="text-muted d-block">
                                Code: {{ $building->building_code }}
                            </small>

                            <small class="text-muted d-block">
                                {{ $building->number_of_floors }} planned floors
                                · {{ $building->units->count() }} units
                            </small>

                            <span class="badge bg-{{ $building->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($building->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button"
                                class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#editBuilding{{ $building->id }}">
                            Edit
                        </button>

                        @if($building->status === 'active')
                            <form method="POST"
                                  action="{{ route('admin.properties.buildings.destroy', [$property, $building]) }}"
                                  onsubmit="return confirm('Deactivate this building?')">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    Deactivate
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="p-3">
                    {{-- Building image management --}}
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3">
                            <i class="bi bi-images me-1"></i>
                            Building images
                        </h6>

                        @include('admin.properties._image-gallery', [
                            'property' => $property,
                            'parent' => $building,
                            'images' => $building->images,
                            'imageType' => 'building',
                        ])
                    </div>

                    <hr>

                    {{-- Floors --}}
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-semibold mb-0">Floors</h6>

                        @if($building->status === 'active')
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#createFloorModal"
                                    data-building-id="{{ $building->id }}">
                                + Add Floor
                            </button>
                        @endif
                    </div>

                    @forelse($building->floors as $floor)
                        <div class="border rounded p-3 mb-3">

                            {{-- Floor header --}}
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <strong>{{ $floor->name }}</strong>

                                    <span class="text-muted">
                                        (Floor {{ $floor->floor_number }})
                                    </span>

                                    <span class="badge bg-{{ $floor->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($floor->status) }}
                                    </span>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editFloor{{ $floor->id }}">
                                        Edit
                                    </button>

                                    @if($floor->status === 'active')
                                        <form method="POST"
                                              action="{{ route('admin.properties.floors.destroy', [$property, $floor]) }}"
                                              onsubmit="return confirm('Deactivate this floor?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Deactivate
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            {{-- Floor image management --}}
                            <div class="mt-3">
                                <h6 class="small fw-semibold mb-2">Floor images</h6>

                                @include('admin.properties._image-gallery', [
                                    'property' => $property,
                                    'parent' => $floor,
                                    'images' => $floor->images,
                                    'imageType' => 'floor',
                                ])
                            </div>

                            <hr>

                            {{-- Units assigned to this floor --}}
                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted fw-semibold">
                                        Units on this floor
                                    </small>

                                    @if($building->status === 'active' && $floor->status === 'active')
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#createUnitModal"
                                                data-building-id="{{ $building->id }}"
                                                data-floor-id="{{ $floor->id }}">
                                            + Add Unit
                                        </button>
                                    @endif
                                </div>

                                @forelse($floor->units as $unit)
                                    @include('admin.properties._unit-row', [
                                        'unit' => $unit,
                                        'property' => $property,
                                        'building' => $building,
                                        'floor' => $floor,
                                    ])

                                    {{-- Unit image management --}}
                                    <div class="ms-2 mb-3 p-3 bg-light rounded">
                                        <h6 class="small fw-semibold mb-2">
                                            Unit {{ $unit->unit_number }} images
                                        </h6>

                                        @include('admin.properties._image-gallery', [
                                            'property' => $property,
                                            'parent' => $unit,
                                            'images' => $unit->images,
                                            'imageType' => 'unit',
                                        ])
                                    </div>
                                @empty
                                    <p class="small text-muted mb-0">
                                        No units on this floor.
                                    </p>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small">
                            No floors registered for this building.
                        </p>
                    @endforelse

                    {{-- Units without a floor --}}
                    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
                        <h6 class="fw-semibold mb-0">Units without a floor</h6>

                        @if($building->status === 'active')
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#createUnitModal"
                                    data-building-id="{{ $building->id }}">
                                + Add Unit
                            </button>
                        @endif
                    </div>

                    @forelse($building->units->whereNull('floor_id') as $unit)
                        @include('admin.properties._unit-row', [
                            'unit' => $unit,
                            'property' => $property,
                            'building' => $building,
                            'floor' => null,
                        ])

                        {{-- Unit image management --}}
                        <div class="ms-2 mb-3 p-3 bg-light rounded">
                            <h6 class="small fw-semibold mb-2">
                                Unit {{ $unit->unit_number }} images
                            </h6>

                            @include('admin.properties._image-gallery', [
                                'property' => $property,
                                'parent' => $unit,
                                'images' => $unit->images,
                                'imageType' => 'unit',
                            ])
                        </div>
                    @empty
                        <p class="small text-muted">
                            No units without a floor.
                        </p>
                    @endforelse
                </div>
            </div>
        @empty
            {{-- Empty state --}}
            <div class="text-center py-5">
                <i class="bi bi-buildings display-5 text-muted"></i>

                <h6 class="mt-3">No buildings yet</h6>

                <p class="text-muted">
                    Add a building to start organizing floors and rental units.
                </p>

                <button type="button"
                        class="btn btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#createBuildingModal">
                    Add First Building
                </button>
            </div>
        @endforelse
    </div>
</div>

{{-- Existing CRUD modals --}}
@include('admin.properties._structure-modals')
