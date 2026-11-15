<?php

namespace App\Mcp;

use RuntimeException;

/** A tool call that went nowhere; ControllerTool turns it into an MCP error result. */
class ToolFailure extends RuntimeException {}
