<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\WorkLogController;
use App\Models\WorkLog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('list_worklogs')]
#[Description('Work logs, newest first. Freelancers see their own, admins everyone\'s (filter with user_id). Dates are YYYY-MM-DD, both ends inclusive. Returns up to 200 rows plus the total and summed hours.')]
class ListWorklogsTool extends ControllerTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()->description('From this date (YYYY-MM-DD).'),
            'end_date' => $schema->string()->description('Up to this date (YYYY-MM-DD).'),
            'project_id' => $schema->integer(),
            'user_id' => $schema->integer()->description('Admins: whose logs.'),
            'billable' => $schema->boolean(),
            'limit' => $schema->integer()->description('Max rows, 1-200, default 50.'),
        ];
    }

    protected function perform(Request $request): Response
    {
        $page = $this->run(fn ($r) => app(WorkLogController::class)->index($r), [
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'project_id' => $request->get('project_id'),
            'user_id' => $request->get('user_id'),
            'billable' => $request->get('billable'),
            'per_page' => $request->get('limit', 50),
        ]);
        $page = is_array($page) ? $page : [];
        $logs = $this->expectList(WorkLog::class, $page['data'] ?? []);

        return Response::json([
            'total' => $page['total'] ?? count($logs),
            'shown' => count($logs),
            'hours_shown' => $this->hours($logs),
            'logs' => array_map(fn (WorkLog $log) => $this->logRow($log), $logs),
        ]);
    }
}
