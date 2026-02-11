<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class SpacesTest extends TestCase
{
    use RefreshDatabase;

    public function test_space_list_requires_permission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create(); // no role
        Sanctum::actingAs($user);

        $res = $this->getJson('/api/v1/spaces');

        $res->assertStatus(403)
            ->assertJson(['success' => false, 'code' => 'FORBIDDEN']);
    }

    public function test_space_crud(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Admin'); // has manage_spaces
        Sanctum::actingAs($user);

        $create = $this->postJson('/api/v1/spaces', [
            'handle' => 'main-space',
            'name' => 'Main Space',
            'settings' => ['theme' => 'dark'],
        ]);

        $create->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.handle', 'main-space');

        $spaceId = $create->json('data.id');

        $list = $this->getJson('/api/v1/spaces');
        $list->assertOk()->assertJsonPath('success', true);

        $del = $this->deleteJson('/api/v1/spaces/' . $spaceId);
        $del->assertOk()->assertJsonPath('success', true);
    }
}
