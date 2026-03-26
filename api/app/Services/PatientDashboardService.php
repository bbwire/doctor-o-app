<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\Prescription;
use App\Models\User;

class PatientDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function summaryForPatient(User $patient): array
    {
        $upcomingConsultationsCount = Consultation::query()
            ->where('patient_id', $patient->id)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ->count();

        $completedConsultationsCount = Consultation::query()
            ->where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->count();

        $prescriptionsCount = Prescription::query()
            ->where('patient_id', $patient->id)
            ->count();

        $nextConsultation = Consultation::query()
            ->with(['doctor'])
            ->where('patient_id', $patient->id)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->first();

        return [
            'patient_number' => $patient->patient_number,
            'upcoming_consultations' => $upcomingConsultationsCount,
            'prescriptions' => $prescriptionsCount,
            'completed_consultations' => $completedConsultationsCount,
            'next_consultation' => $nextConsultation ? [
                'id' => $nextConsultation->id,
                'scheduled_at' => $nextConsultation->scheduled_at?->toISOString(),
                'consultation_type' => $nextConsultation->consultation_type,
                'status' => $nextConsultation->status,
                'doctor' => [
                    'id' => $nextConsultation->doctor?->id,
                    'name' => $nextConsultation->doctor?->name,
                ],
            ] : null,
            'wallet_balance' => (float) ($patient->wallet_balance ?? 0),
        ];
    }
}
