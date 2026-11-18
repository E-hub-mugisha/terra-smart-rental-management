<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Conversation;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
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
use Tests\TestCase;

/**
 * Messaging (brief §13) and notifications (brief §12).
 */
class CommunicationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $ownerUser;
    private User $managerUser;
    private User $tenantUser;
    private User $strangerUser;
    private Tenant $tenant;
    private Property $property;
    private Unit $unit;
    private Lease $lease;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->ownerUser = User::factory()->create(['role' => 'owner', 'name' => 'Olive Owner']);
        $this->managerUser = User::factory()->create(['role' => 'manager', 'name' => 'Mark Manager']);
        $this->tenantUser = User::factory()->create(['role' => 'tenant', 'name' => 'Tina Tenant']);
        $this->strangerUser = User::factory()->create(['role' => 'tenant', 'name' => 'Sam Stranger']);

        $owner = PropertyOwner::forceCreate([
            'user_id' => $this->ownerUser->id, 'owner_number' => 'OWN-1', 'name' => 'Olive Owner',
            'email' => $this->ownerUser->email, 'status' => 'active', 'created_by' => $this->admin->id,
        ]);
        $manager = PropertyManager::forceCreate([
            'user_id' => $this->managerUser->id, 'manager_number' => 'MGR-1', 'status' => 'active',
            'phone' => '+250788000000', 'created_by' => $this->admin->id,
        ]);

        $this->property = Property::forceCreate([
            'property_owner_id' => $owner->id, 'property_code' => 'PROP-1', 'name' => 'Kacyiru Heights',
            'property_type' => 'residential', 'status' => 'active', 'created_by' => $this->admin->id,
        ]);
        $this->property->managerAssignments()->forceCreate([
            'property_manager_id' => $manager->id, 'start_date' => now()->subMonth()->toDateString(),
            'status' => 'active', 'assigned_by' => $this->admin->id,
        ]);

        $building = Building::forceCreate(['property_id' => $this->property->id, 'building_code' => 'B1', 'name' => 'Block A', 'status' => 'active']);
        $this->unit = Unit::forceCreate([
            'building_id' => $building->id, 'unit_number' => '101', 'unit_type' => 'apartment',
            'rent_amount' => 350000, 'status' => 'occupied',
        ]);

        $this->tenant = Tenant::forceCreate([
            'user_id' => $this->tenantUser->id, 'tenant_number' => 'TEN-1', 'full_name' => 'Tina Tenant',
            'email' => $this->tenantUser->email, 'phone' => '+250788111111', 'status' => 'active',
        ]);
        $this->lease = Lease::forceCreate([
            'tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id, 'lease_number' => 'LEASE-1',
            'start_date' => now()->subMonths(2)->toDateString(), 'end_date' => now()->addMonths(10)->toDateString(),
            'monthly_rent' => 350000, 'payment_frequency' => 'monthly', 'status' => 'active',
        ]);

        // Ignore any alerts the fixtures themselves produced.
        \Illuminate\Notifications\DatabaseNotification::query()->delete();
    }

    /* ---------------------------- Messaging ---------------------------- */

    public function test_tenant_can_message_their_property_manager_and_manager_is_notified(): void
    {
        $this->actingAs($this->tenantUser)
            ->post(route('messages.store'), [
                'type' => 'direct',
                'recipient_id' => $this->managerUser->id,
                'subject' => 'Leaking tap',
                'body' => 'The kitchen tap is leaking.',
            ])
            ->assertRedirect();

        $conversation = Conversation::firstOrFail();
        $this->assertSame($this->property->id, $conversation->property_id);
        $this->assertSame($this->unit->id, $conversation->unit_id);
        $this->assertCount(2, $conversation->participants);

        $alert = $this->managerUser->unreadNotifications()->first();
        $this->assertNotNull($alert);
        $this->assertSame('message', $alert->data['category']);
        $this->assertStringContainsString('Tina Tenant', $alert->data['title']);

        // Manager sees and opens it; the alert is cleared.
        $this->actingAs($this->managerUser)->get(route('messages.index'))->assertOk()->assertSee('Leaking tap');
        $this->actingAs($this->managerUser)->get(route('messages.show', $conversation))->assertOk()->assertSee('The kitchen tap is leaking.');
        $this->assertSame(0, $this->managerUser->unreadNotifications()->count());
    }

    public function test_replies_do_not_stack_unread_alerts_for_the_same_thread(): void
    {
        $conversation = $this->startConversation();

        foreach (['One', 'Two', 'Three'] as $text) {
            $this->actingAs($this->managerUser)
                ->post(route('messages.reply', $conversation), ['body' => $text])
                ->assertRedirect();
        }

        $this->assertSame(3, $conversation->messages()->where('user_id', $this->managerUser->id)->count());
        $this->assertSame(1, $this->tenantUser->unreadNotifications()->count());
        $this->assertSame(1, $this->tenantUser->unreadConversationCount());
    }

    public function test_ajax_reply_returns_rendered_message(): void
    {
        $conversation = $this->startConversation();

        $this->actingAs($this->managerUser)
            ->postJson(route('messages.reply', $conversation), ['body' => 'On my way'])
            ->assertOk()
            ->assertJsonStructure(['id', 'html'])
            ->assertJsonFragment(['id' => \App\Models\Message::max('id')]);

        $this->actingAs($this->tenantUser)
            ->getJson(route('messages.poll', $conversation) . '?after=0')
            ->assertOk()
            ->assertJsonPath('last_id', \App\Models\Message::max('id'));
    }

    public function test_archived_thread_returns_to_inbox_when_someone_replies(): void
    {
        $conversation = $this->startConversation();

        $this->actingAs($this->tenantUser)->patch(route('messages.archive', $conversation))->assertRedirect();
        $this->actingAs($this->tenantUser)->get(route('messages.index'))->assertDontSee('First message');

        $this->actingAs($this->managerUser)->post(route('messages.reply', $conversation), ['body' => 'Any update?']);

        $this->assertSame(1, $this->tenantUser->unreadConversationCount());
        $this->actingAs($this->tenantUser)->get(route('messages.index'))->assertSee('Any update?');
    }

    public function test_tenant_cannot_message_unconnected_users(): void
    {
        $this->actingAs($this->tenantUser)
            ->post(route('messages.store'), [
                'type' => 'direct',
                'recipient_id' => $this->strangerUser->id,
                'subject' => 'Hi',
                'body' => 'Hello',
            ])
            ->assertSessionHasErrors('recipient_id');

        $this->assertSame(0, Conversation::count());
    }

    public function test_non_participants_cannot_read_conversation_or_attachments(): void
    {

        $this->actingAs($this->tenantUser)->post(route('messages.store'), [
            'type' => 'direct',
            'recipient_id' => $this->managerUser->id,
            'subject' => 'Lease copy',
            'attachments' => [UploadedFile::fake()->create('lease.pdf', 120, 'application/pdf')],
        ])->assertRedirect();

        $conversation = Conversation::firstOrFail();
        $attachment = $conversation->messages()->first()->attachments()->firstOrFail();
        $this->assertUploadExists($attachment->path, true);

        $this->actingAs($this->strangerUser)->get(route('messages.show', $conversation))->assertNotFound();
        $this->actingAs($this->strangerUser)->get(route('messages.attachments.show', $attachment))->assertNotFound();
        $this->actingAs($this->managerUser)->get(route('messages.attachments.show', $attachment))->assertOk();
    }

    public function test_announcements_reach_tenants_who_cannot_reply(): void
    {
        $this->actingAs($this->managerUser)
            ->post(route('messages.store'), [
                'type' => 'announcement',
                'property_id' => $this->property->id,
                'subject' => 'Water interruption on Saturday',
                'body' => 'WASAC maintenance from 8am to noon.',
            ])
            ->assertRedirect();

        $conversation = Conversation::firstOrFail();
        $this->assertTrue($conversation->isAnnouncement());
        $this->assertSame('announcement', $this->tenantUser->unreadNotifications()->first()->data['category']);

        $this->actingAs($this->tenantUser)->get(route('messages.show', $conversation))
            ->assertOk()->assertSee('Replies are turned off');
        $this->actingAs($this->tenantUser)->post(route('messages.reply', $conversation), ['body' => 'Thanks'])
            ->assertForbidden();
    }

    public function test_tenants_cannot_post_announcements_or_target_other_properties(): void
    {
        $this->actingAs($this->tenantUser)->post(route('messages.store'), [
            'type' => 'announcement', 'property_id' => $this->property->id, 'subject' => 'x', 'body' => 'y',
        ])->assertForbidden();

        $other = Property::forceCreate([
            'property_owner_id' => $this->property->property_owner_id, 'property_code' => 'PROP-2', 'name' => 'Other',
            'property_type' => 'residential', 'status' => 'active', 'created_by' => $this->admin->id,
        ]);
        $other->update(['property_owner_id' => PropertyOwner::forceCreate([
            'user_id' => $this->admin->id, 'owner_number' => 'OWN-2', 'name' => 'Someone', 'status' => 'active', 'created_by' => $this->admin->id,
        ])->id]);

        $this->actingAs($this->managerUser)->post(route('messages.store'), [
            'type' => 'announcement', 'property_id' => $other->id, 'subject' => 'x', 'body' => 'y',
        ])->assertForbidden();
    }

    /* -------------------------- Notifications -------------------------- */

    public function test_confirmed_payment_notifies_tenant_and_property_staff(): void
    {
        RentPayment::forceCreate([
            'payment_number' => 'PAY-1', 'tenant_id' => $this->tenant->id, 'lease_id' => $this->lease->id,
            'unit_id' => $this->unit->id, 'recorded_by' => $this->admin->id, 'payment_method' => 'mobile_money',
            'amount' => 350000, 'currency' => 'RWF', 'payment_date' => now()->toDateString(), 'status' => 'confirmed',
        ]);

        $tenantAlert = $this->tenantUser->notifications()->first();
        $this->assertSame('Payment successful', $tenantAlert->data['title']);
        $this->assertStringContainsString('RWF 350,000', $tenantAlert->data['message']);
        $this->assertStringContainsString('/tenant-portal/payments/', $tenantAlert->data['url']);

        foreach ([$this->ownerUser, $this->managerUser] as $staff) {
            $this->assertSame('Rent received', $staff->notifications()->first()?->data['title']);
        }
    }

    public function test_pending_payment_sends_nothing_until_confirmed(): void
    {
        $payment = RentPayment::forceCreate([
            'payment_number' => 'PAY-2', 'tenant_id' => $this->tenant->id, 'lease_id' => $this->lease->id,
            'unit_id' => $this->unit->id, 'recorded_by' => $this->admin->id, 'payment_method' => 'bank',
            'amount' => 100000, 'currency' => 'RWF', 'payment_date' => now()->toDateString(), 'status' => 'pending',
        ]);
        $this->assertSame(0, $this->tenantUser->notifications()->count());

        $payment->update(['status' => 'confirmed']);
        $this->assertSame(1, $this->tenantUser->notifications()->count());
    }

    public function test_maintenance_request_lifecycle_notifies_both_sides(): void
    {
        $request = MaintenanceRequest::forceCreate([
            'request_number' => 'MR-1', 'tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id,
            'title' => 'Broken window', 'description' => 'Bedroom window cracked.', 'category' => 'general',
            'priority' => 'high', 'status' => 'submitted', 'created_by' => $this->tenantUser->id,
        ]);

        $this->assertStringContainsString('Broken window', $this->managerUser->notifications()->first()->data['title']);

        $request->update(['status' => 'in_progress']);
        $alert = $this->tenantUser->notifications()->first();
        $this->assertSame('Maintenance update: In Progress', $alert->data['title']);
    }

    public function test_reminder_command_sends_due_soon_once(): void
    {
        RentInvoice::forceCreate([
            'lease_id' => $this->lease->id, 'tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id,
            'invoice_number' => 'INV-1', 'billing_period_start' => now()->startOfMonth(), 'billing_period_end' => now()->endOfMonth(),
            'issue_date' => now(), 'due_date' => now()->addDays(3)->toDateString(),
            'subtotal' => 350000, 'total_amount' => 350000, 'balance_due' => 350000, 'status' => 'unpaid',
        ]);
        \Illuminate\Notifications\DatabaseNotification::query()->delete(); // drop the "invoice issued" alert

        $this->artisan('notifications:send-reminders')->assertSuccessful();
        $this->artisan('notifications:send-reminders')->assertSuccessful();

        $alerts = $this->tenantUser->notifications()->get();
        $this->assertCount(1, $alerts);
        $this->assertSame('Rent due in 3 days', $alerts->first()->data['title']);
    }

    public function test_reminder_command_sends_overdue_alerts_to_tenant_and_staff(): void
    {
        RentInvoice::forceCreate([
            'lease_id' => $this->lease->id, 'tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id,
            'invoice_number' => 'INV-2', 'billing_period_start' => now()->subMonth(), 'billing_period_end' => now()->subDays(8),
            'issue_date' => now()->subMonth(), 'due_date' => now()->subDays(7)->toDateString(),
            'subtotal' => 350000, 'total_amount' => 350000, 'balance_due' => 200000, 'status' => 'partially_paid',
        ]);
        \Illuminate\Notifications\DatabaseNotification::query()->delete();

        $this->artisan('notifications:send-reminders')->assertSuccessful();

        $this->assertSame('Rent overdue', $this->tenantUser->notifications()->first()->data['title']);
        $this->assertStringContainsString('Rent overdue', $this->managerUser->notifications()->first()->data['title']);
    }

    public function test_notification_centre_open_mark_read_and_preferences(): void
    {
        $this->startConversation();
        $alert = $this->managerUser->unreadNotifications()->firstOrFail();

        $this->actingAs($this->managerUser)->get(route('notifications.index'))->assertOk()->assertSee($alert->data['title']);
        $this->actingAs($this->managerUser)->get(route('notifications.open', $alert->id))
            ->assertRedirect($alert->data['url']);
        $this->assertNotNull($alert->fresh()->read_at);

        $this->actingAs($this->managerUser)->put(route('notifications.preferences'), [])->assertRedirect();
        $this->assertFalse($this->managerUser->fresh()->notify_email);
    }

    public function test_users_cannot_open_someone_elses_notification(): void
    {
        $this->startConversation();
        $alert = $this->managerUser->unreadNotifications()->firstOrFail();

        $this->actingAs($this->tenantUser)->get(route('notifications.open', $alert->id))->assertNotFound();
    }

    public function test_compose_page_lists_only_connected_contacts(): void
    {
        $this->actingAs($this->tenantUser)->get(route('messages.create'))
            ->assertOk()
            ->assertSee('Mark Manager')
            ->assertSee('Olive Owner')
            ->assertDontSee('Sam Stranger');
    }

    private function startConversation(): Conversation
    {
        $this->actingAs($this->tenantUser)->post(route('messages.store'), [
            'type' => 'direct', 'recipient_id' => $this->managerUser->id, 'subject' => 'Hello', 'body' => 'First message',
        ]);

        return Conversation::firstOrFail();
    }
}
