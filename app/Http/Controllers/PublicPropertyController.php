<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;

/**
 * Public, property-level listing pages (brief §7 "View Property" and §21
 * marketplace flow). Units remain individually reachable via rentals.show.
 */
class PublicPropertyController extends Controller
{
    public function index(Request $request)
    {
        $properties = Property::query()
            ->where('status', 'active')
            ->whereHas('units', fn ($q) => $q->where('units.status', '!=', 'inactive'))
            ->with(['images', 'buildings.images'])
            ->withCount([
                'units as units_listed_count' => fn ($q) => $q->where('units.status', '!=', 'inactive'),
                'units as units_available_count' => fn ($q) => $q->where('units.status', 'available'),
            ])
            ->withMin(['units as rent_from' => fn ($q) => $q->where('units.status', 'available')], 'rent_amount')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('district', 'like', "%{$search}%")
                    ->orWhere('sector', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%"));
            })
            ->when($request->filled('district'), fn ($q) => $q->where('district', $request->district))
            ->when($request->boolean('available'), fn ($q) => $q->whereHas('units', fn ($u) => $u->where('units.status', 'available')))
            ->orderByDesc('units_available_count')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $districts = Property::where('status', 'active')
            ->whereNotNull('district')
            ->distinct()
            ->orderBy('district')
            ->pluck('district');

        return view('properties.index', compact('properties', 'districts'));
    }

    public function show(Property $property)
    {
        abort_unless($property->status === 'active', 404);

        $property->load([
            'images',
            'activeManagers.user',
            'buildings' => fn ($q) => $q->where('status', 'active')
                ->orderBy('name')
                ->with([
                    'images',
                    'units' => fn ($u) => $u->where('status', '!=', 'inactive')->with(['images', 'floor']),
                ]),
        ]);

        return view('properties.show', compact('property'));
    }
}
