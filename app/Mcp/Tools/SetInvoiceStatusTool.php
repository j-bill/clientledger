<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\InvoiceController;
use App\Mcp\ToolFailure;
use App\Models\Invoice;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('set_invoice_status')]
#[Description('Admins only. Set an invoice status, e.g. paid once the money arrived. Nothing is emailed. Paid status moves its work logs from due to paid in revenue.')]
class SetInvoiceStatusTool extends ControllerTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required(),
            'status' => $schema->string()->enum(['draft', 'sent', 'paid', 'overdue', 'cancelled'])->required(),
        ];
    }

    protected function perform(Request $request): Response
    {
        $this->adminOnly();

        $invoice = Invoice::find($request->integer('id')) ?? throw new ToolFailure('Invoice not found.');
        $from = $invoice->status;

        $updated = $this->expect(Invoice::class, $this->run(
            fn ($r) => app(InvoiceController::class)->update($r, $invoice),
            ['status' => $request->get('status')],
        ));

        return Response::json([...$this->invoiceRow($updated), 'previous_status' => $from]);
    }
}
