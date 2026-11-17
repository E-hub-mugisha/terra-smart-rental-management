<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\PropertyManager;
use App\Models\PropertyOwner;
use App\Models\RentInvoice;
use App\Models\ServiceProvider;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\TwoFactorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** API, two-step sign-in, languages, maps and the admin platform monitor. */
class PlatformFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $managerUser;
    private User $tenantUser;
    private Tenant $tenant;
    private Property $property;
    private Unit $unit;
    private Lease $lease;
    private RentInvoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        config(['payments.default' => 'sandbox']);

        $this->admin = User::factory()->create(['role' => 'admin', 'password' => 'secret-pass-1']);
        $ownerUser = User::factory()->create(['role' => 'owner']);
        $this->managerUser = User::factory()->create(['role' => 'manager', 'password' => 'secret-pass-1']);
        $this->tenantUser = User::factory()->create(['role' => 'tenant', 'password' => 'secret-pass-1']);

        $owner = PropertyOwner::forceCreate(['user_id' => $ownerUser->id, 'owner_number' => 'O1', 'name' => 'Owner', 'status' => 'active', 'created_by' => $this->admin->id]);
        $manager = PropertyManager::forceCreate(['user_id' => $this->managerUser->id, 'manager_number' => 'M1', 'status' => 'active', 'created_by' => $this->admin->id]);
        $this->property = Property::forceCreate(['property_owner_id' => $owner->id, 'property_code' => 'P1', 'name' => 'Kacyiru Heights', 'property_type' => 'residential',
            'status' => 'active', 'district' => 'Gasabo', 'sector' => 'Kacyiru', 'latitude' => -1.9355, 'longitude' => 30.0817, 'created_by' => $this->admin->id]);
        $this->property->managerAssignments()->forceCreate(['property_manager_id' => $manager->id, 'start_date' => now()->toDateString(), 'status' => 'active', 'assigned_by' => $this->admin->id]);
        $building = Building::forceCreate(['property_id' => $this->property->id, 'building_code' => 'B1', 'name' => 'Block A', 'status' => 'active']);
        $this->unit = Unit::forceCreate(['building_id' => $building->id, 'unit_number' => '101', 'unit_type' => 'apartment', 'rent_amount' => 350000, 'status' => 'occupied']);
        Unit::forceCreate(['building_id' => $building->id, 'unit_number' => '102', 'unit_type' => 'apartment', 'rent_amount' => 300000, 'status' => 'available']);

        $this->tenant = Tenant::forceCreate(['user_id' => $this->tenantUser->id, 'tenant_number' => 'T1', 'full_name' => 'Tina Tenant', 'email' => $this->tenantUser->email, 'phone' => '0788111111', 'status' => 'active']);
        $this->lease = Lease::forceCreate(['tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id, 'lease_number' => 'L1', 'currency' => 'RWF',
            'start_date' => now()->subMonth()->toDateString(), 'end_date' => now()->addDays(40)->toDateString(),
            'monthly_rent' => 350000, 'deposit_amount' => 0, 'payment_frequency' => 'monthly', 'status' => 'active']);
        $this->invoice = RentInvoice::forceCreate(['lease_id' => $this->lease->id, 'tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id, 'invoice_type' => 'rent',
            'invoice_number' => 'INV-1', 'billing_period_start' => now()->startOfMonth(), 'billing_period_end' => now()->endOfMonth(), 'issue_date' => now()->startOfMonth(),
            'due_date' => now()->addDays(5), 'currency' => 'RWF', 'subtotal' => 350000, 'total_amount' => 350000, 'balance_due' => 350000, 'status' => 'unpaid']);
    }

    /* -------------------------------- API -------------------------------- */

    public function test_tenant_uses_the_api_to_see_balance_and_pay(): void
    {
        $this->postJson('/api/v1/auth/token', ['email' => $this->tenantUser->email, 'password' => 'wrong', 'device_name' => 'Pixel'])->assertStatus(422);

        $token = $this->postJson('/api/v1/auth/token', ['email' => $this->tenantUser->email, 'password' => 'secret-pass-1', 'device_name' => 'Pixel'])
            ->assertCreated()->assertJsonPath('user.role', 'tenant')->json('token');
        $auth = ['Authorization' => 'Bearer ' . $token];

        $this->getJson('/api/v1/me', $auth)->assertOk()->assertJsonPath('data.tenant_id', $this->tenant->id);
        $this->getJson('/api/v1/tenant/dashboard', $auth)->assertOk()->assertJsonPath('data.balance_due', 350000)->assertJsonPath('data.lease.number', 'L1');
        $this->getJson('/api/v1/tenant/invoices?status=open', $auth)->assertOk()->assertJsonPath('data.0.number', 'INV-1');

        $intent = $this->postJson("/api/v1/tenant/invoices/{$this->invoice->id}/pay", ['amount' => 350000, 'phone' => '0788 123 456'], $auth)
            ->assertCreated()->assertJsonPath('data.status', 'pending')->json('data');
        app(\App\Services\Payments\SandboxGateway::class)->decide(\App\Models\PaymentIntent::find($intent['id']), 'approve');
        $this->getJson("/api/v1/tenant/payment-intents/{$intent['id']}", $auth)->assertOk()->assertJsonPath('data.status', 'successful');
        $this->getJson('/api/v1/tenant/payments', $auth)->assertOk()->assertJsonPath('data.0.status', 'confirmed');

        $this->postJson('/api/v1/tenant/maintenance-requests', ['lease_id' => $this->lease->id, 'title' => 'Broken lock', 'description' => 'Front door lock',
            'category' => 'general', 'priority' => 'high'], $auth)->assertCreated();
        $this->assertSame(1, MaintenanceRequest::count());

        // Tenants can't use staff endpoints; revoking ends access.
        $this->getJson('/api/v1/portfolio/summary', $auth)->assertForbidden();
        $this->deleteJson('/api/v1/auth/token', [], $auth)->assertNoContent();
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/me', $auth)->assertUnauthorized();
    }

    public function test_manager_portfolio_api_and_scoping(): void
    {
        $token = $this->managerUser->createToken('test', ['staff'])->plainTextToken;
        $auth = ['Authorization' => 'Bearer ' . $token];

        $this->getJson('/api/v1/portfolio/summary', $auth)->assertOk()
            ->assertJsonPath('data.properties', 1)->assertJsonPath('data.units', 2)->assertJsonPath('data.occupied', 1)
            ->assertJsonPath('data.expected_rent', 350000)->assertJsonPath('data.leases_expiring_60_days', 1);
        $this->getJson("/api/v1/portfolio/properties/{$this->property->id}", $auth)->assertOk()->assertJsonPath('data.buildings.0.units.0.tenant', 'Tina Tenant');

        $other = Property::forceCreate(['property_owner_id' => $this->property->property_owner_id, 'property_code' => 'P9', 'name' => 'Not mine', 'property_type' => 'residential', 'status' => 'active', 'created_by' => $this->admin->id]);
        $other->update(['property_owner_id' => PropertyOwner::forceCreate(['user_id' => $this->admin->id, 'owner_number' => 'O9', 'name' => 'X', 'status' => 'active', 'created_by' => $this->admin->id])->id]);
        $this->getJson("/api/v1/portfolio/properties/{$other->id}", $auth)->assertNotFound();
        $this->getJson('/api/v1/tenant/dashboard', $auth)->assertForbidden();
    }

    public function test_technician_api_and_public_listings(): void
    {
        $tech = User::factory()->create(['role' => 'technician']);
        $provider = ServiceProvider::create(['user_id' => $tech->id, 'company_name' => 'FixIt', 'phone' => '1', 'categories' => ['plumbing']]);
        $job = MaintenanceRequest::forceCreate(['request_number' => 'MR-1', 'tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id, 'title' => 'Tap', 'description' => 'x',
            'category' => 'plumbing', 'priority' => 'normal', 'status' => 'in_progress', 'service_provider_id' => $provider->id, 'created_by' => $this->tenantUser->id]);
        $auth = ['Authorization' => 'Bearer ' . $tech->createToken('t', ['technician'])->plainTextToken];

        $this->getJson('/api/v1/technician/jobs', $auth)->assertOk()->assertJsonPath('data.0.tenant.phone', '0788111111');
        $this->patchJson("/api/v1/technician/jobs/{$job->id}", ['status' => 'resolved'], $auth)->assertStatus(422);
        $this->patchJson("/api/v1/technician/jobs/{$job->id}", ['status' => 'resolved', 'note' => 'Fixed'], $auth)->assertOk()->assertJsonPath('data.status', 'resolved');

        $this->getJson('/api/v1/listings?available=1')->assertOk()
            ->assertJsonPath('data.0.name', 'Kacyiru Heights')->assertJsonPath('data.0.units_available', 1)->assertJsonPath('data.0.rent_from', 300000);
        $this->getJson('/api/v1/listings?bbox=-3,28,-2.5,29')->assertOk()->assertJsonCount(0, 'data');
        $this->get('/api/docs')->assertOk()->assertSee('/portfolio/summary');
    }

    /* ------------------------- Two-step sign-in ------------------------- */

    public function test_two_step_sign_in_with_emailed_code(): void
    {
        Notification::fake();
        $this->managerUser->update(['two_factor_enabled' => true]);

        $this->post(route('login'), ['email' => $this->managerUser->email, 'password' => 'secret-pass-1'])->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();

        $code = null;
        Notification::assertSentTo($this->managerUser, TwoFactorCode::class, function ($n) use (&$code) {
            $code = (fn () => $this->code)->call($n);

            return true;
        });

        $this->get(route('two-factor.challenge'))->assertOk()->assertSee('Check your email');
        $this->post(route('two-factor.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->post(route('two-factor.verify'), ['code' => substr($code, 0, 3) . ' ' . substr($code, 3)])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->managerUser);
        $this->assertNull($this->managerUser->fresh()->two_factor_code);

        // API token sign-in is refused for 2FA accounts.
        auth()->logout();
        $this->postJson('/api/v1/auth/token', ['email' => $this->managerUser->email, 'password' => 'secret-pass-1', 'device_name' => 'x'])->assertStatus(422);
    }

    public function test_two_step_codes_lock_after_too_many_attempts(): void
    {
        Notification::fake();
        $this->managerUser->update(['two_factor_enabled' => true]);
        $this->post(route('login'), ['email' => $this->managerUser->email, 'password' => 'secret-pass-1']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('two-factor.verify'), ['code' => '111111']);
        }
        $this->post(route('two-factor.verify'), ['code' => '111111'])->assertSessionHasErrors('code');
        $this->assertSame(6, $this->managerUser->fresh()->two_factor_attempts);
        $this->assertGuest();
    }

    public function test_security_page_toggles_two_factor_and_manages_tokens(): void
    {
        $this->actingAs($this->managerUser)->get(route('account.security'))->assertOk();
        $this->actingAs($this->managerUser)->put(route('account.security.two-factor'), ['enable' => 1, 'password' => 'nope'])->assertSessionHasErrors('password');
        $this->actingAs($this->managerUser)->put(route('account.security.two-factor'), ['enable' => 1, 'password' => 'secret-pass-1'])->assertRedirect();
        $this->assertTrue($this->managerUser->fresh()->two_factor_enabled);

        $this->actingAs($this->managerUser)->post(route('account.security.tokens.store'), ['name' => 'Integration', 'password' => 'secret-pass-1'])->assertSessionHas('new_token');
        $token = $this->managerUser->tokens()->firstOrFail();
        $this->assertSame(['staff'], $token->abilities);
        $this->actingAs($this->tenantUser)->delete(route('account.security.tokens.destroy', $token->id))->assertNotFound();
        $this->actingAs($this->managerUser)->delete(route('account.security.tokens.destroy', $token->id))->assertRedirect();
        $this->assertSame(0, $this->managerUser->tokens()->count());
    }

    /* ----------------------------- Languages ----------------------------- */

    public function test_language_preference_changes_the_interface(): void
    {
        $this->actingAs($this->tenantUser)->get(route('notifications.index'))->assertSee('Mark all read', false)->assertDontSee('Ubutumwa');

        $this->actingAs($this->tenantUser)->post(route('locale.update'), ['locale' => 'rw'])->assertRedirect();
        $this->assertSame('rw', $this->tenantUser->fresh()->locale);
        $this->actingAs($this->tenantUser->fresh())->get(route('notifications.index'))->assertOk()->assertSee('Ubutumwa')->assertSee('Imenyesha');

        $this->actingAs($this->tenantUser->fresh())->post(route('locale.update'), ['locale' => 'fr']);
        $this->actingAs($this->tenantUser->fresh())->get(route('notifications.index'))->assertSee('Se déconnecter');

        $this->actingAs($this->tenantUser)->post(route('locale.update'), ['locale' => 'de'])->assertSessionHasErrors('locale');

        // Guests: stored in the session; API: Accept-Language.
        auth()->logout();
        $this->post(route('locale.update'), ['locale' => 'fr']);
        $this->get(route('listings.index'))->assertSee('Créer un compte');
    }

    /* ------------------------- Maps & monitoring ------------------------- */

    public function test_maps_and_admin_platform_monitor_render(): void
    {
        $this->get(route('listings.map'))->assertOk()->assertSee('leaflet', false);
        $this->actingAs($this->managerUser)->get(route('portfolio.map'))->assertOk()->assertSee('Kacyiru Heights');
        $this->actingAs($this->tenantUser)->get(route('portfolio.map'))->assertForbidden();

        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Platform monitor')->assertSee('Leases expiring soon')->assertSee('test mode');
    }
}
