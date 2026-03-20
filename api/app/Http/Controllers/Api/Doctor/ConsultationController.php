<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConsultationResource;
use App\Models\Consultation;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsultationController extends Controller
{
    public function index(Request $request)
    {
        $doctorId = $request->user()->id;

        $perPage = min((int) $request->get('per_page', 15), 100);

        $query = Consultation::query()
            ->with('patient')
            ->where('doctor_id', $doctorId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('scheduled_at');

        $consultations = $query->paginate($perPage);

        return ConsultationResource::collection($consultations);
    }

    public function show(Request $request, Consultation $consultation)
    {
        abort_unless(
            (int) $consultation->doctor_id === (int) $request->user()->id,
            403,
            'You do not have access to this consultation.'
        );

        $consultation->load(['patient', 'prescriptions']);

        return new ConsultationResource($consultation);
    }

    public function update(Request $request, Consultation $consultation): JsonResponse
    {
        abort_unless(
            (int) $consultation->doctor_id === (int) $request->user()->id,
            403,
            'You do not have access to this consultation.'
        );

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['scheduled', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:65535'],
            'metadata' => ['nullable', 'array'],
            'clinical_notes' => ['nullable', 'array'],
            'clinical_notes.presenting_complaint' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.history_of_presenting_complaint' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.review_of_systems' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.past_medical_history' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.past_surgical_history' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.growth_and_development' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.immunization_history' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.family_history' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.social_history' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.summary_of_history' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.differential_diagnosis' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.management_plan' => ['nullable', 'array'],
            'clinical_notes.management_plan.treatment' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.management_plan.investigation_radiology' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.management_plan.investigation_laboratory' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.management_plan.investigation_interventional' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.management_plan.referrals' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.management_plan.in_person_visit' => ['nullable', 'array'],
            'clinical_notes.management_plan.in_person_visit.revisit_history' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.management_plan.in_person_visit.general_examination' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value === null) return;
                    if (!is_string($value) && !is_array($value)) {
                        $fail('The ' . $attribute . ' must be a string or an object.');
                    }
                }
            ],
            'clinical_notes.management_plan.in_person_visit.general_examination.appearance' => ['nullable', 'string', 'in:Good,Sick,Very sick'],
            'clinical_notes.management_plan.in_person_visit.general_examination.jaundice' => ['nullable', 'string', 'in:Nil,Mild,Severe'],
            'clinical_notes.management_plan.in_person_visit.general_examination.anemia' => ['nullable', 'string', 'in:Present,Absent'],
            'clinical_notes.management_plan.in_person_visit.general_examination.cyanosis' => ['nullable', 'string', 'in:Present,Absent'],
            'clinical_notes.management_plan.in_person_visit.general_examination.clubbing' => ['nullable', 'string', 'in:Present,Absent'],
            'clinical_notes.management_plan.in_person_visit.general_examination.oedema' => ['nullable', 'string', 'in:Grade I,Grade II,Grade III,Grade IV'],
            'clinical_notes.management_plan.in_person_visit.general_examination.lymphadenopathy' => ['nullable', 'string', 'in:Nil,Present'],
            'clinical_notes.management_plan.in_person_visit.general_examination.dehydration' => ['nullable', 'string', 'in:Nil,Some,Severe'],
            'clinical_notes.management_plan.in_person_visit.system_examination' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.management_plan.selected_categories' => ['nullable', 'array'],
            'clinical_notes.management_plan.selected_categories.*' => ['string', 'in:treatment,investigation_radiology,investigation_laboratory,investigation_interventional,referrals,in_person_visit'],
            'clinical_notes.final_diagnosis_icd11' => ['nullable', 'array'],
            'clinical_notes.final_diagnosis_icd11.code' => ['nullable', 'string', 'max:64'],
            'clinical_notes.final_diagnosis_icd11.title' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.differential_diagnoses_icd11' => ['nullable', 'array'],
            'clinical_notes.differential_diagnoses_icd11.*.code' => ['nullable', 'string', 'max:64'],
            'clinical_notes.differential_diagnoses_icd11.*.title' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.final_diagnosis' => ['nullable', 'string', 'max:65535'],
        ]);

        $oldStatus = $consultation->status;
        $consultation->update($validated);

        if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
            $action = $validated['status'] === 'completed' ? 'consultation.completed' : ($validated['status'] === 'cancelled' ? 'consultation.cancelled_by_doctor' : 'consultation.status_updated');
            app(AuditLogService::class)->log(
                $request->user(),
                $action,
                'Doctor marked consultation #' . $consultation->id . ' as ' . $validated['status'],
                Consultation::class,
                $consultation->id,
                ['old_status' => $oldStatus, 'new_status' => $validated['status']]
            );
        }

        return response()->json([
            'data' => (new ConsultationResource($consultation->load(['patient', 'prescriptions'])))->toArray($request),
        ]);
    }
}

