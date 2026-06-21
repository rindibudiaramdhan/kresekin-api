<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class RequestIdMiddlewareTest extends TestCase
{
    public function test_response_always_has_request_id_header(): void
    {
        $response = $this->getJson('/api/vershealthcheck');

        $response->assertHeader('X-Request-Id');
        $this->assertMatchesRegularExpression($this->uuidPattern(), $response->headers->get('X-Request-Id'));
    }

    public function test_valid_request_id_header_is_preserved(): void
    {
        $requestId = '123e4567-e89b-42d3-a456-426614174000';

        $response = $this->withHeader('X-Request-Id', $requestId)
            ->getJson('/api/vershealthcheck');

        $response->assertHeader('X-Request-Id', $requestId);
    }

    public function test_invalid_request_id_header_is_replaced(): void
    {
        $response = $this->withHeader('X-Request-Id', 'not-a-valid-request-id')
            ->getJson('/api/vershealthcheck');

        $requestId = $response->headers->get('X-Request-Id');

        $this->assertNotSame('not-a-valid-request-id', $requestId);
        $this->assertMatchesRegularExpression($this->uuidPattern(), $requestId);
    }

    public function test_request_id_is_available_in_request_attributes(): void
    {
        Route::get('/request-id-attribute-test', function (Request $request) {
            return response()->json([
                'request_id' => $request->attributes->get('request_id'),
            ]);
        });

        $requestId = '123e4567-e89b-42d3-a456-426614174000';

        $response = $this->withHeader('X-Request-Id', $requestId)
            ->getJson('/request-id-attribute-test');

        $response
            ->assertOk()
            ->assertJson(['request_id' => $requestId]);
    }

    public function test_safe_request_context_is_added_to_logs(): void
    {
        Log::spy();

        $requestId = '123e4567-e89b-42d3-a456-426614174000';

        $this->withHeader('X-Request-Id', $requestId)
            ->getJson('/api/vershealthcheck?token=secret');

        Log::shouldHaveReceived('withContext')
            ->once()
            ->with(Mockery::on(fn (array $context): bool => $context === [
                'request_id' => $requestId,
                'method' => 'GET',
                'path' => 'api/vershealthcheck',
                'environment' => 'testing',
            ]));
    }

    private function uuidPattern(): string
    {
        return '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
    }
}
