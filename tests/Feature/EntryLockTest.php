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

final class EntryLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_lock_and_unlock_entry(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $space = Space::query()->create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);

        $col = Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [],
            'settings' => [],
        ]);

        $entry = Entry::query()->create([
            'space_id' => $space->id,
            'collection_id' => $col->id,
            'status' => 'draft',
            'data' => ['title' => 'Test', 'slug' => 'test'],
            'title' => 'Test',
            'slug' => 'test',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('posts.read');
        Sanctum::actingAs($user);

        $headers = ['X-Space-Id' => (string) $space->id];

        $lock = $this->postJson("/api/v1/admin/posts/{$entry->id}/lock", [], $headers);
        $lock->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.locked', true);
        $this->assertArrayHasKey('locked_by', $lock->json('data'));

        $unlock = $this->postJson("/api/v1/admin/posts/{$entry->id}/unlock", [], $headers);
        $unlock->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.locked', false);
    }

    public function test_lock_conflict_returns_409_with_locked_by(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $space = Space::query()->create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);

        $col = Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [],
            'settings' => [],
        ]);

        $entry = Entry::query()->create([
            'space_id' => $space->id,
            'collection_id' => $col->id,
            'status' => 'draft',
            'data' => ['title' => 'Test'],
            'title' => 'Test',
            'slug' => 'test',
        ]);

        $userA = User::factory()->create(['name' => 'User A']);
        $userA->givePermissionTo('posts.read');
        $userB = User::factory()->create(['name' => 'User B']);
        $userB->givePermissionTo('posts.read');

        $headers = ['X-Space-Id' => (string) $space->id];

        Sanctum::actingAs($userA);
        $this->postJson("/api/v1/admin/posts/{$entry->id}/lock", [], $headers)->assertOk();

        Sanctum::actingAs($userB);
        $conflict = $this->postJson("/api/v1/admin/posts/{$entry->id}/lock", [], $headers);
        $conflict->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'ENTRY_LOCKED')
            ->assertJsonPath('meta.locked_by.id', $userA->id)
            ->assertJsonPath('meta.locked_by.name', 'User A');
    }
}
