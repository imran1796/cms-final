<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Space;
use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class SlugPasswordColorFieldTest extends TestCase
{
    use RefreshDatabase;

    private function createCollectionWithSlugPasswordColor(Space $space): void
    {
        Collection::query()->create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                ['id' => 'f2', 'handle' => 'slug', 'label' => 'Slug', 'type' => 'slug', 'required' => true],
                ['id' => 'f3', 'handle' => 'secret', 'label' => 'Secret', 'type' => 'password', 'required' => true],
                ['id' => 'f4', 'handle' => 'theme', 'label' => 'Theme', 'type' => 'color', 'required' => false],
            ],
            'settings' => [],
        ]);
    }

    public function test_entry_with_valid_slug_password_color_passes(): void
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

        $this->createCollectionWithSlugPasswordColor($space);

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => [
                'title' => 'My Post',
                'slug' => 'my-post',
                'secret' => 'password123',
                'theme' => '#ff0000',
            ],
        ], ['X-Space-Id' => (string) $space->id]);

        $res->assertStatus(201)
            ->assertJsonPath('data.data.slug', 'my-post')
            ->assertJsonPath('data.data.theme', '#ff0000');
        $this->assertArrayHasKey('secret', $res->json('data.data'));
    }

    public function test_entry_with_invalid_slug_fails(): void
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

        $this->createCollectionWithSlugPasswordColor($space);

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => [
                'title' => 'My Post',
                'slug' => 'Invalid Slug With Spaces',
                'secret' => 'password123',
                'theme' => '#00ff00',
            ],
        ], ['X-Space-Id' => (string) $space->id]);

        $res->assertStatus(422);
        $errors = $res->json('errors');
        $this->assertNotEmpty($errors);
        $this->assertTrue(
            isset($errors['data.slug']) || isset($errors['data.slug.0']),
            'Expected validation error for data.slug'
        );
    }

    public function test_entry_with_short_password_fails(): void
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

        $this->createCollectionWithSlugPasswordColor($space);

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => [
                'title' => 'My Post',
                'slug' => 'my-post',
                'secret' => 'short',
                'theme' => '#0000ff',
            ],
        ], ['X-Space-Id' => (string) $space->id]);

        $res->assertStatus(422);
        $errors = $res->json('errors');
        $this->assertNotEmpty($errors);
        $this->assertTrue(
            isset($errors['data.secret']) || isset($errors['data.secret.0']),
            'Expected validation error for data.secret (min 8)'
        );
    }

    public function test_entry_with_invalid_color_hex_fails(): void
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

        $this->createCollectionWithSlugPasswordColor($space);

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => [
                'title' => 'My Post',
                'slug' => 'my-post',
                'secret' => 'password123',
                'theme' => 'not-a-hex-color',
            ],
        ], ['X-Space-Id' => (string) $space->id]);

        $res->assertStatus(422);
        $errors = $res->json('errors');
        $this->assertNotEmpty($errors);
        $this->assertTrue(
            isset($errors['data.theme']) || isset($errors['data.theme.0']),
            'Expected validation error for data.theme'
        );
    }

    public function test_entry_with_color_from_palette_passes(): void
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
                ['id' => 'f2', 'handle' => 'accent', 'label' => 'Accent', 'type' => 'color', 'required' => false, 'options' => ['values' => ['primary', 'secondary', 'muted']]],
            ],
            'settings' => [],
        ]);

        $res = $this->postJson('/api/v1/admin/posts', [
            'status' => 'draft',
            'data' => [
                'title' => 'Post',
                'accent' => 'primary',
            ],
        ], ['X-Space-Id' => (string) $space->id]);

        $res->assertStatus(201)->assertJsonPath('data.data.accent', 'primary');
    }
}
