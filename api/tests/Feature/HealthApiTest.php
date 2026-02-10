<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthApiTest extends TestCase
{
    public function test_health_endpoint_returns_ok_payload(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('service', 'doctor-o-api')
            ->assertJsonStructure(['status', 'service', 'timestamp']);
    }
}
