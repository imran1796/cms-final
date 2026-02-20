<?php

namespace Tests\Feature;

use App\Models\Space;
use App\Models\Collection;
use App\Models\Entry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class PublishScheduledCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduler_publishes_scheduled_with_published_at_lte_now(): void
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

        $scheduledAt = Carbon::now()->subMinute()->startOfSecond();

        $entry = Entry::query()->create([
            'space_id' => $space->id,
            'collection_id' => $collection->id,
            'status' => 'scheduled',
            'published_at' => $scheduledAt,
            'data' => ['title' => 'Scheduled post'],
        ]);

        $this->artisan('cms:publish-scheduled')->assertExitCode(0);

        $entry->refresh();
        $this->assertSame('published', $entry->status);
        $this->assertNotNull($entry->published_at);
        $this->assertSame($scheduledAt->timestamp, $entry->published_at?->timestamp);
    }
}
