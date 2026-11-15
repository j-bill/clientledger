<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\WorkLogController;
use App\Mcp\ToolFailure;
use App\Models\WorkLog;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('stop_timer')]
#[Description('Stop the user\'s running work log. End time defaults to now; the description, if given, replaces the old one.')]
class StopTimerTool extends ControllerTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'end_time' => $schema->string()->description('HH:MM, default now.'),
            'description' => $schema->string(),
        ];
    }

    protected function perform(Request $request): Response
    {
        $active = WorkLog::where('user_id', $this->user()->id)
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->first() ?? throw new ToolFailure('No running work log.');

        $log = $this->expect(WorkLog::class, $this->run(fn ($r) => app(WorkLogController::class)->completeTracking($r, $active), [
            'end_time' => $request->get('end_time', Carbon::now()->format('H:i')),
            'description' => $request->get('description'),
        ]));

        return Response::json(['stopped' => $this->logRow($log)]);
    }
}
