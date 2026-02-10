<?php

namespace Tests\Feature;

use App\Models\HealthcareProfessional;
use App\Models\Institution;
use App\Models\User;
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
    }
}
