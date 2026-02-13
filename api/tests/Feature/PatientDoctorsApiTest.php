<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\HealthcareProfessional;
use App\Models\Institution;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientDoctorsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_list_doctors(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create(['name' => 'Dr. Jane']);
        $institution = Institution::factory()->create(['name' => 'General Hospital']);

        HealthcareProfessional::factory()->create([
            'user_id' => $doctor->id,
            'institution_id' => $institution->id,
            'speciality' => 'Cardiology',
        ]);

        Sanctum::actingAs($patient);

        $response = $this->getJson('/api/v1/doctors');

        $response
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Dr. Jane',
                'speciality' => 'Cardiology',
                'institution' => 'General Hospital',
            ]);
    }

    public function test_non_patient_cannot_list_doctors(): void
    {
        $doctor = User::factory()->doctor()->create();
        Sanctum::actingAs($doctor);

        $this->getJson('/api/v1/doctors')->assertForbidden();
        $this->getJson('/api/v1/doctors/1/availability')->assertForbidden();
    }

    public function test_patient_can_get_doctor_availability_suggestions(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $start = now()->addDay()->startOfHour();

        Consultation::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'scheduled',
            'scheduled_at' => $start,
        ]);

        Sanctum::actingAs($patient);

        $response = $this->getJson("/api/v1/doctors/{$doctor->id}/availability?from=".$start->toISOString().'&limit=3');

        $response
            ->assertOk()
            ->assertJsonPath('data.doctor_id', $doctor->id)
            ->assertJsonCount(3, 'data.available_slots');
    }

    public function test_availability_respects_configured_slot_interval_minutes(): void
    {
        config()->set('consultations.slot_interval_minutes', 30);

        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $start = now()->addDay()->startOfHour();

        Sanctum::actingAs($patient);

        $response = $this->getJson("/api/v1/doctors/{$doctor->id}/availability?from=".$start->toISOString().'&limit=2');

        $slots = $response->json('data.available_slots');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data.available_slots');

        $first = Carbon::parse($slots[0]);
        $second = Carbon::parse($slots[1]);
        $this->assertEquals(30.0, $first->diffInMinutes($second));
    }
}
