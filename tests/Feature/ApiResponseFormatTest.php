<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ApiResponseFormatTest extends TestCase
{
    public function test_api_success_response_format(): void
    {
        $res = $this->getJson('/api/v1/dummy/ping');

        $res->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Pong',
            ]);
    }
}
