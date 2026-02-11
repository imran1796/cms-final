<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class UsersAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_list_users_requires_auth(): void
    {
        $res = $this->getJson('/api/v1/admin/users');
        $res->assertStatus(401);
    }

    public function test_list_users_requires_manage_users_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor'); // no manage_users
        Sanctum::actingAs($user);

        $res = $this->getJson('/api/v1/admin/users');
        $res->assertStatus(403);
    }

    public function test_list_users_returns_paginated_users(): void
    {
        $admin = User::factory()->create(['name' => 'Admin User', 'email' => 'admin@test.com']);
        $admin->assignRole('Admin');
        User::factory()->create(['name' => 'Other', 'email' => 'other@test.com']);
        Sanctum::actingAs($admin);

        $res = $this->getJson('/api/v1/admin/users?per_page=5');

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Users')
            ->assertJsonStructure(['data' => ['data', 'current_page', 'per_page', 'total']]);
        $this->assertGreaterThanOrEqual(2, count($res->json('data.data')));
    }

    public function test_create_user_succeeds_with_roles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        $res = $this->postJson('/api/v1/admin/users', [
            'name'     => 'New User',
            'email'    => 'newuser@test.com',
            'password' => 'password123',
            'roles'    => ['Editor', 'Author'],
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'New User')
            ->assertJsonPath('data.email', 'newuser@test.com')
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'roles', 'permissions', 'created_at', 'updated_at']]);
        $roles = $res->json('data.roles');
        $this->assertIsArray($roles);
        $this->assertContains('Editor', $roles);
        $this->assertContains('Author', $roles);

        $this->assertDatabaseHas('users', ['email' => 'newuser@test.com', 'name' => 'New User']);
    }

    public function test_create_user_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@test.com']);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        $res = $this->postJson('/api/v1/admin/users', [
            'name'     => 'Duplicate',
            'email'    => 'taken@test.com',
            'password' => 'password123',
        ]);

        $res->assertStatus(422);
        $this->assertDatabaseMissing('users', ['name' => 'Duplicate']);
    }

    public function test_show_user_returns_user_payload(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $target = User::factory()->create(['name' => 'Target', 'email' => 'target@test.com']);
        $target->assignRole('Viewer');
        Sanctum::actingAs($admin);

        $res = $this->getJson('/api/v1/admin/users/' . $target->id);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $target->id)
            ->assertJsonPath('data.name', 'Target')
            ->assertJsonPath('data.email', 'target@test.com')
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'roles', 'permissions', 'created_at', 'updated_at']]);
    }

    public function test_show_user_returns_404_when_not_found(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        $res = $this->getJson('/api/v1/admin/users/99999');
        $res->assertStatus(404);
    }

    public function test_update_user_succeeds_with_roles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $target = User::factory()->create(['name' => 'Old', 'email' => 'old@test.com']);
        $target->assignRole('Viewer');
        Sanctum::actingAs($admin);

        $res = $this->putJson('/api/v1/admin/users/' . $target->id, [
            'name'  => 'Updated Name',
            'email' => 'updated@test.com',
            'roles' => ['Admin'],
        ]);

        $res->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.email', 'updated@test.com');
        $target->refresh();
        $this->assertSame('Updated Name', $target->name);
        $this->assertSame('updated@test.com', $target->email);
        $this->assertTrue($target->hasRole('Admin'));
    }

    public function test_destroy_user_succeeds(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $target = User::factory()->create(['email' => 'delete@test.com']);
        $id = $target->id;
        Sanctum::actingAs($admin);

        $res = $this->deleteJson('/api/v1/admin/users/' . $id);

        $res->assertOk()->assertJsonPath('message', 'User deleted');
        $target->refresh();
        $this->assertSoftDeleted($target);
    }

    public function test_list_roles_returns_role_names(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        $res = $this->getJson('/api/v1/admin/users/roles');

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['roles']]);
        $roles = $res->json('data.roles');
        $this->assertIsArray($roles);
        $this->assertContains('Admin', $roles);
        $this->assertContains('Editor', $roles);
    }
}
