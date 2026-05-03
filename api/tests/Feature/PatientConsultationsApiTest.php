<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\ConsultationSettlement;
use App\Models\HealthcareProfessional;
use App\Models\Institution;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientConsultationsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_book_consultation_for_self(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        HealthcareProfessional::factory()->create([
            'user_id' => $doctor->id,
            'institution_id' => Institution::factory()->create()->id,
        ]);

        Sanctum::actingAs($patient);

        $response = $this->postJson('/api/v1/consultations/book', [
            'doctor_id' => $doctor->id,
            'category' => 'General Doctor',
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

    public function test_patient_can_store_review_of_systems_when_booking(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        HealthcareProfessional::factory()->create([
            'user_id' => $doctor->id,
            'institution_id' => Institution::factory()->create()->id,
        ]);

        Sanctum::actingAs($patient);

        $capturedAt = now()->toISOString();
        $ros = [
            'summary' => 'ROS positive for cough and chest pain; otherwise negative on this checklist.',
            'captured_at' => $capturedAt,
            'positive' => [
                ['id' => 'resp-cough', 'label' => 'Cough', 'details' => '1 week'],
                ['id' => 'cv-chest-pain', 'label' => 'Chest pain', 'details' => null],
            ],
        ];

        $response = $this->postJson('/api/v1/consultations/book', [
            'doctor_id' => $doctor->id,
            'category' => 'General Doctor',
            'scheduled_at' => now()->addDay()->toISOString(),
            'consultation_type' => 'video',
            'reason' => 'Follow-up checkup',
            'review_of_systems' => $ros,
        ]);

        $response->assertCreated();

        $consultation = Consultation::query()->where('patient_id', $patient->id)->firstOrFail();
        $meta = $consultation->metadata ?? [];
        $this->assertArrayHasKey('patient_review_of_systems', $meta);
        $this->assertSame($ros['summary'], $meta['patient_review_of_systems']['summary']);
        $this->assertCount(2, $meta['patient_review_of_systems']['positive']);
    }

    public function test_patient_cannot_book_conflicting_scheduled_slot_for_same_doctor(): void
    {
        $patient = User::factory()->patient()->create();
        $otherPatient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        HealthcareProfessional::factory()->create([
            'user_id' => $doctor->id,
            'institution_id' => Institution::factory()->create()->id,
        ]);
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
            'category' => 'General Doctor',
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
        $this->patchJson('/api/v1/consultations/999/outcome', [
            'patient_reports_improved' => true,
        ])->assertForbidden();
        $this->postJson('/api/v1/consultations/book', [])->assertForbidden();
    }

    public function test_patient_can_submit_outcome_on_completed_consultation(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        $consultation = Consultation::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'completed',
            'clinical_notes' => [
                'summary_of_history' => 'Brief history',
                'outcome' => [
                    'doctor_notes' => 'Expected recovery in a few days',
                ],
            ],
        ]);

        Sanctum::actingAs($patient);

        $this->patchJson("/api/v1/consultations/{$consultation->id}/outcome", [
            'patient_reports_improved' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.consultation_summary.outcome.patient_reports_improved', true)
            ->assertJsonPath('data.consultation_summary.outcome.doctor_notes', 'Expected recovery in a few days');

        $consultation->refresh();
        $this->assertTrue($consultation->clinical_notes['outcome']['patient_reports_improved']);
        $this->assertArrayHasKey('patient_reported_at', $consultation->clinical_notes['outcome']);
    }

    public function test_patient_cannot_submit_outcome_before_consultation_completed(): void
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

        $this->patchJson("/api/v1/consultations/{$consultation->id}/outcome", [
            'patient_reports_improved' => false,
        ])
            ->assertStatus(422);
    }

    public function test_patient_booking_without_doctor_creates_waiting_consultation(): void
    {
        $patient = User::factory()->patient()->create();

        Sanctum::actingAs($patient);

        $response = $this->postJson('/api/v1/consultations/book', [
            'scheduled_at' => now()->addDay()->toISOString(),
            'category' => 'General Doctor',
            'consultation_type' => 'video',
            'reason' => 'Need general checkup',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.patient_id', $patient->id)
            ->assertJsonPath('data.status', 'waiting')
            ->assertJsonPath('data.doctor_id', null);
    }

    public function test_doctor_claims_waiting_consultation_and_charges_wallet(): void
    {
        $patient = User::factory()->patient()->create([
            'wallet_balance' => 1000,
        ]);

        $consultationCreatedAt = now()->addDay()->startOfHour();

        Sanctum::actingAs($patient);
        $booking = $this->postJson('/api/v1/consultations/book', [
            'scheduled_at' => $consultationCreatedAt->toISOString(),
            'category' => 'General Doctor',
            'consultation_type' => 'video',
            'reason' => 'Waiting room test',
        ])->assertCreated();

        $consultationId = $booking->json('data.id');

        $doctor = User::factory()->doctor()->create();
        $institution = Institution::factory()->create();

        HealthcareProfessional::factory()->create([
            'user_id' => $doctor->id,
            'institution_id' => $institution->id,
            'speciality' => 'General Doctor',
            'is_active' => true,
            'is_approved' => true,
            'consultation_charge' => 100,
            // null working hours => considered available by claim logic
            'availability_start_time' => null,
            'availability_end_time' => null,
        ]);

        Sanctum::actingAs($doctor);

        $settlementBefore = ConsultationSettlement::query()->count();
        $walletTxBefore = WalletTransaction::query()->count();

        $this->postJson("/api/v1/doctor/consultations/{$consultationId}/claim", [])
            ->assertOk()
            ->assertJsonPath('data.id', $consultationId)
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.doctor_id', $doctor->id);

        $this->assertTrue(ConsultationSettlement::query()->count() > $settlementBefore);
        $this->assertTrue(WalletTransaction::query()->count() > $walletTxBefore);
        $this->assertSame(900.0, (float) $patient->refresh()->wallet_balance);
    }

    public function test_patient_can_post_chat_message_with_image_only(): void
    {
        Storage::fake('public');

        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        $consultation = Consultation::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        Sanctum::actingAs($patient);

        $tinyPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        $this->assertNotFalse($tinyPng);
        $image = UploadedFile::fake()->createWithContent('chat.png', $tinyPng);

        $response = $this->post("/api/v1/consultations/{$consultation->id}/messages", [
            'text' => '',
            'image' => $image,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.text', '');
        $this->assertNotNull($response->json('data.attachment_url'));

        $this->assertDatabaseHas('consultation_messages', [
            'consultation_id' => $consultation->id,
            'user_id' => $patient->id,
            'sender' => 'patient',
            'text' => '',
        ]);
    }

    public function test_chat_message_rejects_empty_text_without_image(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        $consultation = Consultation::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        Sanctum::actingAs($patient);

        $this->postJson("/api/v1/consultations/{$consultation->id}/messages", [
            'text' => '   ',
        ])->assertUnprocessable();
    }
}
