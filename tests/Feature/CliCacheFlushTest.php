<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class CliCacheFlushTest extends TestCase
{
    use RefreshDatabase;

    public function test_cache_flush_exits_zero(): void
    {
        Cache::put('x', 'y', 60);

        $this->artisan('cms:cache:flush')
            ->assertExitCode(0);

        $this->assertNull(Cache::get('x'));
    }
}
