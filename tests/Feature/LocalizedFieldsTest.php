<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Entry;
use App\Models\Space;
use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class LocalizedFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_field_definition_accepts_localized_flag(): void
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
            'fields' => [
                ['name' => 'title', 'type' => 'text', 'required' => true, 'localized' => true],
                ['name' => 'slug', 'type' => 'text', 'required' => false],
            ],
        ], $headers);

        $create->assertCreated();
        $fields = $create->json('data.fields');
        $this->assertIsArray($fields);
        $titleField = collect($fields)->firstWhere('handle', 'title');
        $this->assertTrue($titleField['localized'] ?? false);
    }

    public function test_localized_not_allowed_for_repeater_type(): void
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
            'fields' => [
                ['name' => 'title', 'type' => 'text'],
                ['name' => 'items', 'type' => 'repeater', 'localized' => true, 'fields' => []],
            ],
        ], $headers);

        $create->assertStatus(422);
    }

    public function test_entry_accepts_per_locale_data_for_localized_field(): void
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
        $admin->givePermissionTo('posts.create');
        $admin->givePermissionTo('posts.read');
        Sanctum::actingAs($admin);

        $collection = Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'localized' => true],
                ['id' => 'f2', 'handle' => 'slug', 'label' => 'Slug', 'type' => 'text', 'required' => false],
            ],
            'settings' => [],
        ]);

        $headers = ['X-Space-Id' => (string) $space->id];

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => [
                'title' => ['en' => 'Hello', 'de' => 'Hallo'],
                'slug' => 'hello',
            ],
        ], $headers);

        $res->assertCreated();
        $this->assertSame(['en' => 'Hello', 'de' => 'Hallo'], $res->json('data.data.title'));

        $entryId = $res->json('data.id');
        $get = $this->getJson("/api/v1/admin/posts/{$entryId}", $headers);
        $get->assertOk();
        $this->assertSame(['en' => 'Hello', 'de' => 'Hallo'], $get->json('data.data.title'));
    }

    public function test_entry_rejects_invalid_locale_key_for_localized_field(): void
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
        $admin->givePermissionTo('posts.create');
        Sanctum::actingAs($admin);

        Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => false, 'localized' => true],
                ['id' => 'f2', 'handle' => 'slug', 'label' => 'Slug', 'type' => 'text', 'required' => false],
            ],
            'settings' => [],
        ]);

        $headers = ['X-Space-Id' => (string) $space->id];

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => [
                'title' => ['en' => 'Hi', 'xy' => 'Invalid locale'],
                'slug' => 'hi',
            ],
        ], $headers);

        $res->assertStatus(422);
    }

    public function test_update_merges_localized_field_locales(): void
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
        $admin->givePermissionTo('posts.create');
        $admin->givePermissionTo('posts.read');
        $admin->givePermissionTo('posts.update');
        Sanctum::actingAs($admin);

        $collection = Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => false, 'localized' => true],
                ['id' => 'f2', 'handle' => 'slug', 'label' => 'Slug', 'type' => 'text', 'required' => false],
            ],
            'settings' => [],
        ]);

        $create = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => [
                'title' => ['en' => 'Hello', 'de' => 'Hallo'],
                'slug' => 'hello',
            ],
        ], ['X-Space-Id' => (string) $space->id]);

        $create->assertCreated();
        $entryId = $create->json('data.id');

        $update = $this->putJson("/api/v1/admin/posts/{$entryId}", [
            'status' => 'draft',
            'data' => [
                'title' => ['en' => 'Hello Updated'],
                'slug' => 'hello',
            ],
        ], ['X-Space-Id' => (string) $space->id]);

        $update->assertOk();
        $data = $update->json('data.data');
        $this->assertSame('Hello Updated', $data['title']['en']);
        $this->assertSame('Hallo', $data['title']['de']);
    }

    public function test_public_api_locale_parameter_projects_localized_field(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $space = Space::query()->create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        Sanctum::actingAs($admin);

        $headers = ['X-Space-Id' => (string) $space->id];

        Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'localized' => true],
                ['id' => 'f2', 'handle' => 'slug', 'label' => 'Slug', 'type' => 'text', 'required' => false],
            ],
            'settings' => [],
        ]);

        $this->postJson('/api/v1/admin/posts', [
            'status' => 'published',
            'data' => [
                'title' => ['en' => 'Hello', 'de' => 'Hallo'],
                'slug' => 'hello',
            ],
        ], $headers)->assertCreated();

        $resEn = $this->getJson('/api/content/posts?locale=en&limit=10', $headers);
        $resEn->assertOk();
        $items = $resEn->json('data.items');
        $this->assertCount(1, $items);
        $this->assertSame('Hello', $items[0]['data']['title'] ?? null);

        $resDe = $this->getJson('/api/content/posts?locale=de&limit=10', $headers);
        $resDe->assertOk();
        $itemsDe = $resDe->json('data.items');
        $this->assertCount(1, $itemsDe);
        $this->assertSame('Hallo', $itemsDe[0]['data']['title'] ?? null);
    }

    public function test_add_field_with_localized_via_api_persists_flag(): void
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
            'fields' => [],
        ], $headers);
        $create->assertCreated();
        $id = $create->json('data.id');

        $addField = $this->postJson("/api/v1/admin/collections/{$id}/fields", [
            'handle' => 'title',
            'label' => 'Title',
            'type' => 'text',
            'required' => true,
            'localized' => true,
        ], $headers);

        $addField->assertOk();
        $fields = $addField->json('data.fields');
        $titleField = collect($fields)->firstWhere('handle', 'title');
        $this->assertTrue($titleField['localized'] ?? false);
    }
}
