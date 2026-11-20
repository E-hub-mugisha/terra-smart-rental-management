<?php

namespace App\Support;

/**
 * Login details shown ONCE to the administrator who just created or reset an
 * account. Passwords are stored hashed, so this is the only moment the
 * plain password exists; it lives in the session for a single page view.
 */
final class IssuedCredentials
{
    public const SESSION_KEY = 'issued_credentials';

    public static function flash(string $name, string $username, string $password, bool $emailed, string $role = 'account'): void
    {
        // Only administrators ever see a plain password (never field staff registering people).
        if (! auth()->user()?->isAdmin()) {
            return;
        }

        session()->flash(self::SESSION_KEY, [
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'emailed' => $emailed,
            'role' => $role,
            'login_url' => route('login'),
        ]);
    }

    /** A readable password with no look-alike characters (0/O, 1/l/I). */
    public static function generatePassword(int $length = 12): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        do {
            $password = '';
            for ($i = 0; $i < $length; $i++) {
                $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (! preg_match('/[a-z]/', $password) || ! preg_match('/[A-Z]/', $password) || ! preg_match('/\d/', $password));

        return $password;
    }
}
