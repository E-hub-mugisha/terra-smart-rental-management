<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class PropertyProSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException(
                'PropertyProSeeder is restricted to local development.'
            );
        }

        $email = env('PROPERTYPRO_ADMIN_EMAIL');
        $password = env('PROPERTYPRO_ADMIN_PASSWORD');

        if (! $email || ! $password || strlen($password) < 16) {
            throw new RuntimeException(
                'Configure a unique administrator email and a password of at least 16 characters in .env.'
            );
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'PropertyPro Administrator',
                'password' => Hash::make($password),
                'role' => 'admin',
                'email_verified_at' => now(),
                'remember_token' => Str::random(60),
            ]
        );
    }
}