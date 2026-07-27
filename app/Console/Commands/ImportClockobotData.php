<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportClockobotData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clockobot:import {file?} {--latest}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import clockobot data from JSON export file';

    /**
     * @var array<string, array<int|string, int>>
     */
    private $idMappings = [
        'users' => [],
        'customers' => [],
        'projects' => [],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== Clockobot Data Importer ===');

        // Determine which file to use
        $filePath = $this->getImportFile();

        if (! $filePath) {
            return Command::FAILURE;
        }

        $this->info('Using export file: '.basename($filePath));

        $data = json_decode(File::get($filePath), true);

        if (! is_array($data) || $data === []) {
            $this->error('Failed to parse export file.');

            return Command::FAILURE;
        }

        // Confirm before importing
        if (! $this->confirm('This will import data into your current database. Continue?')) {
            $this->info('Import cancelled.');

            return Command::SUCCESS;
        }

        // Import in order to handle relationships
        $this->importUsers($this->rows($data, 'users'));
        $this->importCustomers($this->rows($data, 'customers'));
        $this->importProjects($this->rows($data, 'projects'));
        $this->importWorkLogs($this->rows($data, 'work_logs'));

        $this->info('Import completed successfully!');
        $this->showImportSummary();

        return Command::SUCCESS;
    }

    private function getImportFile(): ?string
    {
        $exportPath = storage_path('app/exports');

        // If a specific file is provided
        if ($this->argument('file')) {
            $filePath = $exportPath.'/'.$this->argument('file');
            if (! File::exists($filePath)) {
                $this->error("File not found: {$filePath}");

                return null;
            }

            return $filePath;
        }

        // Find export files
        $files = array_values(array_filter(
            File::glob($exportPath.'/clockobot_export_*.json'),
            'is_string'
        ));

        if (empty($files)) {
            $this->error('No export files found. Please run: php artisan clockobot:export');

            return null;
        }

        // If --latest flag is used, get the most recent file
        if ($this->option('latest')) {
            $latestFile = collect($files)->sortByDesc(function ($file) {
                return File::lastModified($file);
            })->first();

            return $latestFile;
        }

        // Let user choose file
        $fileChoices = collect($files)->map(function ($file) {
            return basename($file);
        })->all();

        $choice = $this->choice('Select export file to import:', $fileChoices);
        if (is_array($choice)) {
            $first = reset($choice);
            $choice = is_scalar($first) ? (string) $first : '';
        }

        return $exportPath.'/'.$choice;
    }

    /**
     * Extract a section of row arrays from the decoded export data.
     *
     * @param  array<mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function rows(array $data, string $key): array
    {
        $section = $data[$key] ?? [];

        if (! is_array($section)) {
            return [];
        }

        $rows = [];

        foreach ($section as $row) {
            if (! is_array($row)) {
                continue;
            }

            $fields = [];

            foreach ($row as $field => $value) {
                if (is_string($field)) {
                    $fields[$field] = $value;
                }
            }

            $rows[] = $fields;
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $users
     */
    private function importUsers($users): void
    {
        $this->info('Importing users...');

        foreach ($users as $userData) {
            $oldId = $userData['old_id'] ?? null;

            if (! is_int($oldId) && ! is_string($oldId)) {
                continue;
            }

            $email = $userData['email'] ?? null;
            $emailLabel = is_scalar($email) ? (string) $email : '';

            // Check if user already exists by email
            $existingUser = User::where('email', $email)->first();

            if ($existingUser) {
                $this->info("User {$emailLabel} already exists, skipping...");
                $this->idMappings['users'][$oldId] = $existingUser->id;

                continue;
            }

            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'email_verified_at' => $userData['email_verified_at'],
                'password' => $userData['password'], // Already hashed from clockobot
                'role' => $userData['role'],
                'hourly_rate' => $userData['hourly_rate'] ?? 50.00, // Default rate
                'remember_token' => $userData['remember_token'],
                'created_at' => $userData['created_at'],
                'updated_at' => $userData['updated_at'],
            ]);

            $this->idMappings['users'][$oldId] = $user->id;
            $this->info("Created user: {$user->name} (ID: {$user->id})");
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $customers
     */
    private function importCustomers($customers): void
    {
        $this->info('Importing customers...');

        foreach ($customers as $customerData) {
            $oldId = $customerData['old_id'] ?? null;

            if (! is_int($oldId) && ! is_string($oldId)) {
                continue;
            }

            $name = $customerData['name'] ?? null;
            $nameLabel = is_scalar($name) ? (string) $name : '';

            // Check if customer already exists by name
            $existingCustomer = Customer::where('name', $name)->first();

            if ($existingCustomer) {
                $this->info("Customer {$nameLabel} already exists, skipping...");
                $this->idMappings['customers'][$oldId] = $existingCustomer->id;

                continue;
            }

            $customer = Customer::create([
                'name' => $customerData['name'],
                'contact_person' => $customerData['contact_person'],
                'contact_email' => $customerData['contact_email'],
                'contact_phone' => $customerData['contact_phone'],
                'address_line_1' => $customerData['address_line_1'],
                'address_line_2' => $customerData['address_line_2'],
                'city' => $customerData['city'],
                'state' => $customerData['state'],
                'postcode' => $customerData['postcode'],
                'country' => $customerData['country'],
                'vat_number' => $customerData['vat_number'],
                'hourly_rate' => $customerData['hourly_rate'] ?? 75.00, // Default rate
                'created_at' => $customerData['created_at'],
                'updated_at' => $customerData['updated_at'],
            ]);

            $this->idMappings['customers'][$oldId] = $customer->id;
            $this->info("Created customer: {$customer->name} (ID: {$customer->id})");
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $projects
     */
    private function importProjects($projects): void
    {
        $this->info('Importing projects...');

        foreach ($projects as $projectData) {
            $oldId = $projectData['old_id'] ?? null;

            if (! is_int($oldId) && ! is_string($oldId)) {
                continue;
            }

            $name = $projectData['name'] ?? null;
            $nameLabel = is_scalar($name) ? (string) $name : '';

            // Map the customer ID
            $oldCustomerId = $projectData['customer_id'] ?? null;
            $customerId = is_int($oldCustomerId) || is_string($oldCustomerId)
                ? ($this->idMappings['customers'][$oldCustomerId] ?? null)
                : null;

            if (! $customerId) {
                $this->error("Cannot find mapped customer ID for project: {$nameLabel}");

                continue;
            }

            // Check if project already exists
            $existingProject = Project::where('name', $name)
                ->where('customer_id', $customerId)
                ->first();

            if ($existingProject) {
                $this->info("Project {$nameLabel} already exists, skipping...");
                $this->idMappings['projects'][$oldId] = $existingProject->id;

                continue;
            }

            $project = Project::create([
                'name' => $projectData['name'],
                'description' => $projectData['description'],
                'customer_id' => $customerId,
                'hourly_rate' => $projectData['hourly_rate'] ?? 75.00, // Default rate
                'deadline' => $projectData['deadline'],
                'created_at' => $projectData['created_at'],
                'updated_at' => $projectData['updated_at'],
            ]);

            $this->idMappings['projects'][$oldId] = $project->id;
            $this->info("Created project: {$project->name} (ID: {$project->id})");
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $workLogs
     */
    private function importWorkLogs($workLogs): void
    {
        $this->info('Importing work logs...');
        $progressBar = $this->output->createProgressBar(count($workLogs));
        $imported = 0;

        foreach ($workLogs as $workLogData) {
            // Map the IDs
            $oldProjectId = $workLogData['project_id'] ?? null;
            $oldUserId = $workLogData['user_id'] ?? null;

            $projectId = is_int($oldProjectId) || is_string($oldProjectId)
                ? ($this->idMappings['projects'][$oldProjectId] ?? null)
                : null;
            $userId = is_int($oldUserId) || is_string($oldUserId)
                ? ($this->idMappings['users'][$oldUserId] ?? null)
                : null;

            if (! $projectId || ! $userId) {
                $progressBar->advance();

                continue;
            }

            // Get the project and user to calculate hourly rates
            $project = Project::find($projectId);
            $user = User::find($userId);

            if (! $project || ! $user) {
                $progressBar->advance();

                continue;
            }

            // Calculate hourly rates
            $projectHourlyRate = $project->hourly_rate ?? $project->customer->hourly_rate ?? 75.00;
            $userHourlyRate = $user->hourly_rate ?? 50.00;

            WorkLog::create([
                'project_id' => $projectId,
                'user_id' => $userId,
                'date' => $workLogData['date'],
                'start_time' => $workLogData['start_time'],
                'end_time' => $workLogData['end_time'],
                'hours_worked' => $workLogData['hours_worked'],
                'description' => $workLogData['description'] ?? 'Imported from Clockobot',
                'billable' => $workLogData['billable'],
                'hourly_rate' => $projectHourlyRate,
                'user_hourly_rate' => $userHourlyRate,
                'created_at' => $workLogData['created_at'],
                'updated_at' => $workLogData['updated_at'],
            ]);

            $imported++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Imported {$imported} work logs");
    }

    private function showImportSummary(): void
    {
        $this->newLine();
        $this->info('=== Import Summary ===');
        $this->info('Users imported: '.count($this->idMappings['users']));
        $this->info('Customers imported: '.count($this->idMappings['customers']));
        $this->info('Projects imported: '.count($this->idMappings['projects']));

        $this->info("\nTotal records in database:");
        $this->info('- Users: '.User::count());
        $this->info('- Customers: '.Customer::count());
        $this->info('- Projects: '.Project::count());
        $this->info('- Work Logs: '.WorkLog::count());
    }
}
