<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * MCP tokens are made here on the server; there is no token page in the UI.
 * The token acts with the user's full rights and skips 2FA, so keep it short-lived.
 */
class McpTokenCommand extends Command
{
    protected $signature = 'mcp:token
                            {user : User id or email}
                            {--name=Claude : Token name}
                            {--days=90 : Expiry in days, 0 for never}';

    protected $description = 'Create an MCP API token for a user and print it once';

    public function handle(): int
    {
        $key = $this->argument('user');
        $name = $this->option('name');
        $user = User::where(ctype_digit($key) ? 'id' : 'email', $key)->first();

        if (! $user || ! is_string($name)) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $days = (int) $this->option('days');
        $token = $user->createToken($name, ['mcp'], $days === 0 ? null : now()->addDays($days));
        $url = url('/mcp');

        $this->line("Token for {$user->email}, expires ".($token->accessToken->expires_at?->format('Y-m-d') ?? 'never').':');
        $this->line($token->plainTextToken);
        $this->newLine();
        $this->line("claude mcp add --transport http --scope user clientledger {$url} --header \"Authorization: Bearer {$token->plainTextToken}\"");
        $this->warn('The token is shown only now.');

        return self::SUCCESS;
    }
}
