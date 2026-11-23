<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Floor;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\PropertyExpense;
use App\Models\PropertyOwner;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\RentPaymentAllocation;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\Unit;
use App\Models\UnitImage;
use App\Models\User;
use App\Support\FileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** "Delete permanently" removes demo/test data with everything linked to it. */
class DataPurgeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $ownerUser;
    private User $tenantUser;
    private PropertyOwner $owner;
    private Property $property;
    private Unit $unit;
    private Tenant $tenant;
    private Lease $lease;
    private RentInvoice $invoice;
    private RentPayment $payment;
    private string $imagePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->ownerUser = User::factory()->create(['role' => 'owner']);
        $this->tenantUser = User::factory()->create(['role' => 'tenant']);

        $this->owner = PropertyOwner::forceCreate(['user_id' => $this->ownerUser->id, 'owner_number' => 'O1', 'name' => 'Demo Owner', 'status' => 'active', 'created_by' => $this->admin->id]);
        $this->property = Property::forceCreate(['property_owner_id' => $this->owner->id, 'property_code' => 'P1', 'name' => 'Demo Heights', 'property_type' => 'residential', 'status' => 'active', 'created_by' => $this->admin->id]);
        $building = Building::forceCreate(['property_id' => $this->property->id, 'building_code' => 'B1', 'name' => 'Block A', 'status' => 'active']);
        $floor = Floor::forceCreate(['building_id' => $building->id, 'floor_number' => 1, 'name' => 'First', 'status' => 'active']);
        $this->unit = Unit::forceCreate(['building_id' => $building->id, 'floor_id' => $floor->id, 'unit_number' => '101', 'unit_type' => 'apartment', 'rent_amount' => 100000, 'status' => 'occupied']);

        $this->imagePath = FileUpload::put('units/test', 'purge-test.jpg', 'fake image');
        UnitImage::forceCreate(['unit_id' => $this->unit->id, 'image_path' => $this->imagePath]);

        $this->tenant = Tenant::forceCreate(['user_id' => $this->tenantUser->id, 'tenant_number' => 'T1', 'full_name' => 'Demo Tenant', 'email' => $this->tenantUser->email, 'phone' => '0788111111', 'status' => 'active']);
        $application = TenantApplication::forceCreate(['tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id, 'application_number' => 'APP-1', 'application_date' => now()->toDateString(), 'status' => 'approved']);
        $this->lease = Lease::forceCreate([
            'tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id, 'tenant_application_id' => $application->id, 'lease_number' => 'L-1',
            'currency' => 'RWF', 'rent_due_day' => 5, 'start_date' => now()->toDateString(), 'end_date' => now()->addYear()->toDateString(),
            'monthly_rent' => 100000, 'deposit_amount' => 0, 'payment_frequency' => 'monthly', 'status' => 'active',
        ]);
        $this->invoice = RentInvoice::forceCreate([
            'lease_id' => $this->lease->id, 'tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id, 'invoice_number' => 'INV-1',
            'billing_period_start' => now()->toDateString(), 'billing_period_end' => now()->addMonth()->toDateString(),
            'issue_date' => now()->toDateString(), 'due_date' => now()->toDateString(),
            'subtotal' => 100000, 'total_amount' => 100000, 'amount_paid' => 100000, 'balance_due' => 0, 'status' => 'paid',
        ]);
        $this->payment = RentPayment::forceCreate([
            'payment_number' => 'PAY-1', 'tenant_id' => $this->tenant->id, 'lease_id' => $this->lease->id, 'unit_id' => $this->unit->id,
            'recorded_by' => $this->admin->id, 'payment_method' => 'cash', 'amount' => 100000, 'currency' => 'RWF',
            'payment_date' => now()->toDateString(), 'status' => 'confirmed',
        ]);
        $this->payment->allocations()->create(['rent_invoice_id' => $this->invoice->id, 'amount' => 100000, 'allocated_at' => now()->toDateString()]);

        MaintenanceRequest::forceCreate(['request_number' => 'MR-1', 'tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id, 'title' => 'Leak', 'description' => 'Tap leaks', 'category' => 'plumbing']);
        PropertyExpense::forceCreate(['property_id' => $this->property->id, 'expense_number' => 'EXP-1', 'category' => 'repairs', 'title' => 'Paint',
            'amount' => 5000, 'currency' => 'RWF', 'expense_date' => now()->toDateString(), 'status' => 'paid', 'recorded_by' => $this->admin->id]);
    }

    private function purge(string $type, int $id, ?string $confirm = 'DELETE')
    {
        return $this->actingAs($this->admin)->delete(route('admin.purge', ['type' => $type, 'id' => $id]), ['confirm' => $confirm]);
    }

    public function test_purging_an_owner_removes_the_whole_tree_logins_and_files(): void
    {
        $this->assertTrue(FileUpload::exists($this->imagePath));

        $this->purge('owner', $this->owner->id)->assertRedirect(route('admin.owners.index'))->assertSessionHas('success');

        $this->assertModelMissing($this->owner);
        $this->assertModelMissing($this->ownerUser);
        foreach ([Property::class, Building::class, Floor::class, Unit::class, Tenant::class, TenantApplication::class, Lease::class,
            RentInvoice::class, RentPayment::class, RentPaymentAllocation::class, MaintenanceRequest::class, PropertyExpense::class] as $class) {
            $query = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($class), true) ? $class::withTrashed() : $class::query();
            if ($class === Tenant::class) {
                // Tenants are people, not part of the property: they stay.
                $this->assertSame(1, $query->count());
                continue;
            }
            $this->assertSame(0, $query->count(), "$class rows should be gone");
        }
        $this->assertSame(0, UnitImage::count());
        $this->assertFalse(FileUpload::exists($this->imagePath), 'uploaded file should be removed');
        $this->assertModelExists($this->admin);
        $this->assertDatabaseHas('audit_logs', ['event' => 'purged', 'user_id' => $this->admin->id]);
    }

    public function test_purging_a_tenant_removes_their_tenancy_and_login_but_keeps_the_property(): void
    {
        $this->purge('tenant', $this->tenant->id)->assertRedirect(route('admin.tenants.index'));

        $this->assertSame(0, Tenant::withTrashed()->count());
        $this->assertModelMissing($this->tenantUser);
        $this->assertSame(0, Lease::withTrashed()->count());
        $this->assertSame(0, RentInvoice::withTrashed()->count());
        $this->assertSame(0, RentPayment::withTrashed()->count());
        $this->assertSame(0, MaintenanceRequest::withTrashed()->count());
        $this->assertModelExists($this->unit);
        $this->assertSame('available', $this->unit->fresh()->status);
        $this->assertTrue(FileUpload::exists($this->imagePath));
    }

    public function test_purging_a_payment_reopens_the_invoice_and_soft_deleted_records_can_be_purged(): void
    {
        $this->purge('payment', $this->payment->id)->assertRedirect(route('admin.payments.index'));

        $this->assertSame(0, RentPayment::withTrashed()->count());
        $this->assertEquals(0, (float) $this->invoice->fresh()->amount_paid);
        $this->assertNotSame('paid', $this->invoice->fresh()->status);

        // A record that was only soft-deleted earlier can still be removed for good.
        $this->invoice->delete();
        $this->purge('invoice', $this->invoice->id)->assertRedirect(route('admin.rent-invoices.index'));
        $this->assertSame(0, RentInvoice::withTrashed()->count());
    }

    public function test_admin_pages_show_the_permanent_delete_button(): void
    {
        $pages = [
            route('admin.owners.index'), route('admin.owners.show', $this->owner),
            route('admin.properties.index'), route('admin.properties.show', $this->property),
            route('admin.tenants.index'), route('admin.tenants.show', $this->tenant),
            route('admin.leases.show', $this->lease), route('admin.rent-invoices.show', $this->invoice),
            route('admin.payments.show', $this->payment),
        ];

        foreach ($pages as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk()->assertSee('Delete permanently');
        }
    }

    public function test_admin_deletes_a_service_provider_and_its_jobs_are_reopened(): void
    {
        $tech = User::factory()->create(['role' => 'technician']);
        $provider = \App\Models\ServiceProvider::create([
            'user_id' => $tech->id, 'company_name' => 'Demo Plumbers', 'phone' => '+250788000222',
            'categories' => ['plumbing'], 'status' => 'active', 'created_by' => $this->admin->id,
        ]);
        $job = MaintenanceRequest::firstOrFail();
        $job->update(['service_provider_id' => $provider->id, 'assigned_to' => $tech->id, 'status' => 'in_progress']);

        $this->actingAs($this->ownerUser)->delete(route('service-providers.destroy', $provider))->assertForbidden();

        $this->actingAs($this->admin)->get(route('service-providers.index'))->assertOk()->assertSee(route('service-providers.destroy', $provider), false);
        $this->actingAs($this->admin)->delete(route('service-providers.destroy', $provider))
            ->assertRedirect(route('service-providers.index'))->assertSessionHas('success');

        $this->assertModelMissing($provider);
        $this->assertModelMissing($tech);
        $job->refresh();
        $this->assertNull($job->service_provider_id);
        $this->assertNull($job->assigned_to);
        $this->assertSame('open', $job->status);
    }

    public function test_purge_needs_typed_confirmation_and_an_admin(): void
    {
        $this->purge('lease', $this->lease->id, 'yes')->assertSessionHasErrors('confirm');
        $this->assertModelExists($this->lease);

        $this->actingAs($this->ownerUser)->delete(route('admin.purge', ['type' => 'lease', 'id' => $this->lease->id]), ['confirm' => 'DELETE'])->assertForbidden();
        $this->assertModelExists($this->lease);

        $this->actingAs($this->admin)->delete('/admin/purge/user/' . $this->admin->id, ['confirm' => 'DELETE'])->assertNotFound();
    }
}
