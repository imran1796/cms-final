<?php

namespace Tests\Feature;

use App\Models\Space;
use App\Models\User;
use App\Modules\Assets\Models\MediaFolder;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class FinderAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private int $spaceId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $space = Space::query()->create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);
        $this->spaceId = $space->id;

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');
        $this->admin->givePermissionTo('manage_assets');
    }

    public function test_finder_index_requires_auth(): void
    {
        $res = $this->getJson('/api/v1/admin/finder', ['X-Space-Id' => (string) $this->spaceId]);
        $res->assertStatus(401);
    }

    public function test_finder_index_requires_manage_assets_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor'); // no manage_assets
        Sanctum::actingAs($user);

        $res = $this->getJson('/api/v1/admin/finder', ['X-Space-Id' => (string) $this->spaceId]);
        $res->assertStatus(403);
    }

    public function test_finder_index_returns_folders_and_files(): void
    {
        Sanctum::actingAs($this->admin);

        $res = $this->getJson('/api/v1/admin/finder', ['X-Space-Id' => (string) $this->spaceId]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Finder')
            ->assertJsonStructure(['data' => ['folders', 'files']]);
        $this->assertIsArray($res->json('data.folders'));
        $this->assertIsArray($res->json('data.files'));
    }

    public function test_finder_create_folder_succeeds(): void
    {
        Sanctum::actingAs($this->admin);

        $res = $this->postJson('/api/v1/admin/finder/folders', [
            'name' => 'Documents',
            'parent_id' => null,
        ], ['X-Space-Id' => (string) $this->spaceId]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Documents')
            ->assertJsonPath('data.space_id', $this->spaceId)
            ->assertJsonStructure(['data' => ['id', 'name', 'parent_id', 'path']]);

        $this->assertDatabaseHas('media_folders', [
            'space_id' => $this->spaceId,
            'name' => 'Documents',
        ]);
    }

    public function test_finder_rename_folder_succeeds(): void
    {
        $folder = MediaFolder::query()->create([
            'space_id' => $this->spaceId,
            'parent_id' => null,
            'name' => 'Old Name',
            'path' => '/Old Name',
        ]);
        Sanctum::actingAs($this->admin);

        $res = $this->putJson('/api/v1/admin/finder/folders/' . $folder->id, [
            'name' => 'New Name',
        ], ['X-Space-Id' => (string) $this->spaceId]);

        $res->assertOk()
            ->assertJsonPath('data.name', 'New Name');
        $folder->refresh();
        $this->assertSame('New Name', $folder->name);
    }

    public function test_finder_delete_folder_succeeds_when_empty(): void
    {
        $folder = MediaFolder::query()->create([
            'space_id' => $this->spaceId,
            'parent_id' => null,
            'name' => 'To Delete',
            'path' => '/To Delete',
        ]);
        $id = $folder->id;
        Sanctum::actingAs($this->admin);

        $res = $this->deleteJson('/api/v1/admin/finder/folders/' . $id, [], ['X-Space-Id' => (string) $this->spaceId]);

        $res->assertOk()->assertJsonPath('message', 'Folder deleted');
        $this->assertDatabaseMissing('media_folders', ['id' => $id]);
    }

    public function test_finder_delete_folder_fails_when_not_empty(): void
    {
        $folder = MediaFolder::query()->create([
            'space_id' => $this->spaceId,
            'parent_id' => null,
            'name' => 'Parent',
            'path' => '/Parent',
        ]);
        MediaFolder::query()->create([
            'space_id' => $this->spaceId,
            'parent_id' => $folder->id,
            'name' => 'Child',
            'path' => '/Parent/Child',
        ]);
        Sanctum::actingAs($this->admin);

        $res = $this->deleteJson('/api/v1/admin/finder/folders/' . $folder->id, [], ['X-Space-Id' => (string) $this->spaceId]);

        $res->assertStatus(422);
        $this->assertDatabaseHas('media_folders', ['id' => $folder->id]);
    }
}
