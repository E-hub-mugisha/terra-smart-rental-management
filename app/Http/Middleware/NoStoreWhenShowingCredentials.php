<?php

namespace App\Http\Middleware;

use App\Support\IssuedCredentials;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** A page that shows a one-time password must never be cached by the browser. */
class NoStoreWhenShowingCredentials
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->hasSession() && $request->session()->has(IssuedCredentials::SESSION_KEY)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
