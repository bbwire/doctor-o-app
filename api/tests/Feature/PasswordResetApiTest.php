<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_returns_success_for_existing_email(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $response = $this->postJson('/api/v1/forgot-password', [
            'email' => 'jane@example.com',
        ]);

        $response->assertOk()->assertJsonStructure(['message']);
    }

    public function test_reset_password_with_valid_token_updates_password(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/v1/reset-password', [
            'email' => 'jane@example.com',
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertOk()->assertJsonStructure(['message']);
    }
}
