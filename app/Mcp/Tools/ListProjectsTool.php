<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\ProjectController;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('list_projects')]
#[Description('Projects the user can book on (freelancers: only assigned ones), with customer, customer-facing hourly rate and assigned users. Use the id for book_worklog.')]
class ListProjectsTool extends ControllerTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_id' => $schema->integer()->description('Only projects of this customer.'),
            'search' => $schema->string()->description('Case-insensitive part of the project or customer name.'),
        ];
    }

    protected function perform(Request $request): Response
    {
        $projects = $this->expectList(Project::class, $this->run(
            fn ($r) => app(ProjectController::class)->index($r),
            ['customers' => $request->filled('customer_id') ? [$request->integer('customer_id')] : null],
        ));
        $search = mb_strtolower($request->string('search')->toString());

        return Response::json(collect($projects)
            ->filter(fn (Project $p) => $search === ''
                || str_contains(mb_strtolower($p->name.' '.$p->customer?->name), $search))
            ->map(fn (Project $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'customer_id' => $p->customer_id,
                'customer' => $p->customer?->name,
                'hourly_rate' => $p->hourly_rate,
                'users' => $p->users->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])->all(),
            ])
            ->values());
    }
}
