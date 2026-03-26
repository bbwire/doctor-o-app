<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListConsultationsRequest;
use App\Http\Requests\Admin\StoreConsultationRequest;
use App\Http\Requests\Admin\UpdateConsultationRequest;
use App\Http\Resources\ConsultationResource;
use App\Models\Consultation;
use App\Services\AuditLogService;
use App\Services\ConsultationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConsultationController extends Controller
{
    public function __construct(private readonly ConsultationService $consultationService) {}

    public function index(ListConsultationsRequest $request): AnonymousResourceCollection
    {
        $consultations = $this->consultationService->paginate($request->validated());

        return ConsultationResource::collection($consultations);
    }

    public function store(StoreConsultationRequest $request): JsonResponse
    {
        return (new ConsultationResource($this->consultationService->create($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Consultation $consultation): ConsultationResource
    {
        return new ConsultationResource($consultation->load(['patient', 'doctor', 'prescriptions', 'messages']));
    }

    public function update(UpdateConsultationRequest $request, Consultation $consultation): ConsultationResource
    {
        return new ConsultationResource($this->consultationService->update($consultation, $request->validated()));
    }

    public function destroy(Consultation $consultation): JsonResponse
    {
        $id = $consultation->id;
        $this->consultationService->delete($consultation);
        app(AuditLogService::class)->log(
            request()->user(),
            'consultation.deleted_by_admin',
            'Admin deleted consultation #' . $id,
            Consultation::class,
            $id,
            []
        );
        return response()->json(['message' => 'Consultation deleted successfully']);
    }
}
