<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Space;
use App\Models\Collection;
use App\Models\Entry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class PublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_publish_and_unpublish_entry(): void
    {
        $this->seed(\Database\Seeders\System\RolesAndPermissionsSeeder::class);

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
                ['id' => 'f2', 'handle' => 'slug', 'label' => 'Slug', 'type' => 'text', 'required' => false],
            ],
            'settings' => [],
        ]);

        $entry = Entry::query()->create([
            'space_id' => $space->id,
            'collection_id' => $collection->id,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'Hello', 'slug' => 'hello'],
        ]);

        $admin = User::factory()->create([
            'email' => 'admin@cms.local',
            'password' => Hash::make('secret123'),
        ]);

        $admin->givePermissionTo('posts.publish');

        $this->actingAs($admin);

        $res = $this->postJson("/api/v1/admin/posts/{$entry->id}/publish", [], [
            'X-Space-Id' => (string) $space->id,
        ]);

        $res->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $entry->refresh();
        $this->assertSame('published', $entry->status);
        $this->assertNotNull($entry->published_at);

        $res2 = $this->postJson("/api/v1/admin/posts/{$entry->id}/unpublish", [], [
            'X-Space-Id' => (string) $space->id,
        ]);

        $res2->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $entry->refresh();
        $this->assertSame('draft', $entry->status);
        $this->assertNull($entry->published_at);
    }
}
