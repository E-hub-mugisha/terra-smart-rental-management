<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Admin-only "Database updates" page that runs pending migrations. */
class MigrationRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_see_or_run_migrations(): void
    {
        foreach (['owner', 'manager', 'tenant', 'field_staff'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            // Refused (403), or for a field staff login without a profile, signed out (302 to login).
            $this->assertContains($this->actingAs($user)->get(route('admin.system.migrations'))->status(), [302, 403], $role);
            $this->assertContains($this->actingAs($user)->post(route('admin.system.migrations.run'), ['confirm' => 'MIGRATE'])->status(), [302, 403], $role);
            $this->assertDatabaseMissing('audit_logs', ['event' => 'migrations_run']);
        }

        auth()->logout();
        $this->get(route('admin.system.migrations'))->assertRedirect(route('login'));
    }

    public function test_admin_sees_pending_migrations_and_runs_them_with_confirmation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.system.migrations'))->assertOk()->assertSee('Up to date');

        // Pretend the latest migration has not run yet.
        $latest = DB::table('migrations')->orderByDesc('id')->value('migration');
        app('migrator')->getRepository()->delete((object) ['migration' => $latest]);
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        $this->dropTablesOf($latest);
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $this->actingAs($admin)->get(route('admin.system.migrations'))->assertOk()
            ->assertSee('1 pending')->assertSee($latest);

        // Wrong confirmation: nothing runs.
        $this->actingAs($admin)->post(route('admin.system.migrations.run'), ['confirm' => 'yes'])->assertSessionHasErrors('confirm');
        $this->assertFalse(DB::table('migrations')->where('migration', $latest)->exists());

        $this->actingAs($admin)->post(route('admin.system.migrations.run'), ['confirm' => 'MIGRATE'])
            ->assertRedirect(route('admin.system.migrations'))
            ->assertSessionHas('success', '1 migration ran successfully.');
        $this->assertTrue(DB::table('migrations')->where('migration', $latest)->exists());
        $this->assertDatabaseHas('audit_logs', ['event' => 'migrations_run', 'user_id' => $admin->id]);

        // Running again when up to date does nothing.
        $this->actingAs($admin)->post(route('admin.system.migrations.run'), ['confirm' => 'MIGRATE'])
            ->assertSessionHas('success', 'The database is already up to date.');
    }

    /** Undo what the given migration created so it can run again. */
    private function dropTablesOf(string $migration): void
    {
        $instance = require database_path("migrations/{$migration}.php");
        $instance->down();
    }
}
