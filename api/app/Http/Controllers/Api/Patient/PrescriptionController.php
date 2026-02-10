<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\ListPrescriptionsRequest;
use App\Http\Resources\PrescriptionResource;
use App\Services\PatientPrescriptionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PrescriptionController extends Controller
{
    public function __construct(private readonly PatientPrescriptionService $patientPrescriptionService) {}

    public function index(ListPrescriptionsRequest $request): AnonymousResourceCollection
    {
        $prescriptions = $this->patientPrescriptionService->paginateForPatient($request->user(), $request->validated());

        return PrescriptionResource::collection($prescriptions);
    }
}
