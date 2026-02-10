<?php

namespace App\Services;

use App\Models\Consultation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ConsultationService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Consultation::query()->with(['patient', 'doctor']);

        if (! empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        if (! empty($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['consultation_type'])) {
            $query->where('consultation_type', $filters['consultation_type']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->latest('scheduled_at')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated): Consultation
    {
        return Consultation::create($validated)->load(['patient', 'doctor']);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(Consultation $consultation, array $validated): Consultation
    {
        $consultation->update($validated);

        return $consultation->refresh()->load(['patient', 'doctor']);
    }

    public function delete(Consultation $consultation): void
    {
        $consultation->delete();
    }
}
