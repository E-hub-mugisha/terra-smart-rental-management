<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\LeaseTemplate;
use App\Services\ContractBuilder;
use App\Services\PropertyAudience;
use Illuminate\Http\Request;

/**
 * Lease contract builder (brief §8): pick a template, fill the lease's
 * details into its clauses, edit clauses while in draft, then finalise.
 * The tenant sees the final contract and accepts it through the existing
 * lease acceptance step; a signed paper copy can be stored under Documents.
 */
class LeaseContractController extends Controller
{
    public function __construct(private ContractBuilder $builder, private PropertyAudience $audience)
    {
    }

    public function show(Request $request, Lease $lease)
    {
        $role = $this->roleFor($request, $lease);
        $contract = $lease->contract;
        abort_unless($contract && ($role === 'staff' || $contract->isFinal()), 404);

        $lease->load(['tenant', 'unit.building.property.owner']);

        return view('contracts.show', compact('lease', 'contract', 'role'));
    }

    public function edit(Request $request, Lease $lease)
    {
        abort_unless($this->roleFor($request, $lease) === 'staff', 403);
        $lease->load(['tenant', 'unit.building.property', 'contract']);
        $propertyId = $this->audience->propertyIdForLease($lease);

        return view('contracts.edit', [
            'lease' => $lease,
            'contract' => $lease->contract,
            'templates' => LeaseTemplate::where(fn ($q) => $q->whereNull('property_id')->orWhere('property_id', $propertyId))
                ->orderByDesc('is_default')->orderBy('name')->get(),
            'missing' => $this->missingDetails($lease),
        ]);
    }

    public function generate(Request $request, Lease $lease)
    {
        abort_unless($this->roleFor($request, $lease) === 'staff', 403);
        if ($lease->contract?->isFinal()) {
            return back()->withErrors(['contract' => 'The contract is final. Create a renewal or amendment instead of regenerating it.']);
        }

        $data = $request->validate([
            'lease_template_id' => ['required', 'integer', 'exists:lease_templates,id'],
            'notice_period_days' => ['required', 'integer', 'min:0', 'max:365'],
            'special_conditions' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->builder->generate($lease, LeaseTemplate::findOrFail($data['lease_template_id']), $request->user(), $data);

        return redirect()->route('contracts.edit', $lease)->with('success', 'Contract generated from the template. Review each clause, then finalise it.');
    }

    public function update(Request $request, Lease $lease)
    {
        abort_unless($this->roleFor($request, $lease) === 'staff', 403);
        $contract = $lease->contract;
        abort_unless($contract && ! $contract->isFinal(), 422, 'Only draft contracts can be edited.');

        $data = $request->validate([
            'clauses' => ['required', 'array', 'min:1', 'max:60'],
            'clauses.*.title' => ['required', 'string', 'max:150'],
            'clauses.*.body' => ['required', 'string', 'max:10000'],
            'clauses.*.remove' => ['nullable', 'boolean'],
        ]);

        $clauses = collect($data['clauses'])->reject(fn ($c) => ! empty($c['remove']))
            ->map(fn ($c) => ['title' => trim($c['title']), 'body' => trim($c['body'])])->values()->all();
        abort_if(empty($clauses), 422, 'A contract needs at least one clause.');

        $contract->update(['clauses' => $clauses]);

        if ($request->input('then') === 'finalize') {
            return $this->finalize($request, $lease);
        }

        return back()->with('success', 'Contract saved.');
    }

    public function finalize(Request $request, Lease $lease)
    {
        abort_unless($this->roleFor($request, $lease) === 'staff', 403);
        $contract = $lease->contract;
        abort_unless($contract && ! $contract->isFinal(), 422);

        $contract->update(['status' => 'final', 'finalized_at' => now()]);

        return redirect()->route('contracts.show', $lease)->with('success', 'Contract finalised. The tenant can now read it from their lease page.');
    }

    /** Details the template needs that the lease or tenant record doesn't have yet. */
    private function missingDetails(Lease $lease): array
    {
        $missing = [];
        if (! $lease->tenant?->id_number) {
            $missing[] = 'Tenant ID / passport number (tenant adds it under Documents → Identity)';
        }
        if (! $lease->unit?->building?->property?->address && ! $lease->unit?->building?->property?->district) {
            $missing[] = 'Property address';
        }

        return $missing;
    }

    private function roleFor(Request $request, Lease $lease): string
    {
        $user = $request->user();
        if ($this->audience->canManageProperty($user, $this->audience->propertyIdForLease($lease))) {
            return 'staff';
        }
        abort_unless($this->audience->isTenantOf($user, $lease), 404);

        return 'tenant';
    }
}
