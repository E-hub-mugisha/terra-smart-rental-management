<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\LeaseRequest;
use App\Services\Notifier;
use App\Services\PropertyAudience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Lease renewal (brief §3, §4B "request lease renewal") and tenant
 * move-out notice (§4B "initiate move-out process").
 *
 * Renewal:  tenant requests (or staff open with an offer) → staff send new
 *           end date + rent → tenant accepts → the lease is extended.
 * Move-out: tenant gives notice with a date → staff acknowledge and then
 *           run the move-out inspection.
 */
class LeaseRequestController extends Controller
{
    public function __construct(private PropertyAudience $audience, private Notifier $notifier)
    {
    }

    /** Tenant request (renewal / move-out) or staff renewal offer. */
    public function store(Request $request, Lease $lease)
    {
        $user = $request->user();
        $role = $this->roleFor($user, $lease);
        abort_unless($lease->status === 'active', 422, 'Requests can only be made on active leases.');

        $type = $request->input('type');
        abort_unless(in_array($type, [LeaseRequest::TYPE_RENEWAL, LeaseRequest::TYPE_MOVE_OUT], true), 422);
        abort_if($role === 'staff' && $type === LeaseRequest::TYPE_MOVE_OUT, 403);

        if ($lease->requests()->where('type', $type)->whereIn('status', ['requested', 'offered'])->exists()) {
            return back()->withErrors(['lease_request' => 'There is already an open ' . str_replace('_', '-', $type) . ' request for this lease.']);
        }

        $base = [
            'lease_id' => $lease->id,
            'tenant_id' => $lease->tenant_id,
            'type' => $type,
            'requested_by' => $user->id,
            'current_end_date' => $lease->end_date,
            'current_rent' => $lease->monthly_rent,
        ];

        if ($role === 'staff') {
            $data = $this->validateOffer($request, $lease);
            $leaseRequest = LeaseRequest::create($base + [
                'status' => 'offered',
                'offered_end_date' => $data['offered_end_date'],
                'offered_rent' => $data['offered_rent'],
                'response' => $data['response'] ?? null,
                'responded_by' => $user->id,
                'responded_at' => now(),
            ]);
            $this->notifier->leaseRequestEvent($leaseRequest, 'offered');

            return back()->with('success', 'Renewal offer sent to the tenant.');
        }

        $data = $request->validate($type === LeaseRequest::TYPE_RENEWAL ? [
            'requested_months' => ['required', 'integer', Rule::in([6, 12, 24, 36])],
            'message' => ['nullable', 'string', 'max:2000'],
        ] : [
            'move_out_date' => ['required', 'date', 'after:today'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $leaseRequest = LeaseRequest::create($base + $data + ['status' => 'requested']);
        $this->notifier->leaseRequestEvent($leaseRequest, 'requested');

        return back()->with('success', $type === LeaseRequest::TYPE_RENEWAL
            ? 'Renewal request sent. Your property manager will reply with an offer.'
            : 'Move-out notice sent. Your property manager will confirm and arrange the move-out inspection.');
    }

    public function respond(Request $request, LeaseRequest $leaseRequest)
    {
        $user = $request->user();
        $lease = $leaseRequest->lease;
        $role = $this->roleFor($user, $lease);
        abort_unless($leaseRequest->isOpen(), 422, 'This request is already closed.');

        $allowed = match (true) {
            $role === 'staff' && $leaseRequest->type === 'renewal' && $leaseRequest->status === 'requested' => ['offer', 'decline'],
            $role === 'staff' && $leaseRequest->type === 'renewal' && $leaseRequest->status === 'offered' => ['withdraw'],
            $role === 'staff' && $leaseRequest->type === 'move_out' => ['acknowledge'],
            $role === 'tenant' && $leaseRequest->type === 'renewal' && $leaseRequest->status === 'offered' => ['accept', 'decline'],
            $role === 'tenant' && $leaseRequest->status === 'requested' => ['withdraw'],
            default => [],
        };

        $action = $request->validate(['action' => ['required', Rule::in($allowed ?: ['__none__'])]])['action'];

        if ($action === 'offer') {
            $data = $this->validateOffer($request, $lease);
            $leaseRequest->update($data + ['status' => 'offered', 'responded_by' => $user->id, 'responded_at' => now()]);
            $this->notifier->leaseRequestEvent($leaseRequest, 'offered');

            return back()->with('success', 'Renewal offer sent to the tenant.');
        }

        if ($action === 'accept') {
            DB::transaction(function () use ($leaseRequest, $lease) {
                $locked = Lease::whereKey($lease->id)->lockForUpdate()->firstOrFail();
                abort_unless($locked->status === 'active', 422, 'The lease is no longer active.');
                $locked->update([
                    'end_date' => $leaseRequest->offered_end_date,
                    'monthly_rent' => $leaseRequest->offered_rent,
                ]);
                $leaseRequest->update(['status' => 'accepted', 'closed_at' => now()]);
            });
            $this->notifier->leaseRequestEvent($leaseRequest->fresh(), 'accepted');

            return back()->with('success', 'Renewal accepted. Your lease now runs until ' . $leaseRequest->offered_end_date->format('d M Y') . '.');
        }

        $note = $request->validate(['response' => ['nullable', 'string', 'max:2000']])['response'] ?? null;
        $status = ['decline' => 'declined', 'acknowledge' => 'acknowledged', 'withdraw' => 'withdrawn'][$action];

        $leaseRequest->update([
            'status' => $status,
            'closed_at' => now(),
        ] + ($role === 'staff' ? ['response' => $note ?? $leaseRequest->response, 'responded_by' => $user->id, 'responded_at' => now()] : []));

        $this->notifier->leaseRequestEvent($leaseRequest, $status);

        return back()->with('success', match ($status) {
            'acknowledged' => 'Move-out notice acknowledged. Start the move-out inspection when the tenant leaves.',
            'withdrawn' => 'Request withdrawn.',
            default => 'Response sent.',
        });
    }

    private function validateOffer(Request $request, Lease $lease): array
    {
        return $request->validate([
            'offered_end_date' => ['required', 'date', 'after:' . $lease->end_date->toDateString()],
            'offered_rent' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'response' => ['nullable', 'string', 'max:2000'],
        ], ['offered_end_date.after' => 'The new end date must be after the current end date (' . $lease->end_date->format('d M Y') . ').']);
    }

    private function roleFor($user, Lease $lease): string
    {
        if ($this->audience->canManageProperty($user, $this->audience->propertyIdForLease($lease))) {
            return 'staff';
        }
        abort_unless($this->audience->isTenantOf($user, $lease), 404);

        return 'tenant';
    }
}
