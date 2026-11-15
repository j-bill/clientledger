<?php

namespace App\Mcp\Tools;

use App\Mcp\ToolFailure;
use App\Models\Invoice;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * MCP tools run the existing API controller actions as the token's user, so
 * validation, ownership (resolveOwner), the clash check and per-user scoping
 * live in one place. Route middleware does not run here: tools that wrap
 * admin-only routes call adminOnly() themselves. 2FA is skipped on purpose,
 * the token is minted on the server with `php artisan mcp:token`.
 */
abstract class ControllerTool extends Tool
{
    abstract protected function perform(Request $request): Response;

    public function handle(Request $request): Response
    {
        try {
            return $this->perform($request);
        } catch (ToolFailure $e) {
            return Response::error($e->getMessage());
        }
    }

    /**
     * Run a controller action and hand back what it put into its JSON response
     * (models, collections, arrays), or throw ToolFailure with its message.
     *
     * @param  callable(HttpRequest): mixed  $action
     * @param  array<string, mixed>  $input
     */
    protected function run(callable $action, array $input = []): mixed
    {
        // Controllers test filters with has(), so a null must not arrive as a key.
        $request = HttpRequest::create('/', 'POST', array_filter($input, fn ($v) => $v !== null));
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => Auth::user());

        try {
            $response = $action($request);
        } catch (ValidationException $e) {
            throw new ToolFailure(collect($e->errors())->flatten()->implode(' '));
        } catch (ModelNotFoundException) {
            throw new ToolFailure('Not found.');
        } catch (HttpExceptionInterface $e) {
            throw new ToolFailure($e->getMessage() !== '' ? $e->getMessage() : 'HTTP '.$e->getStatusCode());
        }

        if (! $response instanceof JsonResponse) {
            return $response;
        }

        if ($response->getStatusCode() >= 400) {
            $data = $response->getData(true);
            throw new ToolFailure(is_array($data) && is_string($data['message'] ?? null) ? $data['message'] : 'HTTP '.$response->getStatusCode());
        }

        return $response->original;
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T
     */
    protected function expect(string $class, mixed $value): object
    {
        if ($value instanceof $class) {
            return $value;
        }

        throw new ToolFailure('Unexpected response from '.class_basename($class).' lookup.');
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return list<T>
     */
    protected function expectList(string $class, mixed $value): array
    {
        if (! is_iterable($value)) {
            throw new ToolFailure('Unexpected list response.');
        }

        $items = [];
        foreach ($value as $item) {
            $items[] = $this->expect($class, $item);
        }

        return $items;
    }

    protected function user(): User
    {
        return $this->expect(User::class, Auth::user());
    }

    protected function adminOnly(): void
    {
        if (! $this->user()->isAdmin()) {
            throw new ToolFailure('Admins only.');
        }
    }

    /**
     * A work log trimmed to what a booking conversation needs.
     *
     * @return array<string, mixed>
     */
    protected function logRow(WorkLog $log): array
    {
        return [
            'id' => $log->id,
            'date' => substr((string) $log->date, 0, 10),
            'start' => $log->start_time?->format('H:i'),
            'end' => $log->end_time?->format('H:i'),
            'hours' => $log->hours_worked !== null ? round((float) $log->hours_worked, 2) : null,
            'project_id' => $log->project_id,
            'project' => $log->project?->name,
            'user' => $log->user?->name,
            'description' => $log->description,
            'billable' => (bool) $log->billable,
        ];
    }

    /**
     * @param  list<WorkLog>  $logs
     */
    protected function hours(array $logs): float
    {
        return round(array_sum(array_map(fn (WorkLog $log) => (float) $log->hours_worked, $logs)), 2);
    }

    /**
     * @return array<string, mixed>
     */
    protected function invoiceRow(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'number' => $invoice->invoice_number,
            'customer' => $invoice->customer?->name,
            'issue_date' => $invoice->issue_date->format('Y-m-d'),
            'due_date' => $invoice->due_date->format('Y-m-d'),
            'total' => round((float) $invoice->total_amount, 2),
            'status' => $invoice->status,
            'work_logs' => $invoice->workLogs()->count(),
            'has_pdf' => $invoice->pdf_path !== null,
        ];
    }
}
