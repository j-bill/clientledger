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

#[Name('update_worklog')]
#[Description('Change a work log. Only the fields given change. Same clash and assignment checks as booking. Freelancers can only change their own logs.')]
class UpdateWorklogTool extends ControllerTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required(),
            'project_id' => $schema->integer(),
            'date' => $schema->string()->description('YYYY-MM-DD.'),
            'start_time' => $schema->string()->description('HH:MM.'),
            'end_time' => $schema->string()->description('HH:MM.'),
            'description' => $schema->string(),
            'billable' => $schema->boolean(),
            'user_id' => $schema->integer()->description('Admins only: move the log to this user.'),
        ];
    }

    protected function perform(Request $request): Response
    {
        $workLog = WorkLog::find($request->integer('id')) ?? throw new ToolFailure('Work log not found.');

        $log = $this->expect(WorkLog::class, $this->run(
            fn ($r) => app(WorkLogController::class)->update($r, $workLog),
            collect($request->all())->except('id')->all(),
        ));

        return Response::json(['updated' => $this->logRow($log)]);
    }
}
