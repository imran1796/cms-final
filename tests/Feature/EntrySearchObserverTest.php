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

final class EntrySearchObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_entry_update_to_draft_runs_observer_without_error(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $space = Space::query()->create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);
        $coll = Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [['id' => 'f1', 'handle' => 'title', 'type' => 'text']],
            'settings' => [],
        ]);
        $entry = Entry::query()->create([
            'space_id' => $space->id,
            'collection_id' => $coll->id,
            'status' => 'published',
            'published_at' => now(),
            'data' => ['title' => 'Post'],
            'title' => 'Post',
            'slug' => 'post',
        ]);

        $user = User::factory()->create();
        $user->assignRole('Admin');
        $user->givePermissionTo('posts.update');
        Sanctum::actingAs($user);

        $res = $this->putJson("/api/v1/admin/posts/{$entry->id}", [
            'status' => 'draft',
            'data' => ['title' => 'Post Draft'],
        ], ['X-Space-Id' => (string) $space->id]);

        $res->assertOk();
        $entry->refresh();
        $this->assertSame('draft', $entry->status);
    }

    public function test_entry_delete_runs_observer_without_error(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $space = Space::query()->create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);
        $coll = Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [['id' => 'f1', 'handle' => 'title', 'type' => 'text']],
            'settings' => [],
        ]);
        $entry = Entry::query()->create([
            'space_id' => $space->id,
            'collection_id' => $coll->id,
            'status' => 'published',
            'published_at' => now(),
            'data' => ['title' => 'Post'],
            'title' => 'Post',
            'slug' => 'post',
        ]);

        $user = User::factory()->create();
        $user->assignRole('Admin');
        $user->givePermissionTo('posts.delete');
        Sanctum::actingAs($user);

        $res = $this->deleteJson("/api/v1/admin/posts/{$entry->id}", [], ['X-Space-Id' => (string) $space->id]);

        $res->assertOk();
        $this->assertNull(Entry::find($entry->id));
    }
}
