<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\WorkLog;
use Carbon\Carbon;

class WorkLogsSeeder extends Seeder
{
    private array $taskDescriptions = [
        'Frontend Development' => [
            'Implemented responsive React component for dashboard with dynamic data visualization, state management integration, and comprehensive error handling. Added unit tests and reviewed code with team members. Optimized rendering performance and ensured cross-browser compatibility.',
            'Refactored legacy Vue.js templates to modern Composition API standards. Improved performance by 40% through component memoization and optimized rendering pipelines. Added comprehensive documentation and TypeScript type definitions. Conducted peer review sessions.',
            'Built interactive invoice generation interface with real-time calculation, multi-select work log picker, and advanced filtering capabilities. Implemented client-side validation, error recovery, and user feedback mechanisms. Created accessible UI components following WCAG guidelines.',
            'Designed and implemented dark mode toggle feature with persistent user preferences stored in localStorage. Updated all components for theme consistency and accessibility compliance. Added smooth transitions and tested across all supported browsers and devices.',
            'Created reusable component library with Storybook integration. Documented all components with usage examples and prop specifications. Implemented automated visual regression testing. Collaborated with design team to ensure consistent branding.',
            'Optimized bundle size by implementing code splitting and lazy loading strategies. Reduced initial load time by 55% through strategic webpack configuration. Analyzed bundle metrics and documented performance improvements for stakeholders.'
        ],
        'Backend Development' => [
            'Developed Laravel RESTful API endpoints for invoice management including creation, updating, deletion with proper authorization checks. Added database transactions for data integrity. Implemented comprehensive logging and error tracking mechanisms for debugging.',
            'Created database migration for new customer fields including address, city, state, zip, country, and website URL. Ensured backward compatibility and wrote rollback procedures. Documented schema changes and updated database documentation.',
            'Implemented complex billing rate hierarchy system respecting project rates, customer rates, and default fallback rates. Added comprehensive unit tests covering edge cases and boundary conditions. Optimized query performance through proper indexing strategy.',
            'Refactored invoice calculation logic to handle multiple billing scenarios. Optimized database queries reducing N+1 problems. Performance improved by 60% on large datasets. Added caching layer for frequently accessed calculations.',
            'Built comprehensive error logging and monitoring system with detailed stack traces. Integrated with error tracking service for real-time alerting and analytics. Created dashboard for monitoring application health and performance metrics.',
            'Implemented JWT-based authentication system with refresh token rotation. Added multi-factor authentication support and rate limiting. Conducted security audit and documented authentication procedures.'
        ],
        'Database Management' => [
            'Optimized work_logs table with strategic indexing on frequently queried columns. Query performance improved by 75% for monthly report generation. Analyzed execution plans and documented optimization results. Performed load testing to verify improvements under high traffic.',
            'Created database backup procedures and tested disaster recovery scenarios. Documented restoration processes and created automated backup scheduling. Implemented point-in-time recovery capability and tested restoration accuracy.',
            'Performed data migration from legacy system ensuring data integrity and consistency. Wrote validation scripts to verify data accuracy across all tables. Created rollback procedures and documented migration process for future reference.'
        ],
        'Testing & QA' => [
            'Wrote comprehensive unit tests for invoice calculation logic covering all billing rate combinations, edge cases, and boundary conditions. Achieved 95% code coverage using PHPUnit and pytest. Documented test cases and maintained test documentation.',
            'Performed end-to-end testing of invoice workflow including generation, editing, and deletion. Documented test cases and created manual testing checklist for QA team. Identified and reported 15 bugs with detailed reproduction steps.',
            'Created automated Playwright tests for critical user paths in invoice management system. Implemented CI/CD pipeline integration for continuous testing. Set up parallel test execution reducing test suite runtime by 70% and monitored test stability.'
        ],
        'Documentation & Communication' => [
            'Updated API documentation with new endpoints, request/response schemas, and authentication requirements. Added code examples for common use cases and documented error responses. Created interactive API documentation using OpenAPI specification.',
            'Prepared technical specification document for invoice system redesign. Documented all business rules, validation requirements, and data relationships. Presented findings to stakeholders and incorporated feedback into final specification.',
            'Conducted knowledge transfer session with new team member. Explained architecture decisions, code conventions, and deployment procedures. Created detailed onboarding documentation and recorded video tutorials for reference.'
        ],
        'Bug Fixes & Maintenance' => [
            'Fixed critical bug in invoice total calculation that was causing incorrect amounts for projects with variable hourly rates. Added regression tests to prevent future issues. Analyzed root cause and documented findings in incident report.',
            'Resolved race condition in concurrent invoice creation that was causing duplicate invoice numbers. Implemented proper locking mechanism using database transactions. Added comprehensive tests for concurrent scenarios.',
            'Updated deprecated dependencies and resolved security vulnerabilities in composer.json. Ran security audit using multiple scanning tools and documented all changes. Created dependency update strategy for ongoing maintenance.',
            'Patched timezone handling issues causing discrepancies in work log time tracking. Standardized all times to UTC with proper conversion logic. Tested across multiple timezone configurations to ensure accuracy.'
        ]
    ];

    public function run(): void
    {
        $faker = \Faker\Factory::create();

        // Create work logs for the past 12 months with progressive increase
        $now = Carbon::now();
        $startDate = $now->copy()->subMonths(12)->startOfMonth();
        $currentDate = $startDate->copy();

        $projects = Project::with('users')->get();
        $monthCounter = 0;
        $taskCategories = array_keys($this->taskDescriptions);

        while ($currentDate <= $now) {
            // EXTREME GROWTH MODEL - Much more pronounced and aggressive
            // Month 1: 6/week, Month 2: 10/week, Month 3: 16/week, Month 4+: 25-40/week
            if ($monthCounter === 0) {
                $workLogsPerWeek = 6;
            } elseif ($monthCounter === 1) {
                $workLogsPerWeek = 10;
            } elseif ($monthCounter === 2) {
                $workLogsPerWeek = 16;
            } elseif ($monthCounter === 3) {
                $workLogsPerWeek = 22;
            } else {
                // Exponential growth: 25 at month 4, 35 at month 5, etc.
                $workLogsPerWeek = intval(20 * pow(1.25, $monthCounter - 3));
                $workLogsPerWeek = min($workLogsPerWeek, 60); // Cap at 60 per week
            }
            
            $weekStart = $currentDate->copy()->startOfWeek();
            $weekEnd = $currentDate->copy()->endOfWeek();
            
            for ($i = 0; $i < $workLogsPerWeek; $i++) {
                $date = $weekStart->copy()->addDays(rand(0, 6));
                
                // Hours also increase over time - more ambitious hours in later months
                if ($monthCounter < 3) {
                    $hours = rand(3, 10);
                } elseif ($monthCounter < 6) {
                    $hours = rand(5, 12);
                } else {
                    $hours = rand(6, 14); // Maximum engagement in later months
                }
                
                $project = $projects->random();
                
                // Get a random freelancer assigned to this project
                $freelancer = $project->users()->inRandomOrder()->first();
                
                if ($freelancer) {
                    // 90% chance of being billable
                    $isBillable = rand(1, 100) <= 90;
                    
                    // Vary start times for realism
                    $startHour = rand(8, 11);
                    $endHour = $startHour + $hours;
                    
                    // Get a detailed description from the task pool
                    $category = $taskCategories[array_rand($taskCategories)];
                    $descriptions = $this->taskDescriptions[$category];
                    $description = $descriptions[array_rand($descriptions)];
                    
                    WorkLog::create([
                        'project_id' => $project->id,
                        'user_id' => $freelancer->id,
                        'date' => $date,
                        'start_time' => sprintf('%02d:00', $startHour),
                        'end_time' => sprintf('%02d:00', $endHour),
                        'hours_worked' => $hours,
                        'description' => $description,
                        'billable' => $isBillable,
                        'hourly_rate' => $project->hourly_rate,
                        'user_hourly_rate' => $freelancer->pivot->hourly_rate
                    ]);
                }
            }
            
            $currentDate->addWeek();
            if ($currentDate->dayOfWeek === 0) { // When we cross into a new month
                $monthCounter++;
            }
        }
    }
}
