<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Field staff may only register owners and managers. Every other page —
 * admin, finance, properties, settings, documents — is closed to them,
 * whatever links they find. Suspended field staff are signed out.
 */
class RestrictFieldStaff
{
    /** Route names (patterns) a field staff account may open. */
    public const ALLOWED = [
        'field-staff.*',
        'dashboard',
        'logout',
        'home',
        'messages.*',
        'notifications.*',
        'account.security',
        'account.security.two-factor',
        'locale.update',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->role !== 'field_staff') {
            return $next($request);
        }

        $profile = $user->fieldStaff;
        if (! $profile || ! $profile->isActive()) {
            auth()->guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Your field staff account is suspended. Contact a Terra administrator.']);
        }

        $route = $request->route()?->getName();
        if (! $route || ! $request->routeIs(...self::ALLOWED)) {
            abort(403, 'Field staff accounts can only register property owners and managers.');
        }

        // Activity heartbeat for the admin monitor (at most once a minute).
        if (! $profile->last_active_at || $profile->last_active_at->lt(now()->subMinute())) {
            $profile->forceFill(['last_active_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
