<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\TwoFactorCode;
use App\Observers\AuditObserver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/** Two-step sign-in with a 6-digit code sent by email (brief §24). */
class TwoFactorController extends Controller
{
    private const TTL_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    public function challenge(Request $request)
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        return view('auth.two-factor', ['email' => $this->mask($user->email)]);
    }

    public function verify(Request $request)
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'Your sign-in session expired. Please sign in again.']);
        }

        $code = preg_replace('/\D/', '', (string) $request->validate(['code' => ['required', 'string', 'max:12']])['code']);

        $valid = $user->two_factor_code
            && $user->two_factor_expires_at?->isFuture()
            && $user->two_factor_attempts < self::MAX_ATTEMPTS
            && Hash::check($code, $user->two_factor_code);

        if (! $valid) {
            $user->increment('two_factor_attempts');
            AuditObserver::record('two_factor_failed', $user, null, $user->id);
            $message = $user->two_factor_attempts >= self::MAX_ATTEMPTS || ! $user->two_factor_expires_at?->isFuture()
                ? 'This code has expired or too many attempts were made. Send a new code.'
                : 'That code is not correct.';
            throw ValidationException::withMessages(['code' => $message]);
        }

        $remember = (bool) ($request->session()->get('two_factor.remember'));
        $request->session()->forget('two_factor');
        $user->forceFill(['two_factor_code' => null, 'two_factor_expires_at' => null, 'two_factor_attempts' => 0])->save();

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function resend(Request $request)
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        $this->sendCode($user);

        return back()->with('status', 'A new code has been sent.');
    }

    public function sendCode(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'two_factor_code' => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'two_factor_attempts' => 0,
        ])->save();

        $user->notify(new TwoFactorCode($code, self::TTL_MINUTES));
    }

    private function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get('two_factor.user_id');

        return $id ? User::find($id) : null;
    }

    private function mask(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($name, 0, 2) . str_repeat('•', max(1, mb_strlen($name) - 2)) . '@' . $domain;
    }
}
