<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\ExpenseController;
use App\Models\Expense;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('add_expense')]
#[Description('Record an expense. No receipt upload here; attach it in the app afterwards.')]
class AddExpenseTool extends ControllerTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'description' => $schema->string()->required(),
            'amount' => $schema->number()->description('Gross amount, >= 0.')->required(),
            'date' => $schema->string()->description('YYYY-MM-DD.')->required(),
            'category' => $schema->string()->description('Free text, max 50 chars, e.g. Software, Travel, Hardware.')->required(),
            'currency' => $schema->string()->description('ISO code, default EUR.'),
            'customer_id' => $schema->integer(),
            'project_id' => $schema->integer(),
            'is_tax_deductible' => $schema->boolean(),
        ];
    }

    protected function perform(Request $request): Response
    {
        $expense = $this->expect(Expense::class, $this->run(fn ($r) => app(ExpenseController::class)->store($r), [
            ...$request->all(),
            'currency' => $request->get('currency', 'EUR'),
        ]));

        return Response::json(['added' => $expense->toArray()]);
    }
}
