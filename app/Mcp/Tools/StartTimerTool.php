<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\WorkLogController;
use App\Models\WorkLog;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('start_timer')]
#[Description('Start a running work log on a project today (no end time). Fails if one is already running; stop it first with stop_timer. A running log blocks the rest of its day for other bookings.')]
class StartTimerTool extends ControllerTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->required(),
            'start_time' => $schema->string()->description('HH:MM, default now.'),
            'description' => $schema->string(),
        ];
    }

    protected function perform(Request $request): Response
    {
        $log = $this->expect(WorkLog::class, $this->run(fn ($r) => app(WorkLogController::class)->store($r), [
            'project_id' => $request->get('project_id'),
            'date' => Carbon::today()->toDateString(),
            'start_time' => $request->get('start_time', Carbon::now()->format('H:i')),
            'description' => $request->get('description'),
            'billable' => true,
        ]));

        return Response::json(['started' => $this->logRow($log)]);
    }
}
