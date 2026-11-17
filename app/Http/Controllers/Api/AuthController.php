<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Observers\AuditObserver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Token sign-in for the mobile app and third-party integrations (brief §22, §25–26).
 * Accounts with two-step sign-in enabled must use the web app to sign in.
 */
class AuthController extends Controller
{
    public function token(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            AuditObserver::record('api_login_failed', null, ['email' => mb_substr($data['email'], 0, 120)]);
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        if ($user->two_factor_enabled) {
            throw ValidationException::withMessages(['email' => 'This account uses two-step sign-in. Create an API token from Account → Security in the web app.']);
        }

        $token = $user->createToken($data['device_name'], $this->abilities($user), now()->addDays(90));
        AuditObserver::record('api_token_created', $user, ['device' => $data['device_name']], $user->id);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'user' => self::user($user),
        ], 201);
    }

    public function revoke(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->noContent();
    }

    public function me(Request $request)
    {
        return ['data' => self::user($request->user())];
    }

    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'locale' => $user->locale,
            'tenant_id' => $user->tenant?->id,
            'unread_notifications' => $user->unreadNotifications()->count(),
            'unread_conversations' => $user->unreadConversationCount(),
        ];
    }

    /** Abilities follow the role; endpoints still check access to each record. */
    private function abilities(User $user): array
    {
        return match ($user->role) {
            'tenant' => ['tenant'],
            'technician' => ['technician'],
            'admin', 'manager', 'owner' => ['staff'],
            'accountant' => ['finance'],
            'agent' => ['agent'],
            default => [],
        };
    }
}
