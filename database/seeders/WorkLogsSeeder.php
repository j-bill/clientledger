<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\WorkLog;
use Carbon\Carbon;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Database\Seeder;

class WorkLogsSeeder extends Seeder
{
    /** @var array<string, list<string>> */
    private array $taskDescriptions = [
        'Frontend Development' => [
            'Implemented responsive React component for dashboard with dynamic data visualization, state management integration, and comprehensive error handling. Added unit tests and reviewed code with team members. Optimized rendering performance and ensured cross-browser compatibility.',
            'Refactored legacy Vue.js templates to modern Composition API standards. Improved performance by 40% through component memoization and optimized rendering pipelines. Added comprehensive documentation and TypeScript type definitions. Conducted peer review sessions.',
            'Built interactive invoice generation interface with real-time calculation, multi-select work log picker, and advanced filtering capabilities. Implemented client-side validation, error recovery, and user feedback mechanisms. Created accessible UI components following WCAG guidelines.',
            'Designed and implemented dark mode toggle feature with persistent user preferences stored in localStorage. Updated all components for theme consistency and accessibility compliance. Added smooth transitions and tested across all supported browsers and devices.',
            'Created reusable component library with Storybook integration. Documented all components with usage examples and prop specifications. Implemented automated visual regression testing. Collaborated with design team to ensure consistent branding.',
            'Optimized bundle size by implementing code splitting and lazy loading strategies. Reduced initial load time by 55% through strategic webpack configuration. Analyzed bundle metrics and documented performance improvements for stakeholders.',
        ],
        'Backend Development' => [
            'Developed Laravel RESTful API endpoints for invoice management including creation, updating, deletion with proper authorization checks. Added database transactions for data integrity. Implemented comprehensive logging and error tracking mechanisms for debugging.',
            'Created database migration for new customer fields including address, city, state, zip, country, and website URL. Ensured backward compatibility and wrote rollback procedures. Documented schema changes and updated database documentation.',
            'Implemented complex billing rate hierarchy system respecting project rates, customer rates, and default fallback rates. Added comprehensive unit tests covering edge cases and boundary conditions. Optimized query performance through proper indexing strategy.',
            'Refactored invoice calculation logic to handle multiple billing scenarios. Optimized database queries reducing N+1 problems. Performance improved by 60% on large datasets. Added caching layer for frequently accessed calculations.',
            'Built comprehensive error logging and monitoring system with detailed stack traces. Integrated with error tracking service for real-time alerting and analytics. Created dashboard for monitoring application health and performance metrics.',
            'Implemented JWT-based authentication system with refresh token rotation. Added multi-factor authentication support and rate limiting. Conducted security audit and documented authentication procedures.',
        ],
        'Database Management' => [
            'Optimized work_logs table with strategic indexing on frequently queried columns. Query performance improved by 75% for monthly report generation. Analyzed execution plans and documented optimization results. Performed load testing to verify improvements under high traffic.',
            'Created database backup procedures and tested disaster recovery scenarios. Documented restoration processes and created automated backup scheduling. Implemented point-in-time recovery capability and tested restoration accuracy.',
            'Performed data migration from legacy system ensuring data integrity and consistency. Wrote validation scripts to verify data accuracy across all tables. Created rollback procedures and documented migration process for future reference.',
        ],
        'Testing & QA' => [
            'Wrote comprehensive unit tests for invoice calculation logic covering all billing rate combinations, edge cases, and boundary conditions. Achieved 95% code coverage using PHPUnit and pytest. Documented test cases and maintained test documentation.',
            'Performed end-to-end testing of invoice workflow including generation, editing, and deletion. Documented test cases and created manual testing checklist for QA team. Identified and reported 15 bugs with detailed reproduction steps.',
            'Created automated Playwright tests for critical user paths in invoice management system. Implemented CI/CD pipeline integration for continuous testing. Set up parallel test execution reducing test suite runtime by 70% and monitored test stability.',
        ],
        'Documentation & Communication' => [
            'Updated API documentation with new endpoints, request/response schemas, and authentication requirements. Added code examples for common use cases and documented error responses. Created interactive API documentation using OpenAPI specification.',
            'Prepared technical specification document for invoice system redesign. Documented all business rules, validation requirements, and data relationships. Presented findings to stakeholders and incorporated feedback into final specification.',
            'Conducted knowledge transfer session with new team member. Explained architecture decisions, code conventions, and deployment procedures. Created detailed onboarding documentation and recorded video tutorials for reference.',
        ],
        'Bug Fixes & Maintenance' => [
            'Fixed critical bug in invoice total calculation that was causing incorrect amounts for projects with variable hourly rates. Added regression tests to prevent future issues. Analyzed root cause and documented findings in incident report.',
            'Resolved race condition in concurrent invoice creation that was causing duplicate invoice numbers. Implemented proper locking mechanism using database transactions. Added comprehensive tests for concurrent scenarios.',
            'Updated deprecated dependencies and resolved security vulnerabilities in composer.json. Ran security audit using multiple scanning tools and documented all changes. Created dependency update strategy for ongoing maintenance.',
            'Patched timezone handling issues causing discrepancies in work log time tracking. Standardized all times to UTC with proper conversion logic. Tested across multiple timezone configurations to ensure accuracy.',
        ],
    ];

    /**
     * Build a description of varying length (up to 1500 chars, the API limit)
     * by stitching together entries from the category's pool, so seeded data
     * exercises short, medium, and near-limit descriptions.
     */
    private function makeDescription(string $category, Generator $faker): string
    {
        $pool = $this->taskDescriptions[$category];
        $targetLength = $faker->numberBetween(200, 1500);

        $description = $pool[array_rand($pool)];
        while (strlen($description) < $targetLength) {
            $description .= ' '.$pool[array_rand($pool)];
        }

        if (strlen($description) > 1500) {
            // Cut at the last sentence boundary that fits.
            $cut = strrpos(substr($description, 0, 1500), '.');
            $description = substr($description, 0, $cut === false ? 1500 : $cut + 1);
        }

        return $description;
    }

    public function run(): void
    {
        $faker = Factory::create();
        $now = Carbon::now();

        $projects = Project::with('users')->get();
        $taskCategories = array_keys($this->taskDescriptions);

        // 13 real calendar-month buckets: the 12 completed months InvoicesSeeder
        // will bill, plus the current (partial) month. Oldest first.
        $monthOffsets = range(12, 0);

        // Compounding target for total *billable dollar volume* per month, so
        // the revenue trend chart climbs steadily rather than zig-zagging.
        $baseMonthlyRevenue = 5000;
        $growthRate = 1.15;

        $previousMonthActual = 0;

        foreach ($monthOffsets as $index => $monthsAgo) {
            $monthStart = $now->copy()->subMonths($monthsAgo)->startOfMonth();
            $isCurrentMonth = $monthStart->isSameMonth($now);
            $monthEnd = $isCurrentMonth ? $now->copy() : $monthStart->copy()->endOfMonth();

            $target = $baseMonthlyRevenue * pow($growthRate, $index);
            // Hard floor: never budget less than a real step up from last month.
            $target = max($target, $previousMonthActual * 1.08);

            $logsThisMonth = (int) round(20 + $index * 5); // busier every month
            $billableFraction = 0.9;

            $createdLogs = [];
            $daySpan = max($monthStart->diffInDays($monthEnd), 1);

            for ($n = 0; $n < $logsThisMonth; $n++) {
                $project = $projects->random();
                $freelancer = $project->users()->inRandomOrder()->first();
                if (! $freelancer) {
                    continue;
                }

                $dayOffset = $logsThisMonth > 1
                    ? intval(($n / ($logsThisMonth - 1)) * $daySpan)
                    : 0;
                $date = $monthStart->copy()->addDays($dayOffset);

                $isBillable = $faker->boolean($billableFraction * 100);

                // Hours derived from this log's share of the month's dollar
                // target, so the aggregate lands near target regardless of
                // which (differently-rated) project got picked.
                $perLogTarget = $target / ($logsThisMonth * $billableFraction);
                $hours = ($perLogTarget / max($project->hourly_rate, 1)) * $faker->randomFloat(2, 0.85, 1.15);
                $hours = max(1, min(round($hours * 2) / 2, 10));

                $startHour = rand(8, 11);

                $category = $taskCategories[array_rand($taskCategories)];
                $description = $this->makeDescription($category, $faker);

                $createdLogs[] = WorkLog::create([
                    'project_id' => $project->id,
                    'user_id' => $freelancer->id,
                    'date' => $date,
                    'start_time' => sprintf('%02d:00', $startHour),
                    'end_time' => sprintf('%02d:00', $startHour + $hours),
                    'hours_worked' => $hours,
                    'description' => $description,
                    'billable' => $isBillable,
                    'hourly_rate' => $project->hourly_rate,
                    'user_hourly_rate' => $freelancer->getProjectHourlyRate($project),
                ]);
            }

            // Guarantee this month's billable dollar total is safely above
            // last month's, topping up with extra logs if variance fell short.
            // Looped (not a single log) because a large shortfall can exceed
            // what one hours-capped log can cover.
            $actual = collect($createdLogs)
                ->filter(fn ($log) => $log->billable)
                ->sum(fn ($log) => $log->hours_worked * $log->hourly_rate);

            $minRequired = $previousMonthActual * 1.05;
            $topUpProject = $projects->sortByDesc('hourly_rate')
                ->first(fn ($p) => $p->users()->exists());

            $safety = 0;
            while ($previousMonthActual > 0 && $actual < $minRequired && $topUpProject && $safety < 50) {
                $freelancer = $topUpProject->users()->inRandomOrder()->first();
                if (! $freelancer) {
                    break;
                }
                $hours = min(12, max(1, ceil(($minRequired - $actual) / $topUpProject->hourly_rate)));
                $startHour = rand(8, 11);

                WorkLog::create([
                    'project_id' => $topUpProject->id,
                    'user_id' => $freelancer->id,
                    'date' => $monthEnd->copy(),
                    'start_time' => sprintf('%02d:00', $startHour),
                    'end_time' => sprintf('%02d:00', $startHour + $hours),
                    'hours_worked' => $hours,
                    'description' => $this->makeDescription($taskCategories[array_rand($taskCategories)], $faker),
                    'billable' => true,
                    'hourly_rate' => $topUpProject->hourly_rate,
                    'user_hourly_rate' => $freelancer->getProjectHourlyRate($topUpProject),
                ]);

                $actual += $hours * $topUpProject->hourly_rate;
                $safety++;
            }

            $previousMonthActual = $actual;
        }
    }
}
