<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class RentalUnitController extends Controller
{
    public function index(Request $request)
    {
        $query = Unit::query()
            ->where('status', 'available')
            ->with(['building', 'floor']);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($q) use ($search) {
                $q->where('unit_number', 'like', "%{$search}%")
                    ->orWhere('unit_type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('building', function ($buildingQuery) use ($search) {
                        $buildingQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('min_rent')) {
            $query->where('rent_amount', '>=', $request->min_rent);
        }

        if ($request->filled('max_rent')) {
            $query->where('rent_amount', '<=', $request->max_rent);
        }

        if ($request->filled('bedrooms')) {
            $query->where('bedrooms', $request->bedrooms);
        }

        $units = $query->latest()
            ->paginate(12)
            ->withQueryString();

        return view('rentals.index', compact('units'));
    }

    public function show(Unit $unit)
    {
        abort_unless($unit->status === 'available', 404);

        $unit->load(['building.property', 'floor', 'images']);

        // Preserve the selected unit when the visitor authenticates.
        if (!auth()->check()) {
            session([
                'url.intended' => route('rentals.show', $unit),
                'apply_unit_id' => $unit->id,
            ]);
        }

        $tenant = auth()->check()
            ? auth()->user()->tenant
            : null;

        return view('rentals.show', compact('unit', 'tenant'));
    }
}