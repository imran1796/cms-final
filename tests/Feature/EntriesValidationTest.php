<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Space;
use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class EntriesValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_required_field_fails(): void
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
            ],
            'settings' => [],
        ]);

        $headers = ['X-Space-Id' => (string) $space->id];

        $res = $this->postJson('/api/v1/admin/posts', [
            'data' => ['slug' => 'no-title'],
        ], $headers);

        $res->assertStatus(422);
    }
}
