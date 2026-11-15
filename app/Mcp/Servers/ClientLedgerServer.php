<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddExpenseTool;
use App\Mcp\Tools\BookWorklogTool;
use App\Mcp\Tools\DeleteWorklogTool;
use App\Mcp\Tools\DraftInvoiceTool;
use App\Mcp\Tools\ListCustomersTool;
use App\Mcp\Tools\ListInvoicesTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\ListWorklogsTool;
use App\Mcp\Tools\RevenueTool;
use App\Mcp\Tools\SetInvoiceStatusTool;
use App\Mcp\Tools\StartTimerTool;
use App\Mcp\Tools\StatusTool;
use App\Mcp\Tools\StopTimerTool;
use App\Mcp\Tools\UnbilledWorklogsTool;
use App\Mcp\Tools\UpdateWorklogTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('ClientLedger')]
#[Version('1.0.0')]
#[Instructions('ClientLedger: time tracking and invoicing. Every tool acts as the token\'s user with that user\'s rights. Booking: list_projects for the project id, then book_worklog; a clash error names the existing entry, never work around it by shifting times without asking. Before booking several entries, show the list to the user and wait for an OK. Invoicing (admins): unbilled_worklogs to check, draft_invoice to create a draft, set_invoice_status to mark paid. Invoices are never sent from here. Dates YYYY-MM-DD, times HH:MM 24h.')]
class ClientLedgerServer extends Server
{
    protected array $tools = [
        StatusTool::class,
        ListCustomersTool::class,
        ListProjectsTool::class,
        ListWorklogsTool::class,
        BookWorklogTool::class,
        StartTimerTool::class,
        StopTimerTool::class,
        UpdateWorklogTool::class,
        DeleteWorklogTool::class,
        AddExpenseTool::class,
        UnbilledWorklogsTool::class,
        ListInvoicesTool::class,
        DraftInvoiceTool::class,
        SetInvoiceStatusTool::class,
        RevenueTool::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
