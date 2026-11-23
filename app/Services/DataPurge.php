<?php

namespace App\Services;

use App\Models\AgentCommission;
use App\Models\Building;
use App\Models\BuildingImage;
use App\Models\Document;
use App\Models\FieldStaff;
use App\Models\Floor;
use App\Models\FloorImage;
use App\Models\Inspection;
use App\Models\InspectionItem;
use App\Models\InspectionMedia;
use App\Models\Lease;
use App\Models\LeaseContract;
use App\Models\LeaseRequest;
use App\Models\LeaseTemplate;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestActivity;
use App\Models\PaymentIntent;
use App\Models\Property;
use App\Models\PropertyExpense;
use App\Models\PropertyImage;
use App\Models\PropertyManager;
use App\Models\PropertyManagerAssignment;
use App\Models\PropertyOwner;
use App\Models\PropertyUserRole;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\RentPaymentAllocation;
use App\Models\SubscriptionContract;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\Unit;
use App\Models\UnitImage;
use App\Models\User;
use App\Observers\AuditObserver;
use App\Services\Payments\InvoiceBalance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Permanently removes a record and everything that depends on it, including
 * uploaded files. Meant for clearing demo and test data; unlike the normal
 * delete/archive actions, nothing can be recovered afterwards.
 *
 * Children are removed before parents so RESTRICT foreign keys never block
 * the delete. Models that own files are deleted one by one through Eloquent
 * so DeletesUploadedFiles removes their files once the transaction commits.
 */
class DataPurge
{
    /** Admin-facing record types => model class. */
    public const TYPES = [
        'owner' => PropertyOwner::class,
        'manager' => PropertyManager::class,
        'property' => Property::class,
        'tenant' => Tenant::class,
        'application' => TenantApplication::class,
        'lease' => Lease::class,
        'invoice' => RentInvoice::class,
        'payment' => RentPayment::class,
        'expense' => PropertyExpense::class,
        'contract' => SubscriptionContract::class,
    ];

    private int $actorId;

    /** Permanently delete one record of the given admin type. */
    public function purge(Model $record, int $actorId): void
    {
        $this->actorId = $actorId;
        $label = class_basename($record) . ' #' . $record->getKey();

        DB::transaction(function () use ($record) {
            $ids = [$record->getKey()];

            match (true) {
                $record instanceof PropertyOwner => $this->owners($ids),
                $record instanceof PropertyManager => $this->managers($ids),
                $record instanceof Property => $this->properties($ids),
                $record instanceof Tenant => $this->tenants($ids),
                $record instanceof TenantApplication => $this->applications($ids),
                $record instanceof Lease => $this->leases($ids, freeUnits: true),
                $record instanceof RentInvoice => $this->invoices($ids),
                $record instanceof RentPayment => $this->payments($ids),
                $record instanceof PropertyExpense => $this->wipe(PropertyExpense::class, 'id', $ids),
                $record instanceof SubscriptionContract => $this->contracts($ids),
            };
        });

        AuditObserver::record('purged', null, ['record' => $label], $actorId);
    }

    /* ---------------------------- People ---------------------------- */

    private function owners(array $ids): void
    {
        $this->properties($this->ids(Property::class, 'property_owner_id', $ids));
        $this->documents(PropertyOwner::class, $ids);
        $userIds = $this->ids(PropertyOwner::class, 'id', $ids, 'user_id');
        $this->wipe(PropertyOwner::class, 'id', $ids);
        $this->users($userIds);
    }

    private function managers(array $ids): void
    {
        $this->wipe(PropertyManagerAssignment::class, 'property_manager_id', $ids);
        $userIds = $this->ids(PropertyManager::class, 'id', $ids, 'user_id');
        $this->wipe(PropertyManager::class, 'id', $ids);
        $this->users($userIds);
    }

    private function tenants(array $ids): void
    {
        $this->leases($this->ids(Lease::class, 'tenant_id', $ids), freeUnits: true);
        $this->applications($this->ids(TenantApplication::class, 'tenant_id', $ids));
        $this->maintenance('tenant_id', $ids);
        // Stragglers not tied to one of the tenant's leases.
        $this->payments($this->ids(RentPayment::class, 'tenant_id', $ids));
        $this->invoices($this->ids(RentInvoice::class, 'tenant_id', $ids));
        $this->wipe(PaymentIntent::class, 'tenant_id', $ids);
        $this->wipe(LeaseRequest::class, 'tenant_id', $ids);
        $this->documents(Tenant::class, $ids);

        $userIds = $this->ids(Tenant::class, 'id', $ids, 'user_id');
        $this->wipe(Tenant::class, 'id', $ids);
        $this->users($userIds);
    }

    /**
     * Remove login accounts that belonged only to the purged profile. Admins,
     * and users still linked to another owner/manager/tenant/staff profile, stay.
     */
    private function users(array $ids): void
    {
        $ids = User::whereIn('id', array_filter($ids))
            ->where('role', '!=', 'admin')
            ->whereKeyNot($this->actorId)
            ->pluck('id')->all();

        $ids = array_values(array_filter($ids, fn ($id) => ! PropertyOwner::where('user_id', $id)->exists()
            && ! PropertyManager::where('user_id', $id)->exists()
            && ! Tenant::withTrashed()->where('user_id', $id)->exists()
            && ! FieldStaff::where('user_id', $id)->exists()));

        if (! $ids) {
            return;
        }

        // rent_payments.recorded_by cascades on user delete; keep other people's
        // payments by handing them to the admin doing the purge.
        RentPayment::withTrashed()->whereIn('recorded_by', $ids)->update(['recorded_by' => $this->actorId]);

        $this->contracts($this->ids(SubscriptionContract::class, 'user_id', $ids));
        DB::table('notifications')->where('notifiable_type', User::class)->whereIn('notifiable_id', $ids)->delete();
        DB::table('personal_access_tokens')->where('tokenable_type', User::class)->whereIn('tokenable_id', $ids)->delete();
        DB::table('sessions')->whereIn('user_id', $ids)->delete();
        User::whereIn('id', $ids)->get()->each->delete();
    }

    /* --------------------------- Buildings --------------------------- */

    private function properties(array $ids): void
    {
        if (! $ids) {
            return;
        }

        $this->buildings($this->ids(Building::class, 'property_id', $ids));
        $this->wipe(PropertyManagerAssignment::class, 'property_id', $ids);
        $this->wipe(PropertyExpense::class, 'property_id', $ids);
        $this->wipe(PropertyImage::class, 'property_id', $ids);
        $this->wipe(PropertyUserRole::class, 'property_id', $ids);
        $this->wipe(AgentCommission::class, 'property_id', $ids);
        $this->wipe(LeaseTemplate::class, 'property_id', $ids);
        $this->documents(Property::class, $ids);
        $this->wipe(Property::class, 'id', $ids);
    }

    private function buildings(array $ids): void
    {
        if (! $ids) {
            return;
        }

        $this->units($this->ids(Unit::class, 'building_id', $ids));
        $this->floors($this->ids(Floor::class, 'building_id', $ids));
        $this->wipe(BuildingImage::class, 'building_id', $ids);
        $this->wipe(Building::class, 'id', $ids);
    }

    private function floors(array $ids): void
    {
        if (! $ids) {
            return;
        }

        $this->units($this->ids(Unit::class, 'floor_id', $ids));
        $this->wipe(FloorImage::class, 'floor_id', $ids);
        $this->wipe(Floor::class, 'id', $ids);
    }

    private function units(array $ids): void
    {
        if (! $ids) {
            return;
        }

        $this->leases($this->ids(Lease::class, 'unit_id', $ids));
        $this->applications($this->ids(TenantApplication::class, 'unit_id', $ids));
        $this->maintenance('unit_id', $ids);
        $this->inspections($this->ids(Inspection::class, 'unit_id', $ids));
        $this->payments($this->ids(RentPayment::class, 'unit_id', $ids));
        $this->invoices($this->ids(RentInvoice::class, 'unit_id', $ids));
        $this->wipe(PaymentIntent::class, 'unit_id', $ids);
        $this->wipe(UnitImage::class, 'unit_id', $ids);
        $this->documents(Unit::class, $ids);
        $this->wipe(Unit::class, 'id', $ids);
    }

    /* ---------------------------- Tenancy ---------------------------- */

    private function applications(array $ids): void
    {
        $this->wipe(TenantApplication::class, 'id', $ids);
    }

    private function leases(array $ids, bool $freeUnits = false): void
    {
        if (! $ids) {
            return;
        }

        $unitIds = $this->ids(Lease::class, 'id', $ids, 'unit_id');

        $this->payments($this->ids(RentPayment::class, 'lease_id', $ids), refresh: false);
        $this->invoices($this->ids(RentInvoice::class, 'lease_id', $ids));
        $this->wipe(PaymentIntent::class, 'lease_id', $ids);
        $this->inspections($this->ids(Inspection::class, 'lease_id', $ids));
        $this->wipe(LeaseRequest::class, 'lease_id', $ids);
        $this->wipe(LeaseContract::class, 'lease_id', $ids);
        $this->wipe(AgentCommission::class, 'lease_id', $ids);
        $this->documents(Lease::class, $ids);
        $this->wipe(Lease::class, 'id', $ids);

        if ($freeUnits) {
            // Put units back on the market unless another live lease still holds them.
            foreach ($unitIds as $unitId) {
                $held = Lease::where('unit_id', $unitId)
                    ->whereIn('status', [LeaseLifecycle::AWAITING_TENANT, LeaseLifecycle::AWAITING_ACTIVATION, LeaseLifecycle::ACTIVE])
                    ->exists();
                if (! $held) {
                    Unit::whereKey($unitId)->whereIn('status', ['reserved', 'occupied'])->update(['status' => 'available']);
                }
            }
        }
    }

    private function invoices(array $ids): void
    {
        if (! $ids) {
            return;
        }

        $this->wipe(RentPaymentAllocation::class, 'rent_invoice_id', $ids);
        $this->wipe(RentInvoice::class, 'id', $ids);
    }

    private function payments(array $ids, bool $refresh = true): void
    {
        if (! $ids) {
            return;
        }

        $touched = $this->ids(RentPaymentAllocation::class, 'rent_payment_id', $ids, 'rent_invoice_id');
        $this->wipe(RentPaymentAllocation::class, 'rent_payment_id', $ids);
        $this->wipe(RentPayment::class, 'id', $ids);

        // Invoices that stay behind must show the money as no longer received.
        if ($refresh) {
            RentInvoice::whereIn('id', $touched)->get()->each(fn ($invoice) => InvoiceBalance::refresh($invoice));
        }
    }

    private function inspections(array $ids): void
    {
        if (! $ids) {
            return;
        }

        $this->wipe(InspectionMedia::class, 'inspection_id', $ids);
        InspectionItem::whereIn('inspection_id', $ids)->update(['baseline_item_id' => null]);
        $this->wipe(InspectionItem::class, 'inspection_id', $ids);
        Inspection::whereIn('baseline_inspection_id', $ids)->update(['baseline_inspection_id' => null]);
        $this->wipe(Inspection::class, 'id', $ids);
    }

    private function maintenance(string $column, array $ids): void
    {
        $requestIds = $this->ids(MaintenanceRequest::class, $column, $ids);
        $this->wipe(MaintenanceRequestActivity::class, 'maintenance_request_id', $requestIds);
        $this->wipe(MaintenanceRequest::class, 'id', $requestIds);
    }

    /* -------------------------- Subscriptions ------------------------- */

    private function contracts(array $ids): void
    {
        if (! $ids) {
            return;
        }

        $this->wipe(SubscriptionPayment::class, 'subscription_contract_id', $ids);
        $this->wipe(SubscriptionInvoice::class, 'subscription_contract_id', $ids);
        $this->documents(SubscriptionContract::class, $ids);
        $this->wipe(SubscriptionContract::class, 'id', $ids);
    }

    /* ----------------------------- Helpers ---------------------------- */

    private function documents(string $ownerClass, array $ids): void
    {
        if (! $ids) {
            return;
        }

        Document::withTrashed()->where('documentable_type', $ownerClass)->whereIn('documentable_id', $ids)
            ->get()->each->forceDelete();
    }

    /** Ids (or another column) of $class rows where $column is in $values, trashed rows included. */
    private function ids(string $class, string $column, array $values, string $pluck = 'id'): array
    {
        if (! $values) {
            return [];
        }

        return $this->query($class)->whereIn($column, $values)->pluck($pluck)->filter()->unique()->values()->all();
    }

    /** Permanently delete $class rows where $column is in $values. */
    private function wipe(string $class, string $column, array $values): void
    {
        if (! $values) {
            return;
        }

        $query = $this->query($class)->whereIn($column, $values);
        $model = new $class;
        $soft = $this->usesSoftDeletes($class);

        // Models that own files go one by one so their files are deleted too.
        if (method_exists($model, 'uploadColumns') && $model->uploadColumns()) {
            $query->get()->each(fn (Model $row) => $soft ? $row->forceDelete() : $row->delete());

            return;
        }

        $soft ? $query->forceDelete() : $query->delete();
    }

    private function query(string $class): Builder
    {
        return $this->usesSoftDeletes($class) ? $class::withTrashed() : $class::query();
    }

    private function usesSoftDeletes(string $class): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($class), true);
    }
}
