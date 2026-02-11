<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class SettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_list_settings_requires_auth(): void
    {
        $res = $this->getJson('/api/v1/admin/settings');
        $res->assertStatus(401);
    }

    public function test_list_settings_requires_manage_settings_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor'); // no manage_settings
        Sanctum::actingAs($user);

        $res = $this->getJson('/api/v1/admin/settings');
        $res->assertStatus(403);
    }

    public function test_list_settings_returns_object(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        $res = $this->getJson('/api/v1/admin/settings');

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Settings')
            ->assertJsonStructure(['data' => []]);
        $this->assertIsArray($res->json('data'));
    }

    public function test_update_settings_requires_auth(): void
    {
        $res = $this->putJson('/api/v1/admin/settings', ['site_name' => 'My Site']);
        $res->assertStatus(401);
    }

    public function test_update_settings_requires_manage_settings_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');
        Sanctum::actingAs($user);

        $res = $this->putJson('/api/v1/admin/settings', ['site_name' => 'My Site']);
        $res->assertStatus(403);
    }

    public function test_update_settings_stores_and_returns_settings(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        $res = $this->putJson('/api/v1/admin/settings', [
            'site_name' => 'My CMS',
            'site_tagline' => 'Headless',
        ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Settings updated')
            ->assertJsonPath('data.site_name', 'My CMS')
            ->assertJsonPath('data.site_tagline', 'Headless');

        $res2 = $this->getJson('/api/v1/admin/settings');
        $res2->assertOk()->assertJsonPath('data.site_name', 'My CMS');
    }
}
