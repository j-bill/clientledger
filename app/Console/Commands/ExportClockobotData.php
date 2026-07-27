<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportClockobotData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clockobot:export {--host=localhost} {--username=new_superuser} {--password=your_password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export data from clockobot database to JSON format';

    /**
     * @var array{host: string, database: string, username: string, password: string}
     */
    private $sourceConnection;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $host = $this->option('host');
        $username = $this->option('username');
        $password = $this->option('password');

        $this->sourceConnection = [
            'host' => is_string($host) ? $host : '',
            'database' => 'clockobot',
            'username' => is_string($username) ? $username : '',
            'password' => is_string($password) ? $password : '',
        ];

        $this->info('=== Clockobot Data Exporter ===');
        $this->info('Connecting to database...');

        try {
            $pdo = $this->createConnection();

            $this->info('Exporting data from clockobot database...');

            $exportData = [
                'users' => $this->exportUsers($pdo),
                'customers' => $this->exportCustomers($pdo),
                'projects' => $this->exportProjects($pdo),
                'work_logs' => $this->exportWorkLogs($pdo),
                'work_types' => $this->exportWorkTypes($pdo),
                'export_timestamp' => now()->toDateTimeString(),
            ];

            // Create export directory if it doesn't exist
            $exportDir = storage_path('app/exports');
            if (! File::exists($exportDir)) {
                File::makeDirectory($exportDir, 0755, true);
            }

            // Save to JSON file
            $filename = 'clockobot_export_'.now()->format('Y_m_d_H_i_s').'.json';
            $filePath = $exportDir.'/'.$filename;

            $json = json_encode($exportData, JSON_PRETTY_PRINT);
            if ($json === false) {
                $this->error('Failed to encode export data to JSON: '.json_last_error_msg());

                return Command::FAILURE;
            }
            File::put($filePath, $json);

            $this->info("\n=== Export Completed Successfully! ===");
            $this->info("File saved to: {$filePath}");
            $this->info("\nSummary:");
            $this->info('- Users: '.count($exportData['users']));
            $this->info('- Customers: '.count($exportData['customers']));
            $this->info('- Projects: '.count($exportData['projects']));
            $this->info('- Work Logs: '.count($exportData['work_logs']));
            $this->info('- Work Types: '.count($exportData['work_types']));
            $this->info("\nTo import this data, run: php artisan clockobot:import");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Export failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function createConnection(): \PDO
    {
        return new \PDO(
            "mysql:host={$this->sourceConnection['host']};dbname={$this->sourceConnection['database']}",
            $this->sourceConnection['username'],
            $this->sourceConnection['password'],
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]
        );
    }

    /**
     * Run a query and return all rows, failing loudly instead of returning false.
     *
     * @return list<array<string, mixed>>
     */
    private function fetchRows(\PDO $pdo, string $sql): array
    {
        $stmt = $pdo->query($sql);

        if ($stmt === false) {
            throw new \RuntimeException("Query failed: {$sql}");
        }

        $rows = [];

        foreach ($stmt->fetchAll() as $row) {
            if (! is_array($row)) {
                continue;
            }

            $fields = [];

            foreach ($row as $column => $value) {
                if (is_string($column)) {
                    $fields[$column] = $value;
                }
            }

            $rows[] = $fields;
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function exportUsers(\PDO $pdo): array
    {
        $this->info('Exporting users...');
        $users = $this->fetchRows($pdo, 'SELECT * FROM users ORDER BY id');

        return array_map(function ($user) {
            return [
                'old_id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'email_verified_at' => $user['email_verified_at'],
                'password' => $user['password'],
                'role' => $user['is_admin'] ? 'admin' : 'freelancer',
                'hourly_rate' => null,
                'remember_token' => $user['remember_token'],
                'created_at' => $user['created_at'],
                'updated_at' => $user['updated_at'],
                'locale' => $user['locale'] ?? 'en',
                'avatar' => $user['avatar'],
                'notifications' => $user['notifications'] ?? true,
            ];
        }, $users);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function exportCustomers(\PDO $pdo): array
    {
        $this->info('Exporting customers...');
        $clients = $this->fetchRows($pdo, 'SELECT * FROM clients ORDER BY id');

        return array_map(function ($client) {
            return [
                'old_id' => $client['id'],
                'name' => $client['name'],
                'contact_person' => $client['contact_name'],
                'contact_email' => $client['contact_email'],
                'contact_phone' => $client['contact_phone'],
                'address_line_1' => null,
                'address_line_2' => null,
                'city' => null,
                'state' => null,
                'postcode' => null,
                'country' => null,
                'vat_number' => null,
                'hourly_rate' => null,
                'created_at' => $client['created_at'],
                'updated_at' => $client['updated_at'],
            ];
        }, $clients);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function exportProjects(\PDO $pdo): array
    {
        $this->info('Exporting projects...');
        $projects = $this->fetchRows($pdo, 'SELECT * FROM projects ORDER BY id');

        return array_map(function ($project) {
            return [
                'old_id' => $project['id'],
                'name' => $project['title'],
                'description' => $project['description'],
                'customer_id' => $project['client_id'],
                'hourly_rate' => null,
                'deadline' => $project['deadline'],
                'created_at' => $project['created_at'],
                'updated_at' => $project['updated_at'],
                'hour_estimate' => $project['hour_estimate'],
            ];
        }, $projects);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function exportWorkLogs(\PDO $pdo): array
    {
        $this->info('Exporting work logs...');
        $timeEntries = $this->fetchRows($pdo, '
            SELECT te.*, wt.title as work_type_title
            FROM time_entries te
            LEFT JOIN work_types wt ON te.work_type_id = wt.id
            ORDER BY te.id
        ');

        return array_map(function ($entry) {
            $hoursWorked = null;
            $date = null;
            $startTime = null;
            $endTime = null;

            $rawStart = $entry['start'] ?? null;
            $rawEnd = $entry['end'] ?? null;

            if (is_string($rawStart) && $rawStart !== '' && is_string($rawEnd) && $rawEnd !== '') {
                $start = Carbon::parse($rawStart);
                $end = Carbon::parse($rawEnd);

                $date = $start->format('Y-m-d');
                $startTime = $start->format('H:i:s');
                $endTime = $end->format('H:i:s');
                $hoursWorked = $start->diffInMinutes($end) / 60; // Fixed: start->diffInMinutes(end)
            }

            return [
                'old_id' => $entry['id'],
                'project_id' => $entry['project_id'],
                'user_id' => $entry['user_id'],
                'date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'hours_worked' => $hoursWorked,
                'description' => $entry['description'],
                'billable' => (bool) $entry['billable'],
                'hourly_rate' => null,
                'user_hourly_rate' => null,
                'created_at' => $entry['created_at'],
                'updated_at' => $entry['updated_at'],
                'work_type_title' => $entry['work_type_title'],
                'link' => $entry['link'],
                'client_id' => $entry['client_id'],
            ];
        }, $timeEntries);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function exportWorkTypes(\PDO $pdo): array
    {
        $this->info('Exporting work types...');
        $workTypes = $this->fetchRows($pdo, 'SELECT * FROM work_types ORDER BY id');

        return array_map(function ($workType) {
            return [
                'old_id' => $workType['id'],
                'title' => $workType['title'],
                'created_at' => $workType['created_at'],
                'updated_at' => $workType['updated_at'],
            ];
        }, $workTypes);
    }
}
