<?php

namespace Tests\Feature;

use App\Models\Space;
use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class CollectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_crud_and_fields_and_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $space = Space::query()->create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        $headers = ['X-Space-Id' => (string) $space->id];

        $create = $this->postJson('/api/v1/admin/collections', [
            'handle' => 'posts',
            'type' => 'collection',
            'settings' => ['drafts' => true],
        ], $headers);

        $create->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.handle', 'posts')
            ->assertJsonPath('data.space_id', $space->id);

        $id = $create->json('data.id');

        $this->assertNotNull(Permission::where('name', 'posts.create')->first());
        $this->assertNotNull(Permission::where('name', 'posts.read')->first());
        $this->assertNotNull(Permission::where('name', 'posts.update')->first());
        $this->assertNotNull(Permission::where('name', 'posts.delete')->first());
        $this->assertNotNull(Permission::where('name', 'posts.publish')->first());

        $addField = $this->postJson("/api/v1/admin/collections/{$id}/fields", [
            'handle' => 'title',
            'label' => 'Title',
            'type' => 'text',
            'required' => true,
        ], $headers);

        $addField->assertOk()->assertJsonPath('success', true);

        $fields = $addField->json('data.fields');
        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
        $fieldId = $fields[0]['id'];

        $updateField = $this->putJson("/api/v1/admin/collections/{$id}/fields/{$fieldId}", [
            'handle' => 'title',
            'label' => 'Post Title',
            'type' => 'text',
            'required' => true,
        ], $headers);

        $updateField->assertOk()->assertJsonPath('success', true);

        $deleteField = $this->deleteJson("/api/v1/admin/collections/{$id}/fields/{$fieldId}", [], $headers);
        $deleteField->assertOk()->assertJsonPath('success', true);

        $update = $this->putJson("/api/v1/admin/collections/{$id}", [
            'type' => 'collection',
            'settings' => ['drafts' => false],
        ], $headers);
        $update->assertOk()->assertJsonPath('success', true);

        $del = $this->deleteJson("/api/v1/admin/collections/{$id}", [], $headers);
        $del->assertOk()->assertJsonPath('success', true);
    }

    public function test_cross_tenant_collection_returns_404(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $spaceA = Space::query()->create([
            'handle' => 'space_a',
            'name' => 'Space A',
            'settings' => [],
            'storage_prefix' => 'spaces/a',
        ]);
        $spaceB = Space::query()->create([
            'handle' => 'space_b',
            'name' => 'Space B',
            'settings' => [],
            'storage_prefix' => 'spaces/b',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        $headersA = ['X-Space-Id' => (string) $spaceA->id];
        $create = $this->postJson('/api/v1/admin/collections', [
            'handle' => 'posts',
            'type' => 'collection',
            'settings' => [],
        ], $headersA);
        $create->assertStatus(201);
        $collectionId = $create->json('data.id');

        $headersB = ['X-Space-Id' => (string) $spaceB->id];
        $get = $this->getJson("/api/v1/admin/collections/{$collectionId}", $headersB);
        $get->assertStatus(404)->assertJsonPath('code', 'NOT_FOUND');
    }
}
