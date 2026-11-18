<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Global helpers such as upload_url(). Also listed in composer.json "files",
        // but loaded here too so they work even when vendor/ was installed or
        // copied without regenerating Composer's autoloader.
        require_once app_path('helpers.php');

        // Shared hosting: the web root is public_html, not <project>/public.
        // This makes public_path() — and every image upload — use it, for web
        // requests and artisan commands alike.
        if ($publicPath = config('app.public_path')) {
            $this->app->usePublicPath(rtrim($publicPath, '\\/'));
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                strtolower($request->input('email', '')) . '|' . $request->ip()
            );
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        Paginator::useBootstrapFive();

        // Notification engine (brief §12): domain changes → alerts.
        foreach ([
            \App\Models\RentPayment::class,
            \App\Models\RentInvoice::class,
            \App\Models\MaintenanceRequest::class,
            \App\Models\TenantApplication::class,
            \App\Models\Lease::class,
            \App\Models\Unit::class,
        ] as $model) {
            $model::observe(\App\Observers\NotificationObserver::class);
        }

        // Audit trail (brief §24): every change to these records is logged.
        foreach ([
            \App\Models\User::class,
            \App\Models\Property::class,
            \App\Models\Building::class,
            \App\Models\Unit::class,
            \App\Models\PropertyOwner::class,
            \App\Models\PropertyManager::class,
            \App\Models\PropertyManagerAssignment::class,
            \App\Models\Tenant::class,
            \App\Models\TenantApplication::class,
            \App\Models\Lease::class,
            \App\Models\LeaseRequest::class,
            \App\Models\RentInvoice::class,
            \App\Models\RentPayment::class,
            \App\Models\RentPaymentAllocation::class,
            \App\Models\PropertyExpense::class,
            \App\Models\MaintenanceRequest::class,
            \App\Models\Inspection::class,
            \App\Models\Document::class,
            \App\Models\PaymentIntent::class,
        ] as $model) {
            $model::observe(\App\Observers\AuditObserver::class);
        }

        // Security events.
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class,
            fn ($e) => \App\Observers\AuditObserver::record('login', $e->user, null, $e->user->getAuthIdentifier()));
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Logout::class,
            fn ($e) => $e->user && \App\Observers\AuditObserver::record('logout', $e->user, null, $e->user->getAuthIdentifier()));
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Failed::class,
            fn ($e) => \App\Observers\AuditObserver::record('login_failed', null, ['email' => mb_substr((string) ($e->credentials['email'] ?? ''), 0, 120)]));
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\PasswordReset::class,
            fn ($e) => \App\Observers\AuditObserver::record('password_reset', $e->user, null, $e->user->getAuthIdentifier()));
    }
}
