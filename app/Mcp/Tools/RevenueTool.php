<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\DashboardController;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('revenue')]
#[Description('Admins only. The dashboard revenue figures, counted in the month the work was done (not the invoice date): this month and this year as actual plus extrapolated, last month and last year split into paid and due. Plus all-time invoiced revenue per customer.')]
class RevenueTool extends ControllerTool
{
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    protected function perform(Request $request): Response
    {
        $this->adminOnly();

        $dashboard = $this->run(fn ($r) => app(DashboardController::class)->index($r));
        $dashboard = is_array($dashboard) ? $dashboard : [];
        $kpis = is_array($dashboard['kpis'] ?? null) ? $dashboard['kpis'] : [];

        return Response::json([
            'revenue' => $kpis['revenue'] ?? null,
            'by_customer' => $dashboard['revenue_by_customer'] ?? [],
        ]);
    }
}
