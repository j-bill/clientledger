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

        // This-year revenue: both invoices (no work logs behind them, so their
        // issue month) plus this month's uninvoiced work: 500 + 300 + 400
        $this->assertEquals(1200, $response->json('kpis.revenue.yearly.actual'));
        // This month: 4h * 100 of work + the 300 invoice without work logs
        $this->assertEquals(700, $response->json('kpis.revenue.monthly.actual'));
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

    public function test_admin_revenue_follows_the_month_of_work_not_the_invoice_date(): void
    {
        Carbon::setTestNow('2026-10-07 12:00:00');

        $customer = Customer::factory()->create();
        $project = Project::factory()->create(['customer_id' => $customer->id]);
        $log = fn (string $date, float $hours) => WorkLog::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->freelancer->id,
            'billable' => true,
            'date' => $date,
            'hours_worked' => $hours,
            'hourly_rate' => 100,
        ]);

        // August work, invoiced and paid in September
        $august = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'paid',
            'issue_date' => '2026-09-03',
            'total_amount' => 1000,
        ]);
        $august->workLogs()->attach($log('2026-08-20', 10)->id);

        // September work, invoiced in October (plus a 50 manual item), paid;
        // two more September hours not invoiced yet
        $september = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'paid',
            'issue_date' => '2026-10-02',
            'total_amount' => 850,
        ]);
        $september->workLogs()->attach($log('2026-09-15', 8)->id);
        $log('2026-09-30', 2);

        // October work, not invoiced
        $log('2026-10-05', 3);

        $response = $this->actingAs($this->user)->getJson('/api/dashboard');
        $response->assertStatus(200);

        // Letzter Monat = September's work, not the August invoice issued in September
        $this->assertEquals(800, $response->json('kpis.revenue.last_month.paid'));
        $this->assertEquals(200, $response->json('kpis.revenue.last_month.due'));
        // This month: October work + the manual item on the October invoice
        $this->assertEquals(350, $response->json('kpis.revenue.monthly.actual'));
        $this->assertEquals(2350, $response->json('kpis.revenue.yearly.actual'));

        // Sparkline: each month once, no invoice counted on top of its own work
        $heroTrend = $response->json('hero_trend_data');
        $this->assertIsArray($heroTrend);
        $hero = collect($heroTrend)->pluck('amount', 'date');
        $this->assertEquals(1000, $hero['2026-08-01']);
        $this->assertEquals(1000, $hero['2026-09-01']);
        $this->assertEquals(350, $hero['2026-10-01']);

        Carbon::setTestNow();
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
