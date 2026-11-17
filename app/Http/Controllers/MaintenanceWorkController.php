<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestActivity;
use App\Models\ServiceProvider;
use App\Services\Notifier;
use App\Services\PropertyAudience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Maintenance work-flow additions (brief §14):
 *   staff assign a request to a service provider → technician updates it
 *   from their own portal → tenant confirms it's fixed (or reopens it) → closed.
 */
class MaintenanceWorkController extends Controller
{
    /** Statuses a technician may set. */
    private const TECHNICIAN_STATUSES = ['scheduled' => 'Scheduled', 'in_progress' => 'In progress', 'on_hold' => 'On hold', 'resolved' => 'Resolved'];

    public function __construct(private PropertyAudience $audience, private Notifier $notifier)
    {
    }

    /* ----------------------------- Staff ----------------------------- */

    public function assign(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        abort_unless($this->audience->canManageProperty($request->user(), $this->audience->propertyIdForUnit($maintenanceRequest->unit_id)), 403);
        abort_if(in_array($maintenanceRequest->status, ['closed', 'rejected'], true), 422, 'This request is closed.');

        $data = $request->validate([
            'service_provider_id' => ['required', 'integer', Rule::exists('service_providers', 'id')->where('status', 'active')],
            'scheduled_at' => ['nullable', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $provider = ServiceProvider::findOrFail($data['service_provider_id']);

        DB::transaction(function () use ($maintenanceRequest, $provider, $data) {
            $old = $maintenanceRequest->status;
            $maintenanceRequest->update([
                'service_provider_id' => $provider->id,
                'assigned_to' => $provider->user_id,
                'scheduled_at' => $data['scheduled_at'] ?? $maintenanceRequest->scheduled_at,
                'status' => in_array($old, ['submitted', 'open'], true) ? (($data['scheduled_at'] ?? null) ? 'scheduled' : 'in_progress') : $old,
            ]);
            $this->activity($maintenanceRequest, 'assigned', 'Assigned to ' . $provider->company_name . (($data['note'] ?? null) ? ': ' . $data['note'] : '.'), $old, $maintenanceRequest->status);
        });

        $this->notifier->maintenanceAssigned($maintenanceRequest->fresh());

        return back()->with('success', 'Assigned to ' . $provider->company_name . '.');
    }

    /* -------------------------- Technician portal -------------------------- */

    public function technicianIndex(Request $request)
    {
        $provider = $this->provider($request);
        $filter = $request->query('filter', 'open');

        $jobs = MaintenanceRequest::where('service_provider_id', $provider->id)
            ->with(['unit.building.property', 'tenant'])
            ->when($filter === 'open', fn ($q) => $q->whereNotIn('status', ['resolved', 'closed', 'rejected']))
            ->when($filter === 'done', fn ($q) => $q->whereIn('status', ['resolved', 'closed']))
            ->orderByRaw("case priority when 'emergency' then 0 when 'high' then 1 when 'normal' then 2 else 3 end")
            ->latest('id')
            ->paginate(20)->withQueryString();

        return view('service-provider.index', [
            'provider' => $provider,
            'jobs' => $jobs,
            'filter' => $filter,
            'stats' => [
                'open' => MaintenanceRequest::where('service_provider_id', $provider->id)->whereNotIn('status', ['resolved', 'closed', 'rejected'])->count(),
                'done' => MaintenanceRequest::where('service_provider_id', $provider->id)->whereIn('status', ['resolved', 'closed'])->count(),
                'rating' => MaintenanceRequest::where('service_provider_id', $provider->id)->whereNotNull('tenant_rating')->avg('tenant_rating'),
            ],
        ]);
    }

    public function technicianShow(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $provider = $this->provider($request);
        abort_unless($maintenanceRequest->service_provider_id === $provider->id, 404);
        $maintenanceRequest->load(['unit.building.property', 'tenant', 'activities.user']);

        return view('service-provider.show', [
            'job' => $maintenanceRequest,
            'statuses' => self::TECHNICIAN_STATUSES,
        ]);
    }

    public function technicianUpdate(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $this->applyTechnicianUpdate($request, $maintenanceRequest);

        return back()->with('success', 'Job updated. The tenant and property manager have been notified.');
    }

    /** Validate and apply a technician's update. Shared by the web portal and the API. */
    public function applyTechnicianUpdate(Request $request, MaintenanceRequest $maintenanceRequest): MaintenanceRequest
    {
        $provider = $this->provider($request);
        abort_unless($maintenanceRequest->service_provider_id === $provider->id, 404);
        abort_if(in_array($maintenanceRequest->status, ['closed', 'rejected'], true), 422, 'This job is closed.');

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(self::TECHNICIAN_STATUSES))],
            'scheduled_at' => ['nullable', 'required_if:status,scheduled', 'date'],
            'note' => ['nullable', 'required_if:status,resolved', 'required_if:status,on_hold', 'string', 'max:3000'],
            'actual_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
        ], ['note.required_if' => 'Describe what was done (or why the job is on hold).']);

        DB::transaction(function () use ($maintenanceRequest, $data) {
            $old = $maintenanceRequest->status;
            $maintenanceRequest->update(array_filter([
                'status' => $data['status'],
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'resolved_at' => $data['status'] === 'resolved' ? now() : null,
                'resolution_notes' => $data['status'] === 'resolved' ? $data['note'] : null,
                'actual_cost' => $data['actual_cost'] ?? null,
            ], fn ($v) => $v !== null));
            $this->activity($maintenanceRequest, 'technician_update', $data['note'] ?? ('Status set to ' . self::TECHNICIAN_STATUSES[$data['status']] . '.'), $old, $data['status']);
        });

        return $maintenanceRequest->fresh();
    }

    /* ----------------------------- Tenant ----------------------------- */

    public function tenantConfirm(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $maintenanceRequest->loadMissing('tenant');
        abort_unless($maintenanceRequest->tenant?->user_id === $request->user()->id, 404);
        abort_unless($maintenanceRequest->status === 'resolved', 422, 'You can confirm once the request is marked resolved.');

        $data = $request->validate([
            'fixed' => ['required', 'boolean'],
            'rating' => ['nullable', 'required_if:fixed,1', 'integer', 'between:1,5'],
            'feedback' => ['nullable', 'required_if:fixed,0', 'string', 'max:2000'],
        ], ['feedback.required_if' => 'Tell us what is still wrong.', 'rating.required_if' => 'Rate the repair from 1 to 5.']);
        $fixed = (bool) $data['fixed'];

        DB::transaction(function () use ($maintenanceRequest, $data, $fixed) {
            $old = $maintenanceRequest->status;
            $maintenanceRequest->update([
                'status' => $fixed ? 'closed' : 'in_progress',
                'tenant_confirmed_at' => $fixed ? now() : null,
                'tenant_rating' => $fixed ? $data['rating'] : null,
                'tenant_feedback' => $data['feedback'] ?? null,
                'resolved_at' => $fixed ? $maintenanceRequest->resolved_at : null,
            ]);
            $this->activity($maintenanceRequest, $fixed ? 'tenant_confirmed' : 'tenant_reopened',
                $fixed ? 'Tenant confirmed the repair (' . $data['rating'] . '/5).' : 'Tenant reports it is not fixed: ' . $data['feedback'], $old, $maintenanceRequest->status);
        });

        $this->notifier->maintenanceTenantResponse($maintenanceRequest->fresh(), $fixed);

        return back()->with('success', $fixed ? 'Thanks for confirming — the request is now closed.' : 'Thanks — the request has been reopened and the team notified.');
    }

    /* ------------------------------------------------------------------ */

    private function provider(Request $request): ServiceProvider
    {
        $provider = $request->user()->serviceProvider;
        abort_unless($provider && $provider->status === 'active', 403, 'Your service provider account is not active.');

        return $provider;
    }

    private function activity(MaintenanceRequest $request, string $type, string $description, ?string $old, ?string $new): void
    {
        MaintenanceRequestActivity::create([
            'maintenance_request_id' => $request->id,
            'user_id' => auth()->id(),
            'activity_type' => $type,
            'description' => $description,
            'old_status' => $old,
            'new_status' => $new,
        ]);
    }
}
