<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_429_when_rate_limited(): void
    {
        User::factory()->create([
            'email' => 'rate@test.com',
            'password' => Hash::make('secret123'),
        ]);

        $payload = ['email' => 'rate@test.com', 'password' => 'wrong'];
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', $payload);
        }
        $res = $this->postJson('/api/v1/auth/login', $payload);
        $res->assertStatus(429);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        User::factory()->create([
            'email' => 'a@b.com',
            'password' => Hash::make('secret123'),
        ]);

        $res = $this->postJson('/api/v1/auth/login', [
            'email' => 'a@b.com',
            'password' => 'wrongpass',
        ]);

        $res->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'FORBIDDEN',
            ]);
    }

    public function test_login_success(): void
    {
        User::factory()->create([
            'email' => 'a@b.com',
            'password' => Hash::make('secret123'),
        ]);

        $res = $this->postJson('/api/v1/auth/login', [
            'email' => 'a@b.com',
            'password' => 'secret123',
        ]);

        $res->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ],
                'meta',
            ]);
    }
}
