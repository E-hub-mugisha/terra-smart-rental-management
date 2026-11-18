<?php

namespace Tests\Feature;

use App\Models\Lease;
use App\Models\Property;
use App\Models\RentInvoice;
use App\Models\User;
use Database\Seeders\TerraDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** The Rwanda demo dataset seeds cleanly, is consistent, and sends nothing. */
class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_builds_a_consistent_portfolio_without_side_effects(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Notification::fake();
        Mail::fake();

        $this->seed(TerraDemoSeeder::class);

        $this->assertSame(7, Property::count());
        $this->assertGreaterThan(50, Lease::where('status', 'active')->count());
        foreach (['draft', 'pending_tenant_acceptance', 'pending_activation', 'terminated'] as $status) {
            $this->assertSame(1, Lease::where('status', $status)->count(), "one {$status} lease for training");
        }
        $this->assertSame(0, RentInvoice::whereRaw('abs(amount_paid + balance_due - total_amount) > 0.01')->count());
        $this->assertTrue(Lease::where('status', 'active')->whereHas('unit', fn ($q) => $q->where('status', '!=', 'occupied'))->doesntExist());
        $this->assertTrue(User::where('email', 'like', '%@' . TerraDemoSeeder::DOMAIN)->where('notify_email', true)->doesntExist());

        Notification::assertNothingSent();
        Mail::assertNothingSent();
        Storage::disk('public')->assertExists('demo/properties/TRD-KCY-0.jpg');

        // Demo accounts can sign in.
        $this->post(route('login'), ['email' => 'tenant.aline@' . TerraDemoSeeder::DOMAIN, 'password' => TerraDemoSeeder::DEMO_PASSWORD])
            ->assertRedirect(route('dashboard'));

        // Running again does nothing.
        $this->seed(TerraDemoSeeder::class);
        $this->assertSame(7, Property::count());
    }
}
