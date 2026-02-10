<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminInstitutionsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_perform_crud_operations_for_institutions(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $createResponse = $this->postJson('/api/v1/admin/institutions', [
            'name' => 'Central Hospital',
            'type' => 'hospital',
            'address' => '123 Main Street',
            'phone' => '+123456789',
            'email' => 'central@example.com',
            'is_active' => true,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.name', 'Central Hospital');

        $institutionId = $createResponse->json('data.id');

        $this->getJson('/api/v1/admin/institutions')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->patchJson("/api/v1/admin/institutions/{$institutionId}", [
            'name' => 'Central Medical Center',
        ])->assertOk()->assertJsonPath('data.name', 'Central Medical Center');

        $this->deleteJson("/api/v1/admin/institutions/{$institutionId}")
            ->assertOk()
            ->assertJsonPath('message', 'Institution deleted successfully');
    }

    public function test_non_admin_cannot_access_institutions_endpoints(): void
    {
        $user = User::factory()->patient()->create();
        $institution = Institution::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/admin/institutions')->assertForbidden();
        $this->postJson('/api/v1/admin/institutions', [
            'name' => 'Test',
            'type' => 'clinic',
        ])->assertForbidden();
        $this->patchJson("/api/v1/admin/institutions/{$institution->id}", [
            'name' => 'Updated',
        ])->assertForbidden();
        $this->deleteJson("/api/v1/admin/institutions/{$institution->id}")->assertForbidden();
    }
}
