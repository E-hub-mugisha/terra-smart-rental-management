<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Document;
use App\Models\Lease;
use App\Models\LeaseRequest;
use App\Models\PaymentIntent;
use App\Models\Property;
use App\Models\PropertyManager;
use App\Models\PropertyOwner;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Online payments, receipts, ledger, documents/KYC, renewal & move-out,
 * and the audit trail.
 */
class TenancyOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $managerUser;
    private User $tenantUser;
    private User $otherTenantUser;
    private Tenant $tenant;
    private Unit $unit;
    private Lease $lease;
    private RentInvoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['payments.default' => 'sandbox']);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $ownerUser = User::factory()->create(['role' => 'owner']);
        $this->managerUser = User::factory()->create(['role' => 'manager']);
        $this->tenantUser = User::factory()->create(['role' => 'tenant', 'name' => 'Tina Tenant']);
        $this->otherTenantUser = User::factory()->create(['role' => 'tenant']);

        $owner = PropertyOwner::forceCreate(['user_id' => $ownerUser->id, 'owner_number' => 'O1', 'name' => 'Owner', 'status' => 'active', 'created_by' => $this->admin->id]);
        $manager = PropertyManager::forceCreate(['user_id' => $this->managerUser->id, 'manager_number' => 'M1', 'status' => 'active', 'created_by' => $this->admin->id]);
        $property = Property::forceCreate(['property_owner_id' => $owner->id, 'property_code' => 'P1', 'name' => 'Kacyiru Heights', 'property_type' => 'residential', 'status' => 'active', 'created_by' => $this->admin->id]);
        $property->managerAssignments()->forceCreate(['property_manager_id' => $manager->id, 'start_date' => now()->toDateString(), 'status' => 'active', 'assigned_by' => $this->admin->id]);
        $building = Building::forceCreate(['property_id' => $property->id, 'building_code' => 'B1', 'name' => 'Block A', 'status' => 'active']);
        $this->unit = Unit::forceCreate(['building_id' => $building->id, 'unit_number' => '101', 'unit_type' => 'apartment', 'rent_amount' => 350000, 'status' => 'occupied']);

        $this->tenant = Tenant::forceCreate(['user_id' => $this->tenantUser->id, 'tenant_number' => 'T1', 'full_name' => 'Tina Tenant', 'email' => $this->tenantUser->email, 'phone' => '0788111111', 'status' => 'active']);
        Tenant::forceCreate(['user_id' => $this->otherTenantUser->id, 'tenant_number' => 'T2', 'full_name' => 'Other', 'email' => $this->otherTenantUser->email, 'phone' => '0788222222', 'status' => 'active']);

        $this->lease = Lease::forceCreate([
            'tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id, 'lease_number' => 'L1', 'currency' => 'RWF',
            'start_date' => now()->subMonths(6)->toDateString(), 'end_date' => now()->addMonths(2)->toDateString(),
            'monthly_rent' => 350000, 'deposit_amount' => 700000, 'payment_frequency' => 'monthly', 'status' => 'active',
        ]);
        $this->invoice = RentInvoice::forceCreate([
            'lease_id' => $this->lease->id, 'tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id, 'invoice_type' => 'rent',
            'invoice_number' => 'INV-1', 'billing_period_start' => now()->startOfMonth(), 'billing_period_end' => now()->endOfMonth(),
            'issue_date' => now()->startOfMonth(), 'due_date' => now()->addDays(5), 'currency' => 'RWF',
            'subtotal' => 350000, 'total_amount' => 350000, 'balance_due' => 350000, 'status' => 'unpaid',
        ]);
        \Illuminate\Notifications\DatabaseNotification::query()->delete();
    }

    /* ----------------------------- Payments ----------------------------- */

    public function test_tenant_pays_invoice_online_and_it_is_recorded_allocated_and_receipted(): void
    {
        $this->actingAs($this->tenantUser)->get(route('payments.online.create', $this->invoice))->assertOk()->assertSee('Test mode');

        $this->actingAs($this->tenantUser)
            ->post(route('payments.online.store', $this->invoice), ['amount' => 350000, 'phone' => '0788 123 456'])
            ->assertRedirect();
        $intent = PaymentIntent::firstOrFail();
        $this->assertSame('pending', $intent->status);
        $this->assertSame(0, RentPayment::count());

        // Tenant "approves on the phone".
        $this->actingAs($this->tenantUser)->post(route('payments.online.sandbox', $intent), ['decision' => 'approve'])->assertRedirect();

        $intent->refresh();
        $this->assertSame('successful', $intent->status);
        $payment = RentPayment::firstOrFail();
        $this->assertSame('confirmed', $payment->status);
        $this->assertSame($this->tenantUser->id, $payment->recorded_by);
        $this->assertSame(350000.0, (float) $payment->allocations()->sum('amount'));
        $this->assertSame('paid', $this->invoice->fresh()->status);
        $this->assertSame(0.0, (float) $this->invoice->fresh()->balance_due);
        $this->assertSame('Payment successful', $this->tenantUser->notifications()->first()->data['title']);

        // Syncing again never records the money twice.
        app(\App\Services\Payments\PaymentService::class)->complete($intent);
        $this->assertSame(1, RentPayment::count());

        $this->actingAs($this->tenantUser)->getJson(route('payments.online.status', $intent))
            ->assertJsonPath('status', 'successful')->assertJsonPath('receipt', route('receipts.show', $payment));
    }

    public function test_partial_payment_leaves_balance_and_declined_numbers_fail(): void
    {
        $this->actingAs($this->tenantUser)->post(route('payments.online.store', $this->invoice), ['amount' => 100000, 'phone' => '0788123456']);
        $this->actingAs($this->tenantUser)->post(route('payments.online.sandbox', PaymentIntent::first()), ['decision' => 'approve']);
        $this->assertSame(250000.0, (float) $this->invoice->fresh()->balance_due);
        $this->assertSame('partially_paid', $this->invoice->fresh()->status);

        $this->actingAs($this->tenantUser)->post(route('payments.online.store', $this->invoice), ['amount' => 1000, 'phone' => '0788120000']);
        $this->assertSame('failed', PaymentIntent::latest('id')->first()->status);
        $this->assertSame(1, RentPayment::count());
    }

    public function test_payment_validation_and_authorisation(): void
    {
        $this->actingAs($this->tenantUser)->post(route('payments.online.store', $this->invoice), ['amount' => 999999, 'phone' => '0788123456'])
            ->assertSessionHasErrors('amount');
        $this->actingAs($this->tenantUser)->post(route('payments.online.store', $this->invoice), ['amount' => 1000, 'phone' => '12345'])
            ->assertSessionHasErrors('phone');

        $this->actingAs($this->otherTenantUser)->get(route('payments.online.create', $this->invoice))->assertNotFound();

        $this->actingAs($this->tenantUser)->post(route('payments.online.store', $this->invoice), ['amount' => 1000, 'phone' => '0788123456']);
        $intent = PaymentIntent::firstOrFail();
        $this->actingAs($this->otherTenantUser)->get(route('payments.online.show', $intent))->assertNotFound();
    }

    public function test_provider_callback_cannot_fake_a_successful_payment(): void
    {
        $this->actingAs($this->tenantUser)->post(route('payments.online.store', $this->invoice), ['amount' => 1000, 'phone' => '0788123456']);
        $intent = PaymentIntent::firstOrFail();

        // Sandbox has no public callback, and other providers re-check with the provider.
        $this->post(route('payments.callback', 'sandbox'), ['reference' => $intent->reference, 'status' => 'SUCCESSFUL'])->assertNotFound();
        $this->assertSame('pending', $intent->fresh()->status);
        $this->assertSame(0, RentPayment::count());
    }

    public function test_mtn_momo_request_to_pay_and_status_check(): void
    {
        config(['payments.default' => 'mtn_momo', 'payments.providers.mtn_momo' => array_merge(config('payments.providers.mtn_momo'), [
            'subscription_key' => 'sub', 'api_user' => 'user', 'api_key' => 'key', 'base_url' => 'https://momo.test',
        ])]);
        \Illuminate\Support\Facades\Http::fake([
            'momo.test/collection/token/' => \Illuminate\Support\Facades\Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            'momo.test/collection/v1_0/requesttopay' => \Illuminate\Support\Facades\Http::response(null, 202),
            'momo.test/collection/v1_0/requesttopay/*' => \Illuminate\Support\Facades\Http::response(['status' => 'SUCCESSFUL', 'financialTransactionId' => 'FT123']),
        ]);

        $this->actingAs($this->tenantUser)->post(route('payments.online.store', $this->invoice), ['amount' => 350000, 'phone' => '0788123456'])->assertRedirect();
        $intent = PaymentIntent::firstOrFail();

        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => str_ends_with($r->url(), '/requesttopay')
            && $r['payer']['partyId'] === '250788123456' && $r['externalId'] === $intent->reference && $r->hasHeader('X-Reference-Id'));

        // The callback is only a hint: the status comes from the GET.
        $this->post(route('payments.callback', 'mtn_momo'), ['externalId' => $intent->reference, 'status' => 'SUCCESSFUL'])->assertNoContent();
        $this->assertSame('successful', $intent->fresh()->status);
        $this->assertSame('FT123', RentPayment::first()->transaction_reference);
    }

    /* ------------------------- Receipts & ledger ------------------------- */

    public function test_receipt_access_and_signed_share_link(): void
    {
        $payment = $this->confirmedPayment(350000);

        $this->actingAs($this->tenantUser)->get(route('receipts.show', $payment))->assertOk()->assertSee('RWF 350,000')->assertSee('Kacyiru Heights');
        $this->actingAs($this->managerUser)->get(route('receipts.show', $payment))->assertOk();
        $this->actingAs($this->otherTenantUser)->get(route('receipts.show', $payment))->assertNotFound();

        auth()->logout();
        $this->get(route('receipts.shared', $payment))->assertForbidden(); // unsigned
        $this->get(URL::temporarySignedRoute('receipts.shared', now()->addDay(), ['payment' => $payment->id]))->assertOk()->assertSee('PAID');
    }

    public function test_ledger_shows_running_balance_and_exports_csv(): void
    {
        $this->confirmedPayment(200000);

        $response = $this->actingAs($this->tenantUser)->get(route('ledger.show', $this->lease))->assertOk();
        $entries = $response->viewData('entries');
        $this->assertCount(2, $entries);
        $this->assertSame(350000.0, $entries[0]['balance']);
        $this->assertSame(150000.0, $entries[1]['balance']);

        $csv = $this->actingAs($this->managerUser)->get(route('ledger.show', [$this->lease, 'format' => 'csv']))->assertOk()->streamedContent();
        $this->assertStringContainsString('INV-1', $csv);
        $this->actingAs($this->otherTenantUser)->get(route('ledger.show', $this->lease))->assertNotFound();
    }

    /* --------------------------- Documents/KYC --------------------------- */

    public function test_tenant_uploads_id_and_manager_verifies_it(): void
    {
        $this->actingAs($this->tenantUser)->put(route('documents.identity'), ['id_type' => 'national_id', 'id_number' => '123'])->assertSessionHasErrors('id_number');
        $this->actingAs($this->tenantUser)->put(route('documents.identity'), ['id_type' => 'national_id', 'id_number' => '1199080012345678', 'occupants' => 2])->assertSessionHasNoErrors();

        $this->actingAs($this->tenantUser)->post(route('documents.store'), [
            'owner' => 'tenant:' . $this->tenant->id, 'category' => 'national_id',
            'file' => UploadedFile::fake()->create('id.pdf', 200, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $doc = Document::firstOrFail();
        $this->assertSame('pending', $doc->status);
        $this->assertSame('pending', $this->tenant->fresh()->kyc_status);
        $this->assertSame('document', $this->managerUser->notifications()->first()->data['category']);

        $this->actingAs($this->otherTenantUser)->get(route('documents.download', $doc))->assertNotFound();
        $this->actingAs($this->managerUser)->get(route('documents.download', $doc))->assertOk();

        $this->actingAs($this->managerUser)->post(route('documents.review', $doc), ['decision' => 'reject'])->assertSessionHasErrors('reason');
        $this->actingAs($this->managerUser)->post(route('documents.review', $doc), ['decision' => 'verify'])->assertRedirect();
        $this->assertSame('verified', $this->tenant->fresh()->kyc_status);
        $this->assertSame('Document verified', $this->tenantUser->notifications()->first()->data['title']);

        // Tenants can't verify their own documents or attach to other tenants.
        $this->actingAs($this->tenantUser)->post(route('documents.review', $doc), ['decision' => 'verify'])->assertForbidden();
        $otherTenant = Tenant::where('user_id', $this->otherTenantUser->id)->first();
        $this->actingAs($this->tenantUser)->post(route('documents.store'), [
            'owner' => 'tenant:' . $otherTenant->id, 'category' => 'passport', 'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_document_category_must_match_owner_type(): void
    {
        $this->actingAs($this->managerUser)->post(route('documents.store'), [
            'owner' => 'lease:' . $this->lease->id, 'category' => 'title_deed', 'file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('category');

        $this->actingAs($this->managerUser)->post(route('documents.store'), [
            'owner' => 'lease:' . $this->lease->id, 'category' => 'lease_contract', 'file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->tenantUser)->get(route('documents.index'))->assertOk()->assertSee('Signed lease contract');
    }

    /* ---------------------- Renewal & move-out notice ---------------------- */

    public function test_renewal_request_offer_and_acceptance_extends_lease(): void
    {
        $this->actingAs($this->tenantUser)->post(route('lease-requests.store', $this->lease), ['type' => 'renewal', 'requested_months' => 12])->assertSessionHasNoErrors();
        $request = LeaseRequest::firstOrFail();
        $this->assertSame('Renewal requested', $this->managerUser->notifications()->first()->data['title']);

        $newEnd = $this->lease->end_date->copy()->addYear()->toDateString();
        $this->actingAs($this->managerUser)->post(route('lease-requests.respond', $request), ['action' => 'offer', 'offered_end_date' => now()->toDateString(), 'offered_rent' => 380000])
            ->assertSessionHasErrors('offered_end_date');
        $this->actingAs($this->managerUser)->post(route('lease-requests.respond', $request), ['action' => 'offer', 'offered_end_date' => $newEnd, 'offered_rent' => 380000])->assertRedirect();

        $this->actingAs($this->tenantUser)->post(route('lease-requests.respond', $request), ['action' => 'accept'])->assertRedirect();

        $this->lease->refresh();
        $this->assertSame($newEnd, $this->lease->end_date->toDateString());
        $this->assertSame(380000.0, (float) $this->lease->monthly_rent);
        $this->assertSame('accepted', $request->fresh()->status);
        $this->assertTrue(AuditLog::where('auditable_type', Lease::class)->where('auditable_id', $this->lease->id)->where('event', 'updated')->exists());
    }

    public function test_move_out_notice_and_acknowledgement(): void
    {
        $this->actingAs($this->tenantUser)->post(route('lease-requests.store', $this->lease), ['type' => 'move_out', 'move_out_date' => now()->subDay()->toDateString()])
            ->assertSessionHasErrors('move_out_date');
        $this->actingAs($this->tenantUser)->post(route('lease-requests.store', $this->lease), ['type' => 'move_out', 'move_out_date' => now()->addMonth()->toDateString()]);
        $request = LeaseRequest::firstOrFail();

        // A second open notice is refused, and tenants can't acknowledge their own.
        $this->actingAs($this->tenantUser)->post(route('lease-requests.store', $this->lease), ['type' => 'move_out', 'move_out_date' => now()->addMonths(2)->toDateString()])
            ->assertSessionHasErrors('lease_request');
        $this->actingAs($this->tenantUser)->post(route('lease-requests.respond', $request), ['action' => 'acknowledge'])->assertSessionHasErrors('action');

        $this->actingAs($this->managerUser)->post(route('lease-requests.respond', $request), ['action' => 'acknowledge'])->assertRedirect();
        $this->assertSame('acknowledged', $request->fresh()->status);
        $this->assertSame('Move-out notice acknowledged', $this->tenantUser->notifications()->first()->data['title']);
        $this->actingAs($this->otherTenantUser)->post(route('lease-requests.respond', $request), ['action' => 'withdraw'])->assertNotFound();
    }

    /* ------------------------------ Audit log ------------------------------ */

    public function test_audit_log_records_changes_and_logins_and_is_admin_only(): void
    {
        $this->actingAs($this->managerUser);
        $this->unit->update(['rent_amount' => 400000]);

        $log = AuditLog::where('auditable_type', Unit::class)->where('event', 'updated')->latest('id')->firstOrFail();
        $this->assertSame($this->managerUser->id, $log->user_id);
        $this->assertEquals(400000, $log->new_values['rent_amount']);
        $this->assertEquals(350000, $log->old_values['rent_amount']);

        $this->tenantUser->update(['password' => 'new-secret-123']);
        $pw = AuditLog::where('auditable_type', User::class)->where('auditable_id', $this->tenantUser->id)->latest('id')->first();
        $this->assertTrue($pw === null || ! array_key_exists('password', $pw->new_values ?? []));

        auth()->logout();
        $this->post(route('login'), ['email' => $this->tenantUser->email, 'password' => 'wrong']);
        $this->assertTrue(AuditLog::where('event', 'login_failed')->exists());

        $this->actingAs($this->managerUser)->get(route('admin.audit-logs.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.audit-logs.index'))->assertOk()->assertSee('rent_amount');
    }

    private function confirmedPayment(float $amount): RentPayment
    {
        $payment = RentPayment::forceCreate([
            'payment_number' => 'PAY-' . uniqid(), 'tenant_id' => $this->tenant->id, 'lease_id' => $this->lease->id, 'unit_id' => $this->unit->id,
            'recorded_by' => $this->managerUser->id, 'payment_method' => 'mobile_money', 'amount' => $amount, 'currency' => 'RWF',
            'payment_date' => now()->toDateString(), 'status' => 'confirmed',
        ]);
        $payment->allocations()->create(['rent_invoice_id' => $this->invoice->id, 'amount' => $amount, 'allocated_at' => now()->toDateString()]);

        return $payment;
    }
}
