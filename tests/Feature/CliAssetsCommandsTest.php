<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CliAssetsCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_assets_commands_smoke(): void
    {
        $this->artisan('cms:assets:presets:generate')->assertExitCode(0);
        $this->artisan('cms:assets:thumbhash:generate --limit=10')->assertExitCode(0);
        $this->artisan('cms:assets:cleanup --dry-run')->assertExitCode(0);

        if (DB::getSchemaBuilder()->hasTable('media') && DB::getSchemaBuilder()->hasTable('media_variants')) {
            $spaceId = DB::table('spaces')->insertGetId([
                'handle' => 'demo',
                'name' => 'Demo',
                'settings' => json_encode([]),
                'storage_prefix' => 'demo/',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $mediaId = DB::table('media')->insertGetId([
                'space_id' => $spaceId,
                'filename' => 'x.jpg',
                'mime' => 'image/jpeg',
                'path' => 'demo/x.jpg',
                'meta' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->createMinimalImageAt('demo/x.jpg');

            $this->artisan('cms:assets:presets:generate --preset=thumb')->assertExitCode(0);

            $this->assertTrue(DB::table('media_variants')->where('media_id', $mediaId)->where('preset_key', 'thumb')->exists());

            $this->artisan('cms:assets:thumbhash:generate --limit=10')->assertExitCode(0);

            $m = DB::table('media')->where('id', $mediaId)->first();
            $meta = json_decode((string) $m->meta, true);
            $this->assertIsArray($meta);
            $this->assertArrayHasKey('thumbhash', $meta);
            $this->assertStringNotContainsString('thumbhash_pending_', $meta['thumbhash'] ?? '');
        }
    }

    private function createMinimalImageAt(string $path): void
    {
        $img = imagecreate(2, 2);
        if ($img === false) {
            return;
        }
        imagecolorallocate($img, 64, 128, 200);
        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);
        if ($png !== false) {
            Storage::disk('local')->put($path, $png);
        }
    }
}
