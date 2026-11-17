<?php

namespace App\Http\Controllers\PropertyManager;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Property;
use App\Models\PropertyOwner;
use App\Models\PropertyManager;
use App\Models\PropertyManagerAssignment;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    /**
     * Get the authenticated user's property owner profile.
     */
    private function owner(): PropertyOwner
    {
        $owner = auth()->user()?->propertyOwner;

        abort_unless(
            $owner,
            403,
            'Your property owner profile could not be found.'
        );

        return $owner;
    }

    /**
     * Resolve the authenticated manager profile.
     */
    private function manager(): PropertyManager
    {
        $manager = auth()->user()?->propertyManager;

        abort_unless(
            $manager && $manager->status === 'active',
            403,
            'An active property manager profile is required.'
        );

        return $manager;
    }

    /**
     * Return only properties the authenticated owner or manager may access.
     *
     * Owners access properties through properties.property_owner_id.
     * Managers access properties through an active manager assignment.
     */
    private function assignedProperties(): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();

        abort_unless($user, 403, 'You must be logged in to access properties.');

        // Keep this aligned with the role values stored in the users table.
        $role = strtolower((string) $user->role);

        if (in_array($role, ['owner', 'property_owner', 'landlord'], true)) {
            $owner = $this->owner();

            return Property::query()
                ->where('property_owner_id', $owner->id);
        }

        if (in_array($role, ['manager', 'property_manager'], true)) {
            $manager = $this->manager();

            return Property::query()->whereHas(
                'activeManagers',
                fn($query) => $query->where('property_managers.id', $manager->id)
            );
        }

        abort(403, 'You are not authorized to access property management records.');
    }

    private function assignedProperty(int $propertyId): Property
    {
        return $this->assignedProperties()
            ->whereKey($propertyId)
            ->firstOrFail();
    }

    /**
     * Ensure a building belongs to this property.
     */
    private function buildingFor(Property $property, int $buildingId): Building
    {
        return $property->buildings()
            ->whereKey($buildingId)
            ->firstOrFail();
    }

    /**
     * Ensure a floor belongs to a building on this property.
     */
    private function floorFor(Property $property, int $floorId): Floor
    {
        return Floor::query()
            ->whereKey($floorId)
            ->whereHas('building', fn($query) => $query->where(
                'property_id',
                $property->id
            ))
            ->firstOrFail();
    }

    /**
     * Ensure a unit belongs to a building on this property.
     */
    private function unitFor(Property $property, int $unitId): Unit
    {
        return Unit::query()
            ->whereKey($unitId)
            ->whereHas('building', fn($query) => $query->where(
                'property_id',
                $property->id
            ))
            ->firstOrFail();
    }

    /**
     * Upload images through the model's existing images() relationship.
     *
     * The related image model is expected to accept:
     * image_path, is_primary and sort_order.
     */
    private function uploadImages(Request $request, $model, string $directory): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        foreach ($request->file('images', []) as $file) {
            $path = $file->store($directory, 'public');

            $isFirst = ! $model->images()->exists();

            $model->images()->create([
                'image_path' => $path,
                'is_primary' => $isFirst,
                'sort_order' => (int) $model->images()->max('sort_order') + 1,
            ]);
        }
    }

    /**
     * Property listing.
     */
    public function index(Request $request)
    {
        $query = $this->assignedProperties()
            ->with('owner')
            ->withCount(['buildings', 'units']);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('property_code', 'like', "%{$search}%")
                    ->orWhere('district', 'like', "%{$search}%")
                    ->orWhere('sector', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $properties = $query
            ->latest('properties.created_at')
            ->paginate(10)
            ->withQueryString();

        $statuses = (clone $this->assignedProperties())
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        return view('property-portal.properties.index', compact(
            'properties',
            'statuses'
        ));
    }

    public function create()
    {
        $owner = $this->owner();

        return view('property-portal.properties.create', [
            'property' => new Property(),
            'owner' => $owner,
        ]);
    }

    public function store(Request $request)
    {
        $owner = $this->owner();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'property_type' => [
                'required',
                'in:residential,commercial,mixed_use,industrial,other',
            ],

            'year_built' => [
                'nullable',
                'integer',
                'min:1800',
                'max:' . (date('Y') + 5),
            ],

            'status' => [
                'required',
                'in:active,inactive,under_maintenance',
            ],

            'description' => ['nullable', 'string'],

            'country' => ['required', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:150'],
            'district' => ['required', 'string', 'max:150'],
            'sector' => ['nullable', 'string', 'max:150'],
            'cell' => ['nullable', 'string', 'max:150'],
            'village' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],

            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            'upi' => ['nullable', 'string', 'max:60'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string', Rule::in(array_keys(Property::AMENITIES))],
            'utilities' => ['nullable', 'array'],
            'utilities.*' => ['string', Rule::in(array_keys(Property::UTILITIES))],
            'video_url' => ['nullable', 'url', 'max:255'],

            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $property = DB::transaction(function () use ($validated, $request, $owner) {

            $property = Property::create([
                'property_owner_id' => $owner->id,
                'property_code' => $this->generatePropertyCode(),

                'name' => $validated['name'],
                'property_type' => $validated['property_type'],
                'year_built' => $validated['year_built'] ?? null,
                'status' => $validated['status'],
                'description' => $validated['description'] ?? null,

                'country' => $validated['country'],
                'province' => $validated['province'] ?? null,
                'district' => $validated['district'],
                'sector' => $validated['sector'] ?? null,
                'cell' => $validated['cell'] ?? null,
                'village' => $validated['village'] ?? null,
                'address' => $validated['address'] ?? null,

                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,

                'upi' => $validated['upi'] ?? null,
                'amenities' => array_values(array_unique($validated['amenities'] ?? [])),
                'utilities' => array_values(array_unique($validated['utilities'] ?? [])),
                'video_url' => $validated['video_url'] ?? null,
            ]);

            // Upload property images using the correct argument order.
            $this->uploadImages(
                $request,
                $property,
                'properties'
            );

            return $property;
        });

        return redirect()
            ->route('property-manager.properties.show', $property)
            ->with('success', 'Property created successfully.');
    }

    private function generatePropertyCode(): string
    {
        do {
            $code = 'PROP-' . strtoupper(Str::random(8));
        } while (
            Property::where('property_code', $code)->exists()
        );

        return $code;
    }

    /**
     * Property details with buildings, floors, units and images.
     */
    public function show(Property $property)
    {
        // Load property relationships.
        $property->load([
            'owner',
            'activeManagers.user',
            'buildings' => fn($query) => $query
                ->with([
                    'images',
                    'floors.images',
                    'floors.units.images',
                    'units.images',
                    'units.floor',
                ])
                ->orderBy('name'),
            'images',
        ]);

        // All buildings belonging to this property.
        $buildings = $property->buildings;

        // Active buildings.
        $activeBuildings = $buildings
            ->where('status', 'active')
            ->values();

        // Available active property managers.
        $availableManagers = PropertyManager::query()
            ->where('status', 'active')
            ->with('user')
            ->orderBy('id')
            ->get();

        // Property manager assignment history.
        $managerAssignments = $property->managerAssignments()
            ->with('manager.user')
            ->latest()
            ->get();

        // Building count.
        $buildingCount = $buildings->count();

        // Floor count.
        $floorCount = $buildings->sum(fn($building) => $building->floors->count());

        // Unit count.
        $unitCount = $buildings->sum(fn($building) => $building->units->count());

        // Unit statistics.
        $allUnits = $buildings->flatMap(fn($building) => $building->units);

        $availableUnits = $allUnits->where('status', 'available')->count();

        $occupiedUnits = $allUnits->where('status', 'occupied')->count();

        $inactiveUnits = $allUnits->where('status', 'inactive')->count();

        $rentableUnits = $allUnits
            ->whereIn('status', ['available', 'occupied', 'reserved'])
            ->count();

        // Occupancy percentage.
        $occupancy = $unitCount > 0
            ? round(($occupiedUnits / $unitCount) * 100, 1)
            : 0;

        return view('property-portal.properties.show', compact(
            'property',
            'buildings',
            'activeBuildings',
            'buildingCount',
            'floorCount',
            'unitCount',
            'availableUnits',
            'occupiedUnits',
            'inactiveUnits',
            'rentableUnits',
            'occupancy',
            'availableManagers',
            'managerAssignments'
        ));
    }

    /**
     * Create a building.
     */
    public function storeBuilding(Request $request, int $property)
    {
        $property = $this->assignedProperty($property);

        $data = $request->validate([
            'building_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('buildings', 'building_code')
                    ->where('property_id', $property->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'number_of_floors' => ['required', 'integer', 'min:1', 'max:200'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'description' => ['nullable', 'string', 'max:5000'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $building = DB::transaction(function () use ($property, $data) {
            return $property->buildings()->create([
                'building_code' => $data['building_code'],
                'name' => $data['name'],
                'number_of_floors' => $data['number_of_floors'],
                'status' => $data['status'],
                'description' => $data['description'] ?? null,
            ]);
        });

        $this->uploadImages($request, $building, 'properties/buildings');

        return redirect()
            ->route('property-manager.properties.show', $property)
            ->with('success', 'Building created successfully.');
    }

    /**
     * Update a building.
     */
    public function updateBuilding(Request $request, int $property, int $building)
    {
        $property = $this->assignedProperty($property);
        $building = $this->buildingFor($property, $building);

        $data = $request->validate([
            'building_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('buildings', 'building_code')
                    ->where('property_id', $property->id)
                    ->ignore($building->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'number_of_floors' => ['required', 'integer', 'min:1', 'max:200'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'description' => ['nullable', 'string', 'max:5000'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $building->update([
            'building_code' => $data['building_code'],
            'name' => $data['name'],
            'number_of_floors' => $data['number_of_floors'],
            'status' => $data['status'],
            'description' => $data['description'] ?? null,
        ]);

        $this->uploadImages($request, $building, 'properties/buildings');

        return redirect()
            ->route('property-manager.properties.show', $property)
            ->with('success', 'Building updated successfully.');
    }

    /**
     * Create a floor.
     */
    public function storeFloor(Request $request, int $property)
    {
        $property = $this->assignedProperty($property);

        $data = $request->validate([
            'building_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'floor_number' => ['required', 'integer', 'min:-5', 'max:300'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'description' => ['nullable', 'string', 'max:5000'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $building = $this->buildingFor($property, (int) $data['building_id']);

        abort_if($building->status !== 'active', 422, 'Floors can only be added to active buildings.');

        $floor = $building->floors()->create([
            'name' => $data['name'],
            'floor_number' => $data['floor_number'],
            'status' => $data['status'],
            'description' => $data['description'] ?? null,
        ]);

        $this->uploadImages($request, $floor, 'properties/floors');

        return redirect()
            ->route('property-manager.properties.show', $property)
            ->with('success', 'Floor created successfully.');
    }

    /**
     * Update a floor.
     */
    public function updateFloor(Request $request, int $property, int $floor)
    {
        $property = $this->assignedProperty($property);
        $floor = $this->floorFor($property, $floor);

        $data = $request->validate([
            'building_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'floor_number' => ['required', 'integer', 'min:-5', 'max:300'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'description' => ['nullable', 'string', 'max:5000'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $building = $this->buildingFor($property, (int) $data['building_id']);

        $floor->update([
            'building_id' => $building->id,
            'name' => $data['name'],
            'floor_number' => $data['floor_number'],
            'status' => $data['status'],
            'description' => $data['description'] ?? null,
        ]);

        $this->uploadImages($request, $floor, 'properties/floors');

        return redirect()
            ->route('property-manager.properties.show', $property)
            ->with('success', 'Floor updated successfully.');
    }

    /**
     * Create a rental unit.
     */
    public function storeUnit(Request $request, int $property)
    {
        $property = $this->assignedProperty($property);

        $data = $request->validate([
            'building_id' => ['required', 'integer'],
            'floor_id' => ['nullable', 'integer'],
            'unit_number' => ['required', 'string', 'max:50'],
            'unit_type' => [
                'required',
                Rule::in(['apartment', 'studio', 'house', 'office', 'shop', 'warehouse', 'other']),
            ],
            'bedrooms' => ['required', 'integer', 'min:0', 'max:50'],
            'bathrooms' => ['required', 'integer', 'min:0', 'max:50'],
            'area_sqm' => ['nullable', 'numeric', 'min:0'],
            'rent_amount' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(['RWF', 'USD', 'EUR'])],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(array_keys(Unit::STATUSES))],
            'features' => ['nullable', 'string', 'max:3000'],
            'electricity_meter' => ['nullable', 'string', 'max:60'],
            'water_meter' => ['nullable', 'string', 'max:60'],
            'inventory' => ['nullable', 'string', 'max:5000'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $building = $this->buildingFor($property, (int) $data['building_id']);

        abort_if($building->status !== 'active', 422, 'Units can only be added to active buildings.');

        if (! empty($data['floor_id'])) {
            $floor = $this->floorFor($property, (int) $data['floor_id']);

            abort_unless(
                (int) $floor->building_id === (int) $building->id,
                422,
                'The selected floor does not belong to this building.'
            );

            abort_if($floor->status !== 'active', 422, 'Select an active floor.');
        }

        $duplicate = Unit::query()
            ->where('building_id', $building->id)
            ->where('unit_number', $data['unit_number'])
            ->exists();

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors(['unit_number' => 'This unit number already exists in the selected building.']);
        }

        $unit = $building->units()->create([
            'floor_id' => $data['floor_id'] ?: null,
            'unit_number' => $data['unit_number'],
            'unit_type' => $data['unit_type'],
            'bedrooms' => $data['bedrooms'],
            'bathrooms' => $data['bathrooms'],
            'area_sqm' => $data['area_sqm'] ?? null,
            'rent_amount' => $data['rent_amount'],
            'deposit_amount' => $data['deposit_amount'],
            'currency' => $data['currency'],
            'description' => $data['description'] ?? null,
            'features' => $data['features'] ?? null,
            'electricity_meter' => $data['electricity_meter'] ?? null,
            'water_meter' => $data['water_meter'] ?? null,
            'inventory' => $data['inventory'] ?? null,
            'status' => $data['status'],
        ]);

        $this->uploadImages($request, $unit, 'properties/units');

        return redirect()
            ->route('property-manager.properties.show', $property)
            ->with('success', 'Rental unit created successfully.');
    }

    /**
     * Update a rental unit.
     */
    public function updateUnit(Request $request, int $property, int $unit)
    {
        $property = $this->assignedProperty($property);
        $unit = $this->unitFor($property, $unit);

        $data = $request->validate([
            'building_id' => ['required', 'integer'],
            'floor_id' => ['nullable', 'integer'],
            'unit_number' => ['required', 'string', 'max:50'],
            'unit_type' => [
                'required',
                Rule::in(['apartment', 'studio', 'house', 'office', 'shop', 'warehouse', 'other']),
            ],
            'bedrooms' => ['required', 'integer', 'min:0', 'max:50'],
            'bathrooms' => ['required', 'integer', 'min:0', 'max:50'],
            'area_sqm' => ['nullable', 'numeric', 'min:0'],
            'rent_amount' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(['RWF', 'USD', 'EUR'])],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(array_keys(Unit::STATUSES))],
            'features' => ['nullable', 'string', 'max:3000'],
            'electricity_meter' => ['nullable', 'string', 'max:60'],
            'water_meter' => ['nullable', 'string', 'max:60'],
            'inventory' => ['nullable', 'string', 'max:5000'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $building = $this->buildingFor($property, (int) $data['building_id']);

        if (! empty($data['floor_id'])) {
            $floor = $this->floorFor($property, (int) $data['floor_id']);

            abort_unless(
                (int) $floor->building_id === (int) $building->id,
                422,
                'The selected floor does not belong to this building.'
            );
        }

        $duplicate = Unit::query()
            ->where('building_id', $building->id)
            ->where('unit_number', $data['unit_number'])
            ->whereKeyNot($unit->id)
            ->exists();

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors(['unit_number' => 'This unit number already exists in the selected building.']);
        }

        $unit->update([
            'building_id' => $building->id,
            'floor_id' => $data['floor_id'] ?: null,
            'unit_number' => $data['unit_number'],
            'unit_type' => $data['unit_type'],
            'bedrooms' => $data['bedrooms'],
            'bathrooms' => $data['bathrooms'],
            'area_sqm' => $data['area_sqm'] ?? null,
            'rent_amount' => $data['rent_amount'],
            'deposit_amount' => $data['deposit_amount'],
            'currency' => $data['currency'],
            'description' => $data['description'] ?? null,
            'features' => $data['features'] ?? null,
            'electricity_meter' => $data['electricity_meter'] ?? null,
            'water_meter' => $data['water_meter'] ?? null,
            'inventory' => $data['inventory'] ?? null,
            'status' => $data['status'],
        ]);

        $this->uploadImages($request, $unit, 'properties/units');

        return redirect()
            ->route('property-manager.properties.show', $property)
            ->with('success', 'Rental unit updated successfully.');
    }

    /**
     * Assign an active property manager to an owner's property.
     */
    public function assignManager(Request $request, int $property)
    {
        // Only a property owner can assign managers.
        $owner = $this->owner();

        // Verify that the property belongs to the authenticated owner.
        $property = Property::query()
            ->where('property_owner_id', $owner->id)
            ->whereKey($property)
            ->firstOrFail();

        $validated = $request->validate([
            'property_manager_id' => [
                'required',
                'integer',
                Rule::exists('property_managers', 'id')
                    ->where('status', 'active'),
            ],
            'start_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'property_manager_id.required' => 'Please select a property manager.',
            'property_manager_id.exists' => 'The selected manager is unavailable or inactive.',
            'start_date.required' => 'Please select an assignment start date.',
        ]);

        // Prevent duplicate active assignments.
        $alreadyAssigned = PropertyManagerAssignment::query()
            ->where('property_id', $property->id)
            ->where('property_manager_id', $validated['property_manager_id'])
            ->where('status', 'active')
            ->whereNull('end_date')
            ->exists();

        if ($alreadyAssigned) {
            return back()
                ->withInput()
                ->withErrors([
                    'property_manager_id' => 'This manager is already assigned to this property.',
                ]);
        }

        PropertyManagerAssignment::create([
            'property_id' => $property->id,
            'property_manager_id' => $validated['property_manager_id'],
            'start_date' => $validated['start_date'],
            'end_date' => null,
            'status' => 'active',
            'assigned_by' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('property-manager.properties.show', $property)
            ->with('success', 'Property manager assigned successfully.');
    }
}
