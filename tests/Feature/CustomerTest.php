<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
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

    public function test_admin_can_view_specific_customer(): void
    {
        $customer = Customer::factory()->create(['name' => 'Test Customer']);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJson(['id' => $customer->id, 'name' => 'Test Customer']);
    }

    public function test_admin_can_update_customer(): void
    {
        $customer = Customer::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/customers/{$customer->id}", [
                'name' => 'New Name',
                'contact_email' => 'new@email.com',
            ]);

        $response->assertStatus(200);
    }
}
