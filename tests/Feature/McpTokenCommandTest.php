<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class McpTokenCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_expiring_mcp_token_for_user(): void
    {
        $user = User::factory()->create(['email' => 'julian@example.com']);

        $this->assertSame(0, Artisan::call('mcp:token', ['user' => 'julian@example.com', '--days' => 30]));

        $token = $user->tokens()->sole();
        $this->assertSame(['mcp'], $token->abilities);
        $this->assertTrue($token->expires_at?->isSameDay(now()->addDays(30)));
    }

    public function test_unknown_user_fails(): void
    {
        $this->assertSame(1, Artisan::call('mcp:token', ['user' => 'nobody@example.com']));
    }
}
