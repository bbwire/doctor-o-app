<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminConsultationsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_perform_crud_operations_for_consultations(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        Sanctum::actingAs($admin);

        $createResponse = $this->postJson('/api/v1/admin/consultations', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'scheduled_at' => now()->addDay()->toISOString(),
            'consultation_type' => 'video',
            'status' => 'scheduled',
            'reason' => 'Recurring headache',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.patient_id', $patient->id)
            ->assertJsonPath('data.doctor_id', $doctor->id);

        $consultationId = $createResponse->json('data.id');

        $this->getJson('/api/v1/admin/consultations')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->patchJson("/api/v1/admin/consultations/{$consultationId}", [
            'status' => 'completed',
            'notes' => 'Patient responded well.',
        ])->assertOk()->assertJsonPath('data.status', 'completed');

        $this->deleteJson("/api/v1/admin/consultations/{$consultationId}")
            ->assertOk()
            ->assertJsonPath('message', 'Consultation deleted successfully');
    }

    public function test_consultation_requires_patient_and_doctor_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $notPatient = User::factory()->doctor()->create();
        $notDoctor = User::factory()->patient()->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/consultations', [
            'patient_id' => $notPatient->id,
            'doctor_id' => $notDoctor->id,
            'scheduled_at' => now()->addDay()->toISOString(),
            'consultation_type' => 'video',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['patient_id', 'doctor_id']);
    }

    public function test_non_admin_cannot_access_consultation_endpoints(): void
    {
        $user = User::factory()->patient()->create();
        $consultation = Consultation::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/admin/consultations')->assertForbidden();
        $this->postJson('/api/v1/admin/consultations', [])->assertForbidden();
        $this->patchJson("/api/v1/admin/consultations/{$consultation->id}", [])->assertForbidden();
        $this->deleteJson("/api/v1/admin/consultations/{$consultation->id}")->assertForbidden();
    }
}
