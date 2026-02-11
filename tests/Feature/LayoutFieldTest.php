<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Space;
use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class LayoutFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_layout_field_stores_blocks_schema(): void
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
            'handle' => 'pages',
            'type' => 'collection',
            'settings' => [],
        ], $headers);
        $create->assertStatus(201);
        $id = $create->json('data.id');

        $addField = $this->postJson("/api/v1/admin/collections/{$id}/fields", [
            'handle' => 'sections',
            'label' => 'Sections',
            'type' => 'layout',
            'required' => false,
            'blocks' => [
                [
                    'type' => 'hero',
                    'label' => 'Hero',
                    'fields' => [
                        ['handle' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => true],
                        ['handle' => 'subheading', 'label' => 'Subheading', 'type' => 'text', 'required' => false],
                    ],
                ],
                [
                    'type' => 'text_image',
                    'label' => 'Text + Image',
                    'fields' => [
                        ['handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                        ['handle' => 'body', 'label' => 'Body', 'type' => 'textarea', 'required' => false],
                    ],
                ],
            ],
        ], $headers);

        $addField->assertOk()->assertJsonPath('success', true);
        $fields = $addField->json('data.fields');
        $sectionsField = collect($fields)->firstWhere('handle', 'sections');
        $this->assertNotNull($sectionsField);
        $this->assertSame('layout', $sectionsField['type']);
        $this->assertArrayHasKey('blocks', $sectionsField);
        $this->assertCount(2, $sectionsField['blocks']);
        $hero = collect($sectionsField['blocks'])->firstWhere('type', 'hero');
        $this->assertNotNull($hero);
        $this->assertSame('Hero', $hero['label']);
        $this->assertCount(2, $hero['fields']);
    }

    public function test_entry_with_valid_layout_data_passes(): void
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
        $admin->givePermissionTo('pages.create');
        Sanctum::actingAs($admin);

        $coll = Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'pages',
            'type' => 'collection',
            'fields' => [
                [
                    'handle' => 'title',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'handle' => 'sections',
                    'type' => 'layout',
                    'required' => false,
                    'blocks' => [
                        [
                            'type' => 'hero',
                            'label' => 'Hero',
                            'fields' => [
                                ['handle' => 'heading', 'type' => 'text', 'required' => true],
                            ],
                        ],
                    ],
                ],
            ],
            'settings' => [],
        ]);

        $create = $this->postJson('/api/v1/admin/pages', [
            'data' => [
                'title' => 'Home',
                'sections' => [
                    [
                        'block_type' => 'hero',
                        'data' => ['heading' => 'Welcome'],
                    ],
                ],
            ],
            'status' => 'draft',
        ], $headers);

        $create->assertStatus(201);
        $this->assertSame('Welcome', $create->json('data.data.sections.0.data.heading'));
    }

    public function test_entry_with_invalid_layout_block_type_fails(): void
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
        $admin->givePermissionTo('pages.create');
        Sanctum::actingAs($admin);

        Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'pages',
            'type' => 'collection',
            'fields' => [
                ['handle' => 'title', 'type' => 'text', 'required' => true],
                [
                    'handle' => 'sections',
                    'type' => 'layout',
                    'blocks' => [
                        ['type' => 'hero', 'label' => 'Hero', 'fields' => [['handle' => 'heading', 'type' => 'text', 'required' => true]]],
                    ],
                ],
            ],
            'settings' => [],
        ]);

        $create = $this->postJson('/api/v1/admin/pages', [
            'data' => [
                'title' => 'Home',
                'sections' => [
                    [
                        'block_type' => 'unknown_block',
                        'data' => ['heading' => 'x'],
                    ],
                ],
            ],
            'status' => 'draft',
        ], $headers);

        $create->assertStatus(422);
    }

    public function test_entry_with_invalid_layout_nested_data_fails(): void
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
        $admin->givePermissionTo('pages.create');
        Sanctum::actingAs($admin);

        Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'pages',
            'type' => 'collection',
            'fields' => [
                ['handle' => 'title', 'type' => 'text', 'required' => true],
                [
                    'handle' => 'sections',
                    'type' => 'layout',
                    'blocks' => [
                        ['type' => 'hero', 'label' => 'Hero', 'fields' => [['handle' => 'heading', 'type' => 'text', 'required' => true]]],
                    ],
                ],
            ],
            'settings' => [],
        ]);

        $create = $this->postJson('/api/v1/admin/pages', [
            'data' => [
                'title' => 'Home',
                'sections' => [
                    [
                        'block_type' => 'hero',
                        'data' => [], // missing required 'heading'
                    ],
                ],
            ],
            'status' => 'draft',
        ], $headers);

        $create->assertStatus(422);
    }
}
