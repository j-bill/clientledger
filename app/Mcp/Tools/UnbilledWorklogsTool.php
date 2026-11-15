<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\InvoiceController;
use App\Models\WorkLog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('unbilled_worklogs')]
#[Description('Admins only. Billable work logs of a customer that are on no invoice yet, with the billing amount per log and totals. This is what draft_invoice would bill for the same filters.')]
class UnbilledWorklogsTool extends ControllerTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_id' => $schema->integer()->required(),
            'project_id' => $schema->integer(),
            'start_date' => $schema->string()->description('YYYY-MM-DD.'),
            'end_date' => $schema->string()->description('YYYY-MM-DD.'),
        ];
    }

    protected function perform(Request $request): Response
    {
        $this->adminOnly();

        $logs = $this->expectList(WorkLog::class, $this->run(fn ($r) => app(InvoiceController::class)->unbilledWorkLogs($r), $request->all()));
        $rows = collect($logs)->map(fn (WorkLog $log) => [
            ...$this->logRow($log),
            'rate' => $log->billing_rate,
            'amount' => round($log->billing_amount, 2),
        ]);

        return Response::json([
            'count' => $rows->count(),
            'hours' => $this->hours($logs),
            'amount' => round(array_sum(array_map(fn (WorkLog $log) => $log->billing_amount, $logs)), 2),
            'logs' => $rows->values(),
        ]);
    }
}
