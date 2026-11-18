<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Support\FileUpload;
use RuntimeException;

class PropertyStructureController extends Controller
{
    public function storeBuilding(Request $request, Property $property)
    {
        $data = $request->validate([
            'building_code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'number_of_floors' => ['required', 'integer', 'min:1', 'max:200'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $images = $data['images'] ?? [];
        unset($data['images']);

        $exists = $property->buildings()
            ->where('building_code', $data['building_code'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'building_code' => 'This building code already exists for this property.',
            ])->withInput();
        }

        try {
            $paths = FileUpload::storeMany($images, FileUpload::BUILDING_IMAGES);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['images' => $e->getMessage()]);
        }

        try {
            DB::transaction(function () use ($property, $data, $paths) {
                $building = $property->buildings()->create($data);

                foreach ($paths as $index => $path) {
                    $building->images()->create([
                        'image_path' => $path,
                        'is_primary' => $index === 0,
                        'sort_order' => $index,
                    ]);
                }

                $property->update([
                    'total_buildings' => $property->buildings()->count(),
                ]);
            });
        } catch (\Throwable $e) {
            FileUpload::deleteMany($paths);

            throw $e;
        }

        return back()->with('success', 'Building created successfully.');
    }

    public function updateBuilding(
        Request $request,
        Property $property,
        Building $building
    ) {
        abort_unless($building->property_id === $property->id, 404);

        $data = $request->validate([
            'building_code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'number_of_floors' => ['required', 'integer', 'min:1', 'max:200'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $images = $data['images'] ?? [];
        unset($data['images']);

        $exists = $property->buildings()
            ->where('building_code', $data['building_code'])
            ->where('id', '!=', $building->id)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'building_code' => 'This building code already exists for this property.',
            ])->withInput();
        }

        $building->update($data);
        $this->appendImages($building, $images);

        return back()->with('success', 'Building updated successfully.');
    }

    public function deactivateBuilding(Property $property, Building $building)
    {
        abort_unless($building->property_id === $property->id, 404);

        if ($building->units()->exists() || $building->floors()->exists()) {
            return back()->with(
                'error',
                'This building contains floors or units. Deactivate those records first.'
            );
        }

        $building->update(['status' => 'inactive']);
        $building->delete();

        $property->update([
            'total_buildings' => $property->buildings()->count(),
        ]);

        return back()->with('success', 'Building deactivated successfully.');
    }

    public function storeFloor(Request $request, Property $property)
    {
        $data = $request->validate([
            'building_id' => [
                'required',
                'integer',
                Rule::exists('buildings', 'id')->where(
                    fn ($query) => $query->where('property_id', $property->id)
                        ->where('status', 'active')
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'floor_number' => ['required', 'integer', 'min:-5', 'max:300'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $images = $data['images'] ?? [];
        unset($data['images']);

        $building = Building::where('property_id', $property->id)
            ->findOrFail($data['building_id']);

        if ($building->floors()->where('floor_number', $data['floor_number'])->exists()) {
            return back()->withErrors([
                'floor_number' => 'That floor number already exists in this building.',
            ])->withInput();
        }

        $floor = $building->floors()->create($data);
        $this->appendImages($floor, $images);

        return back()->with('success', 'Floor created successfully.');
    }

    public function updateFloor(
        Request $request,
        Property $property,
        Floor $floor
    ) {
        abort_unless(
            $floor->building()->where('property_id', $property->id)->exists(),
            404
        );

        $data = $request->validate([
            'building_id' => [
                'required',
                'integer',
                Rule::exists('buildings', 'id')->where(
                    fn ($query) => $query->where('property_id', $property->id)
                        ->where('status', 'active')
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'floor_number' => ['required', 'integer', 'min:-5', 'max:300'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $images = $data['images'] ?? [];
        unset($data['images']);

        $building = Building::where('property_id', $property->id)
            ->findOrFail($data['building_id']);

        $duplicate = $building->floors()
            ->where('floor_number', $data['floor_number'])
            ->where('id', '!=', $floor->id)
            ->exists();

        if ($duplicate) {
            return back()->withErrors([
                'floor_number' => 'That floor number already exists in this building.',
            ])->withInput();
        }

        if ($floor->units()->exists() && $floor->building_id !== $building->id) {
            return back()->withErrors([
                'building_id' => 'Move or reassign the floor units before moving this floor.',
            ])->withInput();
        }

        $floor->update($data);
        $this->appendImages($floor, $images);

        return back()->with('success', 'Floor updated successfully.');
    }

    public function deactivateFloor(Property $property, Floor $floor)
    {
        abort_unless(
            $floor->building()->where('property_id', $property->id)->exists(),
            404
        );

        if ($floor->units()->exists()) {
            return back()->with(
                'error',
                'This floor contains units. Move or deactivate those units first.'
            );
        }

        $floor->update(['status' => 'inactive']);
        $floor->delete();

        return back()->with('success', 'Floor deactivated successfully.');
    }

    public function storeUnit(Request $request, Property $property)
    {
        $data = $this->validateUnit($request, $property);

        $images = $data['images'] ?? [];
        unset($data['images']);

        $building = Building::where('property_id', $property->id)
            ->findOrFail($data['building_id']);

        if (!empty($data['floor_id'])) {
            $validFloor = $building->floors()
                ->where('id', $data['floor_id'])
                ->exists();

            if (!$validFloor) {
                return back()->withErrors([
                    'floor_id' => 'The selected floor must belong to the selected building.',
                ])->withInput();
            }
        }

        $unit = $building->units()->create($data);
        $this->appendImages($unit, $images);

        $property->update([
            'total_units' => $property->units()->count(),
        ]);

        return back()->with('success', 'Unit created successfully.');
    }

    public function updateUnit(
        Request $request,
        Property $property,
        Unit $unit
    ) {
        abort_unless(
            $unit->building()->where('property_id', $property->id)->exists(),
            404
        );

        $data = $this->validateUnit($request, $property, $unit);

        $images = $data['images'] ?? [];
        unset($data['images']);

        $building = Building::where('property_id', $property->id)
            ->findOrFail($data['building_id']);

        if (!empty($data['floor_id']) &&
            !$building->floors()->where('id', $data['floor_id'])->exists()) {
            return back()->withErrors([
                'floor_id' => 'The selected floor must belong to the selected building.',
            ])->withInput();
        }

        // Do not silently move a unit that is attached to a lease.
        if ($unit->building_id !== $building->id && $unit->leases()->exists()) {
            return back()->withErrors([
                'building_id' => 'This unit has lease history and cannot be moved to another building.',
            ])->withInput();
        }

        $duplicate = $building->units()
            ->where('unit_number', $data['unit_number'])
            ->where('id', '!=', $unit->id)
            ->exists();

        if ($duplicate) {
            return back()->withErrors([
                'unit_number' => 'This unit number already exists in the selected building.',
            ])->withInput();
        }

        $unit->update($data);
        $this->appendImages($unit, $images);

        $property->update([
            'total_units' => $property->units()->count(),
        ]);

        return back()->with('success', 'Unit updated successfully.');
    }

    public function deactivateUnit(Property $property, Unit $unit)
    {
        abort_unless(
            $unit->building()->where('property_id', $property->id)->exists(),
            404
        );

        if ($unit->leases()->exists()) {
            return back()->with(
                'error',
                'This unit has lease records. Preserve it for historical reporting instead of deactivating it here.'
            );
        }

        $unit->update(['status' => 'inactive']);
        $unit->delete();

        $property->update([
            'total_units' => $property->units()->count(),
        ]);

        return back()->with('success', 'Unit deactivated successfully.');
    }

    private function validateUnit(
        Request $request,
        Property $property,
        ?Unit $unit = null
    ): array {
        return $request->validate([
            'building_id' => [
                'required',
                'integer',
                Rule::exists('buildings', 'id')->where(
                    fn ($query) => $query->where('property_id', $property->id)
                        ->where('status', 'active')
                ),
            ],
            'floor_id' => ['nullable', 'integer', 'exists:floors,id'],
            'unit_number' => ['required', 'string', 'max:50'],
            'unit_type' => [
                'required',
                Rule::in([
                    'apartment',
                    'studio',
                    'house',
                    'office',
                    'shop',
                    'warehouse',
                    'other',
                ]),
            ],
            'bedrooms' => ['required', 'integer', 'min:0', 'max:50'],
            'bathrooms' => ['required', 'integer', 'min:0', 'max:50'],
            'area_sqm' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'rent_amount' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'deposit_amount' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'currency' => ['required', Rule::in(['RWF', 'USD', 'EUR'])],
            'status' => [
                'required',
                Rule::in(array_keys(Unit::STATUSES)),
            ],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'string', 'max:3000'],
            'electricity_meter' => ['nullable', 'string', 'max:60'],
            'water_meter' => ['nullable', 'string', 'max:60'],
            'inventory' => ['nullable', 'string', 'max:5000'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
    }

    /**
     * Moves uploaded images into public/image/{buildings|floors|units}/ and
     * links them to the record. Files are removed again if saving fails.
     */
    private function appendImages($record, array $images): void
    {
        if ($images === []) {
            return;
        }

        $directory = match (true) {
            $record instanceof \App\Models\Building => FileUpload::BUILDING_IMAGES,
            $record instanceof \App\Models\Floor => FileUpload::FLOOR_IMAGES,
            default => FileUpload::UNIT_IMAGES,
        };

        try {
            $paths = FileUpload::storeMany($images, $directory);
        } catch (RuntimeException $e) {
            throw \Illuminate\Validation\ValidationException::withMessages(['images' => $e->getMessage()]);
        }

        try {
            foreach ($paths as $path) {
                $record->images()->create([
                    'image_path' => $path,
                    'is_primary' => ! $record->images()->exists(),
                    'sort_order' => $record->images()->count(),
                ]);
            }
        } catch (\Throwable $e) {
            FileUpload::deleteMany($paths);

            throw $e;
        }
    }
}
