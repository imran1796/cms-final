<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_returns_statuses(): void
    {
        $res = $this->getJson('/api/v1/system/health');

        $res->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'db' => ['ok'],
                    'cache' => ['ok'],
                    'queue' => ['ok'],
                    'ok',
                ],
                'meta',
            ])
            ->assertJsonPath('data.ok', true);
    }
}
