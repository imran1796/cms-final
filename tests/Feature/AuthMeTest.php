<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AuthMeTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_requires_auth(): void
    {
        $res = $this->getJson('/api/v1/auth/me');
        $res->assertStatus(401);
    }

    public function test_roles_and_permissions_seeder_creates_roles(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertNotNull(Role::findByName('Super Admin'));
        $this->assertNotNull(Role::findByName('Admin'));
        $this->assertNotNull(Role::findByName('Editor'));
        $this->assertNotNull(Role::findByName('Author'));
        $this->assertNotNull(Role::findByName('Viewer'));
    }

    public function test_me_returns_user_when_authenticated(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Admin');

        Sanctum::actingAs($user);

        $res = $this->getJson('/api/v1/auth/me');

        $res->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'roles',
                    'permissions',
                ],
            ]);
    }
}
