<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;
use App\Services\AiDescriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Ai;
use Laravel\Ai\AnonymousAgent;
use Tests\TestCase;

class AiDescriptionTest extends TestCase
{
    use RefreshDatabase;

    private User $freelancer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\Require2FASetup::class,
            \App\Http\Middleware\Verify2FA::class,
        ]);

        $this->freelancer = User::factory()->create(['role' => 'freelancer']);
    }

    private function enableAi(): void
    {
        Setting::create(['key' => 'ai_worklog_enabled', 'value' => '1']);
        Setting::create(['key' => 'openai_api_key', 'value' => 'sk-test-key']);
    }

    public function test_freelancer_can_generate_description_when_enabled(): void
    {
        $this->enableAi();

        $customer = Customer::factory()->create(['name' => 'Acme Corp']);
        $project = Project::factory()->create([
            'customer_id' => $customer->id,
            'name' => 'Website Relaunch',
        ]);

        Ai::fakeAgent(AnonymousAgent::class, ['Polished description.']);

        $response = $this->actingAs($this->freelancer)
            ->postJson('/api/worklogs/generate-description', [
                'notes' => 'fixed login bug, deployed staging',
                'project_id' => $project->id,
            ]);

        $response->assertStatus(200)
            ->assertJson(['description' => 'Polished description.']);

        Ai::assertAgentWasPrompted(AnonymousAgent::class, function ($prompt) {
            return $prompt->contains('fixed login bug, deployed staging')
                && $prompt->contains('Website Relaunch')
                && $prompt->contains('Acme Corp');
        });
    }

    public function test_generation_fails_when_feature_disabled(): void
    {
        $response = $this->actingAs($this->freelancer)
            ->postJson('/api/worklogs/generate-description', [
                'notes' => 'some notes',
            ]);

        $response->assertStatus(403);
    }

    public function test_generation_fails_when_api_key_missing(): void
    {
        Setting::create(['key' => 'ai_worklog_enabled', 'value' => '1']);

        $response = $this->actingAs($this->freelancer)
            ->postJson('/api/worklogs/generate-description', [
                'notes' => 'some notes',
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'OpenAI API key is not configured']);
    }

    public function test_generation_requires_notes(): void
    {
        $this->enableAi();

        $response = $this->actingAs($this->freelancer)
            ->postJson('/api/worklogs/generate-description', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['notes']);
    }

    public function test_generation_requires_authentication(): void
    {
        $response = $this->postJson('/api/worklogs/generate-description', [
            'notes' => 'some notes',
        ]);

        $response->assertStatus(401);
    }

    public function test_custom_prompt_from_settings_is_used_as_instructions(): void
    {
        $this->enableAi();
        Setting::create(['key' => 'ai_worklog_prompt', 'value' => 'Write like a pirate.']);

        Ai::fakeAgent(AnonymousAgent::class, ['Arr, fixed the login.']);

        $this->actingAs($this->freelancer)
            ->postJson('/api/worklogs/generate-description', [
                'notes' => 'fixed login',
            ])
            ->assertStatus(200);

        Ai::assertAgentWasPrompted(AnonymousAgent::class, function ($prompt) {
            return $prompt->agent->instructions() === 'Write like a pirate.';
        });
    }

    public function test_default_prompt_is_used_when_setting_missing(): void
    {
        $this->enableAi();

        Ai::fakeAgent(AnonymousAgent::class, ['Result.']);

        $this->actingAs($this->freelancer)
            ->postJson('/api/worklogs/generate-description', [
                'notes' => 'fixed login',
            ])
            ->assertStatus(200);

        Ai::assertAgentWasPrompted(AnonymousAgent::class, function ($prompt) {
            return $prompt->agent->instructions() === AiDescriptionService::DEFAULT_PROMPT;
        });
    }

    public function test_public_settings_expose_flag_but_not_secrets(): void
    {
        $this->enableAi();

        $response = $this->getJson('/api/settings/public');

        $response->assertStatus(200)
            ->assertJson(['ai_worklog_enabled' => '1'])
            ->assertJsonMissing(['openai_api_key' => 'sk-test-key']);
    }
}
