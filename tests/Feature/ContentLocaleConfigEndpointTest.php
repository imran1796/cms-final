<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ContentLocaleConfigEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_locale_config_requires_authentication(): void
    {
        $res = $this->getJson('/api/v1/admin/content/locales');
        $res->assertStatus(401);
    }

    public function test_content_locale_config_returns_fallback_config_when_db_values_missing(): void
    {
        config()->set('content.supported_locales', ['it']);
        config()->set('content.default_locale', 'it');

        $space = Space::query()->create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);

        Sanctum::actingAs(User::factory()->create());

        $res = $this->getJson('/api/v1/admin/content/locales', [
            'X-Space-Id' => (string) $space->id,
        ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Content locale config')
            ->assertJsonPath('data.supported_locales.0', 'it')
            ->assertJsonPath('data.default_locale', 'it');
    }

    public function test_content_locale_config_returns_normalized_db_values(): void
    {
        config()->set('content.supported_locales', ['en', 'de']);
        config()->set('content.default_locale', 'en');

        Setting::query()->updateOrCreate(
            ['key' => 'content_supported_locales'],
            ['value' => json_encode(['de', 'de', 'en', ''])]
        );
        Setting::query()->updateOrCreate(
            ['key' => 'content_default_locale'],
            ['value' => 'fr']
        );

        $space = Space::query()->create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);

        Sanctum::actingAs(User::factory()->create());

        $res = $this->getJson('/api/v1/admin/content/locales', [
            'X-Space-Id' => (string) $space->id,
        ]);

        $res->assertOk()
            ->assertJsonPath('data.default_locale', 'fr')
            ->assertJsonPath('data.supported_locales.0', 'fr')
            ->assertJsonPath('data.supported_locales.1', 'de')
            ->assertJsonPath('data.supported_locales.2', 'en');
    }
}
