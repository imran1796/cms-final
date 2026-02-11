<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Entry;
use App\Models\Space;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class PerCollectionSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_uses_collection_filterable_and_sortable_attributes(): void
    {
        $space = Space::factory()->create();

        $col = Collection::create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [],
            'settings' => [
                'search' => [
                    'enabled' => true,
                    'filterable_attributes' => ['status'],
                    'sortable_attributes' => ['id', 'published_at', 'title'],
                ],
            ],
        ]);

        $older = Entry::create([
            'space_id' => $space->id,
            'collection_id' => $col->id,
            'status' => 'published',
            'published_at' => Carbon::now()->subDays(2),
            'data' => ['title' => 'Older Post', 'slug' => 'older-post'],
            'title' => 'Older Post',
            'slug' => 'older-post',
        ]);

        $newer = Entry::create([
            'space_id' => $space->id,
            'collection_id' => $col->id,
            'status' => 'published',
            'published_at' => Carbon::now()->subDay(1),
            'data' => ['title' => 'Newer Post', 'slug' => 'newer-post'],
            'title' => 'Newer Post',
            'slug' => 'newer-post',
        ]);

        $res = $this->getJson('/api/content/search/posts?q=Post&limit=10&sort=-published_at', [
            'X-Space-Id' => (string) $space->id,
        ]);

        $res->assertOk()->assertJsonPath('success', true);

        $items = $res->json('data.items');
        $this->assertCount(2, $items);
        $this->assertEquals($newer->id, $items[0]['id']);
        $this->assertEquals($older->id, $items[1]['id']);

        $meta = $res->json('data.meta');
        $this->assertNotEmpty($meta);
        $this->assertEquals('db', $meta['engine']);
    }

    public function test_search_respects_collection_sortable_attributes_ignore_unknown_sort(): void
    {
        $space = Space::factory()->create();

        $col = Collection::create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [],
            'settings' => [
                'search' => [
                    'enabled' => true,
                    'sortable_attributes' => ['id'], // only id allowed
                ],
            ],
        ]);

        Entry::create([
            'space_id' => $space->id,
            'collection_id' => $col->id,
            'status' => 'published',
            'published_at' => Carbon::now(),
            'data' => ['title' => 'Only Post', 'slug' => 'only-post'],
            'title' => 'Only Post',
            'slug' => 'only-post',
        ]);

        $res = $this->getJson('/api/content/search/posts?q=Post&limit=10&sort=unknown_attr', [
            'X-Space-Id' => (string) $space->id,
        ]);

        $res->assertOk();
        $this->assertCount(1, $res->json('data.items'));
    }

    public function test_search_disabled_for_collection_returns_403(): void
    {
        $space = Space::factory()->create();

        Collection::create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [],
            'settings' => ['search' => ['enabled' => false]],
        ]);

        $res = $this->getJson('/api/content/search/posts?q=test&limit=10', [
            'X-Space-Id' => (string) $space->id,
        ]);

        $res->assertStatus(403);
    }
}
