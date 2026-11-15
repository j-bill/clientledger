<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\InvoiceController;
use App\Mcp\ToolFailure;
use App\Models\Invoice;
use App\Models\WorkLog;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('draft_invoice')]
#[Description('Admins only. Create a DRAFT invoice from all unbilled billable work logs of a customer in the given period (see unbilled_worklogs). Issue date is today. Always a draft: the user reviews and sends it in the app.')]
class DraftInvoiceTool extends ControllerTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_id' => $schema->integer()->required(),
            'start_date' => $schema->string()->description('YYYY-MM-DD, first day of work to bill.')->required(),
            'end_date' => $schema->string()->description('YYYY-MM-DD, last day of work to bill.')->required(),
            'project_id' => $schema->integer()->description('Only this project.'),
            'due_in_days' => $schema->integer()->description('Default 14.'),
        ];
    }

    protected function perform(Request $request): Response
    {
        $this->adminOnly();

        $controller = app(InvoiceController::class);

        $logs = $this->expectList(WorkLog::class, $this->run(fn ($r) => $controller->unbilledWorkLogs($r), [
            'customer_id' => $request->get('customer_id'),
            'project_id' => $request->get('project_id'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ]));
        if ($logs === []) {
            throw new ToolFailure('Nothing unbilled for this customer in that period.');
        }

        $invoice = $this->expect(Invoice::class, $this->run(fn ($r) => $controller->generateFromWorkLogs($r), [
            'customer_id' => $request->get('customer_id'),
            'work_log_ids' => array_map(fn (WorkLog $log) => $log->id, $logs),
            'due_date' => Carbon::today()->addDays($request->integer('due_in_days', 14))->toDateString(),
            'status' => 'draft',
        ]));

        return Response::json($this->invoiceRow($invoice));
    }
}
