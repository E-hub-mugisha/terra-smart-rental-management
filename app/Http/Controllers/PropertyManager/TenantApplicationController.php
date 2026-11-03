<?php

namespace App\Http\Controllers\PropertyManager;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Property;
use App\Models\PropertyManager;
use App\Models\TenantApplication;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TenantApplicationController extends Controller
{
    /**
     * Get the authenticated user's active manager account.
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
     * Ensure the authenticated user is an owner or active manager.
     */
    private function authorizeApplicationAccess(): void
    {
        abort_unless(
            $this->manager() || $this->owner(),
            403,
            'An active property manager or property owner account is required.'
        );
    }

    /**
     * Get properties accessible to the authenticated user.
     *
     * Owners can access their own properties.
     * Managers can access properties assigned to them.
     */
    private function accessibleProperties(): Builder
    {
        $this->authorizeApplicationAccess();

        $manager = $this->manager();
        $owner = $this->owner();

        return Property::query()->where(function (Builder $query) use (
            $manager,
            $owner
        ) {
            // Property owner access.
            if ($owner) {
                $query->where('property_owner_id', $owner->id);
            }

            // Assigned property manager access.
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
     * Applications belonging to properties accessible
     * to the authenticated owner or manager.
     */
    private function applicationsQuery(): Builder
    {
        $propertyIds = $this->accessibleProperties()
            ->select('properties.id');

        return TenantApplication::query()
            ->whereHas('unit.building', function (Builder $building) use (
                $propertyIds
            ) {
                $building->whereIn('property_id', $propertyIds);
            });
    }

    /**
     * Retrieve an application within the user's access scope.
     */
    private function scopedApplication(
        TenantApplication $application
    ): TenantApplication {
        return $this->applicationsQuery()
            ->whereKey($application->id)
            ->firstOrFail();
    }

    /**
     * Display tenant applications.
     */
    public function index(Request $request)
    {
        $this->authorizeApplicationAccess();

        $base = $this->applicationsQuery();

        $counts = [
            'all' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'under_review' => (clone $base)->where('status', 'under_review')->count(),
            'approved' => (clone $base)->where('status', 'approved')->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
        ];

        $query = $this->applicationsQuery()
            ->with([
                'tenant',
                'unit.floor.building.property',
                'lease',
            ]);

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());

            $query->where(function (Builder $q) use ($search) {
                $q->where(
                    'application_number',
                    'like',
                    "%{$search}%"
                )
                ->orWhereHas('tenant', function (Builder $tenantQuery) use ($search) {
                    $tenantQuery
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })
                ->orWhereHas('unit', function (Builder $unitQuery) use ($search) {
                    $unitQuery->where(
                        'unit_number',
                        'like',
                        "%{$search}%"
                    );
                });
            });
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        $applications = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view(
            'property-portal.tenant-applications.index',
            compact('applications', 'counts')
        );
    }

    /**
     * Display application details.
     */
    public function show(TenantApplication $application)
    {
        $application = $this->scopedApplication($application);

        $application->load([
            'tenant.user',
            'unit.floor.building.property',
            'reviewer',
            'lease',
        ]);

        return view(
            'property-portal.tenant-applications.show',
            compact('application')
        );
    }

    /**
     * Move an application into review.
     */
    public function startReview(TenantApplication $application)
    {
        $application = $this->scopedApplication($application);

        if ($application->status !== 'pending') {
            return back()->with(
                'error',
                'Only pending applications can be moved into review.'
            );
        }

        $application->status = 'under_review';
        $application->reviewed_by = auth()->id();
        $application->reviewed_at = now();
        $application->save();

        return redirect()
            ->route('property-manager.applications.show', $application)
            ->with('success', 'Application moved to under review.');
    }

    /**
     * Approve an application and create a draft lease.
     */
    public function approve(
        Request $request,
        TenantApplication $application
    ) {
        $application = $this->scopedApplication($application);

        if (! in_array(
            $application->status,
            ['pending', 'under_review'],
            true
        )) {
            return back()->with(
                'error',
                'This application can no longer be approved.'
            );
        }

        $validated = $request->validate([
            'start_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],

            'end_date' => [
                'required',
                'date',
                'after:start_date',
            ],

            'monthly_rent' => [
                'required',
                'numeric',
                'min:0',
            ],

            'deposit_amount' => [
                'required',
                'numeric',
                'min:0',
            ],

            'currency' => [
                'required',
                Rule::in(['RWF', 'USD', 'EUR']),
            ],

            'rent_due_day' => [
                'required',
                'integer',
                'between:1,28',
            ],

            'payment_frequency' => [
                'required',
                Rule::in(['monthly', 'quarterly', 'yearly']),
            ],

            'decision_notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        DB::transaction(function () use ($application, $validated) {

            $application = $this->applicationsQuery()
                ->whereKey($application->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array(
                $application->status,
                ['pending', 'under_review'],
                true
            )) {
                throw ValidationException::withMessages([
                    'application' => 'This application has already been processed.',
                ]);
            }

            if ($application->lease()->exists()) {
                throw ValidationException::withMessages([
                    'application' => 'A lease already exists for this application.',
                ]);
            }

            $unit = Unit::query()
                ->whereKey($application->unit_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($unit->status !== 'available') {
                throw ValidationException::withMessages([
                    'unit' => 'This unit is no longer available. Review its status before approving.',
                ]);
            }

            $overlap = Lease::query()
                ->where('unit_id', $unit->id)
                ->whereNotIn('status', [
                    'terminated',
                    'cancelled',
                    'rejected',
                ])
                ->whereDate(
                    'start_date',
                    '<=',
                    $validated['end_date']
                )
                ->whereDate(
                    'end_date',
                    '>=',
                    $validated['start_date']
                )
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'start_date' => 'This unit already has a lease covering the selected dates.',
                ]);
            }

            $lease = new Lease();

            $lease->tenant_id = $application->tenant_id;
            $lease->unit_id = $application->unit_id;
            $lease->tenant_application_id = $application->id;
            $lease->lease_number = $this->generateLeaseNumber();
            $lease->start_date = $validated['start_date'];
            $lease->end_date = $validated['end_date'];
            $lease->monthly_rent = $validated['monthly_rent'];
            $lease->deposit_amount = $validated['deposit_amount'];
            $lease->currency = $validated['currency'];
            $lease->rent_due_day = $validated['rent_due_day'];
            $lease->payment_frequency = $validated['payment_frequency'];
            $lease->status = 'draft';

            $lease->save();

            $application->status = 'approved';
            $application->reviewed_by = auth()->id();
            $application->reviewed_at = now();
            $application->decision_notes =
                $validated['decision_notes'] ?? null;

            $application->save();

            // Reserve the unit while the tenant reviews the draft lease.
            $unit->status = 'reserved';
            $unit->save();
        });

        return redirect()
            ->route('property-manager.applications.show', $application)
            ->with(
                'success',
                'Application approved. A draft lease has been created for tenant acceptance.'
            );
    }

    /**
     * Reject a tenant application.
     */
    public function reject(
        Request $request,
        TenantApplication $application
    ) {
        $application = $this->scopedApplication($application);

        if (! in_array(
            $application->status,
            ['pending', 'under_review'],
            true
        )) {
            return back()->with(
                'error',
                'Only pending or under-review applications can be rejected.'
            );
        }

        $validated = $request->validate([
            'decision_notes' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        DB::transaction(function () use ($application, $validated) {

            $lockedApplication = $this->applicationsQuery()
                ->whereKey($application->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array(
                $lockedApplication->status,
                ['pending', 'under_review'],
                true
            )) {
                throw ValidationException::withMessages([
                    'application' => 'This application has already been processed.',
                ]);
            }

            $lockedApplication->status = 'rejected';
            $lockedApplication->reviewed_by = auth()->id();
            $lockedApplication->reviewed_at = now();
            $lockedApplication->decision_notes =
                $validated['decision_notes'];

            $lockedApplication->save();
        });

        return redirect()
            ->route('property-manager.applications.show', $application)
            ->with('success', 'Application rejected.');
    }

    /**
     * Generate a unique lease number.
     */
    private function generateLeaseNumber(): string
    {
        do {
            $number = 'LSE-' . now()->format('Ymd') . '-'
                . Str::upper(Str::random(5));

        } while (
            Lease::withTrashed()
                ->where('lease_number', $number)
                ->exists()
        );

        return $number;
    }
}
