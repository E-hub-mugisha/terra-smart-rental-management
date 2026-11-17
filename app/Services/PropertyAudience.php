<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves "who is connected to what" for notifications and messaging,
 * so users only ever reach people inside their authorised portfolio.
 */
class PropertyAudience
{
    /** Lease statuses that make a tenant part of a property's community. */
    public const LIVE_LEASE_STATUSES = ['active', 'pending_activation', 'pending_tenant_acceptance'];

    /** Owner + active managers of a property, as user accounts. */
    public function staffUsers(Property|int|null $property): Collection
    {
        if (! $property) {
            return collect();
        }

        $property = $property instanceof Property
            ? $property->loadMissing(['owner.user', 'activeManagers.user'])
            : Property::with(['owner.user', 'activeManagers.user'])->find($property);

        if (! $property) {
            return collect();
        }

        return collect([$property->owner?->user])
            ->concat($property->activeManagers->where('status', 'active')->pluck('user'))
            ->filter()
            ->unique('id')
            ->values();
    }

    /** Property ids a user may act on (null = every property, for admins). */
    public function accessiblePropertyIds(User $user): ?Collection
    {
        if ($user->isAdmin()) {
            return null;
        }

        $ids = collect();

        if ($owner = $user->propertyOwner) {
            $ids = $ids->concat(Property::where('property_owner_id', $owner->id)->pluck('id'));
        }

        if (($manager = $user->propertyManager) && $manager->status === 'active') {
            $ids = $ids->concat(
                Property::whereHas('activeManagers', fn ($q) => $q->where('property_managers.id', $manager->id))->pluck('id')
            );
        }

        return $ids->unique()->values();
    }

    /** Live leases (with tenant.user and unit.building) on the given properties. */
    public function liveLeases(?Collection $propertyIds): Collection
    {
        return Lease::query()
            ->whereIn('status', self::LIVE_LEASE_STATUSES)
            ->when($propertyIds !== null, fn ($q) => $q->whereHas(
                'unit.building',
                fn ($b) => $b->whereIn('property_id', $propertyIds)
            ))
            ->with(['tenant.user', 'unit.building.property'])
            ->get();
    }

    /** Tenant user accounts with a live lease on a property. */
    public function tenantUsersForProperty(int $propertyId): Collection
    {
        return $this->liveLeases(collect([$propertyId]))
            ->map(fn ($lease) => $lease->tenant?->user)
            ->filter()
            ->unique('id')
            ->values();
    }

    /** Properties whose finances the user may read: staff portfolio plus accountant assignments (null = all). */
    public function financePropertyIds(User $user): ?Collection
    {
        $ids = $this->accessiblePropertyIds($user);
        if ($ids === null) {
            return null;
        }

        return $ids->concat($user->propertyRoles()->where('role', 'accountant')->pluck('property_id'))->unique()->values();
    }

    /** Admin, staff, or an accountant assigned to the property. */
    public function canViewFinance(User $user, ?int $propertyId): bool
    {
        $ids = $this->financePropertyIds($user);

        return $ids === null || ($propertyId !== null && $ids->contains($propertyId));
    }

    /** Properties an agent is assigned to. */
    public function agentPropertyIds(User $user): Collection
    {
        return $user->propertyRoles()->where('role', 'agent')->pluck('property_id');
    }

    /** Admin, or the owner / an active manager of this property. */
    public function canManageProperty(User $user, ?int $propertyId): bool
    {
        $ids = $this->accessiblePropertyIds($user);

        return $ids === null || ($propertyId !== null && $ids->contains($propertyId));
    }

    /** True for admins and for users who own or manage at least one property. */
    public function isStaff(User $user): bool
    {
        $ids = $this->accessiblePropertyIds($user);

        return $ids === null || $ids->isNotEmpty();
    }

    /** Property id of a lease (via unit → building). */
    public function propertyIdForLease(Lease $lease): ?int
    {
        return $this->propertyIdForUnit($lease->unit_id);
    }

    /** Is the user the tenant on this lease? */
    public function isTenantOf(User $user, Lease $lease): bool
    {
        $lease->loadMissing('tenant');

        return $lease->tenant?->user_id !== null && $lease->tenant->user_id === $user->id;
    }

    public function propertyIdForUnit(Unit|int|null $unit): ?int
    {
        if (! $unit) {
            return null;
        }

        $unit = $unit instanceof Unit ? $unit->loadMissing('building') : Unit::with('building')->find($unit);

        return $unit?->building?->property_id;
    }
}
