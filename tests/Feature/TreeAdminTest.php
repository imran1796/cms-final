<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class TreeAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_tree_endpoints_work(): void
    {
        $this->seed();

        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
        ]);

        $user->assignRole('Super Admin');
        
        Sanctum::actingAs($user);

        $spaceRes = $this->postJson('/api/v1/spaces', [
            'handle' => 'main',
            'name' => 'Main Space',
            'settings' => [],
            'storage_prefix' => 'main',
        ], [
            'X-Space-Id' => '1',
        ]);

        $spaceRes->assertCreated();

        $spaceId = (int) ($spaceRes->json('data.id') ?? 1);

        $colRes = $this->postJson('/api/v1/admin/collections', [
            'handle' => 'pages',
            'type' => 'tree',
            'fields' => [
                ['id' => 'title', 'type' => 'text', 'required' => true],
            ],
            'settings' => [],
        ], [
            'X-Space-Id' => (string) $spaceId,
        ]);
        $colRes->assertCreated();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $superAdminRole = Role::findByName('Super Admin', 'web');
        if ($superAdminRole) {
            $superAdminRole->syncPermissions(Permission::where('guard_name', 'web')->get());
        }

        $pagesPerms = Permission::where('name', 'like', 'pages.%')->where('guard_name', 'web')->get();
        $user->givePermissionTo($pagesPerms);

        $user->refresh();
        $user->load('roles.permissions', 'permissions');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Sanctum::actingAs($user);

        $e1 = $this->postJson('/api/v1/admin/pages', [
            'data' => ['title' => 'Root A'],
        ], [
            'X-Space-Id' => (string) $spaceId,
        ])->assertCreated()->json('data.id');

        $e2 = $this->postJson('/api/v1/admin/pages', [
            'data' => ['title' => 'Root B'],
        ], [
            'X-Space-Id' => (string) $spaceId,
        ])->assertCreated()->json('data.id');

        $treeRes = $this->getJson('/api/v1/admin/pages/tree', [
            'X-Space-Id' => (string) $spaceId,
        ]);

        $treeRes->assertOk()->assertJson(['success' => true]);

        $moveRes = $this->postJson("/api/v1/admin/pages/{$e2}/move", [
            'parent_id' => $e1,
            'position' => 0,
        ], [
            'X-Space-Id' => (string) $spaceId,
        ]);

        $moveRes->assertOk();

        $reorderRes = $this->postJson('/api/v1/admin/pages/reorder', [
            'parent_id' => null,
            'order' => [$e1],
        ], [
            'X-Space-Id' => (string) $spaceId,
        ]);

        $reorderRes->assertOk();
    }
}
