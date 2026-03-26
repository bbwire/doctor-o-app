<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\User;
use Carbon\Carbon;

class DoctorDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function summaryForDoctor(User $doctor): array
    {
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();

        $todayConsultationsCount = Consultation::query()
            ->where('doctor_id', $doctor->id)
            ->where('status', 'scheduled')
            ->whereBetween('scheduled_at', [$todayStart, $todayEnd])
            ->where('scheduled_at', '>=', now())
            ->count();

        $upcomingConsultationsCount = Consultation::query()
            ->where('doctor_id', $doctor->id)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ->count();

        $completedTodayCount = Consultation::query()
            ->where('doctor_id', $doctor->id)
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$todayStart, $todayEnd])
            ->count();

        $nextConsultation = Consultation::query()
            ->with(['patient'])
            ->where('doctor_id', $doctor->id)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->first();

        return [
            'today_consultations' => $todayConsultationsCount,
            'upcoming_consultations' => $upcomingConsultationsCount,
            'completed_today' => $completedTodayCount,
            'next_consultation' => $nextConsultation ? [
                'id' => $nextConsultation->id,
                'consultation_number' => $nextConsultation->consultation_number,
                'scheduled_at' => $nextConsultation->scheduled_at?->toISOString(),
                'consultation_type' => $nextConsultation->consultation_type,
                'status' => $nextConsultation->status,
                'patient' => [
                    'id' => $nextConsultation->patient?->id,
                    'name' => $nextConsultation->patient?->name,
                    'patient_number' => $nextConsultation->patient?->patient_number,
                ],
            ] : null,
        ];
    }
}
