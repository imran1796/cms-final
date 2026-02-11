<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CliExportImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_then_import_exits_zero(): void
    {
        Storage::fake('local');

        $spaceId = DB::table('spaces')->insertGetId([
            'handle' => 'demo',
            'name' => 'Demo',
            'settings' => json_encode([]),
            'storage_prefix' => 'demo/',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('collections')->insert([
            'space_id' => $spaceId,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => json_encode([]),
            'settings' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan("cms:export {$spaceId}")
            ->assertExitCode(0);

        $files = Storage::disk('local')->allFiles('exports');
        $this->assertNotEmpty($files);

        $file = end($files);

        DB::table('collections')->delete();
        DB::table('spaces')->delete();

        $this->artisan("cms:import {$file}")
            ->assertExitCode(0);

        $this->assertTrue(DB::table('spaces')->where('handle', 'demo')->exists());
        $this->assertTrue(DB::table('collections')->where('handle', 'posts')->exists());
    }
}
