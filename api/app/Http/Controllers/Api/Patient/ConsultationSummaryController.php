<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Services\ConsultationPatientSummaryPdfService;
use App\Services\PatientConsultationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConsultationSummaryController extends Controller
{
    public function __construct(private readonly PatientConsultationService $patientConsultationService) {}

    /**
     * Patient-facing clinical summary PDF (management plan / prescriptions, investigations, referrals, in-person visit notes).
     */
    public function download(Request $request, int $consultationId, ConsultationPatientSummaryPdfService $pdfService): Response
    {
        $consultation = $this->patientConsultationService->findForPatientOrFail($request->user(), $consultationId);

        return $pdfService->download($request, $consultation);
    }
}
