<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

final class FormsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_and_submit_and_list_submissions_and_permissions(): void
    {
        $this->seed(\Database\Seeders\System\RolesAndPermissionsSeeder::class);

        $space = \App\Models\Space::create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        Sanctum::actingAs($admin);

        $headers = ['X-Space-Id' => (string)$space->id];

        $create = $this->postJson('/api/v1/admin/forms', [
            'handle' => 'contact',
            'title' => 'Contact Form',
            'fields' => [
                ['name' => 'email', 'type' => 'email', 'required' => true],
                ['name' => 'message', 'type' => 'textarea', 'required' => true, 'rules' => ['max:2000']],
            ],
            'settings' => [
                'captcha' => ['enabled' => false],
            ],
        ], $headers);

        $create->assertStatus(201)->assertJsonPath('success', true);
        $formId = (int)$create->json('data.id');

        auth()->forgetGuards();

        $submit = $this->postJson('/api/forms/contact/submit', [
            'data' => [
                'email' => 'a@b.com',
                'message' => 'Hello',
            ],
        ], $headers);

        $submit->assertStatus(201)->assertJsonPath('success', true);

        $bad = $this->postJson('/api/forms/contact/submit', [
            'data' => [
                'email' => 'a@b.com',
            ],
        ], $headers);

        $bad->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');

        Sanctum::actingAs($admin);

        $list = $this->getJson("/api/v1/admin/forms/{$formId}/submissions", $headers);
        $list->assertOk()->assertJsonPath('success', true);

        $subs = $list->json('data');
        $this->assertIsArray($subs);
        $this->assertCount(1, $subs);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $deny = $this->getJson("/api/v1/admin/forms/{$formId}/submissions", $headers);
        $deny->assertStatus(403)->assertJsonPath('code', 'FORBIDDEN');
    }
}
