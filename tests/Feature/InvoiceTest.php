<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $freelancer;
    private Customer $customer;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\Require2FASetup::class,
            \App\Http\Middleware\Verify2FA::class,
        ]);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->freelancer = User::factory()->create(['role' => 'freelancer']);
        $this->customer = Customer::factory()->create();
        $this->project = Project::factory()->create(['customer_id' => $this->customer->id]);

        Storage::fake('local');
    }

    public function test_admin_can_create_invoice(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/invoices', [
                'customer_id' => $this->customer->id,
                'invoice_number' => 'INV-001',
                'issue_date' => Carbon::now()->format('Y-m-d'),
                'due_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
                'total_amount' => 1000.00,
                'status' => 'draft',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'invoice_number', 'customer_id', 'total_amount']);

        $this->assertDatabaseHas('invoices', ['invoice_number' => 'INV-001']);
    }

    public function test_freelancer_cannot_create_invoice(): void
    {
        $response = $this->actingAs($this->freelancer)
            ->postJson('/api/invoices', [
                'customer_id' => $this->customer->id,
                'invoice_number' => 'INV-002',
                'issue_date' => Carbon::now()->format('Y-m-d'),
                'due_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
                'total_amount' => 1000.00,
                'status' => 'draft',
            ]);

        $response->assertStatus(403);
    }

    public function test_create_invoice_requires_customer_id(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/invoices', [
                'issue_date' => Carbon::now()->format('Y-m-d'),
                'due_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
                'total_amount' => 1000.00,
                'status' => 'draft',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_id']);
    }

    public function test_create_invoice_requires_issue_date(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/invoices', [
                'customer_id' => $this->customer->id,
                'due_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
                'total_amount' => 1000.00,
                'status' => 'draft',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['issue_date']);
    }

    public function test_create_invoice_requires_due_date(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/invoices', [
                'customer_id' => $this->customer->id,
                'issue_date' => Carbon::now()->format('Y-m-d'),
                'total_amount' => 1000.00,
                'status' => 'draft',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['due_date']);
    }

    public function test_admin_can_list_invoices(): void
    {
        Invoice::factory()->count(5)->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/invoices');

        $response->assertStatus(200)
            ->assertJsonCount(5);
    }

    public function test_freelancer_cannot_list_invoices(): void
    {
        Invoice::factory()->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->freelancer)
            ->getJson('/api/invoices');

        $response->assertStatus(403);
    }

    public function test_admin_can_view_invoice(): void
    {
        $invoice = Invoice::factory()->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(200)
            ->assertJson(['id' => $invoice->id]);
    }

    public function test_admin_can_update_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/invoices/{$invoice->id}", [
                'status' => 'sent',
                'total_amount' => 1500.00,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'sent']);
    }

    public function test_admin_can_delete_invoice(): void
    {
        $invoice = Invoice::factory()->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    public function test_admin_can_generate_invoice_from_worklogs(): void
    {
        WorkLog::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'billable' => true,
            'user_id' => $this->freelancer->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/invoices/generate', [
                'customer_id' => $this->customer->id,
                'work_log_ids' => [1, 2, 3],
                'status' => 'draft',
                'due_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'invoice_number', 'customer_id']);
    }

    public function test_get_unbilled_worklogs(): void
    {
        WorkLog::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'billable' => true,
            'user_id' => $this->freelancer->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/invoices/unbilled-worklogs?customer_id='.$this->customer->id);

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_get_customer_projects(): void
    {
        Project::factory()->count(3)->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/invoices/customer-projects?customer_id='.$this->customer->id);

        $response->assertStatus(200)
            ->assertJsonCount(4);  // 1 from setUp + 3 created here
    }

    public function test_admin_can_upload_invoice_pdf(): void
    {
        $invoice = Invoice::factory()->create(['customer_id' => $this->customer->id]);
        $file = UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)
            ->postJson("/api/invoices/{$invoice->id}/upload-pdf", [
                'pdf' => $file,
            ]);

        $response->assertStatus(200);
        $invoice->refresh();
        $this->assertNotNull($invoice->pdf_path);
    }

    public function test_admin_can_generate_invoice_pdf(): void
    {
        $invoice = Invoice::factory()->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/invoices/{$invoice->id}/generate-pdf");

        $response->assertStatus(200);
    }

    public function test_admin_can_view_invoice_pdf(): void
    {
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'pdf_path' => 'invoices/test.pdf',
        ]);

        Storage::put($invoice->pdf_path, 'fake pdf content');

        $response = $this->actingAs($this->admin)
            ->get("/api/invoices/{$invoice->id}/pdf");

        $response->assertStatus(200);
    }

    public function test_admin_can_download_invoice_pdf(): void
    {
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'pdf_path' => 'invoices/test.pdf',
        ]);

        Storage::put($invoice->pdf_path, 'fake pdf content');

        $response = $this->actingAs($this->admin)
            ->get("/api/invoices/{$invoice->id}/pdf-download");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_upload_pdf_requires_valid_file(): void
    {
        $invoice = Invoice::factory()->create(['customer_id' => $this->customer->id]);
        $file = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

        $response = $this->actingAs($this->admin)
            ->postJson("/api/invoices/{$invoice->id}/upload-pdf", [
                'pdf' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pdf']);
    }

    public function test_view_nonexistent_invoice_returns_404(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/invoices/999999');

        $response->assertStatus(404);
    }
}
