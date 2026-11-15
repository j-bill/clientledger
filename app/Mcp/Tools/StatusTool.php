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

#[Name('status')]
#[Description('Who the token belongs to, the running timer (if any) and own hours booked today and this week (Monday to today).')]
class StatusTool extends ControllerTool
{
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    protected function perform(Request $request): Response
    {
        $user = $this->user();
        $controller = app(WorkLogController::class);

        $active = $this->run(fn ($r) => $controller->getActiveWorkLog($r));

        $weekStart = Carbon::today()->startOfWeek()->toDateString();
        $week = $this->run(fn ($r) => $controller->index($r), [
            'user_id' => $user->id,
            'start_date' => $weekStart,
            'per_page' => 200,
        ]);
        $logs = $this->expectList(WorkLog::class, is_array($week) ? $week['data'] ?? [] : []);
        $today = Carbon::today()->toDateString();

        return Response::json([
            'user' => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role],
            'running' => $active instanceof WorkLog ? $this->logRow($active) : null,
            'hours_today' => $this->hours(array_values(array_filter($logs, fn (WorkLog $log) => substr((string) $log->date, 0, 10) === $today))),
            'hours_this_week' => $this->hours($logs),
            'week_start' => $weekStart,
        ]);
    }
}
