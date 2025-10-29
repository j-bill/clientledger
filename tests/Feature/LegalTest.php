<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_access_privacy_policy(): void
    {
        $response = $this->getJson('/api/legal/privacy');

        $response->assertStatus(200);
    }

    public function test_public_can_access_imprint(): void
    {
        $response = $this->getJson('/api/legal/imprint');

        $response->assertStatus(200);
    }
}
