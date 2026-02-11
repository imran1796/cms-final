<?php

namespace Tests\Feature;

use App\Models\Space;
use App\Models\Collection;
use App\Models\Entry;
use App\Modules\Content\Services\PreviewTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PreviewTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_returns_entry_when_token_valid(): void
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
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'Draft Preview'],
        ]);

        $tokens = app(PreviewTokenService::class);

        $token = $tokens->generate($space->id, 'posts', $entry->id, 900);

        $res = $this->getJson("/api/content/preview/posts/{$entry->id}?token=" . urlencode($token), [
            'X-Space-Id' => (string) $space->id,
        ]);

        $res->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.id', $entry->id);
    }

    public function test_preview_returns_403_when_token_invalid(): void
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
            'fields' => [],
            'settings' => [],
        ]);

        $entry = Entry::query()->create([
            'space_id' => $space->id,
            'collection_id' => $collection->id,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'Draft Preview'],
        ]);

        $res = $this->getJson("/api/content/preview/posts/{$entry->id}?token=invalid-token", [
            'X-Space-Id' => (string) $space->id,
        ]);

        $res->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'FORBIDDEN',
            ]);
    }
}
