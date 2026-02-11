<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

final class SavedViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_views_crud_and_scoping(): void
    {
        $this->seed(\Database\Seeders\System\RolesAndPermissionsSeeder::class);

        $user1 = User::factory()->create([
            'email' => 'u1@x.com',
            'password' => Hash::make('secret123'),
        ]);
        $user2 = User::factory()->create([
            'email' => 'u2@x.com',
            'password' => Hash::make('secret123'),
        ]);

        $user1->assignRole('Super Admin');
        $user2->assignRole('Super Admin');

        $space = \App\Models\Space::create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);

        $headers = ['X-Space-Id' => (string) $space->id];

        $this->actingAs($user1, 'sanctum');

        $create = $this->postJson('/api/v1/admin/saved-views', [
            'resource' => 'posts',
            'name' => 'My Default',
            'config' => [
                'columns' => ['id', 'title', 'status'],
                'filters' => ['status' => 'draft'],
                'sort' => '-created_at',
                'limit' => 10,
            ],
        ], $headers);

        $create->assertStatus(201)->assertJsonPath('success', true);
        $id = (int) $create->json('data.id');

        $list = $this->getJson('/api/v1/admin/saved-views?resource=posts', $headers);
        $list->assertOk();
        $this->assertCount(1, $list->json('data'));

        $upd = $this->putJson("/api/v1/admin/saved-views/{$id}", [
            'name' => 'My Default Updated',
            'config' => ['limit' => 25],
        ], $headers);

        $upd->assertOk()
            ->assertJsonPath('data.name', 'My Default Updated')
            ->assertJsonPath('data.config.limit', 25);

        $this->actingAs($user2, 'sanctum');

        $listOther = $this->getJson('/api/v1/admin/saved-views?resource=posts', $headers);
        $listOther->assertOk();
        $this->assertCount(0, $listOther->json('data'));

        $delForbidden = $this->deleteJson("/api/v1/admin/saved-views/{$id}", [], $headers);
        $delForbidden->assertStatus(404);

        $this->actingAs($user1, 'sanctum');

        $del = $this->deleteJson("/api/v1/admin/saved-views/{$id}", [], $headers);
        $del->assertOk()->assertJsonPath('success', true);

        $list2 = $this->getJson('/api/v1/admin/saved-views?resource=posts', $headers);
        $this->assertCount(0, $list2->json('data'));
    }
}
