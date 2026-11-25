<?php

namespace App\Services\Hr;

use App\Models\Employee;
use App\Models\Property;
use App\Models\User;
use App\Services\PropertyAudience;
use Illuminate\Support\Collection;

/**
 * Who may manage which employees:
 *  - admins: everyone, including Terra's internal staff (no property);
 *  - owners / active managers: staff of their own properties only.
 */
class HrAccess
{
    public function __construct(private PropertyAudience $audience)
    {
    }

    /** Property ids the user's HR covers (null = all, for admins). */
    public function propertyIds(User $user): ?Collection
    {
        return $this->audience->accessiblePropertyIds($user);
    }

    public function authorize(User $user): void
    {
        abort_unless($this->audience->isStaff($user), 403);
    }

    /** May the user manage staff of this employer (null = Terra internal)? */
    public function canManageEmployer(User $user, ?int $propertyId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $propertyId !== null && $this->audience->canManageProperty($user, $propertyId);
    }

    public function authorizeEmployer(User $user, ?int $propertyId): void
    {
        abort_unless($this->canManageEmployer($user, $propertyId), 403);
    }

    public function authorizeEmployee(User $user, Employee $employee): void
    {
        $this->authorizeEmployer($user, $employee->property_id);
    }

    /** Employers for pickers: [value => label]; '' is Terra internal (admins only). */
    public function employers(User $user): array
    {
        $ids = $this->propertyIds($user);
        $properties = Property::when($ids !== null, fn ($q) => $q->whereIn('id', $ids))
            ->orderBy('name')->pluck('name', 'id')->all();

        return ($user->isAdmin() ? ['' => 'Terra (internal staff)'] : []) + $properties;
    }

    /**
     * Turn a filter value into a property scope: 'internal' => Terra staff,
     * an id => that property, empty => everything the user may see.
     */
    public function applyEmployerFilter($query, User $user, ?string $employer, string $column = 'property_id')
    {
        $ids = $this->propertyIds($user);

        if ($employer === 'internal' && $user->isAdmin()) {
            return $query->whereNull($column);
        }

        if ($employer !== null && $employer !== '' && ctype_digit($employer)) {
            abort_unless($this->canManageEmployer($user, (int) $employer), 403);

            return $query->where($column, (int) $employer);
        }

        return $ids === null ? $query : $query->whereIn($column, $ids);
    }
}
