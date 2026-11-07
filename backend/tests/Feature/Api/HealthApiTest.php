<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class HealthApiTest extends TestCase
{
    public function test_health_memeriksa_database_dan_cache(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJson([
                'status' => 'healthy',
                'checks' => [
                    'database' => 'ok',
                    'cache' => 'ok',
                ],
                'failed_components' => [],
            ]);
    }
}
