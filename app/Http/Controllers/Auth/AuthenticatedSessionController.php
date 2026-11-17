<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Two-step sign-in (brief §24): correct password → emailed code before the session starts.
        $candidate = \App\Models\User::where('email', $credentials['email'])->first();
        if ($candidate?->two_factor_enabled && \Illuminate\Support\Facades\Hash::check($credentials['password'], $candidate->password)) {
            app(\App\Http\Controllers\Auth\TwoFactorController::class)->sendCode($candidate);
            $request->session()->put('two_factor', ['user_id' => $candidate->id, 'remember' => $request->boolean('remember')]);

            return redirect()->route('two-factor.challenge');
        }

        if (! Auth::attempt(
            $credentials,
            $request->boolean('remember')
        )) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}