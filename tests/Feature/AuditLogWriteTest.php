<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\System\Audit\Services\Interfaces\AuditLogServiceInterface;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AuditLogWriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_can_be_written(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        $svc = app(AuditLogServiceInterface::class);

        $log = $svc->write(
            action: 'space.create',
            resource: 'spaces',
            diff: ['handle' => 'main-space']
        );

        $this->assertNotNull($log->id);
        $this->assertEquals('space.create', $log->action);
        $this->assertEquals('spaces', $log->resource);
        $this->assertEquals($user->id, $log->actor_id);
    }
}
