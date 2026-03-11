<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Services\PatientConsultationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConsultationSummaryController extends Controller
{
    public function __construct(private readonly PatientConsultationService $patientConsultationService) {}

    public function download(Request $request, int $consultationId): Response
    {
        $consultation = $this->patientConsultationService->findForPatientOrFail($request->user(), $consultationId);
        $consultation->load(['patient', 'doctor']);

        $notes = $consultation->clinical_notes ?? [];
        $summary = [
            'summary_of_history' => $notes['summary_of_history'] ?? null,
            'differential_diagnosis' => $notes['differential_diagnosis'] ?? null,
            'management_plan' => $notes['management_plan'] ?? null,
            'final_diagnosis' => $notes['final_diagnosis'] ?? null,
        ];

        $hasContent = collect($summary)->contains(fn ($v) => is_array($v)
            ? ! empty(array_filter($v))
            : is_string($v) && trim($v) !== '');
        if (! $hasContent) {
            abort(404, 'Consultation summary is not yet available.');
        }

        $pdf = Pdf::loadView('consultation-summary', [
            'consultation' => $consultation,
            'summary' => $summary,
        ])->setPaper('a4');

        $filename = 'consultation-' . $consultation->id . '-summary.pdf';

        return $pdf->download($filename);
    }
}
