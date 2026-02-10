<?php

namespace Tests\Feature;

use App\Models\HealthcareProfessional;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminHealthcareProfessionalsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_perform_crud_operations_for_healthcare_professionals(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = User::factory()->doctor()->create();
        $institution = Institution::factory()->create();

        Sanctum::actingAs($admin);

        $createResponse = $this->postJson('/api/v1/admin/healthcare-professionals', [
            'user_id' => $doctor->id,
            'institution_id' => $institution->id,
            'speciality' => 'Cardiology',
            'license_number' => 'LIC-10001',
            'bio' => 'Experienced specialist',
            'qualifications' => ['MBBS', 'MD'],
            'is_active' => true,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.user_id', $doctor->id)
            ->assertJsonPath('data.speciality', 'Cardiology');

        $healthcareProfessionalId = $createResponse->json('data.id');

        $this->getJson('/api/v1/admin/healthcare-professionals')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->patchJson("/api/v1/admin/healthcare-professionals/{$healthcareProfessionalId}", [
            'speciality' => 'Neurology',
        ])->assertOk()->assertJsonPath('data.speciality', 'Neurology');

        $this->deleteJson("/api/v1/admin/healthcare-professionals/{$healthcareProfessionalId}")
            ->assertOk()
            ->assertJsonPath('message', 'Healthcare professional deleted successfully');
    }

    public function test_store_healthcare_professional_requires_doctor_role_user(): void
    {
        $admin = User::factory()->admin()->create();
        $patientUser = User::factory()->patient()->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/healthcare-professionals', [
            'user_id' => $patientUser->id,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_non_admin_cannot_access_healthcare_professional_endpoints(): void
    {
        $user = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $institution = Institution::factory()->create();
        $healthcareProfessional = HealthcareProfessional::factory()->create([
            'user_id' => $doctor->id,
            'institution_id' => $institution->id,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/admin/healthcare-professionals')->assertForbidden();
        $this->postJson('/api/v1/admin/healthcare-professionals', [
            'user_id' => $doctor->id,
        ])->assertForbidden();
        $this->patchJson("/api/v1/admin/healthcare-professionals/{$healthcareProfessional->id}", [
            'speciality' => 'Updated',
        ])->assertForbidden();
        $this->deleteJson("/api/v1/admin/healthcare-professionals/{$healthcareProfessional->id}")->assertForbidden();
    }
}
