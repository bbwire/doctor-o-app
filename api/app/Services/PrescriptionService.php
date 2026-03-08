<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\Prescription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PrescriptionService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Prescription::query()->with(['consultation', 'patient', 'doctor']);

        if (! empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        if (! empty($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        if (! empty($filters['consultation_id'])) {
            $query->where('consultation_id', $filters['consultation_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->latest('issued_at')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated): Prescription
    {
        $this->validateParticipantsForConsultation($validated);

        $prescription = Prescription::create($validated)->load(['consultation', 'patient', 'doctor']);

        app(NotificationService::class)->createForUser(
            (int) $prescription->patient_id,
            'prescription_issued',
            'Prescription ready',
            'A new prescription has been issued for you.',
            ['prescription_id' => $prescription->id]
        );
        app(NotificationService::class)->notifyAdmins(
            'prescription_issued',
            'New prescription issued',
            'A prescription has been issued for consultation #' . $prescription->consultation_id . '.',
            ['prescription_id' => $prescription->id, 'consultation_id' => $prescription->consultation_id]
        );

        return $prescription;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(Prescription $prescription, array $validated): Prescription
    {
        $payload = array_merge($prescription->toArray(), $validated);
        $this->validateParticipantsForConsultation($payload);

        $prescription->update($validated);

        return $prescription->refresh()->load(['consultation', 'patient', 'doctor']);
    }

    public function delete(Prescription $prescription): void
    {
        $prescription->delete();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validateParticipantsForConsultation(array $payload): void
    {
        $consultation = Consultation::find($payload['consultation_id'] ?? null);

        if (! $consultation) {
            throw ValidationException::withMessages([
                'consultation_id' => ['The selected consultation is invalid.'],
            ]);
        }

        if ((int) $payload['doctor_id'] !== (int) $consultation->doctor_id) {
            throw ValidationException::withMessages([
                'doctor_id' => ['Doctor must match consultation doctor.'],
            ]);
        }

        if ((int) $payload['patient_id'] !== (int) $consultation->patient_id) {
            throw ValidationException::withMessages([
                'patient_id' => ['Patient must match consultation patient.'],
            ]);
        }
    }
}
