<?php

namespace App\Services;

use App\Models\Prescription;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PatientPrescriptionService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForPatient(User $patient, array $filters): LengthAwarePaginator
    {
        $query = Prescription::query()
            ->with(['doctor.healthcareProfessional.institution', 'consultation'])
            ->where('patient_id', $patient->id)
            ->whereNull('patient_received_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->latest('issued_at')->paginate($perPage);
    }

    /**
     * Patient confirms they collected medication from the pharmacy; hides Rx from patient app lists.
     */
    public function acknowledgePharmacyReceipt(User $patient, Prescription $prescription): Prescription
    {
        if ((int) $prescription->patient_id !== (int) $patient->id) {
            abort(403, 'You cannot update this prescription.');
        }

        if ($prescription->patient_received_at !== null) {
            throw ValidationException::withMessages([
                'prescription' => ['This prescription is already marked as received.'],
            ]);
        }

        $prescription->update(['patient_received_at' => now()]);

        return $prescription->refresh()->load(['doctor.healthcareProfessional.institution', 'consultation']);
    }
}
