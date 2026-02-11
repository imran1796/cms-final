<?php

namespace Tests\Feature;

use Tests\TestCase;

final class CorsConfigTest extends TestCase
{
    public function test_cors_allowed_headers_include_x_space_id(): void
    {
        $allowed = config('cors.allowed_headers');
        $this->assertIsArray($allowed);
        $this->assertContains('X-Space-Id', $allowed);
    }

    public function test_cors_allowed_headers_include_x_space_handle(): void
    {
        $allowed = config('cors.allowed_headers');
        $this->assertIsArray($allowed);
        $this->assertContains('X-Space-Handle', $allowed);
    }
}
