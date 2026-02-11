<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Entry;
use App\Models\Space;
use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class RelationFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_relation_field_stores_relation_schema(): void
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
            'handle' => 'related_post',
            'label' => 'Related Post',
            'type' => 'relation',
            'required' => false,
            'relation' => ['collection' => 'posts', 'max' => 1],
        ], $headers);

        $addField->assertOk()->assertJsonPath('success', true);
        $fields = $addField->json('data.fields');
        $relField = collect($fields)->firstWhere('handle', 'related_post');
        $this->assertNotNull($relField);
        $this->assertSame('relation', $relField['type']);
        $this->assertSame(['collection' => 'posts', 'max' => 1], $relField['relation']);
    }

    public function test_entry_with_valid_single_relation_passes(): void
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

        $coll = Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                ['id' => 'f2', 'handle' => 'related', 'label' => 'Related', 'type' => 'relation', 'required' => false, 'relation' => ['collection' => 'posts', 'max' => 1]],
            ],
            'settings' => [],
        ]);

        $targetEntry = Entry::query()->create([
            'space_id' => $space->id,
            'collection_id' => $coll->id,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'Target'],
            'title' => 'Target',
            'slug' => 'target',
        ]);

        $headers = ['X-Space-Id' => (string) $space->id];

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => ['title' => 'Post A', 'related' => $targetEntry->id],
        ], $headers);

        $res->assertStatus(201)->assertJsonPath('data.data.related', $targetEntry->id);
    }

    public function test_entry_with_invalid_single_relation_fails(): void
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
                ['id' => 'f2', 'handle' => 'related', 'label' => 'Related', 'type' => 'relation', 'required' => false, 'relation' => ['collection' => 'posts', 'max' => 1]],
            ],
            'settings' => [],
        ]);

        $headers = ['X-Space-Id' => (string) $space->id];

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => ['title' => 'Post A', 'related' => 99999],
        ], $headers);

        $res->assertStatus(422);
        $this->assertArrayHasKey('errors', $res->json());
    }

    public function test_entry_with_valid_multiple_relation_passes(): void
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

        $coll = Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                ['id' => 'f2', 'handle' => 'related', 'label' => 'Related', 'type' => 'relation', 'required' => false, 'relation' => ['collection' => 'posts', 'max' => null]],
            ],
            'settings' => [],
        ]);

        $e1 = Entry::query()->create([
            'space_id' => $space->id,
            'collection_id' => $coll->id,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'E1'],
            'title' => 'E1',
            'slug' => 'e1',
        ]);
        $e2 = Entry::query()->create([
            'space_id' => $space->id,
            'collection_id' => $coll->id,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'E2'],
            'title' => 'E2',
            'slug' => 'e2',
        ]);

        $headers = ['X-Space-Id' => (string) $space->id];

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => ['title' => 'Post', 'related' => [$e1->id, $e2->id]],
        ], $headers);

        $res->assertStatus(201)->assertJsonPath('data.data.related', [$e1->id, $e2->id]);
    }

    public function test_entry_with_invalid_multiple_relation_fails(): void
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

        $coll = Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                ['id' => 'f2', 'handle' => 'related', 'label' => 'Related', 'type' => 'relation', 'required' => false, 'relation' => ['collection' => 'posts', 'max' => null]],
            ],
            'settings' => [],
        ]);

        $e1 = Entry::query()->create([
            'space_id' => $space->id,
            'collection_id' => $coll->id,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'E1'],
            'title' => 'E1',
            'slug' => 'e1',
        ]);

        $headers = ['X-Space-Id' => (string) $space->id];

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => ['title' => 'Post', 'related' => [$e1->id, 99999]],
        ], $headers);

        $res->assertStatus(422);
        $this->assertArrayHasKey('errors', $res->json());
    }
}
