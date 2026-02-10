<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminUsersApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/users');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }

    public function test_non_admin_cannot_list_users(): void
    {
        $user = User::factory()->patient()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/admin/users');

        $response
            ->assertForbidden()
            ->assertJsonPath('message', 'Forbidden');
    }

    public function test_admin_can_update_user(): void
    {
        $admin = User::factory()->admin()->create();
        $targetUser = User::factory()->patient()->create(['name' => 'Old Name']);

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/v1/admin/users/{$targetUser->id}", [
            'name' => 'Updated Name',
            'role' => 'doctor',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.role', 'doctor');

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Updated Name',
            'role' => 'doctor',
        ]);
    }
}
