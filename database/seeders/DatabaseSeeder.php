<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SettingsSeeder::class);
        $this->call(UsersSeeder::class);
        $this->call(CustomersSeeder::class);
        $this->call(ProjectsSeeder::class);
        $this->call(WorkLogsSeeder::class);
        $this->call(InvoicesSeeder::class);
    }
}
