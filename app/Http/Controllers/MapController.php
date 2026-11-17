<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Services\PropertyAudience;
use Illuminate\Http\Request;

/**
 * GIS & location intelligence (brief §19): interactive maps with clustering,
 * search by location and map-based discovery. Coordinates come from each
 * property's latitude/longitude; boundaries and PostGIS analytics are a
 * later phase.
 */
class MapController extends Controller
{
    /** Public map of available rentals; data comes from GET /api/v1/listings. */
    public function public(Request $request)
    {
        $districts = Property::where('status', 'active')->whereNotNull('district')->distinct()->orderBy('district')->pluck('district');

        return view('maps.public', compact('districts'));
    }

    /** Owners, managers, accountants and admins: their portfolio on a map, coloured by occupancy. */
    public function portfolio(Request $request, PropertyAudience $audience)
    {
        $ids = $audience->financePropertyIds($request->user());
        abort_unless($ids === null || $ids->isNotEmpty(), 403);

        $properties = Property::when($ids !== null, fn ($q) => $q->whereIn('id', $ids))
            ->withCount([
                'units as units_total' => fn ($q) => $q->where('units.status', '!=', 'inactive'),
                'units as units_occupied' => fn ($q) => $q->where('units.status', 'occupied'),
                'units as units_available' => fn ($q) => $q->where('units.status', 'available'),
            ])
            ->orderBy('name')->get();

        $points = $properties->filter(fn ($p) => $p->latitude !== null && $p->longitude !== null)->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'location' => $p->location_line,
            'lat' => (float) $p->latitude,
            'lng' => (float) $p->longitude,
            'units' => $p->units_total,
            'occupied' => $p->units_occupied,
            'available' => $p->units_available,
            'occupancy' => $p->units_total ? round($p->units_occupied / $p->units_total * 100) : null,
            'url' => match (true) {
                $request->user()->isAdmin() => route('admin.properties.show', $p),
                $audience->canManageProperty($request->user(), $p->id) => route('property-manager.properties.show', $p),
                default => route('reports.show', ['report' => 'property_performance', 'property' => $p->id]),
            },
        ])->values();

        return view('maps.portfolio', [
            'points' => $points,
            'missing' => $properties->filter(fn ($p) => $p->latitude === null || $p->longitude === null)->values(),
        ]);
    }
}
