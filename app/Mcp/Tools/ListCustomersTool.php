<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\CustomerController;
use App\Models\Customer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('list_customers')]
#[Description('Customers the user can see (admins: all, freelancers: those with an assigned project). Optional name filter.')]
class ListCustomersTool extends ControllerTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Case-insensitive part of the customer name.'),
        ];
    }

    protected function perform(Request $request): Response
    {
        $customers = $this->expectList(Customer::class, $this->run(fn ($r) => app(CustomerController::class)->index($r)));
        $search = mb_strtolower($request->string('search')->toString());

        return Response::json(collect($customers)
            ->filter(fn (Customer $c) => $search === '' || str_contains(mb_strtolower($c->name), $search))
            ->map(fn (Customer $c) => ['id' => $c->id, 'name' => $c->name, 'hourly_rate' => $c->hourly_rate])
            ->values());
    }
}
