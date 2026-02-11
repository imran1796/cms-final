<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AuthAccountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_me_requires_auth(): void
    {
        $res = $this->patchJson('/api/v1/auth/me', ['name' => 'New Name']);
        $res->assertStatus(401);
    }

    public function test_update_me_updates_name_and_returns_profile(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'user@test.com']);
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        $res = $this->patchJson('/api/v1/auth/me', ['name' => 'New Name']);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Profile updated')
            ->assertJsonPath('data.user.name', 'New Name')
            ->assertJsonPath('data.user.email', 'user@test.com')
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email'], 'roles', 'permissions']]);

        $user->refresh();
        $this->assertSame('New Name', $user->name);
    }

    public function test_update_me_updates_email_with_unique_rule(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create(['email' => 'old@test.com']);
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        $res = $this->patchJson('/api/v1/auth/me', ['email' => 'new@test.com']);

        $res->assertOk()->assertJsonPath('data.user.email', 'new@test.com');
        $user->refresh();
        $this->assertSame('new@test.com', $user->email);
    }

    public function test_update_me_rejects_duplicate_email(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        User::factory()->create(['email' => 'taken@test.com']);
        $user = User::factory()->create(['email' => 'me@test.com']);
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        $res = $this->patchJson('/api/v1/auth/me', ['email' => 'taken@test.com']);

        $res->assertStatus(422);
        $user->refresh();
        $this->assertSame('me@test.com', $user->email);
    }

    public function test_change_password_requires_auth(): void
    {
        $res = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'old',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);
        $res->assertStatus(401);
    }

    public function test_change_password_fails_with_wrong_current_password(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create(['password' => Hash::make('correct')]);
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        $res = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'wrong',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $res->assertStatus(403)->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_change_password_succeeds_and_updates(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create(['password' => Hash::make('oldpass')]);
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        $res = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'oldpass',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $res->assertOk()->assertJsonPath('message', 'Password changed');

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword', $user->password));
    }

    public function test_change_password_requires_confirmation(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create(['password' => Hash::make('oldpass')]);
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        $res = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'oldpass',
            'password' => 'newpassword',
            'password_confirmation' => 'mismatch',
        ]);

        $res->assertStatus(422);
    }
}
