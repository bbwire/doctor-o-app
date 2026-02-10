<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientConsultationsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_book_consultation_for_self(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        Sanctum::actingAs($patient);

        $response = $this->postJson('/api/v1/consultations/book', [
            'doctor_id' => $doctor->id,
            'scheduled_at' => now()->addDay()->toISOString(),
            'consultation_type' => 'video',
            'reason' => 'Follow-up checkup',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.patient_id', $patient->id)
            ->assertJsonPath('data.doctor_id', $doctor->id)
            ->assertJsonPath('data.status', 'scheduled');
    }

    public function test_patient_cannot_book_conflicting_scheduled_slot_for_same_doctor(): void
    {
        $patient = User::factory()->patient()->create();
        $otherPatient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $scheduledAt = now()->addDay()->startOfHour();

        Consultation::factory()->create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => $doctor->id,
            'scheduled_at' => $scheduledAt,
            'status' => 'scheduled',
        ]);

        Sanctum::actingAs($patient);

        $this->postJson('/api/v1/consultations/book', [
            'doctor_id' => $doctor->id,
            'scheduled_at' => $scheduledAt->toISOString(),
            'consultation_type' => 'video',
            'reason' => 'Follow-up checkup',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['scheduled_at']);
    }

    public function test_patient_can_only_see_own_consultations(): void
    {
        $patient = User::factory()->patient()->create();
        $otherPatient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        Consultation::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        Consultation::factory()->create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => $doctor->id,
        ]);

        Sanctum::actingAs($patient);

        $response = $this->getJson('/api/v1/consultations');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.patient_id', $patient->id);
    }

    public function test_patient_can_view_own_consultation_detail(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        $consultation = Consultation::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'scheduled',
        ]);

        Sanctum::actingAs($patient);

        $response = $this->getJson("/api/v1/consultations/{$consultation->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $consultation->id)
            ->assertJsonPath('data.patient_id', $patient->id);
    }

    public function test_patient_can_cancel_scheduled_consultation(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        $consultation = Consultation::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        Sanctum::actingAs($patient);

        $response = $this->patchJson("/api/v1/consultations/{$consultation->id}/cancel");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $consultation->id)
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_patient_cannot_cancel_consultation_too_close_to_start_time(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        $consultation = Consultation::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addMinutes(90),
        ]);

        Sanctum::actingAs($patient);

        $this->patchJson("/api/v1/consultations/{$consultation->id}/cancel")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['scheduled_at']);
    }

    public function test_patient_can_reschedule_scheduled_consultation(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $newScheduledAt = now()->addDays(3)->startOfHour();

        $consultation = Consultation::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        Sanctum::actingAs($patient);

        $response = $this->patchJson("/api/v1/consultations/{$consultation->id}/reschedule", [
            'scheduled_at' => $newScheduledAt->toISOString(),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $consultation->id)
            ->assertJsonPath('data.status', 'scheduled');
    }

    public function test_patient_cannot_reschedule_to_conflicting_doctor_slot(): void
    {
        $patient = User::factory()->patient()->create();
        $otherPatient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $conflictTime = now()->addDays(2)->startOfHour();

        $consultation = Consultation::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(3),
        ]);

        Consultation::factory()->create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => $doctor->id,
            'status' => 'scheduled',
            'scheduled_at' => $conflictTime,
        ]);

        Sanctum::actingAs($patient);

        $this->patchJson("/api/v1/consultations/{$consultation->id}/reschedule", [
            'scheduled_at' => $conflictTime->toISOString(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['scheduled_at']);
    }

    public function test_patient_cannot_view_another_patients_consultation_detail(): void
    {
        $patient = User::factory()->patient()->create();
        $otherPatient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        $consultation = Consultation::factory()->create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => $doctor->id,
        ]);

        Sanctum::actingAs($patient);

        $this->getJson("/api/v1/consultations/{$consultation->id}")
            ->assertNotFound();
    }

    public function test_non_patient_cannot_access_patient_consultation_endpoints(): void
    {
        $doctor = User::factory()->doctor()->create();

        Sanctum::actingAs($doctor);

        $this->getJson('/api/v1/consultations')->assertForbidden();
        $this->getJson('/api/v1/consultations/999')->assertForbidden();
        $this->patchJson('/api/v1/consultations/999/cancel')->assertForbidden();
        $this->patchJson('/api/v1/consultations/999/reschedule', [
            'scheduled_at' => now()->addDay()->toISOString(),
        ])->assertForbidden();
        $this->postJson('/api/v1/consultations/book', [])->assertForbidden();
    }
}
