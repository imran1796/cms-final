<?php

namespace Tests\Feature;

use App\Modules\Assets\Models\Media;
use App\Models\Collection;
use App\Models\Space;
use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AssetFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_asset_field_stores_allowed_kinds(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $space = Space::query()->create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);
        $headers = ['X-Space-Id' => (string) $space->id];

        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        $create = $this->postJson('/api/v1/admin/collections', [
            'handle' => 'posts',
            'type' => 'collection',
            'settings' => [],
        ], $headers);
        $create->assertStatus(201);
        $id = $create->json('data.id');

        $addField = $this->postJson("/api/v1/admin/collections/{$id}/fields", [
            'handle' => 'cover',
            'label' => 'Cover Image',
            'type' => 'asset',
            'required' => false,
            'allowed_kinds' => ['image'],
        ], $headers);

        $addField->assertOk()->assertJsonPath('success', true);
        $fields = $addField->json('data.fields');
        $coverField = collect($fields)->firstWhere('handle', 'cover');
        $this->assertNotNull($coverField);
        $this->assertSame('asset', $coverField['type']);
        $this->assertSame(['image'], $coverField['allowed_kinds']);
    }

    public function test_entry_with_valid_single_asset_passes(): void
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
        Sanctum::actingAs($admin);

        $media = Media::query()->create([
            'space_id' => $space->id,
            'folder_id' => null,
            'disk' => 'local',
            'path' => 'test.jpg',
            'filename' => 'test.jpg',
            'mime' => 'image/jpeg',
            'size' => 1024,
            'kind' => 'image',
        ]);

        Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                ['id' => 'f2', 'handle' => 'cover', 'label' => 'Cover', 'type' => 'asset', 'required' => false],
            ],
            'settings' => [],
        ]);

        $headers = ['X-Space-Id' => (string) $space->id];

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => ['title' => 'Post', 'cover' => $media->id],
        ], $headers);

        $res->assertStatus(201)->assertJsonPath('data.data.cover', $media->id);
    }

    public function test_entry_with_invalid_single_asset_fails(): void
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
        Sanctum::actingAs($admin);

        Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                ['id' => 'f2', 'handle' => 'cover', 'label' => 'Cover', 'type' => 'asset', 'required' => false],
            ],
            'settings' => [],
        ]);

        $headers = ['X-Space-Id' => (string) $space->id];

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => ['title' => 'Post', 'cover' => 99999],
        ], $headers);

        $res->assertStatus(422);
        $this->assertArrayHasKey('errors', $res->json());
    }

    public function test_entry_with_valid_assets_array_passes(): void
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
        Sanctum::actingAs($admin);

        $m1 = Media::query()->create([
            'space_id' => $space->id,
            'folder_id' => null,
            'disk' => 'local',
            'path' => 'a.jpg',
            'filename' => 'a.jpg',
            'mime' => 'image/jpeg',
            'size' => 100,
            'kind' => 'image',
        ]);
        $m2 = Media::query()->create([
            'space_id' => $space->id,
            'folder_id' => null,
            'disk' => 'local',
            'path' => 'b.jpg',
            'filename' => 'b.jpg',
            'mime' => 'image/jpeg',
            'size' => 200,
            'kind' => 'image',
        ]);

        Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                ['id' => 'f2', 'handle' => 'gallery', 'label' => 'Gallery', 'type' => 'assets', 'required' => false],
            ],
            'settings' => [],
        ]);

        $headers = ['X-Space-Id' => (string) $space->id];

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => ['title' => 'Post', 'gallery' => [$m1->id, $m2->id]],
        ], $headers);

        $res->assertStatus(201)->assertJsonPath('data.data.gallery', [$m1->id, $m2->id]);
    }

    public function test_entry_with_invalid_assets_element_fails(): void
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
        Sanctum::actingAs($admin);

        $m1 = Media::query()->create([
            'space_id' => $space->id,
            'folder_id' => null,
            'disk' => 'local',
            'path' => 'a.jpg',
            'filename' => 'a.jpg',
            'mime' => 'image/jpeg',
            'size' => 100,
            'kind' => 'image',
        ]);

        Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                ['id' => 'f2', 'handle' => 'gallery', 'label' => 'Gallery', 'type' => 'assets', 'required' => false],
            ],
            'settings' => [],
        ]);

        $headers = ['X-Space-Id' => (string) $space->id];

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => ['title' => 'Post', 'gallery' => [$m1->id, 99999]],
        ], $headers);

        $res->assertStatus(422);
        $this->assertArrayHasKey('errors', $res->json());
    }
}
