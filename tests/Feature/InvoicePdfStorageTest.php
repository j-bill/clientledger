<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoicePdfStorageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ]);

        // Create a customer
        $this->customer = Customer::factory()->create();

        // Fake the storage
        Storage::fake('local');
    }

    public function test_pdf_is_saved_when_creating_invoice(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/invoices', [
                'customer_id' => $this->customer->id,
                'invoice_number' => 'INV-001',
                'issue_date' => '2025-01-01',
                'due_date' => '2025-01-31',
                'total_amount' => 1000.00,
                'status' => 'draft',
            ]);

        $response->assertStatus(201);

        $invoice = Invoice::first();
        $this->assertNotNull($invoice->pdf_path);

        // Verify PDF file exists in storage
        Storage::assertExists($invoice->pdf_path);
    }

    public function test_existing_invoice_pdf_can_be_uploaded(): void
    {
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-002',
        ]);

        $file = UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)
            ->postJson("/api/invoices/{$invoice->id}/upload-pdf", [
                'pdf' => $file,
            ]);

        $response->assertStatus(200);

        $invoice->refresh();
        $this->assertNotNull($invoice->pdf_path);
        Storage::assertExists($invoice->pdf_path);
    }

    public function test_saved_pdf_is_served_when_downloading(): void
    {
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-003',
            'pdf_path' => 'invoices/test-invoice.pdf',
        ]);

        // Create a fake PDF file
        Storage::put($invoice->pdf_path, 'fake pdf content');

        $response = $this->actingAs($this->admin)
            ->get("/api/invoices/{$invoice->id}/pdf-download");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_saved_pdf_is_served_when_viewing(): void
    {
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-004',
            'pdf_path' => 'invoices/test-invoice.pdf',
        ]);

        // Create a fake PDF file
        Storage::put($invoice->pdf_path, 'fake pdf content');

        $response = $this->actingAs($this->admin)
            ->get("/api/invoices/{$invoice->id}/pdf");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_pdf_is_deleted_when_invoice_is_deleted(): void
    {
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-005',
            'pdf_path' => 'invoices/test-invoice.pdf',
        ]);

        // Create a fake PDF file
        Storage::put($invoice->pdf_path, 'fake pdf content');
        Storage::assertExists($invoice->pdf_path);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(204);

        // Verify PDF is deleted
        Storage::assertMissing($invoice->pdf_path);
    }

    public function test_old_pdf_is_replaced_when_uploading_new_one(): void
    {
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-006',
            'pdf_path' => 'invoices/old-invoice.pdf',
        ]);

        // Create the old PDF file
        Storage::put($invoice->pdf_path, 'old pdf content');

        $newFile = UploadedFile::fake()->create('new-invoice.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)
            ->postJson("/api/invoices/{$invoice->id}/upload-pdf", [
                'pdf' => $newFile,
            ]);

        $response->assertStatus(200);

        $invoice->refresh();

        // Old PDF should be deleted
        Storage::assertMissing('invoices/old-invoice.pdf');

        // New PDF should exist
        Storage::assertExists($invoice->pdf_path);
    }
}
