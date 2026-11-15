<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\WorkLogController;
use App\Models\WorkLog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('book_worklog')]
#[Description('Book a finished block of work. Rates come from the project and the user\'s project rate, never from the caller. Fails with the clashing entry if the time overlaps another log of the same user (a log ending 12:00 does not clash with one starting 12:00). Admins may book for another user via user_id.')]
class BookWorklogTool extends ControllerTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->required(),
            'date' => $schema->string()->description('YYYY-MM-DD.')->required(),
            'start_time' => $schema->string()->description('HH:MM, 24h.')->required(),
            'end_time' => $schema->string()->description('HH:MM, 24h.')->required(),
            'description' => $schema->string()->description('What was done, max 1500 chars.'),
            'billable' => $schema->boolean()->description('Default true.'),
            'user_id' => $schema->integer()->description('Admins only: book for this user.'),
        ];
    }

    protected function perform(Request $request): Response
    {
        $log = $this->expect(WorkLog::class, $this->run(fn ($r) => app(WorkLogController::class)->store($r), [
            'project_id' => $request->get('project_id'),
            'date' => $request->get('date'),
            'start_time' => $request->get('start_time'),
            'end_time' => $request->get('end_time'),
            'description' => $request->get('description'),
            'billable' => $request->get('billable', true),
            'user_id' => $request->get('user_id'),
        ]));

        return Response::json(['booked' => $this->logRow($log)]);
    }
}
