<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Observers\AuditObserver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Throwable;

/**
 * Database updates from the browser, for servers without a terminal.
 * Admins see which migrations are pending, then run them by typing MIGRATE.
 */
class MigrationController extends Controller
{
    public function index(): View
    {
        return view('admin.system.migrations', [
            'pending' => $this->pending(),
            'ranCount' => count($this->ran()),
            'output' => session('migration_output'),
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        $request->validate(
            ['confirm' => ['required', 'in:MIGRATE']],
            ['confirm.*' => 'Type MIGRATE to confirm.']
        );

        $pending = $this->pending();
        if (! $pending) {
            return back()->with('success', 'The database is already up to date.');
        }

        // One run at a time, even if the button is pressed twice.
        $lock = $this->lock();
        if ($lock && ! $lock->get()) {
            return back()->with('error', 'Migrations are already running. Wait a minute and refresh this page.');
        }

        try {
            $exitCode = Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());
        } catch (Throwable $e) {
            report($e);
            $exitCode = 1;
            $output = $e->getMessage();
        } finally {
            $lock?->release();
        }

        $stillPending = $this->pending();
        AuditObserver::record('migrations_run', null, [
            'requested' => $pending,
            'still_pending' => $stillPending,
            'exit_code' => $exitCode,
        ], $request->user()->id);

        return redirect()->route('admin.system.migrations')
            ->with('migration_output', $output)
            ->with($exitCode === 0 && ! $stillPending ? 'success' : 'error', $exitCode === 0 && ! $stillPending
                ? count($pending) . ' ' . \Illuminate\Support\Str::plural('migration', count($pending)) . ' ran successfully.'
                : 'Some migrations did not finish. Check the output below.');
    }

    /** Migration names that exist in database/migrations but have not run yet. */
    private function pending(): array
    {
        $migrator = app('migrator');
        $files = array_keys($migrator->getMigrationFiles([database_path('migrations')]));

        return array_values(array_diff($files, $this->ran()));
    }

    private function ran(): array
    {
        $repository = app('migrator')->getRepository();

        return $repository->repositoryExists() ? $repository->getRan() : [];
    }

    /** A cache lock if the cache store works yet (it may itself need a migration). */
    private function lock()
    {
        try {
            return Cache::lock('admin-run-migrations', 600);
        } catch (Throwable) {
            return null;
        }
    }
}
