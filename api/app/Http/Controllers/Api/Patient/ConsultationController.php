<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\ListConsultationsRequest;
use App\Http\Requests\Patient\RescheduleConsultationRequest;
use App\Http\Requests\Patient\StoreConsultationRequest;
use App\Http\Resources\ConsultationResource;
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
        return (new ConsultationResource(
            $this->patientConsultationService->createForPatient($request->user(), $request->validated())
        ))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $consultationId): ConsultationResource
    {
        return new ConsultationResource(
            $this->patientConsultationService->findForPatientOrFail($request->user(), $consultationId)
        );
    }

    public function cancel(Request $request, int $consultationId): ConsultationResource
    {
        return new ConsultationResource(
            $this->patientConsultationService->cancelForPatient($request->user(), $consultationId)
        );
    }

    public function reschedule(RescheduleConsultationRequest $request, int $consultationId): ConsultationResource
    {
        return new ConsultationResource(
            $this->patientConsultationService->rescheduleForPatient($request->user(), $consultationId, $request->validated())
        );
    }
}
