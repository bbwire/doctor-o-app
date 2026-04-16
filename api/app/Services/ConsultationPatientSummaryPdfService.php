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
        $consultation->loadMissing(['patient', 'doctor.healthcareProfessional', 'prescriptions.doctor']);

        $notes = is_array($consultation->clinical_notes) ? $consultation->clinical_notes : [];

        // Patient-facing summary: only what is needed for management plan / prescriptions.
        $summary = [
            'summary_of_history' => $notes['summary_of_history'] ?? null,
            'differential_diagnosis' => $notes['differential_diagnosis'] ?? null,
            'management_plan' => $notes['management_plan'] ?? null,
            'final_diagnosis' => $notes['final_diagnosis'] ?? null,
        ];

        $issuedPrescriptionBlocks = $this->issuedPrescriptionBlocks($consultation);
        $hasIssuedRx = $issuedPrescriptionBlocks !== [];

        if (! $this->summaryHasContent($summary) && ! $hasIssuedRx) {
            abort(404, 'No consultation summary to export yet.');
        }

        $pdf = Pdf::loadView('consultation-summary', [
            'consultation' => $consultation,
            'summary' => $summary,
            'clinicalSummaryLines' => $this->linesFromText($summary['summary_of_history'] ?? null),
            'provisionalDiagnosisLines' => $this->linesFromText($summary['differential_diagnosis'] ?? null),
            'managementPlanItems' => $this->managementPlanNumberedItems($summary['management_plan'] ?? null),
            'prescriptionItems' => $this->prescriptionItems($summary['management_plan'] ?? null),
            'issuedPrescriptionBlocks' => $issuedPrescriptionBlocks,
            'request' => $request,
        ])->setPaper('a4');

        return $pdf->download('consultation-'.$consultation->id.'-clinical-summary.pdf');
    }

    /**
     * Format medications + instructions as printable lines (patient prescription PDF and clinical summary).
     *
     * @param  list<array<string, mixed>>|null  $medications
     * @return list<string>
     */
    public function buildPrescriptionPdfLines(?array $medications, ?string $generalInstructions): array
    {
        return $this->prescriptionLinesFromRxData([
            'medications' => is_array($medications) ? $medications : [],
            'instructions' => $generalInstructions,
        ]);
    }

    /**
     * Digital prescriptions saved on the consultation (all issued Rx, including after patient marked received).
     *
     * @return list<array{heading: string, lines: list<string>}>
     */
    private function issuedPrescriptionBlocks(Consultation $consultation): array
    {
        $blocks = [];

        foreach ($consultation->prescriptions->sortBy('issued_at') as $p) {
            $lines = $this->prescriptionLinesFromRxData([
                'medications' => is_array($p->medications) ? $p->medications : [],
                'instructions' => $p->instructions,
            ]);
            if ($lines === []) {
                continue;
            }
            $headingParts = array_filter([
                $p->prescription_number ? 'Rx '.$p->prescription_number : null,
                $p->issued_at?->format('F j, Y'),
                $p->doctor?->name,
            ]);
            $blocks[] = [
                'heading' => implode(' · ', $headingParts),
                'lines' => $lines,
            ];
        }

        return $blocks;
    }

    /**
     * @return list<string>
     */
    private function linesFromText(mixed $text): array
    {
        if (! is_string($text)) {
            return [];
        }

        if (trim($text) === '') {
            return [];
        }

        $lines = preg_split('/\R+/u', $text) ?: [];
        $out = [];
        foreach ($lines as $line) {
            $t = trim((string) $line);
            if ($t !== '') {
                $out[] = $t;
            }
        }

        return $out;
    }

    /**
     * Management plan items for a numbered list (excludes prescription — rendered separately).
     *
     * @return list<string>
     */
    private function managementPlanNumberedItems(mixed $mp): array
    {
        if ($mp === null) {
            return [];
        }

        if (is_string($mp)) {
            $t = trim($mp);

            return $t === '' ? [] : [$t];
        }

        if (! is_array($mp)) {
            return [];
        }

        $items = [];

        if (! empty($mp['treatment']) && is_string($mp['treatment']) && trim($mp['treatment']) !== '') {
            $items[] = trim($mp['treatment']);
        }

        $invLines = [];
        foreach (['investigation_radiology' => 'Radiology', 'investigation_laboratory' => 'Laboratory', 'investigation_interventional' => 'Interventional'] as $key => $label) {
            if (! empty($mp[$key]) && is_string($mp[$key]) && trim($mp[$key]) !== '') {
                $invLines[] = $label.': '.trim($mp[$key]);
            }
        }
        if ($invLines !== []) {
            $items[] = implode("\n", $invLines);
        }

        if (! empty($mp['referrals']) && is_string($mp['referrals']) && trim($mp['referrals']) !== '') {
            $items[] = 'Referrals: '.trim($mp['referrals']);
        }

        $ipv = $mp['in_person_visit'] ?? null;
        if (is_array($ipv)) {
            $ipvParts = [];
            if (! empty($ipv['revisit_history'])) {
                $ipvParts[] = 'Doctor revisits history: '.trim((string) $ipv['revisit_history']);
            }
            if (! empty($ipv['general_examination'])) {
                $ge = $ipv['general_examination'];
                if (is_array($ge)) {
                    $geLines = [];
                    foreach ([
                        'appearance' => 'General appearance',
                        'jaundice' => 'Jaundice',
                        'anemia' => 'Anemia',
                        'cyanosis' => 'Cyanosis',
                        'clubbing' => 'Clubbing',
                        'oedema' => 'Oedema',
                        'lymphadenopathy' => 'Lymphadenopathy',
                        'dehydration' => 'Dehydration',
                    ] as $gk => $gl) {
                        if (! empty($ge[$gk])) {
                            $geLines[] = $gl.': '.$ge[$gk];
                        }
                    }
                    if ($geLines !== []) {
                        $ipvParts[] = "General examination:\n".implode("\n", $geLines);
                    }
                } elseif (is_string($ge) && trim($ge) !== '') {
                    $ipvParts[] = 'General examination: '.trim($ge);
                }
            }
            if (! empty($ipv['system_examination'])) {
                $se = $ipv['system_examination'];
                $ipvParts[] = 'System examination: '.(is_string($se) ? trim($se) : trim((string) json_encode($se)));
            }
            if ($ipvParts !== []) {
                $items[] = implode("\n\n", $ipvParts);
            }
        } elseif (is_string($ipv) && trim($ipv) !== '') {
            $items[] = trim($ipv);
        }

        return $items;
    }

    /**
     * Prescription lines from clinical notes management plan block.
     *
     * @return list<string>
     */
    private function prescriptionItems(mixed $mp): array
    {
        if (! is_array($mp)) {
            return [];
        }

        $rx = $mp['prescription'] ?? null;

        return $this->prescriptionLinesFromRxData(is_array($rx) ? $rx : null);
    }

    /**
     * @param  array{medications?: mixed, instructions?: mixed}|null  $rx
     * @return list<string>
     */
    private function prescriptionLinesFromRxData(?array $rx): array
    {
        if (! is_array($rx) || empty($rx['medications']) || ! is_array($rx['medications'])) {
            return [];
        }

        $out = [];
        foreach ($rx['medications'] as $med) {
            if (! is_array($med) || trim((string) ($med['name'] ?? '')) === '') {
                continue;
            }
            $parts = [trim((string) $med['name'])];
            if (! empty($med['form'])) {
                $parts[] = '('.$med['form'].')';
            }
            $dose = [];
            if (! empty($med['dosage'])) {
                $dose[] = $med['dosage'];
            }
            if (! empty($med['frequency'])) {
                $dose[] = $med['frequency'];
            }
            if ($dose !== []) {
                $parts[] = '— '.implode(', ', $dose);
            }
            if (! empty($med['duration'])) {
                $parts[] = '('.$med['duration'].')';
            }
            $line = implode(' ', array_filter($parts));
            if (! empty($med['instructions'])) {
                $line .= "\nInstructions: ".trim((string) $med['instructions']);
            }
            $out[] = $line;
        }

        if (! empty($rx['instructions']) && is_string($rx['instructions']) && trim($rx['instructions']) !== '') {
            $out[] = 'General instructions: '.trim($rx['instructions']);
        }

        return $out;
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
        if ($value === null) {
            return false;
        }

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
