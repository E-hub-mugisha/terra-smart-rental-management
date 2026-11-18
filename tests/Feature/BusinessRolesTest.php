<?php

namespace Tests\Feature;

use App\Models\AgentCommission;
use App\Models\Building;
use App\Models\Lease;
use App\Models\LeaseContract;
use App\Models\LeaseTemplate;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\PropertyManager;
use App\Models\PropertyOwner;
use App\Models\PropertyUserRole;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\ServiceProvider;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Contract builder, service providers & technicians, accountants, agents and reports. */
class BusinessRolesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $managerUser;
    private User $tenantUser;
    private Property $property;
    private Property $otherProperty;
    private Unit $unit;
    private Tenant $tenant;
    private Lease $lease;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $ownerUser = User::factory()->create(['role' => 'owner']);
        $this->managerUser = User::factory()->create(['role' => 'manager']);
        $this->tenantUser = User::factory()->create(['role' => 'tenant']);

        $owner = PropertyOwner::forceCreate(['user_id' => $ownerUser->id, 'owner_number' => 'O1', 'name' => 'Olive Owner', 'status' => 'active', 'created_by' => $this->admin->id]);
        $manager = PropertyManager::forceCreate(['user_id' => $this->managerUser->id, 'manager_number' => 'M1', 'status' => 'active', 'created_by' => $this->admin->id]);
        $this->property = Property::forceCreate(['property_owner_id' => $owner->id, 'property_code' => 'P1', 'name' => 'Kacyiru Heights', 'property_type' => 'residential', 'status' => 'active', 'district' => 'Gasabo', 'created_by' => $this->admin->id]);
        $this->property->managerAssignments()->forceCreate(['property_manager_id' => $manager->id, 'start_date' => now()->toDateString(), 'status' => 'active', 'assigned_by' => $this->admin->id]);
        $this->otherProperty = Property::forceCreate(['property_owner_id' => $owner->id, 'property_code' => 'P2', 'name' => 'Other Place', 'property_type' => 'residential', 'status' => 'active', 'created_by' => $this->admin->id]);

        $building = Building::forceCreate(['property_id' => $this->property->id, 'building_code' => 'B1', 'name' => 'Block A', 'status' => 'active']);
        $this->unit = Unit::forceCreate(['building_id' => $building->id, 'unit_number' => '101', 'unit_type' => 'apartment', 'rent_amount' => 400000, 'status' => 'occupied']);
        $this->tenant = Tenant::forceCreate(['user_id' => $this->tenantUser->id, 'tenant_number' => 'T1', 'full_name' => 'Tina Tenant', 'email' => $this->tenantUser->email,
            'phone' => '0788111111', 'status' => 'active', 'id_type' => 'national_id', 'id_number' => '1199080012345678']);
        $this->lease = Lease::forceCreate([
            'tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id, 'lease_number' => 'L1', 'currency' => 'RWF', 'rent_due_day' => 5,
            'start_date' => now()->subMonth()->toDateString(), 'end_date' => now()->addYear()->toDateString(),
            'monthly_rent' => 400000, 'deposit_amount' => 800000, 'payment_frequency' => 'monthly', 'status' => 'active',
        ]);
        \Illuminate\Notifications\DatabaseNotification::query()->delete();
    }

    /* ---------------------------- Contracts ---------------------------- */

    public function test_contract_is_generated_from_template_edited_and_finalised(): void
    {
        $template = LeaseTemplate::where('is_default', true)->firstOrFail();

        $this->actingAs($this->managerUser)->get(route('contracts.edit', $this->lease))->assertOk()->assertSee('Standard residential lease');
        $this->actingAs($this->managerUser)->post(route('contracts.generate', $this->lease), [
            'lease_template_id' => $template->id, 'notice_period_days' => 60, 'special_conditions' => 'One small pet allowed.',
        ])->assertRedirect(route('contracts.edit', $this->lease));

        $contract = LeaseContract::firstOrFail();
        $text = collect($contract->clauses)->pluck('body')->implode("\n");
        $this->assertStringContainsString('Tina Tenant', $text);
        $this->assertStringContainsString('1199080012345678', $text);
        $this->assertStringContainsString('RWF 400,000', $text);
        $this->assertStringContainsString('RWF 800,000', $text);
        $this->assertStringContainsString('60 days', $text);
        $this->assertStringContainsString('One small pet allowed.', $text);
        $this->assertStringNotContainsString('{{', $text);

        // Tenant can't see the draft.
        $this->actingAs($this->tenantUser)->get(route('contracts.show', $this->lease))->assertNotFound();

        $clauses = collect($contract->clauses)->map(fn ($c, $i) => $c + ['remove' => $i === 0 ? 1 : 0])->all();
        $clauses[] = ['title' => 'Parking', 'body' => 'One parking space is included.'];
        $this->actingAs($this->managerUser)->put(route('contracts.update', $this->lease), ['clauses' => $clauses, 'then' => 'finalize'])->assertRedirect();

        $contract->refresh();
        $this->assertTrue($contract->isFinal());
        $this->assertSame('Parking', collect($contract->clauses)->last()['title']);
        $this->assertNotSame('Parties', $contract->clauses[0]['title']);

        $this->actingAs($this->tenantUser)->get(route('contracts.show', $this->lease))->assertOk()->assertSee('One parking space is included.');
        $this->actingAs($this->managerUser)->post(route('contracts.generate', $this->lease), ['lease_template_id' => $template->id, 'notice_period_days' => 30])
            ->assertSessionHasErrors('contract');
        $this->actingAs($this->tenantUser)->get(route('contracts.edit', $this->lease))->assertForbidden();
    }

    public function test_template_management_permissions(): void
    {
        $this->actingAs($this->tenantUser)->get(route('lease-templates.index'))->assertForbidden();

        $this->actingAs($this->managerUser)->post(route('lease-templates.store'), [
            'name' => 'Commercial', 'is_default' => 1, 'clauses' => [['title' => 'Use', 'body' => 'Office use only for {{tenant_name}}.'], ['title' => '', 'body' => '']],
        ])->assertRedirect();
        $tpl = LeaseTemplate::where('name', 'Commercial')->firstOrFail();
        $this->assertFalse($tpl->is_default, 'Only admins can set the default.');
        $this->assertSame($this->property->id, $tpl->property_id);
        $this->assertCount(1, $tpl->clauses);

        $default = LeaseTemplate::where('is_default', true)->first();
        $this->actingAs($this->managerUser)->get(route('lease-templates.edit', $default))->assertForbidden();
        $this->actingAs($this->admin)->get(route('lease-templates.edit', $default))->assertOk();
    }

    /* ---------------------- Service providers ---------------------- */

    public function test_provider_assignment_technician_update_and_tenant_confirmation(): void
    {
        $this->actingAs($this->managerUser)->post(route('service-providers.store'), [
            'company_name' => 'FixIt Plumbing', 'phone' => '0788999999', 'email' => 'fixit@example.com', 'categories' => ['plumbing'], 'create_login' => 1,
        ])->assertSessionHasNoErrors();

        $provider = ServiceProvider::firstOrFail();
        $tech = $provider->user;
        $this->assertSame('technician', $tech->role);

        $request = MaintenanceRequest::forceCreate([
            'request_number' => 'MR-1', 'tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id, 'title' => 'Leaking tap',
            'description' => 'Kitchen tap leaks', 'category' => 'plumbing', 'priority' => 'high', 'status' => 'submitted', 'created_by' => $this->tenantUser->id,
        ]);
        \Illuminate\Notifications\DatabaseNotification::query()->delete();

        $this->actingAs($this->managerUser)->post(route('maintenance.assign', $request), ['service_provider_id' => $provider->id])->assertRedirect();
        // Regression: the manager maintenance page used to link to route names that do not exist.
        $this->actingAs($this->managerUser)->get(route('property-manager.maintenance-requests.show', $request))->assertOk()->assertSee('FixIt Plumbing');
        $request->refresh();
        $this->assertSame($provider->id, $request->service_provider_id);
        $this->assertSame($tech->id, $request->assigned_to);
        $this->assertStringContainsString('New job', $tech->notifications()->first()->data['title']);
        $this->assertTrue($this->tenantUser->notifications()->get()->contains(fn ($n) => $n->data['title'] === 'Technician assigned'));

        // Technician portal.
        $this->actingAs($tech)->get(route('dashboard'))->assertRedirect(route('service-provider.dashboard'));
        $this->actingAs($tech)->get(route('service-provider.dashboard'))->assertOk()->assertSee('Leaking tap');
        $this->actingAs($tech)->patch(route('service-provider.maintenance.update', $request), ['status' => 'resolved'])->assertSessionHasErrors('note');
        $this->actingAs($tech)->patch(route('service-provider.maintenance.update', $request), ['status' => 'resolved', 'note' => 'Replaced washer', 'actual_cost' => 15000])->assertRedirect();
        $this->assertSame('resolved', $request->fresh()->status);

        // Tenant says it's not fixed → reopened, staff + technician told.
        $this->actingAs($this->tenantUser)->post(route('maintenance.tenant-confirm', $request), ['fixed' => 0, 'feedback' => 'Still dripping'])->assertRedirect();
        $this->assertSame('in_progress', $request->fresh()->status);
        $this->assertTrue($this->managerUser->notifications()->get()->contains(fn ($n) => $n->data['title'] === 'Tenant says it is not fixed'));

        $this->actingAs($tech)->patch(route('service-provider.maintenance.update', $request), ['status' => 'resolved', 'note' => 'Replaced cartridge'])->assertRedirect();
        $this->actingAs($this->tenantUser)->post(route('maintenance.tenant-confirm', $request), ['fixed' => 1, 'rating' => 4])->assertRedirect();
        $request->refresh();
        $this->assertSame('closed', $request->status);
        $this->assertSame(4, $request->tenant_rating);
        $this->assertNotNull($request->tenant_confirmed_at);
    }

    public function test_technicians_only_see_their_own_jobs(): void
    {
        $techA = User::factory()->create(['role' => 'technician']);
        $techB = User::factory()->create(['role' => 'technician']);
        $a = ServiceProvider::create(['user_id' => $techA->id, 'company_name' => 'A', 'phone' => '1', 'categories' => ['electrical']]);
        ServiceProvider::create(['user_id' => $techB->id, 'company_name' => 'B', 'phone' => '2', 'categories' => ['electrical']]);
        $job = MaintenanceRequest::forceCreate(['request_number' => 'MR-9', 'tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id, 'title' => 'Sockets',
            'description' => 'x', 'category' => 'electrical', 'priority' => 'normal', 'status' => 'in_progress', 'service_provider_id' => $a->id, 'created_by' => $this->tenantUser->id]);

        $this->actingAs($techB)->get(route('service-provider.maintenance.show', $job))->assertNotFound();
        $this->actingAs($techB)->patch(route('service-provider.maintenance.update', $job), ['status' => 'in_progress'])->assertNotFound();
        $this->actingAs($this->tenantUser)->get(route('service-provider.dashboard'))->assertForbidden();
        $this->actingAs($this->managerUser)->post(route('service-providers.verify', $a), ['action' => 'verify'])->assertForbidden();
        $this->actingAs($this->admin)->post(route('service-providers.verify', $a), ['action' => 'verify'])->assertRedirect();
        $this->assertTrue($a->fresh()->isVerified());
    }

    /* ---------------------- Accountant & agent ---------------------- */

    public function test_accountant_sees_only_assigned_property_finances(): void
    {
        $accountant = User::factory()->create(['role' => 'accountant']);
        $this->actingAs($accountant)->get(route('finance.dashboard'))->assertForbidden();

        $this->actingAs($this->admin)->post(route('property-roles.store'), ['user_id' => $accountant->id, 'property_id' => $this->property->id])->assertRedirect();

        $payment = RentPayment::forceCreate(['payment_number' => 'PAY-1', 'tenant_id' => $this->tenant->id, 'lease_id' => $this->lease->id, 'unit_id' => $this->unit->id,
            'recorded_by' => $this->managerUser->id, 'payment_method' => 'mobile_money', 'amount' => 400000, 'currency' => 'RWF', 'payment_date' => now()->toDateString(), 'status' => 'confirmed']);

        $this->actingAs($accountant)->get(route('dashboard'))->assertRedirect(route('finance.dashboard'));
        $this->actingAs($accountant)->get(route('finance.dashboard'))->assertOk()->assertSee('RWF 400,000');
        $this->actingAs($accountant)->get(route('finance.payments.index'))->assertOk()->assertSee('PAY-1');
        $this->actingAs($accountant)->get(route('finance.expenses.index'))->assertOk();
        $this->actingAs($accountant)->get(route('receipts.show', $payment))->assertOk();
        $this->actingAs($accountant)->get(route('ledger.show', $this->lease))->assertOk();
        $this->actingAs($accountant)->get(route('reports.show', ['report' => 'income_expenses']))->assertOk()->assertSee('Kacyiru Heights')->assertDontSee('Other Place');
        $this->actingAs($accountant)->get(route('reports.show', ['report' => 'occupancy', 'property' => $this->otherProperty->id]))->assertForbidden();

        // Read-only: no management actions.
        $this->actingAs($accountant)->post(route('lease-requests.store', $this->lease), ['type' => 'renewal', 'offered_end_date' => now()->addYears(2)->toDateString(), 'offered_rent' => 1])->assertNotFound();
        $this->actingAs($accountant)->get(route('contracts.edit', $this->lease))->assertNotFound();
    }

    public function test_agent_sees_assigned_properties_and_commissions(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);
        $this->actingAs($this->admin)->post(route('property-roles.store'), ['user_id' => $agent->id, 'property_id' => $this->property->id, 'commission_rate' => 50]);
        $role = PropertyUserRole::where('user_id', $agent->id)->firstOrFail();
        $this->assertEquals(50, $role->commission_rate);

        $this->actingAs($agent)->get(route('dashboard'))->assertRedirect(route('agent.dashboard'));
        $this->actingAs($agent)->get(route('agent.dashboard'))->assertOk()->assertSee('Kacyiru Heights')->assertDontSee('Other Place');

        // Manager records a commission from the agent's rate: 50% of one month's 400,000.
        $this->actingAs($this->managerUser)->post(route('property-roles.commissions.store'), ['agent_role_id' => $role->id, 'lease_id' => $this->lease->id])->assertRedirect();
        $commission = AgentCommission::firstOrFail();
        $this->assertSame(200000.0, (float) $commission->amount);

        $this->actingAs($agent)->get(route('agent.commissions.index'))->assertOk()->assertSee('200,000');
        $this->actingAs($agent)->patch(route('property-roles.commissions.update', $commission), ['status' => 'paid', 'payment_reference' => 'x'])->assertForbidden();

        $this->actingAs($this->managerUser)->patch(route('property-roles.commissions.update', $commission), ['status' => 'paid'])->assertSessionHasErrors('payment_reference');
        $this->actingAs($this->managerUser)->patch(route('property-roles.commissions.update', $commission), ['status' => 'paid', 'payment_reference' => 'MOMO-1'])->assertRedirect();
        $this->assertSame('paid', $commission->fresh()->status);

        $this->actingAs($this->managerUser)->post(route('property-roles.store'), ['user_id' => $agent->id, 'property_id' => $this->otherProperty->id])->assertForbidden();
        $this->actingAs($this->tenantUser)->get(route('agent.dashboard'))->assertForbidden();
    }

    /* ------------------------------ Reports ------------------------------ */

    public function test_every_report_renders_and_exports(): void
    {
        RentInvoice::forceCreate(['lease_id' => $this->lease->id, 'tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id, 'invoice_type' => 'rent',
            'invoice_number' => 'INV-1', 'billing_period_start' => now()->startOfMonth(), 'billing_period_end' => now()->endOfMonth(), 'issue_date' => now()->startOfMonth(),
            'due_date' => now()->subDays(3), 'currency' => 'RWF', 'subtotal' => 400000, 'total_amount' => 400000, 'balance_due' => 400000, 'status' => 'unpaid']);

        $this->actingAs($this->managerUser)->get(route('reports.index'))->assertOk();
        foreach (array_merge(...array_values(array_map('array_keys', \App\Services\ReportBuilder::REPORTS))) as $key) {
            $this->actingAs($this->managerUser)->get(route('reports.show', $key))->assertOk();
            $csv = $this->actingAs($this->managerUser)->get(route('reports.show', ['report' => $key, 'format' => 'csv']))->assertOk()->streamedContent();
            $this->assertNotEmpty($csv, "CSV for {$key} is empty");
        }

        $this->actingAs($this->managerUser)->get(route('reports.show', 'outstanding'))->assertSee('INV-1')->assertSee('400,000');
        $this->actingAs($this->tenantUser)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($this->managerUser)->get(route('reports.show', 'nope'))->assertNotFound();
    }
}
