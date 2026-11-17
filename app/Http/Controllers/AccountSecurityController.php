<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Observers\AuditObserver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Account → Security & language: two-step sign-in, API tokens, interface language. */
class AccountSecurityController extends Controller
{
    public function show(Request $request)
    {
        return view('account.security', [
            'user' => $request->user(),
            'tokens' => $request->user()->tokens()->latest()->get(),
            'newToken' => session('new_token'),
        ]);
    }

    public function twoFactor(Request $request)
    {
        $user = $request->user();
        $data = $request->validate(['enable' => ['required', 'boolean'], 'password' => ['required', 'string']]);

        if (! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => 'That password is not correct.']);
        }

        $user->update(['two_factor_enabled' => (bool) $data['enable']]);
        AuditObserver::record($data['enable'] ? 'two_factor_enabled' : 'two_factor_disabled', $user);

        return back()->with('success', $data['enable']
            ? 'Two-step sign-in is on. Next time you sign in we will email you a code.'
            : 'Two-step sign-in is off.');
    }

    public function createToken(Request $request)
    {
        $user = $request->user();
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'password' => ['required', 'string']]);
        if (! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => 'That password is not correct.']);
        }

        $abilities = match ($user->role) {
            'tenant' => ['tenant'], 'technician' => ['technician'], 'accountant' => ['finance'], 'agent' => ['agent'],
            'admin', 'manager', 'owner' => ['staff'], default => [],
        };
        $token = $user->createToken($data['name'], $abilities, now()->addYear());
        AuditObserver::record('api_token_created', $user, ['name' => $data['name']]);

        return back()->with('new_token', $token->plainTextToken)->with('success', 'Token created. Copy it now — it will not be shown again.');
    }

    public function revokeToken(Request $request, int $token)
    {
        $request->user()->tokens()->whereKey($token)->firstOrFail()->delete();
        AuditObserver::record('api_token_revoked', $request->user(), ['token_id' => $token]);

        return back()->with('success', 'Token revoked.');
    }

    /** Language switch (brief §23). Works for guests too (stored in the session). */
    public function locale(Request $request)
    {
        $locale = $request->validate(['locale' => ['required', Rule::in(array_keys(User::LOCALES))]])['locale'];

        $request->session()->put('locale', $locale);
        $request->user()?->update(['locale' => $locale]);

        return back();
    }
}
