<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientPrescriptionsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_only_see_own_prescriptions(): void
    {
        $patient = User::factory()->patient()->create();
        $otherPatient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        $consultationA = Consultation::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        $consultationB = Consultation::factory()->create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => $doctor->id,
        ]);

        Prescription::factory()->create([
            'consultation_id' => $consultationA->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        Prescription::factory()->create([
            'consultation_id' => $consultationB->id,
            'patient_id' => $otherPatient->id,
            'doctor_id' => $doctor->id,
        ]);

        Sanctum::actingAs($patient);

        $response = $this->getJson('/api/v1/prescriptions');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.patient_id', $patient->id);
    }

    public function test_non_patient_cannot_access_patient_prescription_endpoints(): void
    {
        $doctor = User::factory()->doctor()->create();
        $patient = User::factory()->patient()->create();
        $consultation = Consultation::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);
        $rx = Prescription::factory()->create([
            'consultation_id' => $consultation->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        Sanctum::actingAs($doctor);

        $this->getJson('/api/v1/prescriptions')->assertForbidden();
        $this->get("/api/v1/prescriptions/{$rx->id}/download")->assertForbidden();
        $this->postJson("/api/v1/prescriptions/{$rx->id}/acknowledge-receipt")->assertForbidden();
    }

    public function test_patient_prescription_list_hides_received_prescriptions(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $consultation = Consultation::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        Prescription::factory()->create([
            'consultation_id' => $consultation->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'patient_received_at' => now(),
        ]);

        Prescription::factory()->create([
            'consultation_id' => $consultation->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'patient_received_at' => null,
        ]);

        Sanctum::actingAs($patient);

        $this->getJson('/api/v1/prescriptions')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_patient_can_acknowledge_prescription_receipt(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $consultation = Consultation::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        $rx = Prescription::factory()->create([
            'consultation_id' => $consultation->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'patient_received_at' => null,
        ]);

        Sanctum::actingAs($patient);

        $response = $this->postJson("/api/v1/prescriptions/{$rx->id}/acknowledge-receipt");

        $response->assertOk();
        $this->assertNotNull($response->json('data.patient_received_at'));

        $this->assertNotNull($rx->refresh()->patient_received_at);
    }

    public function test_patient_can_download_own_prescription_pdf(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $consultation = Consultation::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        $rx = Prescription::factory()->create([
            'consultation_id' => $consultation->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        Sanctum::actingAs($patient);

        $response = $this->get("/api/v1/prescriptions/{$rx->id}/download");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_patient_cannot_acknowledge_other_patients_prescription(): void
    {
        $patient = User::factory()->patient()->create();
        $other = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $consultation = Consultation::factory()->create([
            'patient_id' => $other->id,
            'doctor_id' => $doctor->id,
        ]);

        $rx = Prescription::factory()->create([
            'consultation_id' => $consultation->id,
            'patient_id' => $other->id,
            'doctor_id' => $doctor->id,
        ]);

        Sanctum::actingAs($patient);

        $this->postJson("/api/v1/prescriptions/{$rx->id}/acknowledge-receipt")
            ->assertForbidden();
    }
}
