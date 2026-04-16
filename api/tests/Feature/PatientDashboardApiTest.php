<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_get_dashboard_summary(): void
    {
        $patient = User::factory()->patient()->create();
        $otherPatient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        $nextConsultation = Consultation::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        Consultation::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'completed',
            'scheduled_at' => now()->subDays(2),
        ]);

        Consultation::factory()->create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => $doctor->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addHours(4),
        ]);

        Prescription::factory()->create([
            'consultation_id' => $nextConsultation->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        Sanctum::actingAs($patient);

        $response = $this->getJson('/api/v1/dashboard/summary');

        $response
            ->assertOk()
            ->assertJsonPath('data.upcoming_consultations', 1)
            ->assertJsonPath('data.prescriptions', 1)
            ->assertJsonPath('data.completed_consultations', 1)
            ->assertJsonPath('data.next_consultation.id', $nextConsultation->id)
            ->assertJsonPath('data.next_consultation.doctor.id', $doctor->id);
    }

    public function test_dashboard_prescription_count_excludes_received(): void
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

        $this->getJson('/api/v1/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('data.prescriptions', 1);
    }

    public function test_non_patient_cannot_access_dashboard_summary(): void
    {
        $doctor = User::factory()->doctor()->create();

        Sanctum::actingAs($doctor);

        $this->getJson('/api/v1/dashboard/summary')->assertForbidden();
    }
}
