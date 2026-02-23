<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Entry;
use App\Models\Space;
use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class EntrySchedulingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_with_future_published_at_sets_scheduled_status(): void
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
            ],
            'settings' => [],
        ]);

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'published_at' => Carbon::now()->addHour()->toIso8601String(),
            'data' => ['title' => 'Future post'],
        ], ['X-Space-Id' => (string) $space->id]);

        $res->assertCreated()
            ->assertJsonPath('data.status', 'scheduled');
    }

    public function test_update_with_future_published_at_sets_scheduled_status(): void
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
        $admin->givePermissionTo('posts.update');
        Sanctum::actingAs($admin);

        Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
            ],
            'settings' => [],
        ]);

        $create = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => ['title' => 'Draft'],
        ], ['X-Space-Id' => (string) $space->id]);
        $create->assertCreated();
        $entryId = (int) $create->json('data.id');

        $update = $this->putJson("/api/v1/admin/posts/{$entryId}", [
            'published_at' => Carbon::now()->addHour()->toIso8601String(),
            'data' => ['title' => 'Scheduled now'],
        ], ['X-Space-Id' => (string) $space->id]);

        $update->assertOk()
            ->assertJsonPath('data.status', 'scheduled');
    }

    public function test_unpublish_at_must_be_after_published_at(): void
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
            ],
            'settings' => [],
        ]);

        $publishedAt = Carbon::now()->addHour();
        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'published_at' => $publishedAt->toIso8601String(),
            'unpublish_at' => $publishedAt->copy()->subMinute()->toIso8601String(),
            'data' => ['title' => 'Invalid until'],
        ], ['X-Space-Id' => (string) $space->id]);

        $res->assertStatus(422);
    }

    public function test_unpublish_scheduled_command_unpublishes_entries_with_due_unpublish_at(): void
    {
        $space = Space::query()->create([
            'handle' => 'main',
            'name' => 'Main Space',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);

        $collection = Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
            ],
            'settings' => [],
        ]);

        $entry = Entry::query()->create([
            'space_id' => $space->id,
            'collection_id' => $collection->id,
            'status' => 'published',
            'published_at' => Carbon::now()->subHour(),
            'unpublish_at' => Carbon::now()->subMinute(),
            'data' => ['title' => 'Should unpublish'],
        ]);

        $this->artisan('cms:unpublish-scheduled')
            ->expectsOutput('Unpublished 1 entries.')
            ->assertExitCode(0);

        $this->artisan('cms:unpublish-scheduled')
            ->expectsOutput('Unpublished 0 entries.')
            ->assertExitCode(0);

        $entry->refresh();
        $this->assertSame('draft', $entry->status);
        $this->assertNull($entry->published_at);
        $this->assertNull($entry->unpublish_at);
    }

    public function test_publish_scheduled_command_processes_due_entries_once(): void
    {
        $space = Space::query()->create([
            'handle' => 'main',
            'name' => 'Main Space',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);

        $collection = Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
            ],
            'settings' => [],
        ]);

        $entry = Entry::query()->create([
            'space_id' => $space->id,
            'collection_id' => $collection->id,
            'status' => 'scheduled',
            'published_at' => Carbon::now()->subMinute(),
            'data' => ['title' => 'Should publish once'],
        ]);

        $this->artisan('cms:publish-scheduled')
            ->expectsOutput('Published 1 scheduled entries.')
            ->assertExitCode(0);

        $this->artisan('cms:publish-scheduled')
            ->expectsOutput('Published 0 scheduled entries.')
            ->assertExitCode(0);

        $entry->refresh();
        $this->assertSame('published', $entry->status);
    }

    public function test_admin_list_status_scheduled_returns_scheduled_entries(): void
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
        $admin->givePermissionTo('posts.read');
        Sanctum::actingAs($admin);

        $collection = Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
            ],
            'settings' => [],
        ]);

        Entry::query()->create([
            'space_id' => $space->id,
            'collection_id' => $collection->id,
            'status' => 'scheduled',
            'published_at' => Carbon::now()->addHour(),
            'data' => ['title' => 'Scheduled entry'],
        ]);

        Entry::query()->create([
            'space_id' => $space->id,
            'collection_id' => $collection->id,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'Draft entry'],
        ]);

        Entry::query()->create([
            'space_id' => $space->id,
            'collection_id' => $collection->id,
            'status' => 'published',
            'published_at' => Carbon::now()->subHour(),
            'data' => ['title' => 'Published entry'],
        ]);

        $res = $this->getJson('/api/v1/admin/posts?status=scheduled', [
            'X-Space-Id' => (string) $space->id,
        ]);

        $res->assertOk();
        $items = $res->json('data.data');
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertSame('scheduled', $items[0]['status']);
        $this->assertSame('Scheduled entry', $items[0]['data']['title'] ?? null);
    }

    public function test_public_delivery_only_shows_published_entries(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $space = Space::query()->create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        Sanctum::actingAs($admin);

        $collection = Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
            ],
            'settings' => [],
        ]);

        Entry::query()->create([
            'space_id' => $space->id,
            'collection_id' => $collection->id,
            'status' => 'published',
            'published_at' => Carbon::now()->subMinute(),
            'data' => ['title' => 'Visible'],
        ]);

        Entry::query()->create([
            'space_id' => $space->id,
            'collection_id' => $collection->id,
            'status' => 'scheduled',
            'published_at' => Carbon::now()->addHour(),
            'data' => ['title' => 'Hidden scheduled'],
        ]);

        Entry::query()->create([
            'space_id' => $space->id,
            'collection_id' => $collection->id,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'Hidden draft'],
        ]);

        $res = $this->getJson('/api/content/posts?limit=10', [
            'X-Space-Id' => (string) $space->id,
        ]);

        $res->assertOk();
        $items = $res->json('data.items');
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertSame('Visible', $items[0]['data']['title'] ?? null);
    }
}
