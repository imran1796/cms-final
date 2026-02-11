<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Space;
use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class RepeaterFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_repeater_field_stores_nested_fields(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $space = Space::query()->create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);
        $headers = ['X-Space-Id' => (string) $space->id];

        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        $create = $this->postJson('/api/v1/admin/collections', [
            'handle' => 'posts',
            'type' => 'collection',
            'settings' => [],
        ], $headers);
        $create->assertStatus(201);
        $id = $create->json('data.id');

        $addField = $this->postJson("/api/v1/admin/collections/{$id}/fields", [
            'handle' => 'blocks',
            'label' => 'Blocks',
            'type' => 'repeater',
            'required' => false,
            'fields' => [
                ['handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                ['handle' => 'count', 'label' => 'Count', 'type' => 'number', 'required' => false],
            ],
        ], $headers);

        $addField->assertOk()->assertJsonPath('success', true);
        $fields = $addField->json('data.fields');
        $blocksField = collect($fields)->firstWhere('handle', 'blocks');
        $this->assertNotNull($blocksField);
        $this->assertSame('repeater', $blocksField['type']);
        $this->assertArrayHasKey('fields', $blocksField);
        $this->assertCount(2, $blocksField['fields']);
        $nested = collect($blocksField['fields']);
        $this->assertNotNull($nested->firstWhere('handle', 'title'));
        $this->assertNotNull($nested->firstWhere('handle', 'count'));
    }

    public function test_entry_with_valid_repeater_data_passes(): void
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
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                [
                    'id' => 'f2',
                    'handle' => 'blocks',
                    'label' => 'Blocks',
                    'type' => 'repeater',
                    'required' => false,
                    'fields' => [
                        ['id' => 'b1', 'handle' => 'label', 'type' => 'text', 'required' => true],
                        ['id' => 'b2', 'handle' => 'value', 'type' => 'number', 'required' => false],
                    ],
                ],
            ],
            'settings' => [],
        ]);

        $headers = ['X-Space-Id' => (string) $space->id];

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => [
                'title' => 'Post',
                'blocks' => [
                    ['label' => 'A', 'value' => 1],
                    ['label' => 'B', 'value' => 2],
                ],
            ],
        ], $headers);

        $res->assertStatus(201)->assertJsonPath('data.data.blocks.0.label', 'A')->assertJsonPath('data.data.blocks.1.value', 2);
    }

    public function test_entry_with_invalid_nested_repeater_value_fails(): void
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
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                [
                    'id' => 'f2',
                    'handle' => 'blocks',
                    'label' => 'Blocks',
                    'type' => 'repeater',
                    'required' => false,
                    'fields' => [
                        ['id' => 'b1', 'handle' => 'label', 'type' => 'text', 'required' => true],
                        ['id' => 'b2', 'handle' => 'value', 'type' => 'number', 'required' => false],
                    ],
                ],
            ],
            'settings' => [],
        ]);

        $headers = ['X-Space-Id' => (string) $space->id];

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => [
                'title' => 'Post',
                'blocks' => [
                    ['label' => 'A', 'value' => 'not-a-number'],
                ],
            ],
        ], $headers);

        $res->assertStatus(422);
        $this->assertArrayHasKey('errors', $res->json());
    }
}
