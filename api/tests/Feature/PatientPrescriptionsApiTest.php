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

        Sanctum::actingAs($doctor);

        $this->getJson('/api/v1/prescriptions')->assertForbidden();
    }
}
