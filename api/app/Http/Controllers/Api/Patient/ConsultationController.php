<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\ListConsultationsRequest;
use App\Http\Requests\Patient\RescheduleConsultationRequest;
use App\Http\Requests\Patient\StoreConsultationRequest;
use App\Http\Requests\Patient\SubmitConsultationOutcomeRequest;
use App\Http\Resources\ConsultationResource;
use App\Services\AuditLogService;
use App\Services\PatientConsultationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConsultationController extends Controller
{
    public function __construct(private readonly PatientConsultationService $patientConsultationService) {}

    public function index(ListConsultationsRequest $request): AnonymousResourceCollection
    {
        $consultations = $this->patientConsultationService->paginateForPatient($request->user(), $request->validated());

        return ConsultationResource::collection($consultations);
    }

    public function store(StoreConsultationRequest $request): JsonResponse
    {
        $consultation = $this->patientConsultationService->createForPatient($request->user(), $request->validated());
        app(AuditLogService::class)->log(
            $request->user(),
            'consultation.booked',
            'Patient booked consultation #'.$consultation->id.' for '.$consultation->scheduled_at,
            \App\Models\Consultation::class,
            $consultation->id,
            ['doctor_id' => $consultation->doctor_id, 'consultation_type' => $consultation->consultation_type ?? null]
        );

        return (new ConsultationResource($consultation))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $consultationId): ConsultationResource
    {
        $consultation = $this->patientConsultationService->findForPatientOrFail($request->user(), $consultationId);
        $consultation->loadMissing(['messages', 'prescriptions']);

        return new ConsultationResource($consultation);
    }

    public function cancel(Request $request, int $consultationId): ConsultationResource
    {
        $consultation = $this->patientConsultationService->cancelForPatient($request->user(), $consultationId);
        app(AuditLogService::class)->log(
            $request->user(),
            'consultation.cancelled',
            'Patient cancelled consultation #'.$consultation->id,
            \App\Models\Consultation::class,
            $consultation->id,
            ['scheduled_at' => $consultation->scheduled_at]
        );

        return new ConsultationResource($consultation);
    }

    public function reschedule(RescheduleConsultationRequest $request, int $consultationId): ConsultationResource
    {
        $consultation = $this->patientConsultationService->rescheduleForPatient($request->user(), $consultationId, $request->validated());
        app(AuditLogService::class)->log(
            $request->user(),
            'consultation.rescheduled',
            'Patient rescheduled consultation #'.$consultation->id.' to '.$consultation->scheduled_at,
            \App\Models\Consultation::class,
            $consultation->id,
            ['doctor_id' => $consultation->doctor_id]
        );

        return new ConsultationResource($consultation);
    }

    /**
     * Patient follow-up: whether symptoms improved (stored under clinical_notes.outcome).
     */
    public function submitOutcome(SubmitConsultationOutcomeRequest $request, int $consultationId): ConsultationResource
    {
        $consultation = $this->patientConsultationService->findForPatientOrFail($request->user(), $consultationId);

        abort_unless($consultation->status === 'completed', 422, 'Outcome feedback is only available after the consultation is completed.');

        $validated = $request->validated();
        $notes = is_array($consultation->clinical_notes) ? $consultation->clinical_notes : [];
        $outcome = is_array($notes['outcome'] ?? null) ? $notes['outcome'] : [];
        $outcome['patient_reports_improved'] = $validated['patient_reports_improved'];
        $outcome['patient_reported_at'] = now()->toISOString();
        $notes['outcome'] = $outcome;
        $consultation->update(['clinical_notes' => $notes]);

        return new ConsultationResource($consultation->loadMissing(['messages']));
    }
}
