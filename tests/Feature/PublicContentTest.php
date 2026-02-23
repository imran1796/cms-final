<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

final class PublicContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_list_returns_only_published(): void
    {
        $this->seed(\Database\Seeders\System\RolesAndPermissionsSeeder::class);
        $space = \App\Models\Space::create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        Sanctum::actingAs($admin);

        $headers = ['X-Space-Id' => (string)$space->id];

        $this->postJson('/api/v1/admin/collections', [
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['name' => 'title', 'type' => 'text', 'required' => true],
                ['name' => 'slug', 'type' => 'text', 'required' => false],
            ],
        ], $headers)->assertCreated();

        $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => ['title' => 'Draft One', 'slug' => 'draft-one'],
        ], $headers)->assertCreated();

        $published = $this->postJson('/api/v1/admin/posts', [
            'status' => 'published',
            'data' => ['title' => 'Pub One', 'slug' => 'pub-one'],
        ], $headers)->assertCreated();

        $res = $this->getJson('/api/content/posts?limit=10', $headers);
        $res->assertOk();

        $items = $res->json('data.items');
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertSame('Pub One', $items[0]['data']['title']);
    }

    public function test_public_list_supports_filter_sort_limit(): void
    {
        $this->seed(\Database\Seeders\System\RolesAndPermissionsSeeder::class);
        $space = \App\Models\Space::create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        Sanctum::actingAs($admin);

        $headers = ['X-Space-Id' => (string)$space->id];

        $this->postJson('/api/v1/admin/collections', [
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['name' => 'title', 'type' => 'text', 'required' => true],
                ['name' => 'slug', 'type' => 'text', 'required' => true],
            ],
        ], $headers)->assertCreated();

        $this->postJson('/api/v1/admin/posts', [
            'status' => 'published',
            'data' => ['title' => 'B', 'slug' => 'b'],
        ], $headers)->assertCreated();

        $this->postJson('/api/v1/admin/posts', [
            'status' => 'published',
            'data' => ['title' => 'A', 'slug' => 'a'],
        ], $headers)->assertCreated();

        $res = $this->getJson('/api/content/posts?limit=1&sort=created_at&filter[slug]=a', $headers);
        $res->assertOk();

        $items = $res->json('data.items');
        $this->assertCount(1, $items);
        $this->assertSame('A', $items[0]['data']['title']);
    }

    public function test_public_fields_projection_works(): void
    {
        $this->seed(\Database\Seeders\System\RolesAndPermissionsSeeder::class);
        $space = \App\Models\Space::create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        Sanctum::actingAs($admin);

        $headers = ['X-Space-Id' => (string)$space->id];

        $this->postJson('/api/v1/admin/collections', [
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['name' => 'title', 'type' => 'text', 'required' => true],
                ['name' => 'slug', 'type' => 'text', 'required' => false],
            ],
        ], $headers)->assertCreated();

        $this->postJson('/api/v1/admin/posts', [
            'status' => 'published',
            'data' => ['title' => 'Hello', 'slug' => 'hello'],
        ], $headers)->assertCreated();

        $res = $this->getJson('/api/content/posts?fields=id,title&limit=10', $headers);
        $res->assertOk();

        $items = $res->json('data.items');
        $this->assertCount(1, $items);
        $this->assertArrayHasKey('id', $items[0]);
        $this->assertArrayHasKey('title', $items[0]);
        $this->assertArrayNotHasKey('data', $items[0]);
    }

    public function test_public_list_populate_relation_returns_related_published_entry(): void
    {
        $this->seed(\Database\Seeders\System\RolesAndPermissionsSeeder::class);
        $space = \App\Models\Space::create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        Sanctum::actingAs($admin);

        $headers = ['X-Space-Id' => (string)$space->id];

        $this->postJson('/api/v1/admin/collections', [
            'handle' => 'authors',
            'type' => 'collection',
            'fields' => [
                ['name' => 'name', 'type' => 'text', 'required' => true],
            ],
        ], $headers)->assertCreated();

        $this->postJson('/api/v1/admin/collections', [
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['name' => 'title', 'type' => 'text', 'required' => true],
                ['name' => 'author', 'type' => 'relation', 'required' => false, 'relation' => ['collection' => 'authors', 'max' => 1]],
            ],
        ], $headers)->assertCreated();

        $author = $this->postJson('/api/v1/admin/authors', [
            'status' => 'published',
            'data' => ['name' => 'John Writer'],
        ], $headers)->assertCreated();

        $authorId = (int) $author->json('data.id');

        $this->postJson('/api/v1/admin/posts', [
            'status' => 'published',
            'data' => ['title' => 'Populated Post', 'author' => $authorId],
        ], $headers)->assertCreated();

        $res = $this->getJson('/api/content/posts?limit=10&populate=author', $headers);
        $res->assertOk();

        $items = $res->json('data.items');
        $this->assertCount(1, $items);
        $this->assertSame('Populated Post', $items[0]['data']['title']);
        $this->assertSame($authorId, $items[0]['data']['author']['id'] ?? null);
        $this->assertSame('John Writer', $items[0]['data']['author']['data']['name'] ?? null);
    }
}
