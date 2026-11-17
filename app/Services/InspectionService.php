<?php

namespace App\Services;

use App\Models\Inspection;
use App\Models\Lease;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InspectionService
{
    /** Lease statuses at which each inspection type can be started. */
    public const ALLOWED_LEASE_STATUSES = [
        Inspection::TYPE_MOVE_IN => ['pending_tenant_acceptance', 'pending_activation', 'active'],
        Inspection::TYPE_MOVE_OUT => ['active', 'terminated', 'expired'],
    ];

    /** Why an inspection of this type can't be started for the lease, or null if it can. */
    public function blockerFor(Lease $lease, string $type): ?string
    {
        if (! in_array($lease->status, self::ALLOWED_LEASE_STATUSES[$type] ?? [], true)) {
            return $type === Inspection::TYPE_MOVE_IN
                ? 'A move-in inspection can be started once the lease has been sent to the tenant.'
                : 'A move-out inspection can be started for active, terminated or expired leases.';
        }

        if ($lease->inspections()->where('type', $type)->exists()) {
            return 'This lease already has a ' . str_replace('_', '-', $type) . ' inspection.';
        }

        return null;
    }

    public function start(Lease $lease, string $type, User $by): Inspection
    {
        if ($reason = $this->blockerFor($lease, $type)) {
            throw ValidationException::withMessages(['type' => $reason]);
        }

        $lease->loadMissing('unit');

        return DB::transaction(function () use ($lease, $type, $by) {
            $baseline = $type === Inspection::TYPE_MOVE_OUT
                ? $lease->inspections()->where('type', Inspection::TYPE_MOVE_IN)->where('status', 'completed')->with('items')->first()
                : null;

            $inspection = Inspection::create([
                'inspection_number' => $this->number($type),
                'lease_id' => $lease->id,
                'unit_id' => $lease->unit_id,
                'type' => $type,
                'status' => 'draft',
                'baseline_inspection_id' => $baseline?->id,
                'inspection_date' => now()->toDateString(),
                'conducted_by' => $by->id,
                'currency' => $lease->currency ?: 'RWF',
                'deposit_held' => $type === Inspection::TYPE_MOVE_OUT ? $lease->deposit_amount : null,
            ]);

            if ($baseline) {
                // Same checklist as move-in so every item can be compared.
                foreach ($baseline->items as $i => $item) {
                    $inspection->items()->create([
                        'baseline_item_id' => $item->id,
                        'area' => $item->area,
                        'item' => $item->item,
                        'quantity' => $item->quantity,
                        'sort_order' => $i,
                    ]);
                }
            } else {
                foreach ($this->template($lease->unit) as $i => [$area, $item, $qty]) {
                    $inspection->items()->create(['area' => $area, 'item' => $item, 'quantity' => $qty, 'sort_order' => $i]);
                }
            }

            return $inspection;
        });
    }

    /**
     * Default checklist built from the unit: walls, doors, windows, fixtures,
     * appliances per room, then the unit's recorded inventory.
     *
     * @return array<int, array{0:string,1:string,2:?int}>
     */
    public function template(?Unit $unit): array
    {
        $rows = [];
        $add = function (string $area, array $items) use (&$rows) {
            foreach ($items as $item) {
                $rows[] = [$area, $item, null];
            }
        };

        $add('Entrance & hallway', ['Front door & locks', 'Walls', 'Floor', 'Lights & switches']);
        $add('Living room', ['Walls', 'Ceiling', 'Floor', 'Windows', 'Doors', 'Lights & sockets', 'Curtains / blinds']);
        $add('Kitchen', ['Walls', 'Floor', 'Cabinets', 'Countertop', 'Sink & taps', 'Cooker / stove', 'Fridge', 'Windows']);

        $bedrooms = max(1, (int) ($unit?->bedrooms ?? 1));
        for ($b = 1; $b <= min($bedrooms, 8); $b++) {
            $add($bedrooms > 1 ? "Bedroom {$b}" : 'Bedroom', ['Walls', 'Floor', 'Windows', 'Door', 'Wardrobe', 'Lights & sockets']);
        }

        $bathrooms = max(1, (int) ($unit?->bathrooms ?? 1));
        for ($b = 1; $b <= min($bathrooms, 6); $b++) {
            $add($bathrooms > 1 ? "Bathroom {$b}" : 'Bathroom', ['Walls & tiles', 'Floor', 'Toilet', 'Shower / bath', 'Sink & taps', 'Mirror', 'Ventilation']);
        }

        $add('Utilities & safety', ['Electricity meter', 'Water meter', 'Water heater', 'Fuse box', 'Smoke / fire safety']);

        // Unit inventory: one item per line, optional leading quantity ("2 x Chairs" or "2 Chairs").
        foreach (preg_split('/\r\n|\r|\n/', (string) $unit?->inventory) as $line) {
            $line = trim($line, " \t-•*");
            if ($line === '') {
                continue;
            }
            $qty = null;
            if (preg_match('/^(\d{1,3})\s*[xX×]?\s+(.+)$/u', $line, $m)) {
                [$qty, $line] = [(int) $m[1], $m[2]];
            }
            $rows[] = ['Inventory', Str::limit($line, 115, ''), $qty];
        }

        return $rows;
    }

    /** Recalculate deductions and refund from the item deductions. */
    public function recalculateDeposit(Inspection $inspection): void
    {
        if (! $inspection->isMoveOut()) {
            return;
        }

        $held = (float) ($inspection->deposit_held ?? $inspection->lease?->deposit_amount ?? 0);
        $deductions = (float) $inspection->items()->sum('deduction_amount');

        $inspection->update([
            'deposit_held' => $held,
            'deposit_deductions' => $deductions,
            'deposit_refund' => max(0, $held - $deductions),
        ]);
    }

    private function number(string $type): string
    {
        $prefix = $type === Inspection::TYPE_MOVE_IN ? 'MI' : 'MO';

        do {
            $number = $prefix . '-' . now()->format('ymd') . '-' . strtoupper(Str::random(5));
        } while (Inspection::where('inspection_number', $number)->exists());

        return $number;
    }
}
