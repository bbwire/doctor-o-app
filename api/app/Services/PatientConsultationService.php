<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PatientConsultationService
{
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

        $type = $validated['consultation_type'];
        $pricing = config('consultations.pricing', []);
        $amount = (float) ($pricing[$type] ?? 0);

        if ($amount > 0) {
            $currentBalance = (float) ($patient->wallet_balance ?? 0);
            if ($currentBalance < $amount) {
                throw ValidationException::withMessages([
                    'wallet' => ['You do not have enough credit for this consultation.'],
                ]);
            }
        }

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_id' => $validated['doctor_id'],
            'scheduled_at' => $scheduledAt->toDateTimeString(),
            'consultation_type' => $validated['consultation_type'],
            'status' => 'scheduled',
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
        ])->load(['doctor.healthcareProfessional.institution', 'patient']);

        if ($amount > 0) {
            app(WalletService::class)->chargeForConsultation($patient, $consultation, $amount);
        }

        $notificationService = app(NotificationService::class);
        $notificationService->createForUser(
            $patient->id,
            'consultation_booked',
            'Appointment confirmed',
            'Your consultation has been scheduled for ' . $scheduledAt->format('M j, Y \a\t g:i A') . '.',
            ['consultation_id' => $consultation->id]
        );
        $notificationService->createForUser(
            (int) $validated['doctor_id'],
            'consultation_booked',
            'New appointment',
            'A new consultation has been booked for ' . $scheduledAt->format('M j, Y \a\t g:i A') . '.',
            ['consultation_id' => $consultation->id]
        );

        return $consultation;
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

        app(NotificationService::class)->createForUser(
            (int) $consultation->doctor_id,
            'consultation_cancelled',
            'Appointment cancelled',
            'A patient has cancelled their consultation scheduled for ' . $consultation->scheduled_at->format('M j, Y \a\t g:i A') . '.',
            ['consultation_id' => $consultation->id]
        );

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

        $minimumManageTime = now()->addHours($this->minimumActionLeadHours());
        if ($consultation->scheduled_at && $consultation->scheduled_at->lte($minimumManageTime)) {
            throw ValidationException::withMessages([
                'scheduled_at' => ['Consultation can only be changed at least '.$this->minimumActionLeadHours().' hours before start time.'],
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    public function suggestAvailableSlots(int $doctorId, ?string $from = null, int $limit = 5): array
    {
        $limit = max(1, min($limit, 10));
        $slotIntervalMinutes = $this->slotIntervalMinutes();
        $availabilityWindowDays = $this->availabilityWindowDays();

        $start = $from ? Carbon::parse($from) : now();
        $minimumStart = now()->addMinutes($slotIntervalMinutes);
        if ($start->lt($minimumStart)) {
            $start = $minimumStart;
        }

        $candidate = $this->alignToSlot($start, $slotIntervalMinutes);
        $windowEnd = $candidate->copy()->addDays($availabilityWindowDays);

        $taken = Consultation::query()
            ->where('doctor_id', $doctorId)
            ->where('status', 'scheduled')
            ->whereBetween('scheduled_at', [$candidate->toDateTimeString(), $windowEnd->toDateTimeString()])
            ->pluck('scheduled_at')
            ->map(fn ($value) => Carbon::parse($value)->toDateTimeString())
            ->flip();

        $suggestions = [];
        $maxIterations = (int) ceil(($availabilityWindowDays * 24 * 60) / $slotIntervalMinutes);
        $iterations = 0;

        while (count($suggestions) < $limit && $iterations < $maxIterations) {
            $key = $candidate->toDateTimeString();
            if (! $taken->has($key)) {
                $suggestions[] = $candidate->toISOString();
            }

            $candidate->addMinutes($slotIntervalMinutes);
            $iterations++;
        }

        return $suggestions;
    }

    private function slotIntervalMinutes(): int
    {
        $configured = (int) config('consultations.slot_interval_minutes', 60);

        return max(15, min($configured, 120));
    }

    private function availabilityWindowDays(): int
    {
        $configured = (int) config('consultations.availability_window_days', 14);

        return max(1, min($configured, 30));
    }

    private function minimumActionLeadHours(): int
    {
        $configured = (int) config('consultations.minimum_action_lead_hours', 2);

        return max(1, min($configured, 72));
    }

    private function alignToSlot(Carbon $date, int $slotIntervalMinutes): Carbon
    {
        $aligned = $date->copy()->setSecond(0);
        $remainder = $aligned->minute % $slotIntervalMinutes;

        if ($remainder !== 0) {
            $aligned->addMinutes($slotIntervalMinutes - $remainder);
        }

        if ($aligned->lt($date)) {
            $aligned->addMinutes($slotIntervalMinutes);
        }

        return $aligned;
    }
}
