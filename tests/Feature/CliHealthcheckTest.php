<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CliHealthcheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_healthcheck_command_exits_zero(): void
    {
        $this->artisan('cms:healthcheck --json')
            ->assertExitCode(0);
    }
}
