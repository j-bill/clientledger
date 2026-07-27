<?php

namespace Tests\Feature;

use App\Http\Middleware\Require2FASetup;
use App\Http\Middleware\Verify2FA;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $freelancer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            Require2FASetup::class,
            Verify2FA::class,
        ]);

        $this->user = User::factory()->create(['role' => 'admin']);
        $this->freelancer = User::factory()->create(['role' => 'freelancer']);
    }

    public function test_freelancer_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->freelancer)
            ->getJson('/api/dashboard');

        // Should succeed with 200 status
        $response->assertStatus(200);

        // Should have the expected data structure
        $response->assertJsonStructure([
            'kpis' => [
                'hours',
                'projects',
            ],
        ]);
    }

    public function test_admin_dashboard_aggregates_invoices_and_worklogs(): void
    {
        $customer = Customer::factory()->create(['name' => 'Acme']);
        $project = Project::factory()->create(['customer_id' => $customer->id]);

        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'paid',
            'issue_date' => Carbon::now()->subMonths(2)->format('Y-m-d'),
            'total_amount' => 500,
        ]);
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'sent',
            'issue_date' => Carbon::now()->format('Y-m-d'),
            'total_amount' => 300,
        ]);
        WorkLog::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->freelancer->id,
            'billable' => true,
            'date' => Carbon::now()->format('Y-m-d'),
            'hours_worked' => 4,
            'hourly_rate' => 100,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'kpis' => ['revenue', 'hours', 'projects'],
                'revenue_by_customer',
                'yearly_revenue_trend',
                'hero_trend_data',
                'monthly_hours',
            ]);

        // This-year revenue: both invoices issued this year (paid + sent)
        $this->assertEquals(800, $response->json('kpis.revenue.yearly.actual'));
        // This-month actual revenue from billable work logs: 4h * 100
        $this->assertEquals(400, $response->json('kpis.revenue.monthly.actual'));
        $this->assertEquals(800, $response->json('revenue_by_customer.Acme'));
        // Paid-invoice trend has the paid invoice's month
        $this->assertNotEmpty($response->json('yearly_revenue_trend'));
        $this->assertEquals(500, $response->json('yearly_revenue_trend.0.amount'));
        // Hero trend month containing the sent invoice + uninvoiced work: 300 + 400
        $thisMonth = Carbon::now()->format('Y-m-01');
        $heroTrend = $response->json('hero_trend_data');
        $this->assertIsArray($heroTrend);
        $heroThisMonth = collect($heroTrend)->firstWhere('date', $thisMonth);
        $this->assertIsArray($heroThisMonth);
        $this->assertEquals(700, $heroThisMonth['amount']);
        // Monthly hours contains the logged day
        $monthlyHours = $response->json('monthly_hours');
        $this->assertIsArray($monthlyHours);
        $loggedDay = collect($monthlyHours)
            ->firstWhere('date', Carbon::now()->format('Y-m-d'));
        $this->assertIsArray($loggedDay);
        $this->assertEquals(4, $loggedDay['hours']);
        $this->assertEquals(4, $loggedDay['billable']);
    }

    public function test_freelancer_dashboard_reports_earnings(): void
    {
        $project = Project::factory()->create();
        WorkLog::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->freelancer->id,
            'billable' => true,
            'date' => Carbon::now()->format('Y-m-d'),
            'hours_worked' => 3,
            'user_hourly_rate' => 40,
        ]);
        // Another user's log must not leak into the freelancer's numbers
        WorkLog::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'billable' => true,
            'date' => Carbon::now()->format('Y-m-d'),
            'hours_worked' => 8,
            'user_hourly_rate' => 100,
        ]);

        $response = $this->actingAs($this->freelancer)
            ->getJson('/api/dashboard');

        $response->assertStatus(200);

        $this->assertEquals(120, $response->json('kpis.earnings.monthly.actual'));
        $this->assertEquals(120, $response->json('kpis.earnings.yearly.actual'));
        // Earnings trend for the current month: 3h * 40
        $thisMonth = Carbon::now()->format('Y-m');
        $earningsTrend = $response->json('yearly_earnings_trend');
        $this->assertIsArray($earningsTrend);
        $trendThisMonth = collect($earningsTrend)->firstWhere('date', $thisMonth);
        $this->assertIsArray($trendThisMonth);
        $this->assertEquals(120, $trendThisMonth['amount']);
    }

    public function test_admin_can_list_users(): void
    {
        // Simple test that doesn't trigger complex queries
        $response = $this->actingAs($this->user)
            ->getJson('/api/users');

        $response->assertStatus(200);
    }
}
