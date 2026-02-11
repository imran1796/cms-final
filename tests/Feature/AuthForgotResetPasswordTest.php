<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

final class AuthForgotResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_requires_email(): void
    {
        $res = $this->postJson('/api/v1/auth/forgot-password', []);
        $res->assertStatus(422);
    }

    public function test_forgot_password_accepts_valid_email(): void
    {
        User::factory()->create(['email' => 'user@test.com']);

        $res = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'user@test.com']);

        $res->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'user@test.com']);
    }

    public function test_forgot_password_returns_same_message_for_unknown_email(): void
    {
        $res = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'unknown@test.com']);

        $res->assertOk()->assertJsonPath('success', true);
    }

    public function test_reset_password_requires_valid_input(): void
    {
        $res = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'user@test.com',
            'token' => 'invalid',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);
        $res->assertStatus(422);
    }

    public function test_reset_password_succeeds_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'user@test.com', 'password' => Hash::make('oldpass')]);
        $token = Password::createToken($user);

        $res = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'user@test.com',
            'token' => $token,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $res->assertOk()->assertJsonPath('success', true)->assertJsonPath('message', 'OK');

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword', $user->password));
    }
}
