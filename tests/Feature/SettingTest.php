<?php

namespace Tests\Feature;

use App\Http\Middleware\Require2FASetup;
use App\Http\Middleware\Verify2FA;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            Require2FASetup::class,
            Verify2FA::class,
        ]);

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_list_settings(): void
    {
        Setting::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/settings');

        $response->assertStatus(200)
            ->assertJsonCount(5);
    }

    public function test_admin_can_create_setting(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/settings', [
                'key' => 'company_name',
                'value' => 'My Company',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'key', 'value']);

        $this->assertDatabaseHas('settings', ['key' => 'company_name', 'value' => 'My Company']);
    }

    public function test_admin_can_view_setting(): void
    {
        $setting = Setting::factory()->create(['key' => 'currency', 'value' => 'USD']);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/settings/{$setting->id}");

        $response->assertStatus(200)
            ->assertJson(['id' => $setting->id, 'key' => 'currency']);
    }

    public function test_admin_can_update_setting(): void
    {
        $setting = Setting::factory()->create(['key' => 'currency', 'value' => 'USD']);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/settings/{$setting->id}", [
                'value' => 'EUR',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('settings', ['id' => $setting->id, 'value' => 'EUR']);
    }

    public function test_admin_can_batch_get_settings(): void
    {
        $setting1 = Setting::factory()->create(['key' => 'currency']);
        $setting2 = Setting::factory()->create(['key' => 'timezone']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/settings/batch?keys=currency,timezone');

        $response->assertStatus(200)
            ->assertJsonStructure(['currency', 'timezone']);
    }

    public function test_admin_can_batch_save_settings(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/settings/batch', [
                'currency' => 'USD',
                'timezone' => 'UTC',
                'language' => 'en',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('settings', ['key' => 'currency', 'value' => 'USD']);
        $this->assertDatabaseHas('settings', ['key' => 'timezone', 'value' => 'UTC']);
        $this->assertDatabaseHas('settings', ['key' => 'language', 'value' => 'en']);
    }

    public function test_public_can_access_public_settings(): void
    {
        Setting::factory()->create(['key' => 'company_logo', 'value' => '/path/to/logo.png']);

        $response = $this->getJson('/api/settings/public');

        $response->assertStatus(200);
    }

    public function test_openai_api_key_is_encrypted_at_rest(): void
    {
        Setting::create(['key' => 'openai_api_key', 'value' => 'sk-super-secret-key']);

        $this->assertDatabaseMissing('settings', ['key' => 'openai_api_key', 'value' => 'sk-super-secret-key']);

        $stored = \DB::table('settings')->where('key', 'openai_api_key')->value('value');
        $this->assertNotEquals('sk-super-secret-key', $stored);
        $this->assertEquals('sk-super-secret-key', Setting::where('key', 'openai_api_key')->firstOrFail()->value);
    }

    public function test_openai_api_key_is_masked_in_batch_response(): void
    {
        Setting::create(['key' => 'openai_api_key', 'value' => 'sk-super-secret-key']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/settings/batch');

        $response->assertStatus(200);
        $masked = $response->json('openai_api_key');
        $this->assertIsString($masked);
        $this->assertNotEquals('sk-super-secret-key', $masked);
        $this->assertStringEndsWith('-key', $masked);
        $this->assertStringContainsString('••••••••', $masked);
    }

    public function test_openai_api_key_is_masked_in_index_and_show(): void
    {
        $setting = Setting::create(['key' => 'openai_api_key', 'value' => 'sk-super-secret-key']);

        $index = $this->actingAs($this->admin)->getJson('/api/settings');
        $index->assertStatus(200);
        $indexContent = $index->getContent();
        $this->assertNotFalse($indexContent);
        $this->assertStringNotContainsString('sk-super-secret-key', $indexContent);

        $show = $this->actingAs($this->admin)->getJson("/api/settings/{$setting->id}");
        $show->assertStatus(200);
        $showContent = $show->getContent();
        $this->assertNotFalse($showContent);
        $this->assertStringNotContainsString('sk-super-secret-key', $showContent);
    }

    public function test_resaving_masked_value_does_not_overwrite_stored_api_key(): void
    {
        Setting::create(['key' => 'openai_api_key', 'value' => 'sk-super-secret-key']);

        $masked = $this->actingAs($this->admin)
            ->getJson('/api/settings/batch')
            ->json('openai_api_key');

        $this->actingAs($this->admin)
            ->postJson('/api/settings/batch', ['openai_api_key' => $masked])
            ->assertStatus(200);

        $this->assertEquals('sk-super-secret-key', Setting::where('key', 'openai_api_key')->firstOrFail()->value);
    }

    public function test_batch_save_can_replace_openai_api_key_with_a_new_value(): void
    {
        Setting::create(['key' => 'openai_api_key', 'value' => 'sk-old-key']);

        $this->actingAs($this->admin)
            ->postJson('/api/settings/batch', ['openai_api_key' => 'sk-new-key'])
            ->assertStatus(200);

        $this->assertEquals('sk-new-key', Setting::where('key', 'openai_api_key')->firstOrFail()->value);
    }

    public function test_legacy_plaintext_openai_api_key_is_upgraded_on_read(): void
    {
        // Simulate a row written before encryption existed: raw insert, bypassing the mutator.
        \DB::table('settings')->insert(['key' => 'openai_api_key', 'value' => 'sk-legacy-plaintext']);

        $value = Setting::where('key', 'openai_api_key')->firstOrFail()->value;
        $this->assertEquals('sk-legacy-plaintext', $value);

        $stored = \DB::table('settings')->where('key', 'openai_api_key')->value('value');
        $this->assertNotEquals('sk-legacy-plaintext', $stored);
    }
}
