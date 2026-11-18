<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Inspection;
use App\Models\Lease;
use App\Models\Property;
use App\Models\PropertyManager;
use App\Models\PropertyOwner;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** Move-in / move-out inspections and deposit settlement (brief §16). */
class InspectionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $managerUser;
    private User $tenantUser;
    private User $otherManager;
    private Tenant $tenant;
    private Unit $unit;
    private Lease $lease;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $ownerUser = User::factory()->create(['role' => 'owner']);
        $this->managerUser = User::factory()->create(['role' => 'manager', 'name' => 'Mark Manager']);
        $this->tenantUser = User::factory()->create(['role' => 'tenant', 'name' => 'Tina Tenant']);
        $this->otherManager = User::factory()->create(['role' => 'manager']);

        $owner = PropertyOwner::forceCreate(['user_id' => $ownerUser->id, 'owner_number' => 'OWN-1', 'name' => 'Owner', 'status' => 'active', 'created_by' => $this->admin->id]);
        $manager = PropertyManager::forceCreate(['user_id' => $this->managerUser->id, 'manager_number' => 'MGR-1', 'status' => 'active', 'created_by' => $this->admin->id]);
        PropertyManager::forceCreate(['user_id' => $this->otherManager->id, 'manager_number' => 'MGR-2', 'status' => 'active', 'created_by' => $this->admin->id]);

        $property = Property::forceCreate(['property_owner_id' => $owner->id, 'property_code' => 'P-1', 'name' => 'Kacyiru Heights', 'property_type' => 'residential', 'status' => 'active', 'created_by' => $this->admin->id]);
        $property->managerAssignments()->forceCreate(['property_manager_id' => $manager->id, 'start_date' => now()->toDateString(), 'status' => 'active', 'assigned_by' => $this->admin->id]);
        $building = Building::forceCreate(['property_id' => $property->id, 'building_code' => 'B1', 'name' => 'Block A', 'status' => 'active']);

        $this->unit = Unit::forceCreate([
            'building_id' => $building->id, 'unit_number' => '101', 'unit_type' => 'apartment', 'bedrooms' => 2, 'bathrooms' => 1,
            'rent_amount' => 350000, 'status' => 'occupied', 'inventory' => "2 x Dining chairs\nSofa",
        ]);
        $this->tenant = Tenant::forceCreate(['user_id' => $this->tenantUser->id, 'tenant_number' => 'T-1', 'full_name' => 'Tina Tenant', 'email' => $this->tenantUser->email, 'phone' => '0788', 'status' => 'active']);
        $this->lease = Lease::forceCreate([
            'tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id, 'lease_number' => 'L-1',
            'start_date' => now()->subYear()->toDateString(), 'end_date' => now()->addMonth()->toDateString(),
            'monthly_rent' => 350000, 'deposit_amount' => 700000, 'payment_frequency' => 'monthly', 'status' => 'active',
        ]);
        \Illuminate\Notifications\DatabaseNotification::query()->delete();
    }

    public function test_manager_starts_move_in_with_checklist_built_from_unit(): void
    {
        $this->actingAs($this->managerUser)
            ->post(route('inspections.store'), ['lease_id' => $this->lease->id, 'type' => 'move_in'])
            ->assertRedirect();

        $inspection = Inspection::firstOrFail();
        $this->assertSame('draft', $inspection->status);
        $areas = $inspection->items->pluck('area')->unique()->values()->all();
        $this->assertContains('Bedroom 1', $areas);
        $this->assertContains('Bedroom 2', $areas);
        $this->assertContains('Bathroom', $areas);

        $chairs = $inspection->items->firstWhere('item', 'Dining chairs');
        $this->assertSame('Inventory', $chairs->area);
        $this->assertSame(2, $chairs->quantity);

        // Only one move-in per lease.
        $this->actingAs($this->managerUser)
            ->post(route('inspections.store'), ['lease_id' => $this->lease->id, 'type' => 'move_in'])
            ->assertSessionHasErrors('type');
    }

    public function test_unrelated_staff_and_tenants_cannot_access(): void
    {
        $this->actingAs($this->otherManager)
            ->post(route('inspections.store'), ['lease_id' => $this->lease->id, 'type' => 'move_in'])
            ->assertForbidden();

        $inspection = $this->startInspection('move_in');

        $this->actingAs($this->otherManager)->get(route('inspections.show', $inspection))->assertNotFound();
        // Tenants can't see drafts…
        $this->actingAs($this->tenantUser)->get(route('inspections.show', $inspection))->assertNotFound();
        // …or edit anything.
        $this->rateAll($inspection);
        $this->actingAs($this->managerUser)->post(route('inspections.submit', $inspection));
        $this->actingAs($this->tenantUser)->get(route('inspections.edit', $inspection))->assertForbidden();
    }

    public function test_cannot_submit_until_every_item_is_rated(): void
    {
        $inspection = $this->startInspection('move_in');

        $this->actingAs($this->managerUser)->post(route('inspections.submit', $inspection))->assertSessionHasErrors('items');
        $this->assertSame('draft', $inspection->fresh()->status);
    }

    public function test_full_move_in_flow_with_photo_and_tenant_confirmation(): void
    {
        $inspection = $this->startInspection('move_in');
        $wall = $inspection->items()->where('area', 'Living room')->where('item', 'Walls')->first();

        $this->actingAs($this->managerUser)->post(route('inspections.media.store', $inspection), [
            'inspection_item_id' => $wall->id,
            'media' => [UploadedFile::fake()->image('wall.jpg')],
        ])->assertRedirect();
        $media = $inspection->media()->firstOrFail();
        $this->assertUploadExists($media->path, true);

        $this->rateAll($inspection, ['electricity_reading' => 1520.5, 'keys_handed' => 3], [$wall->id => ['condition' => 'fair', 'notes' => 'Small scuff near door']]);

        $this->actingAs($this->managerUser)->post(route('inspections.submit', $inspection))->assertRedirect();
        $this->assertSame('awaiting_tenant', $inspection->fresh()->status);
        $this->assertSame('inspection', $this->tenantUser->notifications()->first()->data['category']);

        // Tenant sees report and the photo.
        $this->actingAs($this->tenantUser)->get(route('inspections.show', $inspection))->assertOk()->assertSee('Small scuff near door');
        $this->actingAs($this->tenantUser)->get(route('inspections.media.show', $media))->assertOk();
        $this->actingAs($this->otherManager)->get(route('inspections.media.show', $media))->assertNotFound();

        // Confirm requires ticking the box.
        $this->actingAs($this->tenantUser)->post(route('inspections.respond', $inspection), ['action' => 'confirm'])->assertSessionHasErrors('agree');
        $this->actingAs($this->tenantUser)->post(route('inspections.respond', $inspection), ['action' => 'confirm', 'agree' => '1'])->assertRedirect();

        $inspection->refresh();
        $this->assertSame('completed', $inspection->status);
        $this->assertNotNull($inspection->completed_at);
        $this->assertSame('Inspection confirmed', $this->managerUser->notifications()->first()->data['title']);
    }

    public function test_tenant_dispute_reopens_inspection_for_staff(): void
    {
        $inspection = $this->startInspection('move_in');
        $this->rateAll($inspection);
        $this->actingAs($this->managerUser)->post(route('inspections.submit', $inspection));

        $this->actingAs($this->tenantUser)->post(route('inspections.respond', $inspection), ['action' => 'dispute'])->assertSessionHasErrors('comment');
        $this->actingAs($this->tenantUser)->post(route('inspections.respond', $inspection), ['action' => 'dispute', 'comment' => 'Window crack missing'])->assertRedirect();

        $this->assertSame('disputed', $inspection->fresh()->status);
        $this->assertSame('Inspection disputed', $this->managerUser->notifications()->first()->data['title']);
        $this->actingAs($this->managerUser)->get(route('inspections.edit', $inspection))->assertOk()->assertSee('Window crack missing');
    }

    public function test_staff_can_record_in_person_confirmation_but_not_tenant_actions(): void
    {
        $inspection = $this->startInspection('move_in');
        $this->rateAll($inspection);
        $this->actingAs($this->managerUser)->post(route('inspections.submit', $inspection));

        $this->actingAs($this->managerUser)->post(route('inspections.respond', $inspection), ['action' => 'confirm', 'agree' => '1'])
            ->assertSessionHasErrors('action');
        $this->actingAs($this->managerUser)->post(route('inspections.respond', $inspection), ['action' => 'confirm_in_person', 'comment' => 'Signed paper copy'])
            ->assertRedirect();

        $this->assertSame('completed', $inspection->fresh()->status);
        $this->assertStringContainsString('Signed paper copy', $inspection->fresh()->tenant_comment);
    }

    public function test_move_out_compares_with_move_in_and_settles_deposit(): void
    {
        // Completed move-in.
        $moveIn = $this->startInspection('move_in');
        $this->rateAll($moveIn, ['electricity_reading' => 1000]);
        $this->actingAs($this->managerUser)->post(route('inspections.submit', $moveIn));
        $this->actingAs($this->tenantUser)->post(route('inspections.respond', $moveIn), ['action' => 'confirm', 'agree' => '1']);

        $this->lease->update(['status' => 'terminated']);
        $moveOut = $this->startInspection('move_out');

        $this->assertSame($moveIn->id, $moveOut->baseline_inspection_id);
        $this->assertSame($moveIn->items()->count(), $moveOut->items()->count());
        $this->assertSame(700000.0, (float) $moveOut->deposit_held);

        $sofa = $moveOut->items()->where('item', 'Sofa')->first();
        $this->rateAll($moveOut, ['electricity_reading' => 2400], [$sofa->id => ['condition' => 'damaged', 'notes' => 'Torn cushion', 'deduction_amount' => 120000]]);

        $moveOut->refresh();
        $this->assertSame('worse', $moveOut->items()->with('baseline')->find($sofa->id)->changeFromBaseline());
        $this->assertSame(120000.0, (float) $moveOut->deposit_deductions);
        $this->assertSame(580000.0, (float) $moveOut->deposit_refund);

        // Settlement is blocked until the tenant confirms.
        $this->actingAs($this->managerUser)->post(route('inspections.settle', $moveOut))->assertStatus(422);

        $this->actingAs($this->managerUser)->post(route('inspections.submit', $moveOut));
        $this->actingAs($this->tenantUser)->get(route('inspections.show', $moveOut))->assertOk()->assertSee('Torn cushion')->assertSee('580,000');
        $this->actingAs($this->tenantUser)->post(route('inspections.respond', $moveOut), ['action' => 'confirm', 'agree' => '1']);

        $this->actingAs($this->managerUser)->post(route('inspections.settle', $moveOut), ['deposit_refund_reference' => 'MOMO-123'])->assertRedirect();
        $this->assertNotNull($moveOut->fresh()->deposit_settled_at);
        $this->assertTrue($this->tenantUser->notifications()->get()->contains(fn ($n) => $n->data['title'] === 'Deposit settled'));

        $this->actingAs($this->managerUser)->get(route('inspections.report', $moveOut))->assertOk()->assertSee('Refund to tenant');
    }

    public function test_index_and_lease_panel_render_for_each_role(): void
    {
        $inspection = $this->startInspection('move_in');

        $this->actingAs($this->managerUser)->get(route('inspections.index'))->assertOk()->assertSee($inspection->inspection_number);
        $this->actingAs($this->admin)->get(route('inspections.index'))->assertOk()->assertSee($inspection->inspection_number);
        $this->actingAs($this->tenantUser)->get(route('inspections.index'))->assertOk()->assertDontSee($inspection->inspection_number);
    }

    /* ------------------------------------------------------------------ */

    private function startInspection(string $type): Inspection
    {
        $this->actingAs($this->managerUser)->post(route('inspections.store'), ['lease_id' => $this->lease->id, 'type' => $type]);

        return Inspection::where('type', $type)->latest('id')->firstOrFail();
    }

    private function rateAll(Inspection $inspection, array $fields = [], array $overrides = []): void
    {
        $items = [];
        foreach ($inspection->items()->get() as $item) {
            $items[$item->id] = ($overrides[$item->id] ?? []) + ['condition' => 'good'];
        }

        $this->actingAs($this->managerUser)
            ->put(route('inspections.update', $inspection), $fields + ['inspection_date' => now()->toDateString(), 'items' => $items])
            ->assertSessionHasNoErrors();
    }
}
