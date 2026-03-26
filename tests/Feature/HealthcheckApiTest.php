<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthcheckApiTest extends TestCase
{
    public function test_healthcheck_api_returns_version_information(): void
    {
        $response = $this->getJson('/api/healthcheck');

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'message' => 'API is healthy',
                'version' => config('api.version'),
                'framework' => [
                    'name' => 'Laravel',
                    'version' => app()->version(),
                ],
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'version',
                'framework' => ['name', 'version'],
                'timestamp',
            ]);
    }
}
