<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\WorkLogController;
use App\Mcp\ToolFailure;
use App\Models\WorkLog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('delete_worklog')]
#[Description('Delete a work log for good. Refused if the log is on an invoice. Freelancers can only delete their own logs.')]
class DeleteWorklogTool extends ControllerTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required(),
        ];
    }

    protected function perform(Request $request): Response
    {
        $workLog = WorkLog::find($request->integer('id')) ?? throw new ToolFailure('Work log not found.');

        // Deleting an invoiced log would silently change what the invoice says it covers.
        if ($workLog->invoices()->exists()) {
            throw new ToolFailure('Work log '.$workLog->id.' is on an invoice. Remove it there first.');
        }

        $row = $this->logRow($workLog->load(['project', 'user']));
        $this->run(fn ($r) => app(WorkLogController::class)->destroy($r, $workLog));

        return Response::json(['deleted' => $row]);
    }
}
