<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CliModelCreateDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_create_and_delete(): void
    {
        $spaceId = DB::table('spaces')->insertGetId([
            'handle' => 'demo',
            'name' => 'Demo',
            'settings' => json_encode([]),
            'storage_prefix' => 'demo/',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan("cms:model:create {$spaceId} posts --type=collection")
            ->assertExitCode(0);

        $this->assertTrue(DB::table('collections')->where('space_id', $spaceId)->where('handle', 'posts')->exists());

        $this->artisan("cms:model:delete {$spaceId} posts --force")
            ->assertExitCode(0);

        $this->assertFalse(DB::table('collections')->where('space_id', $spaceId)->where('handle', 'posts')->exists());
    }
}
