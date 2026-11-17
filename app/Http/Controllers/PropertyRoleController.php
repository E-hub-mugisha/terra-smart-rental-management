<?php

namespace App\Http\Controllers;

use App\Models\AgentCommission;
use App\Models\Lease;
use App\Models\Property;
use App\Models\PropertyUserRole;
use App\Models\User;
use App\Services\PropertyAudience;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Accountants and agents per property, and agent commissions.
 * Admins assign people to properties; property staff record and pay
 * commissions for agents working on their properties.
 */
class PropertyRoleController extends Controller
{
    public function __construct(private PropertyAudience $audience)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $ids = $this->audience->accessiblePropertyIds($user);
        abort_unless($ids === null || $ids->isNotEmpty(), 403);

        return view('property-roles.index', [
            'isAdmin' => $user->isAdmin(),
            'assignments' => PropertyUserRole::with(['property:id,name', 'user:id,name,email,role'])
                ->when($ids !== null, fn ($q) => $q->whereIn('property_id', $ids))->latest()->get(),
            'commissions' => AgentCommission::with(['agent:id,name', 'property:id,name', 'lease:id,lease_number'])
                ->when($ids !== null, fn ($q) => $q->whereIn('property_id', $ids))->latest()->paginate(20),
            'properties' => Property::when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->orderBy('name')->get(['id', 'name']),
            'people' => User::whereIn('role', ['accountant', 'agent'])->orderBy('name')->get(['id', 'name', 'role']),
            'agents' => PropertyUserRole::with('user:id,name')->where('role', 'agent')
                ->when($ids !== null, fn ($q) => $q->whereIn('property_id', $ids))->get(),
            'leases' => Lease::when($ids !== null, fn ($q) => $q->whereHas('unit.building', fn ($b) => $b->whereIn('property_id', $ids)))
                ->whereIn('status', ['active', 'pending_activation', 'pending_tenant_acceptance'])->with('tenant:id,full_name')->latest('id')->limit(200)->get(['id', 'lease_number', 'tenant_id', 'monthly_rent']),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->whereIn('role', ['accountant', 'agent'])],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
        $person = User::findOrFail($data['user_id']);

        PropertyUserRole::updateOrCreate(
            ['property_id' => $data['property_id'], 'user_id' => $person->id, 'role' => $person->role],
            ['commission_rate' => $person->role === 'agent' ? ($data['commission_rate'] ?? null) : null, 'assigned_by' => $request->user()->id]
        );

        return back()->with('success', $person->name . ' assigned.');
    }

    public function destroy(Request $request, PropertyUserRole $propertyRole)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $propertyRole->delete();

        return back()->with('success', 'Assignment removed.');
    }

    public function storeCommission(Request $request)
    {
        $data = $request->validate([
            'agent_role_id' => ['required', 'integer', 'exists:property_user_roles,id'],
            'lease_id' => ['nullable', 'integer', 'exists:leases,id'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $role = PropertyUserRole::where('role', 'agent')->findOrFail($data['agent_role_id']);
        abort_unless($this->audience->canManageProperty($request->user(), $role->property_id), 403);

        $lease = ! empty($data['lease_id']) ? Lease::findOrFail($data['lease_id']) : null;
        if ($lease && $this->audience->propertyIdForLease($lease) !== $role->property_id) {
            return back()->withErrors(['lease_id' => 'That lease is not at the agent\'s property.']);
        }

        // Default: the agent's rate applied to one month's rent of the lease.
        $amount = $data['amount'] ?? ($lease && $role->commission_rate ? round((float) $lease->monthly_rent * (float) $role->commission_rate / 100, 2) : null);
        if (! $amount) {
            return back()->withErrors(['amount' => 'Enter an amount, or choose a lease for an agent with a commission rate.']);
        }

        AgentCommission::create([
            'user_id' => $role->user_id,
            'property_id' => $role->property_id,
            'lease_id' => $lease?->id,
            'amount' => $amount,
            'currency' => $lease?->currency ?: 'RWF',
            'description' => $data['description'] ?? ($lease ? 'Tenant placement · lease ' . $lease->lease_number : null),
            'recorded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Commission recorded.');
    }

    public function updateCommission(Request $request, AgentCommission $commission)
    {
        abort_unless($this->audience->canManageProperty($request->user(), $commission->property_id), 403);
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'paid', 'cancelled'])],
            'payment_reference' => ['nullable', 'required_if:status,paid', 'string', 'max:255'],
        ]);
        abort_if($commission->status === 'paid' || $commission->status === 'cancelled', 422, 'This commission is already closed.');

        $commission->update($data + ['paid_at' => $data['status'] === 'paid' ? now() : null]);

        return back()->with('success', 'Commission ' . $data['status'] . '.');
    }
}
