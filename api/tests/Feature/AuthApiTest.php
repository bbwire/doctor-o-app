<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PatientNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_payload(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'patient',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'role', 'patient_number'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'role' => 'patient',
        ]);

        $pn = $response->json('user.patient_number');
        $this->assertIsString($pn);
        $this->assertMatchesRegularExpression('/^DRO-\d{2}-\d{5}-\d$/', $pn);
        $this->assertSame(1, preg_match('/^DRO-(\d{2})-(\d{5})-(\d)$/', $pn, $m));
        $gen = app(PatientNumberGenerator::class);
        $this->assertTrue($gen->luhnIsValid($m[1].$m[2].$m[3]));
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        User::factory()->patient()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'role'],
                'token',
            ]);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->patient()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/user');

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', 'patient')
            ->assertJsonPath('data.patient_number', $user->fresh()->patient_number);
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::factory()->patient()->create([
            'name' => 'Old Name',
            'phone' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/user', [
            'name' => 'New Name',
            'phone' => '+123456789',
            'preferred_language' => 'Luganda',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.phone', '+123456789')
            ->assertJsonPath('data.preferred_language', 'Luganda');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'phone' => '+123456789',
            'preferred_language' => 'Luganda',
        ]);
    }

    public function test_authenticated_user_can_upload_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->patient()->create();
        Sanctum::actingAs($user);

        $photo = UploadedFile::fake()->createWithContent(
            'avatar.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/w8AAusB9Yf5PkwAAAAASUVORK5CYII=')
        );

        $response = $this->post('/api/v1/user', [
            '_method' => 'PATCH',
            'profile_photo' => $photo,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();

        $updatedUser = $user->fresh();
        $this->assertNotNull($updatedUser->profile_photo_path);
        Storage::disk('public')->assertExists($updatedUser->profile_photo_path);
    }
}
