<?php

namespace App\Services;

use App\Models\Prescription;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PatientPrescriptionService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForPatient(User $patient, array $filters): LengthAwarePaginator
    {
        $query = Prescription::query()
            ->with(['doctor.healthcareProfessional.institution', 'consultation'])
            ->where('patient_id', $patient->id);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->latest('issued_at')->paginate($perPage);
    }
}
