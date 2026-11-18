<?php

namespace App\Http\Controllers\PropertyManager;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestActivity;
use App\Models\Property;
use App\Models\PropertyManager;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Support\FileUpload;
use RuntimeException;

class MaintenanceRequestController extends Controller
{
    /**
     * Get the authenticated user's active property manager account.
     */
    private function manager(): ?PropertyManager
    {
        return auth()->user()
            ?->propertyManager()
            ->where('status', 'active')
            ->first();
    }

    /**
     * Get the authenticated user's property owner account.
     */
    private function owner()
    {
        return auth()->user()?->propertyOwner;
    }

    /**
     * Ensure the user is an active manager or property owner.
     */
    private function authorizeMaintenanceAccess(): void
    {
        abort_unless(
            $this->manager() || $this->owner(),
            403,
            'An active property manager or property owner account is required.'
        );
    }

    /**
     * Properties accessible to the authenticated user.
     */
    private function accessibleProperties(): Builder
    {
        $this->authorizeMaintenanceAccess();

        $manager = $this->manager();
        $owner = $this->owner();

        return Property::query()
            ->where(function (Builder $query) use ($manager, $owner) {
                if ($owner) {
                    $query->where('property_owner_id', $owner->id);
                }

                if ($manager) {
                    $method = $owner ? 'orWhereHas' : 'whereHas';

                    $query->{$method}(
                        'activeManagers',
                        function (Builder $managerQuery) use ($manager) {
                            $managerQuery->where(
                                'property_managers.id',
                                $manager->id
                            );
                        }
                    );
                }
            });
    }

    /**
     * Units belonging to accessible properties.
     */
    private function accessibleUnits(): Builder
    {
        $propertyIds = $this->accessibleProperties()
            ->select('properties.id');

        return Unit::query()
            ->with(['building.property'])
            ->whereHas('building', function (Builder $building) use ($propertyIds) {
                $building->whereIn('property_id', $propertyIds);
            });
    }

    /**
     * Maintenance requests belonging to accessible properties.
     */
    private function requestsQuery(): Builder
    {
        $propertyIds = $this->accessibleProperties()
            ->select('properties.id');

        return MaintenanceRequest::query()
            ->with([
                'tenant',
                'unit.building.property',
                'assignee',
                'creator',
            ])
            ->whereHas('unit.building', function (Builder $building) use ($propertyIds) {
                $building->whereIn('property_id', $propertyIds);
            });
    }

    /**
     * Retrieve a request only if it belongs to an accessible property.
     */
    private function scopedRequest(int $id): MaintenanceRequest
    {
        return $this->requestsQuery()->findOrFail($id);
    }

    /**
     * Available maintenance categories.
     */
    private function categories(): array
    {
        return [
            'plumbing' => 'Plumbing',
            'electrical' => 'Electrical',
            'hvac' => 'Heating / Cooling',
            'structural' => 'Structural',
            'appliance' => 'Appliance',
            'cleaning' => 'Cleaning',
            'pest_control' => 'Pest Control',
            'security' => 'Security',
            'painting' => 'Painting',
            'other' => 'Other',
        ];
    }

    /**
     * Available maintenance priorities.
     */
    private function priorities(): array
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
        ];
    }

    /**
     * Available maintenance statuses.
     */
    private function statuses(): array
    {
        return [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'scheduled' => 'Scheduled',
            'on_hold' => 'On Hold',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            'rejected' => 'Rejected',
        ];
    }

    /**
     * Get tenants associated with a particular unit.
     *
     * A tenant must have a lease associated with the selected unit.
     */
    private function tenantBelongsToUnit(int $tenantId, int $unitId): bool
    {
        return Tenant::query()
            ->whereKey($tenantId)
            ->whereHas('leases', function (Builder $lease) use ($unitId) {
                $lease->where('unit_id', $unitId);
            })
            ->exists();
    }

    /**
     * Record an activity in the maintenance request history.
     */
    private function recordActivity(
        MaintenanceRequest $maintenanceRequest,
        string $type,
        string $description,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        array $metadata = []
    ): void {
        MaintenanceRequestActivity::create([
            'maintenance_request_id' => $maintenanceRequest->id,
            'user_id' => auth()->id(),
            'activity_type' => $type,
            'description' => $description,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'metadata' => $metadata ?: null,
        ]);
    }

    /**
     * Generate a unique maintenance request number.
     */
    private function generateRequestNumber(): string
    {
        do {
            $number = 'MNT-' . strtoupper(Str::random(10));
        } while (
            MaintenanceRequest::where('request_number', $number)->exists()
        );

        return $number;
    }

    /**
     * Display maintenance requests.
     */
    public function index(Request $request)
    {
        $query = $this->requestsQuery();

        if ($search = trim($request->input('search', ''))) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('tenant', function (Builder $tenant) use ($search) {
                        $tenant->where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('unit', function (Builder $unit) use ($search) {
                        $unit->where('unit_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            if (array_key_exists($request->input('status'), $this->statuses())) {
                $query->where('status', $request->input('status'));
            }
        }

        if ($request->filled('priority')) {
            if (array_key_exists($request->input('priority'), $this->priorities())) {
                $query->where('priority', $request->input('priority'));
            }
        }

        if ($request->filled('category')) {
            if (array_key_exists($request->input('category'), $this->categories())) {
                $query->where('category', $request->input('category'));
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->input('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->input('date_to')
            );
        }

        $requests = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $summaryQuery = $this->requestsQuery();

        $summary = [
            'all' => (clone $summaryQuery)->count(),

            'open' => (clone $summaryQuery)
                ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
                ->count(),

            'urgent' => (clone $summaryQuery)
                ->where('priority', 'urgent')
                ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
                ->count(),

            'in_progress' => (clone $summaryQuery)
                ->where('status', 'in_progress')
                ->count(),

            'resolved' => (clone $summaryQuery)
                ->whereIn('status', ['resolved', 'closed'])
                ->count(),
        ];

        $categories = $this->categories();
        $priorities = $this->priorities();
        $statuses = $this->statuses();

        return view('property-portal.maintenance-requests.index', compact(
            'requests',
            'summary',
            'categories',
            'priorities',
            'statuses'
        ));
    }

    /**
     * Show the create maintenance request form.
     */
    public function create()
    {
        $units = $this->accessibleUnits()
            ->orderBy('unit_number')
            ->get();

        abort_if(
            $units->isEmpty(),
            403,
            'You do not have access to any property units.'
        );

        $unitIds = $units->pluck('id');

        $tenants = Tenant::query()
            ->whereHas('leases', function (Builder $lease) use ($unitIds) {
                $lease->whereIn('unit_id', $unitIds);
            })
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'tenant_number', 'email']);

        $categories = $this->categories();
        $priorities = $this->priorities();

        return view('property-portal.maintenance-requests.create', compact(
            'units',
            'tenants',
            'categories',
            'priorities'
        ));
    }

    /**
     * Store a maintenance request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => [
                'required',
                'integer',
                'exists:tenants,id',
            ],

            'unit_id' => [
                'required',
                'integer',
                'exists:units,id',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'required',
                'string',
                'max:5000',
            ],

            'category' => [
                'required',
                Rule::in(array_keys($this->categories())),
            ],

            'priority' => [
                'required',
                Rule::in(array_keys($this->priorities())),
            ],

            'preferred_visit_at' => [
                'nullable',
                'date',
            ],

            'estimated_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999',
            ],

            'attachment' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf,doc,docx',
                'max:5120',
            ],
        ]);

        // Ensure the selected unit belongs to an accessible property.
        $unit = $this->accessibleUnits()
            ->whereKey($validated['unit_id'])
            ->firstOrFail();

        // Ensure the tenant is associated with the selected unit.
        if (!$this->tenantBelongsToUnit(
            (int) $validated['tenant_id'],
            (int) $unit->id
        )) {
            throw ValidationException::withMessages([
                'tenant_id' => 'The selected tenant does not have a lease associated with this unit.',
            ]);
        }

        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            try {
                $attachmentPath = FileUpload::store($request->file('attachment'), FileUpload::MAINTENANCE_IMAGES);
            } catch (RuntimeException $e) {
                return back()->withInput()->withErrors(['attachment' => $e->getMessage()]);
            }
        }

        try {
            $maintenanceRequest = DB::transaction(function () use (
                $validated,
                $request,
                $attachmentPath
            ) {
                $maintenanceRequest = MaintenanceRequest::create([
                    'request_number' => $this->generateRequestNumber(),
                    'tenant_id' => $validated['tenant_id'],
                    'unit_id' => $validated['unit_id'],
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'category' => $validated['category'],
                    'priority' => $validated['priority'],
                    'status' => 'open',
                    'preferred_visit_at' => $validated['preferred_visit_at'] ?? null,
                    'estimated_cost' => $validated['estimated_cost'] ?? null,
                    'attachment_path' => $attachmentPath,
                    'created_by' => $request->user()->id,
                ]);

                $this->recordActivity(
                    $maintenanceRequest,
                    'created',
                    'Maintenance request created.',
                    null,
                    'open'
                );

                return $maintenanceRequest;
            });
        } catch (\Throwable $e) {
            if ($attachmentPath) {
                FileUpload::delete($attachmentPath);
            }

            throw $e;
        }

        return redirect()
            ->route('property-manager.maintenance-requests.show', $maintenanceRequest->id)
            ->with('success', 'Maintenance request created successfully.');
    }

    /**
     * Display a maintenance request and its activity history.
     */
    public function show(int $maintenance)
    {
        $maintenanceRequest = $this->scopedRequest($maintenance);

        $maintenanceRequest->load([
            'tenant',
            'unit.building.property',
            'assignee',
            'creator',
            'activities.user',
        ]);

        $statuses = $this->statuses();
        $priorities = $this->priorities();

        return view('property-portal.maintenance-requests.show', compact(
            'maintenanceRequest',
            'statuses',
            'priorities'
        ));
    }

    /**
     * Show the edit form.
     */
    public function edit(int $maintenance)
    {
        $maintenanceRequest = $this->scopedRequest($maintenance);

        $units = $this->accessibleUnits()
            ->orderBy('unit_number')
            ->get();

        $unitIds = $units->pluck('id');

        $tenants = Tenant::query()
            ->whereHas('leases', function (Builder $lease) use ($unitIds) {
                $lease->whereIn('unit_id', $unitIds);
            })
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'tenant_number', 'email']);

        $categories = $this->categories();
        $priorities = $this->priorities();

        return view('property-portal.maintenance-requests.edit', compact(
            'maintenanceRequest',
            'units',
            'tenants',
            'categories',
            'priorities'
        ));
    }

    /**
     * Update a maintenance request.
     */
    public function update(Request $request, int $maintenance)
    {
        $maintenanceRequest = $this->scopedRequest($maintenance);

        $validated = $request->validate([
            'tenant_id' => [
                'required',
                'integer',
                'exists:tenants,id',
            ],

            'unit_id' => [
                'required',
                'integer',
                'exists:units,id',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'required',
                'string',
                'max:5000',
            ],

            'category' => [
                'required',
                Rule::in(array_keys($this->categories())),
            ],

            'priority' => [
                'required',
                Rule::in(array_keys($this->priorities())),
            ],

            'preferred_visit_at' => [
                'nullable',
                'date',
            ],

            'estimated_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999',
            ],

            'attachment' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf,doc,docx',
                'max:5120',
            ],

            'remove_attachment' => [
                'nullable',
                'boolean',
            ],
        ]);

        $unit = $this->accessibleUnits()
            ->whereKey($validated['unit_id'])
            ->firstOrFail();

        if (!$this->tenantBelongsToUnit(
            (int) $validated['tenant_id'],
            (int) $unit->id
        )) {
            throw ValidationException::withMessages([
                'tenant_id' => 'The selected tenant does not have a lease associated with this unit.',
            ]);
        }

        $oldStatus = $maintenanceRequest->status;
        $oldValues = $maintenanceRequest->only([
            'tenant_id',
            'unit_id',
            'title',
            'description',
            'category',
            'priority',
            'preferred_visit_at',
            'estimated_cost',
        ]);

        $oldAttachment = $maintenanceRequest->attachment_path;
        $newAttachment = null;

        if ($request->hasFile('attachment')) {
            try {
                $newAttachment = FileUpload::store($request->file('attachment'), FileUpload::MAINTENANCE_IMAGES);
            } catch (RuntimeException $e) {
                return back()->withInput()->withErrors(['attachment' => $e->getMessage()]);
            }
        }

        try {
            DB::transaction(function () use (
                $maintenanceRequest,
                $validated,
                $request,
                $oldStatus,
                $oldValues,
                $oldAttachment,
                $newAttachment
            ) {
                $maintenanceRequest->update([
                    'tenant_id' => $validated['tenant_id'],
                    'unit_id' => $validated['unit_id'],
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'category' => $validated['category'],
                    'priority' => $validated['priority'],
                    'preferred_visit_at' => $validated['preferred_visit_at'] ?? null,
                    'estimated_cost' => $validated['estimated_cost'] ?? null,
                    'attachment_path' => $newAttachment
                        ?: (
                            $request->boolean('remove_attachment')
                                ? null
                                : $oldAttachment
                        ),
                ]);

                $changes = [];

                foreach ($oldValues as $field => $oldValue) {
                    $newValue = $maintenanceRequest->{$field};

                    if ((string) $oldValue !== (string) $newValue) {
                        $changes[$field] = [
                            'old' => $oldValue,
                            'new' => $newValue,
                        ];
                    }
                }

                $this->recordActivity(
                    $maintenanceRequest,
                    'updated',
                    'Maintenance request details updated.',
                    $oldStatus,
                    $maintenanceRequest->status,
                    $changes
                );
            });
        } catch (\Throwable $e) {
            if ($newAttachment) {
                FileUpload::delete($newAttachment);
            }

            throw $e;
        }

        if (
            $oldAttachment &&
            ($newAttachment || $request->boolean('remove_attachment'))
        ) {
            FileUpload::delete($oldAttachment);
        }

        return redirect()
            ->route('property-manager.maintenance-requests.show', $maintenanceRequest->id)
            ->with('success', 'Maintenance request updated successfully.');
    }

    /**
     * Update the status and maintenance scheduling information.
     */
    public function updateStatus(Request $request, int $maintenance)
    {
        $maintenanceRequest = $this->scopedRequest($maintenance);

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(array_keys($this->statuses())),
            ],

            'scheduled_at' => [
                'nullable',
                'date',
            ],

            'resolved_at' => [
                'nullable',
                'date',
            ],

            'resolution_notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'actual_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999',
            ],
        ]);

        $oldStatus = $maintenanceRequest->status;
        $newStatus = $validated['status'];

        if (
            in_array($oldStatus, ['closed', 'rejected'], true) &&
            $newStatus !== $oldStatus
        ) {
            throw ValidationException::withMessages([
                'status' => 'Closed or rejected requests cannot be reopened through this action.',
            ]);
        }

        if ($newStatus === 'resolved' && empty($validated['resolved_at'])) {
            $validated['resolved_at'] = now();
        }

        DB::transaction(function () use (
            $maintenanceRequest,
            $validated,
            $oldStatus,
            $newStatus
        ) {
            $maintenanceRequest->update([
                'status' => $newStatus,
                'scheduled_at' => $validated['scheduled_at'] ?? null,
                'resolved_at' => $validated['resolved_at'] ?? null,
                'resolution_notes' => $validated['resolution_notes'] ?? null,
                'actual_cost' => $validated['actual_cost'] ?? null,
            ]);

            $this->recordActivity(
                $maintenanceRequest,
                'status_changed',
                "Maintenance status changed from {$oldStatus} to {$newStatus}.",
                $oldStatus,
                $newStatus,
                [
                    'scheduled_at' => $validated['scheduled_at'] ?? null,
                    'resolved_at' => $validated['resolved_at'] ?? null,
                    'actual_cost' => $validated['actual_cost'] ?? null,
                ]
            );
        });

        return redirect()
            ->route('property-manager.maintenance-requests.show', $maintenanceRequest->id)
            ->with('success', 'Maintenance request status updated successfully.');
    }

    /**
     * Delete a maintenance request.
     */
    public function destroy(int $maintenance)
    {
        $maintenanceRequest = $this->scopedRequest($maintenance);

        DB::transaction(function () use ($maintenanceRequest) {
            $this->recordActivity(
                $maintenanceRequest,
                'deleted',
                'Maintenance request deleted.'
            );

            $maintenanceRequest->delete();
        });

        return redirect()
            ->route('property-manager.maintenance-requests.index')
            ->with('success', 'Maintenance request deleted successfully.');
    }
}