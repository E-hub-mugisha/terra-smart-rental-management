{{-- CREATE BUILDING --}}
<div class="modal fade" id="createBuildingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST"
            action="{{ route('admin.properties.buildings.store', $property) }}"
            enctype="multipart/form-data"
            class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add Building</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('admin.properties._building-fields', ['building' => null])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Create Building</button>
            </div>
        </form>
    </div>
</div>

{{-- EDIT BUILDINGS --}}
@foreach($property->buildings as $building)
<div class="modal fade" id="editBuilding{{ $building->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST"
            action="{{ route('admin.properties.buildings.update', [$property, $building]) }}"
            enctype="multipart/form-data"
            class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit {{ $building->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('admin.properties._building-fields', ['building' => $building])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endforeach

{{-- CREATE FLOOR --}}
<div class="modal fade" id="createFloorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST"
            action="{{ route('admin.properties.floors.store', $property) }}"
            enctype="multipart/form-data"
            class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add Floor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('admin.properties._floor-fields', [
                'floor' => null,
                'buildings' => $property->buildings->where('status', 'active'),
                ])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Create Floor</button>
            </div>
        </form>
    </div>
</div>

{{-- EDIT FLOORS --}}
@foreach($property->buildings as $building)
@foreach($building->floors as $floor)
<div class="modal fade" id="editFloor{{ $floor->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST"
            action="{{ route('admin.properties.floors.update', [$property, $floor]) }}"
            enctype="multipart/form-data"
            class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit {{ $floor->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('admin.properties._floor-fields', [
                'floor' => $floor,
                'buildings' => $property->buildings->where('status', 'active'),
                ])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endforeach
@endforeach

{{-- CREATE UNIT --}}
<div class="modal fade" id="createUnitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="POST"
            action="{{ route('admin.properties.units.store', $property) }}"
            enctype="multipart/form-data"
            class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add Rental Unit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('admin.properties._unit-fields', [
                'unit' => null,
                'buildings' => $property->buildings->where('status', 'active'),
                'floors' => $property->buildings->where('status', 'active')->flatMap->floors,
                ])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Create Unit</button>
            </div>
        </form>
    </div>
</div>

{{-- EDIT UNITS --}}
@foreach($property->buildings as $building)
@foreach($building->units as $unit)
<div class="modal fade" id="editUnit{{ $unit->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="POST"
            action="{{ route('admin.properties.units.update', [$property, $unit]) }}"
            enctype="multipart/form-data"
            class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit Unit {{ $unit->unit_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('admin.properties._unit-fields', [
                'unit' => $unit,
                'buildings' => $property->buildings->where('status', 'active'),
                'floors' => $property->buildings->where('status', 'active')->flatMap->floors,
                ])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endforeach
@endforeach


<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.modal').forEach(function (modal) {
        const buildingSelect = modal.querySelector('.unit-building-select');
        const floorSelect = modal.querySelector('.unit-floor-select');

        if (!buildingSelect || !floorSelect) return;

        function filterFloors() {
            const buildingId = buildingSelect.value;

            Array.from(floorSelect.options).forEach(function (option) {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }

                option.hidden = option.dataset.buildingId !== buildingId;

                if (option.hidden && option.selected) {
                    floorSelect.value = '';
                }
            });
        }

        buildingSelect.addEventListener('change', filterFloors);
        filterFloors();
    });

    document.querySelectorAll('[data-building-id]').forEach(function (button) {
        button.addEventListener('click', function () {
            const modal = button.closest('.card')?.querySelector('.modal');

            // The create modals are shared across all buildings.
            // Their building selectors are preselected when opened.
            const targetId = button.getAttribute('data-bs-target');
            const target = targetId ? document.querySelector(targetId) : null;
            const buildingId = button.dataset.buildingId;

            if (!target || !buildingId) return;

            const select = target.querySelector('select[name="building_id"]');
            if (select) {
                select.value = buildingId;
                select.dispatchEvent(new Event('change'));
            }
        });
    });
});
</script>
