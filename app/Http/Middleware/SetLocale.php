<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chooses the interface language (brief §23): the signed-in user's saved
 * preference, else the visitor's session choice, else the browser's
 * Accept-Language (useful for API clients), else English.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(User::LOCALES);

        $locale = $request->user()?->locale
            ?? ($request->hasSession() ? $request->session()->get('locale') : null)
            ?? $request->getPreferredLanguage($supported);

        app()->setLocale(in_array($locale, $supported, true) ? $locale : config('app.locale', 'en'));

        return $next($request);
    }
}
