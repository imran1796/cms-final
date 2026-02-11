<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Space;
use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ChoiceFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_select_field_stores_options(): void
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
            'handle' => 'status',
            'label' => 'Status',
            'type' => 'select',
            'required' => true,
            'options' => ['values' => ['draft', 'published', 'archived']],
        ], $headers);

        $addField->assertOk()->assertJsonPath('success', true);
        $fields = $addField->json('data.fields');
        $this->assertNotEmpty($fields);
        $statusField = collect($fields)->firstWhere('handle', 'status');
        $this->assertNotNull($statusField);
        $this->assertSame('select', $statusField['type']);
        $this->assertArrayHasKey('options', $statusField);
        $this->assertSame(['draft', 'published', 'archived'], $statusField['options']['values']);
    }

    public function test_add_select_field_accepts_options_as_list(): void
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
            'handle' => 'category',
            'label' => 'Category',
            'type' => 'enum',
            'required' => false,
            'options' => ['a', 'b', 'c'],
        ], $headers);

        $addField->assertOk();
        $fields = $addField->json('data.fields');
        $catField = collect($fields)->firstWhere('handle', 'category');
        $this->assertSame(['values' => ['a', 'b', 'c']], $catField['options']);
    }

    public function test_add_tags_field_stores_options(): void
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
            'handle' => 'tags',
            'label' => 'Tags',
            'type' => 'tags',
            'required' => false,
            'options' => ['values' => ['php', 'laravel', 'cms']],
        ], $headers);

        $addField->assertOk();
        $fields = $addField->json('data.fields');
        $tagsField = collect($fields)->firstWhere('handle', 'tags');
        $this->assertSame('tags', $tagsField['type']);
        $this->assertSame(['php', 'laravel', 'cms'], $tagsField['options']['values']);
    }

    public function test_entry_with_valid_select_value_passes(): void
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
                ['id' => 'f2', 'handle' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['values' => ['draft', 'published']]],
            ],
            'settings' => [],
        ]);

        $headers = ['X-Space-Id' => (string) $space->id];

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => ['title' => 'Post', 'status' => 'published'],
        ], $headers);

        $res->assertStatus(201)->assertJsonPath('data.data.status', 'published');
    }

    public function test_entry_with_invalid_select_value_fails(): void
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
                ['id' => 'f2', 'handle' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['values' => ['draft', 'published']]],
            ],
            'settings' => [],
        ]);

        $headers = ['X-Space-Id' => (string) $space->id];

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => ['title' => 'Post', 'status' => 'invalid-status'],
        ], $headers);

        $res->assertStatus(422);
        $this->assertArrayHasKey('errors', $res->json());
    }

    public function test_entry_with_valid_tags_passes(): void
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
                ['id' => 'f2', 'handle' => 'tags', 'label' => 'Tags', 'type' => 'tags', 'required' => false, 'options' => ['values' => ['php', 'laravel', 'cms']]],
            ],
            'settings' => [],
        ]);

        $headers = ['X-Space-Id' => (string) $space->id];

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => ['title' => 'Post', 'tags' => ['php', 'laravel']],
        ], $headers);

        $res->assertStatus(201)->assertJsonPath('data.data.tags', ['php', 'laravel']);
    }

    public function test_entry_with_invalid_tag_value_fails(): void
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
                ['id' => 'f2', 'handle' => 'tags', 'label' => 'Tags', 'type' => 'tags', 'required' => false, 'options' => ['values' => ['php', 'laravel']]],
            ],
            'settings' => [],
        ]);

        $headers = ['X-Space-Id' => (string) $space->id];

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => ['title' => 'Post', 'tags' => ['php', 'invalid-tag']],
        ], $headers);

        $res->assertStatus(422);
        $this->assertArrayHasKey('errors', $res->json());
    }
}
