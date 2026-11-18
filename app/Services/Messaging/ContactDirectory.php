<?php

namespace App\Services\Messaging;

use App\Models\User;
use App\Services\PropertyAudience;
use Illuminate\Support\Collection;

/**
 * Who a user is allowed to start a conversation with.
 *
 *  - Tenants:          owner + managers of properties where they hold a live lease, plus Terra admins.
 *  - Managers/owners:  tenants with live leases in their portfolio, co-managers/owners, plus admins.
 *  - Admins:           every active user.
 *
 * Each entry: ['user' => User, 'group' => string, 'context' => string, 'property_id' => ?int, 'unit_id' => ?int]
 */
class ContactDirectory
{
    public function __construct(private PropertyAudience $audience)
    {
    }

    public function for(User $user): Collection
    {
        $contacts = collect();

        if ($user->isAdmin()) {
            return User::where('id', '!=', $user->id)
                ->orderBy('name')
                ->get()
                ->map(fn ($u) => $this->entry($u, $this->roleGroup($u), ucfirst((string) $u->role)));
        }

        if ($user->isTenant() && $user->tenant) {
            $leases = $user->tenant->leases()
                ->whereIn('status', PropertyAudience::LIVE_LEASE_STATUSES)
                ->with('unit.building.property')
                ->get();

            foreach ($leases as $lease) {
                $property = $lease->unit?->building?->property;
                if (! $property) {
                    continue;
                }
                foreach ($this->audience->staffUsers($property) as $staff) {
                    $contacts->push($this->entry(
                        $staff,
                        'My landlord & property managers',
                        $property->name . ' · Unit ' . $lease->unit->unit_number,
                        $property->id,
                        $lease->unit_id
                    ));
                }
            }
        }

        $propertyIds = $this->audience->accessiblePropertyIds($user);
        if ($propertyIds !== null && $propertyIds->isNotEmpty()) {
            foreach ($this->audience->liveLeases($propertyIds) as $lease) {
                if ($tenantUser = $lease->tenant?->user) {
                    $property = $lease->unit->building->property;
                    $contacts->push($this->entry(
                        $tenantUser,
                        'Tenants · ' . $property->name,
                        'Unit ' . $lease->unit->unit_number,
                        $property->id,
                        $lease->unit_id
                    ));
                }
            }

            foreach ($propertyIds as $propertyId) {
                foreach ($this->audience->staffUsers($propertyId) as $staff) {
                    $contacts->push($this->entry($staff, 'Property team', 'Co-manager / owner', $propertyId));
                }
            }
        }

        // Everyone can reach Terra support.
        foreach (User::where('role', 'admin')->get() as $admin) {
            $contacts->push($this->entry($admin, 'Terra support', 'Administrator'));
        }

        return $contacts
            ->reject(fn ($c) => $c['user']->id === $user->id)
            ->unique(fn ($c) => $c['user']->id)
            ->sortBy(fn ($c) => $c['group'] . '|' . $c['user']->name)
            ->values();
    }

    public function find(User $from, int $toUserId): ?array
    {
        return $this->for($from)->first(fn ($c) => $c['user']->id === $toUserId);
    }

    /** Properties where the user may post announcements to tenants. */
    public function announceableProperties(User $user): Collection
    {
        if ($user->isTenant()) {
            return collect();
        }

        $ids = $this->audience->accessiblePropertyIds($user);

        return \App\Models\Property::query()
            ->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))
            ->where('status', '!=', 'inactive')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function entry(User $user, string $group, string $context, ?int $propertyId = null, ?int $unitId = null): array
    {
        return compact('user', 'group', 'context') + ['property_id' => $propertyId, 'unit_id' => $unitId];
    }

    private function roleGroup(User $user): string
    {
        return match ($user->role) {
            'admin' => 'Administrators',
            'manager' => 'Property managers',
            'owner' => 'Property owners',
            'tenant' => 'Tenants',
            'field_staff' => 'Field staff',
            default => 'Other users',
        };
    }
}
