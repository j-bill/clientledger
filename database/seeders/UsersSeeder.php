<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.de',
            'password' => bcrypt('adminadmin'),
            'role' => 'admin',
            'hourly_rate' => 120,
            'email_verified_at' => now(),
        ]);

        // E2E test admin: 2FA enabled with a pre-trusted device so Playwright
        // can log in without the 2FA setup/challenge flow. The e2e helper sets
        // localStorage.device_fingerprint to this client fingerprint, which the
        // axios interceptor sends as the X-Device-Fingerprint header.
        $e2eAdmin = User::create([
            'name' => 'E2E Admin',
            'email' => 'e2e@admin.de',
            'password' => bcrypt('adminadmin'),
            'role' => 'admin',
            'hourly_rate' => 120,
            'email_verified_at' => now(),
        ]);
        $e2eAdmin->forceFill([
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_confirmed_at' => now(),
            'two_factor_device_fingerprints' => [[
                'fingerprint' => 'e2e-seeded-server-fingerprint',
                'client_fingerprint' => 'e2e-trusted-device',
                'user_agent' => 'playwright-e2e',
                'added_at' => now()->timestamp,
                'expires_at' => now()->addYears(10)->timestamp,
            ]],
        ])->save();

        // Create freelancers with different hourly rates
        User::create([
            'name' => 'Alex Thompson',
            'email' => 'alex@example.com',
            'password' => bcrypt('password'),
            'role' => 'freelancer',
            'hourly_rate' => 85,
        ]);

        User::create([
            'name' => 'Sarah Chen',
            'email' => 'sarah@example.com',
            'password' => bcrypt('password'),
            'role' => 'freelancer',
            'hourly_rate' => 95,
        ]);

        User::create([
            'name' => 'Marcus Rodriguez',
            'email' => 'marcus@example.com',
            'password' => bcrypt('password'),
            'role' => 'freelancer',
            'hourly_rate' => 110,
        ]);

        User::create([
            'name' => 'Emma Wilson',
            'email' => 'emma@example.com',
            'password' => bcrypt('password'),
            'role' => 'freelancer',
            'hourly_rate' => 90,
        ]);
    }
}
