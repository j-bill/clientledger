<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

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
            'email_verified_at' => now()
        ]);

        // Create freelancers with different hourly rates
        User::create([
            'name' => 'Alex Thompson',
            'email' => 'alex@example.com',
            'password' => bcrypt('password'),
            'role' => 'freelancer',
            'hourly_rate' => 85
        ]);

        User::create([
            'name' => 'Sarah Chen',
            'email' => 'sarah@example.com',
            'password' => bcrypt('password'),
            'role' => 'freelancer',
            'hourly_rate' => 95
        ]);

        User::create([
            'name' => 'Marcus Rodriguez',
            'email' => 'marcus@example.com',
            'password' => bcrypt('password'),
            'role' => 'freelancer',
            'hourly_rate' => 110
        ]);

        User::create([
            'name' => 'Emma Wilson',
            'email' => 'emma@example.com',
            'password' => bcrypt('password'),
            'role' => 'freelancer',
            'hourly_rate' => 90
        ]);
    }
}
