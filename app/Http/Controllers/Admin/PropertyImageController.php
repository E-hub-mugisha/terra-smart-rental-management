<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\BuildingImage;
use App\Models\Floor;
use App\Models\FloorImage;
use App\Models\Property;
use App\Models\Unit;
use App\Models\UnitImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PropertyImageController extends Controller
{
    public function setPrimaryBuilding(
        Property $property,
        Building $building,
        BuildingImage $image
    ) {
        abort_unless($building->property_id == $property->id, 404);
        abort_unless($image->building_id == $building->id, 404);

        DB::transaction(function () use ($building, $image) {
            $building->images()->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });

        return back()->with('success', 'Primary building image updated.');
    }

    public function deleteBuildingImage(
        Property $property,
        Building $building,
        BuildingImage $image
    ) {
        abort_unless($building->property_id == $property->id, 404);
        abort_unless($image->building_id == $building->id, 404);

        $this->deleteImage($image, $building);

        return back()->with('success', 'Building image deleted.');
    }

    public function setPrimaryFloor(
        Property $property,
        Floor $floor,
        FloorImage $image
    ) {
        abort_unless(
            $floor->building &&
            $floor->building->property_id == $property->id,
            404
        );
        abort_unless($image->floor_id == $floor->id, 404);

        DB::transaction(function () use ($floor, $image) {
            $floor->images()->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });

        return back()->with('success', 'Primary floor image updated.');
    }

    public function deleteFloorImage(
        Property $property,
        Floor $floor,
        FloorImage $image
    ) {
        abort_unless(
            $floor->building &&
            $floor->building->property_id == $property->id,
            404
        );
        abort_unless($image->floor_id == $floor->id, 404);

        $this->deleteImage($image, $floor);

        return back()->with('success', 'Floor image deleted.');
    }

    public function setPrimaryUnit(
        Property $property,
        Unit $unit,
        UnitImage $image
    ) {
        abort_unless(
            $unit->building &&
            $unit->building->property_id == $property->id,
            404
        );
        abort_unless($image->unit_id == $unit->id, 404);

        DB::transaction(function () use ($unit, $image) {
            $unit->images()->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });

        return back()->with('success', 'Primary unit image updated.');
    }

    public function deleteUnitImage(
        Property $property,
        Unit $unit,
        UnitImage $image
    ) {
        abort_unless(
            $unit->building &&
            $unit->building->property_id == $property->id,
            404
        );
        abort_unless($image->unit_id == $unit->id, 404);

        $this->deleteImage($image, $unit);

        return back()->with('success', 'Unit image deleted.');
    }

    private function deleteImage($image, $parent): void
    {
        $wasPrimary = $image->is_primary;
        $path = $image->image_path;

        DB::transaction(function () use ($image, $parent, $wasPrimary) {
            $image->delete();

            if ($wasPrimary) {
                $nextImage = $parent->images()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first();

                if ($nextImage) {
                    $nextImage->update(['is_primary' => true]);
                }
            }
        });

        // Delete only the stored path from the database record.
        Storage::disk('public')->delete($path);
    }
}
