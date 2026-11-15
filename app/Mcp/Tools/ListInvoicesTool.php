<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\InvoiceController;
use App\Models\Invoice;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('list_invoices')]
#[Description('Admins only. Invoices, newest first, without line details. Amounts net in the invoice currency.')]
class ListInvoicesTool extends ControllerTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->enum(['draft', 'sent', 'paid', 'overdue', 'cancelled']),
            'customer_id' => $schema->integer(),
        ];
    }

    protected function perform(Request $request): Response
    {
        $this->adminOnly();

        $invoices = $this->expectList(Invoice::class, $this->run(fn () => app(InvoiceController::class)->index()));
        $status = $request->filled('status') ? $request->string('status')->toString() : null;
        $customerId = $request->filled('customer_id') ? $request->integer('customer_id') : null;

        return Response::json(collect($invoices)
            ->filter(fn (Invoice $i) => ($status === null || $i->status === $status)
                && ($customerId === null || $i->customer_id === $customerId))
            ->map(fn (Invoice $i) => $this->invoiceRow($i))
            ->values());
    }
}
