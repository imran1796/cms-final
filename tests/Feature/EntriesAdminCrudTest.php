<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Space;
use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class EntriesAdminCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_entries_crud_for_posts_collection(): void
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
        $admin->givePermissionTo('posts.create');
        $admin->givePermissionTo('posts.read');
        $admin->givePermissionTo('posts.update');
        $admin->givePermissionTo('posts.delete');

        Sanctum::actingAs($admin);

        Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                ['id' => 'f2', 'handle' => 'slug', 'label' => 'Slug', 'type' => 'text', 'required' => false],
            ],
            'settings' => [],
        ]);

        $headers = ['X-Space-Id' => (string) $space->id];

        $create = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => ['title' => 'Hello', 'slug' => 'hello'],
        ], $headers);

        $create->assertStatus(201)->assertJsonPath('success', true);
        $entryId = $create->json('data.id');

        $list = $this->getJson('/api/v1/admin/posts', $headers);
        $list->assertOk()->assertJsonPath('success', true);

        $get = $this->getJson("/api/v1/admin/posts/{$entryId}?fields=title,slug&populate=1&max_depth=2", $headers);
        $get->assertOk()->assertJsonPath('success', true);

        $update = $this->putJson("/api/v1/admin/posts/{$entryId}", [
            'status' => 'published',
            'data' => ['title' => 'Hello Updated', 'slug' => 'hello-updated'],
        ], $headers);

        $update->assertOk()->assertJsonPath('data.status', 'published');

        $del = $this->deleteJson("/api/v1/admin/posts/{$entryId}", [], $headers);
        $del->assertOk()->assertJsonPath('success', true);
    }
}
