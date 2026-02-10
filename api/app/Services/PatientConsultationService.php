<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PatientConsultationService
{
    private const MINIMUM_ACTION_LEAD_HOURS = 2;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForPatient(User $patient, array $filters): LengthAwarePaginator
    {
        $query = Consultation::query()
            ->with(['doctor.healthcareProfessional.institution'])
            ->where('patient_id', $patient->id);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->latest('scheduled_at')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function createForPatient(User $patient, array $validated): Consultation
    {
        $scheduledAt = Carbon::parse($validated['scheduled_at']);

        $isDoctorSlotTaken = Consultation::query()
            ->where('doctor_id', $validated['doctor_id'])
            ->where('status', 'scheduled')
            ->where('scheduled_at', $scheduledAt->toDateTimeString())
            ->exists();

        if ($isDoctorSlotTaken) {
            throw ValidationException::withMessages([
                'scheduled_at' => ['The selected doctor is already booked for that date and time.'],
            ]);
        }

        return Consultation::create([
            'patient_id' => $patient->id,
            'doctor_id' => $validated['doctor_id'],
            'scheduled_at' => $scheduledAt->toDateTimeString(),
            'consultation_type' => $validated['consultation_type'],
            'status' => 'scheduled',
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
        ])->load(['doctor.healthcareProfessional.institution']);
    }

    public function findForPatientOrFail(User $patient, int $consultationId): Consultation
    {
        return Consultation::query()
            ->with(['doctor.healthcareProfessional.institution', 'prescriptions'])
            ->where('patient_id', $patient->id)
            ->findOrFail($consultationId);
    }

    public function cancelForPatient(User $patient, int $consultationId): Consultation
    {
        $consultation = $this->findForPatientOrFail($patient, $consultationId);
        $this->assertCanManageScheduledConsultation($consultation);

        $consultation->update([
            'status' => 'cancelled',
            'metadata' => array_merge($consultation->metadata ?? [], [
                'cancelled_at' => now()->toISOString(),
                'cancelled_by' => 'patient',
            ]),
        ]);

        return $consultation->refresh()->load(['doctor.healthcareProfessional.institution', 'prescriptions']);
    }

    /**
     * @param  array{scheduled_at: string}  $validated
     */
    public function rescheduleForPatient(User $patient, int $consultationId, array $validated): Consultation
    {
        $consultation = $this->findForPatientOrFail($patient, $consultationId);
        $this->assertCanManageScheduledConsultation($consultation);

        $newScheduledAt = Carbon::parse($validated['scheduled_at']);

        $isDoctorSlotTaken = Consultation::query()
            ->where('doctor_id', $consultation->doctor_id)
            ->where('status', 'scheduled')
            ->where('id', '!=', $consultation->id)
            ->where('scheduled_at', $newScheduledAt->toDateTimeString())
            ->exists();

        if ($isDoctorSlotTaken) {
            throw ValidationException::withMessages([
                'scheduled_at' => ['The selected doctor is already booked for that date and time.'],
            ]);
        }

        $consultation->update([
            'scheduled_at' => $newScheduledAt->toDateTimeString(),
            'metadata' => array_merge($consultation->metadata ?? [], [
                'rescheduled_at' => now()->toISOString(),
                'rescheduled_by' => 'patient',
            ]),
        ]);

        return $consultation->refresh()->load(['doctor.healthcareProfessional.institution', 'prescriptions']);
    }

    private function assertCanManageScheduledConsultation(Consultation $consultation): void
    {
        if ($consultation->status !== 'scheduled') {
            throw ValidationException::withMessages([
                'status' => ['Only scheduled consultations can be updated.'],
            ]);
        }

        $minimumManageTime = now()->addHours(self::MINIMUM_ACTION_LEAD_HOURS);
        if ($consultation->scheduled_at && $consultation->scheduled_at->lte($minimumManageTime)) {
            throw ValidationException::withMessages([
                'scheduled_at' => ['Consultation can only be changed at least 2 hours before start time.'],
            ]);
        }
    }
}
