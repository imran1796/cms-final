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
}
