<?php

namespace Tests\Feature;

use App\Mail\FieldStaffAccountCreated;
use App\Mail\OwnerAccountCreated;
use App\Mail\SubscriberMessage;
use App\Models\Document;
use App\Models\FieldStaff;
use App\Models\PropertyManager;
use App\Models\PropertyOwner;
use App\Models\SubscriberCall;
use App\Models\SubscriptionContract;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SubscriptionBilling;
use App\Support\Phone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Subscriber & contract management, field staff, contract PDFs and international phones. */
class SubscriptionsFieldStaffTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $ownerUser;
    private PropertyOwner $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->ownerUser = User::factory()->create(['role' => 'owner', 'name' => 'Olive Owner']);
        $this->owner = PropertyOwner::forceCreate(['user_id' => $this->ownerUser->id, 'owner_number' => 'O1', 'name' => 'Olive Owner',
            'email' => $this->ownerUser->email, 'phone' => '+250788123456', 'status' => 'active', 'created_by' => $this->admin->id]);
    }

    private function fieldStaff(string $status = 'active'): User
    {
        $user = User::factory()->create(['role' => 'field_staff']);
        FieldStaff::create(['user_id' => $user->id, 'staff_number' => 'FS-' . $user->id, 'status' => $status, 'phone' => '+250788000111']);

        return $user;
    }

    private function contract(array $attributes = []): SubscriptionContract
    {
        return SubscriptionContract::create($attributes + [
            'contract_number' => SubscriptionContract::nextNumber(), 'user_id' => $this->ownerUser->id, 'title' => 'Terra subscription',
            'start_date' => today()->subMonths(2)->startOfMonth(), 'end_date' => today()->addMonths(10), 'billing_cycle' => 'monthly',
            'amount' => 50000, 'currency' => 'RWF', 'payment_due_days' => 7, 'status' => 'pending',
        ]);
    }

    /* ----------------------------- Phones ----------------------------- */

    public function test_phone_numbers_are_validated_for_every_country_and_stored_as_e164(): void
    {
        $this->assertSame('+250788123456', Phone::e164('0788 123 456'));
        $this->assertSame('+12025550143', Phone::e164('+1 (202) 555-0143'));
        $this->assertSame('+447911123456', Phone::e164('+44 7911 123456'));
        $this->assertSame('+256772123456', Phone::e164('00256 772 123456'));
        $this->assertNull(Phone::e164('12345'));
        $this->assertNull(Phone::e164('+250 123'));

        // Owner registration normalises the number on the profile and the login account.
        $this->actingAs($this->admin)->post(route('admin.owners.store'), [
            'owner_type' => 'individual', 'name' => 'Kenyan Owner', 'email' => 'ke.owner@example.com', 'phone' => '+254 712 345 678',
            'country' => 'Kenya', 'verification_status' => 'pending', 'status' => 'active',
        ])->assertSessionHasNoErrors();
        $owner = PropertyOwner::where('email', 'ke.owner@example.com')->firstOrFail();
        $this->assertSame('+254712345678', $owner->phone);
        $this->assertSame('+254712345678', $owner->user->phone);

        // An invalid number is rejected with a clear message.
        $this->actingAs($this->admin)->post(route('admin.managers.store'), [
            'name' => 'Bad Phone', 'email' => 'bad.phone@example.com', 'phone' => '0712', 'country' => 'Rwanda',
            'employment_type' => 'full_time', 'status' => 'active',
        ])->assertSessionHasErrors('phone');

        // Public registration accepts an international number.
        auth()->logout();
        $this->post(route('register'), ['name' => 'New Person', 'email' => 'new.person@example.com', 'phone' => '+33 6 12 34 56 78',
            'password' => 'Secret#Pass2026', 'password_confirmation' => 'Secret#Pass2026'])->assertRedirect(route('dashboard'));
        $this->assertSame('+33612345678', User::where('email', 'new.person@example.com')->value('phone'));
    }

    /* --------------------------- Field staff --------------------------- */

    public function test_admin_creates_field_staff_who_can_only_register_owners_and_managers(): void
    {
        $this->actingAs($this->admin)->post(route('admin.field-staff.store'), [
            'name' => 'Fiston Field', 'email' => 'fiston@example.com', 'phone' => '0788 555 444', 'region' => 'Kigali City', 'district' => 'Gasabo', 'status' => 'active',
        ])->assertRedirect();
        $staffUser = User::where('email', 'fiston@example.com')->firstOrFail();
        $this->assertSame('field_staff', $staffUser->role);
        $this->assertSame('+250788555444', $staffUser->fieldStaff->phone);
        Mail::assertSent(FieldStaffAccountCreated::class);

        // Lands on the field portal, sees only registration navigation.
        $this->actingAs($staffUser)->get(route('dashboard'))->assertRedirect(route('field-staff.dashboard'));
        $this->actingAs($staffUser)->get(route('field-staff.dashboard'))->assertOk()->assertSee('Register Owner')->assertDontSee('Rent Invoices');

        // Blocked from admin, finance, settings and other portals.
        foreach (['admin.dashboard', 'admin.users.index', 'finance.dashboard', 'reports.index', 'documents.index', 'property-manager.dashboard', 'admin.subscribers.index'] as $route) {
            $this->actingAs($staffUser)->get(route($route))->assertForbidden();
        }

        // Registers an owner: always pending review, recorded against the staff member.
        $this->actingAs($staffUser)->post(route('field-staff.owners.store'), [
            'owner_type' => 'individual', 'name' => 'Field Owner', 'email' => 'field.owner@example.com', 'phone' => '0722 123 456',
            'country' => 'Rwanda', 'verification_status' => 'verified', 'status' => 'inactive',
        ])->assertRedirect(route('field-staff.registrations'));
        $owner = PropertyOwner::where('email', 'field.owner@example.com')->firstOrFail();
        $this->assertSame('pending', $owner->verification_status);
        $this->assertSame('active', $owner->status);
        $this->assertSame($staffUser->id, $owner->created_by);
        $this->assertSame('+250722123456', $owner->phone);
        Mail::assertSent(OwnerAccountCreated::class);
        $this->assertTrue($this->admin->notifications()->get()->contains(fn ($n) => ($n->data['category'] ?? null) === 'registration'),
            'admins are told about field registrations');

        // Registers a manager.
        $this->actingAs($staffUser)->post(route('field-staff.managers.store'), [
            'name' => 'Field Manager', 'email' => 'field.manager@example.com', 'phone' => '+256 772 123456', 'country' => 'Uganda',
            'employment_type' => 'contract',
        ])->assertRedirect(route('field-staff.registrations'));
        $manager = PropertyManager::whereHas('user', fn ($q) => $q->where('email', 'field.manager@example.com'))->firstOrFail();
        $this->assertSame($staffUser->id, $manager->created_by);
        $this->assertSame('+256772123456', $manager->phone);

        $this->actingAs($staffUser)->get(route('field-staff.registrations'))->assertOk()->assertSee('Field Owner')->assertSee('Field Manager');

        // Admin sees the registrations per staff member.
        $member = $staffUser->fieldStaff;
        $this->actingAs($this->admin)->get(route('admin.field-staff.index'))->assertOk()->assertSee('Fiston Field');
        $this->actingAs($this->admin)->get(route('admin.field-staff.show', $member))->assertOk()->assertSee('Field Owner')->assertSee('Field Manager');

        // Suspended field staff are signed out.
        $this->actingAs($this->admin)->post(route('admin.field-staff.toggle', $member));
        $this->assertSame('suspended', $member->fresh()->status);
        $this->actingAs($staffUser->fresh())->get(route('field-staff.dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_sees_resends_and_sets_field_staff_credentials(): void
    {
        // Admin sets the password and chooses not to email it: details are shown once.
        $this->actingAs($this->admin)->post(route('admin.field-staff.store'), [
            'name' => 'Grace Field', 'email' => 'grace.field@example.com', 'phone' => '0788 555 111', 'status' => 'active',
            'password_mode' => 'manual', 'password' => 'Kigali2026x', 'send_email' => '0',
        ])->assertRedirect()->assertSessionHas('issued_credentials', fn ($c) => $c['username'] === 'grace.field@example.com' && $c['password'] === 'Kigali2026x' && $c['emailed'] === false);
        Mail::assertNotSent(FieldStaffAccountCreated::class);

        $member = FieldStaff::whereHas('user', fn ($q) => $q->where('email', 'grace.field@example.com'))->firstOrFail();

        // The one-time panel renders with username and password on the next page.
        $this->actingAs($this->admin)->withSession(['issued_credentials' => ['name' => 'Grace Field', 'username' => 'grace.field@example.com', 'password' => 'Kigali2026x', 'emailed' => false, 'role' => 'field staff', 'login_url' => route('login')]])
            ->get(route('admin.field-staff.show', $member))->assertOk()
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private')
            ->assertSee('Login details for Grace Field')->assertSee('Kigali2026x')->assertSee('Resend credentials by email');

        // ...and the staff member can sign in with it.
        auth()->logout();
        $this->post(route('login'), ['email' => 'grace.field@example.com', 'password' => 'Kigali2026x'])->assertRedirect(route('dashboard'));
        auth()->logout();

        // Resend: a new password is generated, emailed and shown once.
        $this->actingAs($this->admin)->post(route('admin.field-staff.resend-credentials', $member))
            ->assertSessionHas('success')->assertSessionHas('issued_credentials', fn ($c) => $c['emailed'] === true && strlen($c['password']) === 12);
        Mail::assertSent(FieldStaffAccountCreated::class, fn ($m) => $m->hasTo('grace.field@example.com') && $m->isReset);
        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('Kigali2026x', $member->user->fresh()->password), 'the old password stops working');

        // Set password manually from the profile.
        $this->actingAs($this->admin)->put(route('admin.field-staff.password', $member), ['password' => 'Musanze2026y', 'send_email' => '0'])
            ->assertSessionHas('issued_credentials', fn ($c) => $c['password'] === 'Musanze2026y');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Musanze2026y', $member->user->fresh()->password));
        $this->actingAs($this->admin)->put(route('admin.field-staff.password', $member), ['password' => 'short'])->assertSessionHasErrors('password');

        // Editing without choosing a new password keeps the current one.
        $this->actingAs($this->admin)->put(route('admin.field-staff.update', $member), [
            'name' => 'Grace Field', 'email' => 'grace.field@example.com', 'phone' => '+250788555111', 'status' => 'active', 'password_mode' => 'keep',
        ])->assertSessionMissing('issued_credentials');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Musanze2026y', $member->user->fresh()->password));

        // Field staff never see the passwords of people they register.
        $fieldUser = $member->user->fresh();
        $this->actingAs($fieldUser)->post(route('field-staff.owners.store'), [
            'owner_type' => 'individual', 'name' => 'Quiet Owner', 'email' => 'quiet.owner@example.com', 'country' => 'Rwanda',
        ])->assertSessionMissing('issued_credentials');

        // Owner resend by an admin shows the details too.
        $this->actingAs($this->admin)->post(route('admin.owners.resend-credentials', $this->owner))
            ->assertSessionHas('issued_credentials', fn ($c) => $c['username'] === $this->ownerUser->email);
    }

    public function test_other_roles_cannot_use_the_field_portal_or_admin_subscriber_pages(): void
    {
        $this->actingAs($this->ownerUser)->get(route('field-staff.dashboard'))->assertForbidden();
        $this->actingAs($this->ownerUser)->get(route('admin.subscribers.index'))->assertForbidden();
        $this->actingAs($this->ownerUser)->get(route('admin.field-staff.index'))->assertForbidden();
    }

    /* ------------------- Subscribers, contracts, billing ------------------- */

    public function test_contract_lifecycle_billing_payments_and_overdue_tracking(): void
    {
        Carbon::setTestNow('2026-11-18 10:00:00');
        $plan = SubscriptionPlan::create(['name' => 'Growth', 'code' => 'growth', 'billing_cycle' => 'monthly', 'price' => 50000, 'currency' => 'RWF']);

        $this->actingAs($this->admin)->get(route('admin.subscriptions.contracts.create', ['subscriber' => $this->ownerUser->id]))->assertOk()->assertSee('Olive Owner');
        $this->actingAs($this->admin)->post(route('admin.subscriptions.contracts.store'), [
            'user_id' => $this->ownerUser->id, 'subscription_plan_id' => $plan->id, 'title' => 'Growth agreement',
            'start_date' => '2026-09-01', 'end_date' => '2027-08-31', 'billing_cycle' => 'monthly', 'amount' => 50000, 'currency' => 'RWF',
            'payment_due_days' => 7, 'auto_renew' => 0,
        ])->assertRedirect();
        $contract = SubscriptionContract::firstOrFail();
        $this->assertSame('pending', $contract->status);
        $this->assertSame(0, $contract->invoices()->count());

        // Activation issues every invoice due so far: Sep, Oct, Nov.
        $this->actingAs($this->admin)->post(route('admin.subscriptions.contracts.transition', $contract), ['action' => 'activate'])->assertSessionHas('success');
        $contract->refresh();
        $this->assertSame('active', $contract->status);
        $this->assertSame(3, $contract->invoices()->count());
        $this->assertEquals(150000, $contract->outstanding());

        // Running billing again creates nothing new.
        $this->assertSame(0, app(SubscriptionBilling::class)->billDue($contract));

        // Payment settles the oldest invoices first.
        $this->actingAs($this->admin)->post(route('admin.subscriptions.contracts.payments.store', $contract), [
            'amount' => 70000, 'method' => 'mobile_money', 'reference' => 'MP123',
        ])->assertSessionHas('success');
        $invoices = $contract->invoices()->orderBy('period_start')->get();
        $this->assertSame(['paid', 'partial', 'unpaid'], $invoices->pluck('status')->all());
        $this->assertEquals(80000, $contract->outstanding());

        // Overdue: invoices due before today and unpaid.
        Carbon::setTestNow('2026-11-30 10:00:00');
        $this->assertSame(2, SubscriptionInvoice::overdue()->count());

        // Dashboard lists the subscriber with balances; filters work.
        $this->actingAs($this->admin)->get(route('admin.subscribers.index', ['billing' => 'overdue']))->assertOk()->assertSee('Olive Owner')->assertSee('RWF 80,000');
        $this->actingAs($this->admin)->get(route('admin.subscribers.show', $this->ownerUser))->assertOk()->assertSee('Growth agreement')->assertSee('MP123');
        $this->actingAs($this->admin)->get(route('admin.subscriptions.invoices', ['filter' => 'overdue']))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.subscriptions.payments'))->assertOk()->assertSee('MP123');
        $this->actingAs($this->admin)->get(route('admin.subscriptions.payments', ['export' => 'csv']))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.subscriptions.contracts.index', ['status' => 'active']))->assertOk()->assertSee($contract->contract_number);

        // The subscriber sees their own subscription.
        $this->actingAs($this->ownerUser)->get(route('subscription.show'))->assertOk()->assertSee($contract->contract_number)->assertSee('overdue');

        // Terminate: future unpaid invoices are voided, status tracked.
        $this->actingAs($this->admin)->post(route('admin.subscriptions.contracts.transition', $contract), ['action' => 'terminate', 'reason' => 'Sold portfolio'])->assertSessionHas('success');
        $this->assertSame('terminated', $contract->fresh()->status);
        Carbon::setTestNow();
    }

    public function test_daily_run_expires_or_renews_contracts(): void
    {
        $expiring = $this->contract(['status' => 'active', 'start_date' => today()->subYear(), 'end_date' => today()->subDay(), 'amount' => 0]);
        $renewing = $this->contract(['status' => 'active', 'start_date' => today()->subYear(), 'end_date' => today()->subDay(), 'auto_renew' => true, 'amount' => 0]);

        $this->artisan('subscriptions:run')->assertSuccessful();

        $this->assertSame('expired', $expiring->fresh()->status);
        $this->assertSame('active', $renewing->fresh()->status);
        $this->assertTrue($renewing->fresh()->end_date->isFuture());
    }

    public function test_admin_emails_and_logs_calls_with_subscribers(): void
    {
        $this->actingAs($this->admin)->post(route('admin.subscribers.email', $this->ownerUser), [
            'subject' => 'Your November invoice', 'body' => "Hello,\nPlease find your invoice.", 'copy_in_app' => 1,
        ])->assertSessionHas('success');
        Mail::assertSent(SubscriberMessage::class, fn ($m) => $m->hasTo($this->ownerUser->email));
        $this->assertTrue($this->ownerUser->notifications()->exists());

        $this->actingAs($this->admin)->post(route('admin.subscribers.calls.store', $this->ownerUser), [
            'channel' => 'phone', 'direction' => 'outbound', 'outcome' => 'answered', 'minutes' => 4, 'seconds' => 30, 'notes' => 'Agreed to pay Friday',
        ])->assertSessionHas('success');
        $this->assertSame(270, SubscriberCall::where('channel', 'phone')->value('duration_seconds'));

        // In-platform call: room created, subscriber invited and allowed in; others are not.
        $this->actingAs($this->admin)->post(route('admin.subscribers.calls.start', $this->ownerUser))->assertRedirect();
        $call = SubscriberCall::where('channel', 'browser')->firstOrFail();
        $this->assertNotEmpty($call->room);
        $this->actingAs($this->ownerUser)->get(route('calls.join', $call))->assertOk()->assertSee($call->room);
        $stranger = User::factory()->create(['role' => 'owner']);
        $this->actingAs($stranger)->get(route('calls.join', $call))->assertForbidden();

        $this->actingAs($this->admin)->post(route('calls.end', $call), ['outcome' => 'answered', 'minutes' => 6])->assertRedirect(route('admin.subscribers.show', $this->ownerUser->id));
        $this->assertSame(360, $call->fresh()->duration_seconds);

        $this->actingAs($this->admin)->get(route('admin.subscribers.show', $this->ownerUser))->assertOk()->assertSee('Agreed to pay Friday')->assertSee('Your November invoice');
    }

    /* ------------------------ Contract documents ------------------------ */

    public function test_contract_pdfs_on_owner_and_tenant_profiles_with_access_control(): void
    {
        $tenantUser = User::factory()->create(['role' => 'tenant']);
        $tenant = Tenant::forceCreate(['user_id' => $tenantUser->id, 'tenant_number' => 'T1', 'full_name' => 'Tina Tenant', 'email' => $tenantUser->email, 'phone' => '+250788111111', 'status' => 'active']);

        // Contracts must be PDF.
        $this->actingAs($this->admin)->post(route('documents.store'), [
            'owner' => 'owner:' . $this->owner->id, 'category' => 'owner_contract', 'file' => UploadedFile::fake()->image('contract.jpg'),
        ])->assertSessionHasErrors('file');

        $this->actingAs($this->admin)->post(route('documents.store'), [
            'owner' => 'owner:' . $this->owner->id, 'category' => 'owner_contract', 'title' => 'Owner agreement 2026',
            'file' => UploadedFile::fake()->create('agreement.pdf', 120, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $ownerDoc = Document::where('category', 'owner_contract')->firstOrFail();
        $this->assertSame(PropertyOwner::class, $ownerDoc->documentable_type);

        $this->actingAs($this->admin)->post(route('documents.store'), [
            'owner' => 'tenant:' . $tenant->id, 'category' => 'tenancy_contract',
            'file' => UploadedFile::fake()->create('tenancy.pdf', 80, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $tenantDoc = Document::where('category', 'tenancy_contract')->firstOrFail();
        $this->assertSame('not_required', $tenantDoc->status, 'contracts do not go through KYC review');

        // Shown on the profiles.
        $this->actingAs($this->admin)->get(route('admin.owners.show', $this->owner))->assertOk()->assertSee('Owner agreement 2026');
        $this->actingAs($this->admin)->get(route('admin.tenants.show', $tenant))->assertOk()->assertSee('Tenancy contract');

        // The person can view their own contract; nobody else can.
        $this->actingAs($this->ownerUser)->get(route('documents.download', $ownerDoc))->assertOk();
        $this->actingAs($tenantUser)->get(route('documents.download', $tenantDoc))->assertOk();
        $this->actingAs($tenantUser)->get(route('documents.download', $ownerDoc))->assertNotFound();
        $this->actingAs($this->fieldStaff())->get(route('documents.download', $ownerDoc))->assertForbidden();

        // Owners and tenants cannot upload contracts themselves.
        $this->actingAs($this->ownerUser)->post(route('documents.store'), [
            'owner' => 'owner:' . $this->owner->id, 'category' => 'owner_contract', 'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ])->assertForbidden();
        $this->actingAs($tenantUser)->post(route('documents.store'), [
            'owner' => 'tenant:' . $tenant->id, 'category' => 'tenancy_contract', 'file' => UploadedFile::fake()->create('y.pdf', 10, 'application/pdf'),
        ])->assertForbidden();

        // Removing keeps the record (soft delete).
        $this->actingAs($this->admin)->delete(route('documents.destroy', $ownerDoc))->assertSessionHas('success');
        $this->assertSoftDeleted($ownerDoc);
    }
}
