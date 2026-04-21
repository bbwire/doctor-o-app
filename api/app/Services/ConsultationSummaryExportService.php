<?php

namespace App\Services;

use App\Models\Consultation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ConsultationSummaryExportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(array $filters): Collection
    {
        $consultations = $this->baseQuery($filters)->get();

        return $consultations->map(function (Consultation $consultation): array {
            $notes = is_array($consultation->clinical_notes) ? $consultation->clinical_notes : [];
            $mp = is_array($notes['management_plan'] ?? null) ? $notes['management_plan'] : [];
            $ipv = is_array($mp['in_person_visit'] ?? null) ? $mp['in_person_visit'] : [];
            $outcome = is_array($notes['outcome'] ?? null) ? $notes['outcome'] : [];
            $uploads = $consultation->metadata['patient_investigation_uploads'] ?? [];

            return [
                'consultation_id' => $consultation->id,
                'consultation_number' => $consultation->consultation_number,
                'referral_number' => $consultation->referral_number,
                'scheduled_at' => $consultation->scheduled_at?->toISOString(),
                'consultation_type' => $consultation->consultation_type,
                'status' => $consultation->status,
                'patient_id' => $consultation->patient?->id,
                'patient_number' => $consultation->patient?->patient_number,
                'patient_name' => $consultation->patient?->name,
                'patient_email' => $consultation->patient?->email,
                'doctor_id' => $consultation->doctor?->id,
                'doctor_name' => $consultation->doctor?->name,
                'doctor_email' => $consultation->doctor?->email,
                'reason' => $consultation->reason,
                'notes' => $consultation->notes,
                'presenting_complaint' => $notes['presenting_complaint'] ?? null,
                'presenting_complaints' => $this->formatPresentingComplaintsForExport($notes['presenting_complaints'] ?? null),
                'history_of_presenting_complaint' => $notes['history_of_presenting_complaint'] ?? null,
                'review_of_systems' => $this->formatReviewOfSystemsForExport($notes['review_of_systems'] ?? null),
                'past_medical_history' => $notes['past_medical_history'] ?? null,
                'past_surgical_history' => $notes['past_surgical_history'] ?? null,
                'growth_and_development' => $notes['growth_and_development'] ?? null,
                'immunization_history' => $notes['immunization_history'] ?? null,
                'family_history' => $notes['family_history'] ?? null,
                'social_history' => $notes['social_history'] ?? null,
                'summary_of_history' => $notes['summary_of_history'] ?? null,
                'differential_diagnosis' => $notes['differential_diagnosis'] ?? null,
                'differential_diagnoses_icd11' => $this->stringify($notes['differential_diagnoses_icd11'] ?? null),
                'investigation_results' => $notes['investigation_results'] ?? null,
                'final_diagnosis' => $notes['final_diagnosis'] ?? null,
                'final_diagnosis_icd11' => $this->stringify($notes['final_diagnosis_icd11'] ?? null),
                'final_treatment' => $notes['final_treatment'] ?? null,
                'management_plan_treatment' => $mp['treatment'] ?? null,
                'management_plan_prescription_medications' => $this->formatPrescriptionMedications($mp['prescription']['medications'] ?? null),
                'management_plan_prescription_instructions' => $mp['prescription']['instructions'] ?? null,
                'management_plan_investigation_radiology' => $mp['investigation_radiology'] ?? null,
                'management_plan_investigation_laboratory' => $mp['investigation_laboratory'] ?? null,
                'management_plan_investigation_interventional' => $mp['investigation_interventional'] ?? null,
                'management_plan_referrals' => $mp['referrals'] ?? null,
                'management_plan_selected_categories' => $this->stringify($mp['selected_categories'] ?? null),
                'in_person_visit_revisit_history' => $ipv['revisit_history'] ?? null,
                'in_person_visit_general_examination' => $this->stringify($ipv['general_examination'] ?? null),
                'in_person_visit_system_examination' => $this->stringify($ipv['system_examination'] ?? null),
                'outcome_doctor_notes' => $outcome['doctor_notes'] ?? null,
                'outcome_patient_reports_improved' => array_key_exists('patient_reports_improved', $outcome)
                    ? ($outcome['patient_reports_improved'] ? 'Yes' : 'No')
                    : null,
                'outcome_patient_reported_at' => $outcome['patient_reported_at'] ?? null,
                'patient_investigation_uploads' => $this->stringify($uploads),
                'metadata_json' => $this->stringify($consultation->metadata),
                'clinical_notes_json' => $this->stringify($notes),
                'created_at' => $consultation->created_at?->toISOString(),
                'updated_at' => $consultation->updated_at?->toISOString(),
            ];
        })->values();
    }

    /**
     * @return array<string, string>
     */
    public function columns(): array
    {
        return [
            'consultation_id' => 'Consultation ID',
            'consultation_number' => 'Consultation Number',
            'referral_number' => 'Referral Number',
            'scheduled_at' => 'Scheduled At',
            'consultation_type' => 'Consultation Type',
            'status' => 'Status',
            'patient_id' => 'Patient ID',
            'patient_number' => 'Patient Number',
            'patient_name' => 'Patient Name',
            'patient_email' => 'Patient Email',
            'doctor_id' => 'Doctor ID',
            'doctor_name' => 'Doctor Name',
            'doctor_email' => 'Doctor Email',
            'reason' => 'Reason',
            'notes' => 'Notes',
            'presenting_complaint' => 'Presenting Complaint',
            'presenting_complaints' => 'Presenting Complaints',
            'history_of_presenting_complaint' => 'History Of Presenting Complaint',
            'review_of_systems' => 'Review Of Systems',
            'past_medical_history' => 'Past Medical History',
            'past_surgical_history' => 'Past Surgical History',
            'growth_and_development' => 'Growth And Development',
            'immunization_history' => 'Immunization History',
            'family_history' => 'Family History',
            'social_history' => 'Social History',
            'summary_of_history' => 'Summary Of History',
            'differential_diagnosis' => 'Differential Diagnosis',
            'differential_diagnoses_icd11' => 'Differential Diagnoses ICD-11',
            'investigation_results' => 'Investigation Results',
            'final_diagnosis' => 'Final Diagnosis',
            'final_diagnosis_icd11' => 'Final Diagnosis ICD-11',
            'final_treatment' => 'Final Treatment',
            'management_plan_treatment' => 'Management Plan Treatment',
            'management_plan_prescription_medications' => 'Management Plan Prescription Medications',
            'management_plan_prescription_instructions' => 'Management Plan Prescription Instructions',
            'management_plan_investigation_radiology' => 'Management Plan Investigation Radiology',
            'management_plan_investigation_laboratory' => 'Management Plan Investigation Laboratory',
            'management_plan_investigation_interventional' => 'Management Plan Investigation Interventional',
            'management_plan_referrals' => 'Management Plan Referrals',
            'management_plan_selected_categories' => 'Management Plan Selected Categories',
            'in_person_visit_revisit_history' => 'In Person Visit Revisit History',
            'in_person_visit_general_examination' => 'In Person Visit General Examination',
            'in_person_visit_system_examination' => 'In Person Visit System Examination',
            'outcome_doctor_notes' => 'Outcome Doctor Notes',
            'outcome_patient_reports_improved' => 'Outcome Patient Reports Improved',
            'outcome_patient_reported_at' => 'Outcome Patient Reported At',
            'patient_investigation_uploads' => 'Patient Investigation Uploads',
            'metadata_json' => 'Metadata JSON',
            'clinical_notes_json' => 'Clinical Notes JSON',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        return Consultation::query()
            ->with(['patient', 'doctor'])
            ->when(! empty($filters['patient_id']), fn (Builder $q) => $q->where('patient_id', (int) $filters['patient_id']))
            ->when(! empty($filters['doctor_id']), fn (Builder $q) => $q->where('doctor_id', (int) $filters['doctor_id']))
            ->when(! empty($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['consultation_type']), fn (Builder $q) => $q->where('consultation_type', $filters['consultation_type']))
            ->latest('scheduled_at');
    }

    private function formatPresentingComplaintsForExport(mixed $value): ?string
    {
        if (! is_array($value) || $value === []) {
            return null;
        }
        $lines = [];
        foreach ($value as $item) {
            if (is_string($item)) {
                $t = trim($item);
                if ($t !== '') {
                    $lines[] = $t;
                }

                continue;
            }
            if (is_array($item)) {
                $c = isset($item['complaint']) && is_string($item['complaint']) ? trim($item['complaint']) : '';
                $d = isset($item['duration']) && is_string($item['duration']) ? trim($item['duration']) : '';
                if ($c === '' && $d === '') {
                    continue;
                }
                if ($c === '') {
                    $lines[] = '(duration: '.$d.')';
                } elseif ($d === '') {
                    $lines[] = $c;
                } else {
                    $lines[] = $c.' (duration: '.$d.')';
                }
            }
        }
        if ($lines === []) {
            return null;
        }
        if (count($lines) === 1) {
            return $lines[0];
        }
        $numbered = [];
        foreach ($lines as $i => $line) {
            $numbered[] = ($i + 1).'. '.$line;
        }

        return implode("\n", $numbered);
    }

    private function formatReviewOfSystemsForExport(mixed $value): ?string
    {
        if (is_string($value)) {
            $t = trim($value);

            return $t === '' ? null : $t;
        }
        if (! is_array($value) || $value === []) {
            return null;
        }
        $defs = [
            'cns' => 'Central nervous system',
            'respiratory' => 'Respiratory system',
            'cardiovascular' => 'Cardiovascular system',
            'digestive' => 'Digestive system',
            'genitourinary' => 'Genital–urinary system',
            'locomotor' => 'Locomotor system',
            'other' => 'Other systems',
        ];
        $parts = [];
        foreach ($defs as $key => $label) {
            $t = isset($value[$key]) && is_string($value[$key]) ? trim($value[$key]) : '';
            if ($t !== '') {
                $parts[] = $label.': '.$t;
            }
        }

        return $parts === [] ? null : implode("\n\n", $parts);
    }

    private function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            return trim($value) === '' ? null : $value;
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function formatPrescriptionMedications(mixed $medications): ?string
    {
        if (! is_array($medications) || $medications === []) {
            return null;
        }

        $lines = [];
        foreach ($medications as $med) {
            if (! is_array($med) || empty(trim((string) ($med['name'] ?? '')))) {
                continue;
            }
            $line = trim((string) $med['name']);
            if (! empty($med['form'])) {
                $line .= ' ('.$med['form'].')';
            }
            $parts = [];
            if (! empty($med['dosage'])) {
                $parts[] = $med['dosage'];
            }
            if (! empty($med['frequency'])) {
                $parts[] = $med['frequency'];
            }
            if (! empty($med['duration'])) {
                $parts[] = $med['duration'];
            }
            if ($parts !== []) {
                $line .= ' - '.implode(', ', $parts);
            }
            if (! empty($med['instructions'])) {
                $line .= ' | Instructions: '.$med['instructions'];
            }
            $lines[] = $line;
        }

        return $lines === [] ? null : implode('; ', $lines);
    }
}
