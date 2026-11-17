<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Lease;
use App\Models\Property;
use App\Models\PropertyManager;
use App\Models\PropertyOwner;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One lease workflow for every portal:
 * draft → pending_tenant_acceptance → pending_activation → active.
 */
class LeaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $managerUser;
    private User $tenantUser;
    private Tenant $tenant;
    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $ownerUser = User::factory()->create(['role' => 'owner']);
        $this->managerUser = User::factory()->create(['role' => 'manager']);
        $this->tenantUser = User::factory()->create(['role' => 'tenant']);

        $owner = PropertyOwner::forceCreate(['user_id' => $ownerUser->id, 'owner_number' => 'O1', 'name' => 'Owner', 'status' => 'active', 'created_by' => $this->admin->id]);
        $manager = PropertyManager::forceCreate(['user_id' => $this->managerUser->id, 'manager_number' => 'M1', 'status' => 'active', 'created_by' => $this->admin->id]);
        $property = Property::forceCreate(['property_owner_id' => $owner->id, 'property_code' => 'P1', 'name' => 'Kacyiru Heights', 'property_type' => 'residential', 'status' => 'active', 'created_by' => $this->admin->id]);
        $property->managerAssignments()->forceCreate(['property_manager_id' => $manager->id, 'start_date' => now()->toDateString(), 'status' => 'active', 'assigned_by' => $this->admin->id]);
        $building = Building::forceCreate(['property_id' => $property->id, 'building_code' => 'B1', 'name' => 'Block A', 'status' => 'active']);
        $this->unit = Unit::forceCreate(['building_id' => $building->id, 'unit_number' => '101', 'unit_type' => 'apartment', 'rent_amount' => 350000, 'status' => 'available']);
        $this->tenant = Tenant::forceCreate(['user_id' => $this->tenantUser->id, 'tenant_number' => 'T1', 'full_name' => 'Tina Tenant', 'email' => $this->tenantUser->email, 'phone' => '0788111111', 'status' => 'active']);
    }

    public function test_full_flow_from_the_manager_portal(): void
    {
        $lease = $this->draft();

        // Tenant can't accept a lease that hasn't been sent.
        $this->actingAs($this->tenantUser)->post(route('tenant-portal.leases.accept', $lease), ['accept_terms' => 1])->assertSessionHasErrors('lease');

        // 1. Send.
        $this->actingAs($this->managerUser)->patch(route('property-manager.leases.send-for-signature', $lease))->assertRedirect();
        $this->assertSame('pending_tenant_acceptance', $lease->fresh()->status);
        $this->assertSame('reserved', $this->unit->fresh()->status);
        $this->assertSame('Your lease is ready to review', $this->tenantUser->notifications()->first()?->data['title']);
        $this->actingAs($this->tenantUser)->get(route('tenant-portal.leases.show', $lease))->assertOk()->assertSee(route('tenant-portal.leases.accept', $lease), false);

        // Terms are locked while the tenant decides.
        $this->actingAs($this->managerUser)->get(route('property-manager.leases.edit', $lease))->assertForbidden();

        // 2. Tenant accepts.
        $this->actingAs($this->tenantUser)->post(route('tenant-portal.leases.accept', $lease), ['accept_terms' => 1])->assertRedirect();
        $lease->refresh();
        $this->assertSame('pending_activation', $lease->status);
        $this->assertNotNull($lease->tenant_accepted_at);
        $this->actingAs($this->tenantUser)->post(route('tenant-portal.leases.accept', $lease), ['accept_terms' => 1])->assertSessionHasErrors('lease');

        // Activation is blocked until invoices are paid and move-in confirmed.
        $this->actingAs($this->managerUser)->patch(route('property-manager.leases.activate', $lease), ['move_in_confirmed' => 1])->assertSessionHasErrors('lease');
        $this->assertSame('pending_activation', $lease->fresh()->status);

        // 3. First invoices (repeatable without duplicates).
        $this->actingAs($this->managerUser)->post(route('property-manager.leases.generate-required-invoices', $lease))->assertRedirect();
        $this->actingAs($this->managerUser)->post(route('property-manager.leases.generate-required-invoices', $lease));
        $this->assertSame(['deposit', 'rent'], RentInvoice::where('lease_id', $lease->id)->orderBy('invoice_type')->pluck('invoice_type')->all());

        // Deposit paid, rent not yet → still blocked.
        $this->pay($lease, 'deposit');
        $this->actingAs($this->managerUser)->patch(route('property-manager.leases.activate', $lease), ['move_in_confirmed' => 1])->assertSessionHasErrors('lease');

        // 4. Rent paid; move-in recorded; activate.
        $this->pay($lease, 'rent');
        $this->actingAs($this->managerUser)->patch(route('property-manager.leases.confirm-move-in', $lease))->assertRedirect();
        $this->actingAs($this->managerUser)->get(route('property-manager.leases.show', $lease))->assertOk()->assertSee('Everything is ready');
        $this->actingAs($this->managerUser)->patch(route('property-manager.leases.activate', $lease))->assertRedirect();

        $lease->refresh();
        $this->assertSame('active', $lease->status);
        $this->assertNotNull($lease->activated_at);
        $this->assertSame('occupied', $this->unit->fresh()->status);
        $this->assertTrue($this->tenantUser->notifications()->get()->contains(fn ($n) => $n->data['title'] === 'Your lease is now active'));
    }

    public function test_full_flow_from_the_admin_portal_including_recall(): void
    {
        $lease = $this->draft();

        $this->actingAs($this->admin)->post(route('admin.leases.send-to-tenant', $lease))->assertRedirect();
        $this->assertSame('pending_tenant_acceptance', $lease->fresh()->status);

        // Recall puts it back to draft and frees the unit.
        $this->actingAs($this->admin)->post(route('admin.leases.recall', $lease))->assertRedirect();
        $this->assertSame('draft', $lease->fresh()->status);
        $this->assertSame('available', $this->unit->fresh()->status);

        $this->actingAs($this->admin)->post(route('admin.leases.send-to-tenant', $lease));
        $this->actingAs($this->tenantUser)->post(route('tenant-portal.leases.accept', $lease), ['accept_terms' => 1]);
        $this->actingAs($this->admin)->post(route('admin.leases.recall', $lease))->assertSessionHasErrors('lease');

        $this->actingAs($this->admin)->post(route('admin.leases.generate-required-invoices', $lease))->assertRedirect();
        $this->pay($lease, 'deposit');
        $this->pay($lease, 'rent');
        $this->actingAs($this->admin)->post(route('admin.leases.activate', $lease))->assertSessionHasErrors('lease'); // move-in missing
        $this->actingAs($this->admin)->post(route('admin.leases.confirm-move-in', $lease))->assertRedirect();
        $this->actingAs($this->admin)->post(route('admin.leases.activate', $lease))->assertRedirect();

        $this->assertSame('active', $lease->fresh()->status);
        $this->assertSame('occupied', $this->unit->fresh()->status);
    }

    public function test_future_start_date_cannot_be_activated_early(): void
    {
        $lease = $this->draft(['start_date' => now()->addWeek()->toDateString(), 'end_date' => now()->addYear()->toDateString()]);
        $this->actingAs($this->managerUser)->patch(route('property-manager.leases.send-for-signature', $lease));
        $this->actingAs($this->tenantUser)->post(route('tenant-portal.leases.accept', $lease), ['accept_terms' => 1]);
        $this->actingAs($this->managerUser)->post(route('property-manager.leases.generate-required-invoices', $lease));
        $this->pay($lease, 'deposit');
        $this->pay($lease, 'rent');

        $this->actingAs($this->managerUser)->patch(route('property-manager.leases.activate', $lease), ['move_in_confirmed' => 1])
            ->assertSessionHasErrors(['lease' => 'The lease starts on ' . now()->addWeek()->format('d M Y') . '; it can be activated from that date.']);
    }

    public function test_cannot_send_when_another_lease_holds_the_unit(): void
    {
        $this->draft(['lease_number' => 'L-OLD', 'status' => 'active']);
        $this->unit->update(['status' => 'occupied']);
        $lease = $this->draft(['lease_number' => 'L-NEW']);

        $this->actingAs($this->managerUser)->patch(route('property-manager.leases.send-for-signature', $lease))->assertSessionHasErrors('lease');
        $this->assertSame('draft', $lease->fresh()->status);
    }

    public function test_admin_approval_creates_a_draft_not_an_active_lease(): void
    {
        $application = TenantApplication::forceCreate([
            'tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id, 'application_number' => 'APP-1',
            'application_date' => now()->toDateString(), 'status' => 'pending', 'currency' => 'RWF',
        ]);

        $this->actingAs($this->admin)->post(route('admin.tenant-applications.approve', $application), [
            'start_date' => now()->subDay()->toDateString(), 'end_date' => now()->addYear()->toDateString(),
            'monthly_rent' => 350000, 'deposit_amount' => 700000, 'rent_due_day' => 5, 'payment_frequency' => 'monthly',
        ])->assertRedirect();

        $lease = Lease::firstOrFail();
        $this->assertSame('draft', $lease->status);
        $this->assertSame('available', $this->unit->fresh()->status);
    }

    public function test_repair_migration_unsticks_old_records(): void
    {
        $stuck = $this->draft(['lease_number' => 'L-STUCK', 'tenant_accepted_at' => now()]);
        $active = $this->draft(['lease_number' => 'L-ACT', 'status' => 'active', 'start_date' => now()->subYears(3)->toDateString(), 'end_date' => now()->subYears(2)->toDateString()]);
        $this->unit->update(['status' => 'available']);

        (require database_path('migrations/2026_11_18_200000_repair_lease_workflow_states.php'))->up();

        $this->assertSame('pending_activation', $stuck->fresh()->status);
        $this->assertSame('occupied', $this->unit->fresh()->status);
        $this->assertSame('active', $active->fresh()->status);
    }

    /* ------------------------------------------------------------------ */

    private function draft(array $overrides = []): Lease
    {
        return Lease::forceCreate($overrides + [
            'tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id, 'lease_number' => 'L-1', 'currency' => 'RWF', 'rent_due_day' => 5,
            'start_date' => now()->toDateString(), 'end_date' => now()->addYear()->toDateString(),
            'monthly_rent' => 350000, 'deposit_amount' => 700000, 'payment_frequency' => 'monthly', 'status' => 'draft',
        ]);
    }

    private function pay(Lease $lease, string $type): void
    {
        $invoice = RentInvoice::where('lease_id', $lease->id)->where('invoice_type', $type)->firstOrFail();
        $payment = RentPayment::forceCreate([
            'payment_number' => 'PAY-' . $type, 'tenant_id' => $lease->tenant_id, 'lease_id' => $lease->id, 'unit_id' => $lease->unit_id,
            'recorded_by' => $this->managerUser->id, 'payment_method' => 'mobile_money', 'amount' => $invoice->total_amount,
            'currency' => 'RWF', 'payment_date' => now()->toDateString(), 'status' => 'confirmed',
        ]);
        $payment->allocations()->create(['rent_invoice_id' => $invoice->id, 'amount' => $invoice->total_amount, 'allocated_at' => now()->toDateString()]);
        \App\Services\Payments\InvoiceBalance::refresh($invoice);
    }
}
