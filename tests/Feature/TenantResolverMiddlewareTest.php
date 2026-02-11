<?php

namespace Tests\Feature;

use App\Models\Space;
use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class TenantResolverMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_sets_space_context_from_header(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        $space = Space::create([
            'handle' => 'ctx',
            'name' => 'CTX',
            'settings' => [],
            'storage_prefix' => 'spaces/ctx',
            'is_active' => true,
        ]);

        $res = $this->withHeader('X-Space-Id', (string) $space->id)
            ->getJson('/api/v1/spaces');

        $res->assertOk();

        $this->assertNotNull(app('currentSpaceId'));
        $this->assertEquals($space->id, app('currentSpaceId'));
    }
}
