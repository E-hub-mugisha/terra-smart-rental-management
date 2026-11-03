<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\PropertyManager;
use App\Models\PropertyManagerAssignment;
use App\Models\PropertyOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $properties = Property::with(['owner', 'activeManagers'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('property_code', 'like', "%{$search}%")
                        ->orWhere('district', 'like', "%{$search}%")
                        ->orWhereHas('owner', function ($ownerQuery) use ($search) {
                            $ownerQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(
                $request->filled('status'),
                fn($q) =>
                $q->where('status', $request->status)
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.properties.index', compact('properties'));
    }

    public function create()
    {
        $owners = PropertyOwner::where('status', 'active')
            ->orderBy('name')
            ->get();

        $managers = PropertyManager::where('status', 'active')
            ->whereHas('user')
            ->with('user')
            ->get();

        return view('admin.properties.create', compact('owners', 'managers'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateProperty($request);

        $images = $validated['images'] ?? [];
        unset($validated['images']);

        $managerIds = $validated['manager_ids'] ?? [];
        unset($validated['manager_ids']);

        $property = DB::transaction(function () use (
            $validated,
            $managerIds,
            $images
        ) {
            $validated['property_code'] = $this->generatePropertyCode();
            $validated['created_by'] = auth()->id();

            $property = Property::create($validated);

            $this->syncActiveManagers($property, $managerIds);

            foreach ($images as $index => $image) {
                $path = $image->store('properties', 'public');

                $property->images()->create([
                    'image_path' => $path,
                    'is_primary' => $index === 0,
                    'sort_order' => $index,
                ]);
            }

            return $property;
        });

        return redirect()
            ->route('admin.properties.show', $property)
            ->with('success', 'Property created successfully.');
    }

    public function show(Property $property)
    {
        $property->load([
            'owner',
            'activeManagers.user',
            'buildings' => fn($query) => $query
                ->with([
                    'images',
                    'floors.images',
                    'units.images',
                    'units.floor',
                ])
                ->orderBy('name'),
            'images',
        ]);

        return view('admin.properties.show', compact('property'));
    }

    public function edit(Property $property)
    {
        $owners = PropertyOwner::where(function ($query) use ($property) {
            $query->where('status', 'active')
                ->orWhere('id', $property->property_owner_id);
        })->orderBy('name')->get();

        $managers = PropertyManager::where('status', 'active')
            ->whereHas('user')
            ->with('user')
            ->get();

        $assignedManagerIds = $property->activeManagers()
            ->pluck('property_managers.id')
            ->all();

        return view('admin.properties.edit', compact(
            'property',
            'owners',
            'managers',
            'assignedManagerIds'
        ));
    }

    public function update(Request $request, Property $property)
    {
        $validated = $this->validateProperty($request, $property);

        $images = $validated['images'] ?? [];
        unset($validated['images']);

        $managerIds = $validated['manager_ids'] ?? [];
        unset($validated['manager_ids']);

        DB::transaction(function () use (
            $property,
            $validated,
            $managerIds,
            $images
        ) {
            $property->update($validated);

            $this->syncActiveManagers($property, $managerIds);

            foreach ($images as $image) {
                $path = $image->store('properties', 'public');

                $property->images()->create([
                    'image_path' => $path,
                    'is_primary' => !$property->images()->exists(),
                    'sort_order' => $property->images()->count(),
                ]);
            }
        });

        return redirect()
            ->route('admin.properties.show', $property)
            ->with('success', 'Property updated successfully.');
    }

    public function destroy(Property $property)
    {
        // Preserve property records for historical reporting.
        $property->update(['status' => 'inactive']);

        return redirect()
            ->route('admin.properties.index')
            ->with('success', 'Property deactivated successfully.');
    }

    private function validateProperty(
        Request $request,
        ?Property $property = null
    ): array {
        return $request->validate([
            'property_owner_id' => [
                'required',
                Rule::exists('property_owners', 'id')
                    ->where(fn($query) => $query->where('status', 'active')
                        ->orWhere('id', $property?->property_owner_id ?? 0)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'property_type' => [
                'required',
                Rule::in([
                    'residential',
                    'commercial',
                    'mixed_use',
                    'industrial',
                    'other',
                ]),
            ],
            'description' => ['nullable', 'string'],
            'country' => ['required', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'sector' => ['nullable', 'string', 'max:100'],
            'cell' => ['nullable', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'year_built' => ['nullable', 'integer', 'min:1800', 'max:' . (date('Y') + 5)],
            'status' => [
                'required',
                Rule::in(['active', 'inactive', 'under_maintenance']),
            ],
            'manager_ids' => ['nullable', 'array'],
            'manager_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('property_managers', 'id')
                    ->where(fn($query) => $query->where('status', 'active')),
            ],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);
    }

    private function syncActiveManagers(
        Property $property,
        array $managerIds
    ): void {
        $managerIds = array_values(array_unique(array_map(
            'intval',
            $managerIds
        )));

        $activeAssignments = PropertyManagerAssignment::where(
            'property_id',
            $property->id
        )->where('status', 'active')->get();

        foreach ($activeAssignments as $assignment) {
            if (!in_array((int) $assignment->property_manager_id, $managerIds, true)) {
                $assignment->update([
                    'status' => 'ended',
                    'end_date' => now()->toDateString(),
                ]);
            }
        }

        foreach ($managerIds as $managerId) {
            $alreadyActive = PropertyManagerAssignment::where('property_id', $property->id)
                ->where('property_manager_id', $managerId)
                ->where('status', 'active')
                ->whereNull('end_date')
                ->exists();

            if (!$alreadyActive) {
                PropertyManagerAssignment::create([
                    'property_id' => $property->id,
                    'property_manager_id' => $managerId,
                    'start_date' => now()->toDateString(),
                    'status' => 'active',
                    'assigned_by' => auth()->id(),
                ]);
            }
        }
    }

    private function generatePropertyCode(): string
    {
        do {
            $code = 'PROP-' . strtoupper(Str::random(8));
        } while (Property::where('property_code', $code)->exists());

        return $code;
    }
}
