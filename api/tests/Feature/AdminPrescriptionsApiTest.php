<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPrescriptionsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_perform_crud_operations_for_prescriptions(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $consultation = Consultation::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        Sanctum::actingAs($admin);

        $createResponse = $this->postJson('/api/v1/admin/prescriptions', [
            'consultation_id' => $consultation->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'medications' => [
                [
                    'name' => 'Paracetamol',
                    'dosage' => '500mg',
                    'frequency' => 'Twice daily',
                    'duration' => '5 days',
                ],
            ],
            'instructions' => 'Take after meals',
            'issued_at' => now()->toISOString(),
            'status' => 'active',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.patient_id', $patient->id)
            ->assertJsonPath('data.doctor_id', $doctor->id);

        $prescriptionId = $createResponse->json('data.id');

        $this->getJson('/api/v1/admin/prescriptions')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->patchJson("/api/v1/admin/prescriptions/{$prescriptionId}", [
            'status' => 'completed',
        ])->assertOk()->assertJsonPath('data.status', 'completed');

        $this->deleteJson("/api/v1/admin/prescriptions/{$prescriptionId}")
            ->assertOk()
            ->assertJsonPath('message', 'Prescription deleted successfully');
    }

    public function test_prescription_requires_doctor_and_patient_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $consultation = Consultation::factory()->create();
        $notDoctor = User::factory()->patient()->create();
        $notPatient = User::factory()->doctor()->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/prescriptions', [
            'consultation_id' => $consultation->id,
            'doctor_id' => $notDoctor->id,
            'patient_id' => $notPatient->id,
            'medications' => [['name' => 'Paracetamol']],
            'issued_at' => now()->toISOString(),
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['doctor_id', 'patient_id']);
    }

    public function test_prescription_requires_consultation_participant_consistency(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $otherDoctor = User::factory()->doctor()->create();
        $consultation = Consultation::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/prescriptions', [
            'consultation_id' => $consultation->id,
            'doctor_id' => $otherDoctor->id,
            'patient_id' => $patient->id,
            'medications' => [['name' => 'Paracetamol']],
            'issued_at' => now()->toISOString(),
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['doctor_id']);
    }

    public function test_non_admin_cannot_access_prescription_endpoints(): void
    {
        $user = User::factory()->patient()->create();
        $prescription = Prescription::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/admin/prescriptions')->assertForbidden();
        $this->postJson('/api/v1/admin/prescriptions', [])->assertForbidden();
        $this->patchJson("/api/v1/admin/prescriptions/{$prescription->id}", [])->assertForbidden();
        $this->deleteJson("/api/v1/admin/prescriptions/{$prescription->id}")->assertForbidden();
    }
}
