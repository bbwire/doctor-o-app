<?php

namespace App\Services;

use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrescriptionPatientPdfService
{
    public function __construct(private readonly ConsultationPatientSummaryPdfService $clinicalSummaryPdf) {}

    public function download(Request $request, Prescription $prescription): Response
    {
        if ((int) $prescription->patient_id !== (int) $request->user()->id) {
            abort(403, 'You cannot access this prescription.');
        }

        $prescription->loadMissing(['patient', 'doctor.healthcareProfessional']);

        $rxLines = $this->clinicalSummaryPdf->buildPrescriptionPdfLines(
            is_array($prescription->medications) ? $prescription->medications : [],
            $prescription->instructions
        );

        $filename = 'prescription-'.($prescription->prescription_number ?: $prescription->id).'.pdf';

        return Pdf::loadView('prescription-patient-download', [
            'prescription' => $prescription,
            'rxLines' => $rxLines,
            'request' => $request,
        ])->setPaper('a4')->download($filename);
    }
}
