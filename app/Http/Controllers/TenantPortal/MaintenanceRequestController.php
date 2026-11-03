<?php

namespace App\Http\Controllers\TenantPortal;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MaintenanceRequestController extends Controller
{
    private function tenant()
    {
        $tenant = auth()->user()
            ->tenant()
            ->where('status', 'active')
            ->first();

        abort_unless($tenant, 403, 'An active tenant account is required.');

        return $tenant;
    }

    private function requestsQuery()
    {
        return MaintenanceRequest::query()
            ->where('tenant_id', $this->tenant()->id)
            ->with([
                'unit.floor',
                'unit.building.property',
                'activities.user',
            ]);
    }

    public function index()
    {
        $requests = $this->requestsQuery()
            ->latest()
            ->paginate(10);

        $summary = [
            'total' => (clone $this->requestsQuery())->count(),
            'open' => (clone $this->requestsQuery())
                ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
                ->count(),
            'in_progress' => (clone $this->requestsQuery())
                ->where('status', 'in_progress')
                ->count(),
            'resolved' => (clone $this->requestsQuery())
                ->whereIn('status', ['resolved', 'closed'])
                ->count(),
        ];

        return view('tenant-portal.maintenance-requests.index', compact(
            'requests',
            'summary'
        ));
    }

    public function create()
    {
        $tenant = $this->tenant();

        $leases = Lease::query()
            ->with(['unit.floor', 'unit.building.property'])
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', today());
            })
            ->get();

        return view('tenant-portal.maintenance-requests.create', compact('leases'));
    }

    public function store(Request $request)
    {
        $tenant = $this->tenant();

        $validated = $request->validate([
            'unit_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:180'],
            'category' => [
                'required',
                Rule::in([
                    'plumbing',
                    'electrical',
                    'appliance',
                    'structural',
                    'pest_control',
                    'security',
                    'cleaning',
                    'other',
                ]),
            ],
            'priority' => [
                'required',
                Rule::in(['low', 'normal', 'high', 'emergency']),
            ],
            'description' => ['required', 'string', 'max:5000'],
            'preferred_visit_at' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'attachment' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ]);

        // Never trust a unit ID from the submitted form.
        $hasActiveLease = Lease::query()
            ->where('tenant_id', $tenant->id)
            ->where('unit_id', $validated['unit_id'])
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', today());
            })
            ->exists();

        abort_unless(
            $hasActiveLease,
            403,
            'You can only report maintenance for a unit you currently rent.'
        );

        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')
                ->store('maintenance-requests', 'public');
        }

        try {
            $maintenanceRequest = DB::transaction(function () use (
                $validated,
                $tenant,
                $attachmentPath
            ) {
                $maintenanceRequest = MaintenanceRequest::create([
                    'request_number' => 'MNT-' . now()->format('Ymd') . '-'
                        . strtoupper(Str::random(6)),
                    'tenant_id' => $tenant->id,
                    'unit_id' => $validated['unit_id'],
                    'title' => $validated['title'],
                    'category' => $validated['category'],
                    'priority' => $validated['priority'],
                    'description' => $validated['description'],
                    'preferred_visit_at' => $validated['preferred_visit_at'] ?? null,
                    'status' => 'submitted',
                    'attachment_path' => $attachmentPath,
                    'created_by' => auth()->id(),
                ]);

                MaintenanceRequestActivity::create([
                    'maintenance_request_id' => $maintenanceRequest->id,
                    'user_id' => auth()->id(),
                    'activity_type' => 'created',
                    'description' => 'Maintenance request submitted by tenant.',
                    'new_status' => 'submitted',
                ]);

                return $maintenanceRequest;
            });
        } catch (\Throwable $exception) {
            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }

            throw $exception;
        }

        // If your application has NotificationService::sendInApp configured,
        // notify the relevant property manager here.

        return redirect()
            ->route('tenant-portal.maintenance-requests.show', $maintenanceRequest)
            ->with('success', 'Your maintenance request has been submitted.');
    }

    public function show(MaintenanceRequest $maintenanceRequest)
    {
        $maintenanceRequest = $this->requestsQuery()
            ->findOrFail($maintenanceRequest->id);

        return view(
            'tenant-portal.maintenance-requests.show',
            compact('maintenanceRequest')
        );
    }
}
