<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PatientConsultationService
{
    public function __construct(private readonly SettingsService $settingsService) {}

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
        
        // If no specific doctor is selected, find an available doctor in the category
        $doctorId = $validated['doctor_id'] ?? $this->findAvailableDoctorInCategory($validated['category'], $scheduledAt);
        
        if (!$doctorId) {
            throw ValidationException::withMessages([
                'scheduled_at' => ['No available doctors found in the selected category for the chosen time slot. Please try a different time or select a specific doctor.'],
            ]);
        }

        $isDoctorSlotTaken = Consultation::query()
            ->where('doctor_id', $doctorId)
            ->where('status', 'scheduled')
            ->where('scheduled_at', $scheduledAt->toDateTimeString())
            ->exists();

        if ($isDoctorSlotTaken) {
            throw ValidationException::withMessages([
                'scheduled_at' => ['The selected doctor is already booked for that date and time.'],
            ]);
        }

        $doctorProfile = User::query()
            ->where('id', $doctorId)
            ->with('healthcareProfessional')
            ->first()
            ?->healthcareProfessional;
        $amount = $this->settingsService->getConsultationAmountForDoctor($doctorProfile);

        if ($amount > 0 && (float) ($patient->wallet_balance ?? 0) < $amount) {
            throw ValidationException::withMessages([
                'wallet' => ['You do not have enough credit. Please top up your wallet.'],
            ]);
        }

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctorId,
            'scheduled_at' => $scheduledAt->toDateTimeString(),
            'consultation_type' => $validated['consultation_type'],
            'status' => 'scheduled',
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
            'metadata' => array_merge($consultation->metadata ?? [], [
                'requested_category' => $validated['category'],
                'auto_assigned' => !isset($validated['doctor_id']),
            ]),
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
            $doctorId,
            'consultation_booked',
            'New appointment',
            'A new consultation has been booked for ' . $scheduledAt->format('M j, Y \a\t g:i A') . '.',
            ['consultation_id' => $consultation->id]
        );
        $notificationService->notifyAdmins(
            'consultation_booked',
            'New consultation booked',
            'A patient has booked a consultation for ' . $scheduledAt->format('M j, Y \a\t g:i A') . '.',
            ['consultation_id' => $consultation->id, 'patient_id' => $patient->id, 'doctor_id' => $doctorId]
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
        app(NotificationService::class)->notifyAdmins(
            'consultation_cancelled',
            'Consultation cancelled',
            'A patient has cancelled a consultation scheduled for ' . $consultation->scheduled_at->format('M j, Y \a\t g:i A') . '.',
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
     * Find an available doctor in a specific category for a given time slot
     */
    private function findAvailableDoctorInCategory(string $category, Carbon $scheduledAt): ?int
    {
        // Find doctors in the specified category who are active and approved
        $availableDoctors = User::query()
            ->where('role', 'doctor')
            ->whereHas('healthcareProfessional', function ($query) use ($category) {
                $query->where('speciality', $category)
                      ->where('is_active', true)
                      ->where('is_approved', true);
            })
            ->with('healthcareProfessional')
            ->get();

        if ($availableDoctors->isEmpty()) {
            return null;
        }

        // Check each doctor for availability at the requested time
        foreach ($availableDoctors as $doctor) {
            $isWithinWorkingHours = $this->isDoctorAvailableAtTime($doctor, $scheduledAt);
            $isSlotTaken = Consultation::query()
                ->where('doctor_id', $doctor->id)
                ->where('status', 'scheduled')
                ->where('scheduled_at', $scheduledAt->toDateTimeString())
                ->exists();

            if ($isWithinWorkingHours && !$isSlotTaken) {
                return $doctor->id;
            }
        }

        return null;
    }

    /**
     * Check if a doctor is available at a specific time based on their working hours
     */
    private function isDoctorAvailableAtTime(User $doctor, Carbon $scheduledAt): bool
    {
        $availabilityStart = $doctor->healthcareProfessional?->availability_start_time;
        $availabilityEnd = $doctor->healthcareProfessional?->availability_end_time;

        if (!$availabilityStart || !$availabilityEnd) {
            // If no working hours are set, assume doctor is available
            return true;
        }

        $time = $scheduledAt->format('H:i:s');
        $startTime = $availabilityStart->format('H:i:s');
        $endTime = $availabilityEnd->format('H:i:s');

        return $time >= $startTime && $time <= $endTime;
    }

    /**
     * @return array<int, string>
     */
    public function suggestAvailableSlotsForCategory(string $category, ?string $from = null, int $limit = 5): array
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

        // Get all doctors in the category who are active and approved
        $doctorsInCategory = User::query()
            ->where('role', 'doctor')
            ->whereHas('healthcareProfessional', function ($query) use ($category) {
                $query->where('speciality', $category)
                      ->where('is_active', true)
                      ->where('is_approved', true);
            })
            ->with('healthcareProfessional')
            ->pluck('id')
            ->toArray();

        if (empty($doctorsInCategory)) {
            return [];
        }

        // Get all taken slots for any doctor in this category
        $taken = Consultation::query()
            ->whereIn('doctor_id', $doctorsInCategory)
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

            // Check if any doctor in the category is available at this time
            $isSlotAvailable = false;
            foreach ($doctorsInCategory as $doctorId) {
                $doctor = User::query()
                    ->where('id', $doctorId)
                    ->with('healthcareProfessional')
                    ->first();

                if ($doctor && $this->isDoctorAvailableAtTime($doctor, $candidate) && !$taken->has($key)) {
                    $isSlotAvailable = true;
                    break;
                }
            }

            if ($isSlotAvailable) {
                $suggestions[] = $candidate->toISOString();
            }

            $candidate->addMinutes($slotIntervalMinutes);
            $iterations++;
        }

        return $suggestions;
    }

    /**
     * @return array<int, string>
     */
    public function suggestAvailableSlots(int $doctorId, ?string $from = null, int $limit = 5): array
    {
        $limit = max(1, min($limit, 10));
        $slotIntervalMinutes = $this->slotIntervalMinutes();
        $availabilityWindowDays = $this->availabilityWindowDays();

        $doctor = User::query()
            ->where('id', $doctorId)
            ->where('role', 'doctor')
            ->with('healthcareProfessional')
            ->first();

        $availabilityStart = $doctor?->healthcareProfessional?->availability_start_time;
        $availabilityEnd = $doctor?->healthcareProfessional?->availability_end_time;

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

            $isWithinDoctorHours = true;
            if ($availabilityStart && $availabilityEnd) {
                $time = $candidate->format('H:i:s');
                $isWithinDoctorHours = $time >= $availabilityStart->format('H:i:s')
                    && $time <= $availabilityEnd->format('H:i:s');
            }

            if ($isWithinDoctorHours && ! $taken->has($key)) {
                $suggestions[] = $candidate->toISOString();
            }

            $candidate->addMinutes($slotIntervalMinutes);
            $iterations++;
        }

        return $suggestions;
    }

    private function slotIntervalMinutes(): int
    {
        return $this->settingsService->getConsultationSlotIntervalMinutes();
    }

    private function availabilityWindowDays(): int
    {
        return $this->settingsService->getConsultationAvailabilityWindowDays();
    }

    private function minimumActionLeadHours(): int
    {
        return $this->settingsService->getConsultationMinimumActionLeadHours();
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
