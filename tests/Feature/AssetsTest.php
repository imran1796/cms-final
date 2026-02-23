<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_metadata_transform_and_folders(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');

        $space = \App\Models\Space::factory()->create([
            'storage_prefix' => 'space_' . 1,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        Sanctum::actingAs($admin);

        $headers = ['X-Space-Id' => (string)$space->id];

        $folder = $this->postJson('/api/v1/admin/assets/folders', [
            'name' => 'marketing',
            'parent_id' => null,
        ], $headers);

        $folder->assertStatus(201)->assertJsonPath('success', true);
        $folderId = (int)$folder->json('data.id');

        $childFolder = $this->postJson('/api/v1/admin/assets/folders', [
            'name' => 'campaigns',
            'parent_id' => $folderId,
        ], $headers);
        $childFolder->assertStatus(201)->assertJsonPath('success', true);
        $childFolderId = (int)$childFolder->json('data.id');

        $folders = $this->getJson('/api/v1/admin/assets/folders', $headers);
        $folders->assertOk()->assertJsonPath('success', true);
        $folders->assertJsonFragment(['id' => $folderId, 'name' => 'marketing', 'parent_id' => null]);
        $folders->assertJsonFragment(['id' => $childFolderId, 'name' => 'campaigns', 'parent_id' => $folderId]);

        $file = UploadedFile::fake()->image('hello.jpg', 800, 600);

        $upload = $this->post('/api/v1/admin/assets', [
            'file' => $file,
            'folder_id' => $folderId,
        ], $headers);

        $upload->assertStatus(201);
        $mediaId = (int)$upload->json('data.id');

        $list = $this->getJson('/api/v1/admin/assets', $headers);
        $list->assertOk()->assertJsonPath('success', true);

        $move = $this->postJson('/api/v1/admin/assets/move', [
            'ids' => [$mediaId],
            'folder_id' => null,
        ], $headers);

        $move->assertOk()->assertJsonPath('success', true);

        $orig = $this->get("/api/storage/media/{$mediaId}", $headers);
        $orig->assertOk();

        $img = $this->get("/api/storage/media/{$mediaId}/image?w=200&h=200&fit=crop&format=webp&q=80", $headers);
        $img->assertOk();

        $preset = $this->get("/api/storage/media/{$mediaId}/preset/thumb", $headers);
        $preset->assertOk();
    }

    public function test_public_asset_cross_tenant_returns_404(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');

        $spaceA = \App\Models\Space::factory()->create(['storage_prefix' => 'space_a']);
        $spaceB = \App\Models\Space::factory()->create(['storage_prefix' => 'space_b']);

        $media = \App\Modules\Assets\Models\Media::query()->create([
            'space_id' => $spaceA->id,
            'folder_id' => null,
            'disk' => 'local',
            'path' => 'test/cross.jpg',
            'filename' => 'cross.jpg',
            'mime' => 'image/jpeg',
            'size' => 100,
            'kind' => 'image',
        ]);

        $res = $this->get("/api/storage/media/{$media->id}", ['X-Space-Id' => (string) $spaceB->id]);
        $res->assertStatus(404)->assertJsonPath('code', 'NOT_FOUND');
    }

    public function test_strict_upload_validation_rejects_disallowed_file_type(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');

        config()->set('cms_assets.strict_upload_validation', true);
        config()->set('cms_assets.allowed_mime_types', ['image/jpeg', 'image/png']);
        config()->set('cms_assets.allowed_extensions', ['jpg', 'jpeg', 'png']);

        $space = \App\Models\Space::factory()->create(['storage_prefix' => 'space_strict_upload']);
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        Sanctum::actingAs($admin);

        $headers = ['X-Space-Id' => (string) $space->id];
        $file = UploadedFile::fake()->create('evil.exe', 20, 'application/octet-stream');

        $res = $this->post('/api/v1/admin/assets', [
            'file' => $file,
        ], $headers);

        $res->assertStatus(422);
    }

    public function test_strict_upload_validation_rejects_disallowed_chunked_extension_on_complete(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        config()->set('cms_assets.strict_upload_validation', true);
        config()->set('cms_assets.allowed_mime_types', ['image/jpeg', 'image/png']);
        config()->set('cms_assets.allowed_extensions', ['jpg', 'jpeg', 'png']);

        $space = \App\Models\Space::factory()->create(['storage_prefix' => 'space_strict_chunk']);
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        Sanctum::actingAs($admin);

        $headers = ['X-Space-Id' => (string) $space->id];

        $init = $this->postJson('/api/v1/admin/assets/upload/chunk/init', [
            'filename' => 'malware.exe',
            'total_chunks' => 1,
        ], $headers);
        $init->assertStatus(201);

        $uploadId = (string) $init->json('data.upload_id');
        $chunkFile = UploadedFile::fake()->create('part.bin', 1, 'application/octet-stream');

        $chunk = $this->post('/api/v1/admin/assets/upload/chunk', [
            'upload_id' => $uploadId,
            'chunk_index' => 0,
            'file' => $chunkFile,
        ], $headers);
        $chunk->assertStatus(200);

        $complete = $this->postJson('/api/v1/admin/assets/upload/chunk/complete', [
            'upload_id' => $uploadId,
        ], $headers);

        $complete->assertStatus(422);
    }

    public function test_assets_config_requires_authentication(): void
    {
        $res = $this->getJson('/api/v1/admin/assets/config');
        $res->assertStatus(401);
    }

    public function test_assets_config_returns_effective_upload_limit(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        config()->set('cms_assets.max_upload_size_mb', 1);

        $space = \App\Models\Space::factory()->create(['storage_prefix' => 'space_assets_cfg']);
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        Sanctum::actingAs($admin);

        $headers = ['X-Space-Id' => (string) $space->id];
        $res = $this->getJson('/api/v1/admin/assets/config', $headers);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Assets config')
            ->assertJsonPath('data.strict_upload_validation', false);

        $uploadBytes = (int) $res->json('data.upload_max_filesize_bytes');
        $postBytes = (int) $res->json('data.post_max_size_bytes');
        $bytes = (int) $res->json('data.effective_max_upload_bytes');
        $this->assertGreaterThan(0, $uploadBytes);
        $this->assertGreaterThan(0, $postBytes);
        $this->assertGreaterThan(0, $bytes);
        $this->assertLessThanOrEqual(1024 * 1024, $bytes);
        $this->assertLessThanOrEqual(min($uploadBytes, $postBytes, 1024 * 1024), $bytes);
        $this->assertNotEmpty((string) $res->json('data.upload_max_filesize_human'));
        $this->assertNotEmpty((string) $res->json('data.post_max_size_human'));
        $this->assertNotEmpty((string) $res->json('data.effective_max_upload_human'));
    }
}
