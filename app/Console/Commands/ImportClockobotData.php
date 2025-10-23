<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\User;
use App\Models\Customer;
use App\Models\Project;
use App\Models\WorkLog;

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

    private $idMappings = [
        'users' => [],
        'customers' => [],
        'projects' => [],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Clockobot Data Importer ===');

        // Determine which file to use
        $filePath = $this->getImportFile();
        
        if (!$filePath) {
            return Command::FAILURE;
        }

        $this->info("Using export file: " . basename($filePath));
        
        $data = json_decode(File::get($filePath), true);
        
        if (!$data) {
            $this->error('Failed to parse export file.');
            return Command::FAILURE;
        }

        // Confirm before importing
        if (!$this->confirm('This will import data into your current database. Continue?')) {
            $this->info('Import cancelled.');
            return Command::SUCCESS;
        }

        // Import in order to handle relationships
        $this->importUsers($data['users']);
        $this->importCustomers($data['customers']);
        $this->importProjects($data['projects']);
        $this->importWorkLogs($data['work_logs']);
        
        $this->info('Import completed successfully!');
        $this->showImportSummary();

        return Command::SUCCESS;
    }

    private function getImportFile()
    {
        $exportPath = storage_path('app/exports');
        
        // If a specific file is provided
        if ($this->argument('file')) {
            $filePath = $exportPath . '/' . $this->argument('file');
            if (!File::exists($filePath)) {
                $this->error("File not found: {$filePath}");
                return null;
            }
            return $filePath;
        }

        // Find export files
        $files = File::glob($exportPath . '/clockobot_export_*.json');
        
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
        })->toArray();

        $choice = $this->choice('Select export file to import:', $fileChoices);
        return $exportPath . '/' . $choice;
    }

    private function importUsers($users)
    {
        $this->info('Importing users...');
        
        foreach ($users as $userData) {
            // Check if user already exists by email
            $existingUser = User::where('email', $userData['email'])->first();
            
            if ($existingUser) {
                $this->info("User {$userData['email']} already exists, skipping...");
                $this->idMappings['users'][$userData['old_id']] = $existingUser->id;
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

            $this->idMappings['users'][$userData['old_id']] = $user->id;
            $this->info("Created user: {$user->name} (ID: {$user->id})");
        }
    }

    private function importCustomers($customers)
    {
        $this->info('Importing customers...');
        
        foreach ($customers as $customerData) {
            // Check if customer already exists by name
            $existingCustomer = Customer::where('name', $customerData['name'])->first();
            
            if ($existingCustomer) {
                $this->info("Customer {$customerData['name']} already exists, skipping...");
                $this->idMappings['customers'][$customerData['old_id']] = $existingCustomer->id;
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

            $this->idMappings['customers'][$customerData['old_id']] = $customer->id;
            $this->info("Created customer: {$customer->name} (ID: {$customer->id})");
        }
    }

    private function importProjects($projects)
    {
        $this->info('Importing projects...');
        
        foreach ($projects as $projectData) {
            // Map the customer ID
            $customerId = $this->idMappings['customers'][$projectData['customer_id']] ?? null;
            
            if (!$customerId) {
                $this->error("Cannot find mapped customer ID for project: {$projectData['name']}");
                continue;
            }

            // Check if project already exists
            $existingProject = Project::where('name', $projectData['name'])
                ->where('customer_id', $customerId)
                ->first();
            
            if ($existingProject) {
                $this->info("Project {$projectData['name']} already exists, skipping...");
                $this->idMappings['projects'][$projectData['old_id']] = $existingProject->id;
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

            $this->idMappings['projects'][$projectData['old_id']] = $project->id;
            $this->info("Created project: {$project->name} (ID: {$project->id})");
        }
    }

    private function importWorkLogs($workLogs)
    {
        $this->info('Importing work logs...');
        $progressBar = $this->output->createProgressBar(count($workLogs));
        $imported = 0;
        
        foreach ($workLogs as $workLogData) {
            // Map the IDs
            $projectId = $this->idMappings['projects'][$workLogData['project_id']] ?? null;
            $userId = $this->idMappings['users'][$workLogData['user_id']] ?? null;
            
            if (!$projectId || !$userId) {
                $progressBar->advance();
                continue;
            }

            // Get the project and user to calculate hourly rates
            $project = Project::find($projectId);
            $user = User::find($userId);
            
            if (!$project || !$user) {
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

    private function showImportSummary()
    {
        $this->newLine();
        $this->info("=== Import Summary ===");
        $this->info("Users imported: " . count($this->idMappings['users']));
        $this->info("Customers imported: " . count($this->idMappings['customers']));
        $this->info("Projects imported: " . count($this->idMappings['projects']));
        
        $this->info("\nTotal records in database:");
        $this->info("- Users: " . User::count());
        $this->info("- Customers: " . Customer::count());
        $this->info("- Projects: " . Project::count());
        $this->info("- Work Logs: " . WorkLog::count());
    }
}
