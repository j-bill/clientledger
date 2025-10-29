<?php

namespace Tests\Feature;

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
            \App\Http\Middleware\Require2FASetup::class,
            \App\Http\Middleware\Verify2FA::class,
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
}
