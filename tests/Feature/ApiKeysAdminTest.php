<?php

namespace Tests\Feature;

use App\Models\Space;
use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ApiKeysAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_keys_crud_and_regenerate(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $space = Space::query()->create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        $headers = ['X-Space-Id' => (string) $space->id];

        $create = $this->postJson('/api/v1/admin/api-keys', [
            'name' => 'Public Key',
            'scopes' => [
                'collections' => ['posts', 'pages'],
                'permissions' => ['content.read'],
            ],
        ], $headers);

        $create->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.api_key.space_id', $space->id)
            ->assertJsonStructure([
                'data' => [
                    'api_key' => ['id', 'name', 'space_id', 'token_hash', 'scopes'],
                    'plain_token',
                ],
            ]);

        $apiKeyId = $create->json('data.api_key.id');
        $plain1 = $create->json('data.plain_token');

        $this->assertNotEmpty($plain1);

        $list = $this->getJson('/api/v1/admin/api-keys', $headers);
        $list->assertOk()->assertJsonPath('success', true);

        $update = $this->putJson('/api/v1/admin/api-keys/' . $apiKeyId, [
            'name' => 'Public Key Updated',
            'scopes' => [
                'collections' => ['posts'],
                'permissions' => ['content.read', 'content.list'],
            ],
        ], $headers);
        $update->assertOk()
            ->assertJsonPath('data.name', 'Public Key Updated');

        $regen = $this->postJson('/api/v1/admin/api-keys/' . $apiKeyId . '/regenerate', [], $headers);
        $regen->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['api_key' => ['id', 'token_hash'], 'plain_token'],
            ]);

        $plain2 = $regen->json('data.plain_token');
        $this->assertNotEmpty($plain2);
        $this->assertNotEquals($plain1, $plain2);

        $del = $this->deleteJson('/api/v1/admin/api-keys/' . $apiKeyId, [], $headers);
        $del->assertOk()->assertJsonPath('success', true);
    }

    public function test_api_keys_require_space_context(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        $res = $this->getJson('/api/v1/admin/api-keys');
        $res->assertStatus(404)
            ->assertJsonPath('code', 'NOT_FOUND');
    }
}
