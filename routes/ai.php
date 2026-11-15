<?php

use App\Mcp\Servers\ClientLedgerServer;
use Laravel\Mcp\Facades\Mcp;

// Remote MCP endpoint. Auth: personal access token (php artisan mcp:token) with the "mcp" ability.
Mcp::web('/mcp', ClientLedgerServer::class)
    ->middleware(['auth:sanctum', 'ability:mcp', 'throttle:120,1']);
