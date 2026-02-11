<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

final class PublicSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_proxy_returns_published_only(): void
    {
        $space = \App\Models\Space::factory()->create();

        $col = \App\Models\Collection::create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [],
            'settings' => ['search' => ['enabled' => true]],
        ]);

        $published = \App\Models\Entry::create([
            'space_id' => $space->id,
            'collection_id' => $col->id,
            'status' => 'published',
            'published_at' => Carbon::now(),
            'data' => ['title' => 'Hello World', 'slug' => 'hello-world'],
            'title' => 'Hello World',
            'slug' => 'hello-world',
        ]);

        \App\Models\Entry::create([
            'space_id' => $space->id,
            'collection_id' => $col->id,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'Hello Draft', 'slug' => 'hello-draft'],
            'title' => 'Hello Draft',
            'slug' => 'hello-draft',
        ]);

        $res = $this->getJson('/api/content/search/posts?q=Hello&limit=10', [
            'X-Space-Id' => (string)$space->id,
        ]);

        $res->assertOk()
            ->assertJsonPath('success', true);

        $items = (array)$res->json('data.items');
        $this->assertNotEmpty($items);

        $this->assertEquals('published', $items[0]['status']);
    }
}
