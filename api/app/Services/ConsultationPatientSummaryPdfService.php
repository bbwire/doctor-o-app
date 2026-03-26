<?php

namespace App\Services;

use App\Models\Consultation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConsultationPatientSummaryPdfService
{
    public function download(Request $request, Consultation $consultation): Response
    {
        $consultation->loadMissing(['patient', 'doctor']);

        $notes = is_array($consultation->clinical_notes) ? $consultation->clinical_notes : [];

        // Patient-facing summary: only what is needed for management plan / prescriptions.
        $summary = [
            'summary_of_history' => $notes['summary_of_history'] ?? null,
            'differential_diagnosis' => $notes['differential_diagnosis'] ?? null,
            'management_plan' => $notes['management_plan'] ?? null,
            'final_diagnosis' => $notes['final_diagnosis'] ?? null,
        ];

        if (! $this->summaryHasContent($summary)) {
            abort(404, 'No consultation summary to export yet.');
        }

        $pdf = Pdf::loadView('consultation-summary', [
            'consultation' => $consultation,
            'summary' => $summary,
            'request' => $request,
        ])->setPaper('a4');

        return $pdf->download('consultation-' . $consultation->id . '-clinical-summary.pdf');
    }

    private function summaryHasContent(array $summary): bool
    {
        foreach ($summary as $value) {
            if ($this->valueHasLeafContent($value)) {
                return true;
            }
        }

        return false;
    }

    private function valueHasLeafContent(mixed $value): bool
    {
        if ($value === null) return false;

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            foreach ($value as $v) {
                if ($this->valueHasLeafContent($v)) {
                    return true;
                }
            }
            return false;
        }

        return false;
    }
}

