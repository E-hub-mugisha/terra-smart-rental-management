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

    /**
     * Platform accounts (managers, accountants, agents, field staff) the user
     * may add as employees, keyed by user id, each with the properties they
     * work on (within the user's own portfolio) and a suggested employer.
     *
     *  - Admins: every such account. Field staff are Terra staff only.
     *  - Owners/managers: managers assigned to, and accountants/agents
     *    assigned to, their own properties.
     *
     * @return Collection<int, array{user: User, property_ids: array<int>, employer: ?int}>
     */
    public function linkableAccounts(User $actor): Collection
    {
        $scope = $this->propertyIds($actor);
        $inScope = fn (Collection $ids) => ($scope === null ? $ids : $ids->intersect($scope))->unique()->values();

        $managerProperties = \App\Models\PropertyManagerAssignment::query()
            ->where('property_manager_assignments.status', 'active')->whereNull('property_manager_assignments.end_date')
            ->join('property_managers', 'property_managers.id', '=', 'property_manager_assignments.property_manager_id')
            ->where('property_managers.status', 'active')
            ->get(['property_managers.user_id', 'property_manager_assignments.property_id'])
            ->groupBy('user_id')->map(fn ($rows) => $rows->pluck('property_id'));

        $roleProperties = \App\Models\PropertyUserRole::whereIn('role', ['accountant', 'agent'])
            ->get(['user_id', 'property_id'])
            ->groupBy('user_id')->map(fn ($rows) => $rows->pluck('property_id'));

        return User::whereIn('role', array_keys(Employee::LINKABLE_ROLES))
            ->orderBy('name')->get(['id', 'name', 'email', 'phone', 'role'])
            ->map(function (User $user) use ($managerProperties, $roleProperties, $inScope, $actor) {
                if ($user->role === 'field_staff') {
                    return $actor->isAdmin() ? ['user' => $user, 'property_ids' => [], 'employer' => null] : null;
                }

                $all = ($user->role === 'manager' ? $managerProperties : $roleProperties)->get($user->id, collect());
                $ids = $inScope($all)->all();

                if (! $actor->isAdmin() && ! $ids) {
                    return null;
                }

                return ['user' => $user, 'property_ids' => $ids, 'employer' => $ids[0] ?? null];
            })
            ->filter()
            ->keyBy(fn ($row) => $row['user']->id);
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
