<?php

namespace Tests\Feature;

use App\Mcp\Servers\ClientLedgerServer;
use App\Mcp\Tools\BookWorklogTool;
use App\Mcp\Tools\DeleteWorklogTool;
use App\Mcp\Tools\DraftInvoiceTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\SetInvoiceStatusTool;
use App\Mcp\Tools\StartTimerTool;
use App\Mcp\Tools\StatusTool;
use App\Mcp\Tools\StopTimerTool;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class McpServerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $freelancer;

    private Customer $customer;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->freelancer = User::factory()->create(['role' => 'freelancer']);
        $this->customer = Customer::factory()->create(['name' => 'Acme']);
        $this->project = Project::factory()->create(['customer_id' => $this->customer->id, 'name' => 'Website', 'hourly_rate' => 100]);
        $this->project->users()->attach($this->freelancer->id, ['hourly_rate' => 60]);
        $this->project->users()->attach($this->admin->id, ['hourly_rate' => 80]);
    }

    /** Tool calls run as a user holding a real mcp token. */
    private function as(User $user): User
    {
        return $user->withAccessToken($user->createToken('test', ['mcp'])->accessToken);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return TestResponse<Response>
     */
    private function rpc(string $token, string $method, array $params = []): TestResponse
    {
        return $this->withToken($token)->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => (object) $params]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function book(User $user, string $start, string $end, array $extra = []): WorkLog
    {
        return WorkLog::create([
            'project_id' => $this->project->id,
            'user_id' => $user->id,
            'date' => '2026-11-10',
            'start_time' => $start,
            'end_time' => $end,
            'hours_worked' => 2,
            'billable' => true,
            'hourly_rate' => 100,
            'user_hourly_rate' => 60,
            ...$extra,
        ]);
    }

    public function test_endpoint_requires_token_with_mcp_ability(): void
    {
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'])->assertUnauthorized();

        $wrong = $this->freelancer->createToken('x', ['other'])->plainTextToken;
        $this->rpc($wrong, 'ping')->assertForbidden();
    }

    public function test_real_token_books_through_http_as_its_user(): void
    {
        $token = $this->freelancer->createToken('x', ['mcp'])->plainTextToken;

        $tools = $this->rpc($token, 'tools/list')->assertOk()->json('result.tools');
        $this->assertIsArray($tools);
        $this->assertCount(15, $tools);

        $this->rpc($token, 'tools/call', ['name' => 'book_worklog', 'arguments' => [
            'project_id' => $this->project->id, 'date' => '2026-11-10', 'start_time' => '09:00', 'end_time' => '11:30',
        ]])->assertOk()->assertJsonPath('result.isError', false);

        $log = WorkLog::sole();
        $this->assertSame($this->freelancer->id, $log->user_id);
        $this->assertEqualsWithDelta(2.5, $log->hours_worked, 0.001);
        $this->assertEquals(100, $log->hourly_rate);
        $this->assertEquals(60, $log->user_hourly_rate);
    }

    public function test_booking_reports_clash_and_writes_nothing(): void
    {
        $this->book($this->freelancer, '09:00', '11:00');

        ClientLedgerServer::actingAs($this->as($this->freelancer))->tool(BookWorklogTool::class, [
            'project_id' => $this->project->id, 'date' => '2026-11-10', 'start_time' => '10:00', 'end_time' => '12:00',
        ])->assertHasErrors()->assertSee('clashes');

        ClientLedgerServer::actingAs($this->as($this->freelancer))->tool(BookWorklogTool::class, [
            'project_id' => $this->project->id, 'date' => '2026-11-10', 'start_time' => '11:00', 'end_time' => '12:00',
        ])->assertHasNoErrors();

        $this->assertSame(2, WorkLog::count());
    }

    public function test_freelancer_cannot_book_unassigned_project_or_for_others(): void
    {
        $other = Project::factory()->create(['customer_id' => $this->customer->id, 'name' => 'Secret']);

        ClientLedgerServer::actingAs($this->as($this->freelancer))->tool(BookWorklogTool::class, [
            'project_id' => $other->id, 'date' => '2026-11-10', 'start_time' => '09:00', 'end_time' => '10:00',
        ])->assertHasErrors()->assertSee('not assigned');

        ClientLedgerServer::actingAs($this->as($this->freelancer))->tool(BookWorklogTool::class, [
            'project_id' => $this->project->id, 'date' => '2026-11-10', 'start_time' => '09:00', 'end_time' => '10:00',
            'user_id' => $this->admin->id,
        ])->assertHasErrors()->assertSee('Only admins');

        ClientLedgerServer::actingAs($this->as($this->freelancer))->tool(ListProjectsTool::class)
            ->assertSee('Website')->assertDontSee('Secret');

        $this->assertSame(0, WorkLog::count());
    }

    public function test_timer_start_status_stop(): void
    {
        $server = ClientLedgerServer::actingAs($this->as($this->freelancer));

        $server->tool(StartTimerTool::class, ['project_id' => $this->project->id, 'start_time' => '08:00'])->assertHasNoErrors();
        $server->tool(StartTimerTool::class, ['project_id' => $this->project->id, 'start_time' => '08:30'])
            ->assertHasErrors()->assertSee('already have an active work log');
        $server->tool(StatusTool::class)->assertSee('"running":{"id"');
        $server->tool(StopTimerTool::class, ['end_time' => '23:59', 'description' => 'done'])->assertHasNoErrors();
        $server->tool(StopTimerTool::class)->assertHasErrors()->assertSee('No running work log');

        $this->assertSame('done', WorkLog::sole()->description);
    }

    public function test_invoice_tools_are_admin_only(): void
    {
        ClientLedgerServer::actingAs($this->as($this->freelancer))->tool(DraftInvoiceTool::class, [
            'customer_id' => $this->customer->id, 'start_date' => '2026-11-01', 'end_date' => '2026-11-30',
        ])->assertHasErrors()->assertSee('Admins only');

        $this->assertSame(0, Invoice::count());
    }

    public function test_draft_invoice_bills_only_unbilled_logs_in_period_once(): void
    {
        $this->book($this->freelancer, '09:00', '11:00');
        $this->book($this->admin, '09:00', '10:00', ['hours_worked' => 1]);
        $this->book($this->freelancer, '12:00', '13:00', ['billable' => false, 'hours_worked' => 1]);
        $this->book($this->freelancer, '09:00', '10:00', ['date' => '2026-10-30', 'hours_worked' => 1]);

        $server = ClientLedgerServer::actingAs($this->as($this->admin));
        $server->tool(DraftInvoiceTool::class, [
            'customer_id' => $this->customer->id, 'start_date' => '2026-11-01', 'end_date' => '2026-11-30',
        ])->assertHasNoErrors()->assertSee('"status":"draft"');

        $invoice = Invoice::sole();
        $this->assertSame('draft', $invoice->status);
        $this->assertCount(2, $invoice->workLogs);
        $this->assertEqualsWithDelta(300.0, (float) $invoice->total_amount, 0.001);

        $server->tool(DraftInvoiceTool::class, [
            'customer_id' => $this->customer->id, 'start_date' => '2026-11-01', 'end_date' => '2026-11-30',
        ])->assertHasErrors()->assertSee('Nothing unbilled');

        $server->tool(SetInvoiceStatusTool::class, ['id' => $invoice->id, 'status' => 'paid'])->assertHasNoErrors();
        $this->assertSame('paid', $invoice->fresh()?->status);
    }

    public function test_delete_refuses_invoiced_and_foreign_logs(): void
    {
        $invoiced = $this->book($this->freelancer, '09:00', '11:00');
        $invoice = Invoice::create([
            'customer_id' => $this->customer->id, 'invoice_number' => 'T-1', 'issue_date' => '2026-11-11',
            'due_date' => '2026-11-25', 'total_amount' => 200, 'status' => 'draft',
        ]);
        $invoice->workLogs()->attach($invoiced->id);
        $foreign = $this->book($this->admin, '12:00', '13:00');

        $server = ClientLedgerServer::actingAs($this->as($this->freelancer));
        $server->tool(DeleteWorklogTool::class, ['id' => $invoiced->id])->assertHasErrors()->assertSee('on an invoice');
        $server->tool(DeleteWorklogTool::class, ['id' => $foreign->id])->assertHasErrors()->assertSee('Unauthorized');

        $this->assertSame(2, WorkLog::count());
    }
}
